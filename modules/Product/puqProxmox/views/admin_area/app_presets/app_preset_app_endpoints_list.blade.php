@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('header')

@endsection

@section('buttons')
    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="create">
        <i class="fa fa-plus"></i>
        {{__('Product.puqProxmox.Create')}}
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.app_presets.app_preset_header')
    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="app_endpoints"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
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

            var tableId = '#app_endpoints';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.app_preset.app_endpoints.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name"
                },
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
                <input type="hidden" class="form-control" id="puq_pm_app_preset_uuid" name="puq_pm_app_preset_uuid" value="{{ $uuid }}">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="subdomain">{{__('Product.puqProxmox.Subdomain')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="subdomain" name="subdomain" placeholder="{{__('Product.puqProxmox.Subdomain')}}">
                    </div>
                </div>
            </form>`;
                $modalBody.html(formHtml);

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

                    PUQajax('{{route('admin.api.Product.puqProxmox.app_endpoint.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });

    </script>
@endsection
