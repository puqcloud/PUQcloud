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
                                    <a href="{{route('admin.web.Product.puqProxmox.dns_zones')}}">{{ __('Product.puqProxmox.DNS Zones') }}</a>
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
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"
                        id="pushToDNS">
                    <i class="fa fa-sync-alt"></i>
                    {{__('Product.puqProxmox.Push All to DNS Manager')}}
                </button>

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

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 col-xxl-4 mb-3">
                            <label for="name" class="form-label">{{__('Product.puqProxmox.Name')}}</label>
                            <input type="text" name="name" id="name" value="" class="form-control"
                                   required>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 col-xl-2 col-xxl-2 mb-3">
                            <label for="ttl" class="form-label">TTL</label>
                            <input type="number" class="form-control" id="ttl" name="ttl" min="30" step="1">
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-2 col-xl-2 col-xxl-2 mb-3">
                            <label for="dns" class="form-label">{{__('Product.puqProxmox.Count')}}</label>
                            <input type="text" name="count" id="count" class="form-control" disabled>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 col-xl-4 col-xxl-4 mb-3">
                            <label class="form-label">{{__('Product.puqProxmox.DNS Manager')}}</label>
                            <div id="dnsManagerInfo"></div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="dns_records"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.Hostname')}}</th>
                    <th>{{__('Product.puqProxmox.IP')}}</th>
                    <th>{{__('Product.puqProxmox.Content')}}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.Hostname')}}</th>
                    <th>{{__('Product.puqProxmox.IP')}}</th>
                    <th>{{__('Product.puqProxmox.Content')}}</th>
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


            var tableId = '#dns_records';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.dns_zone.records.get',$uuid) }}';
            var columnsConfig = [
                {data: "hostname", name: "hostname"},
                {data: "ip", name: "ip"},
                {data: "content", name: "content"},
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);


            function loadFormData() {
                blockUI('container');

                PUQajax('{{route('admin.api.Product.puqProxmox.dns_zone.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        $("#name").val(response.data?.name);
                        $("#ttl").val(response.data?.ttl);
                        $("#count").val(response.data?.count);

                        const dnsManager = response.data?.dns_manager;
                        const dnsDiv = $("#dnsManagerInfo");

                        if (dnsManager && dnsManager.web_url) {
                            dnsDiv.html(`
                    <a href="${dnsManager.web_url}" target="_blank" class="text-success">
                        ${response.data?.name}
                    </a>

                    (${dnsManager.record_count})
                `);
                        } else {
                            dnsDiv.html(`<span class="text-danger">{{__('Product.puqProxmox.No DNS zone found')}}</span>`);
                        }

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
                PUQajax('{{route('admin.api.Product.puqProxmox.dns_zone.put', $uuid)}}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $("#pushToDNS").on("click", function (event) {
                event.preventDefault();

                PUQajax('{{route('admin.api.Product.puqProxmox.dns_zone.push_records.put', $uuid)}}', null, 1000, $(this), 'PUT', null)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
