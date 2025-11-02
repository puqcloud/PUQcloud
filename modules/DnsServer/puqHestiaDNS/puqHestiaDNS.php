<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Dmytro Kravchenko <dmytro@kravchenko.im>
 * Website: https://puqcloud.com
 * E-mail: support@puqcloud.com
 *
 * Do not remove this header.
 */

use App\Models\DnsRecord;
use App\Models\DnsZone;
use App\Models\Task;
use App\Modules\DnsServer;
use Illuminate\Support\Facades\Validator;

class puqHestiaDNS extends DnsServer
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        $this->module_data = [
            'server' => $data['server'] ?? '',
            'username' => $data['username'] ?? '',
            'access_key' => $data['access_key'] ?? '',
            'secret_key' => $data['secret_key'] ?? '',
        ];

        return $this->module_data;
    }

    public function getSettingsPage(array $data = []): string
    {
        $data['admin'] = app('admin');
        $data['module_type'] = $this->module_type;
        $data['module_name'] = $this->module_name;

        return $this->view('configuration', $data);
    }

    public function saveModuleData(array $data = []): array
    {
        $validator = Validator::make($data, [
            'server' => 'required|string',
            'username' => 'required|string',
            'access_key' => 'required|string',
            'secret_key' => 'required|string',
        ], [
            'server.required' => __('DnsServer.puqHestiaDNS.The Server field is required'),
            'server.string' => __('DnsServer.puqHestiaDNS.The Server must be a valid string'),
            'username.required' => __('DnsServer.puqHestiaDNS.The Username field is required'),
            'username.string' => __('DnsServer.puqHestiaDNS.The Username must be a valid string'),
            'access_key.required' => __('DnsServer.puqHestiaDNS.The Access Key field is required'),
            'access_key.string' => __('DnsServer.puqHestiaDNS.The Access Key must be a valid string'),
            'secret_key.required' => __('DnsServer.puqHestiaDNS.The Secret Key field is required'),
            'secret_key.string' => __('DnsServer.puqHestiaDNS.The Secret Key must be a valid string'),
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'code' => 422,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
            'code' => 200,
        ];

    }

    public function adminApiRoutes(): array
    {
        return [];
    }

    public function queues(): array
    {
        return [
            'Queue' => [
                'connection' => 'redis',
                'queue' => ['Queue'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 1,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 3600,
                'nice' => 0,
            ],
        ];
    }

    public function testConnection(): array
    {
        $client = new puqHestiaDnsClient($this->module_data);
        $response = $client->testConnection();

        if ($response['status'] == 'error') {
            return $response;
        }

        $data = $response['data'];
        $user = $data['user'] ?? $data['username'] ?? 'unknown';
        $html = '
    <div class="text-center my-3">
        <div class="d-inline-block">
            <i class="fas fa-check-circle text-success"
               style="font-size: 64px; animation: pulse 1.2s ease-in-out infinite;"></i>
        </div>
        <div class="mt-2">
            <h5 class="text-success fw-bold mb-0">'.__('DnsServer.puqHestiaDNS.Server is available').'</h5>
            <div class="text-muted small">'.__('DnsServer.puqHestiaDNS.Connected as user').': <b>'.$user.'</b></div>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.15); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
    ';

        return [
            'status' => 'success',
            'data' => $html,
        ];
    }

    // Zone actions ------------------------------------------------
    
    /**
     * Get list of DNS zones from HestiaCP server
     */
    public function getZones(): array
    {
        $client = new puqHestiaDnsClient($this->module_data);
        $zones = [];
        $remote_zones = $client->listDnsDomains();

        if ($remote_zones['status'] == 'error') {
            return $remote_zones;
        }

        // HestiaCP returns domains as object keys: {"domain1.com": {...}, "domain2.com": {...}}
        // Extract domain names from keys
        foreach ($remote_zones['data'] as $domain => $domainData) {
            $zones[] = rtrim($domain, '.');
        }
        sort($zones);

        return [
            'status' => 'success',
            'data' => $zones,
        ];
    }

    /**
     * Get DNS records for specific zone from HestiaCP server
     */
    public function getZoneRecords($zone_name): array
    {
        $client = new puqHestiaDnsClient($this->module_data);
        $records = [];
        $remote_zone_records = $client->listDnsRecords($zone_name);

        if ($remote_zone_records['status'] == 'error') {
            return $remote_zone_records;
        }

        // HestiaCP returns records as: {"1": {"RECORD": "@", "TYPE": "A", "VALUE": "1.2.3.4", "TTL": "3600", ...}}
        foreach ($remote_zone_records['data'] as $recordId => $hestiaRecord) {
            $recordName = $hestiaRecord['RECORD'] ?? '';
            $recordType = $hestiaRecord['TYPE'] ?? '';
            $recordValue = $hestiaRecord['VALUE'] ?? '';
            $recordTtl = (int)($hestiaRecord['TTL'] ?? 3600);
            $recordPriority = isset($hestiaRecord['PRIORITY']) && $hestiaRecord['PRIORITY'] !== '' ? (int)$hestiaRecord['PRIORITY'] : null;

            // Skip SOA and NS records - they are managed separately by zone settings
            if (in_array($recordType, ['SOA', 'NS'])) {
                continue;
            }

            // Handle record name - convert zone apex to @
            if ($recordName === $zone_name || $recordName === '@') {
                $recordName = '@';
            }

            // Handle MX records with priority
            if ($recordType === 'MX' && $recordPriority !== null) {
                $recordValue = $recordPriority . ' ' . $recordValue;
            }
            
            // Handle SRV records - HestiaCP already includes priority in VALUE field
            // SRV format in HestiaCP: "weight port target" (e.g., "0 587 mail.domain.com.")
            // We don't need to modify SRV records as they're already in correct format

            $records[] = [
                'name' => $recordName,
                'type' => $recordType,
                'ttl' => $recordTtl,
                'content' => $recordValue,
            ];
        }

        return [
            'status' => 'success',
            'data' => $records,
        ];
    }

    public function createZone($uuid): array
    {
        $this->logInfo('createZone - Start', ['uuid' => $uuid], []);
        
        $data = [
            'module' => $this,
            'method' => 'createZoneJob',
            'callback' => '',
            'tries' => 5,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
            'params' => [$uuid],
        ];

        $tags = [
            'createZone',
        ];

        Task::add('ModuleJob', 'puqHestiaDNS-Queue', $data, $tags);
        
        $this->logInfo('createZone - Task Added to Queue', ['uuid' => $uuid, 'queue' => 'puqHestiaDNS-Queue'], []);

        return ['status' => 'success'];
    }

    public function createZoneJob($uuid): array
    {
        $this->logInfo('createZoneJob - Start', ['uuid' => $uuid], []);
        
        $model_zone = DnsZone::where('uuid', $uuid)->first();
        if (empty($model_zone)) {
            $this->logError('createZoneJob - Zone Not Found', ['uuid' => $uuid], ['error' => 'Zone not found in database']);
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        $this->logInfo('createZoneJob - Zone Found', [
            'uuid' => $uuid,
            'name' => $model_zone->name,
            'records_count' => $model_zone->dnsRecords->count()
        ]);

        $client = new puqHestiaDnsClient($this->module_data);
        
        // Get zone IP (use first A record or default IP)
        $ip = $this->getZoneDefaultIp($model_zone);
        
        // Get nameservers
        $ns = $model_zone->getNameServers();
        $nameservers = $ns['servers'] ?? [];
        
        // Decode JSON if needed
        if (is_string($nameservers)) {
            $nameservers = json_decode($nameservers, true) ?? [];
        }

        $this->logInfo('createZoneJob - Prepared Zone Data', [
            'domain' => $model_zone->name,
            'ip' => $ip,
            'nameservers' => $nameservers
        ]);

        // Create DNS domain in HestiaCP
        $result = $client->addDnsDomain($model_zone->name, $ip, $nameservers, false);

        $this->logInfo('createZoneJob - addDnsDomain Result', ['result' => $result], []);

        if ($result['status'] == 'error') {
            $this->logError('createZoneJob - Failed to Create Domain', [
                'domain' => $model_zone->name
            ], $result);
            return $result;
        }

        // Wait a moment for HestiaCP to create all auto-records
        sleep(2);
        
        // FIRST: Ensure we have a default A record for the domain (@)
        $hasDomainARecord = false;
        foreach ($model_zone->dnsRecords as $record) {
            if ($record->name === '@' && $record->type === 'A') {
                $hasDomainARecord = true;
                break;
            }
        }

        if (!$hasDomainARecord) {
            $this->logInfo('createZoneJob - Creating Default A Record for Domain HestiaDNS module', [
                'domain' => $model_zone->name,
                'ip' => $ip
            ]);

            // Create default A record in panel first
            $panelRecord = new \App\Models\DnsRecord();
            $panelRecord->dns_zone_uuid = $model_zone->uuid;
            $panelRecord->name = '@';
            $panelRecord->type = 'A';
            $panelRecord->content = $ip;
            $panelRecord->ttl = 3600;
            $panelRecord->description = 'Default A record for domain HestiaDNS module';
            $panelRecord->save();

            $this->logInfo('createZoneJob - Default A Record Created in Panel', [
                'domain' => $model_zone->name,
                'record_uuid' => $panelRecord->uuid
            ]);

            // Create default A record on HestiaCP
            $defaultARecordResult = $client->addDnsRecord(
                $model_zone->name,
                '@',
                'A',
                $ip,
                null,
                null,
                false,
                3600
            );

            if ($defaultARecordResult['status'] === 'success') {
                $this->logInfo('createZoneJob - Default A Record Created on HestiaCP', [
                    'domain' => $model_zone->name
                ]);
            } else {
                $this->logError('createZoneJob - Failed to Create Default A Record on HestiaCP', [
                    'domain' => $model_zone->name
                ], $defaultARecordResult);
            }
        }
        
        // SECOND: Get all automatically created records from HestiaCP
        $hestiaRecords = $client->listDnsRecords($model_zone->name);
        
        if ($hestiaRecords['status'] === 'success' && isset($hestiaRecords['data'])) {
            $this->logInfo('createZoneJob - Found Auto-Created Records', [
                'count' => count($hestiaRecords['data'])
            ]);
            
            // Delete all automatically created records (except NS records)
            $deletedCount = 0;
            foreach ($hestiaRecords['data'] as $recordId => $hestiaRecord) {
                // Skip NS records - they are managed separately
                if ($hestiaRecord['TYPE'] === 'NS') {
                    continue;
                }
                
                $this->logInfo('createZoneJob - Deleting Auto-Created Record', [
                    'id' => $recordId,
                    'type' => $hestiaRecord['TYPE'],
                    'record' => $hestiaRecord['RECORD']
                ]);
                
                $deleteResult = $client->deleteDnsRecord($model_zone->name, $recordId, false);
                if ($deleteResult['status'] === 'success') {
                    $deletedCount++;
                } else {
                    $this->logError('createZoneJob - Failed to Delete Auto-Created Record', [
                        'id' => $recordId,
                        'type' => $hestiaRecord['TYPE']
                    ], $deleteResult);
                }
            }
            
            $this->logInfo('createZoneJob - Deleted Auto-Created Records', [
                'deleted_count' => $deletedCount
            ]);
        }

        // Now add all DNS records from our panel
        $recordsAdded = 0;
        $recordsFailed = 0;
        foreach ($model_zone->dnsRecords as $record) {
            $this->logInfo('createZoneJob - Adding Panel Record', [
                'record_uuid' => $record->uuid,
                'name' => $record->name,
                'type' => $record->type
            ]);
            
            $recordResult = $this->addRecordToHestia($client, $model_zone, $record);
            
            if ($recordResult['status'] == 'success') {
                $recordsAdded++;
            } else {
                $recordsFailed++;
                $this->logError('createZoneJob - Failed to Add Panel Record', [
                    'record_uuid' => $record->uuid,
                    'name' => $record->name,
                    'type' => $record->type
                ], $recordResult);
            }
        }

        $this->logInfo('createZoneJob - Completed', [
            'domain' => $model_zone->name,
            'records_added' => $recordsAdded,
            'records_failed' => $recordsFailed
        ]);

        return ['status' => 'success'];
    }

    public function reloadZone($uuid): array
    {
        $data = [
            'module' => $this,
            'method' => 'reloadZoneJob',
            'callback' => '',
            'tries' => 5,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
            'params' => [$uuid],
        ];

        $tags = [
            'reloadZone',
        ];

        Task::add('ModuleJob', 'puqHestiaDNS-Queue', $data, $tags);

        return ['status' => 'success'];
    }

    public function reloadZoneJob($uuid): array
    {
        $this->logInfo('reloadZoneJob - Start', ['uuid' => $uuid], []);
        
        $model_zone = DnsZone::where('uuid', $uuid)->first();
        if (empty($model_zone)) {
            $this->logError('reloadZoneJob - Zone Not Found', ['uuid' => $uuid], ['error' => 'Zone not found in database']);
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        $this->logInfo('reloadZoneJob - Zone Found', [
            'uuid' => $uuid,
            'name' => $model_zone->name,
            'records_count' => $model_zone->dnsRecords->count()
        ]);

        $client = new puqHestiaDnsClient($this->module_data);
        
        // Get zone IP (use first A record or default IP)
        $ip = $this->getZoneDefaultIp($model_zone);
        
        // Get nameservers
        $ns = $model_zone->getNameServers();
        $nameservers = $ns['servers'] ?? [];
        
        // Decode JSON if needed
        if (is_string($nameservers)) {
            $nameservers = json_decode($nameservers, true) ?? [];
        }

        $this->logInfo('reloadZoneJob - Prepared Zone Data', [
            'domain' => $model_zone->name,
            'ip' => $ip,
            'nameservers' => $nameservers
        ]);

        // Check if zone exists on HestiaCP and delete it
        $hestiaDomains = $client->listDnsDomains();
        $zoneExists = false;
        
        if ($hestiaDomains['status'] === 'success' && isset($hestiaDomains['data'])) {
            $zoneExists = isset($hestiaDomains['data'][$model_zone->name]);
        }

        if ($zoneExists) {
            $this->logInfo('reloadZoneJob - Zone Exists on HestiaCP, Deleting', [
                'zone' => $model_zone->name
            ]);
            
            $deleteResult = $client->deleteDnsDomain($model_zone->name);
            if ($deleteResult['status'] !== 'success') {
                $this->logError('reloadZoneJob - Failed to Delete Zone', [
                    'zone' => $model_zone->name
                ], $deleteResult);
                return $deleteResult;
            }
        }

        // Create DNS domain in HestiaCP
        $result = $client->addDnsDomain($model_zone->name, $ip, $nameservers, false);

        $this->logInfo('reloadZoneJob - addDnsDomain Result', ['result' => $result], []);

        if ($result['status'] == 'error') {
            $this->logError('reloadZoneJob - Failed to Create Domain', [
                'domain' => $model_zone->name
            ], $result);
            return $result;
        }

        // Wait a moment for HestiaCP to create all auto-records
        sleep(2);
        
        // FIRST: Ensure we have a default A record for the domain (@)
        $hasDomainARecord = false;
        foreach ($model_zone->dnsRecords as $record) {
            if ($record->name === '@' && $record->type === 'A') {
                $hasDomainARecord = true;
                break;
            }
        }

        if (!$hasDomainARecord) {
            $this->logInfo('reloadZoneJob - Creating Default A Record for Domain HestiaDNS module', [
                'domain' => $model_zone->name,
                'ip' => $ip
            ]);

            // Create default A record in panel first
            $panelRecord = new \App\Models\DnsRecord();
            $panelRecord->dns_zone_uuid = $model_zone->uuid;
            $panelRecord->name = '@';
            $panelRecord->type = 'A';
            $panelRecord->content = $ip;
            $panelRecord->ttl = 3600;
            $panelRecord->description = 'Default A record for domain HestiaDNS module';
            $panelRecord->save();

            $this->logInfo('reloadZoneJob - Default A Record Created in Panel', [
                'domain' => $model_zone->name,
                'record_uuid' => $panelRecord->uuid
            ]);

            // Create default A record on HestiaCP
            $defaultARecordResult = $client->addDnsRecord(
                $model_zone->name,
                '@',
                'A',
                $ip,
                null,
                null,
                false,
                3600
            );

            if ($defaultARecordResult['status'] === 'success') {
                $this->logInfo('reloadZoneJob - Default A Record Created on HestiaCP', [
                    'domain' => $model_zone->name
                ]);
            } else {
                $this->logError('reloadZoneJob - Failed to Create Default A Record on HestiaCP', [
                    'domain' => $model_zone->name
                ], $defaultARecordResult);
            }
        }
        
        // SECOND: Get all automatically created records from HestiaCP
        $this->logInfo('reloadZoneJob - Getting Auto-Created Records from HestiaCP', [
            'zone' => $model_zone->name
        ]);
        
        $hestiaRecords = $client->listDnsRecords($model_zone->name);
        
        $this->logInfo('reloadZoneJob - listDnsRecords Response', [
            'zone' => $model_zone->name,
            'status' => $hestiaRecords['status'] ?? 'unknown',
            'has_data' => isset($hestiaRecords['data']),
            'data_type' => gettype($hestiaRecords['data'] ?? null),
            'data_count' => is_array($hestiaRecords['data'] ?? null) ? count($hestiaRecords['data']) : 'N/A'
        ]);
        
        if ($hestiaRecords['status'] === 'success' && isset($hestiaRecords['data'])) {
            $this->logInfo('reloadZoneJob - Found Auto-Created Records', [
                'count' => count($hestiaRecords['data'])
            ]);
            
            // Before deleting auto-created records, ensure we have at least one A record from panel
            $hasPanelARecord = false;
            $firstPanelARecord = null;
            foreach ($model_zone->dnsRecords as $record) {
                if ($record->type === 'A') {
                    $hasPanelARecord = true;
                    $firstPanelARecord = $record;
                    break;
                }
            }

            if ($hasPanelARecord && $firstPanelARecord) {
                $this->logInfo('reloadZoneJob - Creating First Panel A Record Before Cleanup', [
                    'zone' => $model_zone->name,
                    'record_name' => $firstPanelARecord->name,
                    'record_content' => $firstPanelARecord->content
                ]);

                // Create this A record FIRST to ensure we always have at least one A record
                $firstARecordResult = $this->addRecordToHestia($client, $model_zone, $firstPanelARecord);
                if ($firstARecordResult['status'] === 'success') {
                    $this->logInfo('reloadZoneJob - First Panel A Record Created Successfully', [
                        'zone' => $model_zone->name
                    ]);
                } else {
                    $this->logError('reloadZoneJob - Failed to Create First Panel A Record', [
                        'zone' => $model_zone->name
                    ], $firstARecordResult);
                }
            }
            
            // Now delete all automatically created records (except NS records)
            $deletedCount = 0;
            foreach ($hestiaRecords['data'] as $recordId => $hestiaRecord) {
                // Skip NS records - they are managed separately
                if ($hestiaRecord['TYPE'] === 'NS') {
                    continue;
                }
                
                $this->logInfo('reloadZoneJob - Deleting Auto-Created Record', [
                    'id' => $recordId,
                    'type' => $hestiaRecord['TYPE'],
                    'record' => $hestiaRecord['RECORD']
                ]);
                
                $deleteResult = $client->deleteDnsRecord($model_zone->name, $recordId, false);
                if ($deleteResult['status'] === 'success') {
                    $deletedCount++;
                } else {
                    $this->logError('reloadZoneJob - Failed to Delete Auto-Created Record', [
                        'id' => $recordId,
                        'type' => $hestiaRecord['TYPE']
                    ], $deleteResult);
                }
            }
            
            $this->logInfo('reloadZoneJob - Deleted Auto-Created Records', [
                'deleted_count' => $deletedCount
            ]);
        } else {
            $this->logError('reloadZoneJob - Failed to Get Auto-Created Records', [
                'zone' => $model_zone->name,
                'response' => $hestiaRecords
            ]);
        }

        // Now add all DNS records from our panel
        $recordsAdded = 0;
        $recordsFailed = 0;
        $skipFirstARecord = false;
        
        foreach ($model_zone->dnsRecords as $record) {
            // Skip first A record if we already created it before cleanup
            if ($record->type === 'A' && $hasPanelARecord && !$skipFirstARecord) {
                $skipFirstARecord = true;
                $this->logInfo('reloadZoneJob - Skipping First A Record (Already Created)', [
                    'record_uuid' => $record->uuid,
                    'name' => $record->name,
                    'type' => $record->type
                ]);
                continue;
            }
            
            $this->logInfo('reloadZoneJob - Adding Panel Record', [
                'record_uuid' => $record->uuid,
                'name' => $record->name,
                'type' => $record->type
            ]);
            
            $recordResult = $this->addRecordToHestia($client, $model_zone, $record);
            
            if ($recordResult['status'] == 'success') {
                $recordsAdded++;
            } else {
                $recordsFailed++;
                $this->logError('reloadZoneJob - Failed to Add Panel Record', [
                    'record_uuid' => $record->uuid,
                    'name' => $record->name,
                    'type' => $record->type
                ], $recordResult);
            }
        }

        $this->logInfo('reloadZoneJob - Completed', [
            'domain' => $model_zone->name,
            'records_added' => $recordsAdded,
            'records_failed' => $recordsFailed
        ]);

        return [
            'status' => 'success',
            'synced' => $recordsAdded,
            'message' => "Successfully synced {$recordsAdded} DNS records to HestiaCP"
        ];
    }

    /**
     * Sync DNS records from HestiaCP to local database
     */
    private function syncZoneRecords(DnsZone $model_zone): array
    {
        $this->logInfo('syncZoneRecords - Start', ['zone' => $model_zone->name], []);
        
        $client = new puqHestiaDnsClient($this->module_data);
        
        // Get records from HestiaCP
        $result = $client->listDnsRecords($model_zone->name);
        
        if ($result['status'] === 'error') {
            $this->logError('syncZoneRecords - Failed to Get Records from HestiaCP', [
                'zone' => $model_zone->name
            ], $result);
            return $result;
        }

        $hestiaRecords = $result['data'] ?? [];
        
        $this->logInfo('syncZoneRecords - Retrieved Records', [
            'zone' => $model_zone->name,
            'count' => is_array($hestiaRecords) ? count($hestiaRecords) : 0,
            'type' => gettype($hestiaRecords),
            'sample_keys' => is_array($hestiaRecords) ? implode(', ', array_slice(array_keys($hestiaRecords), 0, 5)) : 'N/A'
        ], []);

        $synced = 0;
        $errors = [];

        foreach ($hestiaRecords as $id => $hestiaRecord) {
            try {
                // Skip SOA and NS records (managed by zone settings)
                if (in_array($hestiaRecord['TYPE'], ['SOA', 'NS'])) {
                    continue;
                }

                    $recordName = $hestiaRecord['RECORD'];
                    $recordType = $hestiaRecord['TYPE'];
                    $recordValue = $hestiaRecord['VALUE'];
                    $recordTtl = (int)($hestiaRecord['TTL'] ?? 3600);
                    $recordPriority = isset($hestiaRecord['PRIORITY']) && $hestiaRecord['PRIORITY'] !== '' ? (int)$hestiaRecord['PRIORITY'] : null;
                    
                    // Format content for MX and SRV records
                    if (in_array($recordType, ['MX', 'SRV']) && $recordPriority !== null) {
                        $recordValue = $recordPriority . ' ' . $recordValue;
                    }

                // Format record name (@ for zone apex)
                if ($recordName === $model_zone->name || $recordName === '@') {
                    $recordName = '@';
                }

                // Check if record exists in database
                $existingRecord = $model_zone->dnsRecords()
                    ->where('name', $recordName)
                    ->where('type', $recordType)
                    ->where('content', $recordValue)
                    ->first();

                if ($existingRecord) {
                    // Update TTL if changed
                    if ($existingRecord->ttl != $recordTtl) {
                        $existingRecord->ttl = $recordTtl;
                        $existingRecord->save();
                        $this->logInfo('syncZoneRecords - Updated Record', [
                            'zone' => $model_zone->name,
                            'name' => $recordName,
                            'type' => $recordType,
                            'old_ttl' => $existingRecord->ttl,
                            'new_ttl' => $recordTtl
                        ], []);
                    }
                } else {
                    // Create new record in database
                    $newRecord = new DnsRecord();
                    $newRecord->dns_zone_uuid = $model_zone->uuid;
                    $newRecord->name = $recordName;
                    $newRecord->type = $recordType;
                    $newRecord->content = $recordValue;
                    $newRecord->ttl = $recordTtl;
                    $newRecord->description = 'Synced from HestiaCP';
                    $newRecord->save();

                    $this->logInfo('syncZoneRecords - Created Record', [
                        'zone' => $model_zone->name,
                        'name' => $recordName,
                        'type' => $recordType,
                        'value' => $recordValue
                    ], []);
                }

                $synced++;
            } catch (\Exception $e) {
                $errors[] = "Failed to sync record {$hestiaRecord['RECORD']}: " . $e->getMessage();
                $this->logError('syncZoneRecords - Record Sync Error', [
                    'zone' => $model_zone->name,
                    'record' => $hestiaRecord
                ], ['error' => $e->getMessage()]);
            }
        }

        $this->logInfo('syncZoneRecords - Completed', [
            'zone' => $model_zone->name,
            'synced' => $synced,
            'errors' => count($errors)
        ], []);

        if (!empty($errors)) {
            return [
                'status' => 'error',
                'errors' => $errors,
                'synced' => $synced,
            ];
        }

        return [
            'status' => 'success',
            'synced' => $synced,
            'message' => "Successfully synced {$synced} DNS records from HestiaCP",
        ];
    }

    public function deleteZone($name): array
    {
        $this->logInfo('deleteZone - Start', ['zone_name' => $name], []);
        
        $data = [
            'module' => $this,
            'method' => 'deleteZoneJob',
            'callback' => '',
            'tries' => 5,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
            'params' => [$name],
        ];

        $tags = [
            'deleteZone',
        ];

        $this->logInfo('deleteZone - Adding task to queue', [
            'zone_name' => $name,
            'queue' => 'puqHestiaDNS-Queue',
            'method' => 'deleteZoneJob'
        ], []);

        Task::add('ModuleJob', 'puqHestiaDNS-Queue', $data, $tags);
        
        $this->logInfo('deleteZone - Task added successfully', [
            'zone_name' => $name,
            'queue' => 'puqHestiaDNS-Queue'
        ], []);

        return ['status' => 'success'];
    }

    public function deleteZoneJob($name): array
    {
        $this->logInfo('deleteZoneJob - Start', ['zone_name' => $name], []);
        
        $client = new puqHestiaDnsClient($this->module_data);
        
        // First check if zone exists on HestiaCP
        $this->logInfo('deleteZoneJob - Checking if zone exists', ['zone_name' => $name], []);
        $hestiaDomains = $client->listDnsDomains();
        
        if ($hestiaDomains['status'] === 'error') {
            $this->logError('deleteZoneJob - Failed to list domains', [
                'zone_name' => $name
            ], $hestiaDomains);
            return $hestiaDomains;
        }
        
        $zoneExists = false;
        if (isset($hestiaDomains['data']) && is_array($hestiaDomains['data'])) {
            $zoneExists = isset($hestiaDomains['data'][$name]);
            $this->logInfo('deleteZoneJob - Zone existence check', [
                'zone_name' => $name,
                'exists' => $zoneExists,
                'available_domains' => array_keys($hestiaDomains['data'])
            ], []);
        } else {
            $this->logError('deleteZoneJob - Invalid domains response', [
                'zone_name' => $name,
                'response' => $hestiaDomains
            ], []);
            return [
                'status' => 'error',
                'errors' => ['Invalid response from HestiaCP'],
                'code' => 500,
            ];
        }
        
        if (!$zoneExists) {
            $this->logInfo('deleteZoneJob - Zone does not exist on HestiaCP', [
                'zone_name' => $name
            ], []);
            return [
                'status' => 'success',
                'message' => 'Zone does not exist on HestiaCP server',
            ];
        }
        
        // Delete the zone
        $this->logInfo('deleteZoneJob - Attempting to delete zone', ['zone_name' => $name], []);
        $result = $client->deleteDnsDomain($name);
        
        $this->logInfo('deleteZoneJob - Delete result', [
            'zone_name' => $name,
            'result' => $result
        ], []);
        
        if ($result['status'] === 'error') {
            $this->logError('deleteZoneJob - Failed to delete zone', [
                'zone_name' => $name
            ], $result);
        } else {
            $this->logInfo('deleteZoneJob - Successfully deleted zone', [
                'zone_name' => $name
            ], []);
        }
        
        return $result;
    }

    // Record actions ----------------------------------------------
    public function createRecord(string $uuid): array
    {
        $this->logInfo('createRecord - Start', ['uuid' => $uuid], []);
        
        $data = [
            'module' => $this,
            'method' => 'createRecordJob',
            'callback' => '',
            'tries' => 5,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
            'params' => [$uuid],
        ];

        $tags = [
            'createRecord',
        ];

        Task::add('ModuleJob', 'puqHestiaDNS-Queue', $data, $tags);
        
        $this->logInfo('createRecord - Task Added to Queue', ['uuid' => $uuid, 'queue' => 'puqHestiaDNS-Queue'], []);

        return ['status' => 'success'];
    }

    public function createRecordJob(string $uuid): array
    {
        $this->logInfo('createRecordJob - Start', ['uuid' => $uuid], []);
        
        $record = DnsRecord::where('uuid', $uuid)->first();
        if (empty($record)) {
            $this->logError('createRecordJob - Record Not Found', ['uuid' => $uuid], ['error' => 'Record not found in database']);
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }
        
        $this->logInfo('createRecordJob - Record Found', [
            'uuid' => $uuid,
            'name' => $record->name,
            'type' => $record->type,
            'content' => $record->content
        ]);
        
        $model_zone = $record->dnsZone;
        $this->logInfo('createRecordJob - Zone Info', [
            'zone_name' => $model_zone->name,
            'zone_uuid' => $model_zone->uuid
        ]);
        
        $client = new puqHestiaDnsClient($this->module_data);

        $result = $this->addRecordToHestia($client, $model_zone, $record);
        
        $this->logInfo('createRecordJob - Result', ['result' => $result], []);
        
        return $result;
    }

    public function updateRecord(string $uuid, string $old_content): array
    {
        $this->logInfo('updateRecord - Start', ['uuid' => $uuid], []);
        
        $data = [
            'module' => $this,
            'method' => 'updateRecordJob',
            'callback' => '',
            'tries' => 5,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
            'params' => [$uuid, $old_content],
        ];

        $tags = [
            'updateRecord',
        ];

        Task::add('ModuleJob', 'puqHestiaDNS-Queue', $data, $tags);
        
        $this->logInfo('updateRecord - Task Added to Queue', ['uuid' => $uuid], []);

        return ['status' => 'success'];
    }

    public function updateRecordJob(string $uuid, string $old_content): array
    {
        $this->logInfo('updateRecordJob - Start', [
            'uuid' => $uuid,
            'old_content' => $old_content
        ], []);
        
        $record = DnsRecord::where('uuid', $uuid)->first();
        if (empty($record)) {
            $this->logError('updateRecordJob - Record Not Found', ['uuid' => $uuid], ['error' => 'Record not found in database']);
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        $this->logInfo('updateRecordJob - Record Found', [
            'uuid' => $uuid,
            'name' => $record->name,
            'type' => $record->type,
            'old_content' => $old_content,
            'new_content' => $record->content
        ], []);

        $model_zone = $record->dnsZone;
        $client = new puqHestiaDnsClient($this->module_data);

        // Find record by OLD content (before update)
        $recordName = $this->formatRecordName($record->name);
        $recordId = $this->findRecordIdByData($client, $model_zone->name, $record->name, $record->type, $old_content);
        
        if (!$recordId) {
            $this->logInfo('updateRecordJob - Record Not Found on HestiaCP, Creating', [
                'zone' => $model_zone->name,
                'name' => $record->name,
                'type' => $record->type,
                'old_content' => $old_content
            ], []);
            // Record doesn't exist, create it
            return $this->addRecordToHestia($client, $model_zone, $record);
        }

        // Check if this is the last A record scenario
        $isLastARecord = false;
        if ($record->type === 'A') {
            $allRecordsResult = $client->listDnsRecords($model_zone->name);
            if ($allRecordsResult['status'] === 'success') {
                $aRecordCount = 0;
                foreach ($allRecordsResult['data'] as $r) {
                    if ($r['TYPE'] === 'A') $aRecordCount++;
                }
                $isLastARecord = ($aRecordCount === 1);
                
                $this->logInfo('updateRecordJob - A Record Analysis', [
                    'zone' => $model_zone->name,
                    'total_a_records' => $aRecordCount,
                    'is_last_a_record' => $isLastARecord,
                    'strategy' => $isLastARecord ? 'add_then_delete' : 'delete_then_add'
                ], []);
            }
        }

        if ($isLastARecord) {
            // Strategy: ADD new record FIRST, then DELETE old
            $this->logInfo('updateRecordJob - Using ADD→DELETE Strategy for Last A Record', [
                'zone' => $model_zone->name,
                'record_id' => $recordId,
                'name' => $record->name,
                'type' => $record->type,
                'old_content' => $old_content,
                'new_content' => $record->content
            ], []);

            // Create new record first
            $addResult = $this->addRecordToHestia($client, $model_zone, $record);
            if ($addResult['status'] !== 'success') {
                $this->logError('updateRecordJob - Failed to Add New Record (ADD→DELETE Strategy)', [
                    'zone' => $model_zone->name,
                    'name' => $record->name,
                    'type' => $record->type,
                    'new_content' => $record->content
                ], $addResult);
                return $addResult;
            }

            $this->logInfo('updateRecordJob - New Record Added, Now Deleting Old', [
                'zone' => $model_zone->name,
                'record_id' => $recordId,
                'name' => $record->name,
                'type' => $record->type,
                'old_content' => $old_content
            ], []);

            // Now delete old record (it's no longer the last A record)
            $deleteResult = $client->deleteDnsRecord($model_zone->name, $recordId, false);
            if ($deleteResult['status'] !== 'success') {
                $this->logError('updateRecordJob - Failed to Delete Old Record After Adding New', [
                    'zone' => $model_zone->name,
                    'record_id' => $recordId
                ], $deleteResult);
                // Don't return error - new record was created successfully
            }

            $this->logInfo('updateRecordJob - ADD→DELETE Strategy Completed', [
                'zone' => $model_zone->name,
                'add_result' => $addResult,
                'delete_result' => $deleteResult
            ], []);
            
            return $addResult;
        } else {
            // Normal strategy: DELETE old, then ADD new
            $this->logInfo('updateRecordJob - Using DELETE→ADD Strategy', [
                'zone' => $model_zone->name,
                'record_id' => $recordId,
                'name' => $record->name,
                'type' => $record->type,
                'old_content' => $old_content,
                'new_content' => $record->content
            ], []);

            // Delete old record first
            $deleteResult = $client->deleteDnsRecord($model_zone->name, $recordId, false);
            if ($deleteResult['status'] !== 'success') {
                $this->logError('updateRecordJob - Failed to Delete Old Record (DELETE→ADD Strategy)', [
                    'zone' => $model_zone->name,
                    'record_id' => $recordId
                ], $deleteResult);
                return $deleteResult;
            }

            $this->logInfo('updateRecordJob - Old Record Deleted, Creating New', [
                'zone' => $model_zone->name,
                'name' => $record->name,
                'type' => $record->type,
                'new_content' => $record->content
            ], []);

            // Create new record with updated content
            $result = $this->addRecordToHestia($client, $model_zone, $record);
            
            $this->logInfo('updateRecordJob - DELETE→ADD Strategy Completed', [
                'zone' => $model_zone->name,
                'result' => $result
            ], []);
            
            return $result;
        }
    }

    public function deleteRecord(string $zone_uuid, string $name, string $type, string $content): array
    {
        $this->logInfo('deleteRecord - Start', [
            'zone_uuid' => $zone_uuid,
            'name' => $name,
            'type' => $type
        ], []);
        
        // Get record details before it's deleted from DB (for potential restoration)
        $zone = DnsZone::where('uuid', $zone_uuid)->first();
        $ttl = 3600;
        $description = '';
        
        if ($zone) {
            // Try to find the record details from zone's records snapshot
            foreach ($zone->dnsRecords as $rec) {
                if ($rec->name === $name && $rec->type === $type && $rec->content === $content) {
                    $ttl = $rec->ttl;
                    $description = $rec->description;
                    break;
                }
            }
        }
        
        $data = [
            'module' => $this,
            'method' => 'deleteRecordJob',
            'callback' => 'restoreRecordOnDeleteError',
            'tries' => 5,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
            'params' => [$zone_uuid, $name, $type, $content, $ttl, $description],
        ];

        $tags = [
            'deleteRecord',
        ];

        Task::add('ModuleJob', 'puqHestiaDNS-Queue', $data, $tags);
        
        $this->logInfo('deleteRecord - Task Added to Queue', [
            'zone_uuid' => $zone_uuid,
            'name' => $name,
            'type' => $type,
            'ttl' => $ttl,
            'description' => $description
        ], []);

        return ['status' => 'success'];
    }

    public function deleteRecordJob(string $zone_uuid, string $name, string $type, string $content, int $ttl = 3600, string $description = ''): array
    {
        $this->logInfo('deleteRecordJob - Start', [
            'zone_uuid' => $zone_uuid,
            'name' => $name,
            'type' => $type,
            'ttl' => $ttl,
            'description' => $description
        ], []);
        
        $model_zone = DnsZone::where('uuid', $zone_uuid)->first();
        if (empty($model_zone)) {
            $this->logError('deleteRecordJob - Zone Not Found', [
                'zone_uuid' => $zone_uuid
            ], ['error' => 'Zone not found in database']);
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        $this->logInfo('deleteRecordJob - Zone Found', [
            'zone_name' => $model_zone->name,
            'zone_uuid' => $zone_uuid
        ], []);

        $client = new puqHestiaDnsClient($this->module_data);

        // Check if this is an A record and if it's the last one
        if ($type === 'A') {
            $allRecordsResult = $client->listDnsRecords($model_zone->name);
            if ($allRecordsResult['status'] === 'success') {
                $aRecordCount = 0;
                foreach ($allRecordsResult['data'] as $r) {
                    if ($r['TYPE'] === 'A') $aRecordCount++;
                }
                
                if ($aRecordCount === 1) {
                    $this->logError('deleteRecordJob - Cannot Delete Last A Record', [
                        'zone' => $model_zone->name,
                        'name' => $name,
                        'type' => $type,
                        'content' => $content
                    ], ['error' => 'Cannot delete the last A record. Zone must have at least one A record.']);
                    
                    return [
                        'status' => 'error',
                        'errors' => [__('DnsServer.puqHestiaDNS.cannot_delete_last_a_record')],
                        'code' => 422,
                    ];
                }
                
                $this->logInfo('deleteRecordJob - A Record Analysis', [
                    'zone' => $model_zone->name,
                    'total_a_records' => $aRecordCount,
                    'safe_to_delete' => true
                ], []);
            }
        }

        // Find record by name, type and content
        $recordId = $this->findRecordIdByData($client, $model_zone->name, $name, $type, $content);
        
        if (!$recordId) {
            $this->logInfo('deleteRecordJob - Record Not Found on HestiaCP', [
                'zone' => $model_zone->name,
                'name' => $name,
                'type' => $type
            ], []);
            return ['status' => 'success']; // Record already deleted
        }

        $this->logInfo('deleteRecordJob - Deleting Record from HestiaCP', [
            'zone' => $model_zone->name,
            'record_id' => $recordId,
            'name' => $name,
            'type' => $type
        ], []);

        $result = $client->deleteDnsRecord($model_zone->name, $recordId, false);
        
        $this->logInfo('deleteRecordJob - Delete Result', [
            'zone' => $model_zone->name,
            'record_id' => $recordId,
            'result' => $result
        ], []);
        
        // Handle specific HestiaCP errors
        if ($result['status'] === 'error' && isset($result['errors'])) {
            foreach ($result['errors'] as $error) {
                if (strpos($error, 'at least one A record should remain active') !== false) {
                    $this->logError('deleteRecordJob - Cannot Delete Last A Record', [
                        'zone' => $model_zone->name,
                        'name' => $name,
                        'type' => $type,
                        'content' => $content
                    ], ['error' => $error]);
                    
                    return [
                        'status' => 'error',
                        'errors' => [__('DnsServer.puqHestiaDNS.cannot_delete_last_a_record')],
                        'code' => 422,
                    ];
                }
            }
        }
        
        return $result;
    }

    /**
     * Callback to restore record in DB if deletion failed
     * Called after deleteRecordJob completes
     */
    public function restoreRecordOnDeleteError($result, $jobId): void
    {
        // Only restore if the error is "cannot_delete_last_a_record"
        if ($result['status'] === 'error' && 
            isset($result['errors']) && 
            is_array($result['errors'])) {
            
            $isLastARecordError = false;
            foreach ($result['errors'] as $error) {
                if (strpos($error, 'cannot_delete_last_a_record') !== false) {
                    $isLastARecordError = true;
                    break;
                }
            }
            
            if (!$isLastARecordError) {
                return; // Not the error we're looking for
            }
            
            // Get job parameters from Task
            $task = Task::where('job_id', $jobId)->first();
            if (!$task) {
                $this->logError('restoreRecordOnDeleteError - Task Not Found', ['job_id' => $jobId], []);
                return;
            }
            
            $inputData = json_decode($task->input_data, true);
            $params = $inputData['params'] ?? [];
            
            if (count($params) < 4) {
                $this->logError('restoreRecordOnDeleteError - Invalid Parameters', [
                    'params' => $params,
                    'input_data' => $inputData
                ], []);
                return;
            }
            
            $zone_uuid = $params[0];
            $name = $params[1];
            $type = $params[2];
            $content = $params[3];
            $ttl = $params[4] ?? 3600;
            $description = $params[5] ?? '';
            
            $this->logInfo('restoreRecordOnDeleteError - Restoring Record', [
                'zone_uuid' => $zone_uuid,
                'name' => $name,
                'type' => $type,
                'content' => $content,
                'ttl' => $ttl,
                'description' => $description
            ], []);
            
            // Restore record in database
            $zone = DnsZone::where('uuid', $zone_uuid)->first();
            if (!$zone) {
                $this->logError('restoreRecordOnDeleteError - Zone Not Found', ['zone_uuid' => $zone_uuid], []);
                return;
            }
            
            // Check if record already exists (maybe it wasn't deleted)
            $existingRecord = $zone->dnsRecords()
                ->where('name', $name)
                ->where('type', $type)
                ->where('content', $content)
                ->first();
            
            if ($existingRecord) {
                $this->logInfo('restoreRecordOnDeleteError - Record Already Exists, No Restoration Needed', [
                    'zone_uuid' => $zone_uuid,
                    'name' => $name
                ], []);
                return;
            }
            
            // Create new record
            $record = new DnsRecord();
            $record->dns_zone_uuid = $zone_uuid;
            $record->name = $name;
            $record->type = $type;
            $record->content = $content;
            $record->ttl = $ttl;
            $record->description = $description;
            $record->save();
            
            $this->logInfo('restoreRecordOnDeleteError - Record Restored Successfully', [
                'zone_uuid' => $zone_uuid,
                'record_uuid' => $record->uuid,
                'name' => $name,
                'type' => $type,
                'content' => $content
            ], []);
        }
    }

    // Private helper methods ----------------------------------------------------------------------------------------------------
    
    /**
     * Get default IP for zone (from first A record or fallback)
     */
    private function getZoneDefaultIp(DnsZone $model_zone): string
    {
        // Try to find first A record
        foreach ($model_zone->dnsRecords as $record) {
            if ($record->type === 'A') {
                return $record->content;
            }
        }

        // Fallback to 127.0.0.1
        return '127.0.0.1';
    }

    /**
     * Format record name for HestiaCP (remove domain part)
     */
    private function formatRecordName(string $name): string
    {
        if ($name === '@' || $name === '') {
            return '@';
        }
        
        return trim($name);
    }

    /**
     * Add record to HestiaCP
     */
    private function addRecordToHestia(puqHestiaDnsClient $client, DnsZone $model_zone, DnsRecord $record): array
    {
        $this->logInfo('addRecordToHestia - Start', [
            'zone' => $model_zone->name,
            'record_uuid' => $record->uuid
        ]);
        
        $get_record_data = $model_zone->getRecord($record->uuid, $record);
        if ($get_record_data['status'] === 'error') {
            $this->logError('addRecordToHestia - Failed to Get Record Data', [
                'zone' => $model_zone->name,
                'record_uuid' => $record->uuid
            ], $get_record_data);
            return $get_record_data;
        }

        $data = $get_record_data['data'];
        $recordName = $this->formatRecordName($data['name']);
        
        // Extract values based on record type
        $recordValue = $data['content'];
        $priority = null;

        if ($data['type'] === 'MX') {
            $priority = isset($data['priority']) ? (int)$data['priority'] : 10;
            $recordValue = $data['mailServer'] ?? '';
        } elseif ($data['type'] === 'SRV') {
            $priority = isset($data['priority']) ? (int)$data['priority'] : 0;
            // For SRV, HestiaCP expects: "weight port target"
            $weight = isset($data['weight']) ? (int)$data['weight'] : 0;
            $port = isset($data['port']) ? (int)$data['port'] : 1;
            $target = $data['target'] ?? '';
            $recordValue = "$weight $port $target";
        } elseif ($data['type'] === 'TXT') {
            // For TXT records, use the parsed txt field
            $recordValue = $data['txt'] ?? $data['content'];
        } elseif (isset($data['priority']) && $data['priority'] > 0) {
            $priority = (int)$data['priority'];
        }

        $this->logInfo('addRecordToHestia - Calling API', [
            'domain' => $model_zone->name,
            'record' => $recordName,
            'type' => $data['type'],
            'value' => $recordValue,
            'priority' => $priority,
            'ttl' => $data['ttl']
        ]);

        $result = $client->addDnsRecord(
            $model_zone->name,
            $recordName,
            $data['type'],
            $recordValue,
            $priority,
            null,
            false,
            $data['ttl']
        );
        
        $this->logInfo('addRecordToHestia - API Result', ['result' => $result], []);
        
        return $result;
    }

    /**
     * Find record ID in HestiaCP by record object
     */
    private function findRecordIdInHestia(puqHestiaDnsClient $client, string $domain, DnsRecord $record): ?int
    {
        $get_record_data = $record->dnsZone->getRecord($record->uuid, $record);
        if ($get_record_data['status'] === 'error') {
            return null;
        }

        $data = $get_record_data['data'];
        return $this->findRecordIdByData($client, $domain, $data['name'], $data['type'], $data['content']);
    }

    /**
     * Find record ID in HestiaCP by data
     */
    private function findRecordIdByData(puqHestiaDnsClient $client, string $domain, string $name, string $type, string $content): ?int
    {
        $result = $client->listDnsRecords($domain);
        
        if ($result['status'] !== 'success' || empty($result['data'])) {
            return null;
        }

        $recordName = $this->formatRecordName($name);
        
        foreach ($result['data'] as $id => $recordData) {
            if (
                $recordData['RECORD'] === $recordName &&
                $recordData['TYPE'] === $type &&
                $recordData['VALUE'] === $content
            ) {
                return (int) $id;
            }
        }

        return null;
    }
}
