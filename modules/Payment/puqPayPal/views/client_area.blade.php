@if($data['status'] === 'success')
    <div class="card border-success shadow-lg p-5 text-center">
        <h2 class="mb-4 text-success">{{ __('Payment.puqPayPal.Ready to Pay with PayPal') }}</h2>
        <p class="mb-4 fs-5">{{ __('Payment.puqPayPal.Click the button below to complete your payment securely via PayPal.') }}</p>

        <a href="{{ $data['approval_url'] }}" class="btn btn-primary btn-lg px-5 py-3 fs-5">
            <i class="fab fa-paypal me-2"></i> {{ __('Payment.puqPayPal.Pay Now with PayPal') }}
        </a>

        <p class="mt-4 text-muted fs-6">
            {{ __('Payment.puqPayPal.Order ID') }}: <strong>{{ $data['order_id'] }}</strong>
        </p>
    </div>
@else
    <div class="alert alert-danger p-5 text-center fs-5 shadow-sm">
        <i class="fa fa-exclamation-triangle me-2"></i> {{ __('Payment.puqPayPal.Something went wrong. Please try again later.') }}
    </div>
@endif
