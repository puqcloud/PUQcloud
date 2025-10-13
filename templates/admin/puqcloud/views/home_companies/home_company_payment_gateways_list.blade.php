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
        <button type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                id="create">
            <i class="fa fa-plus"></i>
            {{__('main.Create')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.home_companies.home_company_header')
    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="payment_gateways"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Module')}}</th>
                    <th>{{__('main.Currencies')}}</th>
                    <th>{{__('main.Order')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Module')}}</th>
                    <th>{{__('main.Currencies')}}</th>
                    <th>{{__('main.Order')}}</th>
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

            var $paymentGatewaysTable;

            function setupPaymentGatewaysTable() {
                var $tableId = $('#payment_gateways');
                var ajaxUrl = '{{ route('admin.api.home_company.payment_gateways.get',$uuid) }}';
                var columnsConfig = [
                    {data: "key", name: "key"},
                    {
                        data: "module_data", name: "module_data",
                        render: function (data, type, row) {
                            if (row.module_data.name) {
                                return row.module_data.name;
                            } else {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'currencies',
                        name: 'currencies',
                        render: function (data, type, row) {
                            if (!Array.isArray(data)) return '';

                            return data.map(function (currency) {
                                return `<span class="badge bg-primary me-1">${currency.code}</span>`;
                            }).join(' ');
                        }
                    },
                    {
                        data: "order",
                        render: function (data, type, row) {
                            return renderOrderButtons(row);
                        }
                    },
                    {
                        data: 'urls',
                        className: "center",
                        orderable: false,
                        render: function (data, type, row) {
                            var btn = '';
                            if (row.urls.web_edit) {
                                btn = btn + renderEditButton(row.urls.web_edit);
                            }
                            if (row.urls.delete) {
                                btn = btn + renderDeleteButton(row.urls.delete);
                            }
                            return btn;
                        }
                    },
                ];
                $paymentGatewaysTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {order: [[3, 'asc']]});

                function DataTableAddData() {
                    return {};
                }

            }

            setupPaymentGatewaysTable();

            $('#create').on('click', function () {
                var $modalTitle = $('.modal-title');
                var $modalBody = $('.modal-body');

                $modalTitle.text(translate('Create Payment Gateway'));

                var formHtml = `
            <form id="createPaymentGatewayForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="key">${translate('Key')}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="key" name="key" placeholder="${translate('Key')}">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="module_uuid" class="form-label">${translate('Module')}</label>
                            <select name="module_uuid" id="module_uuid" class="form-select mb-2 form-control"></select>
                        </div>
                    </div>
                </div>

            </form>`;

                $modalBody.html(formHtml);

                var $elementModule = $modalBody.find('[name="module_uuid"]');
                initializeSelect2($elementModule, '{{route('admin.api.payment_modules.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                var $form = $('#createPaymentGatewayForm');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });
                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                if ($('#createPaymentGatewayForm').length) {
                    var $form = $('#createPaymentGatewayForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.home_company.payment_gateway.post',$uuid)}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $paymentGatewaysTable.ajax.reload(null, false);
                        });
                }
            });

            $paymentGatewaysTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

            $paymentGatewaysTable.on('click', '.move-up, .move-down', function () {
                var $button = $(this);
                var groupUUID = $button.data('uuid');
                var currentOrder = parseInt($button.data('order'), 10);
                var newOrder = currentOrder + ($button.hasClass('move-up') ? -1 : 1);
                var data = {
                    uuid: groupUUID,
                    new_order: newOrder
                };

                PUQajax('{{ route('admin.api.payment_gateway.update_order.post') }}', data, 50, $button, 'POST')
                    .then(function (response) {
                        if (response.status === "success") {
                            $paymentGatewaysTable.ajax.reload(null, false);
                        }
                    });
            });

            $paymentGatewaysTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to delete this record?'))) {
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $paymentGatewaysTable.ajax.reload(null, false);
                            }
                        });
                }
            });

        });
    </script>
@endsection


