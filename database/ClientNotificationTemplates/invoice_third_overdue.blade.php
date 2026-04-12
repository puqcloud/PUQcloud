@php
    $client = $invoice->client;
    $currency = $invoice->getCurrency();
    $formatAmount = function($amount) use ($currency) {
        return ($currency->prefix ?? '') .' '. number_format($amount, 2) .' '. ($currency->suffix ?? '');
    };
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td style="padding: 20px;">
            <p style="font-size: 16px;">
                Dear <strong>{{ $client->company_name ? $client->company_name . ' (' . $client->firstname . ' ' . $client->lastname . ')' : $client->firstname . ' ' . $client->lastname }}</strong>,
            </p>

            <p style="font-size: 15px; color: #cc0000;">
                <strong>This is the final notice regarding your overdue invoice.</strong>
            </p>

            <table cellpadding="6" cellspacing="0" style="border: 1px solid #ddd; margin-top: 10px; font-size: 15px;">
                <tr>
                    <td><strong>Invoice Number:</strong></td>
                    <td>#{{ $invoice->number }}</td>
                </tr>
                <tr>
                    <td><strong>Amount Due:</strong></td>
                    <td style="color: #cc0000;"><strong>{{ $formatAmount($invoice->total) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Due Date:</strong></td>
                    <td style="color: #cc0000;">{{ $invoice->due_date->format('d.m.Y') }}</td>
                </tr>
            </table>

            <p style="margin-top: 20px; font-size: 15px; color: #cc0000;">
                <strong>Your services will be suspended if payment is not received. This is the final reminder before action is taken.</strong>
            </p>

            <p style="font-size: 15px;">
                <a href="{{ route('client.web.panel.client.invoice.details', $invoice->uuid) }}" style="color: #3366cc;">
                    View and Pay Invoice
                </a>
            </p>
        </td>
    </tr>
</table>
