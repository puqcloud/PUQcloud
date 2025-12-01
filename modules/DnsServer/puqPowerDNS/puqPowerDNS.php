<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025 PUQ sp. z o.o.
 * Licensed under GNU GPLv3
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Author: Ruslan Polovyi <ruslan@polovyi.com>
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

class puqPowerDNS extends DnsServer
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getModuleData(array $data = []): array
    {
        $this->module_data = [
            'server' => $data['server'] ?? '',
            'api_key' => $data['api_key'] ?? '',
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
            'api_key' => 'required|string',
        ], [
            'server.required' => __('DnsServer.puqPowerDNS.The Server field is required'),
            'server.string' => __('DnsServer.puqPowerDNS.The Server must be a valid string'),
            'api_key.required' => __('DnsServer.puqPowerDNS.The API Key field is required'),
            'api_key.string' => __('DnsServer.puqPowerDNS.The API Key must be a valid string'),
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

    public function queues(): array
    {
        return [
            'Queue' => [
                'connection' => 'redis',
                'queue' => ['Queue'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
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
        $client = new puqPowerDnsClient($this->module_data);
        $response = $client->testConnection();

        if ($response['status'] == 'error') {
            return $response;
        }

        $data = $response['data'][0];
        $version = $data['version'] ?? 'unknown';
        $html = '
    <div class="text-center my-3">
        <div class="d-inline-block">
            <i class="fas fa-check-circle text-success"
               style="font-size: 64px; animation: pulse 1.2s ease-in-out infinite;"></i>
        </div>
        <div class="mt-2">
            <h5 class="text-success fw-bold mb-0">'.__('DnsServer.puqPowerDNS.Server is available').'</h5>
            <div class="text-muted small">'.__('DnsServer.puqPowerDNS.PowerDNS version').': <b>'.$version.'</b></div>
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

    public function getZones(): array
    {
        $client = new puqPowerDnsClient($this->module_data);
        $zones = [];
        $remote_zones = $client->getZones();

        if ($remote_zones['status'] == 'error') {
            return $remote_zones;
        }

        foreach ($remote_zones['data'] as $zone) {
            $zones[] = rtrim($zone['id'], '.');
        }
        sort($zones);

        return [
            'status' => 'success',
            'data' => $zones,
        ];
    }

    public function getZoneRecords($zone_name): array
    {
        $client = new puqPowerDnsClient($this->module_data);
        $records = [];
        $remote_zone = $client->getZone($zone_name);

        if ($remote_zone['status'] == 'error') {
            return $remote_zone;
        }
        $rrsets = $remote_zone['data']['rrsets'];

        foreach ($rrsets as $rrset) {
            foreach ($rrset['records'] as $record) {
                $name = rtrim($rrset['name'], '.');
                if ($name === $zone_name) {
                    $name = '';
                } elseif (str_ends_with($name, '.' . $zone_name)) {
                    $name = substr($name, 0, -strlen('.' . $zone_name));
                }
                if ($name === '') {
                    $name = '@';
                }
                $records[] = [
                    'name' => $name,
                    'type' => $rrset['type'],
                    'ttl' => $rrset['ttl'],
                    'content' => $record['content'],
                ];
            }
        }

        $this->logDebug('TEST', $records, $zone_name);
        $this->logError('TEST', $records, $zone_name);


        return [
            'status' => 'success',
            'data' => $records,
        ];
    }

    public function createZone($uuid): array
    {
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

        Task::add('ModuleJob', 'puqPowerDNS-Queue', $data, $tags);

        return ['status' => 'success'];
    }

    public function createZoneJob($uuid): array
    {
        $model_zone = DnsZone::where('uuid', $uuid)->first();
        if (empty($model_zone)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        $client = new puqPowerDnsClient($this->module_data);
        $remote_zone = $client->getZone($model_zone->name);

        if ($remote_zone['status'] == 'success') {
            return $this->reloadZone($uuid);
        }

        $zone_payload = $this->createZonePayload($model_zone);

        return $client->createZone($zone_payload);
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

        Task::add('ModuleJob', 'puqPowerDNS-Queue', $data, $tags);

        return ['status' => 'success'];
    }

    public function reloadZoneJob($uuid): array
    {
        $model_zone = DnsZone::where('uuid', $uuid)->first();
        if (empty($model_zone)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        $client = new puqPowerDnsClient($this->module_data);
        $remote_zone = $client->getZone($model_zone->name);

        if ($remote_zone['status'] == 'success') {
            $this->deleteZoneJob($model_zone->name);
        }

        return $this->createZoneJob($uuid);
    }

    public function deleteZone($name): array
    {
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

        Task::add('ModuleJob', 'puqPowerDNS-Queue', $data, $tags);

        return ['status' => 'success'];
    }

    public function deleteZoneJob($name): array
    {
        $client = new puqPowerDnsClient($this->module_data);
        $remote_zone = $client->getZone($name);
        if ($remote_zone['status'] == 'success') {
            return $client->deleteZone($name);
        }

        return ['status' => 'success'];
    }

    // Record actions ----------------------------------------------
    public function createRecord(string $uuid): array
    {
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

        Task::add('ModuleJob', 'puqPowerDNS-Queue', $data, $tags);

        return ['status' => 'success'];
    }

    public function createRecordJob(string $uuid): array
    {
        $record = DnsRecord::where('uuid', $uuid)->first();
        if (empty($record)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }
        $model_zone = $record->dnsZone;
        $rrsets_soa = $this->createRrsetsSOA($model_zone);

        $rrsets_all = $this->createRrsetsAll($model_zone, $record->name, $record->type);

        $rrsets = array_merge($rrsets_soa, $rrsets_all);

        $client = new puqPowerDnsClient($this->module_data);

        return $client->updateZone($model_zone->name, ['rrsets' => $rrsets]);
    }

    public function updateRecord(string $uuid, string $old_content): array
    {
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

        Task::add('ModuleJob', 'puqPowerDNS-Queue', $data, $tags);

        return ['status' => 'success'];
    }

    public function updateRecordJob(string $uuid, string $old_content): array
    {
        // Same as create
        return $this->createRecordJob($uuid);
    }

    public function deleteRecord(string $zone_uuid, string $name, string $type, string $content): array
    {
        $data = [
            'module' => $this,
            'method' => 'deleteRecordJob',
            'callback' => '',
            'tries' => 5,
            'backoff' => 60,
            'timeout' => 600,
            'maxExceptions' => 1,
            'params' => [$zone_uuid, $name, $type, $content],
        ];

        $tags = [
            'deleteRecord',
        ];

        Task::add('ModuleJob', 'puqPowerDNS-Queue', $data, $tags);

        return ['status' => 'success'];
    }

    public function deleteRecordJob(string $zone_uuid, string $name, string $type, string $content): array
    {
        $model_zone = DnsZone::where('uuid', $zone_uuid)->first();
        if (empty($model_zone)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Not found')],
                'code' => 404,
            ];
        }

        $rrsets_soa = $this->createRrsetsSOA($model_zone);

        $rrsets_all = $this->createRrsetsAll($model_zone, $name, $type);

        $rrsets = array_merge($rrsets_soa, $rrsets_all);

        $client = new puqPowerDnsClient($this->module_data);

        return $client->updateZone($model_zone->name, ['rrsets' => $rrsets]);
    }

    // Private ----------------------------------------------------------------------------------------------------
    private function createZonePayload(DnsZone $model_zone): array
    {
        $zone_array = [
            'name' => rtrim($model_zone->name, '.').'.',
            'kind' => 'Master',
            'dnssec' => false,
            'soa_edit' => 'NONE',
            'soa_edit_api' => 'NONE',
            'rrsets' => $this->createRrsets($model_zone),
        ];

        return $zone_array;
    }

    private function createRrsets(DnsZone $model_zone): array
    {
        $soa = $this->createRrsetsSOA($model_zone);
        $ns = $this->createRrsetsNS($model_zone);
        $all = $this->createRrsetsAll($model_zone);

        return array_merge($soa, $ns, $all);
    }

    private function createRrsetsSOA(DnsZone $model_zone): array
    {
        $soa = $model_zone->getSoa();

        $rrsets = [
            [
                'name' => rtrim($soa['name'], '.').'.',
                'type' => 'SOA',
                'ttl' => $soa['ttl'],
                'changetype' => 'REPLACE',
                'records' => [
                    [
                        'content' => $soa['content'],
                        'disabled' => false,
                    ],
                ],
            ],
        ];

        return $rrsets;
    }

    private function createRrsetsNS(DnsZone $model_zone): array
    {
        $ns = $model_zone->getNameServers();

        $rrsets = [
            [
                'name' => rtrim($model_zone->name, '.').'.',
                'type' => 'NS',
                'ttl' => $ns['ttl'],
                'changetype' => 'REPLACE',
                'records' => array_map(fn($server) => [
                    'content' => rtrim($server, '.').'.',
                    'disabled' => false,
                ], $ns['servers']),
            ],
        ];

        return $rrsets;
    }

    private function createRrsetsAll(DnsZone $model_zone, ?string $name = null, ?string $type = null): array
    {
        if ($name && $type) {
            $records = $model_zone->dnsRecords()
                ->where('name', $name)
                ->where('type', $type)
                ->get();
        } else {
            $records = $model_zone->dnsRecords;
        }

        $rrsets = [];

        if ($name && $type && $records->isEmpty()) {
            $record_name = $name === '@' || $name === ''
                ? rtrim($model_zone->name, '.').'.'
                : rtrim($name, '.').'.'.rtrim($model_zone->name, '.').'.';

            $rrsets[] = [
                'name' => $record_name,
                'type' => $type,
                'ttl' => 3600,
                'changetype' => 'DELETE',
                'records' => [],
            ];

            return $rrsets;
        }

        foreach ($records as $record) {
            $get_record_data = $model_zone->getRecord($record->uuid, $record);
            if ($get_record_data['status'] === 'error') {
                continue;
            }

            $data = $get_record_data['data'];
            $record_name = trim($data['name']);

            if ($record_name === '@' || $record_name === '') {
                $record_name = rtrim($model_zone->name, '.').'.';
            } else {
                $record_name = rtrim($record_name, '.').'.'.rtrim($model_zone->name, '.').'.';
            }

            $key = $record_name.'|'.$data['type'];

            if (!isset($rrsets[$key])) {
                $rrsets[$key] = [
                    'name' => $record_name,
                    'type' => $data['type'],
                    'ttl' => $data['ttl'],
                    'changetype' => 'REPLACE',
                    'records' => [],
                ];
            } else {
                $rrsets[$key]['ttl'] = $data['ttl'];
            }

            $rrsets[$key]['records'][] = [
                'content' => $data['content'],
                'disabled' => false,
            ];
        }

        return array_values($rrsets);
    }
}
