@extends(config('template.admin.view') . '.layout.layout')

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

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-user-plus"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Create a New Client')}}</span>
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
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.clients')}}">{{ __('main.Clients') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.Create a New Client')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('clients-create'))
                    <button id="save" type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-save"></i> {{__('main.Save')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <form id="createForm" novalidate="novalidate">
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="main-card mb-3 card">
                    <div class="card-body">
                        <h5 class="card-title">{{__('main.Client/User Information')}}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="email" class="form-label">{{__('main.Email')}}</label>
                                    <input name="email" id="email" placeholder="{{__('main.Email')}}"
                                           type="email" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="password" class="form-label">{{__('main.Password')}}</label>
                                    <input name="password" id="password" placeholder="{{__('main.Password')}}"
                                           type="password" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="firstname" class="form-label">{{__('main.Firstname')}}</label>
                                    <input name="firstname" id="firstname" placeholder="{{__('main.Firstname')}}"
                                           type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="lastname" class="form-label">{{__('main.Lastname')}}</label>
                                    <input name="lastname" id="lastname" placeholder="{{__('main.Lastname')}}"
                                           type="text" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="company_name" class="form-label">{{__('main.Company Name')}}</label>
                                    <input name="company_name" id="company_name"
                                           placeholder="{{__('main.Company Name')}}"
                                           type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="tax_id" class="form-label">{{__('main.Tax ID')}}</label>
                                    <input name="tax_id" id="tax_id" placeholder="{{__('main.Tax ID')}}"
                                           type="text" class="form-control">
                                </div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="language" class="form-label">{{__('main.Language')}}</label>
                                    <select name="language" id="language"
                                            class="form-select mb-2 form-control"></select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label class="form-label" for="phone_number">{{__('main.Phone Number')}}</label>
                                    <div>
                                        <input type="text" class="form-control" id="phone_number" name="phone_number"
                                               style="width: 100%;">
                                        <input id="country_code" type="hidden" name="country_code" value="+1">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="currency_uuid" class="form-label">{{__('main.Currency')}}</label>
                                    <select name="currency_uuid" id="currency_uuid"
                                            class="form-select mb-2 form-control"></select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="status" class="form-label">{{__('main.Status')}}</label>
                                    <select name="status" class="form-select mb-2 form-control">
                                        <option value="new">{{__('main.New')}}</option>
                                        <option value="active">{{__('main.Active')}}</option>
                                        <option value="inactive">{{__('main.Inactive')}}</option>
                                        <option value="closed">{{__('main.Closed')}}</option>
                                        <option value="fraud">{{__('main.Fraud')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="main-card mb-3 card">
                    <div class="card-body">
                        <h5 class="card-title">{{__('main.Billing Address')}}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="address_1" class="form-label">{{__('main.Address 1')}}</label>
                                    <input name="address_1" id="address_1"
                                           type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="address_2" class="form-label">{{__('main.Address 2')}}</label>
                                    <input name="address_2" id="address_2"
                                           type="text" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="city" class="form-label">{{__('main.City')}}</label>
                                    <input name="city" id="city" type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="postcode" class="form-label">{{__('main.Postcode')}}</label>
                                    <input name="postcode" id="postcode" type="text" class="form-control">
                                </div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="country_uuid" class="form-label">{{__('main.Country')}}</label>
                                    <select name="country_uuid" id="country_uuid"
                                            class="form-select mb-2 form-control"></select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="position-relative mb-3">
                                    <label for="region_uuid" class="form-label">{{__('main.State/Region')}}</label>
                                    <select name="region_uuid" id="region_uuid"
                                            class="form-select mb-2 form-control"></select>
                                </div>
                            </div>
                        </div>

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
            var selected_country_uuid;

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

            var $element_language = $("#language");
            initializeSelect2($element_language, '{{route('admin.api.languages.select.get')}}', null, 'GET', 1000, {});

            var $element_currency = $("#currency_uuid");
            initializeSelect2($element_currency, '{{route('admin.api.currencies.select.get')}}', null, 'GET', 1000, {});

            var $element_country = $("#country_uuid");
            initializeSelect2($element_country, '{{route('admin.api.countries.select.get')}}', null, 'GET', 1000, {});


            var $element_region = $("#region_uuid");

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


            $('#save').on('click', function (event) {
                event.preventDefault();

                const $form = $('#createForm');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.client.post')}}', formData, 500, $(this), 'POST', $form);
            });
        });
    </script>
@endsection
