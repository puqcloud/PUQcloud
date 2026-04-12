{{--
    Generic "awaiting payment" sub-view for the default manage template.

    Rendered by general.blade.php when getManageViewState() reports phase = awaiting_payment.
    Service is pending because its invoice hasn't been paid yet — show a clear message
    with a direct link to the invoice payment page, not the generic deploying spinner.

    Polling runs in background (via the core state machine + controller_GetStatus). Once
    the client pays and the lifecycle job flips status → active, the JS reloads the page
    and the normal active content takes over automatically.
--}}
@php
    $invoiceUrl = $manageState['unpaid_invoice_url'] ?? null;
@endphp

<div class="container px-0">
    <div class="card mb-2 shadow-sm border-0" style="min-height: 280px;">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <i class="fas fa-file-invoice-dollar text-warning" style="font-size: 5rem;"></i>
            </div>

            <h3 class="fw-bold text-warning mb-2">
                {{ __('main.Awaiting Payment') }}
            </h3>

            <p class="text-muted mb-4">
                {{ __('main.Your service will be deployed automatically after the invoice is paid.') }}
            </p>

            @if($invoiceUrl)
                <a href="{{ $invoiceUrl }}" class="btn btn-warning btn-lg px-4 shadow-sm">
                    <i class="fas fa-credit-card me-2"></i>
                    {{ __('main.Pay the Invoice') }}
                </a>
            @endif

            <div class="mt-4 small text-muted">
                <i class="fas fa-sync-alt fa-spin me-1"></i>
                {{ __('main.This page will refresh automatically once payment is received') }}
            </div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            // Lightweight polling — same GetStatus endpoint the deploying view uses. When
            // the phase transitions out of awaiting_payment (client paid → service goes
            // active, or admin cancelled → terminated) the page reloads.
            const POLL_INTERVAL = 10; // seconds; longer than deploy poll, client needs time to pay

            function checkStatus() {
                PUQajax(
                    "{{ route('client.api.cloud.service.manage.get', ['uuid' => $service->uuid, 'method' => 'GetStatus']) }}",
                    {}, 50, null, 'GET'
                ).then(function (response) {
                    var phase = response && response.data && response.data.phase;
                    if (phase && phase !== 'awaiting_payment') {
                        location.reload();
                    }
                });
            }

            setInterval(checkStatus, POLL_INTERVAL * 1000);
        });
    </script>
@endsection
