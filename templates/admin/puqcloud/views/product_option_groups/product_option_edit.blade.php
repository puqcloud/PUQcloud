@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('product-groups-management'))
        <button id="add_price" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
            <i class="fa fa-plus"></i> {{__('main.Add Price')}}
        </button>

        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.product_option_groups.product_option_group_header')

    <div class="mb-3 card">
        <div class="card-header-tab card-header">
            <div class="card-header-title font-size-lg text-capitalize fw-normal">
                <i class="header-icon lnr-dice me-3 text-muted opacity-6"></i>
                {{ __('main.Editing an Option') }}
            </div>
        </div>

        <div class="card-body">
            <form id="product_option" class="mx-auto" novalidate="novalidate">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">

                            <div class="col-12 col-sm-4 col-md-4 col-lg mb-1">
                                <label class="form-label" for="key">{{__('main.Key')}}</label>
                                <div>
                                    <input type="text" class="form-control input-mask-trigger"
                                           id="key"
                                           name="key"
                                           placeholder="{{__('main.Key')}}">
                                </div>
                            </div>

                            <div class="col-12 col-sm-4 col-md-4 col-lg mb-1">
                                <label class="form-label" for="key">{{__('main.Value')}}</label>
                                <div>
                                    <input type="text" class="form-control input-mask-trigger"
                                           id="value"
                                           name="value"
                                           placeholder="{{__('main.Value')}}">
                                </div>
                            </div>

                            <div class="col-12 col-sm-4 col-md-4 col-lg mb-1">
                                <label class="form-label" for="disable">{{__('main.Hidden')}}</label>
                                <div>
                                    <input type="checkbox" data-toggle="toggle" data-on="{{__('main.Yes')}}"
                                           id="hidden"
                                           name="hidden"
                                           data-off="{{__('main.No')}}" data-onstyle="danger"
                                           data-offstyle="success">
                                </div>
                            </div>

                            <div class="col-12 mb-1">
                                <label class="form-label" for="notes">{{__('main.Notes')}}</label>
                                <div><textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>

                        </div>

                        <div class="row" id="filepond-container"></div>

                    </div>
                </div>

                <div class="card mb-3">
                    <div class="tabs-lg-alternate card-header">
                        <ul class="nav nav-justified">
                            @php($i=0)
                            @foreach($locales as $key => $locale)
                                <li class="nav-item">
                                    <a data-bs-toggle="tab" href="#tab-{{$i}}"
                                       class="nav-link locale @if($i === 0) active @endif"
                                       data-locale="{{ $key }}">
                                        <div class="widget-number">
                                            <div class="fi fi-{{$locale['flag']}} large mx-auto"></div>
                                        </div>
                                        <div class="tab-subheading">{{$locale['name']}}</div>
                                    </a>
                                </li>
                                @php($i++)
                            @endforeach
                        </ul>
                    </div>
                    <div class="tab-content mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="row">
                                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 mb-1">
                                        <label class="form-label" for="name">{{__('main.Name')}}</label>
                                        <div>
                                            <input type="text" class="form-control input-mask-trigger"
                                                   id="name"
                                                   name="name"
                                                   placeholder="{{__('main.Name')}}">
                                        </div>

                                        <label class="form-label"
                                               for="short_description">{{__('main.Short Description')}}</label>
                                        <div>
                                            <input type="text" class="form-control input-mask-trigger"
                                                   id="short_description"
                                                   name="short_description"
                                                   placeholder="{{__('main.Short Description')}}">
                                        </div>

                                    </div>
                                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 mb-1">
                                        <div class="form-group">
                                            <label for="description">{{__('main.Description')}}</label>
                                            <textarea name="description" id="description" class="form-control"
                                                      rows="5"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="main-card mb-3 card">
            <div class="card-body">
                <table style="width: 100%;" id="product_option_pricing"
                       class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{__('main.Period')}}</th>
                        <th>{{__('main.Currency')}}</th>
                        <th>{{__('main.setup')}}</th>
                        <th>{{__('main.base')}}</th>
                        <th>{{__('main.idle')}}</th>
                        <th>{{__('main.switch_down')}}</th>
                        <th>{{__('main.switch_up')}}</th>
                        <th>{{__('main.uninstall')}}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>{{__('main.Period')}}</th>
                        <th>{{__('main.Currency')}}</th>
                        <th>{{__('main.setup')}}</th>
                        <th>{{__('main.base')}}</th>
                        <th>{{__('main.idle')}}</th>
                        <th>{{__('main.switch_down')}}</th>
                        <th>{{__('main.switch_up')}}</th>
                        <th>{{__('main.uninstall')}}</th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent

    <script>

        $(document).ready(function () {

            function loadFormData(locale) {
                blockUI('product_option');
                const $form = $('#product_option');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.product_option.get', request()->get('edit'))}}?locale=' + locale, {}, 50, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            renderImageFields(response.data.images, 'col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4');
                        }
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
                            unblockUI('product_option');
                        }


                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#product_option");
                event.preventDefault();

                const locale = $('.locale.active').data('locale');
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.product_option.put', request()->get('edit'))}}?locale=' + locale, formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData(locale);
                    });
            });

            loadFormData($('.locale.active').data('locale'));

            var $tableId = $('#product_option_pricing');
            var ajaxUrl = '{{ route('admin.api.product_option.prices.get',request()->get('edit')) }}';
            var columnsConfig = [
                {
                    data: 'period', render: function (data, type, row) {
                        return translate(data)
                    }
                },
                {data: 'currency', name: 'currency'},
                {data: 'setup', name: 'setup'},
                {data: 'base', name: 'base'},
                {data: 'idle', name: 'idle'},
                {data: 'switch_down', name: 'switch_down'},
                {data: 'switch_up', name: 'switch_up'},
                {data: 'uninstall', name: 'uninstall'},

                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) {
                            btn += renderEditButton(row.urls.edit);
                        }
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                "paging": false,
                "ordering": false,
                "searching": false
            });

            function DataTableAddData() {
                return {};
            }


            $('.locale').on('click', function () {
                const locale = $(this).data('locale');
                loadFormData(locale);
            });

            function updateInputFields(period, priceData) {
                const $inputGroupContainer = $('#input-group-container');
                $inputGroupContainer.empty();

                var fields = ['setup', 'base', 'idle', 'switch_up', 'switch_down', 'uninstall'];

                if (period === 'one-time') {
                    fields = ['setup', 'base'];
                } else if (period === 'hourly') {
                    fields = ['setup', 'base', 'idle', 'switch_up', 'uninstall'];
                } else if (period === 'triennially') {
                    fields = ['setup', 'base', 'idle', 'switch_down', 'uninstall'];
                }

                fields.forEach(function (field) {
                    const value = priceData[field] || '';
                    const label = translate(field);

                    const readonly = period === 'one-time' && ['idle', 'switch_up', 'switch_down', 'uninstall'].includes(field) ? 'readonly' : '';

                    const input = `
            <div class="mb-3">
                <label for="${field}" class="form-label">${label}</label>
                <div class="input-group mb-2">
                    <div class="input-group-text">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <input id="${field}" name="${field}" class="form-control input-mask-trigger"
                       value="${value}"
                       data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                       im-insert="true" style="text-align: right;" inputmode="numeric" ${readonly}>
                    </div>
                    </div>
        `;
                    $inputGroupContainer.append(input);
                });

                $(document).ready(() => {
                    $(".input-mask-trigger").inputmask();
                });
            }

            $('#add_price').on('click', function (event) {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Add Price'));

                const formattedData = `
        <form id="addForm" class="col-md-10 mx-auto">
            <div class="mb-3">
                <label for="currency_uuid" class="form-label">` + translate('Currency') + `</label>
                <select class="form-select mb-2 form-control" name="currency_uuid" id="currency_uuid"></select>
            </div>
            <div class="mb-3">
                <label for="period" class="form-label">` + translate('Period') + `</label>
                <select class="form-select mb-2 form-control" name="period" id="period"></select>
            </div>
            <div class="mb-3" id="input-group-container">
            </div>
        </form>
    `;

                $modalBody.html(formattedData);
                var $elementCurrency = $modalBody.find('[name="currency_uuid"]');
                var $elementPeriod = $modalBody.find('[name="period"]');
                var $inputGroupContainer = $('#input-group-container');

                initializeSelect2($elementCurrency, '{{route('admin.api.currencies.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                initializeSelect2($elementPeriod, '{{route('admin.api.price.periods.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $elementPeriod.on('change', function () {
                    var selectedPeriod = $(this).val();
                    updateInputFields(selectedPeriod);
                });

                $('#universalModal').modal('show');

                function createInputField(id, label) {
                    return `
            <div class="mb-3">
                <label for="${id}" class="form-label">${label}</label>
                <div class="input-group mb-2">
                    <div class="input-group-text">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <input id="${id}" name="${id}" class="form-control input-mask-trigger" data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'" im-insert="true" style="text-align: right;" inputmode="numeric">
                </div>
            </div>
        `;
                }

                function updateInputFields(period) {
                    $inputGroupContainer.empty();

                    var fields = ['setup', 'base', 'idle', 'switch_up', 'switch_down', 'uninstall'];

                    if (period === 'one-time') {
                        fields = ['setup', 'base'];
                    } else if (period === 'hourly') {
                        fields = ['setup', 'base', 'idle', 'switch_up', 'uninstall'];
                    } else if (period === 'triennially') {
                        fields = ['setup', 'base', 'idle', 'switch_down', 'uninstall'];
                    }

                    fields.forEach(function (field) {
                        var label = translate(field);
                        var input = createInputField(field, label);
                        $inputGroupContainer.append(input);
                    });

                    $(".input-mask-trigger").inputmask();
                }
            });

            $tableId.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);
                $modalTitle.text(translate('Edit'));

                const formattedData = `
        <form id="editForm" class="col-md-10 mx-auto">
            <div class="mb-3">
                <label for="currency_code" class="form-label">${translate('Currency')}</label>
                <input type="text" class="form-control" id="currency_code" name="currency_code" readonly>
            </div>
            <div class="mb-3">
                <label for="period" class="form-label">${translate('Period')}</label>
                <input type="text" class="form-control" id="period" name="period" readonly>
            </div>
                <input type="hidden" class="form-control" id="price_uuid" name="price_uuid">
            <div class="mb-3" id="input-group-container">
            </div>
        </form>
    `;

                $modalBody.html(formattedData);

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        if (response.status === 'success') {
                            const priceData = response.data;

                            $('#currency_code').val(priceData.currency.code).prop('readonly', true);
                            $('#period').val(translate(priceData.period)).prop('readonly', true);
                            $('#price_uuid').val(translate(priceData.uuid)).prop('readonly', true);

                            const $inputGroupContainer = $('#input-group-container');
                            $inputGroupContainer.empty();

                            updateInputFields(priceData.period, priceData);

                            $('#universalModal').modal('show');
                        } else {
                            console.error('Error loading form data:', response.errors);
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

            $tableId.on('click', 'button.delete-btn', function (e) {
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

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#addForm').length) {
                    var $form = $('#addForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.product_option.price.post',request()->get('edit')) }}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#editForm').length) {
                    var $form = $('#editForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.product_option.price.put',request()->get('edit')) }}', formData, 1000, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
