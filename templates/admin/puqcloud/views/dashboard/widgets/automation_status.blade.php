<div class="row">
    <div class="col-6 text-center">
        <div>{!! $icon !!}</div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 14px; margin-top: 8px;">Cron</div>
        <div class="results-title" style="font-size: 12px;">{{ $statusText }}</div>
        <div class="mt-2" style="font-size: 12px;">{{ $lastRun ? $lastRun->format('Y-m-d H:i:s') : 'Never run' }}</div>
    </div>

    <div class="col-6 text-center">
        <div>{!! $queueStatusIcon !!}</div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 14px; margin-top: 8px;">Horizon</div>
        <div class="results-title" style="font-size: 12px;">{{ $queueStatusText }}</div>
        <div class="mt-2" style="font-size: 12px;">Total Queues: {{ count($queues) }}</div>
    </div>
</div>
