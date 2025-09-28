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
    @include('modules.Product.puqProxmox.views.admin_area.lxc_os_templates.lxc_os_template_header')

    <div id="container">
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <form id="lxcOsTemplateForm" action="" novalidate>
                    <div class="row g-3">

                        <div class="col-xl-3 col-lg-6">
                            <label for="key" class="form-label">
                                <i class="fas fa-key text-warning me-1"></i> {{ __('Product.puqProxmox.Key') }}
                            </label>
                            <input type="text" class="form-control" id="key" name="key" placeholder="{{ __('Product.puqProxmox.Key') }}">
                        </div>

                        <div class="col-xl-3 col-lg-6">
                            <label for="name" class="form-label">
                                <i class="fas fa-tag text-primary me-1"></i> {{ __('Product.puqProxmox.Name') }}
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Example: LXC EU Plan A">
                        </div>

                        <div class="col-xl-3 col-lg-6">
                            <label for="distribution" class="form-label">
                                <i class="fab fa-linux text-success me-1"></i> {{ __('Product.puqProxmox.Distribution') }}
                            </label>
                            <input type="text" class="form-control" id="distribution" name="distribution" placeholder="{{ __('Product.puqProxmox.Distribution') }}">
                        </div>

                        <div class="col-xl-3 col-lg-6">
                            <label for="version" class="form-label">
                                <i class="fas fa-code-branch text-info me-1"></i> {{ __('Product.puqProxmox.Version') }}
                            </label>
                            <input type="text" class="form-control" id="version" name="version" placeholder="{{ __('Product.puqProxmox.Version') }}">
                        </div>

                        <div class="col-xl-6 col-lg-12">
                            <label for="puq_pm_lxc_template_uuid" class="form-label">
                                <i class="fas fa-box text-secondary me-1"></i> {{ __('Product.puqProxmox.LXC Template') }}
                            </label>
                            <select name="puq_pm_lxc_template_uuid" id="puq_pm_lxc_template_uuid" class="form-select">
                            </select>
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

                PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_os_template.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#key").val(response.data?.key);
                        $("#name").val(response.data?.name);
                        $("#distribution").val(response.data?.distribution);
                        $("#version").val(response.data?.version);

                        var $element_country = $("#puq_pm_lxc_template_uuid");
                        initializeSelect2($element_country, '{{route('admin.api.Product.puqProxmox.lxc_templates.select.get')}}', response.data.puq_pm_lxc_template_data, 'GET', 1000, {});

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#lxcOsTemplateForm");
                const formData = serializeForm($form);
                console.log(formData);

                PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_os_template.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
