@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        .fade-in-up {
            opacity: 0;
            transform: translateY(40px);
            animation: fadeInUp 1s ease-out forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-icon {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
@endsection

@section('content')
    @parent
    <div class="container py-5">
        <div class="main-card mb-3 card shadow-lg border-0">
            <div class="card-body">
                <div class="row" id="payment_status">
                    <div class="d-flex justify-content-center align-items-center w-100" style="height: 400px;">
                        <div class="text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        const puqTrans = {
            processing: "{{ __('Payment.puqPrzelewy24.Payment Processing') }}",
            thanks: "{{ __('Payment.puqPrzelewy24.Thank you for your payment! Your transaction is processing') }}",
            goToInvoice: "{{ __('Payment.puqPrzelewy24.Go to Invoice') }}",
            failed: "{{ __('Payment.puqPrzelewy24.Payment Failed') }}",
            unknown: "{{ __('Payment.puqPrzelewy24.Unknown error') }}",
            goToInvoices: "{{ __('Payment.puqPrzelewy24.Go to Invoices') }}"
        };

        $(document).ready(function () {
            blockUI('payment_status');

            PUQajax('{{ route('client.api.module.post', ['type' => 'Payment', 'name' => 'puqPrzelewy24', 'method' => 'apiReturnUrlPost', 'uuid' => $payment_gateway_uuid]) }}',
                {sessionId: '{{ $sessionId }}'}, 5000, null, 'POST', null)
                .then(function (response) {
                    unblockUI('payment_status');

                    const html = `
                        <div class="text-center fade-in-up px-4">
                            <i class="fa fa-check-circle text-success mb-4 pulse-icon" style="font-size: 120px;"></i>
                            <h1 class="text-success fw-bold mb-3">${puqTrans.processing}</h1>
                            <p class="lead mb-4 text-secondary">${puqTrans.thanks}</p>
                            <a href="${response.data.url}" class="btn btn-lg btn-success px-5 py-3 shadow">
                                ${puqTrans.goToInvoice}
                            </a>
                        </div>
                    `;
                    $('#payment_status').html(html);
                })
                .catch(function (xhr) {
                    unblockUI('payment_status');

                    let errors = [puqTrans.unknown];
                    if (xhr && xhr.responseJSON && Array.isArray(xhr.responseJSON.errors)) {
                        errors = xhr.responseJSON.errors;
                    }

                    const errorList = errors.map(e => `<li>${e}</li>`).join('');
                    const html = `
                        <div class="text-center fade-in-up px-4">
                            <i class="fa fa-times-circle text-danger mb-4 pulse-icon" style="font-size: 120px;"></i>
                            <h1 class="text-danger fw-bold mb-3">${puqTrans.failed}</h1>
                            <ul class="mt-3 text-danger list-unstyled fs-5">
                                ${errorList}
                            </ul>
                            <a href="{{ route('client.web.panel.client.invoices') }}" class="btn btn-lg btn-outline-danger px-5 py-3 mt-4 shadow">
                                ${puqTrans.goToInvoices}
                            </a>
                            <div class="mt-4 text-start small text-muted">
                                <strong>Session ID:</strong> {{ $sessionId ?? 'N/A' }}<br>
                                <strong>UUID:</strong> {{ $payment_gateway_uuid ?? 'N/A' }}<br>
                                <strong>Gateway:</strong> Przelewy24<br>
                            </div>
                        </div>
                    `;
                    $('#payment_status').html(html);
                });
        });
    </script>
@endsection

