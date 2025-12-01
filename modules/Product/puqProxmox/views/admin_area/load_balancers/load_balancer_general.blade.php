@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('header')

@endsection

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-warning"
            id="rebalance">
        <i class="fa fa-sync-alt"></i>
        {{ __('Product.puqProxmox.Rebalance') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger"
            data-model-url="{{ route('admin.api.Product.puqProxmox.load_balancer.delete', $uuid) }}"
            id="deleteLoadBalancer">
        <i class="fa fa-trash-alt"></i>
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.load_balancers.load_balancer_header')

    <div id="container">
        <div class="card mb-3">
            <div class="card-body">
                <form id="loadBalancerForm" method="POST" action="" novalidate="novalidate">
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="name" class="form-label">
                                <i class="fa fa-server me-1"></i>
                                {{ __('Product.puqProxmox.Name') }}
                            </label>
                            <input type="text" class="form-control" id="name" name="name"
                                   placeholder="My Load Balancer">
                        </div>

                        <!-- Cluster Group -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="puq_pm_cluster_group_uuid" class="form-label">
                                <i class="fa fa-object-group me-1"></i>
                                {{ __('Product.puqProxmox.Cluster Group') }}
                            </label>
                            <select name="puq_pm_cluster_group_uuid" id="puq_pm_cluster_group_uuid"
                                    class="form-select"></select>
                        </div>

                        <!-- Subdomain + DNS Zone -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label">
                                <i class="fa fa-globe me-1"></i>
                                {{ __('Product.puqProxmox.Domain') }}
                            </label>
                            <div class="d-flex align-items-center">
                                <input type="text"
                                       class="form-control me-1"
                                       id="subdomain"
                                       name="subdomain"
                                       style="text-align: right; direction: ltr; width: 150px;">
                                <span class="mx-1">.</span>
                                <select name="puq_pm_dns_zone_uuid" id="puq_pm_dns_zone_uuid"
                                        class="form-select select2"></select>
                            </div>
                        </div>

                        <!-- DNS TTL -->
                        <div class="col-12 col-md-6 col-lg-2">
                            <label for="dns_record_ttl" class="form-label">
                                <i class="fa fa-clock me-1"></i>
                                {{ __('Product.puqProxmox.DNS Records TTL') }}
                            </label>
                            <input type="number" min="30" step="1" class="form-control" id="dns_record_ttl"
                                   name="dns_record_ttl">
                        </div>
                    </div>

                    <!-- Threshold Builder -->
                    <hr>
                    <h5 class="mt-4"><i
                            class="fa fa-tachometer-alt me-1"></i> {{ __('Product.puqProxmox.Threshold Settings') }}
                    </h5>
                    <div id="thresholds-builder" class="row g-3 mt-2"></div>
                    <input type="hidden" name="default_thresholds" id="default_thresholds">
                </form>
            </div>
        </div>

        <div class="main-card mb-3 card">
            <div class="card-body">
                <table style="width: 100%;" id="web_proxies" class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Enable') }}</th>
                        <th>{{ __('Product.puqProxmox.CPU Load (1 min)') }}</th>
                        <th>{{ __('Product.puqProxmox.CPU Load (5 min)') }}</th>
                        <th>{{ __('Product.puqProxmox.CPU Load (15 min)') }}</th>
                        <th>{{ __('Product.puqProxmox.Memory Free (MB)') }}</th>
                        <th>{{ __('Product.puqProxmox.Memory Free (%)') }}</th>
                        <th>{{ __('Product.puqProxmox.Uptime (sec)') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
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

            const thresholdFields = [
                {key: 'cpu_used_load1', label: 'CPU Load (1 min)', unit: '%', logic: 'Value ↑ Trigger'},
                {key: 'cpu_used_load5', label: 'CPU Load (5 min)', unit: '%', logic: 'Value ↑ Trigger'},
                {key: 'cpu_used_load15', label: 'CPU Load (15 min)', unit: '%', logic: 'Value ↑ Trigger'},
                {key: 'memory_free_megabyte', label: 'Memory Free (MB)', unit: 'MB', logic: 'Value ↓ Trigger'},
                {key: 'memory_free_percent', label: 'Memory Free (%)', unit: '%', logic: 'Value ↓ Trigger'},
                {key: 'uptime', label: 'Uptime (sec)', unit: 's', logic: 'Value ↓ Trigger'},
            ];

            function renderThresholdBuilder(data) {
                const container = $("#thresholds-builder");
                container.empty();

                thresholdFields.forEach(item => {
                    const t = data[item.key] || {enabled: false, value: ''};

                    const html = `
<div class="col-12 col-md-6 col-lg-2">
  <div class="border rounded p-2 text-center">
    <div class="form-check form-switch mb-1">
      <input class="form-check-input threshold-enable" type="checkbox" data-key="${item.key}" ${t.enabled ? 'checked' : ''}>
      <label class="form-check-label small fw-bold">${item.label}</label>
    </div>
    <div class="input-group justify-content-center">
      <input type="number" class="form-control form-control-sm threshold-value" data-key="${item.key}" value="${t.value ?? ''}" min="0" style="width:60px" ${t.enabled ? '' : 'disabled'}>
      <span class="input-group-text small">${item.unit}</span>
    </div>
       <div class="threshold-logic text-muted small mt-1">${item.logic}</div>
  </div>
</div>
`;
                    container.append(html);
                });

                updateThresholdsJSON();
            }

            function updateThresholdsJSON() {
                const result = {};
                thresholdFields.forEach(item => {
                    const enabled = $(`.threshold-enable[data-key="${item.key}"]`).is(':checked');
                    const value = parseFloat($(`.threshold-value[data-key="${item.key}"]`).val()) || 0;
                    result[item.key] = {enabled, value};
                });
                $("#default_thresholds").val(JSON.stringify(result));
            }

            $(document).on('change', '.threshold-enable', function () {
                const key = $(this).data('key');
                const input = $(`.threshold-value[data-key="${key}"]`);
                if ($(this).is(':checked')) input.removeAttr('disabled');
                else input.attr('disabled', true);
                updateThresholdsJSON();
            });

            $(document).on('input', '.threshold-value', function () {
                updateThresholdsJSON();
            });

            function loadFormData() {
                blockUI('container');

                PUQajax('{{ route('admin.api.Product.puqProxmox.load_balancer.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#subdomain").val(response.data?.subdomain);
                        $("#dns_record_ttl").val(response.data?.dns_record_ttl);

                        initializeSelect2(
                            $("#puq_pm_cluster_group_uuid"),
                            '{{ route('admin.api.Product.puqProxmox.cluster_groups.select.get') }}',
                            response.data?.puq_pm_cluster_group_data,
                            'GET',
                            1000,
                            {}
                        );

                        initializeSelect2(
                            $("#puq_pm_dns_zone_uuid"),
                            '{{ route('admin.api.Product.puqProxmox.dns_zones.forward.select.get') }}',
                            response.data?.puq_pm_dns_zone_data,
                            'GET',
                            1000,
                            {}
                        );

                        renderThresholdBuilder(response.data?.default_thresholds || {});

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#loadBalancerForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqProxmox.load_balancer.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                        $dataTable.ajax.reload(null, false);
                    });
            });

            $('#deleteLoadBalancer').on('click', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 50, $(this), 'DELETE');
                }
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                $('#modalSaveButton').show();
            });


            $("#rebalance").on("click", function (event) {

                PUQajax('{{ route('admin.api.Product.puqProxmox.load_balancer.rebalance.put', $uuid) }}', null, 1000, $(this), 'PUT', null)
                    .then(function (response) {
                        loadFormData();
                        $dataTable.ajax.reload(null, false);
                    });
            });


            loadFormData();

            var tableId = '#web_proxies';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.load_balancer.status.web_proxies.get', $uuid) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function(data, type, row) {
                        let ipBadges = '';
                        if (row.ip_dns_records && row.ip_dns_records.length) {
                            row.ip_dns_records.forEach(record => {
                                const colorClass = record.propagated ? 'text-bg-success rounded-pill' : 'text-bg-danger rounded-pill';
                                ipBadges += `<div class="mb-1"><span class="badge ${colorClass}">${record.ip}</span></div>`;
                            });
                        }

                        return `
            <div>
                <div class="fw-bold">${data}</div>
                <div class="mt-1">${ipBadges}</div>
            </div>
        `;
                    }
                }
                ,
                {
                    data: 'disable',
                    render: function (data) {
                        return renderStatus(data);
                    }
                },
                {
                    data: "cpu_used_load1",
                    render: function (data, type, row) {
                        return renderMetric(row.cpu_used_load1);
                    }
                },
                {
                    data: "cpu_used_load5",
                    render: function (data, type, row) {
                        return renderMetric(row.cpu_used_load5);
                    }
                },
                {
                    data: "cpu_used_load15",
                    render: function (data, type, row) {
                        return renderMetric(row.cpu_used_load15);
                    }
                },
                {
                    data: "memory_free_megabyte",
                    render: function (data, type, row) {
                        return renderMetric(row.memory_free_megabyte);
                    }
                },
                {
                    data: "memory_free_percent",
                    render: function (data, type, row) {
                        return renderMetric(row.memory_free_percent);
                    }
                },
                {
                    data: "uptime",
                    render: function (data, type, row) {
                        return renderMetric(row.uptime);
                    }
                },
                {
                    data: 'urls',
                    className: "text-center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) btn += renderEditLink(row.urls.edit);
                        return btn;
                    }
                }
            ];


            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                "paging": false,
                "searching": false,
                "ordering": false,
                "info": false,
                "serverSide": false,
                "order": [],
            });

            function DataTableAddData() {
                return {};
            }

            function renderMetric(metric) {
                if (!metric || metric.enabled === false) {
                    return `<div class="text-center">
                    <span class="fs-6 fw-bold text-dark d-block">${metric.value}</span>
                    <span class="badge text-bg-secondary rounded-pill">—</span>
                </div>`;
                }

                const colorClass = metric.triggered
                    ? 'text-bg-danger rounded-pill'
                    : 'text-bg-success rounded-pill';

                return `
        <div class="text-center">
            <span class="fs-5 fw-bold text-dark d-block">${metric.value}</span>
            <span class="badge ${colorClass} mt-1">
                ${metric.logic} ${metric.threshold}
            </span>
        </div>
    `;
            }

            function reloadTable(){
                $dataTable.ajax.reload(null, false)
            }
            setInterval(reloadTable, 5000);

        });
    </script>
@endsection
