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

        static::created(function ($model) {
            app(\App\Services\HookService::class)->callHooks('ClientCreated', ['client' => $model]);
        });

        static::retrieved(function ($client) {
            if (!ClientBalance::where('client_uuid', $client->uuid)->exists()) {
                ClientBalance::createSafe([
                    'client_uuid' => $client->uuid,
                    'balance' => 0,
                ]);
            }
        });

        static::saving(function ($model) {

            $billingAddress = $model->billingAddress();
            if (!empty($billingAddress)) {
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

    /**
     * Check whether the client has enough available funds (balance + credit limit) to cover a given amount.
     *
     * This centralises the single correct formula so no controller re-implements it incorrectly:
     * `$client->balance` is a HasOne relation to `ClientBalance`; the numeric amount lives on the row
     * itself as `$client->balance->balance`. Accidentally doing `$client->balance + $client->credit_limit`
     * (treating the relation as a number) causes a TypeError in PHP 8+.
     *
     * @param float $amount Amount the client must be able to cover.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        $balanceAmount = (float) ($this->balance->balance ?? 0);
        $creditLimit = (float) ($this->credit_limit ?? 0);

        return ($balanceAmount + $creditLimit) >= $amount;
    }

    /**
     * Total funds currently available to the client for spending (balance + credit limit).
     * Intended for read-only display purposes; use hasSufficientBalance() for gating decisions.
     */
    public function getAvailableFunds(): float
    {
        $balanceAmount = (float) ($this->balance->balance ?? 0);
        $creditLimit = (float) ($this->credit_limit ?? 0);

        return $balanceAmount + $creditLimit;
    }

    /**
     * Calculate a complete order summary for a pre-order (no Service exists yet).
     *
     * Centralises the math that every module's `controller_CalculateSummary` used to duplicate:
     * option iteration, setup fee aggregation (product + all options), tax application for
     * invoice mode, and formatting. Modules just call this then (optionally) add their own
     * extra fields — e.g. puqVPNCP adds `product_group` name.
     *
     * Returns a flat array shaped to drop straight into a JSON response:
     *   [
     *     'product_name'     => string,
     *     'price'            => string,  // formatted base price of the product
     *     'period'           => string,  // localised label e.g. 'Monthly'
     *     'billing_mode'     => string,  // echoed back
     *     'options'          => [[{'uuid','group_label','label','base','base_str','setup','setup_str','price'}], ...],
     *     'setup_fee'        => string,  // formatted TOTAL setup (product + sum of option setups)
     *     'setup_fee_numeric'=> float,
     *     'subtotal'         => float,   // base product + sum of option bases (NET)
     *     'subtotal_str'     => string,
     *     'taxes'            => [[{'name','rate','amount','amount_str'}], ...]  (empty for prepaid)
     *     'tax_total'        => float,
     *     'tax_total_str'    => string,
     *     'total'            => float,   // subtotal + tax for invoice; = subtotal for prepaid
     *     'total_str'        => string,
     *   ]
     *
     * Sort order of options follows the product's `productOptionGroups` pivot order — same
     * visual order the admin set up in the group edit screen.
     *
     * Known quirks of the legacy code that are FIXED here:
     * - Old code used `$option_price->setup_fee` which was a non-existent column name
     *   ("setup_fee" vs real "setup") — setup fees from options were silently lost.
     *   This helper uses the correct `->setup` field.
     *
     * @param  Product  $product       Selected product.
     * @param  Price    $price         Selected price row (determines currency + period).
     * @param  array    $optionUuids   Array of selected ProductOption UUIDs to include.
     * @param  string   $billingMode   'prepaid' or 'invoice' — drives whether taxes are applied.
     */
    public function calculateOrderSummary(
        Product $product,
        Price $price,
        array $optionUuids = [],
        string $billingMode = 'prepaid'
    ): array {
        $currency = $price->currency;

        // Resolve display locale: prefer the authenticated user, fall back to the client row.
        $locale = null;
        try {
            $user = app('user');
            $locale = $user?->language ?? null;
        } catch (\Throwable $e) {
            // Not bound — continue with client locale.
        }
        if (! $locale) {
            $locale = $this->language ?? null;
        }
        if ($locale) {
            $product->setLocale($locale);
        }

        // Currency formatter closure — local so every string uses the same prefix/suffix layout.
        $prefix = $currency->prefix ?? '';
        $suffix = $currency->suffix ?? '';
        $fmt = static function ($amount) use ($prefix, $suffix) {
            return $prefix.' '.number_format((float) $amount, 2).' '.$suffix;
        };

        // Sort options by the product's option-group pivot order so the summary matches
        // the order of groups in the admin edit screen.
        $groupOrder = $product->productOptionGroups()->orderBy('order')->pluck('uuid')->toArray();
        $options = ProductOption::whereIn('uuid', $optionUuids)
            ->get()
            ->sortBy(function ($o) use ($groupOrder) {
                return array_search($o->product_option_group_uuid, $groupOrder);
            })
            ->values();

        $subtotal = (float) $price->base;
        $setupTotal = (float) $price->setup;
        $optionsOutput = [];

        foreach ($options as $option) {
            if ($locale) {
                $option->setLocale($locale);
            }

            // Match the option's price row by the SAME period + currency as the product price.
            $optionPrice = $option->prices()
                ->where('period', $price->period)
                ->where('currency_uuid', $price->currency_uuid)
                ->first();

            $base = 0.0;
            $setup = 0.0;
            if ($optionPrice) {
                $base = (float) ($optionPrice->base ?? 0);
                // Real column is `setup`, not `setup_fee` — old bug silently dropped all option setups.
                $setup = (float) ($optionPrice->setup ?? 0);
                $subtotal += $base;
                $setupTotal += $setup;
            }

            $optionsOutput[] = [
                'uuid' => $option->uuid,
                'group_label' => $option->productOptionGroup?->name,
                'label' => $option->name,
                'base' => $base,
                'base_str' => $fmt($base),
                'setup' => $setup,
                'setup_str' => $fmt($setup),
                // `price` kept as an alias for legacy blade code that reads `summary.options[i].price`.
                'price' => $fmt($base),
            ];
        }

        // Taxes apply only to invoice-mode services — prepaid amounts are already net
        // (tax was taken at balance top-up) so taxing them again would double-charge.
        $taxLines = [];
        $taxTotal = 0.0;
        $taxTotalStr = $fmt(0);
        $finalTotal = round($subtotal, 2);

        if ($billingMode === 'invoice') {
            $tax = $this->calculateTaxLines($subtotal, $currency);
            $taxLines = $tax['lines'];
            $taxTotal = $tax['tax_total'];
            $taxTotalStr = $tax['tax_total_str'];
            $finalTotal = $tax['gross_total'];
        }

        return [
            'product_name' => $product->name,
            'price' => $fmt($price->base),
            'period' => __('main.'.$price->period),
            'billing_mode' => $billingMode,
            'options' => $optionsOutput,
            'setup_fee' => $fmt($setupTotal),
            'setup_fee_numeric' => round($setupTotal, 2),
            'subtotal' => round($subtotal, 2),
            'subtotal_str' => $fmt($subtotal),
            'taxes' => $taxLines,
            'tax_total' => $taxTotal,
            'tax_total_str' => $taxTotalStr,
            'total' => $finalTotal,
            'total_str' => $fmt($finalTotal),
        ];
    }

    /**
     * Build ready-to-render tax lines for an arbitrary subtotal using this client's tax rules.
     *
     * Generic variant usable from any context — including the order flow where no Service exists
     * yet. Service::calculateTaxLines() delegates to this so there is exactly one implementation.
     *
     * Returns:
     *   [
     *     'lines'           => [['name', 'rate', 'amount', 'amount_str'], ...],
     *     'tax_total'       => float,
     *     'tax_total_str'   => string (formatted in the given currency),
     *     'gross_total'     => float,           // subtotal + tax_total
     *     'gross_total_str' => string,
     *   ]
     *
     * @param  float           $subtotal   Net amount the tax is calculated on.
     * @param  Currency|null   $currency   Currency used to format the *_str fields.
     *                                     Defaults to the client's own currency.
     */
    public function calculateTaxLines(float $subtotal, ?Currency $currency = null): array
    {
        $currency ??= $this->currency;
        $taxes = $this->getTaxes();

        $taxTotal = 0.0;
        $lines = [];

        foreach ($taxes as $tax) {
            $taxAmount = round($subtotal * ((float) $tax['rate'] / 100), 2);
            $taxTotal += $taxAmount;
            $lines[] = [
                'name' => $tax['name'],
                'rate' => (float) $tax['rate'],
                'amount' => $taxAmount,
                'amount_str' => formatCurrency($taxAmount, $currency),
            ];
        }

        $grossTotal = round($subtotal + $taxTotal, 2);

        return [
            'lines' => $lines,
            'tax_total' => $taxTotal,
            'tax_total_str' => formatCurrency($taxTotal, $currency),
            'gross_total' => $grossTotal,
            'gross_total_str' => formatCurrency($grossTotal, $currency),
        ];
    }

    public function ViesVatNumberValidation(): void
    {
        logActivity('info', 'Start VIES VAT validation', 'vies_validation', null, null, null, $this->uuid);

        $vies_validation = $this->viesValidation;
        if (!$vies_validation) {
            $vies_validation = new ViesValidation;
            $vies_validation->client_uuid = $this->uuid;
        }
        $vies_validation->request_date = now();

        $billingAddress = $this->billingAddress();

        if (!$billingAddress) {
            $vies_validation->error = 'Billing address not found';
            $vies_validation->save();
            logActivity('error', 'Billing address not found during VIES validation', 'vies_validation', null, null,
                null, $this->uuid);

            return;
        }

        $countryCode = $billingAddress->country->code;
        $euCountries = config('taxes.EU');

        if (!array_key_exists($countryCode, $euCountries)) {
            $vies_validation->error = 'Not applicable';
            $vies_validation->save();
            logActivity('error', 'Client not from EU country during VIES validation', 'vies_validation', null, null,
                null, $this->uuid);

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
                logActivity('error', 'VIES request failed or timed out', 'vies_validation', null, null, null,
                    $this->uuid);
                $vies_validation->save();
            }
        } catch (\Exception $e) {
            $vies_validation->error = $e->getMessage();
            logActivity('error', 'Exception during VIES validation: '.$e->getMessage(), 'vies_validation', null, null,
                null, $this->uuid);
            $vies_validation->save();
        }

        $vies_validation->save();
    }

    public function getTaxRule(): ?TaxRule
    {
        $private_client = empty($this->tax_id) && empty($this->company_name);
        $company_without_tax_id = empty($this->tax_id) && !empty($this->company_name);
        $company_with_tax_id = !empty($this->tax_id);

        $billingAddress = $this->billingAddress();
        $country_uuid = $billingAddress->country->uuid ?? null;
        $region_uuid = $billingAddress->region->uuid ?? null;

        $tax_rule = TaxRule::where(function ($query) use (
            $private_client,
            $company_without_tax_id,
            $company_with_tax_id
        ) {
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

        if (!$tax_rule) {
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
                if (!empty($tax_rule->{$tax})) {
                    $taxes[] = ['name' => $tax_rule->{$tax.'_name'}, 'rate' => $tax_rule->{$tax}];
                }
            }

            return $taxes;
        }

        $taxes = [];
        foreach (['tax_1', 'tax_2', 'tax_3'] as $tax) {
            if (!empty($home_company->{$tax})) {
                $taxes[] = ['name' => $home_company->{$tax.'_name'}, 'rate' => $home_company->{$tax}];
            }
        }

        return $taxes;
    }

    public function getHomeCompany(): ?HomeCompany
    {
        $tax_rule = $this->getTaxRule();

        if (!$tax_rule) {
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

    public function createStandaloneInvoice(): string
    {
        return DB::transaction(function () {
            $taxes = $this->getTaxes();
            $home_company = $this->getHomeCompany();

            $invoice = new Invoice;
            $invoice->type = 'proforma';
            $invoice->status = 'draft';
            $invoice->billing_purpose = 'standalone';
            $invoice->client_uuid = $this->uuid;
            $invoice->home_company_uuid = $home_company->uuid;

            $index = 1;
            foreach ($taxes as $tax) {
                if ($index > 3) {
                    continue;
                }
                $invoice->{"tax_$index"} = $tax['rate'];
                $invoice->{"tax_{$index}_name"} = $tax['name'];
                $index++;
            }

            $invoice->save();

            return $invoice->uuid;
        });
    }

    public function getNotificationRule($category, $notification): ?NotificationRule
    {
        $home_company = $this->getHomeCompany();
        if (!$home_company) {
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
            'balance' => round($balance, 2, PHP_ROUND_HALF_UP),
            'min_add_funds_amount' => round($min_add_funds_amount, 2, PHP_ROUND_HALF_UP),
            'max_add_funds_amount' => round($max_add_funds_amount, 2, PHP_ROUND_HALF_UP),
            'max_client_balance' => round($max_client_balance, 2, PHP_ROUND_HALF_UP),
            'recommended_add_funds_amount' => round($recommended_add_funds_amount, 0),
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
            ->where('billing_mode', '!=', 'invoice')
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
            'hourly' => round($totalHourly, 4, PHP_ROUND_HALF_UP),
            'daily' => round($totalHourly * 24, 2, PHP_ROUND_HALF_UP),
            'weekly' => round($totalHourly * 168, 2, PHP_ROUND_HALF_UP),
            'monthly' => round($totalHourly * 720, 2, PHP_ROUND_HALF_UP),
            'yearly' => round($totalHourly * 8760, 2, PHP_ROUND_HALF_UP),
            'recommended_funds' => round($this->calculateRecommendedAddFundsAmount(), 2, PHP_ROUND_HALF_UP),
            'currency' => $currency,
        ];

        return $totals;
    }
}
