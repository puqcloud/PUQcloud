@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>

@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.clusters.cluster_header')
    <div id="container">
        <form id="clusterForm" method="POST" action="" novalidate="novalidate">
            @include('modules.Product.puqProxmox.views.admin_area.clusters.env-variables')
        </form>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('container');

                PUQajax('{{ route('admin.api.Product.puqProxmox.cluster.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#env_variables").val(JSON.stringify(response.data?.env_variables || []));
                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#clusterForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqProxmox.cluster.env_variables.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
