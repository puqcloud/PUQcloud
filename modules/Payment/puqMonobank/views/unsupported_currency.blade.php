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

<div class="monobank-currency-error-container">
    <div class="currency-error-card card border-warning">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">
                <i class="fas fa-money-bill-alt"></i>
                {{ __('Payment.puqMonobank.Currency Not Supported') }}
            </h4>
        </div>
        
        <div class="card-body text-center">
            <div class="currency-icon mb-4">
                <i class="fas fa-ban text-warning" style="font-size: 4rem;"></i>
            </div>
            
            <h5 class="text-warning mb-3">
                {{ __('Payment.puqMonobank.Sorry, this currency is not supported') }}
            </h5>
            
            <p class="text-muted mb-4">
                {{ __('Payment.puqMonobank.Monobank currently only supports UAH (Ukrainian Hryvnia) payments.') }}
                {{ __('Payment.puqMonobank.Your invoice currency is') }}: <strong>{{ $currency }}</strong>
            </p>
            
            <div class="supported-currency-info alert alert-info">
                <h6 class="mb-2">{{ __('Payment.puqMonobank.Supported Currency') }}:</h6>
                <div class="currency-item">
                    <i class="fas fa-check text-success mr-2"></i>
                    <strong>UAH</strong> - {{ __('Payment.puqMonobank.Ukrainian Hryvnia') }}
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="{{ url()->previous() }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    {{ __('Payment.puqMonobank.Choose Different Payment Method') }}
                </a>
            </div>
        </div>
    </div>
    
    <div class="help-info mt-4">
        <div class="card border-info">
            <div class="card-body">
                <h6 class="text-info">
                    <i class="fas fa-lightbulb"></i>
                    {{ __('Payment.puqMonobank.Alternative Options') }}
                </h6>
                <ul class="text-muted mb-0">
                    <li>{{ __('Payment.puqMonobank.Contact support to change invoice currency') }}</li>
                    <li>{{ __('Payment.puqMonobank.Use a different payment method that supports your currency') }}</li>
                    <li>{{ __('Payment.puqMonobank.Consider currency conversion services') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.monobank-currency-error-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.currency-error-card {
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.currency-item {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.action-buttons {
    margin-top: 20px;
}

.help-info .card {
    border-radius: 8px;
}
</style> 