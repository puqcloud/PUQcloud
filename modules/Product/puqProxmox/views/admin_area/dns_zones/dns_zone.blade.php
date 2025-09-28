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

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2 mb-3">
                            <label for="dns" class="form-label">{{__('Product.puqProxmox.Count')}}</label>
                            <input type="text" name="count" id="count" class="form-control" disabled>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
        <script>
            $(document).ready(function () {
                function loadFormData() {
                    blockUI('container');

                    PUQajax('{{route('admin.api.Product.puqProxmox.dns_zone.get',$uuid)}}', {}, 50, null, 'GET')
                        .then(function (response) {

                            $("#name").val(response.data?.name);
                            $("#count").val(response.data?.count);

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

                loadFormData();
            });
        </script>
@endsection
