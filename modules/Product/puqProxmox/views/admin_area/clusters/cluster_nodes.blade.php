@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
@endsection

@section('buttons')
    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"
            id="refreshNodes">
        <i class="fa fa-sync-alt"></i> {{ __('Product.puqProxmox.Refresh Nodes') }}
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.clusters.cluster_header')

    <div id="container">

        <div class="main-card mb-3 card">
            <div class="card-body">
                <table style="width: 100%;" id="nodes" class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Status') }}</th>
                        <th>{{ __('Product.puqProxmox.CPU') }}</th>
                        <th>{{ __('Product.puqProxmox.Memory') }}</th>
                        <th>{{ __('Product.puqProxmox.Uptime') }}</th>
                        <th>{{ __('Product.puqProxmox.Tags') }}</th>
                        <th>{{ __('Product.puqProxmox.Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Status') }}</th>
                        <th>{{ __('Product.puqProxmox.CPU') }}</th>
                        <th>{{ __('Product.puqProxmox.Memory') }}</th>
                        <th>{{ __('Product.puqProxmox.Uptime') }}</th>
                        <th>{{ __('Product.puqProxmox.Tags') }}</th>
                        <th>{{ __('Product.puqProxmox.Actions') }}</th>
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

            var tableId = '#nodes';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.cluster.nodes.get', $uuid) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `<i class="fa fa-server text-primary me-1"></i> ${data}`;
                    }
                },
                {
                    data: "status",
                    name: "status",
                    render: function (data) {
                        let icon = 'fa-question-circle';
                        let label = data;
                        let color = 'secondary';

                        switch (data) {
                            case 'online':
                                icon = 'fa-check-circle';
                                color = 'success';
                                label = 'Online';
                                break;
                            case 'offline':
                                icon = 'fa-times-circle';
                                color = 'danger';
                                label = 'Offline';
                                break;
                            case 'lost':
                                icon = 'fa-exclamation-circle';
                                color = 'warning';
                                label = 'Lost';
                                break;
                            case 'unknown':
                                icon = 'fa-question-circle';
                                color = 'secondary';
                                label = 'Unknown';
                                break;
                        }

                        return `<span class="text-${color}"><i class="fa ${icon} me-1"></i> ${label}</span>`;
                    }
                },
                {
                    orderable: false,
                    data: null,
                    name: "cpu",
                    render: function (data, type, row) {
                        let usage = (row.cpu * 100).toFixed(1);
                        return `
                <small>${usage}% of ${row.maxcpu} CPU</small>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-info" style="width: ${usage}%;"></div>
                </div>`;
                    }
                },
                {
                    orderable: false,
                    data: null,
                    name: "mem",
                    render: function (data, type, row) {
                        let used = (row.mem / 1024 / 1024 / 1024).toFixed(1);
                        let total = (row.maxmem / 1024 / 1024 / 1024).toFixed(1);
                        let percent = (row.mem / row.maxmem * 100).toFixed(1);
                        return `
                <small>${used} GiB / ${total} GiB</small>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-warning" style="width: ${percent}%;"></div>
                </div>`;
                    }
                },
                {
                    data: "uptime",
                    name: "uptime",
                    render: function (data) {
                        let days = Math.floor(data / 86400);
                        let hours = Math.floor((data % 86400) / 3600);
                        let minutes = Math.floor((data % 3600) / 60);

                        return `<i class="fa fa-clock me-1"></i> ${days}d ${hours}h ${minutes}m`;
                    }
                },
                {
                    orderable: false,
                    data: "tags",
                    className: "w-25",
                    width: "100%",
                    render: function(data, type, row) {
                        const options = data.map(v => `<option value="${v}" selected>${v}</option>`).join('');
                        return `<select class="tag-select"
                                    data-uuid="${row.uuid}"
                                    data-model="${row.model}"
                                    multiple>${options}</select>`;
                    }
                },
                {
                    data: 'urls',
                    className: "text-center",
                    orderable: false,
                    render: function (data, type, row) {
                        let btn = '';
                        if (row.urls.test_connection) {
                            btn += `
                    <button type="button"
                            class="test-connection-btn mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary"
                            data-model-url="${row.urls.test_connection}">
                        <i class="fa fa-plug"></i> {{ __('Product.puqProxmox.Test Connection') }}
                            </button>`;
                        }
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

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


            $('#refreshNodes').on('click', function () {

                blockUI('container');
                PUQajax('{{ route('admin.api.Product.puqProxmox.sync.cluster.nodes.get', $uuid) }}', {}, 500, $(this), 'GET')
                    .then(function (response) {
                        unblockUI('container');
                        $dataTable.ajax.reload(null, false);
                    })
                    .catch(function (error) {
                        unblockUI('container');
                        console.error('Error loading form data:', error);
                    });
            });


            $dataTable.on('draw responsive-display', function () {
                $('.tag-select').each(function () {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        initializeTagSelect2(this, {
                            url: '{{ route('admin.api.Product.puqProxmox.tag-editor.tags.search.get') }}'
                        });
                    }
                });
            });

            $dataTable.on('change', '.tag-select', function() {
                const uuid = $(this).data('uuid');
                const model = $(this).data('model');
                const tags = $(this).val();

                PUQajax('{{route('admin.api.Product.puqProxmox.tag-editor.tags.update.get')}}', {
                    uuid: uuid,
                    model: model,
                    tags: tags
                }, 1000, null, 'POST')
                    .then(() => {
                        console.log('Tags updated');
                    })
                    .catch(errors => {
                        console.error('Error updating tags:', errors);
                    });
            });

        });

    </script>
@endsection
