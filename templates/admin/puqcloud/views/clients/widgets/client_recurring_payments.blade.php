@if(empty($totals['currency']))
    <div class="text-center text-muted py-3">
        <i class="fa fa-sync-alt" style="font-size: 24px;"></i>
        <p class="mt-2 mb-0" style="font-size: 12px;">{{ __('main.No recurring services') }}</p>
    </div>
@else
    <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center py-1">
            <span class="text-muted" style="font-size: 12px;">{{ __('main.Hourly') }}</span>
            <span style="font-size: 13px;"><b>{{ number_format($totals['hourly'], 4) }}</b> <small class="text-muted">{{ $totals['currency']['code'] ?? '' }}</small></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center py-1">
            <span class="text-muted" style="font-size: 12px;">{{ __('main.Daily') }}</span>
            <span style="font-size: 13px;"><b>{{ number_format($totals['daily'], 2) }}</b> <small class="text-muted">{{ $totals['currency']['code'] ?? '' }}</small></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center py-1">
            <span class="text-muted" style="font-size: 12px;">{{ __('main.Weekly') }}</span>
            <span style="font-size: 13px;"><b>{{ number_format($totals['weekly'], 2) }}</b> <small class="text-muted">{{ $totals['currency']['code'] ?? '' }}</small></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 bg-light">
            <span style="font-size: 13px;"><b>{{ __('main.Monthly') }}</b></span>
            <span style="font-size: 15px;"><b class="text-primary">{{ number_format($totals['monthly'], 2) }}</b> <small class="text-muted">{{ $totals['currency']['code'] ?? '' }}</small></span>
        </li>
        <li class="list-group-item d-flex justify-content-between align-items-center py-1 bg-light">
            <span style="font-size: 13px;"><b>{{ __('main.Yearly') }}</b></span>
            <span style="font-size: 15px;"><b class="text-success">{{ number_format($totals['yearly'], 2) }}</b> <small class="text-muted">{{ $totals['currency']['code'] ?? '' }}</small></span>
        </li>
    </ul>

    <hr class="mt-1 mb-1">
    <div class="text-center">
        <span class="text-muted" style="font-size: 11px;">
            {{ __('main.Recommended funds') }}: <b class="text-primary">{{ number_format($totals['recommended_funds'], 2) }} {{ $totals['currency']['code'] ?? '' }}</b>
        </span>
    </div>
@endif
