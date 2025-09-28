{{--
PUQcloud - Free Cloud Billing System
Monobank Payment Gateway Module - Client Area Payment Form

Copyright (C) 2025 PUQ sp. z o.o.
Licensed under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Dmytro Kravchenko <dmytro@kravchenko.im>
Website: https://puqcloud.com
E-mail: support@puqcloud.com

Do not remove this header.
--}}
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
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .monobank-logo {
            max-height: 60px;
            margin-bottom: 20px;
        }

        .error-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
        }

        .error-code {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
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
                        <div class="text-primary" role="status">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">{{ __('Payment.puqMonobank.Loading...') }}</span>
                            </div>
                            <p class="mt-3">{{ __('Payment.puqMonobank.Checking payment status...') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            blockUI('payment_status');

            // Prepare data for API call
            const apiData = {
                invoiceId: '{{ is_string($invoiceId) ? $invoiceId : "" }}',
                status: '{{ is_string($status) ? $status : "" }}',
                errCode: '{{ is_string($errCode) ? $errCode : "" }}',
                invoiceUuid: '{{ is_string($invoiceUuid) ? $invoiceUuid : "" }}'
            };

            PUQajax('{{ route('client.api.module.post', ['type' => 'Payment', 'name' => 'puqMonobank', 'method' => 'apiReturnUrlPost', 'uuid' => is_string($payment_gateway_uuid) ? $payment_gateway_uuid : '']) }}',
                apiData, 10000, null, 'POST', null)
                .then(function (response) {
                    unblockUI('payment_status');

                    // Render strictly by status
                    if (response && response.status === 'success') {
                        // Success
                        let html = `
                            <div class="text-center fade-in-up px-4">
                                <div class="mb-4">
                                    <img src="{{ asset('modules/Payment/puqMonobank/views/assets/img/monobank-logo.png') }}" 
                                         alt="Monobank" 
                                         class="monobank-logo"
                                         onerror="this.style.display='none'">
                                </div>
                                <i class="fa fa-check-circle text-success mb-4 pulse-icon" style="font-size: 120px;"></i>
                                <h1 class="text-success fw-bold mb-3">{{ __('Payment.puqMonobank.Payment Successful') }}</h1>
                                <p class="lead mb-4 text-secondary">{{ __('Payment.puqMonobank.Thank you for your payment! Your transaction has been successfully completed') }}</p>
                                <a href="${(response.data && response.data.url) ? response.data.url : '#'}" class="btn btn-lg btn-success px-5 py-3 shadow">
                                    <i class="fas fa-file-invoice me-2"></i>
                                    {{ __('Payment.puqMonobank.Go to Invoice') }}
                                </a>
                                <div class="mt-4 text-muted small">
                                    <div><strong>Gateway:</strong> Monobank</div>
                                    <div><strong>UUID:</strong> {{ is_string($payment_gateway_uuid) ? $payment_gateway_uuid : 'N/A' }}</div>
                                    <div><strong>Timestamp:</strong> ${new Date().toLocaleString()}</div>
                                </div>
                            </div>
                        `;
                        $('#payment_status').html(html);
                        return;
                    }

                    if (response && response.status === 'pending') {
                        // Pending
                        let html = `
                            <div class="text-center fade-in-up px-4">
                                <div class="mb-4">
                                    <img src="{{ asset('modules/Payment/puqMonobank/views/assets/img/monobank-logo.png') }}" 
                                         alt="Monobank" 
                                         class="monobank-logo"
                                         onerror="this.style.display='none'">
                                </div>
                                <i class="fa fa-clock text-warning mb-4 pulse-icon" style="font-size: 120px;"></i>
                                <h1 class="text-warning fw-bold mb-3">Платіж обробляється</h1>
                                <p class="lead mb-4 text-secondary">Статус платежу уточнюється. Якщо оплата пройшла успішно, вона буде зарахована протягом кількох хвилин.</p>
                                <a href="${(response.data && response.data.url) ? response.data.url : '#'}" class="btn btn-lg btn-primary px-5 py-3 shadow">
                                    <i class="fas a-file-invoice me-2"></i>
                                    Перейти до рахунку
                                </a>
                                <div class="mt-4 text-muted small">
                                    <div><strong>Gateway:</strong> Monobank</div>
                                    <div><strong>Статус:</strong> В обробці</div>
                                </div>
                            </div>
                        `;
                        $('#payment_status').html(html);
                        return;
                    }

                    // Any other status is treated as error
                    const message = (response && response.message) ? response.message : 'Unknown error';
                    const html = `
                        <div class="text-center fade-in-up px-4">
                            <div class="mb-4">
                                <img src="{{ asset('modules/Payment/puqMonobank/views/assets/img/monobank-logo.png') }}" 
                                     alt="Monobank" 
                                     class="monobank-logo"
                                     onerror="this.style.display='none'">
                            </div>
                            <i class="fa fa-times-circle text-danger mb-4 pulse-icon" style="font-size: 120px;"></i>
                            <h1 class="text-danger fw-bold mb-3">{{ __('Payment.puqMonobank.Payment Failed') }}</h1>
                            <ul class="mt-3 text-danger list-unstyled fs-5">
                                <li>${message}</li>
                            </ul>
                            <div class="mt-4">
                                <a href="{{ route('client.web.panel.client.invoices') }}" class="btn btn-lg btn-outline-danger px-5 py-3 me-3 shadow">
                                    <i class="fas fa-list me-2"></i>
                                    {{ __('Payment.puqMonobank.Go to Invoices') }}
                                </a>
                                <a href="{{ route('client.web.panel.client.invoice.details', ['uuid' => is_string($invoiceUuid) ? $invoiceUuid : '']) }}" class="btn btn-lg btn-outline-primary px-5 py-3 shadow">
                                    <i class="fas fa-redo me-2"></i>
                                    {{ __('Payment.puqMonobank.Try Again') }}
                                </a>
                            </div>
                            <div class="mt-4 text-start small text-muted">
                                <div><strong>Gateway:</strong> Monobank</div>
                                <div><strong>UUID:</strong> {{ is_string($payment_gateway_uuid) ? $payment_gateway_uuid : 'N/A' }}</div>
                                <div><strong>Timestamp:</strong> ${new Date().toLocaleString()}</div>
                            </div>
                        </div>
                    `;
                    $('#payment_status').html(html);
                })
                .catch(function (errors) {
                    unblockUI('payment_status');

                    if (!errors || !Array.isArray(errors)) {
                        errors = ['Unknown error'];
                    }

                    const errorList = errors.map(e => `<li>${e}</li>`).join('');
                    const errCode = '{{ is_string($errCode) ? $errCode : "" }}';
                    const invoiceId = '{{ is_string($invoiceId) ? $invoiceId : "" }}';

                    let errorDetails = '';
                    if (errCode) {
                        errorDetails = `
                            <div class="error-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>{{ __('Payment.puqMonobank.Error Code') }}:</strong>
                                        <span class="error-code">${errCode}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{ __('Payment.puqMonobank.Invoice ID') }}:</strong>
                                        <span class="error-code">${invoiceId}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    const html = `
                        <div class="text-center fade-in-up px-4">
                            <div class="mb-4">
                                <img src="{{ asset('modules/Payment/puqMonobank/views/assets/img/monobank-logo.png') }}" 
                                     alt="Monobank" 
                                     class="monobank-logo"
                                     onerror="this.style.display='none'">
                            </div>
                            <i class="fa fa-times-circle text-danger mb-4 pulse-icon" style="font-size: 120px;"></i>
                            <h1 class="text-danger fw-bold mb-3">{{ __('Payment.puqMonobank.Payment Failed') }}</h1>
                            <ul class="mt-3 text-danger list-unstyled fs-5">
                                ${errorList}
                            </ul>
                            <div class="mt-4">
                                <a href="{{ route('client.web.panel.client.invoices') }}" class="btn btn-lg btn-outline-danger px-5 py-3 me-3 shadow">
                                    <i class="fas fa-list me-2"></i>
                                    {{ __('Payment.puqMonobank.Go to Invoices') }}
                                </a>
                                <a href="{{ route('client.web.panel.client.invoice.details', ['uuid' => is_string($invoiceUuid) ? $invoiceUuid : '']) }}" class="btn btn-lg btn-outline-primary px-5 py-3 shadow">
                                    <i class="fas fa-redo me-2"></i>
                                    {{ __('Payment.puqMonobank.Try Again') }}
                                </a>
                            </div>
                            ${errorDetails}
                            <div class="mt-4 text-start small text-muted">
                                <div><strong>Gateway:</strong> Monobank</div>
                                <div><strong>UUID:</strong> {{ is_string($payment_gateway_uuid) ? $payment_gateway_uuid : 'N/A' }}</div>
                                <div><strong>Timestamp:</strong> ${new Date().toLocaleString()}</div>
                            </div>
                        </div>
                    `;
                    $('#payment_status').html(html);
                });
        });
    </script>
@endsection 