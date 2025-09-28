@if($data['status'] === 'success')
    <div class="card border-success shadow-lg p-5 text-center">
        <h2 class="mb-4 text-success">{{ __('Payment.puqPrzelewy24.Ready to Pay with Przelewy24') }}</h2>
        <p class="mb-4 fs-5">{{ __('Payment.puqPrzelewy24.Click the button below to complete your payment securely via Przelewy24.') }}</p>

        <a href="{{ $data['data']['url'] }}" class="btn btn-primary btn-lg px-5 py-3 fs-5">
            <i class="fas fa-money-check-alt me-2"></i> {{ __('Payment.puqPrzelewy24.Pay Now with Przelewy24') }}
        </a>

        <p class="mt-4 text-muted fs-6">
            {{ __('Payment.puqPrzelewy24.Transaction Token') }}:<br><strong>{{ $data['data']['token'] }}</strong><br>
            {{ __('Payment.puqPrzelewy24.Description') }}: <strong>{{ $data['data']['description'] }}</strong>
        </p>
    </div>
@else
    <div class="alert alert-danger p-5 text-center fs-5 shadow-sm">
        <i class="fa fa-exclamation-triangle me-2"></i> {{ __('Payment.puqPrzelewy24.Something went wrong. Please try again later.') }}
    </div>
@endif
