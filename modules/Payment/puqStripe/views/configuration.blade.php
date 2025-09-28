<div class="row" id="module">
    <div class="col-12">
        <div id="test_connection_data"></div>
    </div>

    {{-- WEB Hook URL --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="webhook_url">{{ __('Payment.puqStripe.WEB Hook URL') }}</label>
        <input type="text" class="form-control" id="webhook_url" name="webhook_url"
               value="{{ $webhook_url }}"
               disabled>
    </div>

    {{-- Live Publishable Secret Key --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="publishable_key">{{ __('Payment.puqStripe.Publishable Secret Key') }}</label>
        <input type="text" class="form-control" id="publishable_key" name="publishable_key"
               value="{{ $publishable_key }}"
               placeholder="{{ __('Payment.puqStripe.Publishable Secret Key') }}">
    </div>

    {{-- Live Secret Key --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="secret_key">{{ __('Payment.puqStripe.Secret Key') }}</label>
        <input type="text" class="form-control" id="secret_key" name="secret_key"
               value="{{ $secret_key }}"
               placeholder="{{ __('Payment.puqStripe.Secret Key') }}">
    </div>

    {{-- Live WEB Hook Secret --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="webhook_secret">{{ __('Payment.puqStripe.WEB Hook Secret') }}</label>
        <input type="text" class="form-control" id="webhook_secret" name="webhook_secret"
               value="{{ $webhook_secret }}"
               placeholder="{{ __('Payment.puqStripe.WEB Hook Secret') }}">
    </div>

    {{-- Sandbox Toggle --}}
    <div class="col-12 mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="sandbox" name="sandbox" @if($sandbox) checked @endif>
            <label class="form-check-label" for="sandbox">{{ __('Payment.puqStripe.Sandbox Mode') }}</label>
        </div>
    </div>

    {{-- Sandbox Publishable Secret Key --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_publishable_key">{{ __('Payment.puqStripe.Sandbox Publishable Secret Key') }}</label>
        <input type="text" class="form-control" id="sandbox_publishable_key" name="sandbox_publishable_key"
               value="{{ $sandbox_publishable_key }}"
               placeholder="{{ __('Payment.puqStripe.Sandbox Publishable Secret Key') }}">
    </div>

    {{-- Sandbox Secret Key --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_secret_key">{{ __('Payment.puqStripe.Sandbox Secret Key') }}</label>
        <input type="text" class="form-control" id="sandbox_secret_key" name="sandbox_secret_key"
               value="{{ $sandbox_secret_key }}"
               placeholder="{{ __('Payment.puqStripe.Sandbox Secret Key') }}">
    </div>

    {{-- Sandbox WEB Hook Secret --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_webhook_secret">{{ __('Payment.puqStripe.Sandbox WEB Hook Secret') }}</label>
        <input type="text" class="form-control" id="sandbox_webhook_secret" name="sandbox_webhook_secret"
               value="{{ $sandbox_webhook_secret }}"
               placeholder="{{ __('Payment.puqStripe.Sandbox WEB Hook Secret') }}">
    </div>

</div>
@if($admin->hasPermission('Payment-puqStripe-test-connection'))
    <button id="test_connection" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
        <i class="fa fa-plug"></i> {{__('Payment.puqStripe.Test Connection')}}
    </button>
@endif
<script>
    $("#test_connection").on("click", function (event) {
        const $form = $("#payment_gateway");
        const $btn = $(this);
        blockUI('module');
        const formData = serializeForm($form);

        PUQajax('{{ route('admin.api.Payment.puqStripe.test_connection.post', $uuid) }}', formData, 5000, $btn, 'POST', $form)
            .then(function (response) {
                unblockUI('module');
                if (response.status === 'success') {
                    const d = response.data;
                    let html = `<div class="mb-2">
                        <div class="alert alert-success">
                            <strong>{{ __('Payment.puqStripe.Access Available') }}</strong>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <strong>{{ __('Payment.puqStripe.Account Information') }}</strong>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.ID') }}:</strong> ${d.id || ''}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Business Name') }}:</strong> ${d.business_profile?.name || ''}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Email') }}:</strong> ${d.email || ''}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Country') }}:</strong> ${d.country || ''}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Business Type') }}:</strong> ${d.business_type || ''}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Currency') }}:</strong> ${d.default_currency?.toUpperCase() || ''}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Charges Enabled') }}:</strong> ${d.charges_enabled ? '✅ {{ __('Payment.puqStripe.Yes') }}' : '❌ {{ __('Payment.puqStripe.No') }}'}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Payouts Enabled') }}:</strong> ${d.payouts_enabled ? '✅ {{ __('Payment.puqStripe.Yes') }}' : '❌ {{ __('Payment.puqStripe.No') }}'}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Statement Descriptor') }}:</strong> ${d.settings?.payments?.statement_descriptor || ''}</li>
                                <li class="list-group-item"><strong>{{ __('Payment.puqStripe.Dashboard Timezone') }}:</strong> ${d.settings?.dashboard?.timezone || ''}</li>
                                <li class="list-group-item">
                                    <strong>{{ __('Payment.puqStripe.Support') }}:</strong><br>
                                    {{ __('Payment.puqStripe.Support Email') }}: ${d.business_profile?.support_email || ''}<br>
                                    {{ __('Payment.puqStripe.Support Phone') }}: ${d.business_profile?.support_phone || ''}<br>
                                    {{ __('Payment.puqStripe.Support URL') }}: <a href="${d.business_profile?.support_url || '#'}" target="_blank">${d.business_profile?.support_url || ''}</a>
                                </li>
                            </ul>
                        </div>`;

                    if (d.capabilities) {
                        html += `<div class="card mt-3">
                            <div class="card-header"><strong>{{ __('Payment.puqStripe.Capabilities') }}</strong></div>
                            <ul class="list-group list-group-flush">`;
                        for (const [key, value] of Object.entries(d.capabilities)) {
                            html += `<li class="list-group-item">
                                ${key.replace(/_/g, ' ')}:
                                <span class="badge bg-${value === 'active' ? 'success' : value === 'pending' ? 'warning' : 'secondary'} text-uppercase">${value}</span>
                            </li>`;
                        }
                        html += `</ul></div>`;
                    }

                    html += `</div>`;
                    $("#test_connection_data").html(html);
                } else {
                    $("#test_connection_data").html(`<div class="alert alert-danger">{{ __('Payment.puqStripe.Connection failed') }}</div>`);
                }
            })
            .catch(function (error) {
                unblockUI('module');
                $("#test_connection_data").html(`<div class="alert alert-danger">{{ __('Payment.puqStripe.Connection error') }}</div>`);
            });
    });
</script>
