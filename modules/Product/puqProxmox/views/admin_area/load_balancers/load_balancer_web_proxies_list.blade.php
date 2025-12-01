@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
@endsection

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
            id="deploy_all">
        <i class="fa fa-cloud-upload-alt"></i>
        {{ __('Product.puqProxmox.Deploy ALL') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="create">
        <i class="fa fa-plus"></i>
        {{__('Product.puqProxmox.Create')}}
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.load_balancers.load_balancer_header')

    <div id="container">
        <div class="main-card mb-3 card">
            <div class="card-body">
                <table style="width: 100%;" id="web_proxies" class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Enable') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Enable') }}</th>
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

            var tableId = '#web_proxies';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.load_balancer.web_proxies.get', $uuid) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name"
                },
                {
                    data: 'disable',
                    render: function (data, type, row) {
                        return renderStatus(data);
                    }
                },
                {
                    data: 'urls',
                    className: "text-center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) btn += renderEditLink(row.urls.edit);
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

            $('#create').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__('Product.puqProxmox.Create')}}');

                var formHtml = `
            <form id="createForm" class="col-md-10 mx-auto">
                <input type="hidden" class="form-control" id="puq_pm_load_balancer_uuid" name="puq_pm_load_balancer_uuid" value="{{ $uuid }}">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                    </div>
                </div>
            </form>`;
                $modalBody.html(formHtml);

                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.Product.puqProxmox.web_proxy.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            $("#deploy_all").on("click", function (event) {
                PUQajax('{{ route('admin.api.Product.puqProxmox.load_balancer.deploy_all.put', $uuid) }}', null, 1000, $(this), 'PUT');
            });
        });

    </script>
@endsection
