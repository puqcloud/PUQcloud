@php
    $type = 'service-conf';
    $variables = $load_balancer->getScriptVariables($type);
    $description = 'service-conf';
@endphp
@section('buttons')
    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
            id="deploy">
        <i class="fa fa-cloud-upload-alt"></i>
        {{ __('Product.puqProxmox.Deploy') }}
    </button>
    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary"
            id="default_script">
        <i class="fa fa-cloud-download-alt"></i>
        {{ __('Product.puqProxmox.Load Default') }}
    </button>
    @parent
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            $("#deploy").on("click", function (event) {
                PUQajax('{{ route('admin.api.Product.puqProxmox.load_balancer.deploy.config.put', ['uuid'=>$uuid, 'type'=> $type]) }}', null, 1000, $(this), 'PUT');
            });

            $("#default_script").on("click", function (event) {
                PUQajax('{{ route('admin.api.Product.puqProxmox.load_balancer.default_script.put', ['uuid'=>$uuid, 'type'=> $type]) }}', null, 1000, $(this), 'PUT')
                    .then(function (response) {
                        $(document).trigger('loadFormDataEvent');
                    });
            });

        });
    </script>
@endsection

@include('modules.Product.puqProxmox.views.admin_area.load_balancers.load_balancer_script')

