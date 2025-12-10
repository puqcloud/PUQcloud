<div class="row g-3 align-items-end">

    <!-- Username -->
    <div class="col-md-2">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" value="{{ $username }}">
    </div>

    <!-- Password -->
    <div class="col-md-2">
        <label for="password" class="form-label">Password</label>
        <input type="text" class="form-control" id="password" name="password" value="{{ $password }}">
    </div>

    <!-- Root Password -->
    <div class="col-md-2">
        <label for="root_password" class="form-label">Root Password</label>
        <input type="text" class="form-control" id="root_password" name="root_password" value="{{ $root_password }}">
    </div>

    <!-- SSH Public Key -->
    <div class="col-md-2">
        <label for="puq_pm_ssh_public_key_uuid" class="form-label">SSH Public Key</label>
        <select class="form-select" id="puq_pm_ssh_public_key_uuid" name="puq_pm_ssh_public_key_uuid">
            @foreach($puq_pm_ssh_public_keys as $puq_pm_ssh_public_key)
                <option value="{{ $puq_pm_ssh_public_key->uuid }}"
                    {{ $puq_pm_ssh_public_key_uuid == $puq_pm_ssh_public_key->uuid ? 'selected' : '' }}>
                    {{ $puq_pm_ssh_public_key->name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Show Password Once -->
    <div class="col-md-2 d-flex">
        <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="show_password_once"
                   name="show_password_once" {{ $show_password_once ? 'checked' : '' }}>
            <label class="form-check-label" for="show_password_once">
                Show Password Once
            </label>
        </div>
    </div>

    <!-- Backup Storage -->
    <div class="col-md-2">
        <label for="backup_storage_name" class="form-label">Backup Storage</label>
        <select class="form-select" id="backup_storage_name" name="backup_storage_name">
            @foreach($backup_storages as $backup_storage)
                <option value="{{ $backup_storage->name }}"
                    {{ $backup_storage_name == $backup_storage->name ? 'selected' : '' }}>
                    {{ $backup_storage->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>


<!-- Instance Info Section -->
<div class="row g-3 mt-3">

    <div id="deploy_status_col" class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4">
        <div id="deploy_status">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold mb-0">Deploy Status</h5>
                        <span id="ds_status_badge" class="badge bg-secondary d-inline-flex align-items-center gap-1">
                            <span id="ds_status_text">pending</span>
                            <span id="ds_spinner" class="spinner-border spinner-border-sm d-none"></span>
                        </span>
                    </div>

                    <div class="mb-3">
                        <div class="progress" style="height: 8px;">
                            <div id="ds_progress" class="progress-bar" role="progressbar"></div>
                        </div>
                    </div>

                    <div class="row text-muted small">
                        <div class="col-6">
                            <div>Started: <span id="ds_started">-</span></div>
                            <div>Finished: <span id="ds_finished">-</span></div>
                        </div>
                        <div class="col-6 text-end">
                            <div>Duration: <span id="ds_duration">-</span></div>
                            <div>Step: <span id="ds_step">-</span></div>
                        </div>
                    </div>

                    <div class="text-danger fw-bold small mt-1" id="ds_error"></div>

                    <div class="mt-3">
                        <a href="javascript:void(0)" id="toggle_log" class="small text-decoration-none">
                            ▶ Show log
                        </a>
                    </div>

                    <pre id="deploy_log_box"
                         class="p-2 bg-dark text-light rounded mt-2"
                         style="display:none; height:220px; overflow-y:auto; white-space:pre-wrap; font-size:12px;">
                    </pre>

                </div>
            </div>

        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">

                <h5 class="fw-bold mb-3">Script Execution</h5>

                @foreach($lxc_instance->getScriptLogList() as $script)
                    @php
                        $log = $script['puq_pm_script_logs'] ?? null;
                        $status = $log['status'] ?? 'unknown';
                        $badge = $status === 'success' ? 'bg-success'
                               : ($status === 'error' ? 'bg-danger' : 'bg-secondary');
                    @endphp

                    <div class="border rounded p-2 mb-2">

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-truncate" style="max-width: 250px;">
                                    {{ $script['puq_pm_script_type'] }}
                                </div>
                                <small class="text-muted d-block text-truncate" style="max-width: 250px;">
                                    {{ class_basename($script['related_model_class']) }}
                                    - {{ $script['related_model_name'] }}
                                </small>
                            </div>

                            <span class="badge {{ $badge }}">
                        {{ $status }}
                    </span>
                        </div>

                        @if($log)
                            <div class="d-flex justify-content-between align-items-center small mt-2">

                                <div class="text-muted">
                                    Duration: <strong>{{ $log['duration'] }}s</strong><br>
                                    Created: <strong>{{ $log['created_at']->format('Y-m-d H:i') }}</strong>
                                </div>

                                <button class="btn btn-sm btn-outline-primary btn-icon-only view-script"
                                        style="white-space: nowrap;"
                                        data-url="{{ route('admin.api.Product.puqProxmox.puq_pm_lxc_instance.script_log.get',['uuid'=> $lxc_instance->uuid, 'log_uuid'=>$log['uuid']]) }}">
                                    <i class="fas fa-eye"></i>
                                </button>

                            </div>
                        @endif

                    </div>
                @endforeach

            </div>
        </div>

    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4">
        <div class="card border-success shadow-sm">
            <div class="card-header bg-success text-white d-flex align-items-center">
                <i class="fas fa-server me-2"></i> LXC Status
            </div>
            <div class="card-body">
                <button id="reboot" type="button"
                        class="btn btn-outline-danger btn-sm d-flex align-items-center mb-2">
                    <i class="fa fa-sync me-1"></i> {{__('Product.puqProxmox.Reboot')}}
                </button>

                <p><strong>Name:</strong> {{ $lxc_instance_status['name'] ?? 'n/a' }}</p>
                <p><strong>VMID:</strong> {{ $lxc_instance_status['vmid'] ?? 'n/a' }}</p>
                <p><strong>Status:</strong>
                    <span class="badge bg-{{ $lxc_instance_status['status_btn'] ?? 'secondary' }}">
                        {{ ucfirst($lxc_instance_status['status'] ?? 'n/a') }}
                    </span>
                </p>
                <p><i class="fas fa-memory me-1"></i> Memory: {{ $lxc_instance_status['memory'] ?? 'n/a' }} %</p>
                <p><i class="fas fa-hdd me-1"></i> Disk: {{ $lxc_instance_status['disk'] ?? 'n/a' }} %</p>
                <p><i class="fas fa-microchip me-1"></i> CPU: {{ $lxc_instance_status['cpu'] ?? 'n/a' }} %</p>
                <p><i class="fas fa-network-wired me-1"></i> Net In: {{ $lxc_instance_status['netin'] ?? 'n/a' }} /
                    Out: {{ $lxc_instance_status['netout'] ?? 'n/a' }}</p>
                <p><i class="fas fa-clock me-1"></i> Uptime: {{ $lxc_instance_status['uptime'] ?? 'n/a' }}</p>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4">
        <div class="card border-primary shadow-sm">
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i> LXC Info
            </div>
            <div class="card-body">

                <div class="d-flex align-items-center mb-2">
                    <img src="{{ $lxc_instance_location['icon_url'] ?? '' }}" alt="Location" width="24" class="me-2">
                    <strong>{{ $lxc_instance_location['name'] ?? 'n/a' }}</strong>
                    <span class="text-muted ms-2">ID: {{ $lxc_instance_location['data_center'] ?? 'n/a' }}</span>
                </div>

                <p><i class="fas fa-microchip me-1"></i> Cores: {{ $lxc_instance_info['cores'] ?? 'n/a' }}</p>
                <p><i class="fas fa-memory me-1"></i> RAM: {{ $lxc_instance_info['ram'] ?? 'n/a' }}</p>
                <p><i class="fas fa-hdd me-1"></i> Main Disk: {{ $lxc_instance_info['main_disk'] ?? 'n/a' }}</p>
                <p><i class="fas fa-hdd me-1"></i> Additional Disk: {{ $lxc_instance_info['addition_disk'] ?? 'n/a' }}
                </p>
                <p><i class="fas fa-server me-1"></i> Backups: {{ $lxc_instance_info['backups'] ?? 'n/a' }}</p>
                <p><i class="fas fa-globe me-1"></i> Domain: {{ $lxc_instance_info['domain'] ?? 'n/a' }}</p>
                <p><i class="fas fa-network-wired me-1"></i>IPv4: {{ $lxc_instance_info['ipv4'] ?? 'n/a' }}</p>

            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        let stopUpdates = false;
        let logVisible = false;
        let previousStatus = null;

        const originalDeployCol = "col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4";

        const toggleBtn = document.getElementById("toggle_log");
        const logBox = document.getElementById("deploy_log_box");
        const mainCol = document.getElementById("deploy_status_col");
        const statusText = document.getElementById("ds_status_text");
        const spinner = document.getElementById("ds_spinner");

        toggleBtn.addEventListener("click", function () {
            logVisible = !logVisible;
            logBox.style.display = logVisible ? "block" : "none";
            toggleBtn.textContent = logVisible ? "▼ Hide log" : "▶ Show log";
            mainCol.className = logVisible ? "col-12" : originalDeployCol;
            if (logVisible) {
                setTimeout(() => {
                    logBox.scrollTop = logBox.scrollHeight;
                }, 50);
            }
        });

        function updateDeployStatus(status) {
            statusText.textContent = status;
            if (status === "running") {
                spinner.classList.remove("d-none");
            } else {
                spinner.classList.add("d-none");
            }
        }

        function loadDeployCard() {
            if (stopUpdates) return;

            PUQajax("{{ route('admin.api.Product.puqProxmox.puq_pm_lxc_instance.deploy_status.get', $lxc_instance->uuid) }}", {}, 1500, null, 'GET')
                .then(res => {
                    let d = res.data;
                    if (!d) return;

                    const map = {
                        pending: "secondary",
                        running: "info",
                        success: "success",
                        failed: "danger",
                        canceled: "warning"
                    };

                    const badge = document.getElementById("ds_status_badge");
                    badge.className = "badge bg-" + (map[d.status] ?? "secondary") + " d-inline-flex align-items-center gap-1";

                    updateDeployStatus(d.status);
                    document.getElementById("ds_progress").style.width = d.progress + "%";
                    document.getElementById("ds_started").textContent = d.started_at ?? "-";
                    document.getElementById("ds_finished").textContent = d.finished_at ?? "-";

                    if (d.started_at && d.finished_at) {
                        let s = new Date(d.started_at), f = new Date(d.finished_at);
                        document.getElementById("ds_duration").textContent = Math.floor((f - s) / 1000) + " sec";
                    } else {
                        document.getElementById("ds_duration").textContent = "-";
                    }

                    document.getElementById("ds_step").textContent = d.current_step ?? "-";
                    document.getElementById("ds_error").textContent = d.error ?? "";
                    logBox.textContent = d.logs ?? "";
                    if (logVisible) logBox.scrollTop = logBox.scrollHeight;

                    if (d.status === "success" && previousStatus && previousStatus !== "success") {
                        location.reload();
                    }

                    if (d.status === "success" || ["failed", "canceled"].includes(d.status)) {
                        stopUpdates = true;
                    }

                    previousStatus = d.status;
                });
        }


        setInterval(loadDeployCard, 1500);
        loadDeployCard();


        {{--$('#put_web_dns_records').on('click', function () {--}}
        {{--    PUQajax('{{route('admin.api.Product.puqProxmox.puq_pm_lxc_instance.dns_records.put',$lxc_instance->uuid)}}', {}, 5000, $(this), 'PUT', null);--}}
        {{--});--}}

        $('#reboot').on('click', function () {
            PUQajax('{{route('admin.api.Product.puqProxmox.puq_pm_lxc_instance.reboot.put',$lxc_instance->uuid)}}', {}, 5000, $(this), 'PUT', null);
        });

        function escapeHtml(text) {
            return $('<div/>').text(text).html();
        }

        $(document).on('click', '.view-script', function (e) {
            e.preventDefault();
            var url = $(this).data('url');

            PUQajax(url, null, 500, $(this), 'GET', null)
                .then(function (response) {
                    showScriptLog(response.data);
                })
                .fail(function (err) {
                    console.error('Error loading log:', err);
                });
        });

        function showScriptLog(data) {
            var $modal = $('#universalModal');
            var $modalTitle = $modal.find('.modal-title');
            var $modalBody = $modal.find('.modal-body');

            $modal.find('#modalSaveButton').remove();
            $modal.find('.modal-dialog').css({'max-width': '90%', 'width': '90%'});
            $modalTitle.text('Script Log');

            var statusColor = (data.status === 'success') ? 'bg-success' :
                (data.status === 'error') ? 'bg-danger' :
                    'bg-secondary';

            var html = ''
            html += '<div class="mb-3">';
            html += '  <div class="d-flex justify-content-between align-items-center">';
            html += '    <div><strong>Status:</strong> <span class="badge ' + statusColor + '">' + data.status + '</span></div>';
            html += '    <div><strong>Duration:</strong> ' + data.duration + 's</div>';
            html += '  </div>';
            html += '  <div><strong>Created:</strong> ' + formatDateWithoutTimezone(data.created_at) + '</div>';
            html += '</div>';

            html += '<hr>';

            html += '<h6 class="fw-bold">Input Script</h6>';
            html += '<pre class="bg-dark text-white p-3 rounded" style="font-size:13px; max-height:350px; overflow:auto;">' + escapeHtml(data.input) + '</pre>';

            html += '<h6 class="fw-bold mt-3">Output</h6>';
            html += '<pre id="log-output-block" class="bg-light p-3 rounded" style="font-size:13px; max-height:350px; overflow:auto;">' + escapeHtml(data.output) + '</pre>';

            if (data.errors && data.errors.length > 0) {
                html += '<h6 class="fw-bold mt-3 text-danger">Errors</h6>';
                html += '<pre class="bg-danger text-white p-3 rounded" style="font-size:13px;">' + escapeHtml(JSON.stringify(data.errors, null, 2)) + '</pre>';
            }

            $modalBody.html(html);

            setTimeout(function () {
                var $output = $('#log-output-block');
                if ($output.length) {
                    $output.scrollTop($output[0].scrollHeight);
                }
            }, 200);

            $modal.modal('show');
        }

        function formatDateWithoutTimezone(datetime) {
            if (!datetime) return '-';
            var date = new Date(datetime);
            return date.toLocaleString();
        }

    });
</script>
