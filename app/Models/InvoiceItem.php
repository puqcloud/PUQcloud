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
use Illuminate\Support\Str;

class InvoiceItem extends Model
{
    use ConvertsTimezone;

    protected $table = 'invoice_items';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::saved(function ($item) {
            $item->invoice->load('invoiceItems');
            $item->invoice->calculateTotals();
            $item->invoice->save();
        });

        static::deleting(function ($item) {
            $invoice = $item->invoice;
            $items = $invoice->invoiceItems->reject(fn ($i) => $i->uuid === $item->uuid);
            $invoice->setRelation('invoiceItems', $items);
            $invoice->calculateTotals();
            $invoice->save();
        });
    }

    protected $fillable = [
        'invoice_uuid',
        'description',
        'taxed',
        'relation_model',
        'relation_model_uuid',
        'service_uuid',
        'period_start',
        'period_stop',
        'amount',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'datetime:Y-m-d H:i',
        'period_stop' => 'datetime:Y-m-d H:i',
        'metadata' => 'array',
    ];

    public function invoice(): belongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_uuid', 'uuid');
    }
}
