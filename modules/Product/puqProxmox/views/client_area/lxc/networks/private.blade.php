<div class="main-card card mb-2">
    <div class="card-body" id="private_card">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="p-4 border rounded d-flex flex-column align-items-center" id="local-block">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-laptop-house fs-2 me-2 text-info"></i>
                        <span class="fw-bold fs-4">{{ __('Product.puqProxmox.Local Private Network') }}</span>
                    </div>
                    <div class="fw-bold fs-5 mb-2" id="local_ip">
                        <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                    </div>
                    <div class="text-muted" id="local_info"></div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="p-4 border rounded d-flex flex-column align-items-center" id="global-block">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-globe fs-2 me-2 text-warning"></i>
                        <span class="fw-bold fs-4">{{ __('Product.puqProxmox.Global Private Network') }}</span>
                    </div>
                    <div class="fw-bold fs-5 mb-2" id="global_ip">
                        <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    </div>
                    <div class="text-muted" id="global_info"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            function loadPrivateNetworkData() {
                blockUI('private_card');
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getPrivateNetworks']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        let data = response.data;

                        function renderBlock(type, color) {
                            let ipField = $('#' + type + '_ip');
                            let infoField = $('#' + type + '_info');
                            let block = $('#' + type + '-block');

                            if (data[type] && data[type].ip) {
                                ipField.html('<i class="fas fa-circle text-' + color + ' me-2"></i>' + data[type].ip + '/' + data[type].mask);
                                infoField.html(data[type].name + ' | ' + data[type].mac);
                                block.removeClass('bg-light text-muted');
                            } else {
                                ipField.html('<span class="text-muted">N/A</span>');
                                infoField.html('');
                                block.addClass('bg-light text-muted');
                            }
                        }

                        renderBlock('local', 'success');
                        renderBlock('global', 'success');

                        unblockUI('private_card');
                    });
            }

            loadPrivateNetworkData();
        });
    </script>
@endsection
