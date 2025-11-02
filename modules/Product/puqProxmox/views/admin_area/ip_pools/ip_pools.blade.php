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
            <table style="width: 100%;" id="ip_pools"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.Type')}}</th>
                    <th>{{__('Product.puqProxmox.First IP')}}</th>
                    <th>{{__('Product.puqProxmox.Last IP')}}</th>
                    <th>{{__('Product.puqProxmox.Mask')}}</th>
                    <th>{{__('Product.puqProxmox.Gateway')}}</th>
                    <th>{{__('Product.puqProxmox.DNS')}}</th>
                    <th>{{__('Product.puqProxmox.Used')}}</th>
                    <th>{{__('Product.puqProxmox.Total')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.Type')}}</th>
                    <th>{{__('Product.puqProxmox.First IP')}}</th>
                    <th>{{__('Product.puqProxmox.Last IP')}}</th>
                    <th>{{__('Product.puqProxmox.Mask')}}</th>
                    <th>{{__('Product.puqProxmox.Gateway')}}</th>
                    <th>{{__('Product.puqProxmox.DNS')}}</th>
                    <th>{{__('Product.puqProxmox.Used')}}</th>
                    <th>{{__('Product.puqProxmox.Total')}}</th>
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

            var tableId = '#ip_pools';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.ip_pools.get') }}';
            var columnsConfig = [
                {data: "name", name: "name"},
                {data: "type", name: "type"},
                {data: "first_ip", name: "first_ip"},
                {data: "last_ip", name: "last_ip"},
                {data: "mask", name: "mask"},
                {data: "gateway", name: "gateway"},
                {data: "dns", name: "dns"},
                {data: "used_count", name: "used_count"},
                {data: "count", name: "count"},

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
                $modalTitle.text('{{__('Product.puqProxmox.Create')}}');

                var formHtml = `
<form id="createForm" class="col-md-10 mx-auto">

    <div class="mb-3">
        <label for="name" class="form-label">{{__('Product.puqProxmox.Name')}}</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="type" class="form-label">{{__('Product.puqProxmox.Type')}}</label>
        <select name="type" class="form-select">
            <option value="ipv4">IPv4</option>
            <option value="ipv6">IPv6</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="first_ip" class="form-label">{{__('Product.puqProxmox.First IP')}}</label>
        <input type="text" name="first_ip" class="form-control" required>
        <div class="form-text">{{__('Product.puqProxmox.IPv4: 10.0.0.1')}}</div>
        <div class="form-text">{{__('Product.puqProxmox.IPv6: 2001:0DB8:0000:0000:0000:0000:0000:0001')}}</div>
    </div>

    <div class="mb-3">
        <label for="last_ip" class="form-label">{{__('Product.puqProxmox.Last IP')}}</label>
        <input type="text" name="last_ip" class="form-control" required>
        <div class="form-text">{{__('Product.puqProxmox.IPv4: 10.0.0.1')}}</div>
        <div class="form-text">{{__('Product.puqProxmox.IPv6: 2001:0DB8:0000:0000:0000:0000:0000:0001')}}</div>
    </div>

    <div class="mb-3">
        <label for="gateway" class="form-label">{{__('Product.puqProxmox.Gateway')}}</label>
        <input type="text" name="gateway" class="form-control" required>
        <div class="form-text">{{__('Product.puqProxmox.IPv4: 10.0.0.1')}}</div>
        <div class="form-text">{{__('Product.puqProxmox.IPv6: 2001:0DB8:0000:0000:0000:0000:0000:0001')}}</div>
    </div>

    <div class="mb-3">
        <label for="mask" class="form-label">{{__('Product.puqProxmox.Mask')}}</label>
        <input type="number" name="mask" min="1" max="128" step="1" value="1" class="form-control" required>
        <div class="form-text">{{__('Product.puqProxmox.IPv4: 1–32, IPv6: 1–128')}}</div>
    </div>

    <div class="mb-3">
        <label for="dns" class="form-label">{{__('Product.puqProxmox.DNS')}}</label>
        <input type="text" name="dns" class="form-control">
        <div class="form-text">{{__('Product.puqProxmox.Comma separated')}}</div>
    </div>
</form>
`;

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

                    PUQajax('{{route('admin.api.Product.puqProxmox.ip_pool.post')}}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
