@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        #phone_number {
            width: 100% !important;
        }
        .iti {
            width: 100%;
        }
    </style>
@endsection

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fas fa-building icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{__('main.Client Profile')}}
                    <div class="page-title-subheading">
                        {{__('main.Manage customer profile data')}}
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="save" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            </div>
        </div>
    </div>

    <div class="container   ">
        <div id="mainCard" class="card shadow-sm">
            <div class="card-body">
                <form id="profileForm" novalidate>
                    <div class="row g-3">
                        <!-- Firstname -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="firstname" class="form-label">
                                <i class="fas fa-user me-1"></i> {{ __('main.Firstname') }}
                            </label>
                            <input type="text" class="form-control" id="firstname" name="firstname" placeholder="{{ __('main.Firstname') }}">
                        </div>

                        <!-- Lastname -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="lastname" class="form-label">
                                <i class="fas fa-user me-1"></i> {{ __('main.Lastname') }}
                            </label>
                            <input type="text" class="form-control" id="lastname" name="lastname" placeholder="{{ __('main.Lastname') }}">
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="contact_email" class="form-label">
                                <i class="fas fa-envelope me-1"></i> {{ __('main.Email') }}
                            </label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="{{ __('main.Email') }}">
                        </div>

                        <!-- Company Name -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="company_name" class="form-label">
                                <i class="fas fa-building me-1"></i> {{ __('main.Company Name') }}
                            </label>
                            <input type="text" class="form-control" id="company_name" name="company_name" placeholder="{{ __('main.Company Name') }}">
                        </div>

                        <!-- Tax ID -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="tax_id" class="form-label">
                                <i class="fas fa-file-invoice-dollar me-1"></i> {{ __('main.Tax ID') }}
                            </label>
                            <input type="text" class="form-control" id="tax_id" name="tax_id" placeholder="{{ __('main.Tax ID') }}">
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="phone_number" class="form-label">
                                <i class="fas fa-phone me-1"></i> {{ __('main.Phone Number') }}
                            </label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number">
                            <input type="hidden" id="country_code" name="country_code" value="+1">
                        </div>

                        <!-- Language -->
                        <div class="col-md-6 col-xxl-4">
                            <label for="language" class="form-label">
                                <i class="fas fa-language me-1"></i> {{ __('main.Language') }}
                            </label>
                            <select id="language" name="language" class="form-select">
                                <!-- Populate options dynamically -->
                            </select>
                        </div>

                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">{{ __('main.Address Details') }}</h5>

                    <div class="row g-3">
                        <!-- Address 1 -->
                        <div class="col-md-12 col-xxl-6">
                            <label for="address_1" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i> {{ __('main.Address 1') }}
                            </label>
                            <input type="text" class="form-control" id="address_1" name="address_1">
                        </div>

                        <!-- Address 2 -->
                        <div class="col-md-12 col-xxl-6">
                            <label for="address_2" class="form-label">
                                {{ __('main.Address 2') }}
                            </label>
                            <input type="text" class="form-control" id="address_2" name="address_2">
                        </div>

                        <!-- City -->
                        <div class="col-md-6 col-xxl-3">
                            <label for="city" class="form-label">{{ __('main.City') }}</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>

                        <!-- Postcode -->
                        <div class="col-md-6 col-xxl-3">
                            <label for="postcode" class="form-label">{{ __('main.Postcode') }}</label>
                            <input type="text" class="form-control" id="postcode" name="postcode">
                        </div>

                        <!-- Country -->
                        <div class="col-md-6 col-xxl-3">
                            <label for="country_uuid" class="form-label">{{ __('main.Country') }}</label>
                            <select id="country_uuid" name="country_uuid" class="form-select">
                                <!-- Populate dynamically -->
                            </select>
                        </div>

                        <!-- Region -->
                        <div class="col-md-6 col-xxl-3">
                            <label for="region_uuid" class="form-label">{{ __('main.State/Region') }}</label>
                            <select id="region_uuid" name="region_uuid" class="form-select">
                                <!-- Populate dynamically -->
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

            const $input = $("#phone_number");
            const iti = window.intlTelInput($input[0], {
                separateDialCode: true,
                initialCountry: navigator.language.split('-')[1] || 'us',
            });

            $input.on('countrychange', function () {
                var $countryCodeInput = $("#country_code");
                var dialCode = iti.getSelectedCountryData().dialCode;
                $countryCodeInput.val('+' + dialCode);
            });

            function loadFormData() {
                blockUI('mainCard');
                var $form = $('#profileForm');

                PUQajax('{{route('client.api.client.profile.get')}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        $('#firstname').val(response.data.firstname);
                        $('#lastname').val(response.data.lastname);
                        $('#contact_email').val(response.data.contact_email);
                        $('#company_name').val(response.data.company_name);
                        $('#tax_id').val(response.data.tax_id);
                        if (response.data.contact_phone !== null) {
                            iti.setNumber(response.data.contact_phone);
                        }
                        initializeSelect2($('#language'), '{{route('client.api.languages.select.get')}}', response.data.language_data, 'GET', 1000, {});
                        $('#address_1').val(response.data.address_1);
                        $('#address_2').val(response.data.address_2);
                        $('#city').val(response.data.city);
                        $('#postcode').val(response.data.postcode);


                        var $element_country = $("#country_uuid");
                        initializeSelect2($element_country, '{{route('client.api.countries.select.get')}}', response.data.country_data, 'GET', 1000, {});

                        var selected_country_uuid = $element_country.val();
                        var $element_region = $("#region_uuid");
                        initializeSelect2($element_region, '{{route('client.api.regions.select.get')}}', response.data.region_data, 'GET', 1000, {},
                            {
                                selected_country_uuid: function () {
                                    return selected_country_uuid
                                }
                            });

                        function updateStateOptions() {
                            $element_region.empty().trigger('change');
                            initializeSelect2($element_region, '{{route('client.api.regions.select.get')}}', '', 'GET', 1000, {},
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
                            unblockUI('mainCard');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                var $form = $("#profileForm");
                event.preventDefault();
                const formData = serializeForm($form);

                PUQajax('{{ route('client.api.client.profile.put') }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData()
        });
    </script>
@endsection
