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
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HomeCompany extends Model
{
    use ConvertsTimezone;
    use HasFiles;

    const IMAGES = [
        'logo' => ['label' => 'Logo', 'order' => 1],
    ];

    protected $table = 'home_companies';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static $retrievingFixDone = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::saving(function ($model) {
            if (empty($model->country) or empty($model->region)) {
                $country = Country::query()->where('code', 'CA')->first();
                $region = Region::query()->where('country_uuid', $country->uuid)->where('code', 'MB')->first();
                $model->country_uuid = $country->uuid;
                $model->region_uuid = $region->uuid;
            }
        });

        static::retrieved(function ($model) {
            $class = get_class($model);
            $key = $model->getKey();

            $alreadyFixed = self::$retrievingFixDone["$class:$key"] ?? false;
            if (! $alreadyFixed && (empty($model->country_uuid) || empty($model->region_uuid))) {
                $country = Country::where('code', 'CA')->first();
                $region = Region::where('country_uuid', $country->uuid)->where('code', 'MB')->first();

                $model->country_uuid = $country->uuid;
                $model->region_uuid = $region->uuid;

                self::$retrievingFixDone["$class:$key"] = true;
            }
        });

    }

    protected $fillable = [
        // Basic details
        'name',
        'company_name',
        'address_1',
        'address_2',
        'city',
        'state',
        'country',
        'postcode',
        'region_uuid',
        'country_uuid',

        // Universal tax IDs
        'tax_local_id',
        'tax_local_id_name',
        'tax_eu_vat_id',
        'tax_eu_vat_id_name',
        'registration_number',
        'registration_number_name',

        // US-specific
        'us_ein',
        'us_state_tax_id',
        'us_entity_type',

        // Canada-specific
        'ca_business_number',
        'ca_gst_hst_number',
        'ca_pst_qst_number',
        'ca_entity_type',

        // Tax rates
        'tax_1',
        'tax_1_name',
        'tax_1_region',
        'tax_2',
        'tax_2_name',
        'tax_2_region',
        'tax_3',
        'tax_3_name',
        'tax_3_region',

        // Invoicing
        'proforma_invoice_number_format',
        'proforma_invoice_number_next',
        'proforma_invoice_number_reset',

        'invoice_number_format',
        'invoice_number_next',
        'invoice_number_reset',

        'credit_note_number_format',
        'credit_note_number_next',
        'credit_note_number_reset',

        'balance_credit_purchase_item_name',
        'balance_credit_purchase_item_description',

        'refund_item_name',
        'refund_item_description',

        'pay_to_text',

        'invoice_footer_text',
        'invoice_template',
        'proforma_template',
        'credit_note_template',

        'signature',
        'group_uuid',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_uuid', 'uuid');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_uuid', 'uuid');
    }

    public function taxRules(): HasMany
    {
        return $this->hasMany(TaxRule::class, 'home_company_uuid', 'uuid');
    }

    public function paymentGateways(): HasMany
    {
        return $this->hasMany(PaymentGateway::class, 'home_company_uuid', 'uuid');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_uuid', 'uuid');
    }
}
