<div class="row">
    @foreach ($statuses as $status)
        @php
            $icon = '';
            $labelClass = '';
            switch ($status) {
                case 'active':
                    $icon = '<i class="fa fa-check-circle text-success" style="font-size: 28px;"></i>';
                    $labelClass = 'text-success';
                    break;
                case 'suspended':
                    $icon = '<i class="fa fa-pause-circle text-warning" style="font-size: 28px;"></i>';
                    $labelClass = 'text-warning';
                    break;
                case 'pending':
                    $icon = '<i class="fa fa-hourglass-half text-info" style="font-size: 28px;"></i>';
                    $labelClass = 'text-info';
                    break;
                case 'terminated':
                    $icon = '<i class="fa fa-times-circle text-danger" style="font-size: 28px;"></i>';
                    $labelClass = 'text-danger';
                    break;
                case 'cancelled':
                    $icon = '<i class="fa fa-ban text-secondary" style="font-size: 28px;"></i>';
                    $labelClass = 'text-secondary';
                    break;
            }
            $count = $serviceCounts->get($status)->count ?? 0;
        @endphp

        <div class="col-6 col-md-4 text-center mb-2">
            <div>{!! $icon !!}</div>
            <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">
                {{ ucfirst($status) }}
            </div>
            <div class="results-title {{ $labelClass }}" style="font-size: 18px; font-weight: bold;">
                {{ $count }}
            </div>
        </div>
    @endforeach

    <div class="col-6 col-md-4 text-center mb-2">
        <div><i class="fa fa-exclamation-triangle text-danger" style="font-size: 28px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">
            Termination Requests
        </div>
        <div class="results-title text-danger" style="font-size: 18px; font-weight: bold;">
            {{ $terminationRequests }}
        </div>
    </div>
</div>

<hr class="mt-1 mb-2">
<div class="text-center">
    <span class="text-muted" style="font-size: 12px;">Total: <b>{{ $totalServices }}</b></span>
    @if($idleCount > 0)
        <span class="text-muted ms-3" style="font-size: 12px;">Idle: <b class="text-warning">{{ $idleCount }}</b></span>
    @endif
</div>
