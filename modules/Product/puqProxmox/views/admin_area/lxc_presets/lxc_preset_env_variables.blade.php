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
    @include('modules.Product.puqProxmox.views.admin_area.lxc_presets.lxc_preset_header')

    <div id="container">
        <form id="lxcPresetForm" method="POST" action="" novalidate>

            <div class="row g-3 mb-3">

                {{-- Cluster Group ENV --}}
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-layer-group fa-2x text-primary"></i>
                            </div>

                            <div class="flex-grow-1">
                                <label class="form-check form-switch m-0">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="puq_pm_cluster_group_env_variables"
                                           name="puq_pm_cluster_group_env_variables">

                                    <span class="form-check-label fw-semibold">
                            {{ __('Product.puqProxmox.Include Cluster Group Environment Variables') }}
                        </span>
                                </label>

                                <small class="text-muted d-block mt-1">
                                    {{ __('Product.puqProxmox.Apply environment variables from the cluster group level') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cluster ENV --}}
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-network-wired fa-2x text-success"></i>
                            </div>

                            <div class="flex-grow-1">
                                <label class="form-check form-switch m-0">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="puq_pm_cluster_env_variables"
                                           name="puq_pm_cluster_env_variables">

                                    <span class="form-check-label fw-semibold">
                            {{ __('Product.puqProxmox.Include Cluster Environment Variables') }}
                        </span>
                                </label>

                                <small class="text-muted d-block mt-1">
                                    {{ __('Product.puqProxmox.Apply environment variables from the cluster level') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            @include('modules.Product.puqProxmox.views.admin_area.lxc_presets.env-variables')

        </form>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('container');

                PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_preset.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#puq_pm_cluster_group_env_variables").prop("checked", response.data?.puq_pm_cluster_group_env_variables);
                        $("#puq_pm_cluster_env_variables").prop("checked", response.data?.puq_pm_cluster_env_variables);
                        $("#env_variables").val(JSON.stringify(response.data?.env_variables || []));

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#lxcPresetForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_preset.env_variables.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
