@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
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
                    <i class="fas fa-credit-card icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{__('main.Transactions')}}
                    <div class="page-title-subheading">
                        {{ __('main.View and manage payments and charge transaction history') }}
                    </div>
                </div>
            </div>
            <div class="page-title-actions">

            </div>
        </div>
    </div>

    <div class="container px-0">
        <div id="mainCard" class="card shadow-sm">
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
            var $tableId = $('#transactions');
            var ajaxUrl = '{{ route('client.api.client.transactions.get') }}';
            var columnsConfig = [
                {
                    data: 'transaction_id',
                    render: function (data, type, row) {
                        const transactionId = row.transaction_id ?? '';
                        const heading = transactionId || translate('No Transaction ID');
                        const payment_gateway = row.payment_gateway_name;

                        return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left">
                        <div class="widget-chart-flex">
                            <div class="fsize-1">
                                <span>${renderTransactionType(row.type)} ${payment_gateway}</span>
                            </div>
                        </div>
                    <div class="widget-heading">${heading}</div>
                </div>
            </div>
        </div>`;
                    }
                },
                {
                    data: 'amount_gross',
                    render: function (data, type, row) {

                        return `
            <div class="widget-chart-content ">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${row.amount_gross_str}</span>
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

                        return `
            <div class="widget-chart-content ">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${row.amount_net_str}</span>
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

                        return `
            <div class="widget-chart-content ">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${row.balance_before_str}</span>
                            </div>
                        </div>
                        <div class="widget-chart-flex nowrap">
                            <div class="fsize-1" style="white-space: nowrap;">
                                <span>${row.balance_after_str}</span>
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
                }
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[6, 'desc']],
                responsive: true,
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: -1 }
                ]
            });

            function DataTableAddData() {
                return {};
            }

        });
    </script>
@endsection
