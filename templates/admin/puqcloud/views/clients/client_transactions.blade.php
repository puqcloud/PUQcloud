@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('finance-create'))
        <button id="create" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-plus"></i>
            {{__('main.Create')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="transactions" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Transaction ID')}}</th>
                    <th>{{__('main.Gross')}}</th>
                    <th>{{__('main.Net')}}</th>
                    <th>{{__('main.Balance')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th>{{__('main.Period')}}</th>
                    <th>{{__('main.Date')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Transaction ID')}}</th>
                    <th>{{__('main.Gross')}}</th>
                    <th>{{__('main.Net')}}</th>
                    <th>{{__('main.Balance')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th>{{__('main.Period')}}</th>
                    <th>{{__('main.Date')}}</th>
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

            var $tableId = $('#transactions');
            var ajaxUrl = '{{ route('admin.api.client.transactions.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: 'transaction_id',
                    render: function (data, type, row) {
                        const transactionId = row.transaction_id ?? '';
                        const uuid = row.uuid ?? '';
                        const heading = transactionId || translate('No Transaction ID');
                        const subheading = uuid ? `<div class="widget-subheading">${uuid}</div>` : '';
                        const payment_gateway = row.payment_gateway.name ? ' (' + row.payment_gateway.name + ')' : '';

                        return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left">
                        <div class="widget-chart-flex">
                            <div class="fsize-1">
                                <span>${renderTransactionType(row.type)}${payment_gateway}</span>
                            </div>
                        </div>
                    <div class="widget-heading">${heading}</div>
                    <div class="text-nowrap">${subheading}</div>
                </div>
            </div>
        </div>`;
                    }
                },
                {
                    data: 'amount_gross',
                    render: function (data, type, row) {

                        const code = row.currency_code || '';
                        const amount_gross = parseFloat(row.amount_gross || 0).toFixed(4);

                        return `
            <div class="widget-chart-content ">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${amount_gross}</span>
                                <small class="opacity-5">${code}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
                    }
                },
                {
                    data: 'amount_net',
                    render: function (data, type, row) {

                        const code = row.currency_code || '';
                        const amount_net = parseFloat(row.amount_net || 0).toFixed(4);

                        return `
            <div class="widget-chart-content ">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${amount_net}</span>
                                <small class="opacity-5">${code}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
                    }
                },
                {
                    data: 'balance_before',
                    render: function (data, type, row) {

                        const code = row.currency_code || '';
                        const balance_before = row.balance_before || 0;
                        const balance_after = row.balance_after || 0;

                        return `
            <div class="widget-chart-content ">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${balance_before}</span>
                                <small class="opacity-5">${code}</small>
                            </div>
                        </div>
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${balance_after}</span>
                                <small class="opacity-5">${code}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
                    }
                },
                {
                    data: 'description',
                    name: 'description',
                    render: function (data, type, row) {
                        data = linkify(data || '');
                        const index = data.indexOf(',');
                        if (index === -1) return data;
                        return data.slice(0, index + 1) + '<br>' + data.slice(index + 1);
                    }
                },
                {
                    data: 'period_start',
                    render: function (data, type, row) {

                        const period_start = formatDateWithoutTimezone(row.period_start);
                        const period_stop = formatDateWithoutTimezone(row.period_stop);
                        return `
            <div class="widget-chart-content ">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${period_start}</span>
                            </div>
                        </div>
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${period_stop}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
                    }
                },
                {
                    data: 'transaction_date',
                    render: function (data, type, row) {
                        return formatDateWithoutTimezone(data);
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) {
                            btn = btn + renderEditButton(row.urls.edit);
                        }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[6, 'desc']]
            });

            function DataTableAddData() {
                return {};
            }

            $('#create').on('click', function () {
                const modelUrl = $(this).data('model-url');
                const $modal = $('#universalModal');
                const $modalTitle = $modal.find('.modal-title');
                const $modalBody = $modal.find('.modal-body');
                const $modalSaveButton = $('#modalSaveButton');

                $modalSaveButton.data('modelUrl', modelUrl);
                $modalTitle.text(translate('Create'));

                const formHtml = `
        <form id="createForm" class="mx-auto">

            <div class="row">

                <div class="col-12 mb-3">
                    <label for="amount" class="form-label">${translate('Amount')}</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-money-bill"></i>
                        </span>
                        <input id="amount" name="amount" class="form-control input-mask-trigger"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                            inputmode="numeric" style="text-align: right;">
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label for="transaction_id" class="form-label">${translate('Transaction ID')}</label>
                    <input id="transaction_id" name="transaction_id" type="text" class="form-control">
                </div>

                <div class="col-12 mb-3">
                    <label for="description" class="form-label">${translate('Description')}</label>
                    <input id="description" name="description" type="text" class="form-control">
                </div>

            </div>
        </form>
    `;

                $modalBody.html(formHtml);
                $('.input-mask-trigger').inputmask();
                $modal.modal('show');

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.client.transaction.post',$uuid)}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection

