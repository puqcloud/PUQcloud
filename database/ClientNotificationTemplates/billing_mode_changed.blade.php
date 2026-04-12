@php
    $client = $service->client;
    $productName = $service->product->name ?: $service->product->key;
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td style="padding: 20px;">
            <p style="font-size: 16px;">
                Dear <strong>{{ $client->company_name ? $client->company_name . ' (' . $client->firstname . ' ' . $client->lastname . ')' : $client->firstname . ' ' . $client->lastname }}</strong>,
            </p>

            <p style="font-size: 15px;">
                The billing mode for your service has been changed.
            </p>

            <table cellpadding="6" cellspacing="0" style="border: 1px solid #ddd; margin-top: 10px; font-size: 15px;">
                <tr>
                    <td><strong>Service:</strong></td>
                    <td>{{ $productName }} ({{ $service->admin_label }})</td>
                </tr>
                <tr>
                    <td><strong>Previous Mode:</strong></td>
                    <td>{{ ucfirst($old_mode) }}</td>
                </tr>
                <tr>
                    <td><strong>New Mode:</strong></td>
                    <td>{{ ucfirst($new_mode) }}</td>
                </tr>
            </table>

            <p style="margin-top: 20px; font-size: 15px;">
                <a href="{{ route('client.web.panel.cloud.service', $service->uuid) }}" style="color: #3366cc;">
                    View Service in Client Area
                </a>
            </p>
        </td>
    </tr>
</table>
