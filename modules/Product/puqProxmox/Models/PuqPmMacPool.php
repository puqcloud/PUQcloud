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

class PuqPmMacPool extends Model
{
    protected $table = 'puq_pm_mac_pools';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'first_mac',
        'last_mac',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function puqPmPublicNetworks(): HasMany
    {
        return $this->hasMany(PuqPmPublicNetwork::class, 'puq_pm_mac_pool_uuid', 'uuid');
    }

    public function puqPmPrivateNetworks(): HasMany
    {
        return $this->hasMany(PuqPmPrivateNetwork::class, 'puq_pm_mac_pool_uuid', 'uuid');
    }

    public function getMacCount(): int
    {
        $macToInt = fn($mac) => hexdec(str_replace([':', '-'], '', strtolower($mac)));
        $firstInt = $macToInt($this->first_mac);
        $lastInt = $macToInt($this->last_mac);

        return max(0, $lastInt - $firstInt + 1);
    }



    public function puqPmLxcInstanceNets(): HasMany
    {
        return $this->hasMany(PuqPmLxcInstanceNet::class, 'puq_pm_mac_pool_uuid', 'uuid');
    }

    public function getUsedMacCount(): int
    {
        $puq_pm_lxc_instance_net_count = $this->puqPmLxcInstanceNets()->count();
        return $puq_pm_lxc_instance_net_count ;
    }

    public function hasAvailableMac(): bool
    {
        $total = $this->getMacCount();
        $used = $this->getUsedMacCount();

        return ($total - $used) > 0;
    }

    public function getMac(array $excludeMacs = []): ?string
    {
        $macToInt = fn($mac) => hexdec(str_replace([':', '-'], '', strtolower($mac)));
        $intToMac = fn($int) => strtoupper(implode(':', str_split(str_pad(dechex($int), 12, '0', STR_PAD_LEFT), 2)));

        $firstInt = $macToInt($this->first_mac);
        $lastInt = $macToInt($this->last_mac);

        $usedMacs = $this->puqPmLxcInstanceNets()->pluck('mac')
            ->map(fn($mac) => $macToInt($mac))
            ->toArray();

        $excludeInts = array_map($macToInt, $excludeMacs);

        $blockedMacs = array_flip(array_merge($usedMacs, $excludeInts));

        for ($macInt = $firstInt; $macInt <= $lastInt; $macInt++) {
            if (!isset($blockedMacs[$macInt])) {
                return $intToMac($macInt);
            }
        }

        return null;
    }


}
