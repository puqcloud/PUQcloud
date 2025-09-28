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

            <p style="font-size: 15px;">
                Thank you! Your payment has been received. Below are the details of the paid invoice.
            </p>

            <table cellpadding="6" cellspacing="0" style="border: 1px solid #ddd; margin-top: 10px; font-size: 15px;">
                <tr>
                    <td><strong>Invoice Number:</strong></td>
                    <td>#{{ $invoice->number }}</td>
                </tr>
                <tr>
                    <td><strong>Total Paid:</strong></td>
                    <td>{{ $formatAmount($invoice->total) }}</td>
                </tr>
                <tr>
                    <td><strong>Paid Date:</strong></td>
                    <td>{{ $invoice->due_date->format('j.m.Y') }}</td>
                </tr>
            </table>

            <p style="margin-top: 20px; font-size: 15px;">
                You can view the full invoice in your client area:
            </p>

            <p style="font-size: 15px;">
                <a href="{{ route('client.web.panel.client.invoice.details', $invoice->uuid) }}" style="color: #3366cc;">
                    View Invoice in Client Area
                </a>
            </p>

        </td>
    </tr>
</table>
