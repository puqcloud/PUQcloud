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

@section('buttons')
    @parent
    @if($admin->hasPermission('clients-edit'))
        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>

        <button id="createAddress" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
            <i class="fa fa-plus"></i>
            {{__('main.Create Address')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')

    <div class="row">
        <div class="col-12 col-md-4">
            <form id="clientForm" novalidate="novalidate">
                <div id="clientCard" class="main-card mb-3 card">
                    <div class="card-body">

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
                            <div class="col-md-12">
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
                                    <label for="currency_uuid" class="form-label">{{__('main.Currency')}}</label>
                                    <select name="currency_uuid" id="currency_uuid"
                                            class="form-select mb-2 form-control"></select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="credit_limit" class="form-label">{{__('main.Credit Limit')}}</label>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <i class="fas fa-money-bill"></i>
                                        </div>
                                        <input id="credit_limit" name="credit_limit" class="form-control input-mask-trigger"
                                               value=""
                                               data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                                               im-insert="true" style="text-align: right;" inputmode="numeric">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label" for="admin_notes">{{__('main.Admin Notes')}}</label>
                                <div>
                                    <textarea name="admin_notes" id="admin_notes" class="form-control"
                                              rows="5"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-md-8">
            <div class="main-card mb-3 card">
                <div class="card-body">
                    <table style="width: 100%;" id="clientAddresses"
                           class="table table-hover table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>{{__('main.Name')}}</th>
                            <th>{{__('main.Contact')}}</th>
                            <th>{{__('main.Address')}}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>{{__('main.Name')}}</th>
                            <th>{{__('main.Contact')}}</th>
                            <th>{{__('main.Address')}}</th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                var $form = $('#clientForm');
                blockUI('clientCard');

                PUQajax('{{route('admin.api.client.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        $.each(response.data, function (key, value) {
                            var $element = $form.find('[name="' + key + '"]');
                            if ($element.length) {

                                if (key === 'language') {
                                    initializeSelect2($element[0], '{{route('admin.api.languages.select.get')}}', response.data.language_data, 'GET', 1000, {});
                                    return;
                                }

                                if (key === 'currency_uuid') {
                                    initializeSelect2($element[0], '{{route('admin.api.currencies.select.get')}}', response.data.currency_data, 'GET', 1000, {});
                                    return;
                                }

                                if (key === 'admin_notes') {
                                    if (value !== null) {
                                        $element.val(value);
                                    }
                                    $element.textareaAutoSize().trigger('autosize');
                                    return;
                                }

                                $element.val(value);
                            }
                        });
                        unblockUI('clientCard');

                    })

                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $(".input-mask-trigger").inputmask();

            loadFormData();

            var selected_country_uuid;
            var $tableId = $('#clientAddresses');
            var ajaxUrl = '{{ route('admin.api.client.addresses.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: 'name',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.name}</div>
                            <div class="widget-subheading">${row.type}</div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: 'contact_name',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.contact_name}</div>
                            <div class="widget-subheading">
                                ${row.contact_email ?? ''}
                                ${row.contact_phone ?? ''}
                            </div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: 'country_uuid',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left me-3">
                            <div class="avatar-icon-wrapper">
                                <div class="badge badge-bottom"></div>
                                    <div class="flag ${row.country.code} large mx-auto"></div>
                            </div>
                        </div>
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.address_1}, ${row.address_2 ? row.address_2 + ', ' : ''} ${row.postcode}</div>
                            <div class="widget-subheading">${row.city}, ${row.region.name}, ${row.country.name}</div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.put) {
                            btn = btn + renderEditButton(row.urls.put);
                        }

                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }

                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            $('#createAddress').on('click', function () {

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text(translate('Create'));

                const formHtml = `
    <form id="createForm" class="mx-auto">

    <div class="row">
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="type" class="form-label">${translate('Type')}</label>
                <select name="type" class="form-select mb-2 form-control">
                    <option value="billing">${translate('Billing')}</option>
                    <option value="shipping">${translate('Shipping')}</option>
                    <option value="service">${translate('Service')}</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="name" class="form-label">${translate('Name')}</label>
                <input name="name" id="name" type="text" class="form-control">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="contact_name" class="form-label">${translate('Contact')}</label>
                <input name="contact_name" id="contact_name" type="text" class="form-control">
            </div>
        </div>
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label class="form-label" for="phone_number">${translate('Phone Number')}</label>
                <div>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" style="width: 100%;">
                    <input id="country_code" type="hidden" name="country_code" value="+1">
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="contact_email" class="form-label">${translate('Email')}</label>
                <input name="contact_email" id="contact_email" type="email" class="form-control">
            </div>
        </div>
    </div>

    <div class="position-relative mb-3">
        <label for="address_1" class="form-label">${translate('Address 1')}</label>
        <input name="address_1" id="address_1" type="text" class="form-control">
    </div>

    <div class="position-relative mb-3">
        <label for="address_2" class="form-label">${translate('Address 2')}</label>
        <input name="address_2" id="address_2" type="text" class="form-control">
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="city" class="form-label">${translate('City')}</label>
                <input name="city" id="city" type="text" class="form-control">
            </div>
        </div>
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <div class="position-relative mb-3">
                    <label for="postcode" class="form-label">${translate('Postcode')}</label>
                    <input name="postcode" id="postcode" type="text" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="position-relative mb-3">
        <label for="country_uuid" class="form-label">${translate('Country')}</label>
        <select name="country_uuid" id="country_uuid" class="form-select mb-2 form-control"></select>
    </div>

    <div class="position-relative mb-3">
        <label for="region_uuid" class="form-label">${translate('State/Region')}</label>
        <select name="region_uuid" id="region_uuid" class="form-select mb-2 form-control"></select>
    </div>
</form>
    `;

                $modalBody.html(formHtml);

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

                var $element_country = $("#country_uuid");
                initializeSelect2($element_country, '{{route('admin.api.countries.select.get')}}', {}, 'GET', 1000,
                    {
                        dropdownParent: $('#universalModal')
                    });

                selected_country_uuid = $element_country.val();
                var $element_region = $("#region_uuid");

                function updateStateOptions() {
                    $element_region.empty().trigger('change');
                    initializeSelect2($element_region, '{{route('admin.api.regions.select.get')}}', '', 'GET', 1000, {
                            dropdownParent: $('#universalModal')
                        },
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
                $('#universalModal').modal('show');

            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text(translate('Edit'));

                const formHtml = `
    <form id="editForm" class="mx-auto">

    <div class="row">
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="type" class="form-label">${translate('Type')}</label>
                <select name="type" id="type" class="form-select mb-2 form-control">
                    <option value="billing">${translate('Billing')}</option>
                    <option value="shipping">${translate('Shipping')}</option>
                    <option value="service">${translate('Service')}</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="name" class="form-label">${translate('Name')}</label>
                <input name="name" id="name" type="text" class="form-control">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="contact_name" class="form-label">${translate('Contact')}</label>
                <input name="contact_name" id="contact_name" type="text" class="form-control">
            </div>
        </div>
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label class="form-label" for="phone_number">${translate('Phone Number')}</label>
                <div>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" style="width: 100%;">
                    <input id="country_code" type="hidden" name="country_code" value="+1">
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="contact_email" class="form-label">${translate('Email')}</label>
                <input name="contact_email" id="contact_email" type="email" class="form-control">
            </div>
        </div>
    </div>

    <div class="position-relative mb-3">
        <label for="address_1" class="form-label">${translate('Address 1')}</label>
        <input name="address_1" id="address_1" type="text" class="form-control">
    </div>

    <div class="position-relative mb-3">
        <label for="address_2" class="form-label">${translate('Address 2')}</label>
        <input name="address_2" id="address_2" type="text" class="form-control">
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <label for="city" class="form-label">${translate('City')}</label>
                <input name="city" id="city" type="text" class="form-control">
            </div>
        </div>
        <div class="col-md-6">
            <div class="position-relative mb-3">
                <div class="position-relative mb-3">
                    <label for="postcode" class="form-label">${translate('Postcode')}</label>
                    <input name="postcode" id="postcode" type="text" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="position-relative mb-3">
        <label for="country_uuid" class="form-label">${translate('Country')}</label>
        <select name="country_uuid" id="country_uuid" class="form-select mb-2 form-control"></select>
    </div>

    <div class="position-relative mb-3">
        <label for="region_uuid" class="form-label">${translate('State/Region')}</label>
        <select name="region_uuid" id="region_uuid" class="form-select mb-2 form-control"></select>
    </div>
</form>
    `;

                $modalBody.html(formHtml);

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


                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {

                        $('#name').val(response.data.name);
                        $('#type').val(response.data.type);
                        $('#contact_name').val(response.data.contact_name);
                        $('#contact_email').val(response.data.contact_email);
                        $('#address_1').val(response.data.address_1);
                        $('#address_2').val(response.data.address_2);

                        if (response.data.contact_phone !== null) {
                            iti.setNumber(response.data.contact_phone);
                        }

                        $('#city').val(response.data.city);
                        $('#postcode').val(response.data.postcode);

                        var $element_country = $("#country_uuid");
                        initializeSelect2($element_country, '{{route('admin.api.countries.select.get')}}', response.data.country_data, 'GET', 1000,
                            {
                                dropdownParent: $('#universalModal')
                            });

                        var selected_country_uuid = $element_country.val();
                        var $element_region = $("#region_uuid");
                        initializeSelect2($element_region, '{{route('admin.api.regions.select.get')}}', response.data.region_data, 'GET', 1000,
                            {
                                dropdownParent: $('#universalModal')
                            },
                            {
                                selected_country_uuid: function () {
                                    return selected_country_uuid
                                }
                            });

                        function updateStateOptions() {
                            $element_region.empty().trigger('change');
                            initializeSelect2($element_region, '{{route('admin.api.regions.select.get')}}', '', 'GET', 1000, {
                                    dropdownParent: $('#universalModal')
                                },
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

                        $('#universalModal').modal('show');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });


            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to delete this record?'))) {
                    PUQajax(modelUrl, null, 3000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $("#save").on("click", function (event) {
                const $form = $("#clientForm");
                event.preventDefault();
                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.client.put',$uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.client.address.post',$uuid)}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#editForm').length) {
                    var $form = $('#editForm');
                    var formData = serializeForm($form);
                    var modelUrl = $(this).data('model-url');

                    PUQajax(modelUrl, formData, 500, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
