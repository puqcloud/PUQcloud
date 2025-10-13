@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Tax Rules')}}</span>
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
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.Tax Rules')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('finance-create'))
                    <button type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                            id="create">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </button>

                    <button type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                            id="create_canadian">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create Canadian Rules')}}
                    </button>

                    <button type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                            id="create_eu">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create EU Rules')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="countries"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Order')}}</th>
                    <th>{{__('main.Country')}}/{{__('main.Region')}}</th>
                    <th>{{__('main.Private Client')}}</th>
                    <th>{{__('main.Without TAX ID')}}</th>
                    <th>{{__('main.With TAX ID')}}</th>
                    <th>{{__('main.Home Company')}}</th>
                    <th>{{__('main.Taxes')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Order')}}</th>
                    <th>{{__('main.Country')}}/{{__('main.Region')}}</th>
                    <th>{{__('main.Private Client')}}</th>
                    <th>{{__('main.Without TAX ID')}}</th>
                    <th>{{__('main.With TAX ID')}}</th>
                    <th>{{__('main.Home Company')}}</th>
                    <th>{{__('main.Taxes')}}</th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var tableId = '#countries';
            var ajaxUrl = '{{ route('admin.api.tax_rules.get') }}';
            var columnsConfig = [
                {
                    data: "order",
                    render: function (data, type, row) {
                        return renderOrderButtons(row);
                    }
                },
                {
                    data: 'country_uuid',
                    render: function (data, type, row) {
                        const country = row.country?.name || translate('All');
                        const region = row.region?.name || translate('All');
                        return country + '<br>' + region;
                    }
                },
                {
                    data: "private_client", name: "private_client",
                    render: function (data, type, row) {
                        return renderStatus(!data);
                    }
                },
                {
                    data: "company_without_tax_id", name: "company_without_tax_id",
                    render: function (data, type, row) {
                        return renderStatus(!data);
                    }
                },
                {
                    data: "company_with_tax_id", name: "company_with_tax_id",
                    render: function (data, type, row) {
                        return renderStatus(!data);
                    }
                },
                {
                    data: "home_company", name: "home_company",
                    render: function (data, type, row) {
                        return row.home_company?.company_name;
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        if (!row.individual_tax_rate) {
                            return `<span style="color: #999;">${translate('As home company')}</span>`;
                        }

                        let lines = [];

                        if (row.tax_1_name && row.tax_1) {
                            lines.push(`<strong>${row.tax_1_name}</strong>: ${row.tax_1}%`);
                        }
                        if (row.tax_2_name && row.tax_2) {
                            lines.push(`<strong>${row.tax_2_name}</strong>: ${row.tax_2}%`);
                        }
                        if (row.tax_3_name && row.tax_3) {
                            lines.push(`<strong>${row.tax_3_name}</strong>: ${row.tax_3}%`);
                        }

                        if (lines.length > 0) {
                            return lines.join('<br>');
                        } else {
                            return `<span style="color: #999;">${translate('No taxes')}</span>`;
                        }
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
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                "paging": false,
                "searching": false,
                "ordering": false,
            });

            function DataTableAddData() {
                return {};
            }

            $dataTable.on('click', 'button.view-btn', function (e) {
                e.preventDefault();
                const modelUrl = $(this).data('model-url');
                PUQajax(modelUrl, null, 500, $(this), 'GET', null)
                    .then(function (response) {
                        displayModalData(response.data);
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

            function displayModalData(data) {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $('#universalModal #modalSaveButton').remove();
                $('#universalModal .modal-dialog').css({
                    'min-width': '90%',
                    'width': '90%'
                });
                $modalTitle.text(translate('Regions'));

                let formattedData = `
        <div class="row mb-2">
            <div class="col-5 font-weight-bold"><b>${translate('Region Name')}</b></div>
            <div class="col-5 font-weight-bold"><b>${translate('Native Name')}</b></div>
            <div class="col-2 font-weight-bold"><b>${translate('Code')}</b></div>
        </div>
    `;
                data.forEach(region => {
                    formattedData += `
        <div class="list-group-item">
            <div class="row mb-2">
                <div class="col-5">${region.name}</div>
                <div class="col-5">${region.native_name}</div>
                <div class="col-2">${region.code}</div>
            </div>
        </div>
        `;
                });
                $modalBody.html(formattedData);
                $('#universalModal').modal('show');
            }

            $('#create').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Create'));

                var formHtml = `
<form id="createForm">
    <div class="row">

        <div class="col-6">
            <div class="mb-3">
                <label for="country_uuid" class="form-label">${translate('Country')}</label>
                <select name="country_uuid" id="country_uuid" class="form-select"></select>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label for="region_uuid" class="form-label">${translate('State/Region')}</label>
                <select name="region_uuid" id="region_uuid" class="form-select"></select>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="private_client">${translate('Private Client')}</label>
                <div>
                    <input type="checkbox" id="private_client" name="private_client" data-toggle="toggle"
                       data-on="${translate('Yes')}" data-off="${translate('No')}" data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="company_without_tax_id">${translate('Company Without TAX ID')}</label>
                <div>
                    <input type="checkbox" id="company_without_tax_id" name="company_without_tax_id" data-toggle="toggle"
                       data-on="${translate('Yes')}" data-off="${translate('No')}" data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="company_with_tax_id">${translate('Company With TAX ID')}</label>
                <div>
                    <input type="checkbox" id="company_with_tax_id" name="company_with_tax_id" data-toggle="toggle"
                       data-on="${translate('Yes')}" data-off="${translate('No')}" data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="individual_tax_rate">${translate('Individual TAX Rate')}</label>
                <div>
                    <input type="checkbox" id="individual_tax_rate" name="individual_tax_rate" data-toggle="toggle"
                       data-on="${translate('Yes')}" data-off="${translate('No')}" data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_1_name">${translate('Tax 1 Name')}</label>
                <input type="text" class="form-control" name="tax_1_name" id="tax_1_name">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_1">${translate('Tax 1 Rate (%)')}</label>
                <div class="input-group">
                    <input class="form-control input-mask-trigger" id="tax_1" name="tax_1" inputmode="numeric"
                                           data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100">
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_2_name">${translate('Tax 2 Name')}</label>
                <input type="text" class="form-control" name="tax_2_name" id="tax_2_name">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_2">${translate('Tax 2 Rate (%)')}</label>
                <div class="input-group">
                    <input class="form-control input-mask-trigger" id="tax_2" name="tax_2" inputmode="numeric"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100">
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_3_name">${translate('Tax 3 Name')}</label>
                <input type="text" class="form-control" name="tax_3_name" id="tax_3_name">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_3">${translate('Tax 3 Rate (%)')}</label>
                <div class="input-group">
                    <input class="form-control input-mask-trigger" id="tax_3" name="tax_3" inputmode="numeric"
                                                                      data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100">
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="mb-3">
                <label for="home_company_uuid" class="form-label">${translate('Home Company')}</label>
                <select name="home_company_uuid" id="home_company_uuid" class="form-select"></select>
            </div>
        </div>

    </div>
</form>
`;

                $modalBody.html(formHtml);

                $('#private_client').bootstrapToggle();
                $('#company_without_tax_id').bootstrapToggle();
                $('#company_with_tax_id').bootstrapToggle();
                $('#individual_tax_rate').bootstrapToggle();
                $(".input-mask-trigger").inputmask();

                var $element_country = $("#country_uuid");
                initializeSelect2($element_country, '{{route('admin.api.countries.select.get',['empty'=>true])}}', {}, 'GET', 1000,
                    {dropdownParent: $('#universalModal')},
                    {},
                    null);

                var selected_country_uuid = $element_country.val();
                var $element_region = $("#region_uuid");

                function updateStateOptions() {
                    $element_region.empty().trigger('change');
                    initializeSelect2($element_region, '{{route('admin.api.regions.select.get',['empty'=>true])}}', '', 'GET', 1000,
                        {dropdownParent: $('#universalModal')},
                        {
                            selected_country_uuid: function () {
                                return selected_country_uuid;
                            }
                        },
                        null);
                }

                $element_country.on('change', function () {
                    selected_country_uuid = $(this).val();
                    updateStateOptions();
                });


                var $element_home_company = $("#home_company_uuid");
                initializeSelect2($element_home_company, '{{route('admin.api.home_companies.select.get')}}', {}, 'GET', 1000,
                    {dropdownParent: $('#universalModal')},
                    {},
                    null);

                $('#universalModal').modal('show');
            });

            $('#create_eu').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Create'));

                var formHtml = `
<form id="createEuForm">
    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <label for="home_company_uuid" class="form-label">${translate('Home Company')}</label>
                <select name="home_company_uuid" id="home_company_uuid" class="form-select"></select>
            </div>
        </div>
    </div>
</form>
`;

                $modalBody.html(formHtml);

                var $element_home_company = $("#home_company_uuid");
                initializeSelect2($element_home_company, '{{route('admin.api.home_companies.select.get')}}', {}, 'GET', 1000,
                    {dropdownParent: $('#universalModal')},
                    {},
                    null);

                $('#universalModal').modal('show');
            });

            $('#create_canadian').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Create'));

                var formHtml = `
<form id="createCanadianForm">
    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <label for="home_company_uuid" class="form-label">${translate('Home Company')}</label>
                <select name="home_company_uuid" id="home_company_uuid" class="form-select"></select>
            </div>
        </div>
    </div>
</form>
`;

                $modalBody.html(formHtml);

                var $element_home_company = $("#home_company_uuid");
                initializeSelect2($element_home_company, '{{route('admin.api.home_companies.select.get')}}', {}, 'GET', 1000,
                    {dropdownParent: $('#universalModal')},
                    {},
                    null);

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
    <form id="editForm">
        <div class="row">

        <div class="col-6">
            <div class="mb-3">
                <label for="country_uuid" class="form-label">${translate('Country')}</label>
                <select name="country_uuid" id="country_uuid" class="form-select"></select>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label for="region_uuid" class="form-label">${translate('State/Region')}</label>
                <select name="region_uuid" id="region_uuid" class="form-select"></select>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="private_client">${translate('Private Client')}</label>
                <div>
                    <input type="checkbox" id="private_client" name="private_client" data-toggle="toggle"
                       data-on="${translate('Yes')}" data-off="${translate('No')}" data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="company_without_tax_id">${translate('Company Without TAX ID')}</label>
                <div>
                    <input type="checkbox" id="company_without_tax_id" name="company_without_tax_id" data-toggle="toggle"
                       data-on="${translate('Yes')}" data-off="${translate('No')}" data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="company_with_tax_id">${translate('Company With TAX ID')}</label>
                <div>
                    <input type="checkbox" id="company_with_tax_id" name="company_with_tax_id" data-toggle="toggle"
                       data-on="${translate('Yes')}" data-off="${translate('No')}" data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="individual_tax_rate">${translate('Individual TAX Rate')}</label>
                <div>
                    <input type="checkbox" id="individual_tax_rate" name="individual_tax_rate" data-toggle="toggle"
                       data-on="${translate('Yes')}" data-off="${translate('No')}" data-onstyle="success" data-offstyle="danger">
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_1_name">${translate('Tax 1 Name')}</label>
                <input type="text" class="form-control" name="tax_1_name" id="tax_1_name">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_1">${translate('Tax 1 Rate (%)')}</label>
                <div class="input-group">
                    <input class="form-control input-mask-trigger" id="tax_1" name="tax_1" inputmode="numeric"
                                           data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100">
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_2_name">${translate('Tax 2 Name')}</label>
                <input type="text" class="form-control" name="tax_2_name" id="tax_2_name">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_2">${translate('Tax 2 Rate (%)')}</label>
                <div class="input-group">
                    <input class="form-control input-mask-trigger" id="tax_2" name="tax_2" inputmode="numeric"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100">
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_3_name">${translate('Tax 3 Name')}</label>
                <input type="text" class="form-control" name="tax_3_name" id="tax_3_name">
            </div>
        </div>

        <div class="col-6">
            <div class="mb-3">
                <label class="form-label" for="tax_3">${translate('Tax 3 Rate (%)')}</label>
                <div class="input-group">
                    <input class="form-control input-mask-trigger" id="tax_3" name="tax_3" inputmode="numeric"
                                                                      data-inputmask="'alias': 'numeric', 'groupSeparator': ',', 'autoGroup': true, 'digits': 3, 'digitsOptional': false, 'placeholder': '0', 'allowMinus': false, 'min': 0, 'max': 100">
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="mb-3">
                <label for="home_company_uuid" class="form-label">${translate('Home Company')}</label>
                <select name="home_company_uuid" id="home_company_uuid" class="form-select"></select>
            </div>
        </div>

    </div>
    </form>
    `;

                $modalBody.html(formHtml);

                PUQajax(modelUrl, {}, 500, $(this), 'GET')
                    .then(function (response) {

                        $(".input-mask-trigger").inputmask();

                        var $element_country = $("#country_uuid");
                        initializeSelect2($element_country, '{{route('admin.api.countries.select.get',['empty'=>true])}}', response.data.country_data||{}, 'GET', 1000,
                            {dropdownParent: $('#universalModal')},
                            {},
                            null);

                        var selected_country_uuid = $element_country.val();
                        var $element_region = $("#region_uuid");
                        updateStateOptions();
                        function updateStateOptions() {
                            $element_region.empty().trigger('change');
                            initializeSelect2($element_region, '{{route('admin.api.regions.select.get',['empty'=>true])}}', response.data.region_data||{}, 'GET', 1000,
                                {dropdownParent: $('#universalModal')},
                                {
                                    selected_country_uuid: function () {
                                        return selected_country_uuid;
                                    }
                                },
                                null);
                        }

                        $element_country.on('change', function () {
                            selected_country_uuid = $(this).val();
                            updateStateOptions();
                        });


                        var $element_home_company = $("#home_company_uuid");
                        initializeSelect2($element_home_company, '{{route('admin.api.home_companies.select.get')}}', response.data.home_company_data||{}, 'GET', 1000,
                            {dropdownParent: $('#universalModal')},
                            {},
                            null);

                        $('#private_client').prop('checked', response.data.private_client);
                        $('#company_without_tax_id').prop('checked', response.data.company_without_tax_id);
                        $('#company_with_tax_id').prop('checked', response.data.company_with_tax_id);
                        $('#individual_tax_rate').prop('checked', response.data.individual_tax_rate);
                        $('#private_client').bootstrapToggle();
                        $('#company_without_tax_id').bootstrapToggle();
                        $('#company_with_tax_id').bootstrapToggle();
                        $('#individual_tax_rate').bootstrapToggle();

                        $('#tax_1').val(response.data.tax_1);
                        $('#tax_1_name').val(response.data.tax_1_name);

                        $('#tax_2').val(response.data.tax_2);
                        $('#tax_2_name').val(response.data.tax_2_name);

                        $('#tax_3').val(response.data.tax_3);
                        $('#tax_3_name').val(response.data.tax_3_name);

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
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.tax_rule.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#createEuForm').length) {
                    var $form = $('#createEuForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.tax_rule.eu.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#createCanadianForm').length) {
                    var $form = $('#createCanadianForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.tax_rule.canadian.post')}}', formData, 500, $(this), 'POST', $form)
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

            $dataTable.on('click', '.move-up, .move-down', function () {
                var $button = $(this);
                var groupUUID = $button.data('uuid');
                var currentOrder = parseInt($button.data('order'), 10);
                var newOrder = currentOrder + ($button.hasClass('move-up') ? -1 : 1);
                var data = {
                    uuid: groupUUID,
                    new_order: newOrder
                };

                PUQajax('{{ route('admin.api.tax_rules.update_order.post') }}', data, 500, $button, 'POST')
                    .then(function (response) {
                        if (response.status === "success") {
                            $dataTable.ajax.reload(null, false);
                        }
                    });
            });
        });
    </script>
@endsection
