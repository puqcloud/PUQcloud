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
            
            // Handle SRV records - HestiaCP stores priority separately in PRIORITY field
            // VALUE field contains only "weight port target" (e.g., "0 587 mail.domain.com.")
            // But buildRecordSRV() with reverse=true expects format: "priority weight port target"
            // So we need to prepend priority to the content
            if ($recordType === 'SRV' && $recordPriority !== null) {
                // Check if priority is already in content (shouldn't be, but just in case)
                if (!preg_match('/^\d+\s+\d+\s+\d+\s+/', $recordValue)) {
                    $recordValue = $recordPriority . ' ' . $recordValue;
                }
            }

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

            $this->createDefaultARecord($model_zone, $client, $ip, 'createZoneJob');
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

            $this->createDefaultARecord($model_zone, $client, $ip, 'reloadZoneJob');
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
        $recordsSkipped = 0;
        $failedRecords = [];
        $skippedRecords = [];
        $skipFirstARecord = false;
        
        foreach ($model_zone->dnsRecords as $record) {
            // Skip first A record if we already created it before cleanup
            // This ensures we always have at least one A record (HestiaCP requirement)
            if ($record->type === 'A' && $hasPanelARecord && !$skipFirstARecord) {
                $skipFirstARecord = true;
                $recordsSkipped++;
                $skippedRecords[] = [
                    'name' => $record->name,
                    'type' => $record->type,
                    'content' => $record->content,
                    'reason' => 'Already created before cleanup to ensure at least one A record exists'
                ];
                $this->logInfo('reloadZoneJob - Skipping First A Record (Already Created)', [
                    'record_uuid' => $record->uuid,
                    'name' => $record->name,
                    'type' => $record->type,
                    'content' => $record->content,
                    'reason' => 'Already created before cleanup to ensure at least one A record exists'
                ]);
                continue;
            }
            
            $this->logInfo('reloadZoneJob - Adding Panel Record', [
                'record_uuid' => $record->uuid,
                'name' => $record->name,
                'type' => $record->type,
                'content' => $record->content
            ]);
            
            $recordResult = $this->addRecordToHestia($client, $model_zone, $record);
            
            if ($recordResult['status'] == 'success') {
                $recordsAdded++;
            } else {
                $recordsFailed++;
                $errorMessage = !empty($recordResult['errors']) 
                    ? (is_array($recordResult['errors']) ? implode(', ', $recordResult['errors']) : $recordResult['errors'])
                    : 'Unknown error';
                
                $failedRecords[] = [
                    'name' => $record->name,
                    'type' => $record->type,
                    'content' => $record->content,
                    'error' => $errorMessage
                ];
                
                $this->logError('reloadZoneJob - Failed to Add Panel Record', [
                    'record_uuid' => $record->uuid,
                    'name' => $record->name,
                    'type' => $record->type,
                    'content' => $record->content,
                    'error' => $errorMessage
                ], $recordResult);
            }
        }

        $this->logInfo('reloadZoneJob - Completed', [
            'domain' => $model_zone->name,
            'records_added' => $recordsAdded,
            'records_failed' => $recordsFailed,
            'records_skipped' => $recordsSkipped,
            'failed_records' => $failedRecords,
            'skipped_records' => $skippedRecords
        ]);

        $message = "Successfully synced {$recordsAdded} DNS records to HestiaCP";
        if ($recordsFailed > 0) {
            $message .= ". Failed: {$recordsFailed}";
        }
        if ($recordsSkipped > 0) {
            $message .= ". Skipped: {$recordsSkipped} (first A record was created before cleanup)";
        }

        return [
            'status' => 'success',
            'synced' => $recordsAdded,
            'failed' => $recordsFailed,
            'skipped' => $recordsSkipped,
            'failed_records' => $failedRecords,
            'skipped_records' => $skippedRecords,
            'message' => $message
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
        
        // Validate record type - check if HestiaCP supports this type
        $typeValidation = $this->validateRecordType($record->type);
        if ($typeValidation['status'] === 'error') {
            $this->logError('createRecordJob - Unsupported Record Type', [
                'uuid' => $uuid,
                'type' => $record->type,
                'supported_types' => $this->getSupportedRecordTypes()
            ], $typeValidation);
            
            // Add error comment to record instead of deleting it
            $this->logInfo('createRecordJob - Adding Error Comment for Unsupported Record Type', [
                'record_uuid' => $record->uuid,
                'type' => $record->type,
                'zone' => $record->dnsZone->name ?? 'unknown'
            ], []);
            
            $errorMessages = !empty($typeValidation['errors']) ? $typeValidation['errors'] : ['Unsupported record type'];
            $this->addErrorCommentToRecord($record, 'create', $errorMessages);
            
            // Return error instead of throwing exception - job should complete successfully
            return [
                'status' => 'error',
                'errors' => $errorMessages,
                'code' => $typeValidation['code'] ?? 422,
            ];
        }
        
        $model_zone = $record->dnsZone;
        $this->logInfo('createRecordJob - Zone Info', [
            'zone_name' => $model_zone->name,
            'zone_uuid' => $model_zone->uuid
        ]);
        
        $client = new puqHestiaDnsClient($this->module_data);

        $result = $this->addRecordToHestia($client, $model_zone, $record);
        
        $this->logInfo('createRecordJob - Result', ['result' => $result], []);
        
        // If record creation failed on server, add error comment instead of deleting
        if ($result['status'] === 'error') {
            $this->logInfo('createRecordJob - Record Creation Failed on Server, Adding Error Comment', [
                'record_uuid' => $record->uuid,
                'name' => $record->name,
                'type' => $record->type,
                'errors' => $result['errors'] ?? []
            ], []);
            
            $errorMessages = !empty($result['errors']) ? $result['errors'] : ['Record creation failed'];
            $this->addErrorCommentToRecord($record, 'create', $errorMessages);
            
            // Return error instead of throwing exception - job should complete successfully
            return [
                'status' => 'error',
                'errors' => $errorMessages,
                'code' => $result['code'] ?? 500,
            ];
        }
        
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
        
        // Validate record type - check if HestiaCP supports this type
        $typeValidation = $this->validateRecordType($record->type);
        if ($typeValidation['status'] === 'error') {
            $this->logError('updateRecordJob - Unsupported Record Type', [
                'uuid' => $uuid,
                'type' => $record->type,
                'supported_types' => $this->getSupportedRecordTypes()
            ], $typeValidation);
            
            // Add error comment to record instead of deleting it
            $this->logInfo('updateRecordJob - Adding Error Comment for Unsupported Record Type', [
                'record_uuid' => $record->uuid,
                'type' => $record->type,
                'zone' => $record->dnsZone->name ?? 'unknown'
            ], []);
            
            $errorMessages = !empty($typeValidation['errors']) ? $typeValidation['errors'] : ['Unsupported record type'];
            $this->addErrorCommentToRecord($record, 'update', $errorMessages);
            
            // Return error instead of throwing exception - job should complete successfully
            return [
                'status' => 'error',
                'errors' => $errorMessages,
                'code' => $typeValidation['code'] ?? 422,
            ];
        }

        $model_zone = $record->dnsZone;
        $client = new puqHestiaDnsClient($this->module_data);

        // Diagnostic: Get all records from HestiaCP before searching
        // This helps diagnose format mismatches, especially for SRV records
        if ($record->type === 'SRV') {
            $diagnosticResult = $client->listDnsRecords($model_zone->name);
            if ($diagnosticResult['status'] === 'success' && isset($diagnosticResult['data'])) {
                $srvRecords = [];
                foreach ($diagnosticResult['data'] as $id => $recordData) {
                    if (($recordData['TYPE'] ?? '') === 'SRV') {
                        $srvRecords[] = [
                            'id' => $id,
                            'record' => $recordData['RECORD'] ?? '',
                            'type' => $recordData['TYPE'] ?? '',
                            'value' => $recordData['VALUE'] ?? '',
                            'priority' => $recordData['PRIORITY'] ?? '',
                            'ttl' => $recordData['TTL'] ?? '',
                            'suspended' => $recordData['SUSPENDED'] ?? '',
                        ];
                    }
                }
                $this->logInfo('updateRecordJob - Diagnostic: All SRV Records from HestiaCP', [
                    'zone' => $model_zone->name,
                    'searching_for_name' => $record->name,
                    'searching_for_type' => $record->type,
                    'searching_for_old_content' => $old_content,
                    'total_srv_records' => count($srvRecords),
                    'srv_records' => $srvRecords
                ], []);
            }
        }

        // Find record by OLD content (before update)
        // Note: old_content comes from database and may contain quotes for TXT records
        // findRecordIdByData will normalize it using normalizeContentForComparison
        $this->logInfo('updateRecordJob - Searching for Record on HestiaCP', [
            'zone' => $model_zone->name,
            'name' => $record->name,
            'type' => $record->type,
            'old_content' => $old_content,
            'new_content' => $record->content
        ], []);
        
        $recordId = $this->findRecordIdByData($client, $model_zone, $record->name, $record->type, $old_content);
        
        if (!$recordId) {
            // This is UPDATE operation - if record not found, it's an error
            // We should NOT create new record during update!
            $this->logError('updateRecordJob - Record Not Found on HestiaCP - Cannot Update', [
                'zone' => $model_zone->name,
                'name' => $record->name,
                'type' => $record->type,
                'old_content' => $old_content,
                'new_content' => $record->content,
                'error' => 'Record to update not found on HestiaCP server. This is UPDATE operation, not CREATE.'
            ], []);
            
            // Add error comment to record instead of deleting it
            $this->logInfo('updateRecordJob - Adding Error Comment (record not found on server)', [
                'record_uuid' => $record->uuid,
                'zone' => $model_zone->name
            ], []);
            
            $errorMessages = ['Record to update not found on HestiaCP server. Record may have been deleted or content format mismatch.'];
            $this->addErrorCommentToRecord($record, 'update', $errorMessages);
            
            // Return error instead of throwing exception - job should complete successfully
            return [
                'status' => 'error',
                'errors' => $errorMessages,
                'code' => 404,
            ];
        }

        // Check if this is the last A record (for informational purposes)
        // Note: UPDATE operation should work for all records including last A record
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
                    'strategy' => 'update'
                ], []);
            }
        }

        // Use UPDATE strategy for all records
        $this->logInfo('updateRecordJob - Using UPDATE Strategy', [
            'zone' => $model_zone->name,
            'record_id' => $recordId,
            'name' => $record->name,
            'type' => $record->type,
            'old_content' => $old_content,
            'new_content' => $record->content,
            'is_last_a_record' => $isLastARecord
        ], []);

        // Update existing record using v-change-dns-record
        $result = $this->updateRecordInHestia($client, $model_zone, $record, $recordId);
        
        // If record update failed on server, add error comment instead of deleting
        if ($result['status'] === 'error') {
            $this->logInfo('updateRecordJob - Record Update Failed on Server, Adding Error Comment', [
                'record_uuid' => $record->uuid,
                'name' => $record->name,
                'type' => $record->type,
                'errors' => $result['errors'] ?? []
            ], []);
            
            $errorMessages = !empty($result['errors']) ? $result['errors'] : ['Record update failed'];
            $this->addErrorCommentToRecord($record, 'update', $errorMessages);
            
            // Return error instead of throwing exception - job should complete successfully
            return [
                'status' => 'error',
                'errors' => $errorMessages,
                'code' => $result['code'] ?? 500,
            ];
        }
        
        $this->logInfo('updateRecordJob - UPDATE Strategy Completed', [
            'zone' => $model_zone->name,
            'record_id' => $recordId,
            'result' => $result
        ], []);
        
        return $result;
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
        $this->logInfo('Delete Record Job - Start', [
            'zone_uuid' => $zone_uuid,
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
            'description' => $description
        ], []);
        
        $model_zone = DnsZone::where('uuid', $zone_uuid)->first();
        if (empty($model_zone)) {
            $this->logError('Delete Record Job - Zone Not Found', [
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
        $this->logInfo('Delete Record Job - Searching for Record on HestiaCP', [
            'zone' => $model_zone->name,
            'name' => $name,
            'type' => $type,
            'content' => $content
        ], []);
        
        $recordId = $this->findRecordIdByData($client, $model_zone, $name, $type, $content);
        
        if (!$recordId) {
            $this->logInfo('Delete Record Job - Record Not Found on HestiaCP', [
                'zone' => $model_zone->name,
                'name' => $name,
                'type' => $type,
                'content' => $content,
                'note' => 'Record may already be deleted or name/content format mismatch'
            ], []);
            return ['status' => 'success']; // Record already deleted
        }
        
        $this->logInfo('Delete Record Job - Record Found, Deleting from HestiaCP', [
            'zone' => $model_zone->name,
            'record_id' => $recordId,
            'name' => $name,
            'type' => $type
        ], []);

        $result = $client->deleteDnsRecord($model_zone->name, $recordId, false);
        
        $this->logInfo('Delete Record Job - Delete Result', [
            'zone' => $model_zone->name,
            'record_id' => $recordId,
            'result_status' => $result['status'] ?? 'unknown',
            'result_errors' => $result['errors'] ?? []
        ], $result);
        
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
     * Get list of DNS record types supported by HestiaCP
     * Based on: https://hestiacp.com/docs/user-guide/dns
     */
    private function getSupportedRecordTypes(): array
    {
        return [
            'A',
            'AAAA',
            'CAA',
            'CNAME',
            'DNSKEY',
            'IPSECKEY',
            'KEY',
            'MX',
            'NS',
            'PTR',
            'SPF',
            'SRV',
            'TLSA',
            'TXT',
        ];
    }
    
    /**
     * Validate if record type is supported by HestiaCP
     */
    private function validateRecordType(string $type): array
    {
        $type = strtoupper($type);
        $supportedTypes = $this->getSupportedRecordTypes();
        
        if (!in_array($type, $supportedTypes)) {
            return [
                'status' => 'error',
                'errors' => [
                    "Record type '{$type}' is not supported by HestiaCP. Supported types: " . implode(', ', $supportedTypes)
                ],
                'code' => 422,
            ];
        }
        
        return ['status' => 'success'];
    }
    
    /**
     * Create default A record in panel and on HestiaCP
     */
    private function createDefaultARecord(DnsZone $model_zone, puqHestiaDnsClient $client, string $ip, string $logPrefix = ''): ?DnsRecord
    {
        // Create default A record in panel first
        $panelRecord = new \App\Models\DnsRecord();
        $panelRecord->dns_zone_uuid = $model_zone->uuid;
        $panelRecord->name = '@';
        $panelRecord->type = 'A';
        $panelRecord->content = $ip;
        $panelRecord->ttl = 30;
        $panelRecord->description = 'Default A record for domain HestiaDNS module';
        $panelRecord->save();

        $this->logInfo($logPrefix . ' - Default A Record Created in Panel', [
            'domain' => $model_zone->name,
            'record_uuid' => $panelRecord->uuid
        ]);

        // Create default A record on HestiaCP
        $defaultARecordResult = $client->addDnsRecord(
            $model_zone->name,
            $panelRecord->name,
            $panelRecord->type,
            $panelRecord->content,
            null,
            null,
            false,
            $panelRecord->ttl
        );

        if ($defaultARecordResult['status'] === 'success') {
            $this->logInfo($logPrefix . ' - Default A Record Created on HestiaCP', [
                'domain' => $model_zone->name
            ]);
        } else {
            $this->logError($logPrefix . ' - Failed to Create Default A Record on HestiaCP', [
                'domain' => $model_zone->name
            ], $defaultARecordResult);
        }

        return $panelRecord;
    }
    
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
     * Add error comment to record description instead of deleting it
     */
    private function addErrorCommentToRecord(DnsRecord $record, string $operation, array $errors = []): void
    {
        $serverAddress = $this->module_data['server'] ?? 'unknown';
        
        // Format error message in English
        $errorDetails = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
        $errorMessage = sprintf(
            'Record was not accepted by Hestia server. Server: %s. Operation: %s. Answer: %s',
            $serverAddress,
            $operation,
            $errorDetails
        );
        
        // Sanitize error message
        $errorMessage = strip_tags($errorMessage);
        
        // Preserve existing description if present
        $existingDescription = $record->description ?? '';
        if (!empty($existingDescription)) {
            $record->description = $existingDescription . ' | ' . $errorMessage;
        } else {
            $record->description = $errorMessage;
        }
        
        // Save record
        try {
            $record->save();
            $this->logInfo('addErrorCommentToRecord - Error Comment Added', [
                'record_uuid' => $record->uuid,
                'operation' => $operation,
                'server' => $serverAddress,
                'error_message' => $errorMessage
            ], []);
        } catch (\Throwable $e) {
            $this->logError('addErrorCommentToRecord - Failed to Save Error Comment', [
                'record_uuid' => $record->uuid,
                'operation' => $operation,
                'exception' => $e->getMessage()
            ], []);
        }
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
            // Ensure mailServer is FQDN (add trailing dot if not present)
            if (!empty($recordValue) && !str_ends_with($recordValue, '.')) {
                $recordValue .= '.';
            }
        } elseif ($data['type'] === 'SRV') {
            $priority = isset($data['priority']) ? (int)$data['priority'] : 0;
            // For SRV, HestiaCP expects: "priority weight port target" in VALUE
            // Priority is also passed as separate parameter (required for SRV records in HestiaCP)
            // HestiaCP's is_dns_fqnd() checks for minimum 3 dots in the entire VALUE string for SRV records
            $weight = isset($data['weight']) ? (int)$data['weight'] : 0;
            $port = isset($data['port']) ? (int)$data['port'] : 1;
            $target = $data['target'] ?? '';
            // Ensure target is FQDN (add trailing dot if not present)
            if (!empty($target) && !str_ends_with($target, '.')) {
                $target .= '.';
            }
            // For SRV records, VALUE contains "???priority?? weight port target"
            // Priority is also passed separately (both are required by HestiaCP)
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
     * Update record in HestiaCP
     */
    private function updateRecordInHestia(puqHestiaDnsClient $client, DnsZone $model_zone, DnsRecord $record, int $recordId): array
    {
        $this->logInfo('updateRecordInHestia - Start', [
            'zone' => $model_zone->name,
            'record_uuid' => $record->uuid,
            'record_id' => $recordId
        ]);
        
        $get_record_data = $model_zone->getRecord($record->uuid, $record);
        if ($get_record_data['status'] === 'error') {
            $this->logError('updateRecordInHestia - Failed to Get Record Data', [
                'zone' => $model_zone->name,
                'record_uuid' => $record->uuid,
                'record_id' => $recordId
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
            // Ensure mailServer is FQDN (add trailing dot if not present)
            if (!empty($recordValue) && !str_ends_with($recordValue, '.')) {
                $recordValue .= '.';
            }
        } elseif ($data['type'] === 'SRV') {
            $priority = isset($data['priority']) ? (int)$data['priority'] : 0;
            // For SRV, HestiaCP expects: "weight port target" in VALUE
            // Priority is also passed as separate parameter (required for SRV records in HestiaCP)
            $weight = isset($data['weight']) ? (int)$data['weight'] : 0;
            $port = isset($data['port']) ? (int)$data['port'] : 1;
            $target = $data['target'] ?? '';
            // Ensure target is FQDN (add trailing dot if not present)
            if (!empty($target) && !str_ends_with($target, '.')) {
                $target .= '.';
            }
            // For SRV records, VALUE contains "weight port target"
            // Priority is also passed separately (both are required by HestiaCP)
            $recordValue = "$weight $port $target";
        } elseif ($data['type'] === 'TXT') {
            // For TXT records, use the parsed txt field
            $recordValue = $data['txt'] ?? $data['content'];
        } elseif (isset($data['priority']) && $data['priority'] > 0) {
            $priority = (int)$data['priority'];
        }

        $this->logInfo('updateRecordInHestia - Calling API', [
            'domain' => $model_zone->name,
            'record_id' => $recordId,
            'record' => $recordName,
            'type' => $data['type'],
            'value' => $recordValue,
            'priority' => $priority,
            'ttl' => $data['ttl']
        ]);

        $result = $client->changeDnsRecord(
            $model_zone->name,
            $recordId,
            $recordName,
            $data['type'],
            $recordValue,
            $priority,
            false,
            $data['ttl']
        );
        
        $this->logInfo('updateRecordInHestia - API Result', ['result' => $result], []);
        
        return $result;
    }

    /**
     * Find record ID in HestiaCP by data
     */
    private function findRecordIdByData(puqHestiaDnsClient $client, DnsZone $model_zone, string $name, string $type, string $content): ?int
    {
        $domain = $model_zone->name;
        
        $this->logInfo('Find Record ID - Start', [
            'domain' => $domain,
            'name' => $name,
            'type' => $type,
            'content' => $content
        ], []);
        
        $result = $client->listDnsRecords($domain);
        
        // Log full response for SRV records debugging
        if ($type === 'SRV') {
            $this->logInfo('Find Record ID - Full listDnsRecords Response for SRV', [
                'domain' => $domain,
                'status' => $result['status'] ?? 'unknown',
                'has_data' => isset($result['data']) && !empty($result['data']),
                'data_count' => isset($result['data']) ? count($result['data']) : 0,
                'full_response' => $result
            ], []);
        }
        
        if ($result['status'] !== 'success' || empty($result['data'])) {
            $this->logInfo('findRecordIdByData - No Records or Error', [
                'domain' => $domain,
                'status' => $result['status'] ?? 'unknown',
                'has_data' => isset($result['data']) && !empty($result['data'])
            ], []);
            return null;
        }

        $recordName = $this->formatRecordName($name);
        
        // For root domain records, HestiaCP may return domain name instead of "@"
        // So we need to check both "@" and domain name when searching for root records
        // Also add original name for non-root records to handle special characters
        $possibleRecordNames = [$recordName];
        if ($recordName === '@') {
            $possibleRecordNames[] = $domain;
            $possibleRecordNames[] = rtrim($domain, '.');
        } else {
            // For non-root records, also check original name (before formatRecordName)
            // This handles cases where formatRecordName might modify the name
            $possibleRecordNames[] = $name;
        }
        
        // Normalize content for comparison using DnsZone buildRecord methods
        $normalizedContent = $this->normalizeContentForComparison($model_zone, $content, $type, null);
        
        // Log hex dump for SRV records to detect hidden characters
        $hexDumpSearch = '';
        if ($type === 'SRV') {
            $hexDumpSearch = bin2hex($normalizedContent);
        }
        
        $this->logInfo('Find Record ID - Search Criteria', [
            'domain' => $domain,
            'record_name' => $recordName,
            'possible_record_names' => $possibleRecordNames,
            'type' => $type,
            'original_content' => $content,
            'normalized_content' => $normalizedContent,
            'normalized_content_hex' => $hexDumpSearch,
            'normalized_content_length' => strlen($normalizedContent),
            'total_records_to_check' => count($result['data'])
        ], []);
        
        $checkedCount = 0;
        $skippedByName = 0;
        $skippedByType = 0;
        
        // Log all records for SRV type to debug
        if ($type === 'SRV') {
            $allRecords = [];
            foreach ($result['data'] as $id => $recordData) {
                $allRecords[] = [
                    'id' => $id,
                    'record' => $recordData['RECORD'] ?? '',
                    'type' => $recordData['TYPE'] ?? '',
                    'value' => $recordData['VALUE'] ?? '',
                    'priority' => $recordData['PRIORITY'] ?? '',
                ];
            }
            $this->logInfo('Find Record ID - All Records from HestiaCP', [
                'search_type' => $type,
                'search_name' => $name,
                'all_records' => $allRecords,
            ], []);
        }
        
        foreach ($result['data'] as $id => $recordData) {
            $hestiaRecordName = $recordData['RECORD'] ?? '';
            $hestiaType = $recordData['TYPE'] ?? '';
            $hestiaValue = $recordData['VALUE'] ?? '';
            $hestiaPriority = isset($recordData['PRIORITY']) && $recordData['PRIORITY'] !== '' ? (int)$recordData['PRIORITY'] : null;
            
            // Normalize HestiaCP record name (convert domain name to "@" for comparison)
            $originalHestiaRecordName = $hestiaRecordName;
            if ($hestiaRecordName === $domain || $hestiaRecordName === rtrim($domain, '.')) {
                $hestiaRecordName = '@';
            }
            
            // For non-root records, also check exact match (case-sensitive)
            // HestiaCP may return the name exactly as stored, including underscores, dots, etc.
            $nameMatches = in_array($hestiaRecordName, $possibleRecordNames);
            
            // Additional check: if name doesn't match normalized, try direct comparison
            // This handles cases where record name has special characters like underscores
            if (!$nameMatches) {
                // Try direct comparison with original HestiaCP record name
                if ($recordName === '@') {
                    // For root records, check if HestiaCP record name matches domain
                    $nameMatches = ($originalHestiaRecordName === $domain || $originalHestiaRecordName === rtrim($domain, '.'));
                } else {
                    // For non-root records, try all possible combinations
                    $nameMatches = ($hestiaRecordName === $recordName 
                        || $originalHestiaRecordName === $name 
                        || $originalHestiaRecordName === $recordName
                        || $hestiaRecordName === $name);
                }
            }
            
            if (!$nameMatches) {
                $skippedByName++;
                // Log only for SRV records to avoid spam
                if ($type === 'SRV' || $hestiaType === 'SRV') {
                    $this->logInfo('findRecordIdByData - Skipping Record (Name Mismatch)', [
                        'id' => $id,
                        'hestia_record_name' => $originalHestiaRecordName,
                        'hestia_record_name_length' => strlen($originalHestiaRecordName),
                        'normalized_hestia_record_name' => $hestiaRecordName,
                        'search_record_name' => $recordName,
                        'search_record_name_length' => strlen($recordName),
                        'search_name' => $name,
                        'search_name_length' => strlen($name),
                        'search_possible_names' => $possibleRecordNames,
                        'hestia_type' => $hestiaType,
                        'search_type' => $type,
                        'direct_match_hestia_normalized' => ($hestiaRecordName === $recordName),
                        'direct_match_hestia_original' => ($originalHestiaRecordName === $name),
                        'hestia_record_name_hex' => bin2hex($originalHestiaRecordName),
                        'search_name_hex' => bin2hex($name)
                    ], []);
                }
                continue;
            }
            
            // Check if type matches
            $typeMatches = ($hestiaType === $type);
            if (!$typeMatches) {
                $checkedCount++;
                $skippedByType++;
                $this->logInfo('findRecordIdByData - Name Matches But Type Mismatch', [
                    'id' => $id,
                    'hestia_record_name' => $originalHestiaRecordName,
                    'normalized_hestia_record_name' => $hestiaRecordName,
                    'hestia_type' => $hestiaType,
                    'search_type' => $type,
                    'hestia_value' => $hestiaValue,
                    'hestia_priority' => $hestiaPriority
                ], []);
                continue;
            }
            
            // Normalize and compare content using DnsZone buildRecord methods
            // Log before normalization for SRV records
            if ($type === 'SRV' || $hestiaType === 'SRV') {
                $this->logInfo('Find Record ID - Before Normalization (SRV)', [
                    'id' => $id,
                    'hestia_value' => $hestiaValue,
                    'hestia_priority' => $hestiaPriority,
                    'hestia_priority_type' => gettype($hestiaPriority),
                    'search_content' => $content,
                    'search_normalized_content' => $normalizedContent
                ], []);
            }
            
            $normalizedHestiaValue = $this->normalizeContentForComparison($model_zone, $hestiaValue, $type, $hestiaPriority);
            
            $checkedCount++;
            $contentMatches = ($normalizedContent === $normalizedHestiaValue);
            
            // Always log SRV records comparison in detail for debugging
            if ($type === 'SRV' || $hestiaType === 'SRV') {
                // Generate hex dump for both normalized strings
                $hexDumpSearch = bin2hex($normalizedContent);
                $hexDumpHestia = bin2hex($normalizedHestiaValue);
                
                // Compare character by character
                $charComparison = [];
                $maxLen = max(strlen($normalizedContent), strlen($normalizedHestiaValue));
                for ($i = 0; $i < $maxLen; $i++) {
                    $charSearch = $i < strlen($normalizedContent) ? $normalizedContent[$i] : '<EOF>';
                    $charHestia = $i < strlen($normalizedHestiaValue) ? $normalizedHestiaValue[$i] : '<EOF>';
                    if ($charSearch !== $charHestia) {
                        $charComparison[] = [
                            'position' => $i,
                            'search_char' => $charSearch,
                            'search_hex' => $i < strlen($normalizedContent) ? bin2hex($normalizedContent[$i]) : '',
                            'hestia_char' => $charHestia,
                            'hestia_hex' => $i < strlen($normalizedHestiaValue) ? bin2hex($normalizedHestiaValue[$i]) : ''
                        ];
                    }
                }
                
                $this->logInfo('Find Record ID - SRV Comparison Details', [
                    'id' => $id,
                    'hestia_record_name' => $originalHestiaRecordName,
                    'hestia_value' => $hestiaValue,
                    'hestia_priority' => $hestiaPriority,
                    'hestia_priority_type' => gettype($hestiaPriority),
                    'normalized_hestia_value' => $normalizedHestiaValue,
                    'normalized_hestia_value_hex' => $hexDumpHestia,
                    'normalized_hestia_length' => strlen($normalizedHestiaValue),
                    'search_normalized_content' => $normalizedContent,
                    'search_normalized_content_hex' => $hexDumpSearch,
                    'normalized_content_length' => strlen($normalizedContent),
                    'content_matches' => $contentMatches,
                    'match_exact' => $normalizedContent === $normalizedHestiaValue,
                    'char_differences' => $charComparison,
                    'first_10_chars_search' => substr($normalizedContent, 0, 10),
                    'first_10_chars_hestia' => substr($normalizedHestiaValue, 0, 10),
                ], []);
            }
            
            // Log all MX, SRV and TXT records comparison in detail
            if ($type === 'MX' || $hestiaType === 'MX') {
                $this->logInfo('Find Record ID - Comparing MX Record', [
                    'id' => $id,
                    'hestia_record_name' => $originalHestiaRecordName,
                    'normalized_hestia_record_name' => $hestiaRecordName,
                    'hestia_type' => $hestiaType,
                    'hestia_value' => $hestiaValue,
                    'hestia_priority' => $hestiaPriority,
                    'hestia_priority_type' => gettype($hestiaPriority),
                    'normalized_hestia_value' => $normalizedHestiaValue,
                    'search_normalized_content' => $normalizedContent,
                    'content_matches' => $contentMatches,
                    'search_name' => $name,
                    'search_type' => $type
                ], []);
            }
            
            if ($type === 'SRV' || $hestiaType === 'SRV') {
                $this->logInfo('Find Record ID - Comparing SRV Record', [
                    'id' => $id,
                    'hestia_record_name' => $originalHestiaRecordName,
                    'normalized_hestia_record_name' => $hestiaRecordName,
                    'hestia_type' => $hestiaType,
                    'hestia_value' => $hestiaValue,
                    'hestia_priority' => $hestiaPriority,
                    'hestia_priority_type' => gettype($hestiaPriority),
                    'normalized_hestia_value' => $normalizedHestiaValue,
                    'search_normalized_content' => $normalizedContent,
                    'content_matches' => $contentMatches,
                    'search_name' => $name,
                    'search_type' => $type
                ], []);
            }
            
            // Log records comparison in detail
  
            $this->logInfo('Find Record ID - Comparing Record', [
                'id' => $id,
                'hestia_record_name' => $originalHestiaRecordName,
                'normalized_hestia_record_name' => $hestiaRecordName,
                'hestia_type' => $hestiaType,
                'hestia_value' => $hestiaValue,
                'hestia_value_length' => strlen($hestiaValue),
                'search_original_content' => $content,
                'search_original_content_length' => strlen($content),
                'normalized_hestia_value' => $normalizedHestiaValue,
                'normalized_hestia_value_length' => strlen($normalizedHestiaValue),
                'search_normalized_content' => $normalizedContent,
                'search_normalized_content_length' => strlen($normalizedContent),
                'content_matches' => $contentMatches,
                'search_name' => $name,
                'search_type' => $type
            ], []);
        
            
            if ($contentMatches) {
                $this->logInfo('Find Record ID - Match Found', [
                    'id' => $id,
                    'domain' => $domain,
                    'name' => $name,
                    'type' => $type,
                    'content' => $content
                ], []);
                return (int) $id;
            }
        }

        // Log all SRV records found for debugging
        $srvRecordsFound = [];
        if ($type === 'SRV') {
            foreach ($result['data'] as $id => $recordData) {
                if (($recordData['TYPE'] ?? '') === 'SRV') {
                    $srvRecordsFound[] = [
                        'id' => $id,
                        'record' => $recordData['RECORD'] ?? '',
                        'value' => $recordData['VALUE'] ?? '',
                        'priority' => $recordData['PRIORITY'] ?? '',
                    ];
                }
            }
        }
        
        // Fallback: Try to find by name+type only (for diagnostic purposes)
        // This helps identify if there's a record with matching name and type but different content
        $fallbackMatches = [];
        foreach ($result['data'] as $id => $recordData) {
            $hestiaRecordName = $recordData['RECORD'] ?? '';
            $hestiaType = $recordData['TYPE'] ?? '';
            
            // Normalize HestiaCP record name
            $normalizedHestiaName = $hestiaRecordName;
            if ($hestiaRecordName === $domain || $hestiaRecordName === rtrim($domain, '.')) {
                $normalizedHestiaName = '@';
            }
            
            // Check if name and type match (but content might be different)
            // Use the same logic as in main loop
            $nameMatches = in_array($normalizedHestiaName, $possibleRecordNames);
            if (!$nameMatches) {
                if ($recordName === '@') {
                    $nameMatches = ($hestiaRecordName === $domain || $hestiaRecordName === rtrim($domain, '.'));
                } else {
                    $nameMatches = ($normalizedHestiaName === $recordName 
                        || $hestiaRecordName === $name 
                        || $hestiaRecordName === $recordName
                        || $normalizedHestiaName === $name);
                }
            }
            
            if ($nameMatches && $hestiaType === $type) {
                // Try to normalize and compare content for fallback match
                $hestiaValue = $recordData['VALUE'] ?? '';
                $hestiaPriority = isset($recordData['PRIORITY']) && $recordData['PRIORITY'] !== '' ? (int)$recordData['PRIORITY'] : null;
                $normalizedHestiaValue = $this->normalizeContentForComparison($model_zone, $hestiaValue, $type, $hestiaPriority);
                
                $fallbackMatches[] = [
                    'id' => $id,
                    'record' => $hestiaRecordName,
                    'type' => $hestiaType,
                    'value' => $hestiaValue,
                    'priority' => $hestiaPriority,
                    'normalized_value' => $normalizedHestiaValue,
                    'search_normalized_content' => $normalizedContent,
                    'content_matches' => ($normalizedContent === $normalizedHestiaValue),
                    'note' => 'Found by name+type match'
                ];
            }
        }
        
        // If we have exactly one fallback match, use it
        // For records with same name+type (especially SRV), this is the same record
        if (count($fallbackMatches) === 1) {
            // Check if content matches
            $contentMatches = isset($fallbackMatches[0]['content_matches']) && $fallbackMatches[0]['content_matches'];
            
            // For SRV records, if name+type match and only one record exists, use it even if content slightly differs
            // This handles trailing dot and other minor format differences
            if ($type === 'SRV' || $contentMatches) {
                $this->logInfo('Find Record ID - Match Found via Fallback', [
                    'domain' => $domain,
                    'name' => $name,
                    'type' => $type,
                    'id' => $fallbackMatches[0]['id'],
                    'content' => $content,
                    'content_matches' => $contentMatches,
                    'reason' => $contentMatches ? 'Content matches' : 'SRV record with unique name+type'
                ], []);
                return (int)$fallbackMatches[0]['id'];
            }
        }
        
        $this->logError('Find Record ID - No Match Found', [
            'domain' => $domain,
            'name' => $name,
            'type' => $type,
            'original_content' => $content,
            'normalized_content' => $normalizedContent,
            'records_checked' => $checkedCount,
            'skipped_by_name' => $skippedByName,
            'skipped_by_type' => $skippedByType,
            'total_records' => count($result['data']),
            'srv_records_found' => $srvRecordsFound,
            'fallback_matches_by_name_type' => $fallbackMatches,
            'fallback_matches_count' => count($fallbackMatches),
            'error' => 'Record not found on HestiaCP. This may indicate content format mismatch or record was deleted.'
        ], []);
        
        return null;
    }
    
    /**
     * Normalize content for comparison using DnsZone buildRecord methods
     * Uses DnsZone::buildRecordMX, buildRecordSRV, etc. with reverse=true to parse content
     * Then reconstructs normalized content for comparison
     */
    private function normalizeContentForComparison(DnsZone $model_zone, string $content, string $type, ?int $hestiaPriority = null): string
    {
        $originalContent = $content;
        $content = trim($content);
        $type = strtoupper($type);
        
        // Use reflection to access private buildRecord methods
        $methodName = 'buildRecord' . $type;
        
        if (!method_exists($model_zone, $methodName)) {
            // For unsupported types, just remove trailing dot
            return rtrim($content);
        }
        
        try {
            // Create temporary data array with content
            $tempData = [
                'type' => $type,
                'content' => $content,
                'name' => '@', // Dummy value, not used for parsing
                'ttl' => 3600, // Dummy value, not used for parsing
            ];
            
            // For MX records from HestiaCP, add priority if provided separately
            if ($type === 'MX' && $hestiaPriority !== null && !preg_match('/^\d+\s+/', $content)) {
                // Content doesn't have priority prefix, use provided priority
                $tempData['content'] = $hestiaPriority . ' ' . $content;
            }
            
            // For SRV records from HestiaCP, add priority if provided separately
            // SRV format in HestiaCP VALUE: "weight port target" (without priority)
            // Priority is stored separately in PRIORITY field
            // When comparing with HestiaCP records, we need to add priority from PRIORITY field
            // BUT: if content already has priority (starts with number), don't add it again
            if ($type === 'SRV' && $hestiaPriority !== null) {
                // Check if content already starts with priority (format: "priority weight port target")
                // If it does, don't add priority again
                if (!preg_match('/^\d+\s+\d+\s+\d+\s+/', $content)) {
                    // Content doesn't have priority prefix, add it from PRIORITY field
                    // HestiaCP always stores SRV VALUE without priority (format: "weight port target")
                    // So we add priority from PRIORITY field when comparing
                    // Format: "priority weight port target"
                    $beforeAdd = $tempData['content'];
                    $tempData['content'] = $hestiaPriority . ' ' . $content;
                    $this->logInfo('normalizeContentForComparison - SRV Adding Priority', [
                        'original_content' => $originalContent,
                        'before_add' => $beforeAdd,
                        'after_add' => $tempData['content'],
                        'hestia_priority' => $hestiaPriority
                    ], []);
                } else {
                    // Content already has priority, just log it
                    $this->logInfo('normalizeContentForComparison - SRV Priority Already in Content', [
                        'original_content' => $originalContent,
                        'content' => $content,
                        'hestia_priority' => $hestiaPriority,
                        'note' => 'Priority already present in content, not adding from hestiaPriority'
                    ], []);
                }
            }
            
            // Use reflection to call private method
            $reflection = new \ReflectionClass($model_zone);
            $method = $reflection->getMethod($methodName);
            $method->setAccessible(true);
            
            // Call buildRecord with reverse=true to parse content into fields
            $result = $method->invoke($model_zone, $tempData, true);
            
            if ($result['status'] !== 'success') {
                // If parsing failed, fallback to simple normalization
                return rtrim($content, '.');
            }
            
            $parsedData = $result['data'];
            
            // Reconstruct normalized content from parsed fields
            if ($type === 'MX') {
                $priority = $parsedData['priority'] ?? ($hestiaPriority ?? 10);
                $mailServer = $parsedData['mailServer'] ?? '';
                return $priority . ' ' . rtrim($mailServer, '.');
            } elseif ($type === 'SRV') {
                // Use priority from parsed data, or from hestiaPriority if provided, or default 65535
                // Convert to int to ensure consistent comparison
                $priority = (int)($parsedData['priority'] ?? ($hestiaPriority ?? 65535));
                $weight = (int)($parsedData['weight'] ?? 0);
                $port = (int)($parsedData['port'] ?? 1);
                $target = isset($parsedData['target']) ? rtrim($parsedData['target'], '.') : '';
                
                // Ensure consistent format: "priority weight port target" (no trailing dot on target)
                // All fields are integers except target, target has no trailing dot
                $normalized = $priority . ' ' . $weight . ' ' . $port . ' ' . $target;
                
                $this->logInfo('normalizeContentForComparison - SRV Normalized', [
                    'original_content' => $originalContent,
                    'parsed_priority' => $parsedData['priority'] ?? null,
                    'parsed_weight' => $parsedData['weight'] ?? null,
                    'parsed_port' => $parsedData['port'] ?? null,
                    'parsed_target' => $parsedData['target'] ?? null,
                    'hestia_priority' => $hestiaPriority,
                    'final_priority' => $priority,
                    'final_weight' => $weight,
                    'final_port' => $port,
                    'final_target' => $target,
                    'normalized_result' => $normalized,
                    'normalized_length' => strlen($normalized)
                ], []);
                return $normalized;
            } elseif ($type === 'CNAME') {
                return isset($parsedData['target']) ? rtrim($parsedData['target'], '.') : rtrim($content, '.');
            } elseif ($type === 'TXT') {
                // For TXT records, use the parsed txt field (without quotes and escaping)
                // If txt field is not available, try to manually remove quotes and escaping
                if (isset($parsedData['txt'])) {
                    return $parsedData['txt'];
                }
                // Fallback: manually remove quotes and escaping
                $normalized = trim($content);
                $normalized = str_replace('" "', '', $normalized);
                $normalized = preg_replace('/^"|"$|"\s+"|"\s*$/', '', $normalized);
                $normalized = stripcslashes($normalized);
                return $normalized;
            } else {
                // For other types (A, AAAA, etc.), use content as is or remove trailing dot
                return isset($parsedData['content']) ? rtrim($parsedData['content'], '.') : rtrim($content, '.');
            }
            
        } catch (\Throwable $e) {
            // If anything goes wrong, fallback to simple normalization
            return rtrim($content, '.');
        }
    }
}
