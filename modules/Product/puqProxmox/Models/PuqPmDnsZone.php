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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function puqPmDnsServers(): BelongsToMany
    {
        return $this->belongsToMany(PuqPmDnsServer::class,
            'puq_pm_dns_server_x_dns_zone',
            'puq_pm_dns_zone_uuid',
            'puq_pm_dns_server_uuid'
        );
    }

    public function getRecordCount(): int
    {
        return 0;
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
            $hostname = $this->normalizeHostname($result . '-' . $counter);
            $counter++;
        }

        return $hostname;
    }

}
