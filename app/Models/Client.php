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

use App\Services\SettingService;
use App\Services\UserPermissionService;
use App\Traits\ConvertsTimezone;
use App\Traits\ModelActivityLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Client extends Model
{
    use ConvertsTimezone;
    use HasFactory;
    use ModelActivityLogger;

    protected $table = 'clients';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });

        static::retrieved(function ($client) {
            if (! ClientBalance::where('client_uuid', $client->uuid)->exists()) {
                ClientBalance::createSafe([
                    'client_uuid' => $client->uuid,
                    'balance' => 0,
                ]);
            }
        });

        static::saving(function ($model) {

            $billingAddress = $model->billingAddress();
            if (! empty($billingAddress)) {
                $countryCode = $billingAddress->country->code;
                $euCountries = config('taxes.EU');

                if (array_key_exists($countryCode, $euCountries)) {
                    $data = ['client_uuid' => $model->uuid];
                    $tags = ['ViesVatNumberValidation'];
                    Task::add('ViesVatNumberValidation', 'Client', $data, $tags);
                }
            }
        });

    }

    protected $fillable = [
        'firstname',
        'lastname',
        'company_name',
        'tax_id',
        'status',
        'language',
        'currency_uuid',
        'notes',
        'admin_notes',
    ];

    public function viesValidation(): HasOne
    {
        return $this->hasOne(ViesValidation::class, 'client_uuid', 'uuid');
    }

    public function clientSessionLog(): HasMany
    {
        return $this->hasMany(ClientSessionLog::class, 'client_uuid', 'uuid');
    }

    public function ips(): HasMany
    {
        return $this->hasMany(ClientIP::class, 'client_uuid', 'uuid');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_uuid', 'uuid');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'client_x_user', 'client_uuid', 'user_uuid')
            ->withPivot('owner', 'permissions')
            ->withTimestamps();
    }

    public function owner()
    {
        return $this->users()->wherePivot('owner', true)->first();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(ClientAddress::class, 'client_uuid', 'uuid');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'client_uuid', 'uuid');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_uuid', 'uuid');
    }

    public function billingAddress(): ?ClientAddress
    {
        return $this->addresses()->where('type', 'billing')->first();
    }

    public function updateOwner(string $newOwnerUuid): void
    {

        $permissions = UserPermissionService::allKey();

        $currentOwner = $this->users()->wherePivot('owner', true)->first();

        if ($currentOwner) {
            $this->users()->updateExistingPivot($currentOwner->uuid, [
                'permissions' => json_encode($permissions),
            ]);
        }

        $this->users()->update(['owner' => false]);

        $this->users()->updateExistingPivot($newOwnerUuid, [
            'owner' => true,
            'permissions' => json_encode($permissions),
        ]);
    }

    public function language(): array
    {
        $default = config('locale.client.default');
        $defaultLanguage = [];
        $language = [];
        foreach (config('locale.client.locales') as $key => $value) {
            if ($key == $this->language) {
                $language = $value;
            }

            if ($key == $default) {
                $defaultLanguage = $value;
            }
        }

        if (empty($language)) {
            return $defaultLanguage;
        }

        return $language;
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'client_uuid', 'uuid');
    }

    public function balance(): HasOne
    {
        return $this->hasOne(ClientBalance::class, 'client_uuid', 'uuid');
    }

    public function ViesVatNumberValidation(): void
    {
        logActivity('info', 'Start VIES VAT validation', 'vies_validation', null, null, null, $this->uuid);

        $vies_validation = $this->viesValidation;
        if (! $vies_validation) {
            $vies_validation = new ViesValidation;
            $vies_validation->client_uuid = $this->uuid;
        }
        $vies_validation->request_date = now();

        $billingAddress = $this->billingAddress();

        if (! $billingAddress) {
            $vies_validation->error = 'Billing address not found';
            $vies_validation->save();
            logActivity('error', 'Billing address not found during VIES validation', 'vies_validation', null, null, null, $this->uuid);

            return;
        }

        $countryCode = $billingAddress->country->code;
        $euCountries = config('taxes.EU');

        if (! array_key_exists($countryCode, $euCountries)) {
            $vies_validation->error = 'Not applicable';
            $vies_validation->save();
            logActivity('error', 'Client not from EU country during VIES validation', 'vies_validation', null, null, null, $this->uuid);

            return;
        }

        $vatNo = $this->tax_id;

        try {
            $response = Http::timeout(30)
                ->get("https://ec.europa.eu/taxation_customs/vies/rest-api/ms/{$countryCode}/vat/{$vatNo}");

            $vies_validation->raw = $response->body();
            if ($response->successful()) {
                $resultArray = $response->json();

                if ($resultArray['isValid']) {
                    $vies_validation->valid = $resultArray['isValid'];
                }

                if ($resultArray['name'] && $resultArray['name'] !== '---') {
                    $vies_validation->name = $resultArray['name'];
                }

                if ($resultArray['address'] && $resultArray['address'] !== '---') {
                    $vies_validation->address = $resultArray['address'];
                }

                if ($resultArray['userError'] && $resultArray['userError'] !== 'VALID' && $resultArray['userError'] !== 'INVALID') {
                    $vies_validation->error = $resultArray['userError'];
                }

                logActivity('info', 'VIES validation successful', 'vies_validation', null, null, null, $this->uuid);
                $vies_validation->error = null;
                $vies_validation->save();
            } else {
                $vies_validation->error = 'Request failed or timed out';
                logActivity('error', 'VIES request failed or timed out', 'vies_validation', null, null, null, $this->uuid);
                $vies_validation->save();
            }
        } catch (\Exception $e) {
            $vies_validation->error = $e->getMessage();
            logActivity('error', 'Exception during VIES validation: '.$e->getMessage(), 'vies_validation', null, null, null, $this->uuid);
            $vies_validation->save();
        }

        $vies_validation->save();
    }

    public function getTaxRule(): ?TaxRule
    {
        $private_client = empty($this->tax_id) && empty($this->company_name);
        $company_without_tax_id = empty($this->tax_id) && ! empty($this->company_name);
        $company_with_tax_id = ! empty($this->tax_id);

        $billingAddress = $this->billingAddress();
        $country_uuid = $billingAddress->country->uuid ?? null;
        $region_uuid = $billingAddress->region->uuid ?? null;

        $tax_rule = TaxRule::where(function ($query) use ($private_client, $company_without_tax_id, $company_with_tax_id) {
            if ($private_client) {
                $query->where('private_client', true);
            }
            if ($company_without_tax_id) {
                $query->where('company_without_tax_id', true);
            }
            if ($company_with_tax_id) {
                $query->where('company_with_tax_id', true);
            }
        })
            ->where(function ($query) use ($country_uuid) {
                $query->whereNull('country_uuid')
                    ->orWhere('country_uuid', $country_uuid);
            })
            ->where(function ($query) use ($region_uuid) {
                $query->whereNull('region_uuid')
                    ->orWhere('region_uuid', $region_uuid);
            })
            ->orderByRaw('CASE WHEN country_uuid IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN region_uuid IS NULL THEN 1 ELSE 0 END')
            ->first();

        return $tax_rule;
    }

    public function getTaxes(): array
    {
        $tax_rule = $this->getTaxRule();
        $billingAddress = $this->billingAddress();
        $country_uuid = $billingAddress->country->uuid ?? null;

        if (! $tax_rule) {
            $home_company = HomeCompany::where('default', true)->first();
        } else {
            $home_company = $tax_rule->homeCompany;
        }

        if ($this->viesValidation && $this->viesValidation->valid) {
            $home_company_country_code = $home_company->country->code ?? null;
            $euCountries = config('taxes.EU');
            if (array_key_exists($home_company_country_code, $euCountries)) {
                if ($home_company->country_uuid != $country_uuid) {
                    return [];
                }
            }
        }

        if ($tax_rule) {
            $taxes = [];
            foreach (['tax_1', 'tax_2', 'tax_3'] as $tax) {
                if (! empty($tax_rule->{$tax})) {
                    $taxes[] = ['name' => $tax_rule->{$tax.'_name'}, 'rate' => $tax_rule->{$tax}];
                }
            }

            return $taxes;
        }

        $taxes = [];
        foreach (['tax_1', 'tax_2', 'tax_3'] as $tax) {
            if (! empty($home_company->{$tax})) {
                $taxes[] = ['name' => $home_company->{$tax.'_name'}, 'rate' => $home_company->{$tax}];
            }
        }

        return $taxes;
    }

    public function getHomeCompany(): HomeCompany
    {
        $tax_rule = $this->getTaxRule();

        if (! $tax_rule) {
            $home_company = HomeCompany::where('default', true)->first();
        } else {
            $home_company = $tax_rule->homeCompany;
        }

        return $home_company;
    }

    public function createInvoiceProformaAddFunds($amount): string
    {
        $invoiceUuid = DB::transaction(function () use ($amount) {
            $taxes = $this->getTaxes();
            $home_company = $this->getHomeCompany();

            $proforma_invoice = new Invoice;
            $proforma_invoice->type = 'proforma';
            $proforma_invoice->status = 'draft';
            $proforma_invoice->client_uuid = $this->uuid;
            $proforma_invoice->home_company_uuid = $home_company->uuid;

            $index = 1;
            foreach ($taxes as $tax) {
                if ($index > 3) {
                    continue;
                }
                $proforma_invoice->{"tax_$index"} = $tax['rate'];
                $proforma_invoice->{"tax_{$index}_name"} = $tax['name'];
                $index++;
            }

            $proforma_invoice->save();
            $proforma_invoice->refresh();

            $invoice_item = new InvoiceItem;
            $invoice_item->invoice_uuid = $proforma_invoice->uuid;
            $invoice_item->description = $home_company->balance_credit_purchase_item_name;

            if ($home_company->balance_credit_purchase_item_description) {
                $invoice_item->description .= PHP_EOL.'*-'.$home_company->balance_credit_purchase_item_description;
            }

            $invoice_item->description = str_replace('{YEAR}', date('Y'), $invoice_item->description);
            $invoice_item->description = str_replace('{MONTH}', date('m'), $invoice_item->description);
            $invoice_item->description = str_replace('{DAY}', date('d'), $invoice_item->description);

            $invoice_item->taxed = true;
            $invoice_item->amount = $amount;
            $invoice_item->save();

            return $proforma_invoice->uuid;
        });

        $invoice = Invoice::findOrFail($invoiceUuid);
        $invoice->publish();

        return $invoiceUuid;
    }

    public function getNotificationRule($category, $notification): ?NotificationRule
    {
        $home_company = $this->getHomeCompany();
        if (! $home_company) {
            return null;
        }
        $group = $home_company->group;

        return $group->notificationRules()->where('notification', $notification)->where('category', $category)->first();
    }

    public function getAddFundsParams(): array
    {
        $currency = $this->currency;
        $taxes = $this->getTaxes();

        $min_add_funds_amount = SettingService::get('finance.min_add_funds_amount') * $currency->exchange_rate;
        $max_add_funds_amount = SettingService::get('finance.max_add_funds_amount') * $currency->exchange_rate;
        $max_client_balance = SettingService::get('finance.max_client_balance') * $currency->exchange_rate;
        $recommended_add_funds_amount = $this->calculateRecommendedAddFundsAmount();

        $balance = $this->balance->balance;
        if ($balance < 0) {
            $recommended_add_funds_amount += abs($balance);
        }

        $recommended_add_funds_amount = max($recommended_add_funds_amount, $min_add_funds_amount);
        $recommended_add_funds_amount = min($recommended_add_funds_amount, $max_add_funds_amount);

        return [
            'balance' => round($balance, 2,PHP_ROUND_HALF_UP),
            'min_add_funds_amount' => round($min_add_funds_amount, 2,PHP_ROUND_HALF_UP),
            'max_add_funds_amount' => round($max_add_funds_amount, 2,PHP_ROUND_HALF_UP),
            'max_client_balance' => round($max_client_balance, 2,PHP_ROUND_HALF_UP),
            'recommended_add_funds_amount' => ceil($recommended_add_funds_amount * 100) / 100,
            'taxes' => $taxes,
            'currency' => [
                'code' => $currency->code,
                'prefix' => $currency->prefix,
                'suffix' => $currency->suffix,
            ],
        ];
    }

    protected function calculateRecommendedAddFundsAmount(): float
    {
        $services = $this->services()
            ->whereIn('status', ['active', 'suspended', 'pending'])
            ->where('termination_request', false)
            ->get();

        $now = now();
        $threshold = $now->copy()->addDays(30);
        $total = 0;

        foreach ($services as $service) {
            $billingTime = $service->billing_timestamp
                ? \Illuminate\Support\Carbon::parse($service->billing_timestamp)
                : null;

            if ($billingTime && $billingTime->gte($threshold)) {
                continue;
            }

            $priceDetails = $service->getPriceDetailed();
            $servicePrices = $priceDetails['service'];
            $options = $priceDetails['options'] ?? [];

            $sum = 0;

            $sum += (float) $servicePrices['base'];

            if ($service->status === 'pending') {
                $sum += (float) $servicePrices['setup'];
            }

            foreach ($options as $option) {
                $optionPrice = $option['price'];
                $sum += (float) $optionPrice['base'];

                if ($service->status === 'pending') {
                    $sum += (float) $optionPrice['setup'];
                }
            }

            $total += $sum;
        }

        return $total;
    }

    public function calculateRecurringPaymentsBreakdown(): array
    {
        $services = $this->services()
            ->whereIn('status', ['active', 'suspended'])
            ->where('termination_request', false)
            ->get();

        $periodHours = [
            'hourly' => 1,
            'daily' => 24,
            'weekly' => 168,
            'bi-weekly' => 336,
            'monthly' => 720,
            'quarterly' => 2160,
            'semi-annually' => 4320,
            'annually' => 8760,
            'biennially' => 17520,
            'triennially' => 26280,
        ];

        $totalHourly = 0;
        $currency = [];
        foreach ($services as $service) {
            $priceDetails = $service->getPriceDetailed();
            $currency = $priceDetails['currency'];
            $base = (float) $priceDetails['total']['base'];
            $cycleHours = $periodHours[$priceDetails['period']];
            $totalHourly += $base / $cycleHours;
        }

        $totals = [
            'hourly' => round($totalHourly, 4,PHP_ROUND_HALF_UP),
            'daily' => round($totalHourly * 24, 2,PHP_ROUND_HALF_UP),
            'weekly' => round($totalHourly * 168, 2,PHP_ROUND_HALF_UP),
            'monthly' => round($totalHourly * 720, 2,PHP_ROUND_HALF_UP),
            'yearly' => round($totalHourly * 8760, 2,PHP_ROUND_HALF_UP),
            'recommended_funds' => round($this->calculateRecommendedAddFundsAmount(), 2,PHP_ROUND_HALF_UP),
            'currency' => $currency,
        ];

        return $totals;
    }
}
