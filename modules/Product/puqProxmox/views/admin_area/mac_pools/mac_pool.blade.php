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
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.Product.puqProxmox.mac_pools')}}">{{ __('Product.puqProxmox.MAC Pools') }}</a>
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
                        id="save">
                    <i class="fa fa-save"></i>
                    {{__('Product.puqProxmox.Save')}}
                </button>
            </div>

        </div>
    </div>

    <div id="container">
        <div class="card mb-3">
            <div class="card-body">
                <form id="ipPoolForm" method="POST" action="" novalidate="novalidate">
                    <div class="row">

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2 mb-3">
                            <label for="name" class="form-label">{{__('Product.puqProxmox.Name')}}</label>
                            <input type="text" name="name" id="name" value="" class="form-control"
                                   required>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 mb-3">
                            <label for="first_mac" class="form-label">{{__('Product.puqProxmox.First MAC')}}</label>
                            <input type="text" name="first_mac" id="first_mac" class="form-control" required>
                            <div class="form-text">{{__('Product.puqProxmox.MAC format: 00:1A:2B:3C:4D:5E')}}</div>
                            <div class="form-text">{{__('Product.puqProxmox.MAC format with dashes: 00-1A-2B-3C-4D-5E')}}</div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 mb-3">
                            <label for="last_mac" class="form-label">{{__('Product.puqProxmox.Last MAC')}}</label>
                            <input type="text" name="last_mac" id="last_mac" class="form-control" required>
                            <div class="form-text">{{__('Product.puqProxmox.MAC format: 00:1A:2B:3C:4D:5E')}}</div>
                            <div class="form-text">{{__('Product.puqProxmox.MAC format with dashes: 00-1A-2B-3C-4D-5E')}}</div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2 mb-3">
                            <label for="dns" class="form-label">{{__('Product.puqProxmox.Count')}}</label>
                            <input type="text" name="count" id="count" class="form-control" disabled>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="used_mac"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.MAC')}}</th>
                    <th>{{__('Product.puqProxmox.Service')}}</th>
                    <th>{{__('Product.puqProxmox.Type')}}</th>
                    <th>{{__('Product.puqProxmox.Interface')}}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.MAC')}}</th>
                    <th>{{__('Product.puqProxmox.Service')}}</th>
                    <th>{{__('Product.puqProxmox.Type')}}</th>
                    <th>{{__('Product.puqProxmox.Interface')}}</th>
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

            var tableId = '#used_mac';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.mac_pool.used_macs.get',$uuid) }}';
            var columnsConfig = [
                {data: "mac", name: "mac"},
                {
                    data: "service_uuid", name: "service_uuid",
                    render: function (service_uuid, type, row) {
                        return linkify(`service:${service_uuid}`, true);
                    }
                },
                {data: "type", name: "type"},
                {data: "name", name: "name"},
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            function loadFormData() {
                blockUI('container');

                PUQajax('{{route('admin.api.Product.puqProxmox.mac_pool.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        $("#name").val(response.data?.name);
                        $("#first_mac").val(response.data?.first_mac);
                        $("#last_mac").val(response.data?.last_mac);
                        $("#count").val(response.data?.used_count + '/' + response.data?.count);

                        unblockUI('container');
                    })

                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#ipPoolForm");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.Product.puqProxmox.mac_pool.put', $uuid)}}', formData, 1000, $(this), 'PUT', $form)
                        .then(function (response) {
                            loadFormData();
                        });
                });

                loadFormData();
            });
        </script>
@endsection
