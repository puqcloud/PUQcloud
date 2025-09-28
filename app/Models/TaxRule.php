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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaxRule extends Model
{
    use ConvertsTimezone;

    protected $table = 'tax_rules';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

    }

    protected $fillable = [
        'order',
        'country_uuid',
        'region_uuid',
        'private_client',
        'company_without_tax_id',
        'company_with_tax_id',
        'individual_tax_rate',
        'tax_1',
        'tax_1_name',
        'tax_2',
        'tax_2_name',
        'tax_3',
        'tax_3_name',
        'home_company_uuid',
    ];

    public static function reorder(): void
    {
        $tax_rules = self::orderBy('order')->get();
        $order = 1;

        foreach ($tax_rules as $tax_rule) {
            DB::table('tax_rules')
                ->where('uuid', $tax_rule->uuid)
                ->update(['order' => $order]);
            $order++;
        }
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_uuid', 'uuid');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_uuid', 'uuid');
    }

    public function homeCompany(): BelongsTo
    {
        return $this->belongsTo(HomeCompany::class, 'home_company_uuid', 'uuid');
    }
}
