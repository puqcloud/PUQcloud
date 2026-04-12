<div class="row">
    <div class="col-4 text-center mb-2">
        <div><i class="fa fa-file-invoice-dollar text-danger" style="font-size: 28px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">Unpaid</div>
        <div class="results-title text-danger" style="font-size: 16px; font-weight: bold;">{{ $unpaidCount }}</div>
        <div style="font-size: 11px;" class="text-muted">{{ number_format($unpaidTotal, 2) }}</div>
    </div>
    <div class="col-4 text-center mb-2">
        <div><i class="fa fa-check-double text-success" style="font-size: 28px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">Paid (Month)</div>
        <div class="results-title text-success" style="font-size: 16px; font-weight: bold;">{{ $paidMonthCount }}</div>
        <div style="font-size: 11px;" class="text-muted">{{ number_format($paidMonthTotal, 2) }}</div>
    </div>
    <div class="col-4 text-center mb-2">
        <div><i class="fa fa-file-alt text-info" style="font-size: 28px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">Draft</div>
        <div class="results-title text-info" style="font-size: 16px; font-weight: bold;">{{ $draftCount }}</div>
        <div style="font-size: 11px;" class="text-muted">{{ number_format($draftTotal, 2) }}</div>
    </div>
</div>

<hr class="mt-1 mb-2">

<div class="row">
    <div class="col-6 text-center">
        <div><i class="fa fa-calendar-check text-primary" style="font-size: 24px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">Due Today</div>
        <div class="results-title text-primary" style="font-size: 16px; font-weight: bold;">{{ $dueTodayCount }}</div>
    </div>
    <div class="col-6 text-center">
        <div><i class="fa fa-exclamation-circle text-danger" style="font-size: 24px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">Overdue</div>
        <div class="results-title text-danger" style="font-size: 16px; font-weight: bold;">{{ $overdueCount }}</div>
    </div>
</div>
