@php
    $items = $invoice->invoiceItems;
    $home_company = $invoice->homeCompany;
    $currency = $invoice->getCurrency();

    $formatAmount = function($amount) use ($currency) {
        return ($currency->prefix ?? '') .' '. number_format($amount, 2) .' '. ($currency->suffix ?? '');
    };

    $related_invoice = $invoice->finalInvoice;
@endphp

    <!-- HEADER -->
<table
    style="width: 100%; max-width: 800px; font-family: {{ $pdf_font }}, serif; color: #5d4e37; border-collapse: collapse; background: #fffefc;">
    <tr>
        <td style="padding: 10px 0; background: #f7f4ef; border-bottom: 1px solid #ddd4c2;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 60%; vertical-align: middle; padding-left: 10px;">
                        @if($home_company->images['logo'] ?? false)
                            <img src="{{ $home_company->images['logo'] }}" alt="Logo" style="max-height: 40px;">
                        @endif
                        <div style="font-size: 11px; color: #776655;">
                            <div>Credit Note Date: <strong>{{ $invoice->issue_date->format('j.m.Y') }}</strong></div>
                        </div>
                    </td>
                    <td style="width: 40%; text-align: right; vertical-align: middle; padding-right: 10px;">
                        <div style="background: #fdfaf5; padding: 10px; border: 1px solid #e0d8c5;">
                            <h3 style="margin: 0; font-size: 18px; color: #5d4e37;">Credit Note</h3>
                            <div style="font-size: 13px; color: #5d4e37; font-weight: 600;">
                                #{{ $invoice->number }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<br>
<!-- BILL TO / FROM -->
<table
    style="width: 100%; max-width: 800px; font-family: {{ $pdf_font }}, serif; color: #5d4e37; border-collapse: collapse; background: #fffefc; border: 1px solid #e0d8c5; margin-top: 10px;">
    <thead>
    <tr style="background: #eae4da; text-transform: uppercase; font-size: 13px;">
        <th style="width: 50%; padding: 8px; text-align: left;">Bill To</th>
        <th style="width: 50%; padding: 8px; text-align: left;">From</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td style="padding: 10px; vertical-align: top; font-size: 12px;">
            <div
                style="font-weight: 600;">{{ $invoice->client_company_name ?: $invoice->client_firstname . ' ' . $invoice->client_lastname }}</div>
            @if ($invoice->client_address_1)
                {{ $invoice->client_address_1 }}<br>
            @endif
            @if ($invoice->client_address_2)
                {{ $invoice->client_address_2 }}<br>
            @endif
            @if ($invoice->client_postcode || $invoice->client_city)
                {{ $invoice->client_postcode }} {{ $invoice->client_city }}<br>
            @endif
            @if ($invoice->client_country)
                {{ $invoice->client_country }}<br>
            @endif
            @if ($invoice->client_tax_id)
                <small>TAX ID: {{ $invoice->client_tax_id }}</small>
            @endif
        </td>
        <td style="padding: 10px; vertical-align: top; font-size: 12px;">
            <div style="font-weight: 600;">{{ $invoice->home_company_company_name }}</div>
            @if ($invoice->home_company_address_1)
                {{ $invoice->home_company_address_1 }}<br>
            @endif
            @if ($invoice->home_company_address_2)
                {{ $invoice->home_company_address_2 }}<br>
            @endif
            @if ($invoice->home_company_postcode || $invoice->home_company_city)
                {{ $invoice->home_company_postcode }} {{ $invoice->home_company_city }}<br>
            @endif
            @if ($invoice->home_company_country)
                {{ $invoice->home_company_country }}<br>
            @endif
            @if ($invoice->home_company_tax_local_id)
                <small>TAX ID: {{ $invoice->home_company_tax_local_id }}</small>
            @endif
        </td>
    </tr>
    </tbody>
</table>
<br>
<br>
<br>
<br>
@if($related_invoice)
    <table style="width: 100%; max-width: 800px; font-family: {{ $pdf_font }}, serif; color: #5d4e37; background: #fffefc; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 12px 16px; background: #fdfaf5; border: 1px solid #e0d8c5; font-size: 13px;">
                <strong>Note:</strong> This credit note applies to
                <strong>Invoice #{{ $related_invoice->number }}</strong>
                issued on <strong>{{ $related_invoice->issue_date->format('j.m.Y') }}</strong>.
            </td>
        </tr>
    </table>
@endif

<!-- ITEMS TITLE -->
<h4 style="font-family: {{ $pdf_font }}, serif; color: #5d4e37; margin-top: 20px;">Items</h4>

<!-- ITEMS TABLE -->
<table
    style="width: 100%; max-width: 800px; font-family: {{ $pdf_font }}, serif; color: #5d4e37; border-collapse: separate; border-spacing: 0 4px; background: #fffefc; border: 1px solid #e0d8c5;">
    <thead>
    <tr style="background: #eae4da; font-size: 13px; text-transform: uppercase;">
        <th style="text-align: left; padding: 8px;">Description</th>
        <th style="text-align: right; padding: 8px;">Amount</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($items as $index => $item)
        <tr style="background: {{ $index % 2 == 0 ? '#fffefc' : '#f5f2ec' }};">
            <td style="padding: 8px 8px 8px 12px; font-size: 12px; border-bottom: 1px dotted #d8d0c4;">
                @foreach (explode("\n", $item->description) as $line)
                    @if (Str::startsWith(trim($line), '*-'))
                        <div style="font-size: 10px; color: #a0916d; font-style: italic;">{{ trim($line, '*- ') }}</div>
                    @else
                        <div style="{{ $loop->first ? 'font-weight: 600;' : '' }}">{{ $line }}</div>
                    @endif
                @endforeach
            </td>
            <td style="text-align: right; padding: 8px 12px; font-size: 12px; font-weight: 600; border-bottom: 1px dotted #d8d0c4;">{{ $formatAmount($item->amount) }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="text-align: right; padding: 6px 12px; font-weight: 600;">Subtotal:</td>
        <td style="text-align: right; padding: 6px 12px;">{{ $formatAmount($invoice->subtotal) }}</td>
    </tr>
    @foreach ([
        ['name' => $invoice->tax_1_name, 'rate' => $invoice->tax_1, 'amount' => $invoice->tax_1_amount],
        ['name' => $invoice->tax_2_name, 'rate' => $invoice->tax_2, 'amount' => $invoice->tax_2_amount],
        ['name' => $invoice->tax_3_name, 'rate' => $invoice->tax_3, 'amount' => $invoice->tax_3_amount],
    ] as $tax)
        @if ($tax['amount'] > 0)
            <tr>
                <td style="text-align: right; padding: 4px 12px; font-size: 12px;">{{ $tax['name'] ?: 'Tax' }}
                    ({{ number_format($tax['rate'], 2) }}%)
                </td>
                <td style="text-align: right; padding: 4px 12px; font-size: 12px;">{{ $formatAmount($tax['amount']) }}</td>
            </tr>
        @endif
    @endforeach
    <tr>
        <td style="text-align: right; padding: 6px 12px; font-weight: 600;">Total Tax:</td>
        <td style="text-align: right; padding: 6px 12px;">{{ $formatAmount($invoice->tax) }}</td>
    </tr>
    <tr style="background: #8b7355; color: #fff;">
        <td style="text-align: right; padding: 8px 12px; font-weight: 700;">Grand Total:</td>
        <td style="text-align: right; padding: 8px 12px; font-weight: 700;">{{ $formatAmount($invoice->total) }}</td>
    </tr>
    </tbody>
</table>
<br>
<br>
<br>
<br>
<!-- TRANSACTIONS -->
@if ($invoice->transactions && count($invoice->transactions) > 0)
    <h4 style="font-family: {{ $pdf_font }}, serif; color: #5d4e37; margin-top: 20px;">Transactions</h4>
    <table
        style="width: 100%; max-width: 800px; font-family: {{ $pdf_font }}, serif; color: #5d4e37; border-collapse: separate; border-spacing: 0 4px; background: #fffefc; margin-top: 10px;">
        <thead>
        <tr style="background: #eae4da; font-size: 13px; text-transform: uppercase;">
            <th style="text-align: left; padding: 8px 12px;">Date</th>
            <th style="text-align: left; padding: 8px 12px;">Gateway</th>
            <th style="text-align: left; padding: 8px 12px;">Transaction ID</th>
            <th style="text-align: right; padding: 8px 12px;">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($invoice->transactions as $index => $item)
            @php
                $gateway_name = $item->paymentGateway->name ?? $item->paymentGateway->key ?? '---';
            @endphp
            <tr style="background: {{ $index % 2 == 0 ? '#fffefc' : '#f5f2ec' }};">
                <td style="padding: 6px 12px; font-size: 12px;">{{ $item->transaction_date->format('j.m.Y') }}</td>
                <td style="padding: 6px 12px; font-size: 12px;">{{ $gateway_name }}</td>
                <td style="padding: 6px 12px; font-size: 12px;">{{ $item->transaction_id }}</td>
                <td style="padding: 6px 12px; text-align: right; font-size: 12px;">{!! formatCurrency($item->amount_gross, $currency) !!}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

