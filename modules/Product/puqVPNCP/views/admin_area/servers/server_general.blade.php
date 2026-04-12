@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('buttons')

    <button type="button"
            id="test_connection"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
        <i class="fa fa-plug"></i> {{__('Product.puqVPNCP.Test Connection')}}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{__('Product.puqVPNCP.Save')}}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger"
            data-model-url="{{ route('admin.api.Product.puqVPNCP.server.delete', $uuid) }}"
            id="deleteServer">
        <i class="fa fa-trash-alt"></i>
    </button>

@endsection

@section('content')
    @include('modules.Product.puqVPNCP.views.admin_area.servers.server_header')

    <div id="container">
        <div class="card mb-3">
            <div class="card-body">
                <form id="serverForm" method="POST" action="" novalidate="novalidate">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 mb-3">
                            <label for="name" class="form-label">{{ __('Product.puqVPNCP.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 mb-3">
                            <label for="api_url" class="form-label">{{ __('Product.puqVPNCP.API URL') }}</label>
                            <input type="text" class="form-control" id="api_url" name="api_url" placeholder="https://vpn.example.com">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 mb-3">
                            <label for="api_token" class="form-label">{{ __('Product.puqVPNCP.API Token') }}</label>
                            <input type="password" class="form-control" id="api_token" name="api_token">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="verify_ssl" name="verify_ssl" value="1" checked>
                                <label class="form-check-label" for="verify_ssl">{{ __('Product.puqVPNCP.Verify SSL') }}</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Server Information (from cached sync data) --}}
        <div id="server-info-section" style="display: none;">
            <div class="row">
                {{-- System Info --}}
                <div class="col-lg-6 mb-3">
                    <div class="card" id="system-info-card">
                        <div class="card-header">
                            <i class="fas fa-server me-2"></i>{{ __('Product.puqVPNCP.Server Information') }}
                        </div>
                        <div class="card-body" id="system-info-body">
                        </div>
                    </div>
                </div>

                {{-- License Info --}}
                <div class="col-lg-6 mb-3">
                    <div class="card" id="license-info-card">
                        <div class="card-header">
                            <i class="fas fa-key me-2"></i>{{ __('Product.puqVPNCP.License Information') }}
                        </div>
                        <div class="card-body" id="license-info-body">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Usage --}}
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-2"></i>{{ __('Product.puqVPNCP.Usage') }}
                        </div>
                        <div class="card-body" id="usage-info-body">
                        </div>
                    </div>
                </div>

                {{-- Protocols --}}
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-shield-alt me-2"></i>{{ __('Product.puqVPNCP.Protocols') }}
                        </div>
                        <div class="card-body" id="protocols-info-body">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <small class="text-muted" id="synced-at-text"></small>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('container');

                PUQajax('{{route('admin.api.Product.puqVPNCP.server.get', $uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#api_url").val(response.data?.api_url);
                        $("#api_token").val(response.data?.api_token);
                        $("#verify_ssl").prop('checked', response.data?.verify_ssl !== false);

                        renderServerInfo(response.data?.description);
                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('container');
                    });
            }

            function renderServerInfo(desc) {
                if (!desc || !desc.system || !Object.keys(desc.system).length) {
                    $('#server-info-section').hide();
                    return;
                }

                $('#server-info-section').show();

                let isLicenseInvalid = desc.license && !desc.license.valid;

                // System Info — show all fields from API response
                let systemHtml = '';
                let systemLabels = {
                    'version': '{{ __('Product.puqVPNCP.Version') }}',
                    'status': '{{ __('Product.puqVPNCP.Status') }}',
                    'license': '{{ __('Product.puqVPNCP.License') }}',
                    'license_valid': '{{ __('Product.puqVPNCP.License Valid') }}',
                    'count_accounts': '{{ __('Product.puqVPNCP.Total Accounts') }}',
                    'count_accounts_available': '{{ __('Product.puqVPNCP.Available Accounts') }}',
                    'count_wireguard': '{{ __('Product.puqVPNCP.WireGuard Networks') }}',
                    'count_networks': '{{ __('Product.puqVPNCP.Total Networks') }}',
                    'hostname': '{{ __('Product.puqVPNCP.Hostname') }}',
                    'os': '{{ __('Product.puqVPNCP.OS') }}',
                    'cpu': '{{ __('Product.puqVPNCP.CPU') }}',
                    'timezone': '{{ __('Product.puqVPNCP.Timezone') }}',
                    'cpu_load': '{{ __('Product.puqVPNCP.CPU Load') }}',
                    'memory': '{{ __('Product.puqVPNCP.Memory') }}',
                    'uptime': '{{ __('Product.puqVPNCP.Uptime') }}'
                };

                Object.entries(desc.system).forEach(([k, v]) => {
                    if (typeof v === 'object' && v !== null) return;

                    let label = systemLabels[k] || k;
                    let valueHtml = '';
                    let valueClass = '';

                    // Special rendering for certain fields
                    if (k === 'status') {
                        let isOk = (v === 'OK' || v === 'ok');
                        valueClass = isOk ? 'text-success' : 'text-warning';
                        valueHtml = `<strong class="${valueClass}">${v}</strong>`;
                    } else if (k === 'license') {
                        let isInactive = String(v).toLowerCase().includes('inactive') || String(v).toLowerCase().includes('invalid');
                        valueClass = isInactive ? 'text-danger fw-bold' : '';
                        valueHtml = `<strong class="${valueClass}">${v}</strong>`;
                    } else if (k === 'license_valid') {
                        let isFalse = (v === false || v === 'false');
                        valueClass = isFalse ? 'text-danger' : 'text-success';
                        valueHtml = `<strong class="${valueClass}">${v}</strong>`;
                    } else {
                        valueHtml = `<strong>${v}</strong>`;
                    }

                    systemHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">${label}</span>
                        ${valueHtml}
                    </div>`;
                });

                $('#system-info-body').html(systemHtml);

                // License Info — with danger styling when invalid
                let licenseHtml = '';
                let $licenseCard = $('#license-info-card');
                $licenseCard.removeClass('border-danger');

                if (desc.license && Object.keys(desc.license).length) {
                    let license = desc.license;

                    // Normalize any ISO-8601 timestamp to "Y-m-d H:i:s"; short strings pass through unchanged.
                    const fmtDt = v => v ? String(v).replace('T', ' ').substring(0, 19) : '';

                    if (isLicenseInvalid) {
                        $licenseCard.addClass('border-danger');
                        licenseHtml += `<div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                            <div>
                                <strong>{{ __('Product.puqVPNCP.License Invalid') }}</strong><br>
                                <small>${fmtDt(license.expiration_date)}</small>
                            </div>
                        </div>`;
                    }

                    let validClass = license.valid ? 'text-success' : 'text-danger fw-bold';
                    let validText = license.valid ? '{{ __('Product.puqVPNCP.Valid') }}' : '{{ __('Product.puqVPNCP.Invalid') }}';
                    licenseHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">{{ __('Product.puqVPNCP.Status') }}</span>
                        <span class="${validClass}">${validText}</span>
                    </div>`;

                    if (license.expiration_date) {
                        let expClass = isLicenseInvalid ? 'text-danger fw-bold' : '';
                        licenseHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">{{ __('Product.puqVPNCP.Expiration Date') }}</span>
                            <strong class="${expClass}">${fmtDt(license.expiration_date)}</strong>
                        </div>`;
                    }

                    if (license.accounts !== undefined) {
                        licenseHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">{{ __('Product.puqVPNCP.VPN Client Slots') }}</span>
                            <strong>${license.accounts}</strong>
                        </div>`;
                    }

                    if (license.last_check) {
                        licenseHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">{{ __('Product.puqVPNCP.Last Checked') }}</span>
                            <strong>${fmtDt(license.last_check)}</strong>
                        </div>`;
                    }
                } else {
                    licenseHtml = '<span class="text-muted">-</span>';
                }
                $('#license-info-body').html(licenseHtml);

                // Usage — data from /system/status
                // count_accounts = existing/active accounts
                // count_accounts_available = total capacity (max allowed)
                let usageHtml = '';
                let sys = desc.system || {};
                let activeClients = parseInt(sys.count_accounts || 0);
                let totalCapacity = parseInt(sys.count_accounts_available || 0);
                let freeSlots = Math.max(0, totalCapacity - activeClients);
                let countNetworks = parseInt(sys.count_networks || 0);

                // Fallback to networks array if system data missing
                if (!totalCapacity && desc.networks) {
                    let networks = Array.isArray(desc.networks) ? desc.networks : Object.values(desc.networks);
                    countNetworks = networks.length;
                    activeClients = 0;
                    networks.forEach(n => {
                        if (typeof n === 'object') activeClients += parseInt(n.client_count || 0);
                    });
                    totalCapacity = activeClients;
                    freeSlots = 0;
                }

                let usagePct = totalCapacity > 0 ? Math.round(activeClients / totalCapacity * 100) : 0;
                let usageBarClass = usagePct > 90 ? 'bg-danger' : (usagePct > 70 ? 'bg-warning' : 'bg-success');

                let capacityNote = '';

                usageHtml += `<div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ __('Product.puqVPNCP.VPN Clients') }}</span>
                        <strong>${activeClients} / ${totalCapacity}</strong>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar ${usageBarClass}" style="width: ${usagePct}%">${usagePct}%</div>
                    </div>
                </div>`;

                usageHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted"><i class="fas fa-network-wired me-1"></i> {{ __('Product.puqVPNCP.Networks') }}</span>
                    <strong>${countNetworks}</strong>
                </div>`;

                usageHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted"><i class="fas fa-users me-1"></i> {{ __('Product.puqVPNCP.Active Clients') }}</span>
                    <strong>${activeClients}</strong>
                </div>`;

                usageHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted"><i class="fas fa-door-open me-1"></i> {{ __('Product.puqVPNCP.Available Slots') }}</span>
                    <strong>${freeSlots}</strong>
                </div>`;

                usageHtml += `<div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted"><i class="fas fa-tachometer-alt me-1"></i> {{ __('Product.puqVPNCP.Total Capacity') }}</span>
                    <strong>${totalCapacity}</strong>
                </div>`;

                $('#usage-info-body').html(usageHtml);

                // Protocols (from desc.protocols aggregated from networks)
                let protocolsHtml = '';
                let proto = desc.protocols || {};
                let protocolsDef = [
                    {key: 'wireguard', name: 'WireGuard', icon: 'fas fa-shield-alt'},
                    {key: 'openvpn', name: 'OpenVPN', icon: 'fas fa-lock'},
                    {key: 'ikev2', name: 'IKEv2', icon: 'fas fa-key'}
                ];

                protocolsDef.forEach(p => {
                    let enabled = proto[p.key] === true;
                    let statusBadge = enabled
                        ? '<span class="badge bg-success">{{ __('Product.puqVPNCP.Enabled') }}</span>'
                        : '<span class="badge bg-danger">{{ __('Product.puqVPNCP.Disabled') }}</span>';

                    protocolsHtml += `<div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span><i class="${p.icon} me-2"></i><strong>${p.name}</strong></span>
                        ${statusBadge}
                    </div>`;
                });

                $('#protocols-info-body').html(protocolsHtml);

                // Synced at — normalize ISO-8601 to "Y-m-d H:i:s".
                if (desc.synced_at) {
                    const syncedAt = String(desc.synced_at).replace('T', ' ').substring(0, 19);
                    $('#synced-at-text').html('<i class="fas fa-clock me-1"></i> {{ __('Product.puqVPNCP.Last synced') }}: ' + syncedAt);
                }
            }

            $("#save").on("click", function (event) {
                const $form = $("#serverForm");
                event.preventDefault();

                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.Product.puqVPNCP.server.put', $uuid)}}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $("#test_connection").on("click", function (event) {
                event.preventDefault();

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');

                $modalSaveButton.hide();
                $modalTitle.text('{{ __('Product.puqVPNCP.Test Connection') }}');

                const formHtml = `
                    <div class="mb-3" id="test_connection_result">
                        <div class="text-center my-3">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                `;
                $modalBody.html(formHtml);
                $('#universalModal').modal('show');

                const $form = $("#serverForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqVPNCP.server.test_connection.post', $uuid) }}', formData, 1000, $(this), 'POST', $form)
                    .then(function (response) {
                        if (response.status === 'success' && response.data) {
                            let html = `
                                <div class="alert alert-success">
                                    <i class="fa fa-check-circle me-1 text-success"></i> {{ __('Product.puqVPNCP.Test Connection Success') }}
                                </div>
                            `;
                            html += renderTestConnection(response.data);
                            $('#test_connection_result').html(html);
                        } else {
                            $('#test_connection_result').html(`<div class="alert alert-warning">{{ __('Product.puqVPNCP.Test Connection Failed') }}</div>`);
                        }

                        loadFormData();
                    })
                    .catch(function (errors) {
                        const html = `
                            <div class="alert alert-danger">
                                <strong>{{ __('Product.puqVPNCP.Test Connection Failed') }}</strong>
                                {{ __('Product.puqVPNCP.Test Connection Failed Desc') }}
                                <ul class="mb-0 mt-2">
                                    ${Array.isArray(errors) ? errors.map(err => `<li>${err}</li>`).join('') : ''}
                                </ul>
                            </div>
                        `;
                        $('#test_connection_result').html(html);
                    });
            });

            $('#deleteServer').on('click', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqVPNCP.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 50, $(this), 'DELETE');
                }
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                $('#modalSaveButton').show();
            });

            function renderTestConnection(data) {
                let html = '';

                if (data.system && Object.keys(data.system).length) {
                    html += `<div class="mb-4"><h5><i class="fas fa-server me-2"></i>{{ __('Product.puqVPNCP.System Info') }}</h5>`;
                    Object.entries(data.system).forEach(([k, v]) => {
                        if (typeof v !== 'object') {
                            html += `<div class="d-flex justify-content-between border rounded p-2 bg-light mb-2">
                                <div>${k}</div><div><strong>${v}</strong></div>
                            </div>`;
                        }
                    });
                    html += `</div>`;
                }

                if (data.license && Object.keys(data.license).length) {
                    html += `<div class="mb-4"><h5><i class="fas fa-key me-2"></i>{{ __('Product.puqVPNCP.License') }}</h5>`;
                    Object.entries(data.license).forEach(([k, v]) => {
                        if (typeof v !== 'object') {
                            let badgeClass = (k === 'valid' && v === true) ? 'text-success' : '';
                            html += `<div class="d-flex justify-content-between border rounded p-2 bg-light mb-2">
                                <div>${k}</div><div><strong class="${badgeClass}">${v}</strong></div>
                            </div>`;
                        }
                    });
                    html += `</div>`;
                }

                if (data.networks) {
                    let networks = Array.isArray(data.networks) ? data.networks : Object.values(data.networks);
                    html += `<div class="mb-4"><h5><i class="fas fa-network-wired me-2"></i>{{ __('Product.puqVPNCP.Networks') }} (${networks.length})</h5>`;
                    if (networks.length) {
                        networks.forEach(n => {
                            let name = typeof n === 'string' ? n : (n.name || n.interface || JSON.stringify(n));
                            let clients = typeof n === 'object' ? (n.client_count || 0) : '';
                            let addr = typeof n === 'object' ? (n.address || '') : '';
                            html += `<div class="d-flex justify-content-between border rounded p-2 bg-light mb-2">
                                <div><i class="fa fa-network-wired me-1"></i> ${name} ${addr ? '<small class="text-muted">(' + addr + ')</small>' : ''}</div>
                                <div><span class="badge bg-secondary">${clients} clients</span></div>
                            </div>`;
                        });
                    } else {
                        html += `<div class="text-muted">{{ __('Product.puqVPNCP.None') }}</div>`;
                    }
                    html += `</div>`;
                }

                return html;
            }

            loadFormData();
        });
    </script>
@endsection
