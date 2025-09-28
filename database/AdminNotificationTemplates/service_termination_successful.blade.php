@php
    $client = $service->client;
    $product= $service->product;
    $product_group = $product->productGroups()->first();
    $price_total = $service->getPriceTotal();
    $price_detailed = $service->getPriceDetailed();
    $currency = $service->price->currency;
    $period = $service->price->period;
@endphp
<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7; padding: 30px;">
    <tr>
        <td align="center">
            <table width="90%" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border: 1px solid #dddddd;">
                <tr>
                    <td style="padding: 30px; font-family: Arial, sans-serif; font-size: 14px; color: #333;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td colspan="2" style="font-size: 16px; font-weight: bold; padding-bottom: 10px;">
                                    Client
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-bottom: 20px;">
                                    <a href="{{ route('admin.web.client.tab', [$client->uuid, 'summary']) }}" target="_blank" style="color: #007bff; text-decoration: none;">
                                        {{ $client->company_name ? $client->company_name . ' (' . $client->firstname . ' ' . $client->lastname . ')' : $client->firstname . ' ' . $client->lastname }}
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2" style="font-size: 16px; font-weight: bold; padding-bottom: 10px;">
                                    Service
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-bottom: 20px;">
                                    <a href="{{ route('admin.web.client.tab', ['uuid' => $client->uuid, 'tab' => 'services', 'edit' => $service->uuid]) }}" target="_blank" style="color: #007bff; text-decoration: none;">
                                        {{ $product->name ?? $product->key }} {{ $service->admin_label }}
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2" style="font-size: 16px; font-weight: bold; padding-bottom: 10px;">
                                    Order Summary
                                </td>
                            </tr>
                            <tr>
                                <td width="180" style="padding: 5px 0;"><strong>Product Group:</strong></td>
                                <td>{{ $product_group->name ?? $product_group->key }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;"><strong>Product:</strong></td>
                                <td>{{ $product->name ?? $product->key }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;"><strong>Order Date:</strong></td>
                                <td>{{ $service->order_date }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;"><strong>Billing Period:</strong></td>
                                <td>{{ ucfirst($period) }}</td>
                            </tr>

                            <tr><td colspan="2" style="padding-top: 25px;"></td></tr>

                            <tr>
                                <td colspan="2" style="font-size: 16px; font-weight: bold; padding-bottom: 10px;">
                                    Price Breakdown
                                </td>
                            </tr>

                            @foreach (['setup', 'base', 'idle', 'switch_down', 'switch_up', 'uninstall'] as $key)
                                @if (!empty($price_total[$key]))
                                    <tr>
                                        <td style="padding: 5px 0;"><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong></td>
                                        <td>{{ $price_total[$key] }} {{ $currency->code }}</td>
                                    </tr>
                                @endif
                            @endforeach

                            @if (!empty($price_detailed['options']))
                                <tr><td colspan="2" style="padding-top: 25px;"></td></tr>
                                <tr>
                                    <td colspan="2" style="font-size: 16px; font-weight: bold; padding-bottom: 10px;">
                                        Ordered Options
                                    </td>
                                </tr>
                                @foreach ($price_detailed['options'] as $option)
                                    <tr>
                                        <td colspan="2" style="padding: 5px 0;">
                                            <strong>{{ $option['product_option_group_key'] }}:</strong>
                                            {{ $option['product_option_key'] }} –
                                            {{ $option['price']['base'] ?? '0' }} {{ $price_detailed['currency']['code'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
