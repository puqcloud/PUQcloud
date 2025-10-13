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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DnsZone extends Model
{
    use HasFactory;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'dns_zones';

    protected $fillable = [
        'name',
        'description',
        'soa_admin_email',
        'soa_ttl',
        'soa_refresh',
        'soa_retry',
        'soa_expire',
        'soa_minimum',
        'dns_server_group_uuid',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function delete(): bool
    {
        try {
            $delete_remote_zone = $this->deleteZone();
            if ($delete_remote_zone['status'] === 'error') {
                throw new \Exception(implode(', ', $delete_remote_zone['errors'] ?? ['Remote deletion failed']));
            }

            return parent::delete();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function dnsServerGroup(): BelongsTo
    {
        return $this->belongsTo(DnsServerGroup::class, 'dns_server_group_uuid', 'uuid');
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DnsRecord::class, 'dns_zone_uuid', 'uuid');
    }

    public function getSoaPrimaryNs(): string
    {
        return $this->dnsServerGroup->ns_domains[0] ?? '';
    }

    public function getNameServers(): array
    {
        return [
            'servers' => $this->dnsServerGroup->ns_domains ?? [],
            'ttl' => $this->dnsServerGroup->dns_ttl ?? 3600,
        ];
    }

    public function getSoa(): array
    {
        $primaryNs = $this->getSoaPrimaryNs();

        $serial = time();

        $soa = [
            'name' => $this->name,
            'admin' => $this->soa_admin_email,
            'serial' => $serial,
            'refresh' => $this->soa_refresh,
            'retry' => $this->soa_retry,
            'expire' => $this->soa_expire,
            'minimum' => $this->soa_minimum,
            'ttl' => $this->soa_ttl,
        ];

        $soa['content'] = sprintf(
            '%s %s %d %d %d %d %d',
            rtrim($primaryNs, '.').'.',
            str_replace('@', '.', $soa['admin']).'.',
            $soa['serial'],
            $soa['refresh'],
            $soa['retry'],
            $soa['expire'],
            $soa['minimum']
        );

        return $soa;
    }

    protected function runOnDnsServers(callable $action): array
    {
        $dns_server_group = $this->dnsServerGroup;
        $dns_servers = $dns_server_group->dnsServers;

        $errors = [];

        foreach ($dns_servers as $dns_server) {
            $result = $action($dns_server);

            if (isset($result['status']) && $result['status'] === 'error' && !empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $errors[] = 'Server: '.$dns_server->name.' Error: '.$error;
                }
            }
        }

        if (!empty($errors)) {
            return [
                'status' => 'error',
                'errors' => $errors,
                'code' => 500,
            ];
        }

        return ['status' => 'success'];
    }

    // exports ----------------------------------------------------------------------
    public function exportBind(): array
    {
        $soa = $this->getSoa();
        $ns = $this->getNameServers();
        $admin_email = str_replace('@', '.', $soa['admin']).'.';

        $bind = "\$TTL {$soa['ttl']}\n";

        $firstLine = "@ IN SOA {$ns['servers'][0]}. {$admin_email} (";
        $bind .= $firstLine."\n";

        $pos = strpos($firstLine, '(') + 1;
        $padding = str_repeat(' ', $pos);

        $bind .= $padding."{$soa['serial']} ; Serial\n";
        $bind .= $padding."{$soa['refresh']} ; Refresh\n";
        $bind .= $padding."{$soa['retry']} ; Retry\n";
        $bind .= $padding."{$soa['expire']} ; Expire\n";
        $bind .= $padding."{$soa['minimum']} ; Minimum TTL\n";
        $bind .= ")\n\n";

        foreach ($ns['servers'] as $server) {
            $bind .= "@ IN NS {$server}.\n";
        }
        $bind .= "\n";

        $maxNameLen = 0;
        $maxTtlLen = 0;
        $maxTypeLen = 0;

        $recordsData = [];
        foreach ($this->dnsRecords as $dns_record) {
            $rec = $this->getRecord($dns_record->uuid, $dns_record);
            if ($rec['status'] === 'error') {
                continue;
            }

            $r = $rec['data'];
            $recordsData[] = $r;

            $nameLen = strlen($r['name'] === '@' ? '@' : $r['name']);
            $ttlLen = strlen((string) ($r['ttl'] ?? $soa['ttl']));
            $typeLen = strlen($r['type']);

            if ($nameLen > $maxNameLen) {
                $maxNameLen = $nameLen;
            }
            if ($ttlLen > $maxTtlLen) {
                $maxTtlLen = $ttlLen;
            }
            if ($typeLen > $maxTypeLen) {
                $maxTypeLen = $typeLen;
            }
        }

        foreach ($recordsData as $r) {
            $name = $r['name'] === '@' ? '@' : $r['name'];
            $ttl = $r['ttl'] ?? $soa['ttl'];
            $type = $r['type'];
            $content = $r['content'];

            $namePadding = str_repeat(' ', $maxNameLen - strlen($name) + 1);
            $ttlPadding = str_repeat(' ', $maxTtlLen - strlen($ttl) + 1);
            $typePadding = str_repeat(' ', $maxTypeLen - strlen($type) + 1);

            $bind .= "{$name}{$namePadding}{$ttl}{$ttlPadding}IN {$type}{$typePadding}{$content}\n";
        }

        return [
            'status' => 'success',
            'data' => $bind,
        ];
    }

    public function exportJson(): array
    {
        $soa = $this->getSoa();
        $ns = $this->getNameServers();
        $records = [];

        foreach ($this->dnsRecords as $dns_record) {
            $rec = $this->getRecord($dns_record->uuid, $dns_record);
            if ($rec['status'] === 'error') {
                continue;
            }

            unset($rec['data']['uuid']);
            unset($rec['data']['dns_zone_uuid']);
            unset($rec['data']['created_at']);
            unset($rec['data']['updated_at']);

            $records[] = $rec['data'];
        }

        return [
            'status' => 'success',
            'data' => [
                'soa' => $soa,
                'ns' => $ns,
                'records' => $records,
            ],
        ];
    }

    // zone action ---------------------------------------------------------------
    public static function createZone(array $data): array
    {

        $validator = Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:dns_zones,name',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                        $fail(__('error.The name must be a valid DNS zone'));
                    }
                },
            ],
            'soa_ttl' => 'required|integer|min:30',
            'soa_admin_email' => 'required|email|max:255',
            'soa_refresh' => 'required|integer|min:30',
            'soa_retry' => 'required|integer|min:30',
            'soa_expire' => 'required|integer|min:30',
            'soa_minimum' => 'required|integer|min:30',
            'dns_server_group_uuid' => 'required|uuid|exists:dns_server_groups,uuid',
        ], [
            'name.required' => __('error.The name field is required'),
            'name.string' => __('error.The name must be a string'),
            'name.max' => __('error.The name may not be greater than 255 characters'),
            'name.unique' => __('error.A DNS zone with this name already exists'),

            'soa_ttl.required' => __('error.The SOA TTL field is required'),
            'soa_ttl.integer' => __('error.The SOA TTL must be an integer'),
            'soa_ttl.min' => __('error.The SOA TTL must be at least 30'),

            'soa_admin_email.required' => __('error.The SOA admin email field is required'),
            'soa_admin_email.email' => __('error.The SOA admin email must be a valid email address'),
            'soa_admin_email.max' => __('error.The SOA admin email may not be greater than 255 characters'),

            'soa_refresh.required' => __('error.The SOA refresh field is required'),
            'soa_refresh.integer' => __('error.The SOA refresh must be an integer'),
            'soa_refresh.min' => __('error.The SOA refresh must be at least 30'),

            'soa_retry.required' => __('error.The SOA retry field is required'),
            'soa_retry.integer' => __('error.The SOA retry must be an integer'),
            'soa_retry.min' => __('error.The SOA retry must be at least 30'),

            'soa_expire.required' => __('error.The SOA expire field is required'),
            'soa_expire.integer' => __('error.The SOA expire must be an integer'),
            'soa_expire.min' => __('error.The SOA expire must be at least 30'),

            'soa_minimum.required' => __('error.The SOA minimum field is required'),
            'soa_minimum.integer' => __('error.The SOA minimum must be an integer'),
            'soa_minimum.min' => __('error.The SOA minimum must be at least 30'),

            'dns_server_group_uuid.required' => __('error.The DNS server group field is required'),
            'dns_server_group_uuid.uuid' => __('error.The DNS server group must be a valid UUID'),
            'dns_server_group_uuid.exists' => __('error.The selected DNS server group does not exist'),
        ]);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        try {
            $zone = self::create($data);
            $zone->runOnDnsServers(fn($dns_server) => $dns_server->createZone($zone->uuid));

            return [
                'status' => 'success',
                'data' => $zone,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => null,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    public function reloadZone(): array
    {
        return $this->runOnDnsServers(fn($dns_server) => $dns_server->reloadZone($this->uuid));
    }

    public function deleteZone(): array
    {
        return $this->runOnDnsServers(fn($dns_server) => $dns_server->deleteZone($this->name));
    }

    // record actions ------------------------------------------------------------
    public function getRecord($uuid, $record = null): array
    {
        if (empty($record)) {
            $record = $this->dnsRecords()->where('uuid', $uuid)->first();
        }

        if (empty($record)) {
            return [
                'status' => 'error',
                'errors' => [__('error.DNS record not found')],
            ];
        }

        $type = strtoupper($record->type);
        $method = 'buildRecord'.$type;

        if (method_exists($this, $method)) {
            $build_record = $this->{$method}($record->toArray(), true);
        } else {
            return [
                'status' => 'error',
                'message' => ['type' => [__('error.Type not supported')]],
                'errors' => [__('error.Type not supported')],
                'code' => 404,
            ];
        }

        if ($build_record['status'] == 'error') {
            $build_record['code'] = 412;

            return $build_record;
        }

        return [
            'status' => 'success',
            'data' => $build_record['data'] ?? [],
        ];

    }

    public function createUpdateRecord(array $data, $uuid = null): array
    {

        // ----------------------------------------------------------------------
        if ($uuid) {
            $record = $this->dnsRecords()->where('uuid', $uuid)->first();
            if (empty($record)) {
                return [
                    'status' => 'error',
                    'errors' => [__('error.DNS record not found')],
                    'code' => 404,
                ];
            }
            $data['type'] = strtoupper($record->type);
            $data['name'] = $record->name;

        } else {
            $record = new DnsRecord($data);
        }
        // ----------------------------------------------------------------------
        $type = strtoupper($data['type']);
        $zoneName = $this->name;

        $isReverseZone = str_ends_with($zoneName, '.in-addr.arpa') || str_ends_with($zoneName, '.ip6.arpa');

        if ($isReverseZone) {
            if ($type !== 'PTR') {
                return [
                    'status' => 'error',
                    'message' => ['type' => [__('error.Only PTR records allowed in reverse zones')]],
                    'errors' => [__('error.Only PTR records allowed in reverse zones')],
                    'code' => 412,
                ];
            }
        } else {
            if ($type === 'PTR') {
                return [
                    'status' => 'error',
                    'message' => ['type' => [__('error.PTR records only allowed in reverse zones')]],
                    'errors' => [__('error.PTR records only allowed in reverse zones')],
                    'code' => 412,
                ];
            }
        }
        // ----------------------------------------------------------------------
        $type = strtoupper($data['type']);
        $method = 'buildRecord'.$type;

        if (method_exists($this, $method)) {
            $build_record = $this->{$method}($data);
        } else {
            return [
                'status' => 'error',
                'message' => ['type' => [__('error.Type not supported')]],
                'errors' => [__('error.Type not supported')],
                'code' => 404,
            ];
        }

        if ($build_record['status'] == 'error') {
            $build_record['code'] = 412;

            return $build_record;
        }
        $content = $build_record['data']['content'];

        $exist = $this->dnsRecords()
            ->where('type', $data['type'])
            ->where('name', $data['name'])
            ->where('content', $content)
            ->where('uuid', '<>', $uuid)
            ->exists();

        if ($exist) {
            return [
                'status' => 'error',
                'errors' => [__('error.DNS record exists')],
                'code' => 412,
            ];
        }

        // ----------------------------------------------------------------------
        $record->name = $data['name'];
        $record->ttl = $data['ttl'];
        $old_content = $record->content;
        $record->content = $content;
        $record->description = strip_tags($data['description'] ?? $record->description);
        $record->dns_zone_uuid = $this->uuid;
        $record->save();
        $record->refresh();


        if ($uuid) {
            $update_remote = $this->runOnDnsServers(fn($dns_server) => $dns_server->updateRecord($record->uuid,
                $old_content));

            if ($update_remote['status'] == 'error') {
                return $update_remote;
            }
        } else {
            $create_remote = $this->runOnDnsServers(fn($dns_server) => $dns_server->createRecord($record->uuid));

            if ($create_remote['status'] == 'error') {
                return $create_remote;
            }
        }

        return [
            'status' => 'success',
            'data' => $record,
        ];
    }

    private function buildRecordA(array $data, bool $reverse = false): array
    {

        if ($reverse) {
            $data['ipv4'] = $data['content'] ?? null;
        } else {
            $data['content'] = $data['ipv4'] ?? null;
        }

        $rules = [
            'type' => 'required|in:A',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value !== '@' && !preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('error.The name must be a valid subdomain prefix or @ for root'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'ipv4' => 'required|ipv4',
        ];

        $messages = [
            'type.required' => __('error.Type is required'),
            'type.in' => __('error.Invalid type selected'),
            'name.required' => __('error.Name is required'),
            'ttl.required' => __('error.TTL is required'),
            'ttl.integer' => __('error.TTL must be an integer'),
            'ttl.min' => __('error.TTL must be at least 30'),
            'ipv4.required' => __('error.IP address is required'),
            'ipv4.ipv4' => __('error.Invalid IPv4 address'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordAAAA(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $data['ipv6'] = $data['content'] ?? null;
        } else {
            $data['content'] = $data['ipv6'] ?? null;
        }

        $rules = [
            'type' => 'required|in:AAAA',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value !== '@' && !preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('error.The name must be a valid subdomain prefix or @ for root'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'ipv6' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $fail(__('error.Invalid IPv6 address'));
                    }
                },
            ],
        ];

        $messages = [
            'type.required' => __('error.Type is required'),
            'type.in' => __('error.Invalid type selected'),
            'name.required' => __('error.Name is required'),
            'ttl.required' => __('error.TTL is required'),
            'ttl.integer' => __('error.TTL must be an integer'),
            'ttl.min' => __('error.TTL must be at least 30'),
            'ipv6.required' => __('error.IP address is required'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordCNAME(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $data['target'] = isset($data['content']) ? rtrim($data['content'], '.') : null;
        } else {
            $data['content'] = isset($data['target']) ? rtrim($data['target'], '.').'.' : null;
        }

        $rules = [
            'type' => 'required|in:CNAME',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value === '@') {
                        $fail(__('error.CNAME record cannot be used on the root domain'));
                    } elseif (!preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('error.The name must be a valid subdomain prefix'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'target' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                        $fail(__('error.Invalid target domain for CNAME record'));
                    }
                },
            ],
        ];

        $messages = [
            'type.required' => __('error.Type is required'),
            'type.in' => __('error.Invalid type selected'),
            'name.required' => __('error.Name is required'),
            'ttl.required' => __('error.TTL is required'),
            'ttl.integer' => __('error.TTL must be an integer'),
            'ttl.min' => __('error.TTL must be at least 30'),
            'target.required' => __('error.Target is required'),
            'target.string' => __('error.Target must be a valid string'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordMX(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $parts = isset($data['content']) ? explode(' ', $data['content'], 2) : [];
            $data['priority'] = $parts[0] ?? null;
            $data['mailServer'] = isset($parts[1]) ? rtrim($parts[1], '.') : null;
        } else {
            if (!isset($data['priority']) || $data['priority'] < 0 || $data['priority'] > 65535) {
                $data['priority'] = 10;
            }

            $data['content'] = $data['priority'].' '.(isset($data['mailServer']) ? rtrim($data['mailServer'],
                        '.').'.' : '');
        }

        $rules = [
            'type' => 'required|in:MX',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value !== '@' && !preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('error.The name must be a valid subdomain prefix or @ for root'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'priority' => 'required|integer|min:0|max:65535',
            'mailServer' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                        $fail(__('error.Invalid mail server for MX record'));
                    }
                },
            ],
        ];

        $messages = [
            'type.required' => __('error.Type is required'),
            'type.in' => __('error.Invalid type selected'),
            'name.required' => __('error.Name is required'),
            'ttl.required' => __('error.TTL is required'),
            'ttl.integer' => __('error.TTL must be an integer'),
            'ttl.min' => __('error.TTL must be at least 30'),
            'priority.required' => __('error.Priority is required'),
            'priority.integer' => __('error.Priority must be an integer'),
            'priority.min' => __('error.Priority must be at least 0'),
            'priority.max' => __('error.Priority cannot exceed 65535'),
            'mailServer.required' => __('error.Mail server is required'),
            'mailServer.string' => __('error.Mail server must be a valid string'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordTXT(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $content = trim($data['content'] ?? '');
            $content = str_replace('" "', '', $content);
            $content = preg_replace('/^"|"$|"\s+"|"\s*$/', '', $content);
            $content = stripcslashes($content);
            $data['txt'] = $content;
        } else {
            $content = trim($data['txt'] ?? '');
            $content = addcslashes($content, "\\\"\n\r\t");
            $max_length = 255;
            $parts = str_split($content, $max_length);
            $record_txt = '';
            foreach ($parts as $part) {
                $record_txt .= '"'.$part.'" ';
            }

            $data['content'] = trim($record_txt);
        }

        $rules = [
            'type' => 'required|in:TXT',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value !== '@' && !preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('error.The name must be a valid subdomain prefix or @ for root'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'txt' => 'required|string|max:65535',
        ];

        $messages = [
            'type.required' => __('error.Type is required'),
            'type.in' => __('error.Invalid type selected'),
            'name.required' => __('error.Name is required'),
            'ttl.required' => __('error.TTL is required'),
            'ttl.integer' => __('error.TTL must be an integer'),
            'ttl.min' => __('error.TTL must be at least 30'),
            'txt.required' => __('error.Content is required'),
            'txt.string' => __('error.Content must be text'),
            'txt.max' => __('error.Content too long'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordSRV(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $parts = isset($data['content']) ? explode(' ', $data['content'], 4) : [];
            $data['priority'] = $parts[0] ?? 65535;
            $data['weight'] = $parts[1] ?? 0;
            $data['port'] = $parts[2] ?? 1;
            $data['target'] = isset($parts[3]) ? rtrim($parts[3], '.') : null;
        } else {
            $data['priority'] = $data['priority'] ?? 65535;
            $data['weight'] = $data['weight'] ?? 0;
            $data['port'] = $data['port'] ?? 1;

            if ($data['priority'] < 0 || $data['priority'] > 65535) {
                $data['priority'] = 65535;
            }
            if ($data['weight'] < 0 || $data['weight'] > 65535) {
                $data['weight'] = 0;
            }
            if ($data['port'] < 1 || $data['port'] > 65535) {
                $data['port'] = 1;
            }

            $data['content'] = $data['priority'].' '.$data['weight'].' '.$data['port'].' '.(isset($data['target']) ? rtrim($data['target'],
                        '.').'.' : '');
        }

        $rules = [
            'type' => 'required|in:SRV',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value !== '@' && !preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('error.The name must be a valid subdomain prefix or @ for root'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'priority' => 'required|integer|min:0|max:65535',
            'weight' => 'required|integer|min:0|max:65535',
            'port' => 'required|integer|min:1|max:65535',
            'target' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                        $fail(__('error.Invalid target for SRV record'));
                    }
                },
            ],
        ];

        $messages = [
            'type.required' => __('error.Type is required'),
            'type.in' => __('error.Invalid type selected'),
            'name.required' => __('error.Name is required'),
            'ttl.required' => __('error.TTL is required'),
            'ttl.integer' => __('error.TTL must be an integer'),
            'ttl.min' => __('error.TTL must be at least 30'),
            'priority.required' => __('error.Priority is required'),
            'priority.integer' => __('error.Priority must be an integer'),
            'priority.min' => __('error.Priority must be at least 0'),
            'priority.max' => __('error.Priority cannot exceed 65535'),
            'weight.required' => __('error.Weight is required'),
            'weight.integer' => __('error.Weight must be an integer'),
            'weight.min' => __('error.Weight must be at least 0'),
            'weight.max' => __('error.Weight cannot exceed 65535'),
            'port.required' => __('error.Port is required'),
            'port.integer' => __('error.Port must be an integer'),
            'port.min' => __('error.Port must be at least 1'),
            'port.max' => __('error.Port cannot exceed 65535'),
            'target.required' => __('error.Target is required'),
            'target.string' => __('error.Target must be a valid string'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordALIAS(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $data['target'] = isset($data['content']) ? rtrim($data['content'], '.') : null;
        } else {
            $data['target'] = isset($data['target']) ? rtrim($data['target'], '.') : null;
            $data['content'] = $data['target'] ? $data['target'].'.' : '';
        }

        $rules = [
            'type' => 'required|in:ALIAS',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value !== '@' && !preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('The name must be a valid subdomain prefix or @ for root'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'target' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                        $fail(__('Invalid target for ALIAS record'));
                    }
                },
            ],
        ];

        $messages = [
            'type.required' => __('Type is required'),
            'type.in' => __('Invalid type selected'),
            'name.required' => __('Name is required'),
            'ttl.required' => __('TTL is required'),
            'ttl.integer' => __('TTL must be an integer'),
            'ttl.min' => __('TTL must be at least 30'),
            'target.required' => __('Target is required'),
            'target.string' => __('Target must be a valid string'),
            'Invalid target for ALIAS record' => __('Invalid target for ALIAS record'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordNS(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $parts = isset($data['content']) ? explode(' ', $data['content'], 2) : [];
            $data['target'] = isset($parts[0]) ? rtrim($parts[0], '.') : null;
        } else {
            $data['target'] = isset($data['target']) ? rtrim($data['target'], '.') : null;
            $data['content'] = $data['target'] ? $data['target'].'.' : '';
        }

        $rules = [
            'type' => 'required|in:NS',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value === '@') {
                        $fail(__('error.NS record cannot be used on the root domain'));
                    } elseif (!preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('error.The name must be a valid subdomain prefix'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'target' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                        $fail(__('error.Invalid NS target'));
                    }
                },
            ],
        ];

        $messages = [
            'type.required' => __('Type is required'),
            'type.in' => __('Invalid type selected'),
            'name.required' => __('Name is required'),
            'ttl.required' => __('TTL is required'),
            'ttl.integer' => __('TTL must be an integer'),
            'ttl.min' => __('TTL must be at least 30'),
            'NS record cannot be used on the root domain' => __('NS record cannot be used on the root domain'),
            'The name must be a valid subdomain prefix' => __('The name must be a valid subdomain prefix'),
            'ns.required' => __('NS target is required'),
            'ns.string' => __('NS target must be a valid string'),
            'Invalid NS target' => __('Invalid NS target'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordCAA(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $parts = isset($data['content']) ? explode(' ', $data['content'], 3) : [];
            $data['flag'] = isset($parts[0]) ? (int) $parts[0] : 0;
            $data['tag'] = $parts[1] ?? null;
            $data['value'] = isset($parts[2]) ? trim($parts[2], '"') : null;
        } else {
            $data['flag'] = $data['flag'] ?? 0;
            $data['tag'] = $data['tag'] ?? 'issue';
            $data['value'] = $data['value'] ?? '';

            $data['content'] = $data['flag'].' '.$data['tag'].' "'.$data['value'].'"';
        }

        $rules = [
            'type' => 'required|in:CAA',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    if ($value !== '@' && !preg_match('/^[a-z0-9-]+$/i', $value)) {
                        $fail(__('The name must be a valid subdomain prefix or @ for root'));
                    }
                },
            ],
            'ttl' => 'required|integer|min:30',
            'flag' => 'required|integer|min:0|max:255',
            'tag' => 'required|string|in:issue,issuewild,iodef',
            'value' => 'required|string|max:255',
        ];

        $messages = [
            'type.required' => __('Type is required'),
            'type.in' => __('Invalid type selected'),
            'name.required' => __('Name is required'),
            'ttl.required' => __('TTL is required'),
            'ttl.integer' => __('TTL must be an integer'),
            'ttl.min' => __('TTL must be at least 30'),
            'flag.required' => __('Flag is required'),
            'flag.integer' => __('Flag must be an integer'),
            'flag.min' => __('Flag must be at least 0'),
            'flag.max' => __('Flag cannot exceed 255'),
            'tag.required' => __('Tag is required'),
            'tag.in' => __('Tag must be one of: issue, issuewild, iodef'),
            'value.required' => __('Value is required'),
            'value.string' => __('Value must be a string'),
            'value.max' => __('Value cannot exceed 255 characters'),
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    private function buildRecordPTR(array $data, bool $reverse = false): array
    {
        if ($reverse) {
            $parts = isset($data['content']) ? explode(' ', $data['content'], 2) : [];
            $data['ptrdname'] = isset($parts[0]) ? rtrim($parts[0], '.') : null;
        } else {
            $data['ptrdname'] = isset($data['ptrdname']) ? rtrim($data['ptrdname'], '.') : null;
            $data['content'] = $data['ptrdname'] ? $data['ptrdname'].'.' : '';
        }

        $rules = [
            'type' => 'required|in:PTR',
            'name' => [
                'required',
                'string',
                'max:63',
                function ($attribute, $value, $fail) {
                    $zone = $this->name ?? '';

                    if (preg_match('/^(\d{1,3}\.)+in-addr\.arpa$/', $zone)) {
                        // IPv4
                        $zoneOctets = explode('.', str_replace('.in-addr.arpa', '', $zone));
                        $valueOctets = explode('.', $value);
                        $totalOctets = count($zoneOctets) + count($valueOctets);
                        if ($totalOctets !== 4) {
                            $fail(__('error.Invalid IPv4 PTR name length for this reverse zone'));
                        }
                    } elseif (preg_match('/^([0-9a-f]\.)+ip6\.arpa$/i', $zone)) {
                        // IPv6
                        $zoneNibbles = explode('.', str_replace('.ip6.arpa', '', $zone));
                        $valueNibbles = explode('.', $value);
                        $totalNibbles = count($zoneNibbles) + count($valueNibbles);
                        if ($totalNibbles !== 32) {
                            $fail(__('error.Invalid IPv6 PTR name length for this reverse zone'));
                        }
                    } else {
                        $fail(__('error.Zone is not a valid reverse zone'));
                    }

                },
            ],
            'ttl' => 'required|integer|min:30',
            'ptrdname' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                        $fail(__('error.Invalid PTR target'));
                    }
                },
            ],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $allErrors = [];
            foreach ($validator->errors()->all() as $error) {
                $allErrors[] = $error;
            }

            return [
                'status' => 'error',
                'message' => $validator->errors(),
                'errors' => $allErrors,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data,
        ];
    }

    public function deleteRecord($uuid): array
    {
        $record = $this->dnsRecords()->where('uuid', $uuid)->first();
        if (empty($record)) {
            return [
                'status' => 'error',
                'errors' => [__('error.DNS record not found')],
            ];
        }

        $record_name = $record->name;
        $record_type = $record->type;
        $record_content = $record->content;
        $record->delete();

        $delete_remote = $this->runOnDnsServers(fn($dns_server) => $dns_server->deleteRecord($this->uuid, $record_name,
            $record_type, $record_content));

        if ($delete_remote['status'] == 'error') {
            return $delete_remote;
        }

        return [
            'status' => 'success',
        ];
    }
}
