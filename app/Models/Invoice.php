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

use App\Services\HookService;
use App\Services\SettingService;
use App\Traits\ConvertsTimezone;
use App\Traits\ModelActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use ConvertsTimezone;
    use ModelActivityLogger;

    protected $table = 'invoices';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->fillHomeCompanyData();
            $model->fillClientData();
            $model->setOther();
            $model->setNumber();
        });

    }

    protected $fillable = [
        'client_uuid',
        'home_company_uuid',
        'type',
        'number',
        'invoice_uuid',
        'status',
        'currency_code',
        'tax_1',
        'tax_2',
        'tax_3',
        'tax_1_name',
        'tax_2_name',
        'tax_3_name',
        'tax_1_amount',
        'tax_2_amount',
        'tax_3_amount',
        'subtotal',
        'tax',
        'total',
        'issue_date',
        'due_date',
        'payment_date',
        'refunded_date',
        'canceled_date',
        'admin_notes',
        'client_firstname',
        'client_lastname',
        'client_company_name',
        'client_country',
        'client_postcode',
        'client_address_1',
        'client_address_2',
        'client_city',
        'client_region',
        'client_email',
        'client_tax_id',
        'home_company_company_name',
        'home_company_address_1',
        'home_company_address_2',
        'home_company_city',
        'home_company_postcode',
        'home_company_country',
        'home_company_region',
        'home_company_tax_local_id',
        'home_company_tax_local_id_name',
        'home_company_tax_eu_vat_id',
        'home_company_tax_eu_vat_id_name',
        'home_company_registration_number',
        'home_company_registration_number_name',
        'home_company_us_ein',
        'home_company_us_state_tax_id',
        'home_company_us_entity_type',
        'home_company_ca_gst_hst_number',
        'home_company_ca_pst_qst_number',
        'home_company_ca_entity_type',
        'home_company_pay_to_text',
        'home_company_invoice_footer_text',
    ];

    protected $casts = [
        'issue_date' => 'datetime:Y-m-d H:i',
        'due_date' => 'datetime:Y-m-d H:i',
        'payment_date' => 'datetime:Y-m-d H:i',
        'refunded_date' => 'datetime:Y-m-d H:i',
        'canceled_date' => 'datetime:Y-m-d H:i',
    ];

    protected $appends = [
        'paid_net_amount',
        'paid_gross_amount',
        'due_amount',

        'reference_proforma_uuid',
        'reference_invoice_uuid',
        'reference_credit_note_uuids',

    ];

    protected $with = [
        'transactions',
    ];

    public function getCurrency(): Currency
    {
        $currency = Currency::where('code', $this->currency_code)->first();
        if (! $currency) {
            $currency = Currency::where('default', true)->first();
        }

        return $currency;
    }

    public function invoiceItems(): hasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_uuid', 'uuid');
    }

    public function transactions(): hasMany
    {
        return $this->hasMany(Transaction::class, 'relation_model_uuid', 'uuid')
            ->where('relation_model', '=', get_class($this));
    }

    public function finalInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_uuid', 'uuid');
    }

    public function referencedBy(): hasMany
    {
        return $this->hasMany(Invoice::class, 'invoice_uuid', 'uuid');
    }

    protected function referenceProformaUuid(): Attribute
    {
        return Attribute::get(function () {
            return $this->type === 'invoice'
                ? $this->referencedBy()->where('type', 'proforma')->value('uuid')
                : null;
        });
    }

    protected function referenceInvoiceUuid(): Attribute
    {
        return Attribute::get(function () {
            if ($this->type === 'proforma' || $this->type === 'credit_note') {
                return $this->invoice_uuid;
            }

            return null;
        });
    }

    protected function referenceCreditNoteUuids(): Attribute
    {
        return Attribute::get(function () {
            return $this->type === 'invoice'
                ? $this->referencedBy()->where('type', 'credit_note')->pluck('uuid', 'number')->toArray()
                : [];
        });
    }

    public function getPaidNetAmountAttribute(): string
    {
        return number_format_custom($this->transactions->sum('amount_net'));
    }

    public function getPaidGrossAmountAttribute(): string
    {
        return number_format_custom($this->transactions->sum('amount_gross'), 2);
    }

    public function getDueAmountAttribute()
    {
        $tmp = (float) $this->total - (float) $this->paid_gross_amount;

        return max('0.00', number_format_custom($tmp));
    }

    public function client(): belongsTo
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function homeCompany(): belongsTo
    {
        return $this->belongsTo(HomeCompany::class, 'home_company_uuid', 'uuid');
    }

    public function fillHomeCompanyData(): void
    {
        if (! $this->home_company_uuid) {
            return;
        }

        $homeCompany = HomeCompany::with('country', 'region')->find($this->home_company_uuid);

        if (! $homeCompany) {
            return;
        }

        $map = [
            'home_company_company_name' => 'company_name',
            'home_company_address_1' => 'address_1',
            'home_company_address_2' => 'address_2',
            'home_company_city' => 'city',
            'home_company_postcode' => 'postcode',
            'home_company_tax_local_id' => 'tax_local_id',
            'home_company_tax_local_id_name' => 'tax_local_id_name',
            'home_company_tax_eu_vat_id' => 'tax_eu_vat_id',
            'home_company_tax_eu_vat_id_name' => 'tax_eu_vat_id_name',
            'home_company_registration_number' => 'registration_number',
            'home_company_registration_number_name' => 'registration_number_name',
            'home_company_us_ein' => 'us_ein',
            'home_company_us_state_tax_id' => 'us_state_tax_id',
            'home_company_us_entity_type' => 'us_entity_type',
            'home_company_ca_gst_hst_number' => 'ca_gst_hst_number',
            'home_company_ca_pst_qst_number' => 'ca_pst_qst_number',
            'home_company_ca_entity_type' => 'ca_entity_type',
            'home_company_pay_to_text' => 'pay_to_text',
            'home_company_invoice_footer_text' => 'invoice_footer_text',
        ];

        foreach ($map as $invoiceField => $homeCompanyField) {
            $this->$invoiceField = $homeCompany->$homeCompanyField;
        }

        $country = $homeCompany->country;
        $this->home_company_country = $country->name ?? '';

        $region = $homeCompany->region;
        $this->home_company_region = $region->name ?? '';
    }

    public function fillClientData(): void
    {
        if (! $this->client_uuid) {
            return;
        }

        $client = Client::with('currency')->find($this->client_uuid);

        if (! $client) {
            return;
        }

        $billing_address = $client->billingAddress();

        $this->client_firstname = $client->firstname;
        $this->client_lastname = $client->lastname;
        $this->client_company_name = $client->company_name;
        $this->client_email = $billing_address->contact_email;
        $this->client_tax_id = $client->tax_id;

        if ($billing_address) {
            $this->client_postcode = $billing_address->postcode;
            $this->client_address_1 = $billing_address->address_1;
            $this->client_address_2 = $billing_address->address_2;
            $this->client_city = $billing_address->city;

            $country = $billing_address->country;
            $this->client_country = $country->name;

            $region = $billing_address->region;
            $this->client_region = $region->name;
        }
        $currency = $client->currency;
        $this->currency_code = $currency->code;
    }

    public function checkAndResetInvoiceNumbering(HomeCompany $homeCompany): void
    {
        $now = now();

        $types = [
            'proforma' => [
                'next' => 'proforma_invoice_number_next',
                'reset' => 'proforma_invoice_number_reset',
            ],
            'invoice' => [
                'next' => 'invoice_number_next',
                'reset' => 'invoice_number_reset',
            ],
            'credit_note' => [
                'next' => 'credit_note_number_next',
                'reset' => 'credit_note_number_reset',
            ],
        ];

        foreach ($types as $type => $config) {
            $resetType = $homeCompany->{$config['reset']};
            if ($resetType === 'never') {
                continue;
            }

            $query = self::where('type', $type)
                ->where('home_company_uuid', $homeCompany->uuid);

            if ($resetType === 'monthly') {
                $query->whereYear('issue_date', $now->year)
                    ->whereMonth('issue_date', $now->month);
            }
            if ($resetType === 'yearly') {
                $query->whereYear('issue_date', $now->year);
            }

            $exists = $query->exists();

            if (! $exists) {
                $homeCompany->{$config['next']} = 1;
            }
        }

        $homeCompany->save();
    }

    public function setNumber(): void
    {
        if (! $this->home_company_uuid) {
            return;
        }

        $homeCompany = HomeCompany::find($this->home_company_uuid);

        if (! $homeCompany) {
            return;
        }

        $this->checkAndResetInvoiceNumbering($homeCompany);

        if ($this->type == 'proforma' and $this->status == 'unpaid' and $this->number == null) {
            $number = $homeCompany->proforma_invoice_number_next;
            $this->number = $homeCompany->proforma_invoice_number_format;
            $this->number = str_replace('{YEAR}', date('Y'), $this->number);
            $this->number = str_replace('{MONTH}', date('m'), $this->number);
            $this->number = str_replace('{DAY}', date('d'), $this->number);
            $this->number = str_replace('{NUMBER}', $number, $this->number);
            $homeCompany->proforma_invoice_number_next += 1;
            $homeCompany->save();
        }

        if ($this->type == 'invoice' and $this->status == 'paid' and $this->number == null) {
            $number = $homeCompany->invoice_number_next;
            $this->number = $homeCompany->invoice_number_format;
            $this->number = str_replace('{YEAR}', date('Y'), $this->number);
            $this->number = str_replace('{MONTH}', date('m'), $this->number);
            $this->number = str_replace('{DAY}', date('d'), $this->number);
            $this->number = str_replace('{NUMBER}', $number, $this->number);
            $homeCompany->invoice_number_next += 1;
            $homeCompany->save();
        }

        if ($this->type == 'credit_note' and $this->status == 'refunded' and $this->number == null) {
            $number = $homeCompany->credit_note_number_next;
            $this->number = $homeCompany->credit_note_number_format;
            $this->number = str_replace('{YEAR}', date('Y'), $this->number);
            $this->number = str_replace('{MONTH}', date('m'), $this->number);
            $this->number = str_replace('{DAY}', date('d'), $this->number);
            $this->number = str_replace('{NUMBER}', $number, $this->number);
            $homeCompany->credit_note_number_next += 1;
            $homeCompany->save();
        }

    }

    public function setOther(): void
    {
        $default_invoice_due_days = SettingService::get('finance.default_invoice_due_days');
        $this->issue_date = now();
        $this->due_date = now()->addDays((int) $default_invoice_due_days);
    }

    public function calculateTotals(): void
    {
        $items = $this->invoiceItems;

        $subtotal = $items->sum('amount');
        $this->subtotal = $subtotal;

        $taxedAmount = $items->where('taxed', true)->sum('amount');

        $taxTotal = 0;

        for ($i = 1; $i <= 3; $i++) {
            $rate = (float) $this->{'tax_'.$i};
            $name = $this->{'tax_'.$i.'_name'};

            if (! empty($name)) {
                $amount = number_format($taxedAmount * ($rate / 100), 2, '.', '');
                $this->{'tax_'.$i.'_amount'} = $amount;
                $taxTotal += $amount;
            } else {
                $this->{'tax_'.$i.'_amount'} = 0;
            }
        }

        $this->tax = $taxTotal;
        $this->total = $subtotal + $taxTotal;
    }

    public function publish(): array
    {
        if ($this->type != 'proforma') {
            return [
                'status' => 'error',
                'errors' => [__('error.The type should be proforma')],
            ];
        }

        if ($this->status != 'draft') {
            return [
                'status' => 'error',
                'errors' => [__('error.The status should be draft')],
            ];
        }

        if ($this->invoiceItems->count() == 0) {
            return [
                'status' => 'error',
                'errors' => [__('error.There are no items in the invoice')],
            ];
        }

        $this->fillHomeCompanyData();
        $this->fillClientData();
        $this->setOther();
        $this->calculateTotals();
        $this->type = 'proforma';
        $this->status = 'unpaid';
        $this->setNumber();
        $this->save();
        logActivity(
            'info',
            'Invoice:'.$this->uuid.' ('.$this->number.') published. Subtotal: '.$this->subtotal.' '.$this->client->currency->code,
            'publish',
            null,
            null,
            null,
            $this->client_uuid
        );
        app(HookService::class)->callHooks('ProformaInvoiceCreated', ['invoice' => $this]);

        return ['status' => 'success'];
    }

    public function cancel(): array
    {

        if ($this->type != 'proforma') {
            return [
                'status' => 'error',
                'errors' => [__('error.The type should be proforma')],
            ];
        }

        if ($this->status != 'unpaid') {
            return [
                'status' => 'error',
                'errors' => [__('error.The status should be unpaid')],
            ];
        }

        $this->type = 'proforma';
        $this->status = 'canceled';
        $this->canceled_date = now();
        $this->save();

        logActivity(
            'info',
            'Invoice:'.$this->uuid.' ('.$this->number.') published. Subtotal: '.$this->subtotal.' '.$this->client->currency->code,
            'cancel',
            null,
            null,
            null,
            $this->client_uuid
        );

        return ['status' => 'success'];
    }

    public function makeRefund($params): array
    {
        if ($params['amount'] <= 0) {
            return [
                'status' => 'error',
                'errors' => [__('error.The amount must be greater than 0')],
            ];
        }

        if ($params['amount'] > $this->paid_net_amount) {
            return [
                'status' => 'error',
                'errors' => [__('error.The refund amount must not exceed the amount paid')],
            ];
        }

        if ($params['amount'] > $this->total) {
            return [
                'status' => 'error',
                'errors' => [__('error.Amount must be less than or equal to Total')],
            ];
        }

        if (empty($params['transaction_id'])) {
            return [
                'status' => 'error',
                'errors' => [__('error.Transaction ID is required')],
            ];
        }

        $payment_gateway = $this->homeCompany->paymentGateways()->where('uuid', $params['payment_gateway_uuid'] ?? '')->first();
        if (empty($payment_gateway)) {
            return [
                'status' => 'error',
                'errors' => [__('error.Payment Gateway is required')],
            ];
        }

        $exists = $this->transactions()->where('transaction_id', $params['transaction_id'])->exists();

        if ($exists) {
            return [
                'status' => 'error',
                'errors' => [__('error.Transaction already exists')],
            ];
        }

        try {
            DB::beginTransaction();

            $this->createTransaction(
                -(float) $params['amount'],
                -$this->getGross($params['amount']),
                -$params['fees'] ?? 0.00,
                $params['transaction_id'] ?? '',
                $params['description'] ?? null,
                'refund',
                $payment_gateway->uuid,
            );

            $this->refresh();

            $newInvoice = $this->replicate();
            $newInvoice->uuid = (string) Str::uuid();
            $newInvoice->client_uuid = $this->client_uuid;
            $newInvoice->home_company_uuid = $this->home_company_uuid;
            $newInvoice->invoice_uuid = $this->uuid;
            $newInvoice->type = 'credit_note';
            $newInvoice->status = 'refunded';
            $newInvoice->number = null;

            $newInvoice->save();

            $invoice_item = new InvoiceItem;
            $home_company = $newInvoice->homeCompany;

            $invoice_item->invoice_uuid = $newInvoice->uuid;

            $invoice_item->description = $home_company->refund_item_name;
            if ($home_company->refund_item_description) {
                $invoice_item->description .= PHP_EOL.'*-'.$home_company->refund_item_description;
            }
            $invoice_item->description = str_replace('{INVOICE_NUMBER}', $this->number, $invoice_item->description);
            $invoice_item->description = str_replace('{YEAR}', date('Y'), $invoice_item->description);
            $invoice_item->description = str_replace('{MONTH}', date('m'), $invoice_item->description);
            $invoice_item->description = str_replace('{DAY}', date('d'), $invoice_item->description);

            $invoice_item->taxed = true;
            $invoice_item->amount = -$params['amount'];
            $invoice_item->save();

            $newInvoice->calculateTotals();
            $newInvoice->setNumber();
            $newInvoice->issue_date = now();
            $newInvoice->due_date = now();
            $newInvoice->paid_date = now();
            $newInvoice->refunded_date = now();
            $newInvoice->save();
            $newInvoice->refresh();
            logActivity(
                'info',
                'CreditNote:'.$newInvoice->uuid.' ('.$newInvoice->number.') created. Amount: '.$newInvoice->subtotal.' '.$this->client->currency->code,
                'create',
                null,
                null,
                null,
                $this->client_uuid
            );

            $this->save();

            DB::commit();

            return ['status' => 'success', 'data' => $newInvoice->toArray() ?? []];
        } catch (\Throwable $e) {
            DB::rollBack();

            logActivity(
                'error',
                'Refund failed: '.$e->getMessage(),
                'exception',
                null,
                null,
                null,
                $this->client_uuid ?? null
            );

            return [
                'status' => 'error',
                'errors' => [__('error.Refund failed. Please try again later'), $e->getMessage()],
            ];
        }
    }

    protected function createTransaction(float $amount_net, float $amount_gross, float $fees, string $transaction_id, $note = null, $type = 'payment', $payment_gateway_uuid = null): void
    {
        $description = "Invoice:{$this->uuid}";

        if ($note !== null) {
            $description .= ", {$note}";
        }

        Transaction::create([
            'client_uuid' => $this->client_uuid,
            'type' => $type,
            'amount_net' => $amount_net,
            'amount_gross' => $amount_gross,
            'fees' => $fees,
            'description' => $description,
            'relation_model' => get_class($this),
            'relation_model_uuid' => $this->uuid,
            'transaction_date' => now(),
            'period_start' => now(),
            'period_stop' => now(),
            'transaction_id' => $transaction_id,
            'payment_gateway_uuid' => $payment_gateway_uuid,
        ]);
    }

    private function getNet($netAmount, bool $returnBreakdown = false): float|array
    {
        $netAmount = (float) $netAmount;
        $taxPercent = 0;
        $taxRates = [];

        if (! empty($this->tax_1_name)) {
            $taxRates[$this->tax_1_name] = (float) $this->tax_1;
            $taxPercent += $taxRates[$this->tax_1_name];
        }
        if (! empty($this->tax_2_name)) {
            $taxRates[$this->tax_2_name] = (float) $this->tax_2;
            $taxPercent += $taxRates[$this->tax_2_name];
        }
        if (! empty($this->tax_3_name)) {
            $taxRates[$this->tax_3_name] = (float) $this->tax_3;
            $taxPercent += $taxRates[$this->tax_3_name];
        }

        $grossAmount = $netAmount;
        $netAmount = $taxPercent > 0 ? $grossAmount / (1 + $taxPercent / 100) : $grossAmount;
        $taxAmount = $grossAmount - $netAmount;

        if ($returnBreakdown) {
            $taxes = [];
            foreach ($taxRates as $name => $rate) {
                $taxes[$name] = number_format_custom($taxAmount * ($rate / $taxPercent));
            }

            return [
                'gross' => number_format_custom($grossAmount),
                'net' => number_format_custom($netAmount),
                'tax' => number_format_custom($taxAmount),
                'taxes' => $taxes,
            ];
        }

        return number_format_custom($netAmount);
    }

    private function getGross($grossAmount, bool $returnBreakdown = false): float|array
    {
        $grossAmount = (float) $grossAmount;
        $taxPercent = 0;
        $taxRates = [];

        if (! empty($this->tax_1_name)) {
            $taxRates[$this->tax_1_name] = (float) $this->tax_1;
            $taxPercent += $taxRates[$this->tax_1_name];
        }
        if (! empty($this->tax_2_name)) {
            $taxRates[$this->tax_2_name] = (float) $this->tax_2;
            $taxPercent += $taxRates[$this->tax_2_name];
        }
        if (! empty($this->tax_3_name)) {
            $taxRates[$this->tax_3_name] = (float) $this->tax_3;
            $taxPercent += $taxRates[$this->tax_3_name];
        }

        $netAmount = $grossAmount;
        $grossAmount = $taxPercent > 0 ? $netAmount * (1 + $taxPercent / 100) : $netAmount;
        $taxAmount = $grossAmount - $netAmount;

        if ($returnBreakdown) {
            $taxes = [];
            foreach ($taxRates as $name => $rate) {
                $taxes[$name] = number_format_custom($taxAmount * ($rate / $taxPercent));
            }

            return [
                'net' => number_format_custom($netAmount),
                'gross' => number_format_custom($grossAmount),
                'tax' => number_format_custom($taxAmount),
                'taxes' => $taxes,
            ];
        }

        return number_format_custom($grossAmount);
    }

    private function getTaxFromNet($netAmount, bool $returnBreakdown = false): float|array
    {
        $netAmount = (float) $netAmount;
        $taxes = [];

        if (! empty($this->tax_1_name)) {
            $taxes[$this->tax_1_name] = $netAmount * ((float) $this->tax_1 / 100);
        }
        if (! empty($this->tax_2_name)) {
            $taxes[$this->tax_2_name] = $netAmount * ((float) $this->tax_2 / 100);
        }
        if (! empty($this->tax_3_name)) {
            $taxes[$this->tax_3_name] = $netAmount * ((float) $this->tax_3 / 100);
        }

        $total = array_sum($taxes);

        if ($returnBreakdown) {
            $result = ['total' => number_format_custom($total)];
            foreach ($taxes as $name => $value) {
                $result[$name] = number_format_custom($value);
            }

            return $result;
        }

        return number_format_custom($total);
    }

    private function getTaxFromGross($grossAmount, bool $returnBreakdown = false): float|array
    {
        $grossAmount = (float) $grossAmount;
        $taxes = [];
        $taxTotalPercent = 0;

        if (! empty($this->tax_1_name)) {
            $taxTotalPercent += (float) $this->tax_1;
        }
        if (! empty($this->tax_2_name)) {
            $taxTotalPercent += (float) $this->tax_2;
        }
        if (! empty($this->tax_3_name)) {
            $taxTotalPercent += (float) $this->tax_3;
        }

        if ($taxTotalPercent === 0) {
            return $returnBreakdown ? ['total' => 0.0] : 0.0;
        }

        $netAmount = $grossAmount / (1 + $taxTotalPercent / 100);
        $total = $grossAmount - $netAmount;

        if ($returnBreakdown) {
            $result = ['total' => number_format_custom($total)];
            if (! empty($this->tax_1_name)) {
                $result[$this->tax_1_name] = number_format_custom($total * ((float) $this->tax_1 / $taxTotalPercent));
            }
            if (! empty($this->tax_2_name)) {
                $result[$this->tax_2_name] = number_format_custom($total * ((float) $this->tax_2 / $taxTotalPercent));
            }
            if (! empty($this->tax_3_name)) {
                $result[$this->tax_3_name] = number_format_custom($total * ((float) $this->tax_3 / $taxTotalPercent));
            }

            return $result;
        }

        return number_format_custom($total);
    }

    public function generatePdf(): \Barryvdh\DomPDF\PDF
    {
        $home_company = $this->homeCompany;

        if ($this->type == 'proforma') {
            $template = $home_company->proforma_template;
        }
        if ($this->type == 'invoice') {
            $template = $home_company->invoice_template;
        }
        if ($this->type == 'credit_note') {
            $template = $home_company->credit_note_template;
        }

        $footerText = $this->home_company_invoice_footer_text;
        $paper = $home_company->pdf_paper;
        $font = $home_company->pdf_font;

        $html = Blade::render($template, [
            'invoice' => $this,
            'home_company' => $home_company,
            'pdf_font' => $font,

        ]);

        $pdf = Pdf::loadHTML($html)
            ->setPaper($paper)
            ->setOptions([
                'defaultFont' => $font,
                'isRemoteEnabled' => true,
            ]);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->get_canvas();

        $width = $canvas->get_width();
        $height = $canvas->get_height();

        $font = $dompdf->getFontMetrics()->getFont($font, 'normal');
        $fontSize = 9;

        $lines = explode("\n", $footerText);
        $lineHeight = 12;
        $startY = $height - 30 - ($lineHeight * (count($lines) - 1));

        foreach ($lines as $i => $line) {
            $y = $startY + ($i * $lineHeight);
            $canvas->page_text(35, $y, trim($line), $font, $fontSize, [0, 0, 0]);
        }

        $canvas->page_text($width - 50, $height - 30, '{PAGE_NUM}/{PAGE_COUNT}', $font, $fontSize, [0, 0, 0]);

        return $pdf;
    }

    public function generatePdfBase64(): string
    {
        $pdf = $this->generatePdf();

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $pdfContent = $dompdf->output();

        return base64_encode($pdfContent);
    }

    public function getSafeFilename(): string
    {
        $type = $this->type ?? 'invoice';
        $number = $this->number ?? 'unknown';
        $filename = "{$type}-{$number}";

        return preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $filename).'.pdf';
    }

    public function addPayment($params): array
    {
        if ($params['amount'] <= 0) {
            return ['status' => 'error', 'errors' => [__('error.The amount must be greater than 0')]];
        }

        if ($params['amount'] > $this->due_amount) {
            return ['status' => 'error', 'errors' => [__('error.Amount must be less than or equal to Due Amount')]];
        }

        $payment_gateway = $this->homeCompany->paymentGateways()->where('uuid', $params['payment_gateway_uuid'] ?? '')->first();
        if (! $payment_gateway) {
            return ['status' => 'error', 'errors' => [__('error.Payment Gateway is required')]];
        }

        if (empty($params['transaction_id'])) {
            return ['status' => 'error', 'errors' => [__('error.Transaction ID is required')]];
        }

        if ($this->transactions()->where('transaction_id', $params['transaction_id'])->exists()) {
            return ['status' => 'error', 'errors' => [__('error.Transaction already exists')]];
        }

        return $this->handleSuccessfulPayment(
            (float) $params['amount'],
            (float) ($params['fees'] ?? 0.00),
            $params['transaction_id'],
            $params['description'] ?? null,
            $payment_gateway->uuid,
        );
    }

    public function addPaymentByPaymentGateway(
        float $amount,
        float $fee,
        string $transaction_id,
        ?string $description,
        string $payment_gateway_uuid
    ): array {
        if ($this->transactions()->where('transaction_id', $transaction_id)->exists()) {
            return ['status' => 'error', 'errors' => [__('error.Transaction already exists')]];
        }

        return $this->handleSuccessfulPayment(
            $amount,
            $fee,
            $transaction_id,
            $description,
            $payment_gateway_uuid,
        );
    }

    private function handleSuccessfulPayment(
        float $amount,
        float $fee,
        string $transaction_id,
        ?string $description,
        string $payment_gateway_uuid
    ): array {
        DB::beginTransaction();

        try {
            $this->createTransaction(
                $this->getNet($amount),
                $amount,
                $fee,
                $transaction_id,
                $description,
                'payment',
                $payment_gateway_uuid,
            );

            $this->refresh();

            if ($this->due_amount > 0) {
                DB::commit();

                return ['status' => 'success', 'message' => __('message.Payment add Successfully')];
            }

            $newInvoice = $this->replicate();
            $newInvoice->uuid = (string) Str::uuid();
            $newInvoice->client_uuid = $this->client_uuid;
            $newInvoice->home_company_uuid = $this->home_company_uuid;
            $newInvoice->type = 'invoice';
            $newInvoice->status = 'paid';
            $newInvoice->number = null;
            $newInvoice->save();

            foreach ($this->invoiceItems as $item) {
                $newItem = $item->replicate();
                $newItem->uuid = (string) Str::uuid();
                $newItem->invoice_uuid = $newInvoice->uuid;
                $newItem->save();
            }

            $newInvoice->calculateTotals();
            $newInvoice->setNumber();
            $newInvoice->issue_date = now();
            $newInvoice->due_date = now();
            $newInvoice->paid_date = now();
            $newInvoice->save();

            foreach ($this->transactions as $transaction) {
                $transaction->relation_model_uuid = $newInvoice->uuid;
                $transaction->save();
            }

            logActivity(
                'info',
                'Invoice:'.$newInvoice->uuid.' ('.$newInvoice->number.') created. Amount: '.$newInvoice->subtotal.' '.$this->client->currency->code,
                'create',
                null,
                null,
                null,
                $this->client_uuid
            );

            $this->status = 'invoiced';
            $this->paid_date = now();
            $this->invoice_uuid = $newInvoice->uuid;
            $this->save();

            DB::commit();

            app(HookService::class)->callHooks('InvoiceCreated', ['invoice' => $newInvoice]);

            return ['status' => 'success'];
        } catch (\Throwable $e) {
            DB::rollBack();

            logActivity(
                'error',
                'Payment failed: '.$e->getMessage(),
                'exception',
                null,
                null,
                null,
                $this->client_uuid ?? null
            );

            return [
                'status' => 'error',
                'errors' => [__('error.Payment failed. Please try again later.'), $e->getMessage()],
            ];
        }
    }
}
