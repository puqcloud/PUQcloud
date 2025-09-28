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

    <button type="button"
            class="mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger"
            data-model-url="{{ route('admin.api.Product.puqProxmox.cluster_group.delete', $uuid) }}"
            id="deleteClusterGroup">
        <i class="fa fa-trash-alt"></i>
    </button>

@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.cluster_groups.cluster_group_header')

    <div id="container">
        <div class="card mb-3">
            <div class="card-body">
                <form id="clusterGroupForm" action="" novalidate="novalidate">
                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <label for="name" class="form-label">
                                <i class="fa fa-tag me-1 text-primary"></i> {{ __('Product.puqProxmox.Name') }}
                            </label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Example: LXC EU Plan A">
                        </div>

                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <label for="fill_type" class="form-label">
                                <i class="fa fa-balance-scale-left me-1 text-primary"></i> {{ __('Product.puqProxmox.Fill Type') }}
                            </label>
                            <select id="fill_type" name="fill_type" class="form-select">
                                <option value="default">{{ __('Default') }}</option>
                                <option value="lowest">{{ __('Lowest') }}</option>
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-12 mb-3">
                            <label for="country_uuid" class="form-label">
                                <i class="fa fa-globe-americas me-1 text-primary"></i> {{ __('main.Country') }}
                            </label>
                            <select name="country_uuid" id="country_uuid" class="form-select">
                                <option value="">{{ __('Select Country') }}</option>
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-12 mb-3">
                            <label for="region_uuid" class="form-label">
                                <i class="fa fa-map-marked-alt me-1 text-primary"></i> {{ __('main.State/Region') }}
                            </label>
                            <select name="region_uuid" id="region_uuid" class="form-select">
                                <option value="">{{ __('Select Region') }}</option>
                            </select>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <label for="data_center" class="form-label">
                                <i class="fa fa-database me-1 text-primary"></i> {{ __('Product.puqProxmox.Data Center') }}
                            </label>
                            <input type="text" class="form-control" id="data_center" name="data_center" placeholder="ex: DC-1 Frankfurt">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="description" class="form-label">
                                <i class="fa fa-align-left me-1 text-primary"></i> {{ __('main.Description') }}
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="{{ __('Brief description or notes...') }}"></textarea>
                        </div>

                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <label for="local_private_network" class="form-label">
                                <i class="fa fa-tag me-1 text-primary"></i> {{ __('Product.puqProxmox.Local Private Network (CIDR)') }}
                            </label>
                            <input type="text" class="form-control" id="local_private_network" name="local_private_network" placeholder="Example: 192.168.0.0/24">
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

                PUQajax('{{ route('admin.api.Product.puqProxmox.cluster_group.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#fill_type").val(response.data?.fill_type);
                        $("#data_center").val(response.data?.data_center);
                        $("#local_private_network").val(response.data?.local_private_network);

                        $("#description").val(response.data?.description).textareaAutoSize().trigger('autosize');

                        var $element_country = $("#country_uuid");
                        initializeSelect2($element_country, '{{route('admin.api.countries.select.get')}}', response.data.country_data, 'GET', 1000, {});

                        var selected_country_uuid = $element_country.val();
                        var $element_region = $("#region_uuid");
                        initializeSelect2($element_region, '{{route('admin.api.regions.select.get')}}', response.data.region_data, 'GET', 1000, {},
                            {
                                selected_country_uuid: function () {
                                    return selected_country_uuid
                                }
                            });

                        function updateStateOptions() {
                            $element_region.empty().trigger('change');
                            initializeSelect2($element_region, '{{route('admin.api.regions.select.get')}}', '', 'GET', 1000, {},
                                {
                                    selected_country_uuid: function () {
                                        return selected_country_uuid
                                    }
                                });
                        }

                        $element_country.on('change', function () {
                            selected_country_uuid = $(this).val();
                            updateStateOptions();
                        });

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#clusterGroupForm");
                const formData = serializeForm($form);
                console.log(formData);

                PUQajax('{{ route('admin.api.Product.puqProxmox.cluster_group.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $('#deleteClusterGroup').on('click', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 50, null, 'DELETE');
                }
            });

            loadFormData();
        });
    </script>
@endsection
