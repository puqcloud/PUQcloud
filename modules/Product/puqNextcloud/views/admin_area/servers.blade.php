@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-server"></i>
                                            </span>
                        <span class="d-inline-block">{{ $title }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav class="" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a>
                                        <i aria-hidden="true" class="fa fa-home"></i>
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('Product.puqNextcloud.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ $title }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="page-title-actions">
                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        id="create">
                    <i class="fa fa-plus"></i>
                    {{__('Product.puqNextcloud.Create')}}
                </button>
            </div>

        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="servers"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqNextcloud.Name')}}</th>
                    <th>{{__('Product.puqNextcloud.Group')}}</th>
                    <th>{{__('Product.puqNextcloud.Host')}}</th>
                    <th>{{__('Product.puqNextcloud.Max Accounts')}}</th>
                    <th>{{ __('Product.puqNextcloud.Version') }}</th>
                    <th>{{ __('Product.puqNextcloud.CPU Load') }}</th>
                    <th>{{ __('Product.puqNextcloud.RAM Free') }}</th>
                    <th>{{ __('Product.puqNextcloud.Free Space') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqNextcloud.Name')}}</th>
                    <th>{{__('Product.puqNextcloud.Group')}}</th>
                    <th>{{__('Product.puqNextcloud.Host')}}</th>
                    <th>{{__('Product.puqNextcloud.Max Accounts')}}</th>
                    <th>{{ __('Product.puqNextcloud.Version') }}</th>
                    <th>{{ __('Product.puqNextcloud.CPU Load') }}</th>
                    <th>{{ __('Product.puqNextcloud.RAM Free') }}</th>
                    <th>{{ __('Product.puqNextcloud.Free Space') }}</th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var tableId = '#servers';
            var ajaxUrl = '{{ route('admin.api.Product.puqNextcloud.servers.get') }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        let colorClass = row.active ? 'text-success fw-bold' : 'text-danger fw-bold';
                        let badge = row.default ? '<span class="badge bg-success ms-2">default</span>' : '';
                        return `<span class="${colorClass}">${data}</span>${badge}`;
                    }
                },
                {data: "group", name: "group"},
                {data: "host", name: "host"},
                {
                    data: "max_accounts",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `${row.use_accounts}/${row.max_accounts}`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
            <span class="nextcloud-metric" data-id="${row.uuid}" data-metric="version">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            </span>
        `;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
            <span class="nextcloud-metric" data-id="${row.uuid}" data-metric="cpuload">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            </span>
        `;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
            <span class="nextcloud-metric" data-id="${row.uuid}" data-metric="ram">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            </span>
        `;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `
            <span class="nextcloud-metric" data-id="${row.uuid}" data-metric="disk">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            </span>
        `;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.edit) {
                            btn = btn + renderEditButton(row.urls.edit);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $('#create').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__('Product.puqNextcloud.Create')}}');

                var formHtml = `
            <form id="createForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqNextcloud.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqNextcloud.Name')}}">
                    </div>
                </div>
            </form>`;
                $modalBody.html(formHtml);
                $('#universalModal').modal('show');
            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{__('Product.puqNextcloud.Are you sure you want to delete this record?')}}')) {
                    PUQajax(modelUrl, null, 3000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.Product.puqNextcloud.server.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            $dataTable.on('draw', function () {
                const uuids = new Set();

                $('.nextcloud-metric').each(function () {
                    uuids.add($(this).data('id'));
                });

                uuids.forEach(uuid => {
                    PUQajax('{{ route('admin.api.Product.puqNextcloud.server.test_connection.get') }}?uuid=' + uuid, {}, 1, null, 'GET')
                        .then(response => {
                            if (response.status === 'success') {
                                const sys = response.data.nextcloud.system;
                                $(`.nextcloud-metric[data-id="${uuid}"][data-metric="version"]`).text(sys.version);
                                const cpuPercent = sys.cpuload.map(load => (load * 100).toFixed(1) + '%').join(', ');
                                $(`.nextcloud-metric[data-id="${uuid}"][data-metric="cpuload"]`).text(cpuPercent);
                                $(`.nextcloud-metric[data-id="${uuid}"][data-metric="ram"]`).text(formatBytes(sys.mem_free * 1024));
                                $(`.nextcloud-metric[data-id="${uuid}"][data-metric="disk"]`).text(formatBytes(sys.freespace));
                            }
                        })
                        .catch(() => {
                            $(`.nextcloud-metric[data-id="${uuid}"]`).html('<i class="fas fa-times-circle" style="color: #dc3545;"></i>');
                        });
                });
            });

            function formatBytes(bytes) {
                const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                if (bytes === 0) return '0 B';
                const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)), 10);
                return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
            }
        });
    </script>
@endsection
