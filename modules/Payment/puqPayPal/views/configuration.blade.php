<div class="row" id="module">
    <div class="col-12">
        <div id="test_connection_data"></div>
    </div>

    {{-- WEB Hook URL --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="webhook_url">{{ __('Payment.puqPayPal.WEB Hook URL') }}</label>
        <input type="text" class="form-control" id="webhook_url" name="webhook_url"
               value="{{ $webhook_url }}"
               disabled>
    </div>

    {{-- Live Client ID --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="client_id">{{ __('Payment.puqPayPal.Client ID') }}</label>
        <input type="text" class="form-control" id="client_id" name="client_id"
               value="{{ $client_id }}"
               placeholder="{{ __('Payment.puqPayPal.Client ID') }}">
    </div>

    {{-- Live Secret --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="secret">{{ __('Payment.puqPayPal.Secret') }}</label>
        <input type="text" class="form-control" id="secret" name="secret"
               value="{{ $secret }}"
               placeholder="{{ __('Payment.puqPayPal.Secret') }}">
    </div>

    {{-- WEB Hook ID --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="secret">{{ __('Payment.puqPayPal.WEB Hook ID') }}</label>
        <input type="text" class="form-control" id="webhook_id" name="webhook_id"
               value="{{ $webhook_id }}"
               placeholder="{{ __('Payment.puqPayPal.WEB Hook ID') }}">
    </div>

    {{-- Sandbox Toggle --}}
    <div class="col-12 mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="sandbox" name="sandbox" @if($sandbox) checked @endif>
            <label class="form-check-label" for="sandbox">{{ __('Payment.puqPayPal.Sandbox Mode') }}</label>
        </div>
    </div>

    {{-- Sandbox Client ID --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_client_id">{{ __('Payment.puqPayPal.Sandbox Client ID') }}</label>
        <input type="text" class="form-control" id="sandbox_client_id" name="sandbox_client_id"
               value="{{ $sandbox_client_id }}"
               placeholder="{{ __('Payment.puqPayPal.Sandbox Client ID') }}">
    </div>

    {{-- Sandbox Secret --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_secret">{{ __('Payment.puqPayPal.Sandbox Secret') }}</label>
        <input type="text" class="form-control" id="sandbox_secret" name="sandbox_secret"
               value="{{ $sandbox_secret }}"
               placeholder="{{ __('Payment.puqPayPal.Sandbox Secret') }}">
    </div>

    {{-- Sandbox WEB Hook ID --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="secret">{{ __('Payment.puqPayPal.Sandbox WEB Hook ID') }}</label>
        <input type="text" class="form-control" id="sandbox_webhook_id" name="sandbox_webhook_id"
               value="{{ $sandbox_webhook_id }}"
               placeholder="{{ __('Payment.puqPayPal.Sandbox WEB Hook ID') }}">
    </div>

</div>
@if($admin->hasPermission('Payment-puqPayPal-test-connection'))
    <button id="test_connection" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
        <i class="fa fa-plug"></i> {{__('Payment.puqPayPal.Test Connection')}}
    </button>
@endif

<script>
    $("#test_connection").on("click", function (event) {
        const $form = $("#payment_gateway");
        const $btn = $(this);
        blockUI('module');
        const formData = serializeForm($form);

        PUQajax('{{ route('admin.api.Payment.puqPayPal.test_connection.post',$uuid) }}', formData, 5000, $btn, 'POST', $form)
            .then(function (response) {
                unblockUI('module');
                if (response.status === 'success') {
                    let html = `<div class="mb-2">
                        <div class="alert alert-success">
                            <strong>${response.message}</strong>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <strong>{{ __('Payment.puqPayPal.Balances') }}</strong>
                            </div>
                            <ul class="list-group list-group-flush">`;

                    response.data.balances.forEach(balance => {
                        html += `
                            <li class="list-group-item">
                                <strong>${balance.currency}</strong><br>
                                {{ __('Payment.puqPayPal.Total') }}: ${balance.total_balance.value} ${balance.currency}<br>
                                {{ __('Payment.puqPayPal.Available') }}: ${balance.available_balance.value} ${balance.currency}<br>
                                {{ __('Payment.puqPayPal.Withheld') }}: ${balance.withheld_balance.value} ${balance.currency}
                            </li>`;
                    });

                    html += `</ul></div></div>`;
                    $("#test_connection_data").html(html);
                } else {
                    $("#test_connection_data").html('<div class="alert alert-danger">{{ __('Payment.puqPayPal.Connection failed') }}</div>');
                }
            })
            .catch(function (error) {
                unblockUI('module');
                $("#test_connection_data").html('<div class="alert alert-danger">{{ __('Payment.puqPayPal.Connection error') }}</div>');
            });
    });
</script>
