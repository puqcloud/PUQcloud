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

<div class="monobank-error-container">
    <div class="error-card card border-danger">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">
                <i class="fas fa-exclamation-triangle"></i>
                Payment Error
            </h4>
        </div>
        
        <div class="card-body text-center">
            <div class="error-icon mb-4">
                <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
            </div>
            
            <h5 class="text-danger mb-3">
                Unable to process payment
            </h5>
            
            <p class="text-muted mb-4">
                {{ $error_message ?? 'An error occurred while processing your payment. Please try again.' }}
            </p>
            
            @if(!empty($error_code))
                <div class="alert alert-light">
                    <small class="text-muted">
                        <strong>Error Code:</strong> {{ $error_code }}
                    </small>
                </div>
            @endif

            @if(!empty($error_details))
                <div class="alert alert-warning">
                    <small class="text-muted">
                        <strong>Debug Info:</strong><br>
                        <strong>File:</strong> {{ $error_details['file'] ?? 'N/A' }}<br>
                        <strong>Line:</strong> {{ $error_details['line'] ?? 'N/A' }}<br>
                        <details>
                            <summary>Stack Trace</summary>
                            <pre style="font-size: 10px;">{{ $error_details['trace'] ?? 'N/A' }}</pre>
                        </details>
                    </small>
                </div>
            @endif
            
            <div class="action-buttons">
                <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i>
                    Try Again
                </button>
                
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary ml-2">
                    <i class="fas fa-arrow-left"></i>
                    Go Back
                </a>
            </div>
        </div>
    </div>
    
    <div class="help-info mt-4">
        <div class="card border-info">
            <div class="card-body">
                <h6 class="text-info">
                    <i class="fas fa-info-circle"></i>
                    Need Help?
                </h6>
                <p class="text-muted mb-0">
                    If the problem persists, please contact our support team or try using a different payment method.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.monobank-error-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.error-card {
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.action-buttons {
    margin-top: 20px;
}

.help-info .card {
    border-radius: 8px;
}
</style> 