@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('products-management'))
        <button id="add" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-plus"></i> {{__('main.Add')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.products.product_header')


    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="product_pricing" class="table table-hover table-striped table-bordered">
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

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var $tableId = $('#product_pricing');
            var ajaxUrl = '{{ route('admin.api.product.prices.get',$uuid) }}';
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
                paging: false,
                ordering: false,
                searching: false,
                columns: columnsConfig,

                rowCallback: function (row, data) {
                    if (data.default === 1) {
                        $(row).addClass('table-success');
                    }
                }
            });

            function DataTableAddData() {
                return {};
            }

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

            $('#add').on('click', function (event) {
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

                    $(document).ready(() => {
                        $(".input-mask-trigger").inputmask();
                    });
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

                if ($('#addForm').length) {
                    var $form = $('#addForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.product.price.post',$uuid) }}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#editForm').length) {
                    var $form = $('#editForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.product.price.put',$uuid) }}', formData, 1000, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
