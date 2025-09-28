<style>
    .white-blur-edges {
        position: relative;
        background: #f8f9fa;
        border-radius: 10px;
        overflow: hidden;
        z-index: 1;
    }

    .white-blur-edges::before {
        content: '';
        position: absolute;
        top: -20px;
        left: -20px;
        right: -20px;
        bottom: -20px;
        background: white;
        filter: blur(20px);
        z-index: -1;
    }
</style>

<div class="widget-content d-none d-md-inline mb-0">
    <div class="widget-content-wrapper white-blur-edges m-0 p-2 pe-4 ps-4" style="white-space: nowrap; padding: 1rem;">

        <div class="widget-content-right ms-0 me-3 text-end" style="display: inline-block;">
            @php
                $balanceValue = $client->balance->balance;
                $balance = number_format_custom($balanceValue, 4, $client->currency->format);

                $lastDot = strrpos($balance, '.');
                $lastComma = strrpos($balance, ',');

                if ($lastDot === false && $lastComma === false) {
                    $int = $balance;
                    $dec = '';
                    $separator = '';
                } else {
                    if ($lastDot > $lastComma) {
                        $separator = '.';
                        $pos = $lastDot;
                    } else {
                        $separator = ',';
                        $pos = $lastComma;
                    }
                    $int = substr($balance, 0, $pos);
                    $dec = substr($balance, $pos + 1);
                }

                $balanceColor = $balanceValue >= 0 ? 'text-success' : 'text-danger';
            @endphp

            <div class="widget-numbers {{ $balanceColor }}">
                <small class="opacity-5">{{ $client->currency->prefix }}</small>
                <span style="white-space: nowrap;">
                    {!! $int !!}<span style="font-size: 0.7em;">{{ $separator }}{!! $dec !!}</span>
                </span>
                <small class="opacity-5">{{ $client->currency->suffix }}</small>
            </div>
        </div>

        <div class="widget-content-left ms-0 me-3" style="display: inline-block; white-space: nowrap;">
            <div class="widget-heading">{{ __('main.Credit') }}</div>
            <div class="widget-subheading">
                {{ $client->currency->prefix }}
                {{ number_format_custom($client->credit_limit, 4, $client->currency->format) }}
                {{ $client->currency->suffix }}
            </div>
        </div>

        <div class="widget-content-left" style="display: inline-block;">
            <a href="{{ route('client.web.panel.client.add_funds') }}" class="btn-shadow btn-outline-2x btn btn-outline-info" style="white-space: nowrap;">
                <i class="fa fa-money-bill btn-icon-wrapper"></i>
                {{ __('main.Add Funds') }}
            </a>
        </div>

    </div>
</div>
