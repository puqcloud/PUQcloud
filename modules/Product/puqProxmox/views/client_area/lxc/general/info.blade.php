<div class="card shadow-sm border-0">
    <div class="card-body" id="info">
        <div class="d-flex justify-content-center mb-2 d-none" id="show_password_once-block">
            <button class="btn btn-lg btn-warning fw-bold" id="show_password_once">
                {{ __('Product.puqProxmox.Get Login and Password (one time link)') }}
            </button>
        </div>

        <div class="d-flex flex-wrap text-start">
            <div class="p-0 d-flex align-items-center justify-content-start rounded me-2 mb-2"
                 id="os-block" data-visible="true">
                <div class="d-flex align-items-center justify-content-center p-0 m-0" style="width:70px; height:50px;">
                    <img id="os_icon" alt="" class="img-fluid"
                         style="max-width:100%; max-height:100%; object-fit:contain;">
                </div>
                <div class="d-flex flex-column justify-content-center">
                    <h6 class="mb-1 fw-bold" id="os_name" style="font-size:1rem;"></h6>
                </div>
            </div>

            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-primary rounded me-2 mb-2"
                 id="cores-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-microchip fs-5 text-primary me-2"></i>
                    <div class="fw-bold fs-6" id="cores">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.Cores') }}</small>
            </div>
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-success rounded me-2 mb-2"
                 id="ram-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-memory fs-5 text-success me-2"></i>
                    <div class="fw-bold fs-6" id="ram">
                        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.RAM') }}</small>
            </div>
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-warning rounded me-2 mb-2"
                 id="main_disk-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-hdd fs-5 text-warning me-2"></i>
                    <div class="fw-bold fs-6" id="main_disk">
                        <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.Main Disk') }}</small>
            </div>
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-secondary rounded me-2 mb-2"
                 id="addition_disk-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-hdd fs-5 text-secondary me-2"></i>
                    <div class="fw-bold fs-6" id="addition_disk">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.Additional Disk') }}</small>
            </div>
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-purple rounded me-2 mb-2"
                 id="backups-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-database fs-5 text-purple me-2"></i>
                    <div class="fw-bold fs-6" id="backups">
                        <div class="spinner-border spinner-border-sm text-purple" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.Backups') }}</small>
            </div>
        </div>
        <div class="d-flex flex-wrap text-start">
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-primary rounded me-2 mb-2"
                 id="domain-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-globe fs-5 text-primary me-2"></i>
                    <div class="fw-bold fs-6" id="domain">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.Domain') }}</small>
            </div>
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-info rounded me-2 mb-2"
                 id="ipv4-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-network-wired fs-5 text-info me-2"></i>
                    <div class="fw-bold fs-6" id="ipv4">
                        <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.IPv4') }}</small>
            </div>
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-danger rounded me-2 mb-2"
                 id="ipv6-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-network-wired fs-5 text-danger me-2"></i>
                    <div class="fw-bold fs-6" id="ipv6">
                        <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.IPv6') }}</small>
            </div>
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-success rounded me-2 mb-2"
                 id="local_private_network_ipv4-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-project-diagram fs-5 text-success me-2"></i>
                    <div class="fw-bold fs-6" id="local_private_network_ipv4">
                        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.Local Private IPv4') }}</small>
            </div>
            <div class="p-2 d-flex flex-column align-items-center flex-grow-1 flex-shrink-0 border border-warning rounded me-2 mb-2"
                 id="global_private_network_ipv4-block" data-visible="true">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-globe fs-5 text-warning me-2"></i>
                    <div class="fw-bold fs-6" id="global_private_network_ipv4">
                        <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    </div>
                </div>
                <small class="text-muted">{{ __('Product.puqProxmox.Global Private IPv4') }}</small>
            </div>
        </div>
    </div>
</div>
@section('js')
    @parent
    <script>
        $(document).ready(function () {
            function hasValue(v) {
                if (v === null || v === undefined) return false;
                if (typeof v === 'number') return true;
                if (typeof v === 'string') return v.trim() !== '';
                if (Array.isArray(v)) return v.length > 0;
                if (typeof v === 'object') return !jQuery.isEmptyObject(v);
                return false;
            }

            function loadInfoData() {
                blockUI('info');
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcInfo']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        var data = response.data;
                        $.each(data || {}, function (key, value) {
                            var block = $('#' + key + '-block');
                            var field = $('#' + key);


                            if (key === 'os') {
                                $("#os_name").text(value.name);
                                $("#os_icon").attr("src", value.icon_url).attr("alt", value.name);
                                return;
                            }


                            if (block.length) {
                                if (hasValue(value) || value === true) {
                                    var text = String(value);

                                    if (field && value !== true && value !== false) {
                                        field.text(text);
                                    }
                                    block.removeClass('d-none');
                                } else {
                                    block.addClass('d-none');
                                }
                            }
                        });
                        unblockUI('info');
                    })
                    .catch(function () {
                    });
            }

            loadInfoData();


            $('#show_password_once').on('click', function (e) {
                e.preventDefault();

                var $modal = $('#universalModal');
                var $modalTitle = $modal.find('.modal-title');
                var $modalBody = $modal.find('.modal-body');
                var $modalConfirmButton = $('#modalConfirmButton');

                $modalConfirmButton.hide();
                $modalTitle.text('{{ __('Product.puqProxmox.One-time display of login and password') }}');

                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getUsernamePassword']) }}", {}, 50, $(this), 'GET')
                    .then(function (response) {
                        loadInfoData();
                        if (response.status === 'success' && response.data) {
                            const {username, password, root_password} = response.data;

                            const formHtml = `
                    <div class="mb-2">
                        <label class="form-label fw-bold">Username:</label>
                        <input type="text" class="form-control" readonly value="${username}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Password:</label>
                        <input type="text" class="form-control" readonly value="${password}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Root Password:</label>
                        <input type="text" class="form-control" readonly value="${root_password}">
                    </div>
                `;
                            $modalBody.html(formHtml);
                            $modal.modal('show');
                        } else {
                            $modalBody.html('<p class="text-danger">{{ __('Product.puqProxmox.Failed to load credentials') }}</p>');
                            $modal.modal('show');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading data:', error);
                        $modalBody.html('<p class="text-danger">{{ __('Product.puqProxmox.Error loading data') }}</p>');
                        $modal.modal('show');
                    });

                $modal.on('hidden.bs.modal', function () {
                    $modalConfirmButton.show();
                    $modalBody.empty();
                    $modal.off('hidden.bs.modal');
                });
            });

        });
    </script>
@endsection
