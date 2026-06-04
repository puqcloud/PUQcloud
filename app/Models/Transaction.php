<?php

/*
 * PUQcloud - Free Cloud Billing System
 * Main billing system core logic
 *
 * Copyright (C) 2025-2026 PUQ Software
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

            if (! $model->fees) {
                $model->fees = 0.00;
            }

            // Set currency_code with a single query instead of loading two Eloquent models
            $model->currency_code = DB::table('clients')
                ->join('currencies', 'clients.currency_uuid', '=', 'currencies.uuid')
                ->where('clients.uuid', $model->client_uuid)
                ->value('currencies.code') ?? 'USD';

            // Set placeholder values — will be recalculated under lock in `created`
            $model->balance_before = 0.00;
            $model->balance_after = $model->amount_net;
        });

        static::created(function ($model) {

            DB::transaction(function () use ($model) {
                // Check billing_purpose inside transaction to prevent race conditions
                $skipBalance = false;
                if ($model->relation_model === 'App\\Models\\Invoice' && $model->relation_model_uuid) {
                    $billingPurpose = DB::table('invoices')
                        ->where('uuid', $model->relation_model_uuid)
                        ->value('billing_purpose');

                    if (in_array($billingPurpose, ['service_billing', 'standalone'])) {
                        $skipBalance = true;
                    }
                }

                $balance = ClientBalance::lockForUpdate()->find($model->client_uuid);

                if (! $balance) {
                    if (! $skipBalance) {
                        ClientBalance::withoutEvents(function () use ($model) {
                            ClientBalance::create([
                                'client_uuid' => $model->client_uuid,
                                'balance' => $model->amount_net,
                            ]);
                        });
                    } else {
                        // Ensure ClientBalance record exists even if we skip balance update
                        ClientBalance::withoutEvents(function () use ($model) {
                            ClientBalance::create([
                                'client_uuid' => $model->client_uuid,
                                'balance' => 0,
                            ]);
                        });
                    }

                    $currentBalance = $skipBalance ? 0 : $model->amount_net;
                    DB::table('transactions')->where('uuid', $model->uuid)->update([
                        'balance_before' => 0.00,
                        'balance_after' => $currentBalance,
                    ]);

                    logActivity(
                        'info',
                        'Client:'.$model->client_uuid.' balance initialized. Balance: '.$currentBalance.' '.$model->currency_code.($skipBalance ? ' (service billing - balance unchanged)' : ''),
                        'balance_update',
                        null,
                        null,
                        null,
                        $model->client_uuid
                    );
                } else {
                    $balanceBefore = $balance->balance;

                    if (! $skipBalance) {
                        $balanceAfter = $balance->balance + $model->amount_net;
                        ClientBalance::withoutEvents(function () use ($balance, $model) {
                            $balance->balance += $model->amount_net;
                            $balance->save();
                        });
                    } else {
                        $balanceAfter = $balance->balance;
                    }

                    DB::table('transactions')->where('uuid', $model->uuid)->update([
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                    ]);

                    logActivity(
                        'info',
                        'Client:'.$model->client_uuid.' balance updated. Before: '.$balanceBefore.' After: '.$balanceAfter.' '.$model->currency_code.' (Transaction: '.$model->uuid.')'.($skipBalance ? ' (service billing - balance unchanged)' : ''),
                        'balance_update',
                        null,
                        null,
                        null,
                        $model->client_uuid
                    );
                }
            });

            logActivity(
                'info',
                'Transaction:'.$model->uuid.' created. Amount Net: '.$model->amount_net.' '.$model->currency_code.' Amount Gross: '.$model->amount_gross.' '.$model->currency_code.' Fees: '.$model->fees.' '.$model->currency_code,
                $model->type,
                null,
                null,
                null,
                $model->client_uuid
            );

            app(\App\Services\HookService::class)->callHooks('TransactionCreated', ['transaction' => $model]);
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
