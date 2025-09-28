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

<div class="monobank-payment-container">
    <!-- Payment Header -->
    <div class="payment-header text-center mb-4">
        <div class="monobank-logo mb-3">
            <img src="{{ asset('modules/Payment/puqMonobank/views/assets/img/monobank-logo.png') }}" 
                 alt="Monobank" 
                 class="img-fluid" 
                 style="max-height: 80px;"
                 onerror="this.style.display='none'">
        </div>
        <h3 class="text-primary">
            <i class="fas fa-credit-card"></i>
            {{ __('Payment.puqMonobank.Pay with Monobank') }}
        </h3>
        <p class="text-muted">{{ __('Payment.puqMonobank.Secure payment powered by Monobank') }}</p>
    </div>

    <!-- Payment Information -->
    <div class="payment-info-card card mb-4" id="payment-info-card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-secondary">{{ __('Payment.puqMonobank.Payment Details') }}</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>{{ __('Payment.puqMonobank.Invoice') }}:</strong></td>
                            <td>#{{ $invoice->number }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ __('Payment.puqMonobank.Amount') }}:</strong></td>
                            <td class="text-primary">
                                <span class="h4">{{ number_format($amount, 2) }} {{ $currency }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ __('Payment.puqMonobank.Client') }}:</strong></td>
                            <td>{{ $invoice->client->name ?? __('Payment.puqMonobank.Guest') }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h5 class="text-secondary">{{ __('Payment.puqMonobank.Payment Methods') }}</h5>
                    <div class="payment-methods">
                        <div class="method-item d-flex align-items-center mb-2">
                            <i class="fas fa-credit-card text-primary mr-2"></i>&nbsp;
                            <span>&nbsp;{{ __('Payment.puqMonobank.Bank cards (Visa, Mastercard)') }}</span>
                        </div>
                        <div class="method-item d-flex align-items-center mb-2">
                            <i class="fas fa-mobile-alt text-success mr-2"></i>&nbsp;
                            <span>&nbsp;{{ __('Payment.puqMonobank.Monobank mobile app') }}</span>
                        </div>
                        <div class="method-item d-flex align-items-center mb-2">
                            <i class="fab fa-apple text-dark mr-2"></i>&nbsp;
                            <span>&nbsp;Apple Pay</span>
                        </div>
                        <div class="method-item d-flex align-items-center mb-2">
                            <i class="fab fa-google text-primary mr-2"></i>&nbsp;
                            <span>&nbsp;Google Pay</span>
                        </div>
                        <div class="method-item d-flex align-items-center">
                            <i class="fas fa-qrcode text-info mr-2"></i>&nbsp;
                            <span>&nbsp;{{ __('Payment.puqMonobank.QR code payment') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($iframe_mode)
        <!-- iFrame Payment Mode -->
        <div class="iframe-payment-container" id="iframe-payment-container">
            <div class="iframe-loading text-center mb-3" id="iframe-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">{{ __('Payment.puqMonobank.Loading payment form...') }}</span>
                </div>
                <p class="mt-2">{{ __('Payment.puqMonobank.Loading secure payment form...') }}</p>
            </div>
            
            <div class="iframe-container" style="display: none;" id="iframe-container">
                <iframe
                    id="monobankPayFrame"
                    title="Monobank Payment"
                    width="100%"
                    height="600"
                    src="{{ $payment_url }}"
                    allow="payment *"
                    style="border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);"
                    onload="iframeLoaded()">
                </iframe>
            </div>
            
            <div class="iframe-fallback text-center mt-3">
                <p class="text-muted">{{ __('Payment.puqMonobank.Having trouble with the payment form?') }}</p>
                <a href="{{ $payment_url }}" target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i>
                    {{ __('Payment.puqMonobank.Open in new window') }}
                </a>
            </div>
        </div>
    @else
        <!-- Redirect Payment Mode -->
        <div class="redirect-payment-container text-center">
            <div class="payment-actions mb-4">
                @if($auto_redirect)
                    <div class="auto-redirect-info mb-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            {{ __('Payment.puqMonobank.You will be redirected to secure payment page in') }}
                            <span id="countdown" class="font-weight-bold">5</span>
                            {{ __('Payment.puqMonobank.seconds') }}...
                        </div>
                    </div>
                @endif
                
                <a href="{{ $payment_url }}" 
                   class="btn btn-primary btn-lg payment-button"
                   id="payment-button"
                   @if($auto_redirect) style="display: none;" @endif>
                    <i class="fas fa-credit-card"></i>
                    {{ __('Payment.puqMonobank.Proceed to Payment') }}
                </a>
                
                @if(!$auto_redirect)
                    <p class="text-muted mt-2">
                        {{ __('Payment.puqMonobank.You will be redirected to Monobank secure payment page') }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    <!-- Security Notice -->
    <div class="security-notice mt-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-shield-alt text-success mr-2"></i>
                    <small class="text-success">
                        {{ __('Payment.puqMonobank.Your payment is protected by Monobank security systems') }}
                    </small>
                </div>
                <small class="text-muted d-block mt-1">
                    {{ __('Payment.puqMonobank.Transaction ID') }}: {{ $monobank_invoice_id }}
                </small>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="help-section mt-4">
        <div class="card border-light">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-question-circle text-info"></i>
                    {{ __('Payment.puqMonobank.Need Help?') }}
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <strong>{{ __('Payment.puqMonobank.Payment Issues') }}:</strong><br>
                            {{ __('Payment.puqMonobank.Contact our support team') }}
                        </small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">
                            <strong>{{ __('Payment.puqMonobank.Monobank Support') }}:</strong><br>
                            <a href="https://monobank.ua/support" target="_blank">https://monobank.ua/support</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.monobank-payment-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.payment-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 20px;
}

.payment-info-card {
    border: 1px solid #dee2e6;
    border-radius: 12px;
}

.payment-methods .method-item {
    padding: 5px 0;
    font-size: 14px;
}

.payment-button {
    min-width: 250px;
    padding: 15px 30px;
    font-size: 18px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.payment-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.iframe-container {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Make iframe take full viewport height in iframe mode by replacing info card */
@media (min-width: 0px) {
    .iframe-full-height {
        height: calc(100vh - 650px);
        min-height: 300px;
    }
}

.security-notice .card {
    border-radius: 8px;
}

.help-section .card {
    border-radius: 8px;
    background-color: #f8f9fa;
}

@media (max-width: 768px) {
    .monobank-payment-container {
        padding: 10px;
    }
    
    iframe {
        height: 500px !important;
    }
    
    .payment-button {
        width: 100%;
        min-width: auto;
    }
}
</style>

<script>
@if($iframe_mode)
// iFrame event handling
function iframeLoaded() {
    // Hide info card and expand iframe to full height
    var info = document.getElementById('payment-info-card');
    if (info) { info.style.display = 'none'; }

    var loading = document.getElementById('iframe-loading');
    if (loading) { loading.style.display = 'none'; }

    var container = document.getElementById('iframe-container');
    if (container) {
        container.style.display = 'block';
        var iframe = document.getElementById('monobankPayFrame');
        if (iframe) {
            iframe.classList.add('iframe-full-height');
        }
    }
}

// Listen for messages from iFrame
function listenFrame(event) {
    try {
        const data = JSON.parse(event.data || "{}");
        
        // Handle close button click
        if (data.message === "close-button") {
            window.location.reload();
        }
        
        // Handle mobile deeplink
        if (data.message === "monopay-link") {
            window.location.href = data.value;
        }
    } catch (e) {
        // Ignore parsing errors for non-Monobank messages
    }
}

window.addEventListener("message", listenFrame, false);
@endif

@if($auto_redirect && !$iframe_mode)
// Auto redirect countdown
let countdown = 5;
const countdownElement = document.getElementById('countdown');
const paymentButton = document.getElementById('payment-button');

const countdownTimer = setInterval(function() {
    countdown--;
    countdownElement.textContent = countdown;
    
    if (countdown <= 0) {
        clearInterval(countdownTimer);
        window.location.href = '{{ $payment_url }}';
    }
}, 1000);

// Show button after countdown starts
setTimeout(function() {
    paymentButton.style.display = 'inline-block';
}, 2000);
@endif

// Payment button click tracking
document.addEventListener('DOMContentLoaded', function() {
    const paymentButton = document.getElementById('payment-button');
    if (paymentButton) {
        paymentButton.addEventListener('click', function() {
            // Track payment button click for analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'payment_started', {
                    'payment_method': 'monobank',
                    'currency': '{{ $currency }}',
                    'value': {{ $amount }}
                });
            }
        });
    }
});
</script> 