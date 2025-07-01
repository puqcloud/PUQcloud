@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('finance-edit'))
        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.home_companies.home_company_header')
    <form id="home_company" class="mx-auto" novalidate>
        <!-- === Company Info === -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header fw-semibold fs-5">
                {{ __('main.Company Info') }}
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12 col-md-3">
                        <label for="name" class="form-label">{{ __('main.Name') }}</label>
                        <input type="text" class="form-control" id="name" name="name">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="company_name" class="form-label">{{ __('main.Company Name') }}</label>
                        <input type="text" class="form-control" id="company_name" name="company_name">
                    </div>

                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <div>
                            <label class="form-label d-block">{{ __('main.Default') }}</label>
                            <input type="checkbox" id="default" name="default" data-toggle="toggle"
                                   data-on="{{ __('main.Yes') }}" data-off="{{ __('main.No') }}">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="address_1" class="form-label">{{ __('main.Address 1') }}</label>
                        <input type="text" class="form-control" id="address_1" name="address_1">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="address_2" class="form-label">{{ __('main.Address 2') }}</label>
                        <input type="text" class="form-control" id="address_2" name="address_2">
                    </div>

                    <div class="col-12 col-sm-3 col-md-6 col-lg-3 col-xl-3">
                        <label for="postcode" class="form-label">{{ __('main.Postcode') }}</label>
                        <input type="text" class="form-control" id="postcode" name="postcode">
                    </div>

                    <div class="col-12 col-sm-3 col-md-6 col-lg-3 col-xl-3">
                        <label for="city" class="form-label">{{ __('main.City') }}</label>
                        <input type="text" class="form-control" id="city" name="city">
                    </div>

                    <div class="col-12 col-sm-3 col-md-6 col-lg-3 col-xl-3">
                        <label for="country_uuid" class="form-label">{{ __('main.Country') }}</label>
                        <select name="country_uuid" id="country_uuid" class="form-select"></select>
                    </div>

                    <div class="col-12 col-sm-3 col-md-6 col-lg-3 col-xl-3">
                        <label for="region_uuid" class="form-label">{{ __('main.State/Region') }}</label>
                        <select name="region_uuid" id="region_uuid" class="form-select"></select>
                    </div>

                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            $(".input-mask-trigger").inputmask();

            function loadFormData() {
                blockUI('home_company');
                const $form = $('#home_company');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.home_company.get', $uuid)}}', {}, 1500, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {

                                if ($element.is(':checkbox')) {
                                    $element.prop('checked', !!value).trigger('click');
                                    return;
                                }

                                if ($element.is('textarea')) {
                                    if (value !== null) {
                                        $element.val(value);
                                    }
                                    if (key === 'description') {
                                        $element.textareaAutoSize().trigger('autosize');
                                    }
                                    return;
                                }

                                $element.val(value);
                            }
                        });

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


                        if (response.data) {
                            unblockUI('home_company');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#home_company");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.home_company.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
