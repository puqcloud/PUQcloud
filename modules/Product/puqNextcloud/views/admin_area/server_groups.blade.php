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
            <table style="width: 100%;" id="server_groups"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqNextcloud.Name')}}</th>
                    <th>{{__('Product.puqNextcloud.Fill Type')}}</th>
                    <th>{{__('Product.puqNextcloud.Servers')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqNextcloud.Name')}}</th>
                    <th>{{__('Product.puqNextcloud.Fill Type')}}</th>
                    <th>{{__('Product.puqNextcloud.Servers')}}</th>
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

            var tableId = '#server_groups';
            var ajaxUrl = '{{ route('admin.api.Product.puqNextcloud.server_groups.get') }}';
            var columnsConfig = [
                {data: "name", name: "name"},
                {data: "fill_type", name: "fill_type"},
                {
                    data: "servers",
                    name: "servers",
                    render: function (data, type, row) {
                        if (!Array.isArray(data) || data.length === 0) {
                            return '';
                        }

                        return data.map(function(server) {
                            return `<a href="${server.url}" target="_blank" class="badge bg-primary me-1">${server.name}</a>`;
                        }).join(' ');
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.put) {
                            btn = btn + renderEditButton(row.urls.put);
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
                    <label class="form-label" for="name">{{__('Product.puqSampleProduct.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqSampleProduct.Name')}}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fill_type">{{ __('Product.puqNextcloud.Fill Type') }}</label>
                    <select id="fill_type" name="fill_type" class="form-select">
                        <option value="default">Default</option>
                        <option value="lowest">Lowest</option>
                    </select>
                </div>
            </form>`;
                $modalBody.html(formHtml);
                $('#universalModal').modal('show');
            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text('{{__('Plugin.puqNextcloud.Edit')}}');

                const formHtml = `
            <form id="editForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqSampleProduct.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqSampleProduct.Name')}}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fill_type">{{ __('Product.puqNextcloud.Fill Type') }}</label>
                    <select id="fill_type" name="fill_type" class="form-select">
                        <option value="default">Default</option>
                        <option value="lowest">Lowest</option>
                    </select>
                </div>
            </form>`;

                $modalBody.html(formHtml);

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        $('#name').val(response.data.name);
                        $('#fill_type').val(response.data.fill_type);

                        $('#universalModal').modal('show');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
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

                    PUQajax('{{route('admin.api.Product.puqNextcloud.server_group.post')}}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#editForm').length) {
                    var $form = $('#editForm');
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
