@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
@endsection

@section('buttons')
    @parent
    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.load_balancers.load_balancer_header')

    <div id="container">
        <div class="card mb-3">
            <div class="card-body">
                <form id="webProxyForm" method="POST" action="" novalidate="novalidate">
                    <div class="row g-3">

                        <div class="col-12 col-sm-6 col-md-6 col-lg-3 col-xl-3">
                            <label for="name" class="form-label">
                                <i class="fa fa-server me-1"></i>
                                {{ __('Product.puqProxmox.Name') }}
                            </label>
                            <input type="text" class="form-control" id="name" name="name">
                        </div>

                        <div class="col-12 col-sm-6 col-md-6 col-lg-1 col-xl-1">
                            <label class="form-label" for="disable">{{ __('Product.puqProxmox.Disable') }}</label>
                            <div>
                                <input type="checkbox"
                                       data-toggle="toggle"
                                       id="disable"
                                       name="disable"
                                       data-off="{{ __('Product.puqProxmox.No') }}"
                                       data-on="{{ __('Product.puqProxmox.Yes') }}"
                                       data-onstyle="danger"
                                       data-offstyle="success">
                            </div>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12 col-lg-8 col-xl-8">
                            <div class="border p-3 rounded">
                                <div class="row g-3 align-items-end">
                                    <!-- API URL -->
                                    <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-6">
                                        <label for="api_url"
                                               class="form-label">{{ __('Product.puqProxmox.API URL') }}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="api_url" name="api_url">
                                        </div>
                                    </div>

                                    <!-- API Key -->
                                    <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-6">
                                        <label for="api_key"
                                               class="form-label">{{ __('Product.puqProxmox.API Key') }}</label>
                                        <input type="text" class="form-control" id="api_key" name="api_key">
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                            <div class="border p-3 rounded">
                                <div class="row g-3 align-items-end">
                                    <div class="col-12 col-sm-6 col-md-6 col-lg-8 col-xl-8">
                                        <label for="ip_input"><i
                                                class="fas fa-network-wired me-1"></i> {{ __('Product.puqProxmox.Frontend IPs') }}
                                        </label>
                                        <input type="text" class="form-control" id="ip_input">
                                        <div
                                            class="invalid-feedback">{{ __('Product.puqProxmox.Invalid IP address format') }}</div>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-4">
                                        <button type="button" class="btn-icon btn-outline-2x btn btn-outline-secondary"
                                                id="add_ip_btn">
                                            <i class="fa fa-plus me-1"></i>{{ __('Product.puqProxmox.Add IP') }}
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60%">{{ __('Product.puqProxmox.IP Address') }}</th>
                                                    <th class="text-center"
                                                        style="width: 20%">{{ __('Product.puqProxmox.Type') }}</th>
                                                    <th class="text-center"
                                                        style="width: 20%">{{ __('Product.puqProxmox.Actions') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody id="ip_table_body">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                            <div id="system_status" style="min-height: 300px;">
                                <div id="system_info" class="card mb-3" style="display: none;">
                                    <div class="card-header">{{ __('Product.puqProxmox.System Status') }}</div>
                                    <div class="card-body">

                                        <!-- CPU LOAD -->
                                        <div class="row mb-3">
                                            <div class="col-sm-4 fw-bold">{{ __('Product.puqProxmox.CPU Load') }}:</div>
                                            <div class="col-sm-8">
                                                <div class="mb-1 small">1 min</div>
                                                <div class="progress mb-2" style="height: 20px;">
                                                    <div id="cpu_load1_bar" class="progress-bar bg-info" role="progressbar" style="width: 0%">0%</div>
                                                </div>
                                                <div class="mb-1 small">5 min</div>
                                                <div class="progress mb-2" style="height: 20px;">
                                                    <div id="cpu_load5_bar" class="progress-bar bg-info" role="progressbar" style="width: 0%">0%</div>
                                                </div>
                                                <div class="mb-1 small">15 min</div>
                                                <div class="progress" style="height: 20px;">
                                                    <div id="cpu_load15_bar" class="progress-bar bg-info" role="progressbar" style="width: 0%">0%</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MEMORY -->
                                        <div class="row mb-3">
                                            <div class="col-sm-4 fw-bold">{{ __('Product.puqProxmox.Memory') }}:</div>
                                            <div class="col-sm-8">
                                                <div class="progress" style="height: 20px;">
                                                    <div id="memory_bar" class="progress-bar bg-success" role="progressbar" style="width: 0%">0%</div>
                                                </div>
                                                <div class="small text-muted mt-1" id="memory_text"></div>
                                            </div>
                                        </div>

                                        <!-- OTHER INFO -->
                                        <div class="row mb-2">
                                            <div class="col-sm-4 fw-bold">{{ __('Product.puqProxmox.Version') }}:</div>
                                            <div class="col-sm-8" id="version"></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-sm-4 fw-bold">{{ __('Product.puqProxmox.Hostname') }}:</div>
                                            <div class="col-sm-8" id="hostname"></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-sm-4 fw-bold">{{ __('Product.puqProxmox.OS') }}:</div>
                                            <div class="col-sm-8" id="os"></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-sm-4 fw-bold">{{ __('Product.puqProxmox.CPU') }}:</div>
                                            <div class="col-sm-8" id="cpu"></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-sm-4 fw-bold">{{ __('Product.puqProxmox.Swap') }}:</div>
                                            <div class="col-sm-8" id="swap"></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-sm-4 fw-bold">{{ __('Product.puqProxmox.Uptime') }}:</div>
                                            <div class="col-sm-8" id="uptime"></div>
                                        </div>
                                    </div>
                                </div>

                                <div id="system_errors" class="alert alert-danger" style="display: none;">
                                    <ul id="error_list" class="mb-0"></ul>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="frontend_ips" name="frontend_ips" value="[]">
                    </div>
                </form>
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

                PUQajax('{{ route('admin.api.Product.puqProxmox.web_proxy.get', request()->get('edit')) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#disable").prop('checked', !!response.data?.disable).trigger('click');
                        $("#api_url").val(response.data?.api_url);
                        $("#api_key").val(response.data?.api_key);
                        $("#frontend_ips").val(JSON.stringify(response.data?.frontend_ips || []));
                        renderIPs();
                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function renderSystemStatus(response) {
                const $infoDiv = $('#system_info');
                const $errorDiv = $('#system_errors');
                const $errorList = $('#error_list');

                if (response.status === 'success' && response.data) {
                    $errorDiv.hide();
                    $infoDiv.show();

                    const data = response.data;

                    $('#version').text(data.version);
                    $('#hostname').text(data.hostname);
                    $('#os').text(`${data.os_name} (${data.os_architecture})`);
                    $('#cpu').text(`${data.cpu_model}, Threads: ${data.cpu_threads}`);

                    const cpuThreads = parseInt(data.cpu_threads || 1, 10);

                    const loads = {
                        '1': ((parseFloat(data.cpu_used_load1) / cpuThreads) * 100).toFixed(1),
                        '5': ((parseFloat(data.cpu_used_load5) / cpuThreads) * 100).toFixed(1),
                        '15': ((parseFloat(data.cpu_used_load15) / cpuThreads) * 100).toFixed(1)
                    };

                    updateProgressBar('cpu_load1_bar', loads['1']);
                    updateProgressBar('cpu_load5_bar', loads['5']);
                    updateProgressBar('cpu_load15_bar', loads['15']);

                    const memPercent = parseFloat(data.memory_used_percent || 0);
                    updateProgressBar('memory_bar', memPercent);
                    $('#memory_text').text(
                        `Total: ${data.memory_total} MB, Used: ${data.memory_used} MB, Free: ${data.memory_free} MB`
                    );

                    $('#swap').text(
                        `Total: ${data.swap_total} MB, Used: ${data.swap_used} MB (${data.swap_used_percent}%), Free: ${data.swap_free} MB`
                    );

                    const uptimeSec = parseInt(data.uptime || 0, 10);
                    $('#uptime').text(
                        `${Math.floor(uptimeSec / 3600)}h ${Math.floor((uptimeSec % 3600) / 60)}m ${uptimeSec % 60}s`
                    );

                } else {
                    $infoDiv.hide();
                    $errorDiv.show();
                    $errorList.empty();

                    const errors = response.errors || ['Unknown error'];
                    $.each(errors, function (_, err) {
                        $('<li>').text(err).appendTo($errorList);
                    });
                }
            }

            function updateProgressBar(id, percent) {
                const $el = $('#' + id);
                if (!$el.length) return;

                percent = Math.min(100, Math.max(0, parseFloat(percent) || 0));
                $el.css('width', percent + '%')
                    .text(percent.toFixed(1) + '%')
                    .attr('class', 'progress-bar ' + getLoadColor(percent));
            }

            function getLoadColor(percent) {
                percent = parseFloat(percent);
                if (percent > 85) return 'bg-danger';
                if (percent > 65) return 'bg-warning';
                if (percent > 40) return 'bg-info';
                return 'bg-success';
            }


            function loadSystemStatus() {
                blockUI('system_status');

                PUQajax('{{ route('admin.api.Product.puqProxmox.web_proxy.system.status.get', request()->get('edit')) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        renderSystemStatus(response);
                        unblockUI('system_status');
                    })
                    .catch(function (error) {
                        renderSystemStatus({status: 'error', errors: [error || 'Unknown error']});
                        unblockUI('system_status');
                    });
            }

            loadFormData();

            loadSystemStatus();

            setInterval(loadSystemStatus, 5000);

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#webProxyForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqProxmox.web_proxy.put', request()->get('edit')) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                        loadSystemStatus();
                    });
            });

            const ipInput = document.getElementById("ip_input");
            const addBtn = document.getElementById("add_ip_btn");
            const ipTableBody = document.getElementById("ip_table_body");
            const hiddenInput = document.getElementById("frontend_ips");

            function isValidIP(ip) {
                const ipv4 = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
                const ipv6 = /^(([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}|(([0-9a-fA-F]{1,4}:){1,7}|:):([0-9a-fA-F]{1,4}:){0,6}[0-9a-fA-F]{1,4})$/;
                return ipv4.test(ip) || ipv6.test(ip);
            }

            function detectIPType(ip) {
                return ip.includes(":") ? "IPv6" : "IPv4";
            }

            function renderIPs() {
                ipTableBody.innerHTML = "";
                const ips = JSON.parse(hiddenInput.value || "[]");

                if (ips.length === 0) {
                    ipTableBody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3">{{ __('Product.puqProxmox.No IP addresses added') }}</td></tr>`;
                    return;
                }

                ips.forEach((ip, index) => {
                    const type = detectIPType(ip);
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td><i class="fa fa-network-wired text-secondary me-2"></i>${ip}</td>
                        <td class="text-center"><span class="badge bg-${type === 'IPv4' ? 'info' : 'secondary'}">${type}</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-ip" data-index="${index}">
                                <i class="fa fa-trash"></i> {{ __('Product.puqProxmox.Remove') }}
                    </button>
                </td>
`;
                    ipTableBody.appendChild(row);
                });
            }

            function addIP() {
                const ip = ipInput.value.trim();
                ipInput.classList.remove("is-invalid");

                if (!ip || !isValidIP(ip)) {
                    ipInput.classList.add("is-invalid");
                    ipInput.nextElementSibling.nextElementSibling.textContent = "{{ __('Product.puqProxmox.Invalid IP address format') }}";
                    return;
                }

                const ips = JSON.parse(hiddenInput.value || "[]");
                if (ips.includes(ip)) {
                    ipInput.classList.add("is-invalid");
                    ipInput.nextElementSibling.nextElementSibling.textContent = "{{ __('Product.puqProxmox.This IP already exists') }}";
                    return;
                }

                ips.push(ip);
                hiddenInput.value = JSON.stringify(ips);
                renderIPs();
                ipInput.value = "";
            }

            addBtn.addEventListener("click", addIP);

            ipTableBody.addEventListener("click", function (e) {
                if (e.target.closest(".remove-ip")) {
                    const index = e.target.closest(".remove-ip").dataset.index;
                    const ips = JSON.parse(hiddenInput.value || "[]");
                    ips.splice(index, 1);
                    hiddenInput.value = JSON.stringify(ips);
                    renderIPs();
                }
            });

            renderIPs();
        });
    </script>
@endsection
