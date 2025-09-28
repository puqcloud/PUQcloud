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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use ConvertsTimezone;

    protected $table = 'transactions';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'client_uuid',
        'admin_uuid',
        'type',
        'amount_gross',
        'amount_net',
        'fees',
        'description',
        'relation_model',
        'relation_model_uuid',
        'transaction_id',
        'transaction_date',
        'period_start',
        'period_stop',
        'payment_gateway_uuid',
    ];

    protected $casts = [
        'amount_gross' => 'decimal:4',
        'amount_net' => 'decimal:4',
        'fees' => 'decimal:4',
        'transaction_date' => 'datetime',
        'period_start' => 'datetime',
        'period_stop' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();

            if (! $model->transaction_date) {
                $model->transaction_date = now();
            }

            $balance = ClientBalance::lockForUpdate()->find($model->client_uuid);
            if ($balance) {
                $model->balance_before = $balance->balance;
                $model->balance_after = $balance->balance + $model->amount_net;
            } else {
                $model->balance_before = 0.00;
                $model->balance_after = $model->amount_net;
            }

            $client = Client::with('currency')->find($model->client_uuid);
            $model->currency_code = optional($client->currency)->code ?? 'USD';

            if (! $model->fees) {
                $model->fees = 0.00;
            }
        });

        static::created(function ($model) {

            DB::transaction(function () use ($model) {
                $balance = ClientBalance::lockForUpdate()->find($model->client_uuid);

                if (! $balance) {
                    ClientBalance::withoutEvents(function () use ($model) {
                        ClientBalance::create([
                            'client_uuid' => $model->client_uuid,
                            'balance' => $model->amount_net,
                        ]);
                    });
                } else {
                    ClientBalance::withoutEvents(function () use ($balance, $model) {
                        $balance->balance += $model->amount_net;
                        $balance->save();
                    });
                }
            });

            if (! $model->fees) {
                $model->fees = 0.00;
            }
            logActivity(
                'info',
                'Transaction:'.$model->uuid.' created. Amount Net: '.$model->amount_net.' '.$model->currency->code.' Amount Gross: '.$model->amount_gross.' '.$model->currency->code.' Fees: '.$model->fees.' '.$model->currency->code,
                $model->type,
                null,
                null,
                null,
                $model->client_uuid
            );
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_uuid', 'uuid');
    }

    public function currency(): HasOneThrough
    {
        return $this->hasOneThrough(
            Currency::class,
            Client::class,
            'uuid',          // Foreign key on clients table...
            'uuid',          // Foreign key on currencies table...
            'client_uuid',   // Local key on transactions table...
            'currency_uuid'  // Local key on clients table...
        );
    }

    public function relatedModel(): MorphTo
    {
        return $this->morphTo(null, 'relation_model', 'relation_model_uuid');
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_uuid', 'uuid');
    }
}
