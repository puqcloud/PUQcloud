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
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('Product.puqProxmox.Dashboard') }}</a>
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

                <!-- Download missing templates only -->
                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"
                        id="syncTemplatesBtnNoDelete"
                        data-delete="0">
                    <i class="fa fa-download"></i> {{ __('Product.puqProxmox.Sync Missing Templates') }}
                </button>

                <!-- Full sync with delete -->
                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-danger"
                        id="syncTemplatesBtnWithDelete"
                        data-delete="1">
                    <i class="fa fa-sync"></i> {{ __('Product.puqProxmox.Sync & Remove Extra Templates') }}
                </button>

                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        id="create">
                    <i class="fa fa-plus"></i>
                    {{__('Product.puqProxmox.Create')}}
                </button>
            </div>

        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="lxc_templates"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.URL')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.URL')}}</th>
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

            var tableId = '#lxc_templates';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.lxc_templates.get') }}';
            var columnsConfig = [
                {data: "name", name: "name"},
                {
                    data: "url",
                    name: "url",
                    render: function (url, type, row) {
                        return `
                <a href="${url}">${url}</a>
                <span class="file-status ms-2" data-uuid="${row.uuid}">
                    <i class="fa fa-spinner fa-spin"></i>
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
                $modalTitle.text('{{__('Product.puqProxmox.Create')}}');

                var formHtml = `
            <form id="createForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="url">{{__('Product.puqProxmox.URL')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="url" name="url" placeholder="{{__('Product.puqProxmox.URL')}}">
                    </div>
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

                $modalTitle.text('{{__('Product.puqProxmox.Edit')}}');

                const formHtml = `
            <form id="editForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="url">{{__('Product.puqProxmox.URL')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="url" name="url" placeholder="{{__('Product.puqProxmox.URL')}}">
                    </div>
                </div>
            </form>`;

                $modalBody.html(formHtml);

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        $('#name').val(response.data.name);
                        $('#url').val(response.data.url);

                        $('#universalModal').modal('show');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{__('Product.puqProxmox.Are you sure you want to delete this record?')}}')) {
                    PUQajax(modelUrl, null, 1000, null, 'DELETE')
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

                    PUQajax('{{route('admin.api.Product.puqProxmox.lxc_template.post')}}', formData, 1000, $(this), 'POST', $form)
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


            $('#lxc_templates').on('draw.dt', function () {
                $('.file-status').each(function () {
                    const span = $(this);
                    const uuid = span.data('uuid');

                    const url = '{{ route('admin.api.Product.puqProxmox.lxc_template.check_lxc_template_file.get', '__UUID__') }}'.replace('__UUID__', uuid);

                    PUQajax(url, {}, 1000, null, 'GET')
                        .then(function (res) {
                            if (res.status === 'success' && res.data.status === 'ok') {
                                span.html(`✅ ${Math.round(res.data.size / 1024 / 1024)} MB`);
                            } else {
                                span.html('<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> {{__('Product.puqProxmox.File missing or unreachable')}}</span>');
                            }
                        })
                        .catch(() => {
                            span.html('<span class="text-danger fw-bold"><i class="fas fa-times-circle"></i> {{__('Product.puqProxmox.Server error')}}</span>');
                        });
                });
            });


            $('#syncTemplatesBtnNoDelete').on('click', function () {
                PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_templates.sync_templates.get') }}', {}, 5000, $(this), 'GET')
                    .then(function (response) {
                    })
                    .catch(function (error) {
                        unblockUI('container');
                        console.error('Error loading form data:', error);
                    });
            });

            $('#syncTemplatesBtnWithDelete').on('click', function () {
                PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_templates.sync_delete_templates.get') }}', {}, 5000, $(this), 'GET')
                    .then(function (response) {
                    })
                    .catch(function (error) {
                        unblockUI('container');
                        console.error('Error loading form data:', error);
                    });
            });

        });
    </script>
@endsection
