@section('content')
    @parent
    @if(!empty($service_data['client_name']) && !empty($service_data['server_uuid']))
    <div class="container px-0">

        {{-- Loading --}}
        <div class="text-center py-5" id="vpnc-loading">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 text-muted">{{ __('Product.puqVPNCP.Loading') }}...</div>
        </div>

        {{-- Error --}}
        <div id="vpnc-error" class="d-none">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span id="vpnc-error-text"></span>
            </div>
        </div>

        {{-- Content --}}
        <div id="vpnc-data" class="d-none">

            {{-- One-Time Link (top) --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-secondary text-white py-2">
                    <i class="fas fa-link me-2"></i>{{ __('Product.puqVPNCP.One-Time Link') }}
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-secondary" id="vpnc-otl-generate">
                            <i class="fas fa-magic me-1"></i>{{ __('Product.puqVPNCP.Generate One-Time Link') }}
                        </button>
                        <span id="vpnc-otl-spinner" class="d-none">
                            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                        </span>
                    </div>
                    <div id="vpnc-otl-result" class="mt-3 d-none">
                        <div class="input-group">
                            <input type="text" class="form-control" id="vpnc-otl-url" readonly>
                            <button type="button" class="btn btn-outline-secondary" id="vpnc-otl-copy">
                                <i class="fas fa-copy me-1"></i>{{ __('Product.puqVPNCP.Copy') }}
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ __('Product.puqVPNCP.OTL expires after first use') }}
                        </small>
                    </div>
                </div>
            </div>

            {{-- Client Info + Location (2 columns) --}}
            <div class="row">
                <div class="col-lg-7 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-user-shield me-2"></i>{{ __('Product.puqVPNCP.VPN Client') }}</span>
                            <span id="vpnc-status-badge"></span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody id="vpnc-client-details"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-map-marker-alt me-2"></i>{{ __('Product.puqVPNCP.Location') }}</span>
                            @if(!empty($product_data['instructions_url']))
                                <a href="{{ $product_data['instructions_url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="btn btn-sm btn-outline-light py-0 px-2">
                                    <i class="fas fa-book-open me-1"></i>{{ __('Product.puqVPNCP.Instructions') }}
                                </a>
                            @endif
                        </div>
                        <div class="card-body" id="vpnc-location-body">
                            @if(!empty($location))
                                <div class="text-center py-3">
                                    @if(!empty($location['country_code']))
                                        <img src="https://flagcdn.com/80x60/{{ strtolower($location['country_code']) }}.png"
                                             alt="{{ $location['country_code'] }}" class="mb-3 rounded shadow-sm">
                                    @endif
                                    <h5 class="mb-1">{{ $location['country_name'] ?? '' }}</h5>
                                    @if(!empty($location['data_center']))
                                        <div class="text-muted">
                                            <i class="fas fa-building me-1"></i>{{ $location['data_center'] }}
                                        </div>
                                    @endif
                                    @if(!empty($location['description']))
                                        <p class="text-muted mt-2 mb-0 small">{{ $location['description'] }}</p>
                                    @endif
                                </div>
                            @else
                                <div class="text-center text-muted py-3">-</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- WireGuard --}}
            <div class="card shadow-sm mb-3 d-none" id="vpnc-wg-section">
                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-lock me-2"></i>WireGuard</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-light py-0 px-2 me-1" id="vpnc-wg-copy">
                            <i class="fas fa-copy me-1"></i>{{ __('Product.puqVPNCP.Copy Config') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-light py-0 px-2" id="vpnc-wg-download">
                            <i class="fas fa-download me-1"></i>{{ __('Product.puqVPNCP.Download') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <pre class="bg-light p-3 rounded mb-0" id="vpnc-wg-config" style="white-space: pre-wrap; word-break: break-all; font-size: 0.85rem; max-height: 300px; overflow-y: auto;"></pre>
                        </div>
                        <div class="col-12 col-md-4 text-center mt-3 mt-md-0" id="vpnc-qr-container">
                        </div>
                    </div>
                </div>
            </div>

            {{-- OpenVPN --}}
            <div class="card shadow-sm mb-3 d-none" id="vpnc-ovpn-section">
                <div class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-shield-alt me-2"></i>OpenVPN</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-light py-0 px-2 me-1" id="vpnc-ovpn-copy">
                            <i class="fas fa-copy me-1"></i>{{ __('Product.puqVPNCP.Copy Config') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-light py-0 px-2" id="vpnc-ovpn-download">
                            <i class="fas fa-download me-1"></i>{{ __('Product.puqVPNCP.Download') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded mb-0" id="vpnc-ovpn-config" style="white-space: pre-wrap; word-break: break-all; font-size: 0.85rem; max-height: 300px; overflow-y: auto;"></pre>
                </div>
            </div>

            {{-- IKEv2 --}}
            <div class="card shadow-sm mb-3 d-none" id="vpnc-ikev2-section">
                <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-key me-2"></i>IKEv2</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-dark py-0 px-2" id="vpnc-ikev2-download">
                            <i class="fas fa-download me-1"></i>{{ __('Product.puqVPNCP.Download') }}
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody id="vpnc-ikev2-details"></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    @endif
@endsection

@if(!empty($service_data['client_name']) && !empty($service_data['server_uuid']))
@section('js')
    @parent
    <script>
        $(document).ready(function () {
            var vpncProfiles = {wg: '', ovpn: '', ikev2: '', otl: ''};
            var vpncClientName = @json($service_data['client_name'] ?? 'vpn');
            var vpncServerUrl = '';

            function vpncDetailRow(label, value, icon) {
                icon = icon || '';
                var iconHtml = icon ? '<i class="' + icon + ' me-2 text-muted"></i>' : '';
                return '<tr><td class="ps-3 text-muted" style="width:40%">' + iconHtml + label + '</td><td class="fw-bold">' + (value || '-') + '</td></tr>';
            }

            function vpncCopyText(text) {
                if (!text) return;
                navigator.clipboard.writeText(text).then(function() {
                    toastr.success('{{ __("Product.puqVPNCP.Copied to clipboard") }}');
                });
            }

            function vpncDownloadFile(content, ext, mime) {
                if (!content) return;
                var blob = new Blob([content], {type: mime});
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = vpncClientName + ext;
                a.click();
            }

            // Button handlers
            $('#vpnc-wg-copy').on('click', function(e) { e.preventDefault(); vpncCopyText(vpncProfiles.wg); });
            $('#vpnc-wg-download').on('click', function(e) { e.preventDefault(); vpncDownloadFile(vpncProfiles.wg, '.conf', 'text/plain'); });
            $('#vpnc-ovpn-copy').on('click', function(e) { e.preventDefault(); vpncCopyText(vpncProfiles.ovpn); });
            $('#vpnc-ovpn-download').on('click', function(e) { e.preventDefault(); vpncDownloadFile(vpncProfiles.ovpn, '.ovpn', 'application/x-openvpn-profile'); });
            $('#vpnc-ikev2-download').on('click', function(e) { e.preventDefault(); vpncDownloadFile(vpncProfiles.ikev2, '.ikev2.json', 'application/json'); });
            $('#vpnc-otl-copy').on('click', function(e) { e.preventDefault(); vpncCopyText(vpncProfiles.otl); });

            // OTL Generate
            $('#vpnc-otl-generate').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.prop('disabled', true);
                $('#vpnc-otl-spinner').removeClass('d-none');
                $('#vpnc-otl-result').addClass('d-none');

                $.ajax({
                    url: "{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'clientInfoGet']) }}",
                    method: "GET",
                    data: { otl_only: 1 },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $('#vpnc-otl-spinner').addClass('d-none');
                        if (response.status === 'success' && response.data && response.data.otl) {
                            var serverUrl = (response.data.server_url || vpncServerUrl || '').replace(/\/+$/, '');
                            var fullUrl = serverUrl + '/otl/' + response.data.otl;
                            vpncProfiles.otl = fullUrl;
                            $('#vpnc-otl-url').val(fullUrl);
                            $('#vpnc-otl-result').removeClass('d-none');
                        } else {
                            toastr.error('{{ __("Product.puqVPNCP.Failed to generate OTL") }}');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        $('#vpnc-otl-spinner').addClass('d-none');
                        toastr.error('{{ __("Product.puqVPNCP.Failed to generate OTL") }}');
                    }
                });
            });

            // Load all data
            $.ajax({
                url: "{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'clientInfoGet']) }}",
                method: "GET",
                success: function(response) {
                    $('#vpnc-loading').addClass('d-none');
                    if (response.status === 'success') {
                        $('#vpnc-data').removeClass('d-none');
                        vpncRenderData(response.data);
                    } else {
                        $('#vpnc-error').removeClass('d-none');
                        $('#vpnc-error-text').text(response.errors ? response.errors.join(', ') : '{{ __("Product.puqVPNCP.Something went wrong try again later") }}');
                    }
                },
                error: function(xhr) {
                    $('#vpnc-loading').addClass('d-none');
                    $('#vpnc-error').removeClass('d-none');
                    var msg = '{{ __("Product.puqVPNCP.Something went wrong try again later") }}';
                    try { msg = xhr.responseJSON.errors.join(', '); } catch(e) {}
                    $('#vpnc-error-text').text(msg);
                }
            });

            function vpncRenderData(data) {
                var c = data.client || {};
                var wg = data.wireguard || {};
                var ovpn = data.openvpn || {};
                var ikev2 = data.ikev2 || {};
                vpncServerUrl = data.server_url || '';

                // Status badge
                var isEnabled = (c.status === 'enable');
                $('#vpnc-status-badge').html(
                    isEnabled
                        ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>{{ __("Product.puqVPNCP.Enabled") }}</span>'
                        : '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>{{ __("Product.puqVPNCP.Disabled") }}</span>'
                );

                // Client details (without VPN Client name, Network — those are in header)
                var html = '';
                html += vpncDetailRow('{{ __("Product.puqVPNCP.VPN IP Address") }}', c.ipv4, 'fas fa-network-wired');
                if (c.ipv6) html += vpncDetailRow('IPv6', c.ipv6, 'fas fa-network-wired');
                html += vpncDetailRow('{{ __("Product.puqVPNCP.Username") }}', c.username, 'fas fa-user');
                html += vpncDetailRow('{{ __("Product.puqVPNCP.Password") }}', c.password ? '<code>' + c.password + '</code>' : '-', 'fas fa-key');
                html += vpncDetailRow('{{ __("Product.puqVPNCP.Download Limit") }}', c.b_download && c.b_download != '0' ? c.b_download + ' Mbit/s' : '<span class="text-muted">{{ __("Product.puqVPNCP.Unlimited") }}</span>', 'fas fa-arrow-down');
                html += vpncDetailRow('{{ __("Product.puqVPNCP.Upload Limit") }}', c.b_upload && c.b_upload != '0' ? c.b_upload + ' Mbit/s' : '<span class="text-muted">{{ __("Product.puqVPNCP.Unlimited") }}</span>', 'fas fa-arrow-up');

                // Protocols summary (from client.protocols)
                var cp = c.protocols || {};
                var protocols = [];
                if (cp.wireguard) protocols.push('<span class="badge bg-primary">WireGuard</span>');
                if (cp.openvpn) protocols.push('<span class="badge bg-info">OpenVPN</span>');
                if (cp.ikev2) protocols.push('<span class="badge bg-warning text-dark">IKEv2</span>');
                if (protocols.length) {
                    html += vpncDetailRow('{{ __("Product.puqVPNCP.Protocols") }}', protocols.join(' '), 'fas fa-shield-alt');
                }
                $('#vpnc-client-details').html(html);

                // WireGuard
                if (wg.config_text) {
                    vpncProfiles.wg = typeof wg.config_text === 'string' ? wg.config_text : JSON.stringify(wg.config_text, null, 2);
                    $('#vpnc-wg-config').text(vpncProfiles.wg);
                    $('#vpnc-wg-section').removeClass('d-none');

                    if (wg.config_qr) {
                        var imgData = wg.config_qr;
                        if (typeof imgData === 'string' && !imgData.startsWith('data:')) {
                            imgData = 'data:image/png;base64,' + imgData;
                        }
                        $('#vpnc-qr-container').html('<img src="' + imgData + '" class="img-fluid" alt="QR Code" style="max-width: 200px;">');
                    } else {
                        $('#vpnc-qr-container').html('<span class="text-muted">{{ __("Product.puqVPNCP.QR not available") }}</span>');
                    }
                }

                // OpenVPN
                if (ovpn.available && ovpn.profile) {
                    var ovpnText = ovpn.profile;
                    if (typeof ovpnText === 'object' && ovpnText !== null) {
                        ovpnText = ovpnText.profile || ovpnText.config || JSON.stringify(ovpnText, null, 2);
                    }
                    if (typeof ovpnText === 'string') {
                        ovpnText = ovpnText.replace(/\\n/g, '\n').replace(/\\t/g, '\t');
                    }
                    vpncProfiles.ovpn = ovpnText;
                    $('#vpnc-ovpn-config').text(vpncProfiles.ovpn);
                    $('#vpnc-ovpn-section').removeClass('d-none');
                }

                // IKEv2
                if (ikev2.available && ikev2.profile) {
                    var profile = ikev2.profile;
                    if (typeof profile === 'string') {
                        try { profile = JSON.parse(profile); } catch(e) {}
                    }

                    var iHtml = '';
                    if (typeof profile === 'object' && profile !== null) {
                        if (profile.name) iHtml += vpncDetailRow('{{ __("Product.puqVPNCP.Name") }}', profile.name, 'fas fa-tag');
                        if (profile.type) iHtml += vpncDetailRow('Type', '<code>' + profile.type + '</code>', 'fas fa-cog');
                        if (profile.remote) {
                            if (profile.remote.addr) iHtml += vpncDetailRow('Remote', profile.remote.addr, 'fas fa-globe');
                            if (profile.remote.id) iHtml += vpncDetailRow('Remote ID', profile.remote.id, 'fas fa-id-badge');
                        }
                        if (profile.local) {
                            if (profile.local.eap_id) iHtml += vpncDetailRow('EAP ID', profile.local.eap_id, 'fas fa-user');
                        }
                        vpncProfiles.ikev2 = JSON.stringify(profile, null, 2);
                    } else {
                        iHtml += vpncDetailRow('Profile', String(profile), 'fas fa-file');
                        vpncProfiles.ikev2 = String(profile);
                    }
                    $('#vpnc-ikev2-details').html(iHtml);
                    $('#vpnc-ikev2-section').removeClass('d-none');
                }
            }
        });
    </script>
@endsection
@endif
