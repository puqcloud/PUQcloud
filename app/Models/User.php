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

use App\Traits\ConvertsTimezone;
use App\Traits\ModelActivityLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Str;

class User extends Model implements \Illuminate\Contracts\Auth\Authenticatable, AuthorizableContract
{
    use Authorizable;
    use ConvertsTimezone;
    use HasFactory;
    use \Illuminate\Auth\Authenticatable;
    use ModelActivityLogger;

    protected string $guard_name = 'client';

    protected $table = 'users';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::saved(function ($model) {});

        static::retrieved(function ($model) {

            if (! $model->verifications()->where('type', 'email')->where('value', $model->email)->exists()) {
                Verification::create([
                    'user_uuid' => $model->uuid,
                    'type' => 'email',
                    'value' => $model->email,
                    'secret' => rand(100000, 999999),
                    'device_name' => 'system',
                    'expires_at' => now(),
                ]);
            }

            $hasDefault = $model->verifications()->where('default', true)->exists();
            if (! $hasDefault) {
                $verification = $model->verifications()->where('type', 'email')->where('value', $model->email)->first();
                $verification->default = true;
                $verification->save();
            }

            $verification = $model->verifications()->where('type', 'email')->where('value', $model->email)->first();
            $model->email_verified = $verification->verified;
            $model->save();
        });
    }

    protected $fillable = [
        'email',
        'phone_number',
        'password',
        'status',
        'two_factor',
        'disable',
        'firstname',
        'lastname',
        'email_verified',
        'language',
        'notes',
        'admin_notes',
        'password_reset_token',
        'password_reset_token_created_at',
    ];

    protected $casts = [
        'disable' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_x_user', 'user_uuid', 'client_uuid')
            ->withPivot('owner', 'permissions')
            ->withTimestamps();
    }

    public function ownedClients(): BelongsToMany
    {
        return $this->clients()->wherePivot('owner', true);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class, 'user_uuid', 'uuid');
    }

    public function clientSessionLog(): HasMany
    {
        return $this->hasMany(ClientSessionLog::class, 'user_uuid', 'uuid');
    }

    public function ips(): HasMany
    {
        return $this->hasMany(ClientIP::class, 'user_uuid', 'uuid');
    }

    public function updateIpAddress($ip): void
    {
        $client = app('client');
        $latestIp = $this->ips()->orderBy('created_at', 'desc')->first();

        $now = Carbon::now();

        if ($latestIp && $latestIp->ip_address === $ip) {
            $latestIp->update([
                'stop_use' => $now,
            ]);
        } else {
            $this->ips()->create([
                'client_uuid' => $client->uuid,
                'ip_address' => $ip,
                'start_use' => $now,
                'stop_use' => $now,
            ]);
        }
    }

    public function createFirstClient(): void
    {
        if ($this->clients()->count() != 0) {
            return;
        }

        $home_company = HomeCompany::query()->where('default', true)->first();
        $default_currency = Currency::query()->where('default', true)->first();
        $client = $this->clients()->create([
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'currency_uuid' => $default_currency->uuid,
            'language' => $this->language,
            'status' => 'new',
        ]);

        $client->refresh();

        if (! $client->billingAddress()) {
            $client->addresses()->create([
                'name' => 'Default',
                'type' => 'billing',
                'contact_name' => $this->firstname.' '.$this->lastname,
                'contact_email' => $this->email,
                'address_1' => '',
                'city' => $home_company->city ?? '',
                'postcode' => $home_company->postcode ?? '',
                'region_uuid' => $home_company->region_uuid ?? '',
                'country_uuid' => $home_company->country_uuid ?? '',
            ]);
        }

        $client->updateOwner($this->uuid);

    }

    public function getHomeCompany(): HomeCompany
    {
        $client = $this->ownedClients()->first();
        if (! $client) {
            $client = $this->clients()->first();
        }
        if (! $client) {
            $home_company = HomeCompany::query()->where('default', true)->first();
        } else {
            $home_company = $client->getHomeCompany();
        }

        return $home_company;
    }

    public function generatePasswordResetToken(): string
    {
        $this->password_reset_token = Str::random(64);
        $this->password_reset_token_created_at = now();
        $this->save();

        return $this->password_reset_token;
    }
}
