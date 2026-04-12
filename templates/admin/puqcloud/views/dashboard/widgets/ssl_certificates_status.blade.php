<div class="row">
    @foreach ($statuses as $status)
        @php
            $icon = '';
            $labelClass = '';
            switch ($status) {
                case 'active':
                    $icon = '<i class="fa fa-shield-alt text-success" style="font-size: 24px;"></i>';
                    $labelClass = 'text-success';
                    break;
                case 'expired':
                    $icon = '<i class="fa fa-clock text-danger" style="font-size: 24px;"></i>';
                    $labelClass = 'text-danger';
                    break;
                case 'pending':
                    $icon = '<i class="fa fa-hourglass-half text-info" style="font-size: 24px;"></i>';
                    $labelClass = 'text-info';
                    break;
                case 'draft':
                    $icon = '<i class="fa fa-file-alt text-secondary" style="font-size: 24px;"></i>';
                    $labelClass = 'text-secondary';
                    break;
                case 'revoked':
                    $icon = '<i class="fa fa-ban text-danger" style="font-size: 24px;"></i>';
                    $labelClass = 'text-danger';
                    break;
                case 'failed':
                    $icon = '<i class="fa fa-times-circle text-danger" style="font-size: 24px;"></i>';
                    $labelClass = 'text-danger';
                    break;
            }
            $count = $certCounts->get($status)->count ?? 0;
        @endphp

        <div class="col-4 text-center mb-2">
            <div>{!! $icon !!}</div>
            <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 10px;">
                {{ ucfirst($status) }}
            </div>
            <div class="results-title {{ $labelClass }}" style="font-size: 16px; font-weight: bold;">
                {{ $count }}
            </div>
        </div>
    @endforeach
</div>

@if($expiringSoon > 0)
    <hr class="mt-1 mb-2">
    <div class="text-center">
        <i class="fa fa-exclamation-triangle text-warning"></i>
        <span class="text-warning" style="font-size: 12px;">
            <b>{{ $expiringSoon }}</b> certificate(s) expiring within 30 days
        </span>
    </div>
@endif
