{{--
    puqVPNCP "awaiting payment" sub-view. Identical logic to the default template:
    service is pending because its invoice hasn't been paid yet — direct link to the
    invoice, background polling reloads the page as soon as payment lands and the
    service activates.
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
            const POLL_INTERVAL = 10;

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
