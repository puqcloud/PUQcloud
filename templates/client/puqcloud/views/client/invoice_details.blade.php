@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fas fa-file-invoice icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{ __('main.Invoice') }}
                    <div class="page-title-subheading text-muted"></div>
                </div>
            </div>
            <div class="page-title-actions">
                <a href="{{ route('client.web.panel.client.invoices') }}"
                   class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
                    <i class="fa fa-arrow-left"></i> {{ __('main.Back to Invoices') }}
                </a>

                <a href="{{route('client.web.panel.client.invoice.payment', $uuid)}}"
                   id="pay_now"
                   class="payment-btn mb-2 me-2 btn btn-outline-2x btn-outline-success text-danger"
                   style="display: none;">
                    <i class="fa fa-credit-card me-1"></i> {{ __('main.Pay Now') }}
                </a>

                <a href="{{ route('client.api.client.invoice.pdf.get', $uuid) }}"
                   class="download-pdf-btn mb-2 me-2 btn btn-outline-2x btn-outline-primary">
                    <i class="fa fa-download me-1"></i> {{ __('main.PDF') }}
                </a>
            </div>
        </div>
    </div>

    <div class="container px-0">
        <div id="invoiceData">
            <div class="main-card card mb-1">
                <div class="card-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="position-relative mb-2 w-100">
                                <label for="number" class="form-label" id="type"></label>
                                <label for="number" class="form-label" id="status"></label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger fw-bold">#</div>
                                    <input name="number" id="number" type="text" class="form-control" disabled>
                                </div>
                            </div>
                            <div id="credit_note_buttons" class="d-flex flex-wrap gap-2 mb-2"></div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">

                            <div class="position-relative mb-3">
                                <table class="table table-striped">
                                    <tbody>
                                    <tr>
                                        <td>{{__('main.Tax')}}</td>
                                        <td>
                                            <div id="tax"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{__('main.Net')}}</td>
                                        <td>
                                            <div id="subtotal"></div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>{{__('main.Gross')}}</td>
                                        <td>
                                            <div id="total" class="fw-bold"></div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="position-relative mb-3">
                                <table class="table table-striped">
                                    <tbody>
                                    <tr>
                                        <td>{{__('main.Due')}}</td>
                                        <td>
                                            <div id="due_amount" class="fw-bold text-danger"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{__('main.Net Paid')}}</td>
                                        <td>
                                            <div id="paid_net_amount"></div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>{{__('main.Gross Paid')}}</td>
                                        <td>
                                            <div id="paid_gross_amount"></div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="position-relative mb-2 w-100">
                                <label for="issue_date" class="form-label">{{__('main.Issue Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="issue_date" id="issue_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="position-relative mb-2 w-100">
                                <label for="due_date" class="form-label">{{__('main.Due Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="due_date" id="due_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="position-relative mb-2 w-100">
                                <label for="paid_date" class="form-label">{{__('main.Paid Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="paid_date" id="paid_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="position-relative mb-2 w-100">
                                <label for="refunded_date" class="form-label">{{__('main.Refunded Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="refunded_date" id="refunded_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon" disabled>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>

            <div class="main-card mb-3 card">
                <div class="card-body">
                    <table style="width: 100%;" id="invoice_items"
                           class="table table-hover table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>{{__('main.Description')}}</th>
                            <th>{{__('main.Amount')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="main-card mb-3 card">
                <div class="card-body">
                    <table style="width: 100%;" id="transactions"
                           class="table table-hover table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>{{__('main.Transaction ID')}}</th>
                            <th>{{__('main.Payment Method')}}</th>
                            <th>{{__('main.Gross')}}</th>
                            <th>{{__('main.Net')}}</th>
                            <th>{{__('main.Date')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
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

            var currency_code;
            var due_amount = 0.00;
            var paid_net_amount = 0.00;
            var subtotal = 0.00;
            var $transactionsTable;
            var $invoiceItemsTable;

            function setupInvoiceData() {
                blockUI('invoiceData');

                PUQajax('{{route('client.api.client.invoice.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        currency_code = response.data?.currency_code;
                        due_amount = response.data?.due_amount;
                        paid_net_amount = response.data?.paid_net_amount;
                        subtotal = response.data?.subtotal;

                        $("#number").val(response.data?.number);
                        $("#type").html(renderInvoiceType(response.data?.type));
                        $("#status").html(renderInvoiceStatus(response.data?.status));

                        $("#issue_date").val(response.data?.issue_date);
                        $("#due_date").val(response.data?.due_date);
                        $("#paid_date").val(response.data?.paid_date);
                        $("#refunded_date").val(response.data?.refunded_date);


                        $("#total").html(response.data?.total_str);
                        $("#subtotal").html(response.data?.subtotal_str);
                        $("#tax").html(response.data?.tax_str);
                        $("#paid_net_amount").html(response.data?.paid_net_amount_str);
                        $("#paid_gross_amount").html(response.data?.paid_gross_amount_str);
                        $("#due_amount").html(response.data?.due_amount_str);

                        if (response.data?.status === 'unpaid') {
                            $("#pay_now").show();
                        }

                        unblockUI('invoiceData');
                    })

                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function setupInvoiceItemsTable() {
                var $tableId = $('#invoice_items');
                var ajaxUrl = '{{ route('client.api.client.invoice.items.get',$uuid) }}';
                var columnsConfig = [
                    {
                        data: 'description',
                        name: 'description',
                        render: function (data, type, row) {
                            const formatted = data
                                .split('\n')
                                .map(line => {
                                    if (line.trim().startsWith('*-')) {
                                        return '<small>' + line + '</small>';
                                    }
                                    return line;
                                })
                                .join('<br>');

                            if (row.notes === 'subtotal' || row.notes === 'total') {
                                return '<div style="text-align: right; font-weight: bold;">' + formatted + '</div>';
                            }
                            if (row.notes === 'tax') {
                                return '<div style="text-align: right;">' + formatted + '</div>';
                            }
                            return formatted;
                        }
                    },
                    {
                        data: 'amount',
                        className: 'text-start align-middle', // можно добавить nowrap если у тебя есть CSS
                        width: '1%',
                        render: function (data, type, row) {
                            const bold = row.notes === 'subtotal' || row.notes === 'total';
                            const style = 'white-space: nowrap; text-align: left;' + (bold ? ' font-weight: bold;' : '');
                            return `<div style="${style}">${row.amount_str}</div>`;
                        }
                    },
                ];
                $invoiceItemsTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                    "paging": false,
                    "searching": false,
                    "ordering": false,
                    "info": false,
                    "serverSide": false,
                    "order": [],
                    rowCallback: function (row, data) {
                        if (data.notes === 'subtotal') {
                            $(row).addClass('bg-warning');
                        }
                    }
                });

                function DataTableAddData() {
                    return {};
                }

            }

            function setupInvoiceTransactionsTable() {
                var $tableId = $('#transactions');
                var ajaxUrl = '{{ route('client.api.client.invoice.transactions.get',$uuid) }}';
                var columnsConfig = [
                    {
                        data: 'transaction_id',
                        render: function (data, type, row) {
                            const transactionId = row.transaction_id ?? '';
                            const heading = transactionId || translate('No Transaction ID');

                            return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left">
                        <div class="widget-chart-flex">
                            <div class="fsize-1">
                                <span>${renderTransactionType(row.type)}</span>
                            </div>
                        </div>
                    <div class="widget-heading">${heading}</div>
                </div>
            </div>
        </div>`;
                        }
                    },
                    {
                        data: 'payment_gateway_name',
                        render: function (data, type, row) {
                            const payment_gateway_name = row.payment_gateway_name ? row.payment_gateway_name : '';

                            return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left">
                        <div class="widget-chart-flex">
                            <div class="fsize-1">
                                <span>${payment_gateway_name}</span>
                            </div>
                        </div>
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
                        data: 'transaction_date',
                        render: function (data, type, row) {
                            return formatDateWithoutTimezone(data);
                        }
                    },
                ];

                $transactionsTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                    "paging": false,
                    "searching": false,
                    "ordering": false,
                    "info": false,
                    "serverSide": false,
                    "order": [],
                });

                function DataTableAddData() {
                    return {};
                }
            }

            setupInvoiceData();
            setupInvoiceItemsTable();
            setupInvoiceTransactionsTable();

        });

    </script>
@endsection
