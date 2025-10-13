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
            <table style="width: 100%;" id="lxc_os_templates"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.Key')}}</th>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.LXC Template')}}</th>
                    <th>{{__('Product.puqProxmox.Distribution')}}</th>
                    <th>{{__('Product.puqProxmox.Version')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.Key')}}</th>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.LXC Template')}}</th>
                    <th>{{__('Product.puqProxmox.Distribution')}}</th>
                    <th>{{__('Product.puqProxmox.Version')}}</th>
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

            var tableId = '#lxc_os_templates';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.lxc_os_templates.get') }}';
            var columnsConfig = [
                {data: "key", name: "key"},
                {data: "name", name: "name"},
                {data: "template_name", name: "template_name"},
                {data: "distribution", name: "distribution"},
                {data: "version", name: "version"},
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.edit) {
                            btn = btn + renderEditLink(row.urls.edit);
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
                    <label class="form-label" for="key">{{__('Product.puqProxmox.Key')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="key" name="key" placeholder="{{__('Product.puqProxmox.Key')}}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="distribution">{{__('Product.puqProxmox.Distribution')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="distribution" name="distribution" placeholder="{{__('Product.puqProxmox.Distribution')}}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="version">{{__('Product.puqProxmox.Version')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="version" name="version" placeholder="{{__('Product.puqProxmox.Version')}}">
                    </div>
                </div>

                <label for="puq_pm_lxc_template_uuid" class="form-label">{{ __('Product.puqProxmox.LXC Template') }}</label>
                <select name="puq_pm_lxc_template_uuid" id="puq_pm_lxc_template_uuid" class="form-select mb-2 form-control"></select>

            </form>`;
                $modalBody.html(formHtml);

                initializeSelect2(
                    $("#puq_pm_lxc_template_uuid"),
                    '{{ route('admin.api.Product.puqProxmox.lxc_templates.select.get') }}',
                    {},
                    'GET',
                    1000,
                    {
                        dropdownParent: $('#universalModal')
                    }
                );

                $('#universalModal').modal('show');
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{__('Product.puqProxmox.Are you sure you want to delete this record?')}}')) {
                    PUQajax(modelUrl, null, 1000, $(this), 'DELETE')
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

                    PUQajax('{{route('admin.api.Product.puqProxmox.lxc_os_template.post')}}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
