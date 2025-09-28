<div class="row" id="module">
    <div class="col-12">
        <div id="test_connection_data"></div>
    </div>

    {{-- Live Merchant ID --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="merchant_id">{{ __('Payment.puqPrzelewy24.Merchant ID') }}</label>
        <input type="text" class="form-control" id="merchant_id" name="merchant_id"
               value="{{ $merchant_id }}"
               placeholder="{{ __('Payment.puqPrzelewy24.Merchant ID') }}">
    </div>

    {{-- Live API KEY --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="api_key">{{ __('Payment.puqPrzelewy24.API KEY') }}</label>
        <input type="text" class="form-control" id="api_key" name="api_key"
               value="{{ $api_key }}"
               placeholder="{{ __('Payment.puqPrzelewy24.API KEY') }}">
    </div>

    {{-- Live POS ID --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="pos_id">{{ __('Payment.puqPrzelewy24.POS ID') }}</label>
        <input type="text" class="form-control" id="pos_id" name="pos_id"
               value="{{ $pos_id }}"
               placeholder="{{ __('Payment.puqPrzelewy24.POS ID') }}">
    </div>

    {{-- Live CRC --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="crc">{{ __('Payment.puqPrzelewy24.CRC') }}</label>
        <input type="text" class="form-control" id="crc" name="crc"
               value="{{ $crc }}"
               placeholder="{{ __('Payment.puqPrzelewy24.CRC') }}">
    </div>

    {{-- Sandbox Toggle --}}
    <div class="col-12 mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="sandbox" name="sandbox" @if($sandbox) checked @endif>
            <label class="form-check-label" for="sandbox">{{ __('Payment.puqPrzelewy24.Sandbox Mode') }}</label>
        </div>
    </div>

    {{-- Sandbox Merchant ID --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_merchant_id">{{ __('Payment.puqPrzelewy24.Sandbox Merchant ID') }}</label>
        <input type="text" class="form-control" id="sandbox_merchant_id" name="sandbox_merchant_id"
               value="{{ $sandbox_merchant_id }}"
               placeholder="{{ __('Payment.puqPrzelewy24.Sandbox Merchant ID') }}">
    </div>

    {{-- Sandbox API KEY --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_api_key">{{ __('Payment.puqPrzelewy24.Sandbox API KEY') }}</label>
        <input type="text" class="form-control" id="sandbox_api_key" name="sandbox_api_key"
               value="{{ $sandbox_api_key }}"
               placeholder="{{ __('Payment.puqPrzelewy24.Sandbox API KEY') }}">
    </div>

    {{-- Sandbox POS ID --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_pos_id">{{ __('Payment.puqPrzelewy24.Sandbox POS ID') }}</label>
        <input type="text" class="form-control" id="sandbox_pos_id" name="sandbox_pos_id"
               value="{{ $sandbox_pos_id }}"
               placeholder="{{ __('Payment.puqPrzelewy24.Sandbox POS ID') }}">
    </div>

    {{-- Sandbox CRC --}}
    <div class="col-12 mb-3">
        <label class="form-label" for="sandbox_crc">{{ __('Payment.puqPrzelewy24.Sandbox CRC') }}</label>
        <input type="text" class="form-control" id="sandbox_crc" name="sandbox_crc"
               value="{{ $sandbox_crc }}"
               placeholder="{{ __('Payment.puqPrzelewy24.Sandbox CRC') }}">
    </div>

</div>
@if($admin->hasPermission('Payment-puqPrzelewy24-test-connection'))
    <button id="test_connection" type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
        <i class="fa fa-plug"></i> {{__('Payment.puqPrzelewy24.Test Connection')}}
    </button>
@endif
<script>
    $("#test_connection").on("click", function () {
        const $form = $("#payment_gateway");
        const $btn = $(this);
        blockUI('module');
        const formData = serializeForm($form);

        PUQajax('{{ route('admin.api.Payment.puqPrzelewy24.test_connection.post', $uuid) }}', formData, 5000, $btn, 'POST', $form)
            .then(function (response) {
                unblockUI('module');

                if (response.status === 'success' && response.data.data === true) {
                    $("#test_connection_data").html(`
                    <div class="alert alert-success">
                        ✅ {{ __('Payment.puqPrzelewy24.Access Available') }}
                    </div>
                `);
                } else {
                    $("#test_connection_data").html(`
                    <div class="alert alert-danger">
                        ❌ {{ __('Payment.puqPrzelewy24.Connection failed') }}<br>
                        <code>${response.data.error || 'Unknown error'}</code>
                    </div>
                `);
                }
            })
            .catch(function () {
                unblockUI('module');
                $("#test_connection_data").html(`
                <div class="alert alert-danger">
                    ❌ {{ __('Payment.puqPrzelewy24.Connection error') }}
                </div>
            `);
            });
    });
</script>
