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

namespace Modules\Product\puqVPNCP\Models;

use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class PuqVpncpServer extends Model
{
    protected $table = 'puq_vpncp_servers';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'api_url',
        'api_token',
        'verify_ssl',
        'description',
    ];

    protected $casts = [
        'description' => 'array',
        'verify_ssl' => 'boolean',
    ];

    protected $attributes = [
        'verify_ssl' => true,
    ];

    /**
     * Per-process cache of API clients keyed by server UUID.
     * Shared across Eloquent instances so queue jobs reuse a single client per server
     * instead of re-decrypting the token on every API call.
     *
     * @var array<string, \puqVpncpClient>
     */
    protected static array $apiClientCache = [];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        // Invalidate cached API client if credentials or SSL setting changed.
        static::saved(function (self $model) {
            if (
                $model->isDirty('api_url')
                || $model->isDirty('api_token')
                || $model->isDirty('verify_ssl')
            ) {
                unset(self::$apiClientCache[$model->uuid]);
            }
        });

        static::deleted(function (self $model) {
            unset(self::$apiClientCache[$model->uuid]);
        });
    }

    public function serverGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            PuqVpncpServerGroup::class,
            'puq_vpncp_server_group_server',
            'server_uuid',
            'server_group_uuid',
            'uuid',
            'uuid'
        )->withPivot('selected_networks')->withTimestamps();
    }

    public function getUseAccounts(): int
    {
        return Service::query()
            ->whereIn('status', ['active', 'suspended'])
            ->whereJsonContains('provision_data->server_uuid', $this->uuid)
            ->count();
    }

    /**
     * Active/existing accounts on the server (from /system/status count_accounts).
     */
    public function getActiveClientsCount(): int
    {
        return (int) ($this->description['system']['count_accounts'] ?? 0);
    }

    /**
     * Total capacity — max accounts allowed (from /system/status count_accounts_available).
     */
    public function getTotalAccounts(): int
    {
        return (int) ($this->description['system']['count_accounts_available'] ?? 0);
    }

    /**
     * Free slots = total capacity - active accounts.
     */
    public function getFreeSlots(): int
    {
        return max(0, $this->getTotalAccounts() - $this->getActiveClientsCount());
    }

    /**
     * License max accounts (from /license accounts) — informational only.
     */
    public function getLicenseAccounts(): int
    {
        return (int) ($this->description['license']['accounts'] ?? 0);
    }

    public function isAssignedToGroups(): bool
    {
        return $this->serverGroups()->exists();
    }

    public function hasClients(): bool
    {
        return $this->getUseAccounts() > 0;
    }

    public function canDelete(): array
    {
        $errors = [];

        if ($this->isAssignedToGroups()) {
            $groups = $this->serverGroups()->pluck('name')->toArray();
            $errors[] = __('Product.puqVPNCP.Server is assigned to groups:').' '.implode(', ', $groups);
        }

        if ($this->hasClients()) {
            $errors[] = __('Product.puqVPNCP.Server has active clients');
        }

        return $errors;
    }

    public function getApiClient(): \puqVpncpClient
    {
        return self::$apiClientCache[$this->uuid] ??= new \puqVpncpClient([
            'api_url' => $this->api_url,
            'api_token' => $this->api_token,
            'verify_ssl' => $this->verify_ssl ?? true,
        ]);
    }
}
