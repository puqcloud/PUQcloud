@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
@endsection

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"
            id="syncTemplatesBtn">
        <i class="fa fa-download"></i> {{ __('Product.puqProxmox.Sync Templates') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"
            id="refreshStorages">
        <i class="fa fa-sync-alt"></i> {{ __('Product.puqProxmox.Refresh Storages') }}
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.clusters.cluster_header')

    <div id="container">

        <div class="main-card mb-3 card">
            <div class="card-body">
                <table style="width: 100%;" id="storages" class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Node') }}</th>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Status') }}</th>
                        <th>{{ __('Product.puqProxmox.Type/Shared') }}</th>
                        <th>{{ __('Product.puqProxmox.Usage') }}</th>
                        <th>{{ __('Product.puqProxmox.Tags') }}</th>
                        <th>{{ __('Product.puqProxmox.Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Node') }}</th>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Status') }}</th>
                        <th>{{ __('Product.puqProxmox.Type/Shared') }}</th>
                        <th>{{ __('Product.puqProxmox.Usage') }}</th>
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

            var tableId = '#storages';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.cluster.storages.get', $uuid) }}';
            var columnsConfig = [
                {
                    data: "node_name",
                    name: "node_name",
                    render: function (data, type, row) {
                        return `<i class="fa fa-server text-secondary me-1"></i> ${data}`;
                    }
                },
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `<i class="fa fa-hdd text-primary me-1"></i> <b>${data}</b>`;
                    }
                },
                {
                    data: "status",
                    name: "status",
                    className: "w-25",
                    render: function (data, type, row) {
                        let icon = 'fa-question-circle';
                        let label = data;
                        let color = 'secondary';

                        switch (data) {
                            case 'available':
                                icon = 'fa-check-circle';
                                color = 'success';
                                label = 'Available';
                                break;
                            case 'lost':
                                icon = 'fa-exclamation-circle';
                                color = 'warning';
                                label = 'Lost';
                                break;
                            case 'offline':
                                icon = 'fa-times-circle';
                                color = 'danger';
                                label = 'Offline';
                                break;
                            case 'unknown':
                                icon = 'fa-question-circle';
                                color = 'secondary';
                                label = 'Unknown';
                                break;
                        }

                        const contentMap = {
                            'images': { icon: 'fa-cogs', label: 'VPS', color: 'primary' },
                            'iso': { icon: 'fa-archive', label: 'ISO', color: 'warning' },
                            'rootdir': { icon: 'fa-hdd', label: 'LXC', color: 'info' },
                            'vztmpl': { icon: 'fa-cube', label: 'LXC template', color: 'warning' },
                            'backup': { icon: 'fa-save', label: 'Backup', color: 'success' }
                        };

                        const contents = row.content ? row.content.split(',') : [];
                        const order = ['images', 'iso', 'rootdir', 'vztmpl', 'backup'];

                        let contentIcons = '';
                        order.forEach(key => {
                            if (contents.includes(key)) {
                                const c = contentMap[key];
                                contentIcons += `<span class="badge bg-${c.color} me-1 mb-1 text-nowrap" style="font-size: 0.75rem;"><i class="fa ${c.icon} me-1"></i>${c.label}</span>`;
                            }
                        });

                        return `

            <span class="text-${color}">
                <i class="fa ${icon} me-1"></i> ${label}
            </span>
            <div class="mt-1">${contentIcons}</div>

        `;
                    }
                },
                {
                    orderable: false,
                    data: null,
                    name: "plugintype_shared",
                    render: function (data, type, row) {
                        const typeBadge = `<span class="badge bg-info text-dark text-uppercase mb-1 ">${row.plugintype}</span>`;
                        const sharedText = row.shared
                            ? `<span class="text-success d-block"><i class="fa fa-check-circle me-1"></i>Yes</span>`
                            : `<span class="text-muted d-block"><i class="fa fa-times-circle me-1"></i>No</span>`;
                        return `${typeBadge}${sharedText}`;
                    }
                },

                {
                    orderable: false,
                    data: null,
                    name: "disk",
                    render: function (data, type, row) {
                        if (row.maxdisk === 0) {
                            return `<span class="text-muted">N/A</span>`;
                        }
                        let usedGiB = (row.disk / 1073741824).toFixed(1);
                        let totalGiB = (row.maxdisk / 1073741824).toFixed(1);
                        let percent = (row.disk / row.maxdisk * 100).toFixed(1);
                        return `
                <small>${usedGiB} GiB / ${totalGiB} GiB</small>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-warning" style="width: ${percent}%;"></div>
                </div>`;
                    }
                },
                {
                    orderable: false,
                    data: "tags",
                    className: "w-25",
                    width: "100%",
                    render: function (data, type, row) {
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


            $('#refreshStorages').on('click', function () {
                blockUI('container');
                PUQajax('{{ route('admin.api.Product.puqProxmox.sync.cluster.storages.get', $uuid) }}', {}, 500, $(this), 'GET')
                    .then(function (response) {
                        unblockUI('container');
                        $dataTable.ajax.reload(null, false);
                    })
                    .catch(function (error) {
                        unblockUI('container');
                        console.error('Error loading form data:', error);
                    });
            });

            $('#syncTemplatesBtn').on('click', function () {
                PUQajax('{{ route('admin.api.Product.puqProxmox.sync.cluster.storages.sync_templates.get', $uuid) }}', {}, 5000, $(this), 'GET')
                    .then(function (response) {
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

            $dataTable.on('change', '.tag-select', function () {
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
