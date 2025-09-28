<div class="main-card card mb-2">
    <div class="card-body" id="public_card">
        <form id="public_form">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="p-4 d-flex flex-column align-items-center border rounded"
                         id="ipv4-block">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-network-wired fs-2 me-2 text-info"></i>
                            <span class="fw-bold fs-4">{{ __('Product.puqProxmox.IPv4') }}</span>
                        </div>
                        <div class="fw-bold fs-5 mb-2" id="ipv4">
                            <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                        </div>
                        <div class="text-muted mb-3" id="ipv4_info"></div>
                        <div class="input-group mb-2">
                            <span class="input-group-text fw-bold">{{ __('Product.puqProxmox.rDNS') }}</span>
                            <input type="text" class="form-control text-center" name="ipv4_rdns" id="ipv4_rdns"
                                   placeholder="{{ __('Product.puqProxmox.Reverse DNS') }}">
                            <span class="input-group-text"><i class="fas fa-server"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="p-4 d-flex flex-column align-items-center border rounded"
                         id="ipv6-block">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-network-wired fs-2 me-2 text-warning"></i>
                            <span class="fw-bold fs-4">{{ __('Product.puqProxmox.IPv6') }}</span>
                        </div>
                        <div class="fw-bold fs-5 mb-2" id="ipv6">
                            <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                        </div>
                        <div class="text-muted mb-3" id="ipv6_info"></div>
                        <div class="input-group mb-2">
                            <span class="input-group-text fw-bold">{{ __('Product.puqProxmox.rDNS') }}</span>
                            <input type="text" class="form-control text-center" name="ipv6_rdns" id="ipv6_rdns"
                                   placeholder="{{ __('Product.puqProxmox.Reverse DNS') }}">
                            <span class="input-group-text"><i class="fas fa-server"></i></span>
                        </div>
                    </div>
                </div>

            </div>
        </form>

        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <button type="button" class="btn btn-success btn-lg px-4" id="public_save">
                    <i class="fa fa-save me-2"></i>{{ __('Product.puqProxmox.Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            function loadPublicNetworkData() {
                blockUI('public_card');
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getPublicNetworks']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        let data = response.data;

                        function renderBlock(type, color) {
                            let block = $('#' + type + '-block');
                            let ipField = $('#' + type);
                            let rdnsField = $('#' + type + '_rdns');
                            let infoField = $('#' + type + '_info');

                            if (data[type] && data[type].ip) {
                                ipField.html('<i class="fas fa-circle text-' + color + ' me-2"></i>' + data[type].ip);
                                infoField.html(data[type].name + ' | ' + data[type].mac);
                                rdnsField.val(data[type].rdns).prop('disabled', false);
                                block.removeClass('bg-light text-muted');
                            } else {
                                ipField.html('<span class="text-muted">N/A</span>');
                                infoField.html('');
                                rdnsField.val('').prop('disabled', true);
                                block.addClass('bg-light text-muted');
                            }
                        }

                        renderBlock('ipv4', 'success');
                        renderBlock('ipv6', 'success');

                        unblockUI('public_card');
                    });
            }

            loadPublicNetworkData();


            $('#public_save').on('click', function (event) {
                event.preventDefault();
                if ($('#public_form').length) {
                    var $form = $('#public_form');
                    var formData = serializeForm($form);
                    PUQajax('{{ route('client.api.cloud.service.module.post', ['uuid' => $service_uuid, 'method' => 'postLxcPublicNetworks']) }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            loadPublicNetworkData();
                        });
                }
            });

        });
    </script>
@endsection
