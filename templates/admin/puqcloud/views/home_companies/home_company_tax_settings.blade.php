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
        <input type="hidden" class="form-control" id="name" name="name">
        <!-- === Tax & ID Numbers === -->
        <div class="card mb-3">
            <div class="card-header">{{ __('main.European Tax Information') }}</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <label class="form-label" for="tax_local_id_name">{{ __('main.Local Tax ID Name') }}</label>
                            <input type="text" class="form-control mb-2" id="tax_local_id_name"
                                   name="tax_local_id_name">

                            <label class="form-label" for="tax_local_id">{{ __('main.Local Tax ID') }}</label>
                            <input type="text" class="form-control" id="tax_local_id" name="tax_local_id">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <label class="form-label" for="tax_eu_vat_id_name">{{ __('main.EU VAT ID Name') }}</label>
                            <input type="text" class="form-control mb-2" id="tax_eu_vat_id_name"
                                   name="tax_eu_vat_id_name">

                            <label class="form-label" for="tax_eu_vat_id">{{ __('main.EU VAT ID') }}</label>
                            <input type="text" class="form-control" id="tax_eu_vat_id" name="tax_eu_vat_id">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <label class="form-label"
                                   for="registration_number_name">{{ __('main.Registration Number Name') }}</label>
                            <input type="text" class="form-control mb-2" id="registration_number_name"
                                   name="registration_number_name">

                            <label class="form-label"
                                   for="registration_number">{{ __('main.Registration Number') }}</label>
                            <input type="text" class="form-control" id="registration_number" name="registration_number">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">{{ __('main.Country-Specific Tax Information') }}</div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- US Section -->
                    <div class="col-md-6">
                        <div class="border rounded bg-light p-3">
                            <h6 class="text-primary mb-3">{{ __('main.United States') }}</h6>
                            <div class="mb-3">
                                <label class="form-label" for="us_ein">{{ __('main.US EIN') }}</label>
                                <input type="text" class="form-control" id="us_ein" name="us_ein">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="us_state_tax_id">{{ __('main.US State Tax ID') }}</label>
                                <input type="text" class="form-control" id="us_state_tax_id" name="us_state_tax_id">
                            </div>
                            <div>
                                <label class="form-label" for="us_entity_type">{{ __('main.US Entity Type') }}</label>
                                <input type="text" class="form-control" id="us_entity_type" name="us_entity_type">
                            </div>
                        </div>
                    </div>

                    <!-- Canada Section -->
                    <div class="col-md-6">
                        <div class="border rounded bg-light p-3">
                            <h6 class="text-primary mb-3">{{ __('main.Canada') }}</h6>
                            <div class="mb-3">
                                <label class="form-label"
                                       for="ca_business_number">{{ __('main.CA Business Number') }}</label>
                                <input type="text" class="form-control" id="ca_business_number"
                                       name="ca_business_number">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"
                                       for="ca_gst_hst_number">{{ __('main.CA GST/HST Number') }}</label>
                                <input type="text" class="form-control" id="ca_gst_hst_number" name="ca_gst_hst_number">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"
                                       for="ca_pst_qst_number">{{ __('main.CA PST/QST Number') }}</label>
                                <input type="text" class="form-control" id="ca_pst_qst_number" name="ca_pst_qst_number">
                            </div>
                            <div>
                                <label class="form-label" for="ca_entity_type">{{ __('main.CA Entity Type') }}</label>
                                <input type="text" class="form-control" id="ca_entity_type" name="ca_entity_type">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- === Tax Rates === -->
        <div class="card mb-3">
            <div class="card-header">{{ __('main.Tax Rates') }}</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <div class="mb-3">
                                <label class="form-label" for="tax_1_name">{{ __('main.Tax 1 Name') }}</label>
                                <input type="text" class="form-control" name="tax_1_name" id="tax_1_name">
                            </div>
                            <div>
                                <label class="form-label" for="tax_1">{{ __('main.Tax 1 Rate (%)') }}</label>
                                <div class="input-group">
                                    <input class="form-control input-mask-trigger"
                                           id="tax_1"
                                           name="tax_1"
                                           data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100"
                                           inputmode="numeric"
                                           style="text-align: right;">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <div class="mb-3">
                                <label class="form-label" for="tax_2_name">{{ __('main.Tax 2 Name') }}</label>
                                <input type="text" class="form-control" name="tax_2_name" id="tax_2_name">
                            </div>
                            <div>
                                <label class="form-label" for="tax_2">{{ __('main.Tax 2 Rate (%)') }}</label>
                                <div class="input-group">
                                    <input class="form-control input-mask-trigger"
                                           id="tax_2"
                                           name="tax_2"
                                           data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100"
                                           inputmode="numeric"
                                           style="text-align: right;">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <div class="mb-3">
                                <label class="form-label" for="tax_3_name">{{ __('main.Tax 3 Name') }}</label>
                                <input type="text" class="form-control" name="tax_3_name" id="tax_3_name">
                            </div>
                            <div>
                                <label class="form-label" for="tax_3">{{ __('main.Tax 3 Rate (%)') }}</label>
                                <div class="input-group">
                                    <input class="form-control input-mask-trigger"
                                           id="tax_3"
                                           name="tax_3"
                                           data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100"
                                           inputmode="numeric"
                                           style="text-align: right;">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
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

                const locale = $('.locale.active').data('locale');
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.home_company.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData(locale);
                    });
            });

            loadFormData($('.locale.active').data('locale'));
        });
    </script>
@endsection
