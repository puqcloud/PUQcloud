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
<div class="row" id="module">
    <div class="col-12">
        <div id="test_connection_data"></div>
    </div>

    {{-- Production Token --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="production_token">
            {{ __('Payment.puqMonobank.Production Token') }}
            <span class="text-danger">*</span>
            @if($module_data['production_token_exists'] ?? false)
                <span class="badge bg-success ms-2">{{ __('Payment.puqMonobank.Configured') }}</span>
            @endif
        </label>
        <textarea name="production_token" id="production_token" class="form-control" rows="3" 
                  placeholder="@if($module_data['production_token_exists'] ?? false){{ __('Payment.puqMonobank.Leave empty to keep current token') }}@else{{ __('Payment.puqMonobank.Enter your production API token from Monobank merchant panel') }}@endif">{{ $module_data['production_token'] ?? '' }}</textarea>
        <small class="form-text text-muted">
            @if($module_data['production_token_exists'] ?? false)
                {{ __('Payment.puqMonobank.Token is currently configured') }}<br>
            @endif
            {{ __('Payment.puqMonobank.Get your token from') }} 
            <a href="https://web.monobank.ua/" target="_blank">https://web.monobank.ua/</a>
        </small>
    </div>

    {{-- Sandbox Mode --}}
    <div class="col-12 mb-3">
        <div class="form-check form-switch">
            <input type="hidden" name="sandbox_mode" value="">
            <input class="form-check-input" type="checkbox" id="sandbox_mode" name="sandbox_mode" value="yes"
                   @if($module_data['sandbox_mode'] ?? false) checked @endif onchange="toggleSandboxMode()">
            <label class="form-check-label" for="sandbox_mode">
                {{ __('Payment.puqMonobank.Enable Sandbox Mode') }}
            </label>
        </div>
        <small class="form-text text-muted">
            {{ __('Payment.puqMonobank.Use for testing. Get test token from') }}
            <a href="https://api.monobank.ua/" target="_blank">https://api.monobank.ua/</a>
        </small>
    </div>

    {{-- Sandbox Token --}}
    <div class="col-12 mb-3" id="sandbox_token_group" style="@if(!($module_data['sandbox_mode'] ?? false))display: none;@endif">
        <label class="form-label" for="sandbox_token">
            {{ __('Payment.puqMonobank.Sandbox Token') }}
            <span class="text-danger">*</span>
            @if($module_data['sandbox_token_exists'] ?? false)
                <span class="badge bg-success ms-2">{{ __('Payment.puqMonobank.Configured') }}</span>
            @endif
        </label>
        <textarea name="sandbox_token" id="sandbox_token" class="form-control" rows="3"
                  placeholder="@if($module_data['sandbox_token_exists'] ?? false){{ __('Payment.puqMonobank.Leave empty to keep current token') }}@else{{ __('Payment.puqMonobank.Enter your test API token') }}@endif">{{ $module_data['sandbox_token'] ?? '' }}</textarea>
        @if($module_data['sandbox_token_exists'] ?? false)
            <small class="form-text text-muted">
                {{ __('Payment.puqMonobank.Token is currently configured') }}
            </small>
        @endif
    </div>

    {{-- Payment Timeout --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="payment_timeout">
            {{ __('Payment.puqMonobank.Payment Timeout (seconds)') }}
        </label>
        <input type="number" name="payment_timeout" id="payment_timeout" class="form-control"
               value="{{ $module_data['payment_timeout'] ?? 3600 }}" min="300" max="86400">
        <small class="form-text text-muted">
            {{ __('Payment.puqMonobank.Time limit for payment completion (5 minutes to 24 hours)') }}
        </small>
    </div>

    {{-- Payment Type --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="payment_type">
            {{ __('Payment.puqMonobank.Payment Type') }}
        </label>
        <select name="payment_type" id="payment_type" class="form-control">
            <option value="debit" {{ ($module_data['payment_type'] ?? 'debit') === 'debit' ? 'selected' : '' }}>
                {{ __('Payment.puqMonobank.Debit (Immediate payment)') }}
            </option>
            <option value="hold" {{ ($module_data['payment_type'] ?? 'debit') === 'hold' ? 'selected' : '' }}>
                {{ __('Payment.puqMonobank.Hold (Authorize and capture later)') }}
            </option>
        </select>
        <small class="form-text text-muted">
            {{ __('Payment.puqMonobank.Hold payments require manual finalization within 9 days') }}
        </small>
    </div>

    {{-- iFrame Mode --}}
    <div class="col-12 mb-3">
        <div class="form-check form-switch">
            <input type="hidden" name="iframe_mode" value="">
            <input class="form-check-input" type="checkbox" id="iframe_mode" name="iframe_mode" value="yes"
                   @if($module_data['iframe_mode'] ?? false) checked @endif>
            <label class="form-check-label" for="iframe_mode">
                {{ __('Payment.puqMonobank.Enable iFrame Mode') }}
            </label>
        </div>
        <small class="form-text text-muted">
            {{ __('Payment.puqMonobank.Display payment form in iframe instead of redirect') }}
        </small>
    </div>

    {{-- Auto Redirect --}}
    <div class="col-12 mb-3">
        <div class="form-check form-switch">
            <input type="hidden" name="auto_redirect" value="">
            <input class="form-check-input" type="checkbox" id="auto_redirect" name="auto_redirect" value="yes"
                   @if($module_data['auto_redirect'] ?? true) checked @endif>
            <label class="form-check-label" for="auto_redirect">
                {{ __('Payment.puqMonobank.Auto Redirect to Payment') }}
            </label>
        </div>
        <small class="form-text text-muted">
            {{ __('Payment.puqMonobank.Automatically redirect customers to payment page') }}
        </small>
    </div>

    {{-- Webhook Security --}}
    <div class="col-12 mb-3">
        <h6 class="border-bottom pb-2">{{ __('Payment.puqMonobank.Webhook Security') }}</h6>
    </div>

    {{-- Webhook Signature Verification --}}
    <div class="col-12 mb-3">
        <div class="form-check form-switch">
            <input type="hidden" name="webhook_signature_verification" value="">
            <input class="form-check-input" type="checkbox" id="webhook_signature_verification" name="webhook_signature_verification" value="yes"
                   @if($module_data['webhook_signature_verification'] ?? true) checked @endif>
            <label class="form-check-label" for="webhook_signature_verification">
                {{ __('Payment.puqMonobank.Enable Webhook Signature Verification') }}
            </label>
        </div>
        <small class="form-text text-muted">
            {{ __('Payment.puqMonobank.Verify webhook signatures to ensure authenticity (recommended)') }}
            <br>
            <i class="fas fa-info-circle"></i> {{ __('Payment.puqMonobank.Public key is automatically fetched from Monobank API for each webhook') }}
        </small>
    </div>

    {{-- Webhook Configuration --}}
    <div class="col-12 mb-3">
        <label class="form-label">{{ __('Payment.puqMonobank.Webhook URL') }}</label>
        <div class="input-group">
            <input type="text" class="form-control" id="webhook_url" 
                   value="{{ $webhook_url ?? 'Configuration required' }}" readonly>
            <button type="button" class="btn btn-outline-primary" onclick="copyToClipboard('webhook_url')">
                <i class="fas fa-copy"></i> {{ __('Payment.puqMonobank.Copy') }}
            </button>
        </div>
        <small class="form-text text-muted">
            {{ __('Payment.puqMonobank.Configure this URL in your Monobank merchant panel to receive payment notifications') }}
        </small>
    </div>

    {{-- Test Connection --}}
    <div class="col-12 mb-3">
        <button type="button" class="btn btn-info" onclick="testConnection()">
            <i class="fas fa-plug"></i>
            {{ __('Payment.puqMonobank.Test Connection') }}
        </button>
        <div id="test_result" class="mt-2"></div>
    </div>

    {{-- Support Information --}}
    <div class="col-12">
        <div class="alert alert-info">
            <h6>{{ __('Payment.puqMonobank.Support Information') }}</h6>
            <p><strong>{{ __('Payment.puqMonobank.Supported Currency') }}:</strong> UAH (Ukrainian Hryvnia)</p>
            <p><strong>{{ __('Payment.puqMonobank.Documentation') }}:</strong> 
               <a href="https://api.monobank.ua/" target="_blank">https://api.monobank.ua/</a></p>
        </div>
    </div>
</div>

<script>
function toggleSandboxMode() {
    const sandboxMode = document.getElementById('sandbox_mode').checked;
    const sandboxTokenGroup = document.getElementById('sandbox_token_group');
    const productionToken = document.getElementById('production_token');
    const sandboxToken = document.getElementById('sandbox_token');
    
    if (sandboxMode) {
        sandboxTokenGroup.style.display = 'block';
        sandboxToken.required = true;
        productionToken.required = false;
    } else {
        sandboxTokenGroup.style.display = 'none';
        sandboxToken.required = false;
        productionToken.required = true;
    }
}



function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.value;
    
    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        const button = element.nextElementSibling;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> {{ __("Payment.puqMonobank.Copied") }}';
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-success');
        
        setTimeout(function() {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');
        }, 2000);
    });
}

function testConnection() {
    const testButton = document.querySelector('button[onclick="testConnection()"]');
    const resultDiv = document.getElementById('test_result');
    
    // Show loading state
    testButton.disabled = true;
    testButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Payment.puqMonobank.Testing...") }}';
    resultDiv.innerHTML = '';
    
    // Get CSRF token safely
    let csrfToken = '';
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        csrfToken = csrfMeta.getAttribute('content');
    } else {
        // Try to get from Laravel _token input
        const tokenInput = document.querySelector('input[name="_token"]');
        if (tokenInput) {
            csrfToken = tokenInput.value;
        }
    }
    
    // Get current form data
    const formData = {
        sandbox_mode: document.getElementById('sandbox_mode').checked,
        production_token: document.getElementById('production_token').value,
        sandbox_token: document.getElementById('sandbox_token').value
    };
    
    // Prepare headers
    const headers = {
        'Content-Type': 'application/json'
    };
    
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }
    
    console.log('Testing connection with data:', formData);
    console.log('CSRF Token found:', !!csrfToken);
    
    // Get UUID from current URL or webhook URL field
    let uuid = '{{ request()->uuid ?? "" }}';
    if (!uuid) {
        const webhookUrl = document.getElementById('webhook_url').value;
        const uuidMatch = webhookUrl.match(/([a-f0-9-]{36})/);
        if (uuidMatch) {
            uuid = uuidMatch[1];
        }
    }
    
    // Try both static and API routes
    const origin = window.location.origin;
    const staticUrl = `${origin}/static/module/Payment/puqMonobank/test_connection/${uuid}`;
    const apiUrl = `${origin}/admin/api/modules/Payment/puqMonobank/test_connection/${uuid}`;
    
    console.log('Static URL:', staticUrl);
    console.log('API URL:', apiUrl);
    
    // Try static URL first, then API URL if it fails
    const testUrl = staticUrl;
    
    // Make AJAX request to test connection
    fetch(testUrl, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(formData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.text().then(text => {
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(`Invalid JSON response: ${text.substring(0, 100)}...`);
            }
        });
    })
    .then(data => {
        if (data.status === 'success') {
            resultDiv.innerHTML = `
                <div class="alert alert-success alert-sm">
                    <i class="fas fa-check-circle"></i>
                    <strong>${data.message || '{{ __("Payment.puqMonobank.Connection successful") }}'}</strong>
                    <div class="mt-2">
                        ${data.merchant_name ? '<div><i class="fas fa-building"></i> <strong>{{ __("Payment.puqMonobank.Merchant") }}:</strong> ' + data.merchant_name + '</div>' : ''}
                        ${data.merchant_id ? '<div><i class="fas fa-id-card"></i> <strong>{{ __("Payment.puqMonobank.Merchant ID") }}:</strong> ' + data.merchant_id + '</div>' : ''}
                        ${data.api_mode ? '<div><i class="fas fa-server"></i> <strong>{{ __("Payment.puqMonobank.API Mode") }}:</strong> ' + data.api_mode + '</div>' : ''}
                        ${data.endpoint_used ? '<div><i class="fas fa-link"></i> <strong>{{ __("Payment.puqMonobank.Endpoint") }}:</strong> ' + data.endpoint_used + '</div>' : ''}
                    </div>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger alert-sm">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>${data.message || '{{ __("Payment.puqMonobank.Connection failed") }}'}</strong>
                    <div class="mt-2">
                        ${data.error_code ? '<div><i class="fas fa-code"></i> <strong>{{ __("Payment.puqMonobank.Error Code") }}:</strong> ' + data.error_code + '</div>' : ''}
                        ${data.api_mode ? '<div><i class="fas fa-server"></i> <strong>{{ __("Payment.puqMonobank.API Mode") }}:</strong> ' + data.api_mode + '</div>' : ''}
                        ${data.endpoint_used ? '<div><i class="fas fa-link"></i> <strong>{{ __("Payment.puqMonobank.Endpoint") }}:</strong> ' + data.endpoint_used + '</div>' : ''}
                    </div>
                    <hr class="my-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        {{ __("Payment.puqMonobank.Please check your API token and network connection") }}
                    </small>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Static URL failed, trying API URL:', error);
        
        // Try API URL as fallback
        fetch(apiUrl, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(formData)
        })
        .then(response => {
            console.log('API Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.text().then(text => {
                console.log('API Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error(`Invalid JSON response: ${text.substring(0, 100)}...`);
                }
            });
        })
        .then(data => {
            console.log('API Success:', data);
            if (data.status === 'success') {
                resultDiv.innerHTML = `
                    <div class="alert alert-success alert-sm">
                        <i class="fas fa-check-circle"></i>
                        <strong>${data.message || '{{ __("Payment.puqMonobank.Connection successful") }}'}</strong>
                        <div class="mt-2">
                            ${data.merchant_name ? '<div><i class="fas fa-building"></i> <strong>{{ __("Payment.puqMonobank.Merchant") }}:</strong> ' + data.merchant_name + '</div>' : ''}
                            ${data.merchant_id ? '<div><i class="fas fa-id-card"></i> <strong>{{ __("Payment.puqMonobank.Merchant ID") }}:</strong> ' + data.merchant_id + '</div>' : ''}
                            ${data.api_mode ? '<div><i class="fas fa-server"></i> <strong>{{ __("Payment.puqMonobank.API Mode") }}:</strong> ' + data.api_mode + '</div>' : ''}
                            ${data.endpoint_used ? '<div><i class="fas fa-link"></i> <strong>{{ __("Payment.puqMonobank.Endpoint") }}:</strong> ' + data.endpoint_used + '</div>' : ''}
                        </div>
                        <small class="text-success">✓ {{ __("Payment.puqMonobank.Connected via API route") }}</small>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger alert-sm">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>${data.message || '{{ __("Payment.puqMonobank.Connection failed") }}'}</strong>
                        <div class="mt-2">
                            ${data.error_code ? '<div><i class="fas fa-code"></i> <strong>{{ __("Payment.puqMonobank.Error Code") }}:</strong> ' + data.error_code + '</div>' : ''}
                            ${data.api_mode ? '<div><i class="fas fa-server"></i> <strong>{{ __("Payment.puqMonobank.API Mode") }}:</strong> ' + data.api_mode + '</div>' : ''}
                            ${data.endpoint_used ? '<div><i class="fas fa-link"></i> <strong>{{ __("Payment.puqMonobank.Endpoint") }}:</strong> ' + data.endpoint_used + '</div>' : ''}
                        </div>
                        <hr class="my-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            {{ __("Payment.puqMonobank.Please check your API token and network connection") }}
                        </small>
                    </div>
                `;
            }
        })
        .catch(apiError => {
            console.error('Both URLs failed:', { staticError: error, apiError: apiError });
            resultDiv.innerHTML = `
                <div class="alert alert-danger alert-sm">
                    <i class="fas fa-times"></i>
                    <strong>{{ __("Payment.puqMonobank.Test failed") }}:</strong> Both static and API routes failed
                    <div class="mt-2">
                        <div><strong>Static URL Error:</strong> ${error.message}</div>
                        <div><strong>API URL Error:</strong> ${apiError.message}</div>
                    </div>
                    <hr class="my-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        {{ __("Payment.puqMonobank.Check browser console for details") }}
                    </small>
                </div>
            `;
        });
    })
    .finally(() => {
        // Restore button state
        testButton.disabled = false;
        testButton.innerHTML = '<i class="fas fa-plug"></i> {{ __("Payment.puqMonobank.Test Connection") }}';
    });
}

// Initialize form state on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleSandboxMode();
});
</script> 