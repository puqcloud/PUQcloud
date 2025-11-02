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

namespace Modules\Product\puqProxmox\Models;

use App\Models\DnsZone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PuqPmDnsZone extends Model
{
    protected $table = 'puq_pm_dns_zones';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'ttl',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmLxcPresets(): HasMany
    {
        return $this->hasMany(PuqPmLxcPreset::class, 'puq_pm_dns_zone_uuid', 'uuid');
    }

    public function puqPmLxcInstances(): HasMany
    {
        return $this->hasMany(PuqPmLxcInstance::class, 'puq_pm_dns_zone_uuid', 'uuid');
    }

    public function getDnsZone(): ?DnsZone
    {
        return DnsZone::query()->where('name', $this->name)->first();
    }

    public function getRecordCount(): int
    {
        $type = $this->getZoneType();
        if ($type == 'forward') {

            $ipv4 = DB::table('puq_pm_lxc_instances')
                ->join('puq_pm_lxc_instance_nets', 'puq_pm_lxc_instance_nets.puq_pm_lxc_instance_uuid', '=',
                    'puq_pm_lxc_instances.uuid')
                ->where('puq_pm_lxc_instances.puq_pm_dns_zone_uuid', $this->uuid)
                ->where('puq_pm_lxc_instance_nets.type', 'public')
                ->whereNotNull('puq_pm_lxc_instance_nets.ipv4')
                ->count();
            $ipv6 = DB::table('puq_pm_lxc_instances')
                ->join('puq_pm_lxc_instance_nets', 'puq_pm_lxc_instance_nets.puq_pm_lxc_instance_uuid', '=',
                    'puq_pm_lxc_instances.uuid')
                ->where('puq_pm_lxc_instances.puq_pm_dns_zone_uuid', $this->uuid)
                ->where('puq_pm_lxc_instance_nets.type', 'public')
                ->whereNotNull('puq_pm_lxc_instance_nets.ipv6')
                ->count();

            return $ipv4 + $ipv6;
        }

        $ip_pools = $this->getMatchingIpPools();
        $count = 0;
        foreach ($ip_pools as $ip_pool) {
            $count += $ip_pool->getUsedIpCount();
        }

        return $count;
    }

    public function getZoneType(): string
    {
        $name = strtolower($this->name);

        if (str_ends_with($name, '.in-addr.arpa')) {
            return 'reverse_ipv4';
        }

        if (str_ends_with($name, '.ip6.arpa')) {
            return 'reverse_ipv6';
        }

        return 'forward';
    }

    public function getMatchingIpPools(): EloquentCollection
    {
        $zoneName = strtolower($this->name);
        $zoneType = $this->getZoneType();

        if ($zoneType === 'forward') {
            return new EloquentCollection;
        }

        $ipType = $zoneType === 'reverse_ipv4' ? 'ipv4' : 'ipv6';

        if ($ipType === 'ipv4') {
            $parts = explode('.', str_replace('.in-addr.arpa', '', $zoneName));
            $reversed = array_reverse($parts);
            $gateway = implode('.', $reversed);
        } else {
            $clean = str_replace('.ip6.arpa', '', $zoneName);
            $nibbles = array_reverse(explode('.', $clean));
            $gateway = implode('', $nibbles);
            $gateway = trim(preg_replace('/(.{4})/', '$1:', $gateway), ':');
            while (strlen(str_replace(':', '', $gateway)) % 4 !== 0) {
                $gateway .= '0';
            }
            $parts = explode(':', $gateway);
            if (count($parts) < 8) {
                $gateway .= str_repeat(':0', 8 - count($parts));
            }
        }

        return PuqPmIpPool::query()
            ->where('type', $ipType)
            ->where(function ($query) use ($gateway, $ipType) {
                if ($ipType === 'ipv4') {
                    $pattern = $gateway.'.%';
                    $query->where('first_ip', 'like', $pattern)
                        ->orWhere('last_ip', 'like', $pattern)
                        ->orWhere('gateway', 'like', $pattern);
                } else {
                    $normalized = strtolower($gateway);
                    $compress = compressIpv6($normalized);

                    $fullPattern = substr($normalized, 0, strrpos($normalized, ':')).':%';
                    $shortPattern = substr($compress, 0, strrpos($compress, ':')).':%';

                    $query->where(function ($sub) use ($shortPattern, $fullPattern) {
                        $sub->where('first_ip', 'like', $shortPattern)
                            ->orWhere('last_ip', 'like', $shortPattern)
                            ->orWhere('gateway', 'like', $shortPattern)
                            ->orWhere('first_ip', 'like', $fullPattern)
                            ->orWhere('last_ip', 'like', $fullPattern)
                            ->orWhere('gateway', 'like', $fullPattern);
                    });
                }
            })
            ->get();
    }

    public function checkRecordAvailability(string $record): bool
    {
        $lxc_instance_exist = PuqPmLxcInstance::query()->where('hostname', $record)->exists();

        return $lxc_instance_exist;
    }

    private function normalizeHostname(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);

        return trim($name, '-');
    }

    public function generateLxcHostname(string $pattern, string $country): string
    {
        $now = Carbon::now();

        $replacements = [
            '{YEAR}' => $now->format('Y'),
            '{MONTH}' => $now->format('m'),
            '{DAY}' => $now->format('d'),
            '{HOUR}' => $now->format('H'),
            '{MINUTE}' => $now->format('i'),
            '{SECOND}' => $now->format('s'),
            '{TIMESTAMP}' => time(),
            '{COUNTRY}' => strtolower($country),
        ];

        // Basic replacements
        $result = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        // Handle {RAND:X}
        $result = preg_replace_callback('/\{RAND:(\d+)\}/', function ($matches) {
            $len = (int) $matches[1];

            return substr(str_shuffle(str_repeat('0123456789', $len)), 0, $len);
        }, $result);

        // Handle {RSTR:X}
        $result = preg_replace_callback('/\{RSTR:(\d+)\}/', function ($matches) {
            $len = (int) $matches[1];
            $chars = 'abcdefghijklmnopqrstuvwxyz';

            return substr(str_shuffle(str_repeat($chars, $len)), 0, $len);
        }, $result);

        // Normalize base name
        $result = $this->normalizeHostname($result);

        // Increment if exists
        $hostname = $result;
        $counter = 1;
        while ($this->checkRecordAvailability($hostname)) {
            $hostname = $this->normalizeHostname($result.'-'.$counter);
            $counter++;
        }

        return $hostname;
    }

    public function updateForwardRecord($name, $ip, $shadow = false): void
    {
        $dns_zone = DnsZone::query()->where('name', $this->name)->first();
        if (!$dns_zone) {
            return;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $data = [
                'type' => 'A',
                'ipv4' => $ip,
                'name' => $name,
                'ttl' => $this->ttl ?? 300,
            ];
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $data = [
                'type' => 'AAAA',
                'ipv6' => $ip,
                'name' => $name,
                'ttl' => $this->ttl ?? 300,
            ];
        } else {
            return;
        }

        $records = $dns_zone->dnsRecords()->where('name', $name)->where('type', $data['type'])->get();
        foreach ($records as $record) {
            $dns_zone->deleteRecord($record->uuid);
        }

        $dns_zone->createUpdateRecord($data, null, $shadow);
    }

    public function updateReversRecord($ip, $content, $shadow = false): void
    {
        $dns_zone = DnsZone::query()->where('name', $this->name)->first();
        if (!$dns_zone) {
            return;
        }

        $name = $this->getReverseName($ip, $dns_zone->name);
        if (!$name) {
            return;
        }

        $data = [
            'type' => 'PTR',
            'name' => $name,
            'ptrdname' => $content,
            'ttl' => $this->ttl ?? 300,
        ];

        $records = $dns_zone->dnsRecords()->where('name', $name)->where('type', $data['type'])->get();
        foreach ($records as $record) {
            $dns_zone->deleteRecord($record->uuid);
        }

        $dns_zone->createUpdateRecord($data, null, $shadow);
    }

    public function deleteForwardRecord(string $name): void
    {
        $dns_zone = DnsZone::query()->where('name', $this->name)->first();
        if (!$dns_zone) {
            return;
        }

        $records = $dns_zone->dnsRecords()->where('name', $name)->get();
        foreach ($records as $record) {
            $dns_zone->deleteRecord($record->uuid);
        }
    }

    public function deleteReversRecord($ip): void
    {
        $dns_zone = DnsZone::query()->where('name', $this->name)->first();

        if (!$dns_zone) {
            return;
        }

        $name = $this->getReverseName($ip, $dns_zone->name);
        if (!$name) {
            return;
        }

        $records = $dns_zone->dnsRecords()->where('name', $name)->get();
        foreach ($records as $record) {
            $dns_zone->deleteRecord($record->uuid);
        }
    }


    private function getReverseName($ip, $zoneName): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $octets = explode('.', $ip);
            $fullPtr = implode('.', array_reverse($octets)).'.in-addr.arpa';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $expanded = inet_pton($ip);
            $hex = bin2hex($expanded);
            $nibbles = str_split($hex);
            $fullPtr = implode('.', array_reverse($nibbles)).'.ip6.arpa';
        } else {
            return '';
        }

        if (str_ends_with($fullPtr, $zoneName)) {
            $subdomain = substr($fullPtr, 0, -strlen($zoneName));
            $subdomain = trim($subdomain, '.');

            return $subdomain;
        }

        return '';
    }

    public function pushZone(): array
    {
        $dns_zone = DnsZone::query()->where('name', $this->name)->first();
        if (empty($dns_zone)) {
            return [
                'status' => 'error',
                'errors' => [__('Product.puqProxmox.Target Zone not found')],
                'code' => 404,
            ];
        }

        $zone_type = $this->getZoneType();

        if ($zone_type == 'forward') {

            $ips = PuqPmLxcInstance::query()
                ->join('puq_pm_lxc_instance_nets', 'puq_pm_lxc_instance_nets.puq_pm_lxc_instance_uuid', '=',
                    'puq_pm_lxc_instances.uuid')
                ->where('puq_pm_lxc_instances.puq_pm_dns_zone_uuid', $this->uuid)
                ->where('puq_pm_lxc_instance_nets.type', 'public')
                ->select('puq_pm_lxc_instance_nets.ipv4',
                    'puq_pm_lxc_instance_nets.ipv6',
                    'puq_pm_lxc_instances.hostname'
                )
                ->get();

            foreach ($ips as $ip) {
                if ($ip->ipv4) {
                    $this->updateForwardRecord($ip->hostname, $ip->ipv4, true);
                }
                if ($ip->ipv6) {
                    $this->updateForwardRecord($ip->hostname, $ip->ipv6, true);
                }
            }
        }

        if (in_array($zone_type, ['reverse_ipv4', 'reverse_ipv6'])) {
            $ip_pools = $this->getMatchingIpPools();
            foreach ($ip_pools as $ip_pool) {
                $ipv4s = $ip_pool->puqPmLxcInstanceNetsV4;

                foreach ($ipv4s as $ipv4) {
                    $this->updateReversRecord($ipv4->ipv4, $ipv4->rdns_v4, true);
                }

                $ipv6s = $ip_pool->puqPmLxcInstanceNetsV6;
                foreach ($ipv6s as $ipv6) {
                    $this->updateReversRecord($ipv6->ipv6, $ipv6->rdns_v6, true);
                }
            }
        }

        return $dns_zone->reloadZone();
    }

    public static function findReverseZoneByIp(string $ip): ?self
    {
        $ipType = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ipv4' :
            (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'ipv6' : null);

        if (!$ipType) {
            return null;
        }

        $zones = self::query()->get();

        foreach ($zones as $zone) {
            $zoneType = $zone->getZoneType();
            if (!str_starts_with($zoneType, 'reverse')) {
                continue;
            }

            $ipPools = $zone->getMatchingIpPools();
            foreach ($ipPools as $pool) {
                if ($ipType === 'ipv4') {
                    $nets = $pool->puqPmLxcInstanceNetsV4;
                    foreach ($nets as $net) {
                        if ($net->ipv4 === $ip || self::ipInRange($ip, $pool->first_ip, $pool->last_ip)) {
                            return $zone;
                        }
                    }
                } else {
                    $nets = $pool->puqPmLxcInstanceNetsV6;
                    foreach ($nets as $net) {
                        if ($net->ipv6 === $ip || self::ipInRange($ip, $pool->first_ip, $pool->last_ip)) {
                            return $zone;
                        }
                    }
                }
            }
        }

        return null;
    }

    private static function ipInRange(string $ip, string $start, string $end): bool
    {
        $ipBin = inet_pton($ip);
        $startBin = inet_pton($start);
        $endBin = inet_pton($end);

        return $ipBin >= $startBin && $ipBin <= $endBin;
    }

    public static function findReverseZoneByPoolUuid(string $poolUuid): ?self
    {
        $pool = PuqPmIpPool::find($poolUuid);
        if (!$pool) {
            return null;
        }

        $ipType = $pool->type;
        $zones = self::query()->get();

        foreach ($zones as $zone) {
            $zoneType = $zone->getZoneType();
            if (($ipType === 'ipv4' && $zoneType === 'reverse_ipv4') ||
                ($ipType === 'ipv6' && $zoneType === 'reverse_ipv6')) {

                $ipPools = $zone->getMatchingIpPools();
                foreach ($ipPools as $zPool) {
                    if ($zPool->uuid === $poolUuid) {
                        return $zone;
                    }
                }
            }
        }

        return null;
    }
}
