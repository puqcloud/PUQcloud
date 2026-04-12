<div class="row">
    <div class="col-4 text-center mb-2">
        <div><i class="fa fa-users text-primary" style="font-size: 28px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">Total Clients</div>
        <div class="results-title text-primary" style="font-size: 22px; font-weight: bold;">{{ $totalClients }}</div>
    </div>
    <div class="col-4 text-center mb-2">
        <div><i class="fa fa-user-plus text-success" style="font-size: 28px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">This Month</div>
        <div class="results-title text-success" style="font-size: 22px; font-weight: bold;">{{ $newThisMonth }}</div>
    </div>
    <div class="col-4 text-center mb-2">
        <div><i class="fa fa-user-clock text-info" style="font-size: 28px;"></i></div>
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">Today</div>
        <div class="results-title text-info" style="font-size: 22px; font-weight: bold;">{{ $newToday }}</div>
    </div>
</div>

<hr class="mt-1 mb-2">

<div class="row">
    <div class="col-6 text-center">
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">With Active Services</div>
        <div class="results-title text-success" style="font-size: 16px; font-weight: bold;">{{ $withActiveServices }}</div>
    </div>
    <div class="col-6 text-center">
        <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 11px;">With Unpaid Invoices</div>
        <div class="results-title text-warning" style="font-size: 16px; font-weight: bold;">{{ $withUnpaidInvoices }}</div>
    </div>
</div>
