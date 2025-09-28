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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PuqPmIpPool extends Model
{
    protected $table = 'puq_pm_ip_pools';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'type',
        'first_ip',
        'last_ip',
        'mask',
        'gateway',
        'dns',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmLxcInstanceNetsV4(): HasMany
    {
        return $this->hasMany(puqPmLxcInstanceNet::class, 'puq_pm_ipv4_pool_uuid', 'uuid');
    }

    public function puqPmLxcInstanceNetsV6(): HasMany
    {
        return $this->hasMany(puqPmLxcInstanceNet::class, 'puq_pm_ipv6_pool_uuid', 'uuid');
    }

    public function puqPmPublicNetworks(): HasMany
    {
        return $this->hasMany(PuqPmPublicNetwork::class, 'puq_pm_ip_pool_uuid', 'uuid');
    }

    public function getIpCount(): int
    {
        if ($this->type === 'ipv4') {
            $start = ip2long($this->first_ip);
            $end = ip2long($this->last_ip);

            return ($start !== false && $end !== false && $end >= $start) ? ($end - $start + 1) : 0;
        }

        if ($this->type === 'ipv6') {
            $start = inet_pton($this->first_ip);
            $end = inet_pton($this->last_ip);

            if ($start === false || $end === false || strlen($start) !== 16 || strlen($end) !== 16) {
                return 0;
            }

            $result = 0;
            for ($i = 0; $i < 16; $i++) {
                $s = ord($start[$i]);
                $e = ord($end[$i]);

                // Early exit if start > end
                if ($s > $e && $result === 0) {
                    return 0;
                }

                $result = ($result << 8) + ($e - $s);
            }

            return $result + 1;
        }

        return 0;
    }

    public function getUsedIpCount(): int
    {
        if ($this->type === 'ipv4') {
            $puq_pm_lxc_instance_net_v4_count = $this->puqPmLxcInstanceNetsV4()->count();

            return $puq_pm_lxc_instance_net_v4_count;
        }

        if ($this->type === 'ipv6') {
            $puq_pm_lxc_instance_net_v6_count = $this->puqPmLxcInstanceNetsV6()->count();

            return $puq_pm_lxc_instance_net_v6_count;
        }

        return 0;
    }

    public function hasAvailableIp(): bool
    {
        $total = $this->getIpCount();
        $used = $this->getUsedIpCount();

        return ($total - $used) > 0;
    }

    public function getIp(): ?string
    {
        if ($this->type === 'ipv4') {
            $start = ip2long($this->first_ip);
            $end = ip2long($this->last_ip);

            if ($start === false || $end === false || $end < $start) {
                return null;
            }

            $usedIps = $this->puqPmLxcInstanceNetsV4()->pluck('ipv4')->map(fn($ip) => ip2long($ip))
                ->toArray();

            $usedIps = array_flip($usedIps);

            for ($ip = $start; $ip <= $end; $ip++) {
                if (!isset($usedIps[$ip])) {
                    return long2ip($ip);
                }
            }
        } elseif ($this->type === 'ipv6') {
            $start = inet_pton($this->first_ip);
            $end = inet_pton($this->last_ip);

            if ($start === false || $end === false || strlen($start) !== 16 || strlen($end) !== 16) {
                return null;
            }

            $usedIps = $this->puqPmLxcInstanceNetsV6()->pluck('ipv6')->toArray();

            $usedIpsInt = [];
            foreach ($usedIps as $ip) {
                $packed = inet_pton($ip);
                if ($packed !== false) {
                    $usedIpsInt[bin2hex($packed)] = true;
                }
            }

            $current = $start;
            while (strcmp($current, $end) <= 0) {
                $currentHex = bin2hex($current);
                if (!isset($usedIpsInt[$currentHex])) {
                    return inet_ntop($current);
                }

                // increment $current by 1 (binary)
                $current = $this->incrementBinaryString($current);
                if ($current === null) {
                    break;
                } // overflow safety
            }
        }

        return null;
    }

    private function incrementBinaryString(string $bin): ?string
    {
        $arr = unpack('C*', $bin);
        for ($i = count($arr); $i >= 1; $i--) {
            if ($arr[$i] < 255) {
                $arr[$i]++;
                for ($j = $i + 1; $j <= count($arr); $j++) {
                    $arr[$j] = 0;
                }

                return call_user_func_array('pack', array_merge(['C*'], $arr));
            }
        }

        return null; // overflow, no increment possible
    }


    /**
     * Compress IPv6 fields (in-place).
     */
    public function compressIps(): void
    {
        if ($this->type !== 'ipv6') {
            return;
        }

        $this->first_ip = self::compress($this->first_ip);
        $this->last_ip = self::compress($this->last_ip);
        $this->gateway = self::compress($this->gateway);

        $dnsList = array_map('trim', explode(',', $this->dns));
        $dnsList = array_filter($dnsList);
        $compressedDns = [];

        foreach ($dnsList as $ip) {
            $compressedDns[] = self::compress($ip);
        }

        $this->dns = implode(',', $compressedDns);
    }

    /**
     * Compress single IP if IPv6.
     */
    public static function compress(string $ip): string
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? inet_ntop(inet_pton($ip))
            : $ip;
    }

    /**
     * Check if IP is in the given range.
     */
    public static function ipInRange(string $ip, string $start, string $end, string $type): bool
    {
        if ($type === 'ipv4') {
            $ipLong = ip2long($ip);

            return $ipLong >= ip2long($start) && $ipLong <= ip2long($end);
        }

        $ipBin = inet_pton($ip);

        return strcmp($ipBin, inet_pton($start)) >= 0 && strcmp($ipBin, inet_pton($end)) <= 0;
    }

    /**
     * Compare two IPs to ensure $first <= $last
     */
    public static function isValidRange(string $first, string $last, string $type): bool
    {
        if ($type === 'ipv4') {
            return ip2long($first) <= ip2long($last);
        }

        return strcmp(inet_pton($first), inet_pton($last)) <= 0;
    }

    /**
     * Check if all IPs are in the same subnet.
     */
    public static function sameSubnet(string $first, string $second, string $third, string $type, int $mask): bool
    {
        if ($type === 'ipv4') {
            $netmask = -1 << (32 - $mask);

            return (ip2long($first) & $netmask) === (ip2long($second) & $netmask)
                && (ip2long($first) & $netmask) === (ip2long($third) & $netmask);
        }

        $firstBin = inet_pton($first);
        $secondBin = inet_pton($second);
        $thirdBin = inet_pton($third);

        $maskBin = str_repeat("f", intval($mask / 4));
        $remaining = $mask % 4;
        if ($remaining > 0) {
            $map = ['0' => '0', '1' => '8', '2' => 'c', '3' => 'e'];
            $maskBin .= $map[$remaining];
        }
        $maskBin = str_pad($maskBin, 32, '0');
        $netmask = pack("H*", $maskBin);

        return ($firstBin & $netmask) === ($secondBin & $netmask)
            && ($firstBin & $netmask) === ($thirdBin & $netmask);
    }

}
