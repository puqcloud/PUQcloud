@if($data['status'] === 'success')
    <div class="card border-success shadow-lg p-5 text-center">
        <h2 class="mb-4 text-success">{{ __('Payment.puqStripe.Ready to Pay with Stripe') }}</h2>
        <p class="mb-4 fs-5">{{ __('Payment.puqStripe.Click the button below to complete your payment securely via Stripe.') }}</p>

        <a href="{{ $data['data']['url'] }}" class="btn btn-primary btn-lg px-5 py-3 fs-5">
            <i class="fab fa-cc-stripe me-2"></i> {{ __('Payment.puqStripe.Pay Now with Stripe') }}
        </a>

        <p class="mt-4 text-muted fs-6">
            {{ __('Payment.puqStripe.Session ID') }}: <strong>{{ $data['data']['id'] }}</strong><br>
            {{ __('Payment.puqStripe.Invoice ID') }}: <strong>{{ $data['data']['metadata']['invoice_id'] ?? '—' }}</strong>
        </p>
    </div>
@else
    <div class="alert alert-danger p-5 text-center fs-5 shadow-sm">
        <i class="fa fa-exclamation-triangle me-2"></i> {{ __('Payment.puqStripe.Something went wrong. Please try again later.') }}
    </div>
@endif
