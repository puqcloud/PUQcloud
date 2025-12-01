@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('header')

@endsection

@section('buttons')
    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
            id="createAppEndpointLocation">
        <i class="fa fa-plus"></i>
        {{ __('Product.puqProxmox.Create Location') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.app_presets.app_preset_header')

    <div id="container">
        <form id="appEndpointForm" method="POST" action="" novalidate="novalidate">

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 mb-3">

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="name" class="form-label">
                                <i class="fa fa-server me-1"></i>
                                {{ __('Product.puqProxmox.Name') }}
                            </label>
                            <input type="text" class="form-control" id="name" name="name">
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="subdomain" class="form-label">
                                <i class="fa fa-server me-1"></i>
                                {{ __('Product.puqProxmox.Subdomain') }}
                            </label>
                            <input type="text" class="form-control" id="subdomain" name="subdomain">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4 col-lg-4">
                            <label for="server_custom_config_before" class="form-label">
                                <i class="fa fa-code me-1"></i>
                                {{ __('Product.puqProxmox.Server Custom Config Before') }}
                            </label>
                            <textarea class="form-control" id="server_custom_config_before" name="server_custom_config_before" rows="4"></textarea>
                        </div>
                        <div class="col-12 col-md-4 col-lg-4">
                            <label for="server_custom_config" class="form-label">
                                <i class="fa fa-code me-1"></i>
                                {{ __('Product.puqProxmox.Server Custom Config') }}
                            </label>
                            <textarea class="form-control" id="server_custom_config" name="server_custom_config" rows="4"></textarea>
                        </div>
                        <div class="col-12 col-md-4 col-lg-4">
                            <label for="server_custom_config_after" class="form-label">
                                <i class="fa fa-code me-1"></i>
                                {{ __('Product.puqProxmox.Server Custom Config After') }}
                            </label>
                            <textarea class="form-control" id="server_custom_config_after" name="server_custom_config_after" rows="4"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="app_endpoint_locations" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{ __('Product.puqProxmox.Path') }}</th>
                    <th>{{ __('Product.puqProxmox.Show to Client') }}</th>
                    <th>{{ __('Product.puqProxmox.Proxy Protocol') }}</th>
                    <th>{{ __('Product.puqProxmox.Proxy Port') }}</th>
                    <th>{{ __('Product.puqProxmox.Proxy Path') }}</th>
                    <th></th>
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


@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('container');

                PUQajax('{{ route('admin.api.Product.puqProxmox.app_endpoint.get', request()->get('edit')) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#subdomain").val(response.data?.subdomain);
                        $("#server_custom_config_before").val(response.data?.server_custom_config_before || '');
                        $("#server_custom_config").val(response.data?.server_custom_config || '');
                        $("#server_custom_config_after").val(response.data?.server_custom_config_after || '');

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#appEndpointForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqProxmox.app_endpoint.put', request()->get('edit')) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                $('#modalSaveButton').show();
            });

            loadFormData();

            var tableId = '#app_endpoint_locations';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.app_endpoint.app_endpoint_locations.get', request()->get('edit')) }}';
            var columnsConfig = [
                {
                    data: "path",
                    name: "path",
                    render: function(data, type, row) {
                        return `<code>${data}</code>`;
                    }
                },
                {
                    data: "show_to_client",
                    name: "show_to_client",
                    render: function(data, type, row) {
                        return data
                            ? '<span class="badge bg-success"><i class="fa fa-check"></i></span>'
                            : '<span class="badge bg-secondary"><i class="fa fa-times"></i></span>';
                    },
                    className: "text-center"
                },
                {
                    data: "proxy_protocol",
                    name: "proxy_protocol",
                    render: function(data, type, row) {
                        let color = data === 'https' ? 'bg-success' : 'bg-info';
                        return `<span class="badge ${color}">${data.toUpperCase()}</span>`;
                    },
                    className: "text-center"
                },
                {
                    data: "proxy_port",
                    name: "proxy_port",
                    render: function(data, type, row) {
                        return `<span class="fw-bold">${data}</span>`;
                    },
                    className: "text-center"
                },
                {
                    data: "proxy_path",
                    name: "proxy_path",
                    render: function(data, type, row) {
                        if (!data) return "";
                        return `<code>${data}</code>`;
                    }
                },
                {
                    data: 'urls',
                    className: "text-center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.get) btn += renderEditButton(row.urls.get);
                        if (row.urls.delete) btn += renderDeleteButton(row.urls.delete);
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 1000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text('{{__("Product.puqProxmox.Edit")}}');

                var formHtml = `
    <form id="appEndpointLocationEditForm">
        <input type="hidden" class="form-control" id="puq_pm_app_endpoint_uuid" name="puq_pm_app_endpoint_uuid" value="{{ request()->get('edit') }}">

        <div class="mb-3">
            <label class="form-label" for="path">{{__("Product.puqProxmox.Path")}}</label>
            <input type="text" class="form-control input-mask-trigger" id="path" name="path">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="show_to_client" name="show_to_client">
            <label class="form-check-label" for="show_to_client">{{__("Product.puqProxmox.Show to Client")}}</label>
        </div>

        <div class="mb-3">
            <label class="form-label" for="proxy_protocol">{{ __("Product.puqProxmox.Proxy Protocol") }}</label>
            <select class="form-select" id="proxy_protocol" name="proxy_protocol" required>
                <option value="http">HTTP</option>
                <option value="https">HTTPS</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label" for="proxy_port">{{__("Product.puqProxmox.Proxy Port")}}</label>
            <input type="number" class="form-control" id="proxy_port" name="proxy_port">
        </div>

        <div class="mb-3">
            <label class="form-label" for="proxy_path">{{__("Product.puqProxmox.Proxy Path")}}</label>
            <input type="text" class="form-control" id="proxy_path" name="proxy_path">
        </div>

        <div class="mb-3">
            <label class="form-label" for="custom_config">{{__("Product.puqProxmox.Custom Config")}}</label>
            <textarea rows="5" class="form-control" id="custom_config" name="custom_config"></textarea>
        </div>

    </form>
    `;

                $modalBody.html(formHtml);

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        $('#puq_pm_app_endpoint_uuid').val(response.data.puq_pm_app_endpoint_uuid);
                        $('#path').val(response.data.path);
                        $('#proxy_protocol').val(response.data.proxy_protocol);
                        $('#proxy_port').val(response.data.proxy_port);
                        $('#proxy_path').val(response.data.proxy_path);
                        $('#custom_config').val(response.data.custom_config);
                        $('#show_to_client').prop('checked', response.data.show_to_client);
                        $('#universalModal').modal('show');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

            $('#createAppEndpointLocation').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__("Product.puqProxmox.Create")}}');

                var formHtml = `
    <form id="appEndpointLocationCreateForm">
        <input type="hidden" class="form-control" id="puq_pm_app_endpoint_uuid" name="puq_pm_app_endpoint_uuid" value="{{ request()->get('edit') }}">

        <div class="mb-3">
            <label class="form-label" for="path">{{__("Product.puqProxmox.Path")}}</label>
            <input type="text" class="form-control input-mask-trigger" id="path" name="path">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="show_to_client" name="show_to_client">
            <label class="form-check-label" for="show_to_client">{{__("Product.puqProxmox.Show to Client")}}</label>
        </div>

        <div class="mb-3">
            <label class="form-label" for="proxy_protocol">{{ __("Product.puqProxmox.Proxy Protocol") }}</label>
            <select class="form-select" id="proxy_protocol" name="proxy_protocol" required>
                <option value="http">HTTP</option>
                <option value="https">HTTPS</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label" for="proxy_port">{{__("Product.puqProxmox.Proxy Port")}}</label>
            <input type="number" class="form-control" id="proxy_port" name="proxy_port">
        </div>

        <div class="mb-3">
            <label class="form-label" for="proxy_path">{{__("Product.puqProxmox.Proxy Path")}}</label>
            <input type="text" class="form-control" id="proxy_path" name="proxy_path">
        </div>

        <div class="mb-3">
            <label class="form-label" for="custom_config">{{__("Product.puqProxmox.Custom Config")}}</label>
            <textarea rows="5" class="form-control" id="custom_config" name="custom_config"></textarea>
        </div>

    </form>
    `;

                $modalBody.html(formHtml);
                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#appEndpointLocationCreateForm').length) {
                    var $form = $('#appEndpointLocationCreateForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.Product.puqProxmox.app_endpoint_location.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#appEndpointLocationEditForm').length) {
                    var $form = $('#appEndpointLocationEditForm');
                    var formData = serializeForm($form);
                    var modelUrl = $(this).data('model-url');

                    PUQajax(modelUrl, formData, 500, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
