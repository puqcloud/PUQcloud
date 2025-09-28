@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"
            id="refreshClusterInfo">
        <i class="fa fa-sync-alt"></i> {{ __('Product.puqProxmox.Refresh Cluster Info') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
            id="addAccessServer">
        <i class="fa fa-plus"></i> <i class="fa fa-server"></i> {{ __('Product.puqProxmox.Add Access Server') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger"
            data-model-url="{{ route('admin.api.Product.puqProxmox.cluster.delete', $uuid) }}"
            id="deleteCluster">
        <i class="fa fa-trash-alt"></i>
    </button>

@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.clusters.cluster_header')

    <div id="container">
        <div class="card mb-3">
            <div class="card-body">
                <form id="clusterForm" method="POST" action="" novalidate="novalidate">
                    <div class="row">
                        <div class="col-12">
                            <div id="test_connection_data"></div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-3">
                            <label for="name" class="form-label">{{ __('Product.puqProxmox.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
                            <label for="puq_pm_cluster_group_uuid" class="form-label">
                                {{ __('Product.puqProxmox.Cluster Group') }}
                            </label>
                            <select name="puq_pm_cluster_group_uuid" id="puq_pm_cluster_group_uuid"
                                    class="form-select mb-2 form-control"></select>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-2 col-xxl-2 mb-3">
                            <label class="form-label" for="default">{{ __('Product.puqProxmox.Default') }}</label>
                            <div>
                                <input type="checkbox"
                                       data-toggle="toggle"
                                       id="default"
                                       name="default"
                                       data-off="{{ __('Product.puqProxmox.No') }}"
                                       data-on="{{ __('Product.puqProxmox.Yes') }}"
                                       data-onstyle="success"
                                       data-offstyle="danger">
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-1 col-xxl-1 mb-3">
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

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-3">
                            <label for="max_accounts"
                                   class="form-label">{{ __('Product.puqProxmox.Max Accounts') }}</label>
                            <input type="number" class="form-control" id="max_accounts" name="max_accounts" min="0"
                                   value="0">
                        </div>
                    </div>
                    <div class="border p-3 rounded">
                        <div class="row g-3 align-items-end">
                            <!-- VNC WEB Proxy Domain -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                                <label for="vncwebproxy_domain"
                                       class="form-label">{{ __('Product.puqProxmox.VNC WEB Proxy Domain') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">https://</span>
                                    <input type="text" class="form-control" id="vncwebproxy_domain"
                                           name="vncwebproxy_domain">
                                </div>
                            </div>

                            <!-- VNC WEB Proxy API Key -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                                <label for="vncwebproxy_api_key"
                                       class="form-label">{{ __('Product.puqProxmox.VNC WEB Proxy API Key') }}</label>
                                <input type="text" class="form-control" id="vncwebproxy_api_key"
                                       name="vncwebproxy_api_key">
                            </div>

                            <!-- Test Button -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3">
                                <button type="button" class="btn-icon btn-outline-2x btn btn-outline-secondary"
                                        id="vncwebproxy_test_connection">
                                    <i class="fas fa-terminal me-1"></i> {{ __('Product.puqProxmox.Test') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="clusterInfo">

            <div id="cluster_alert" class="alert alert-warning text-center" style="display: none;">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                {{ __('Product.puqProxmox.No cluster or server information available') }}
            </div>

            <div id="cluster_info_card" class="card mb-3 shadow-sm border-0" style="display: none;">

                <div class="card-header d-flex align-items-center text-white p-3"
                     style="background: linear-gradient(90deg, #0d6efd, #0dcaf0); border-radius: .5rem .5rem 0 0;">
                    <i class="fa-solid fa-server me-2 fa-lg"></i>
                    <strong id="cluster" class="me-2"></strong>
                    <span class="badge bg-dark text-white me-2">{{ __('Product.puqProxmox.Version') }}: <span
                            id="version"></span></span>
                    <span
                        class="badge bg-success ms-auto fs-6 py-1 px-2">{{ __('Product.puqProxmox.Quorate') }}: <strong
                            id="quorate"></strong></span>
                </div>

                <div class="card-body">
                    <div class="row justify-content-center text-start">
                        <!-- Nodes -->
                        <div class="col-md-3 d-flex justify-content-center">
                            <div class="d-flex align-items-start">
                                <div class="text-center me-3" style="line-height: 1;">
                                    <div class="fw-bold">{{ __('Product.puqProxmox.Nodes') }}</div>
                                    <i class="fa-solid fa-network-wired fa-2x text-secondary d-block"
                                       style="margin-top: 0; padding-top: 0; line-height: 1;"></i>
                                </div>
                                <div>
                                    <div class="text-success">{{ __('Product.puqProxmox.Online') }}: <span
                                            id="node_online"></span></div>
                                    <div class="text-danger">{{ __('Product.puqProxmox.Offline') }}: <span
                                            id="node_offline"></span></div>
                                </div>
                            </div>
                        </div>
                        <!-- VMs -->
                        <div class="col-md-3 d-flex justify-content-center">
                            <div class="d-flex align-items-start">
                                <div class="text-center me-3" style="line-height: 1;">
                                    <div class="fw-bold">{{ __('Product.puqProxmox.VMs') }}</div>
                                    <i class="fa-solid fa-desktop fa-2x text-secondary d-block"
                                       style="margin-top: 0; padding-top: 0; line-height: 1;"></i>
                                </div>
                                <div>
                                    <div class="text-success">{{ __('Product.puqProxmox.Running') }}: <span
                                            id="wm_running"></span></div>
                                    <div class="text-muted">{{ __('Product.puqProxmox.Stopped') }}: <span
                                            id="wm_stopped"></span></div>
                                </div>
                            </div>
                        </div>
                        <!-- LXC -->
                        <div class="col-md-3 d-flex justify-content-center">
                            <div class="d-flex align-items-start">
                                <div class="text-center me-3" style="line-height: 1;">
                                    <div class="fw-bold">{{ __('Product.puqProxmox.LXC') }}</div>
                                    <i class="fa-solid fa-cube fa-2x text-secondary d-block"
                                       style="margin-top: 0; padding-top: 0; line-height: 1;"></i>
                                </div>
                                <div>
                                    <div class="text-success">{{ __('Product.puqProxmox.Running') }}: <span
                                            id="lxc_running"></span></div>
                                    <div class="text-muted">{{ __('Product.puqProxmox.Stopped') }}: <span
                                            id="lxc_stopped"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-card mb-3 card">
            <div class="card-body">
                <div class="mb-4">
                    <h3 class="fw-bold">{{ __('Product.puqProxmox.Access Servers') }}</h3>
                    <p class="text-muted">
                        {{ __('Product.puqProxmox.These are the servers that have access to manage the Proxmox cluster. To increase reliability, add multiple nodes as access servers. In case one server fails, the system will use the remaining servers to maintain control') }}
                    </p>
                </div>
                <table style="width: 100%;" id="accessServers" class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.API Host') }}</th>
                        <th>{{ __('Product.puqProxmox.API Token ID') }}</th>
                        <th>{{ __('Product.puqProxmox.SSH Host') }}</th>
                        <th>{{ __('Product.puqProxmox.SSH Username') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.API Host') }}</th>
                        <th>{{ __('Product.puqProxmox.API Token ID') }}</th>
                        <th>{{ __('Product.puqProxmox.SSH Host') }}</th>
                        <th>{{ __('Product.puqProxmox.SSH Username') }}</th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
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

                PUQajax('{{ route('admin.api.Product.puqProxmox.cluster.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);

                        $("#disable").prop('checked', !!response.data?.disable).trigger('click');
                        $("#default").prop('checked', !!response.data?.default).trigger('click');

                        $("#vncwebproxy_domain").val(response.data?.vncwebproxy_domain);
                        $("#vncwebproxy_api_key").val(response.data?.vncwebproxy_api_key);

                        initializeSelect2(
                            $("#puq_pm_cluster_group_uuid"),
                            '{{ route('admin.api.Product.puqProxmox.cluster_groups.select.get') }}',
                            response.data?.puq_pm_cluster_group_data,
                            'GET',
                            1000,
                            {}
                        );

                        if (!response.data?.description) {
                            $("#cluster_alert").show();
                        }

                        if (response.data?.description) {
                            const desc = response.data?.description;

                            $("#cluster_alert").hide();
                            $("#cluster_info_card").show();

                            $("#cluster").text(desc.cluster || '—');
                            $("#version").text(desc.version || '—');

                            $("#quorate").text(desc.quorate || '—');

                            $("#node_online").text(desc.nodes?.online ?? '—');
                            $("#node_offline").text(desc.nodes?.offline ?? '—');

                            $("#wm_running").text(desc.vms?.running ?? '—');
                            $("#wm_stopped").text(desc.vms?.stopped ?? '—');

                            $("#lxc_running").text(desc.lxc?.running ?? '—');
                            $("#lxc_stopped").text(desc.lxc?.stopped ?? '—');
                        }

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            var tableId = '#accessServers';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.cluster.access_servers.get', $uuid) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `
                            <div>
                                <strong>${row.name}</strong><br>
                                <small class="text-muted">${row.description ?? ''}</small>
                            </div>
                        `;
                    }
                },
                {
                    data: "api_host",
                    name: "api_host",
                    render: function (data, type, row) {
                        return `${row.api_host}:${row.api_port} ${formatResponseTime(row.api_response_time)}`;
                    }
                },
                {data: "api_token_id", name: "api_token_id"},
                {
                    data: "ssh_host",
                    name: "ssh_host",
                    render: function (data, type, row) {
                        return `${row.ssh_host}:${row.ssh_port} ${formatResponseTime(row.ssh_response_time)}`;
                    }
                },
                {data: "ssh_username", name: "ssh_username"},
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.test_connection) {
                            btn += `
                                <button type="button"
                                        class="test-connection-btn mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary"
                                        data-model-url="${row.urls.test_connection}">
                                    <i class="fa fa-plug"></i> {{ __('Product.puqProxmox.Test Connection') }}
                            </button>
`;
                        }
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#clusterForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqProxmox.cluster.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $('#addAccessServer').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');

                $modalTitle.text('{{ __('Product.puqProxmox.Create') }}');

                var formHtml = `
                    <form id="createForm" class="col-md-10 mx-auto">
                        <input type="hidden" class="form-control" id="puq_pm_cluster_uuid" name="puq_pm_cluster_uuid" value="{{ $uuid }}">

                        <div class="mb-3">
                            <label class="form-label" for="name">{{ __('Product.puqProxmox.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="ex: node-01" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="description">{{ __('Product.puqProxmox.Description') }}</label>
                            <input type="text" class="form-control" id="description" name="description" placeholder="ex: Main Proxmox node">
                        </div>

                        <hr>
                        <div class="mb-3">
                            <label class="form-label" for="api_host">{{ __('Product.puqProxmox.API Host') }}</label>
                            <input type="text" class="form-control" id="api_host" name="api_host" placeholder="ex: 192.168.1.10" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_port">{{ __('Product.puqProxmox.API Port') }}</label>
                            <input type="number" class="form-control" id="api_port" name="api_port" value="8006" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_token_id">{{ __('Product.puqProxmox.API Token ID') }}</label>
                            <input type="text" class="form-control" id="api_token_id" name="api_token_id" placeholder="ex: root@pam!puqcloud" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_token">{{ __('Product.puqProxmox.API Token') }}</label>
                            <input type="password" class="form-control" id="api_token" name="api_token" placeholder="••••••••" autocomplete="api-token" required>
                        </div>
                        <hr>

                        <div class="mb-3">
                            <label class="form-label" for="ssh_host">{{ __('Product.puqProxmox.SSH Host') }}</label>
                            <input type="text" class="form-control" id="ssh_host" name="ssh_host" placeholder="ex: 192.168.1.10" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="ssh_port">{{ __('Product.puqProxmox.SSH Port') }}</label>
                            <input type="number" class="form-control" id="ssh_port" name="ssh_port" value="22" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="ssh_username">{{ __('Product.puqProxmox.SSH Username') }}</label>
                            <input type="text" class="form-control" id="ssh_username" name="ssh_username" placeholder="ex: root" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="ssh_password">{{ __('Product.puqProxmox.SSH Password') }}</label>
                            <input type="password" class="form-control" id="ssh_password" name="ssh_password" placeholder="••••••••" autocomplete="new-password" required>
                        </div>

                    </form>
                `;

                $modalBody.html(formHtml);
                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.Product.puqProxmox.access_server.post') }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                            loadFormData();
                        });
                }
            });

            $dataTable.on('click', 'button.test-connection-btn', function (e) {
                e.preventDefault();

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');

                $modalSaveButton.data('modelUrl', modelUrl).hide(); // hide Save button

                $modalTitle.text('{{ __('Product.puqProxmox.Test Connection') }}');

                const formHtml = `
                    <div class="mb-3" id="test_connection">
                        <div class="text-center my-3">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                `;
                $modalBody.html(formHtml);
                $('#universalModal').modal('show');

                PUQajax(modelUrl, {}, 500, $(this), 'GET')
                    .then(function (response) {
                        if (response.status === 'success' && response.data) {
                            let data = response.data;
                            let isCluster = data.cluster && data.cluster !== 'Not in cluster';

                            let html = `
                                <div class="alert alert-success">
                                    <i class="fa fa-check-circle me-1 text-success"></i> {{ __('Product.puqProxmox.Successfully') }}
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between border rounded p-2 bg-light mb-2">
                                    <div><i class="fa fa-cogs me-1"></i> {{ __('Product.puqProxmox.API Response Time') }}:</div>
                                        <div><strong>${formatResponseTime(data.api_response_time)}</strong></div>
                                     </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between border rounded p-2 bg-light mb-2">
                                    <div><i class="fa fa-cogs me-1"></i> {{ __('Product.puqProxmox.SSH Response Time') }}:</div>
                                        <div><strong>${formatResponseTime(data.ssh_response_time)}</strong></div>
                                     </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between border rounded p-2 bg-light mb-2">
                                    <div><i class="fa fa-cogs me-1"></i> {{ __('Product.puqProxmox.Version') }}:</div>
                                        <div><strong>${data.version}</strong></div>
                                     </div>
                                </div>
                            </div>
`;

                            if (!isCluster) {
                                html += `
                                    <div class="alert alert-info">
                                        <i class="fa fa-server me-2"></i> <strong>{{ __('Product.puqProxmox.Standalone Server') }}</strong> – {{ __('Product.puqProxmox.this node is not part of a cluster') }}
                                </div>
`;
                            } else {
                                html += `
                                    <div class="mb-4">
                                        <h5><i class="fa fa-project-diagram me-2"></i>{{ __('Product.puqProxmox.Cluster Info') }}</h5>
                                        <div class="d-flex justify-content-between border rounded p-2 bg-light mb-2">
                                            <div><i class="fa fa-network-wired me-1"></i> {{ __('Product.puqProxmox.Cluster') }}:</div>
                                            <div><strong>${data.cluster}</strong></div>
                                        </div>
                                        <div class="d-flex justify-content-between border rounded p-2 bg-light">
                                            <div><i class="fa fa-shield-alt me-1"></i> {{ __('Product.puqProxmox.') }}Quorate:</div>
                                            <div><strong>${data.quorate}</strong></div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h5><i class="fa fa-server me-2"></i>Nodes</h5>
                                        <div class="d-flex justify-content-between border rounded p-2 bg-success bg-opacity-10 mb-2">
                                            <div><i class="fa fa-check-circle text-success me-1"></i> {{ __('Product.puqProxmox.Online') }}:</div>
                                            <div><strong>${data.nodes.online}</strong></div>
                                        </div>
                                        <div class="d-flex justify-content-between border rounded p-2 bg-danger bg-opacity-10">
                                            <div><i class="fa fa-times-circle text-danger me-1"></i> {{ __('Product.puqProxmox.Offline') }}:</div>
                                            <div><strong>${data.nodes.offline}</strong></div>
                                        </div>
                                    </div>
                                `;
                            }

                            html += `
                                <div class="mb-4">
                                    <h5><i class="fa fa-desktop me-2"></i>{{ __('Product.puqProxmox.') }}Virtual Machines</h5>
                                    <div class="d-flex justify-content-between border rounded p-2 bg-success bg-opacity-10 mb-2">
                                        <div><i class="fa fa-play-circle text-success me-1"></i> {{ __('Product.puqProxmox.Running') }}:</div>
                                        <div><strong>${data.vms.running}</strong></div>
                                    </div>
                                    <div class="d-flex justify-content-between border rounded p-2 bg-secondary bg-opacity-10">
                                        <div><i class="fa fa-stop-circle text-secondary me-1"></i> {{ __('Product.puqProxmox.Stopped') }}:</div>
                                        <div><strong>${data.vms.stopped}</strong></div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h5><i class="fa fa-box me-2"></i>{{ __('Product.puqProxmox.LXC Containers') }}</h5>
                                    <div class="d-flex justify-content-between border rounded p-2 bg-success bg-opacity-10 mb-2">
                                        <div><i class="fa fa-play-circle text-success me-1"></i> {{ __('Product.puqProxmox.Running') }}:</div>
                                        <div><strong>${data.lxc.running}</strong></div>
                                    </div>
                                    <div class="d-flex justify-content-between border rounded p-2 bg-secondary bg-opacity-10">
                                        <div><i class="fa fa-stop-circle text-secondary me-1"></i> {{ __('Product.puqProxmox.Stopped') }}:</div>
                                        <div><strong>${data.lxc.stopped}</strong></div>
                                    </div>
                                </div>
                            `;
                            $('#test_connection').html(html);
                        } else {
                            $('#test_connection').html(`<div class="alert alert-warning">{{ __('Product.puqProxmox.Unexpected format in success response') }}</div>`);
                        }

                        $dataTable.ajax.reload(null, false);
                        loadFormData();
                    })
                    .catch(function (errors) {
                        const html = `
                            <div class="alert alert-danger">
                                <strong>Error:</strong>
                                <ul>
                                    ${errors.map(err => `<li>${err}</li>`).join('')}
                                </ul>
                            </div>
                        `;
                        $('#test_connection').html(html);
                    });
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 1000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $('#deleteCluster').on('click', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 50, null, 'DELETE');
                }
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                $('#modalSaveButton').show();
            });

            $('#refreshClusterInfo').on('click', function () {

                blockUI('container');
                PUQajax('{{ route('admin.api.Product.puqProxmox.sync.cluster.info.get', $uuid) }}', {}, 500, $(this), 'GET')
                    .then(function (response) {
                        unblockUI('container');
                        loadFormData();
                    })
                    .catch(function (error) {
                        unblockUI('container');
                        console.error('Error loading form data:', error);
                    });
            });

            $('#vncwebproxy_test_connection').on('click', function () {
                blockUI('status');
                PUQajax('{{ route('admin.api.Product.puqProxmox.cluster.vncwebproxy_test_connection.get', $uuid) }}', {}, 1000, $(this), 'GET')
                    .then(function (response) {
                        unblockUI('status');

                        if (response.data) {
                            window.open(response.data, 'consoleWindow', 'width=800,height=600');
                        }

                    })
                    .catch(function (error) {
                        unblockUI('status');
                        console.error(error);
                    });
            });

            function formatResponseTime(ms) {
                let sec = (ms / 1000).toFixed(2);
                let color = 'green';

                if (ms > 2001) color = 'red';
                else if (ms > 2000) color = 'orange';

                return `<span style="color: ${color}; font-weight: bold;">
        <i class="fa fa-clock" aria-hidden="true"></i> ${sec}s
    </span>`;
            }

            loadFormData();
        });
    </script>
@endsection
