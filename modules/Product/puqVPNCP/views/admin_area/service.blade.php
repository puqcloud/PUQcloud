<div class="row">
    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-3">
        <label for="server_uuid" class="form-label">{{ __('Product.puqVPNCP.Server') }}</label>
        <select name="server_uuid" id="server_uuid" class="form-select mb-2 form-control">
            @foreach($servers as $uuid => $name)
                <option value="{{ $uuid }}" {{ ($service_data['server_uuid'] ?? '') == $uuid ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-3">
        <label class="form-label" for="client_name">{{__('Product.puqVPNCP.Client Name')}}</label>
        <div>
            <input type="text" class="form-control input-mask-trigger" id="client_name" name="client_name"
                   value="{{$service_data['client_name'] ?? ''}}"
                   inputmode="text">
        </div>
    </div>
</div>

{{-- Live VPN Client Info --}}
@if(!empty($service_data['client_name']) && !empty($service_data['server_uuid']))
<div id="vpn-live-info" class="mt-2">
    <div class="text-center py-4" id="vpn-loading">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2 text-muted">{{__('Product.puqVPNCP.Loading client data from VPN server...')}}</div>
    </div>
    <div id="vpn-error" class="d-none">
        <div class="alert alert-warning mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="vpn-error-text"></span>
        </div>
    </div>
    <div id="vpn-data" class="d-none">

        {{-- Row 1: Client Status + Server Info --}}
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-shield me-2"></i>{{__('Product.puqVPNCP.VPN Client Status')}}</span>
                        <span id="vpn-status-badge"></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <tbody id="vpn-client-details"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-dark text-white py-2">
                        <i class="fas fa-server me-2"></i>{{__('Product.puqVPNCP.Server Info')}}
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <tbody id="vpn-server-details"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- WireGuard --}}
        <div class="row d-none" id="vpn-wg-section">
            <div class="col-lg-8 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-lock me-2"></i>WireGuard</span>
                        <div>
                            <button type="button" class="btn btn-sm btn-light py-0 px-2 me-1" id="vpn-wg-copy" title="{{__('Product.puqVPNCP.Copy')}}">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light py-0 px-2" id="vpn-wg-download" title="{{__('Product.puqVPNCP.Download')}}">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-3 rounded mb-0" id="vpn-wg-config" style="white-space: pre-wrap; word-break: break-all; font-size: 0.8rem; max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white py-2">
                        <i class="fas fa-qrcode me-2"></i>QR Code
                    </div>
                    <div class="card-body text-center" id="vpn-qr-container">
                        <span class="text-muted">-</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- OpenVPN --}}
        <div class="row d-none" id="vpn-ovpn-section">
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-shield-alt me-2"></i>OpenVPN</span>
                        <div>
                            <button type="button" class="btn btn-sm btn-light py-0 px-2 me-1" id="vpn-ovpn-copy" title="{{__('Product.puqVPNCP.Copy')}}">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light py-0 px-2" id="vpn-ovpn-download" title="{{__('Product.puqVPNCP.Download')}}">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-3 rounded mb-0" id="vpn-ovpn-config" style="white-space: pre-wrap; word-break: break-all; font-size: 0.8rem; max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- IKEv2 --}}
        <div class="row d-none" id="vpn-ikev2-section">
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-warning text-dark py-2 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-key me-2"></i>IKEv2</span>
                        <div>
                            <button type="button" class="btn btn-sm btn-dark py-0 px-2 me-1" id="vpn-ikev2-copy" title="{{__('Product.puqVPNCP.Copy')}}">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-dark py-0 px-2" id="vpn-ikev2-download" title="{{__('Product.puqVPNCP.Download')}}">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <tbody id="vpn-ikev2-details"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- OTL --}}
        <div class="row" id="vpn-otl-section">
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-secondary text-white py-2">
                        <i class="fas fa-link me-2"></i>{{__('Product.puqVPNCP.One-Time Link')}}
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-secondary" id="vpn-otl-generate">
                                <i class="fas fa-magic me-1"></i>{{__('Product.puqVPNCP.Generate One-Time Link')}}
                            </button>
                            <span id="vpn-otl-spinner" class="d-none">
                                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                            </span>
                        </div>
                        <div id="vpn-otl-result" class="mt-3 d-none">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" id="vpn-otl-url" readonly>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="vpn-otl-copy">
                                    <i class="fas fa-copy me-1"></i>{{__('Product.puqVPNCP.Copy')}}
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-exclamation-circle me-1"></i>{{__('Product.puqVPNCP.OTL expires after first use')}}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Traffic --}}
        <div class="row">
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white py-2">
                        <i class="fas fa-chart-area me-2"></i>{{__('Product.puqVPNCP.Traffic')}} — <span id="vpn-traffic-period"></span>
                    </div>
                    <div class="card-body">
                        <canvas id="vpn-traffic-chart" height="120"></canvas>
                        <div id="vpn-traffic-empty" class="text-center text-muted py-3 d-none">
                            {{__('Product.puqVPNCP.No traffic data available')}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    var vpnProfiles = {wg: '', ovpn: '', ikev2: '', otl: ''};
    var vpnClientName = @json($service_data['client_name'] ?? '');
    var vpnServerApiUrl = '';
    var vpnTrafficChart = null;

    function vpnFormatBytes(bytes) {
        if (!bytes || bytes == 0) return '0 B';
        var k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        var i = Math.floor(Math.log(Math.abs(bytes)) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function vpnDetailRow(label, value, icon) {
        icon = icon || '';
        var iconHtml = icon ? '<i class="' + icon + ' me-1 text-muted" style="width:18px"></i>' : '';
        return '<tr><td class="ps-3 text-muted" style="width:40%">' + iconHtml + label + '</td><td class="fw-bold">' + (value || '-') + '</td></tr>';
    }

    function vpnCopyText(type) {
        var text = vpnProfiles[type] || '';
        if (!text) return;
        navigator.clipboard.writeText(text).then(function() {
            toastr.success('{{__("Product.puqVPNCP.Copied to clipboard")}}');
        });
    }

    function vpnDownloadFile(type) {
        var text = vpnProfiles[type] || '';
        if (!text) return;
        var ext = {wg: '.conf', ovpn: '.ovpn', ikev2: '.ikev2.json'}[type] || '.txt';
        var mime = {wg: 'text/plain', ovpn: 'application/x-openvpn-profile', ikev2: 'application/json'}[type] || 'text/plain';
        var blob = new Blob([text], {type: mime});
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = vpnClientName + ext;
        a.click();
    }

    // Button handlers (type="button" + e.preventDefault to ensure no form submit)
    $(document).on('click', '#vpn-wg-copy', function(e) { e.preventDefault(); vpnCopyText('wg'); });
    $(document).on('click', '#vpn-wg-download', function(e) { e.preventDefault(); vpnDownloadFile('wg'); });
    $(document).on('click', '#vpn-ovpn-copy', function(e) { e.preventDefault(); vpnCopyText('ovpn'); });
    $(document).on('click', '#vpn-ovpn-download', function(e) { e.preventDefault(); vpnDownloadFile('ovpn'); });
    $(document).on('click', '#vpn-ikev2-copy', function(e) { e.preventDefault(); vpnCopyText('ikev2'); });
    $(document).on('click', '#vpn-ikev2-download', function(e) { e.preventDefault(); vpnDownloadFile('ikev2'); });
    $(document).on('click', '#vpn-otl-copy', function(e) { e.preventDefault(); vpnCopyText('otl'); });

    // OTL Generate
    $(document).on('click', '#vpn-otl-generate', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#vpn-otl-spinner').removeClass('d-none');
        $('#vpn-otl-result').addClass('d-none');

        $.ajax({
            url: "{{ route('admin.api.Product.puqVPNCP.service_client_info.get') }}",
            method: "GET",
            data: {
                server_uuid: @json($service_data['server_uuid'] ?? ''),
                client_name: @json($service_data['client_name'] ?? ''),
                otl_only: 1
            },
            success: function(response) {
                $btn.prop('disabled', false);
                $('#vpn-otl-spinner').addClass('d-none');

                if (response.status === 'success' && response.data && response.data.otl) {
                    var token = response.data.otl;
                    var serverUrl = response.data.server ? response.data.server.api_url : vpnServerApiUrl;
                    serverUrl = serverUrl ? serverUrl.replace(/\/+$/, '') : '';
                    var fullUrl = serverUrl + '/otl/' + token;

                    vpnProfiles.otl = fullUrl;
                    $('#vpn-otl-url').val(fullUrl);
                    $('#vpn-otl-result').removeClass('d-none');
                } else {
                    toastr.error('{{__("Product.puqVPNCP.Failed to generate OTL")}}');
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                $('#vpn-otl-spinner').addClass('d-none');
                toastr.error('{{__("Product.puqVPNCP.Failed to generate OTL")}}');
            }
        });
    });

    function vpnLoadData() {
        $.ajax({
            url: "{{ route('admin.api.Product.puqVPNCP.service_client_info.get') }}",
            method: "GET",
            data: {
                server_uuid: @json($service_data['server_uuid'] ?? ''),
                client_name: @json($service_data['client_name'] ?? '')
            },
            success: function(response) {
                $('#vpn-loading').addClass('d-none');
                if (response.status === 'success') {
                    $('#vpn-data').removeClass('d-none');
                    vpnRenderData(response.data);
                } else {
                    $('#vpn-error').removeClass('d-none');
                    $('#vpn-error-text').text(response.errors ? response.errors.join(', ') : 'Unknown error');
                }
            },
            error: function(xhr) {
                $('#vpn-loading').addClass('d-none');
                $('#vpn-error').removeClass('d-none');
                var msg = 'Connection error';
                try { msg = xhr.responseJSON.errors.join(', '); } catch(e) {}
                $('#vpn-error-text').text(msg);
            }
        });
    }

    function vpnRenderData(data) {
        var c = data.client || {};
        var s = data.server || {};
        var wg = data.wireguard || {};
        var ovpn = data.openvpn || {};
        var ikev2 = data.ikev2 || {};

        vpnServerApiUrl = s.api_url || '';

        // Status badge
        var isEnabled = (c.status === 'enable');
        $('#vpn-status-badge').html(
            isEnabled
                ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>{{__("Product.puqVPNCP.Enabled")}}</span>'
                : '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>{{__("Product.puqVPNCP.Disabled")}}</span>'
        );

        // Client details
        var html = '';
        html += vpnDetailRow('{{__("Product.puqVPNCP.Name")}}', c.name, 'fas fa-tag');
        html += vpnDetailRow('{{__("Product.puqVPNCP.Status")}}', isEnabled ? '<span class="text-success">{{__("Product.puqVPNCP.Enabled")}}</span>' : '<span class="text-danger">{{__("Product.puqVPNCP.Disabled")}}</span>', 'fas fa-power-off');
        html += vpnDetailRow('IPv4', c.ipv4, 'fas fa-network-wired');
        if (c.ipv6) html += vpnDetailRow('IPv6', c.ipv6, 'fas fa-network-wired');
        html += vpnDetailRow('{{__("Product.puqVPNCP.Network")}}', c.network_name, 'fas fa-project-diagram');
        html += vpnDetailRow('{{__("Product.puqVPNCP.Username")}}', c.username, 'fas fa-user');
        html += vpnDetailRow('{{__("Product.puqVPNCP.Password")}}', c.password ? '<code>' + c.password + '</code>' : '-', 'fas fa-key');
        html += vpnDetailRow('{{__("Product.puqVPNCP.Download Limit")}}', c.b_download && c.b_download != '0' ? c.b_download + ' Mbit/s' : '<span class="text-muted">{{__("Product.puqVPNCP.Unlimited")}}</span>', 'fas fa-arrow-down');
        html += vpnDetailRow('{{__("Product.puqVPNCP.Upload Limit")}}', c.b_upload && c.b_upload != '0' ? c.b_upload + ' Mbit/s' : '<span class="text-muted">{{__("Product.puqVPNCP.Unlimited")}}</span>', 'fas fa-arrow-up');
        if (c.mangle) html += vpnDetailRow('Mangle', c.mangle, 'fas fa-cog');

        // Protocols summary (from client.protocols)
        var cp = c.protocols || {};
        var protocols = [];
        if (cp.wireguard) protocols.push('<span class="badge bg-primary">WireGuard</span>');
        if (cp.openvpn) protocols.push('<span class="badge bg-info">OpenVPN</span>');
        if (cp.ikev2) protocols.push('<span class="badge bg-warning text-dark">IKEv2</span>');
        html += vpnDetailRow('{{__("Product.puqVPNCP.Protocols")}}', protocols.length ? protocols.join(' ') : '<span class="text-muted">-</span>', 'fas fa-shield-alt');
        $('#vpn-client-details').html(html);

        // Server details
        var sHtml = '';
        sHtml += vpnDetailRow('{{__("Product.puqVPNCP.Server Name")}}', s.name, 'fas fa-server');
        sHtml += vpnDetailRow('{{__("Product.puqVPNCP.API URL")}}', s.api_url ? '<a href="' + s.api_url + '" target="_blank" class="text-decoration-none">' + s.api_url + '</a>' : '-', 'fas fa-link');
        if (s.synced_at) {
            // Normalize ISO-8601 to "Y-m-d H:i:s".
            const syncedAt = String(s.synced_at).replace('T', ' ').substring(0, 19);
            sHtml += vpnDetailRow('{{__("Product.puqVPNCP.Last synced")}}', syncedAt, 'fas fa-clock');
        }
        $('#vpn-server-details').html(sHtml);

        // WireGuard section
        if (wg.config_text) {
            vpnProfiles.wg = typeof wg.config_text === 'string' ? wg.config_text : JSON.stringify(wg.config_text, null, 2);
            $('#vpn-wg-config').text(vpnProfiles.wg);
            $('#vpn-wg-section').removeClass('d-none');

            if (wg.config_qr) {
                var imgData = wg.config_qr;
                if (typeof imgData === 'string' && !imgData.startsWith('data:')) {
                    imgData = 'data:image/png;base64,' + imgData;
                }
                $('#vpn-qr-container').html('<img src="' + imgData + '" class="img-fluid" alt="QR" style="max-width:220px">');
            }
        }

        // OpenVPN section
        if (ovpn.available && ovpn.profile) {
            var ovpnText = ovpn.profile;
            if (typeof ovpnText === 'object' && ovpnText !== null) {
                ovpnText = ovpnText.profile || ovpnText.config || JSON.stringify(ovpnText, null, 2);
            }
            if (typeof ovpnText === 'string') {
                ovpnText = ovpnText.replace(/\\n/g, '\n').replace(/\\t/g, '\t');
            }
            vpnProfiles.ovpn = ovpnText;
            $('#vpn-ovpn-config').text(vpnProfiles.ovpn);
            $('#vpn-ovpn-section').removeClass('d-none');
        }

        // IKEv2 section
        if (ikev2.available && ikev2.profile) {
            var profile = ikev2.profile;
            if (typeof profile === 'string') {
                try { profile = JSON.parse(profile); } catch(e) {}
            }

            var iHtml = '';
            if (typeof profile === 'object' && profile !== null) {
                if (profile.name) iHtml += vpnDetailRow('{{__("Product.puqVPNCP.Name")}}', profile.name, 'fas fa-tag');
                if (profile.type) iHtml += vpnDetailRow('Type', '<code>' + profile.type + '</code>', 'fas fa-cog');
                if (profile.remote) {
                    if (profile.remote.addr) iHtml += vpnDetailRow('Remote', profile.remote.addr, 'fas fa-globe');
                    if (profile.remote.id) iHtml += vpnDetailRow('Remote ID', profile.remote.id, 'fas fa-id-badge');
                }
                if (profile.local) {
                    if (profile.local.eap_id) iHtml += vpnDetailRow('EAP ID', profile.local.eap_id, 'fas fa-user');
                }
                vpnProfiles.ikev2 = JSON.stringify(profile, null, 2);
            } else {
                iHtml += vpnDetailRow('Profile', String(profile), 'fas fa-file');
                vpnProfiles.ikev2 = String(profile);
            }
            $('#vpn-ikev2-details').html(iHtml);
            $('#vpn-ikev2-section').removeClass('d-none');
        }

        // Traffic chart
        vpnRenderTraffic(data.traffic);
    }

    function vpnRenderTraffic(traffic) {
        var now = new Date();
        // Period label in locale-independent "YYYY-MM" format (consistent with Y-m-d H:i:s style).
        $('#vpn-traffic-period').text(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));

        if (!traffic || (Array.isArray(traffic) && traffic.length === 0) || (typeof traffic === 'object' && Object.keys(traffic).length === 0)) {
            $('#vpn-traffic-chart').addClass('d-none');
            $('#vpn-traffic-empty').removeClass('d-none');
            return;
        }

        var labels = [], rxData = [], txData = [];

        if (Array.isArray(traffic)) {
            traffic.forEach(function(item) {
                labels.push(item.day || item.date || item.label || '');
                rxData.push(item.traffic_download || item.rx || item.download || item.received || 0);
                txData.push(item.traffic_upload || item.tx || item.upload || item.sent || 0);
            });
        } else if (typeof traffic === 'object') {
            Object.keys(traffic).sort().forEach(function(key) {
                var item = traffic[key];
                labels.push(key);
                if (typeof item === 'object') {
                    rxData.push(item.traffic_download || item.rx || item.download || item.received || 0);
                    txData.push(item.traffic_upload || item.tx || item.upload || item.sent || 0);
                } else {
                    rxData.push(item);
                    txData.push(0);
                }
            });
        }

        if (labels.length === 0) {
            $('#vpn-traffic-chart').addClass('d-none');
            $('#vpn-traffic-empty').removeClass('d-none');
            return;
        }

        var ctx = document.getElementById('vpn-traffic-chart').getContext('2d');
        if (vpnTrafficChart) vpnTrafficChart.destroy();

        vpnTrafficChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '{{__("Product.puqVPNCP.Download")}} (RX)',
                        data: rxData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '{{__("Product.puqVPNCP.Upload")}} (TX)',
                        data: txData,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return vpnFormatBytes(value); }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': ' + vpnFormatBytes(ctx.raw);
                            }
                        }
                    }
                }
            }
        });
    }

    vpnLoadData();
})();
</script>
@endif
