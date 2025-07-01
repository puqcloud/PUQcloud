@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')
    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-exchange-alt"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Transactions')}}</span>
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
                                    {{__('main.Transactions')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="transactions" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Client')}}</th>
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
                    <th>{{__('main.Client')}}</th>
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
        var $tableId = $('#transactions');
        var ajaxUrl = '{{ route('admin.api.transactions.get') }}';
        var columnsConfig = [
            {
                data: 'client',
                render: function (data, type, row) {
                    const firstname = row.client.firstname ?? '';
                    const lastname = row.client.lastname ?? '';
                    const company = row.client.company_name ?? '';
                    const fullName = `${firstname} ${lastname}`.trim();
                    const displayName = fullName || company || 'Unnamed Client';

                    return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left">
                    <div class="widget-heading">
                        <a href="${row.urls.client_web}" target="_blank" rel="noopener noreferrer">
                            ${displayName}
                        </a>
                    </div>
                    ${company && fullName ? `<div class="widget-subheading">${company}</div>` : ''}
                </div>
            </div>
        </div>`;
                }
            },
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
            order: [[7, 'desc']]
        });

        function DataTableAddData() {
            return {};
        }
    </script>
@endsection
