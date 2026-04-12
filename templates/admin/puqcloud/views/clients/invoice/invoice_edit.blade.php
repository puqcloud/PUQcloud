@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('clients-edit') and $admin->hasPermission('finance-edit'))

        <button id="publish" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-warning" style="display: none;">
            <i class="fa fa-cloud-upload-alt"></i> {{__('main.Publish')}}
        </button>

        <button id="add_payment" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success" style="display: none;">
            <i class="fas fa-money-bill-wave"></i> {{__('main.Add Payment')}}
        </button>

        <button id="add_item" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info" style="display: none;">
            <i class="fa fa-plus"></i> {{__('main.Add Item')}}
        </button>

        <div id="actions_dropdown" class="mb-2 me-2 btn-group" style="display: none;">
            <button type="button" data-bs-toggle="dropdown" class="dropdown-toggle btn btn-primary" aria-expanded="false">
                <i class="fas fa-cogs"></i> {{__('main.Actions')}}
            </button>
            <div class="dropdown-menu">
                <button id="pdf" type="button" class="dropdown-item">
                    <i class="fas fa-file-download me-2"></i> {{__('main.Download PDF')}}
                </button>
                <button id="resend_notification" type="button" class="dropdown-item" style="display: none;">
                    <i class="fas fa-envelope me-2"></i> {{__('main.Resend Notification')}}
                </button>
                <div class="dropdown-divider" id="actions_divider" style="display: none;"></div>
                <button id="to_draft" type="button" class="dropdown-item" style="display: none;">
                    <i class="fa fa-pencil-alt me-2"></i> {{__('main.To Draft')}}
                </button>
                <button id="cancel" type="button" class="dropdown-item" style="display: none;">
                    <i class="fa fa-times me-2"></i> {{__('main.Cancel')}}
                </button>
                <button id="make_refund" type="button" class="dropdown-item text-danger" style="display: none;">
                    <i class="fas fa-rotate-left me-2"></i> {{__('main.Make Refund')}}
                </button>
                <button id="delete" type="button" class="dropdown-item text-danger" style="display: none;">
                    <i class="fa fa-trash me-2"></i> {{__('main.Delete')}}
                </button>
            </div>
        </div>

    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')

    <form id="invoiceForm" novalidate="novalidate">

        @include(config('template.admin.view') .'.clients.invoice.top')

        <div class="row g-1 gy-1 d-flex align-items-stretch mb-2">

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                @include(config('template.admin.view') .'.clients.invoice.client')
            </div>

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                @include(config('template.admin.view') .'.clients.invoice.home_company')
            </div>

        </div>
    </form>

    @include(config('template.admin.view') .'.clients.invoice.invoice_items')

    @include(config('template.admin.view') .'.clients.invoice.invoice_transactions')

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var currency_code;
            var billing_purpose = '';
            var invoice_status = '';
            var due_amount = 0.00;
            var paid_net_amount = 0.00;
            var subtotal = 0.00;
            var $transactionsTable;
            var $invoiceItemsTable;

            function loadFormData() {
                blockUI('invoiceForm');

                PUQajax('{{route('admin.api.invoice.get',request()->get('edit'))}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        var $form = $("#invoiceForm");
                        $.each(response.data, function (key, value) {
                            var $element = $form.find('[name="' + key + '"]');
                            if ($element.length) {
                                $element.val(value);
                            }
                        });
                        currency_code = response.data?.currency_code;
                        billing_purpose = response.data?.billing_purpose || '';
                        invoice_status = response.data?.status || '';
                        due_amount = response.data?.due_amount;
                        paid_net_amount = response.data?.paid_net_amount;
                        subtotal = response.data?.subtotal;

                        $("#type").html(renderInvoiceType(response.data?.type));
                        $("#status").html(renderInvoiceStatus(response.data?.status));

                        var isStandalone = billing_purpose === 'standalone';
                        var st = response.data?.status;
                        var tp = response.data?.type;

                        // Main action buttons
                        $("#publish").toggle(st === 'draft');
                        $("#add_payment").toggle(st === 'unpaid');
                        $("#add_item").toggle(st === 'draft' && isStandalone);

                        // Dropdown always visible
                        $("#actions_dropdown").show();

                        // Dropdown items
                        var canResend = (tp === 'proforma' && st === 'unpaid') || (tp === 'invoice' && st === 'paid');
                        $("#resend_notification").toggle(canResend);
                        $("#to_draft").toggle(st === 'unpaid' && isStandalone);
                        $("#cancel").toggle(st === 'unpaid');
                        $("#delete").toggle(st === 'draft');

                        var showRefund = st === 'paid' && Math.abs(paid_net_amount) > 0.00001;
                        $("#make_refund").toggle(showRefund);

                        // Show divider if there are items below it
                        var hasSecondaryActions = (st === 'unpaid' && isStandalone) || st === 'unpaid' || st === 'draft' || showRefund;
                        $("#actions_divider").toggle(hasSecondaryActions && canResend);

                        if (response.data.reference_invoice_uuid) {
                            $('#go_to_invoice')
                                .data('uuid', response.data.reference_invoice_uuid)
                                .show();
                        } else {
                            $('#go_to_invoice').hide();
                        }

                        if (response.data.reference_proforma_uuid) {
                            $('#go_to_proforma')
                                .data('uuid', response.data.reference_proforma_uuid)
                                .show();
                        } else {
                            $('#go_to_proforma').hide();
                        }

                        if (response.data.reference_credit_note_uuids) {
                            const container = $('#credit_note_buttons');
                            container.empty();

                            Object.entries(response.data.reference_credit_note_uuids).forEach(([noteNumber, uuid]) => {
                                const button = $(`
            <button type="button"
                    class="btn btn-outline-danger btn-icon btn-outline-2x"
                    data-uuid="${uuid}">
                <i class="fa fa-file-alt"></i> ${translate('CN')} #${noteNumber}
            </button>
        `);
                                container.append(button);
                            });
                        }
                        initializeDatePicker($("#issue_date"), response.data?.issue_date);

                        if (isStandalone && response.data?.status === 'draft') {
                            $("#due_date").prop("disabled", false);
                            initializeDatePicker($("#due_date"), response.data?.due_date);
                            $("#due_date").off("apply.daterangepicker").on("apply.daterangepicker", function (ev, picker) {
                                var newDate = picker.startDate.format("YYYY-MM-DD HH:mm");
                                $(this).val(newDate);
                                PUQajax('{{route('admin.api.invoice.due_date.put',request()->get('edit'))}}', {due_date: newDate}, 500, null, 'PUT');
                            });
                        } else {
                            $("#due_date").prop("disabled", true);
                            if ($("#due_date").data('daterangepicker')) {
                                $("#due_date").data('daterangepicker').remove();
                            }
                        }

                        $("#total").html(renderCurrencyAmountSmall(response.data?.total, response.data?.currency_code));
                        $("#subtotal").html(renderCurrencyAmountSmall(response.data?.subtotal, response.data?.currency_code));
                        $("#tax").html(renderCurrencyAmountSmall(response.data?.tax, response.data?.currency_code));
                        $("#paid_net_amount").html(renderCurrencyAmountSmall(response.data?.paid_net_amount, response.data?.currency_code));
                        $("#paid_gross_amount").html(renderCurrencyAmountSmall(response.data?.paid_gross_amount, response.data?.currency_code));
                        $("#due_amount").html(renderCurrencyAmountSmall(response.data?.due_amount, response.data?.currency_code));

                        unblockUI('invoiceForm');
                    })

                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function setupInvoiceItemsTable() {
                var $tableId = $('#invoice_items');
                var ajaxUrl = '{{ route('admin.api.invoice.items.get',request()->get('edit')) }}';
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
                        className: 'nowrap',
                        width: '1%',
                        render: function (data, type, row) {
                            if (row.notes === 'subtotal' || row.notes === 'total') {
                                return '<div style="text-align: left; font-weight: bold;">' + renderCurrencyAmount(data, currency_code) + '</div>';
                            }
                            if (row.notes === 'tax') {
                                return '<div style="text-align: left;">' + renderCurrencyAmount(data, currency_code) + '</div>';
                            }
                            return renderCurrencyAmount(data, currency_code);
                        }
                    },
                    {
                        data: 'taxed',
                        className: 'nowrap',
                        width: '1%',
                        render: function (data, type, row) {
                            if (!row.notes) {
                                return renderStatus(!data);
                            }
                            return '';
                        }
                    },
                    {
                        data: 'urls',
                        className: "center nowrap",
                        width: '1%',
                        render: function (data, type, row) {
                            if (row.notes || billing_purpose !== 'standalone' || invoice_status !== 'draft') {
                                return '';
                            }
                            var btn = '<div class="d-flex flex-nowrap gap-1">';
                            btn += '<button class="btn btn-sm btn-outline-primary edit-item-btn" data-uuid="' + row.uuid + '" data-description="' + (row.description || '').replace(/"/g, '&quot;') + '" data-amount="' + row.amount + '" data-taxed="' + (row.taxed ? '1' : '0') + '"><i class="fa fa-pencil-alt"></i></button>';
                            btn += '<button class="btn btn-sm btn-outline-danger delete-item-btn" data-uuid="' + row.uuid + '"><i class="fa fa-trash"></i></button>';
                            btn += '</div>';
                            return btn;
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
                var ajaxUrl = '{{ route('admin.api.invoice.transactions.get',request()->get('edit')) }}';
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
                        data: 'transaction_date',
                        render: function (data, type, row) {
                            return formatDateWithoutTimezone(data);
                        }
                    },
                    {
                        data: 'urls',
                        className: "center",
                        render: function (data, type, row) {
                            return '';
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

            loadFormData();
            setupInvoiceItemsTable();
            setupInvoiceTransactionsTable();

            $("#publish").on("click", function (event) {
                PUQajax('{{route('admin.api.invoice.publish.put',request()->get('edit'))}}', {}, 5000, $(this), 'PUT')
                    .then(function (response) {
                        loadFormData();
                        $invoiceItemsTable.ajax.reload(null, false);
                        $transactionsTable.ajax.reload(null, false);
                    });
            });

            $("#delete").on("click", function (event) {
                handleConfirmAction({
                    message: 'Are you sure you want to delete this item?',
                    style: 'danger',
                    onConfirm: function () {
                        PUQajax('{{route('admin.api.invoice.delete',request()->get('edit'))}}', {}, 50, null, 'DELETE');
                    }
                });
            });

            $("#cancel").on("click", function (event) {
                handleConfirmAction({
                    message: 'Are you sure you want to cancel?',
                    style: 'warning',
                    onConfirm: function () {
                        PUQajax('{{route('admin.api.invoice.cancel.put',request()->get('edit'))}}', {}, 5000, null, 'PUT')
                            .then(function (response) {
                                loadFormData();
                                $invoiceItemsTable.ajax.reload(null, false);
                                $transactionsTable.ajax.reload(null, false);
                            });
                    }
                });
            });

            $('#add_payment').on('click', function () {
                const modelUrl = $(this).data('model-url');
                const $modal = $('#universalModal');
                const $modalTitle = $modal.find('.modal-title');
                const $modalBody = $modal.find('.modal-body');
                const $modalSaveButton = $('#modalSaveButton');

                $modalSaveButton.data('modelUrl', modelUrl);
                $modalTitle.text(translate('Add Payment'));

                const formHtml = `
        <form id="addPaymentForm" class="mx-auto">

            <div class="row">

                <div class="col-12 mb-3">
                    <label for="amount" class="form-label">${translate('Gross Amount')}</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-money-bill"></i>
                        </span>
                        <input id="amount" name="amount" class="form-control input-mask-trigger" value="${due_amount}"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                            inputmode="numeric" style="text-align: right;">
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label for="fees" class="form-label">${translate('Fees')}</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-money-bill"></i>
                        </span>
                        <input id="fees" name="fees" class="form-control input-mask-trigger"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                            inputmode="numeric" style="text-align: right;">
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="payment_gateway_uuid" class="form-label">${translate('Payment Gateway')}</label>
                            <select name="payment_gateway_uuid" id="payment_gateway_uuid" class="form-select mb-2 form-control"></select>
                        </div>
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

                var $elementModule = $modalBody.find('[name="payment_gateway_uuid"]');
                initializeSelect2($elementModule, '{{route('admin.api.invoice.payment_gateways.select.get',request()->get('edit'))}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $modal.modal('show');

            });

            $('#make_refund').on('click', function () {
                const modelUrl = $(this).data('model-url');
                const $modal = $('#universalModal');
                const $modalTitle = $modal.find('.modal-title');
                const $modalBody = $modal.find('.modal-body');
                const $modalSaveButton = $('#modalSaveButton');

                $modalSaveButton.data('modelUrl', modelUrl);
                $modalTitle.text(translate('Make Refund'));

                const formHtml = `
        <form id="makeRefundForm" class="mx-auto">

            <div class="row">

                <div class="col-12 mb-3">
                    <label for="amount" class="form-label">${translate('Net Amount')}</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-money-bill"></i>
                        </span>
                        <input id="amount" name="amount" class="form-control input-mask-trigger" value="${paid_net_amount}"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                            inputmode="numeric" style="text-align: right;">
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label for="fees" class="form-label">${translate('Fees')}</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-money-bill"></i>
                        </span>
                        <input id="fees" name="fees" class="form-control input-mask-trigger"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                            inputmode="numeric" style="text-align: right;">
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="payment_gateway_uuid" class="form-label">${translate('Payment Gateway')}</label>
                            <select name="payment_gateway_uuid" id="payment_gateway_uuid" class="form-select mb-2 form-control"></select>
                        </div>
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

                var $elementModule = $modalBody.find('[name="payment_gateway_uuid"]');
                initializeSelect2($elementModule, '{{route('admin.api.invoice.payment_gateways.select.get',request()->get('edit'))}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $modal.modal('show');

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                if ($('#addPaymentForm').length) {
                    var $form = $('#addPaymentForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.invoice.add_payment.post',request()->get('edit'))}}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            loadFormData();
                            $invoiceItemsTable.ajax.reload(null, false);
                            $transactionsTable.ajax.reload(null, false);
                        });
                }

                if ($('#makeRefundForm').length) {
                    var $form = $('#makeRefundForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.invoice.make_refund.post',request()->get('edit'))}}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            loadFormData();
                            $invoiceItemsTable.ajax.reload(null, false);
                            $transactionsTable.ajax.reload(null, false);
                        });
                }
            });

            $('#go_to_invoice, #go_to_proforma').on('click', function () {
                const uuid = $(this).data('uuid');
                if (uuid) {
                    const url = window.routes.adminRedirect.replace('__label__', 'invoice').replace('__uuid__', uuid);
                    window.open(url, '_blank');
                }
            });

            $('#credit_note_buttons').on('click', 'button', function () {
                const uuid = $(this).data('uuid');
                if (uuid) {
                    const url = window.routes.adminRedirect
                        .replace('__label__', 'invoice')
                        .replace('__uuid__', uuid);
                    window.open(url, '_blank');
                }
            });

            $("#to_draft").on("click", function (event) {
                handleConfirmAction({
                    message: 'Are you sure you want to return this invoice to draft?',
                    style: 'warning',
                    onConfirm: function () {
                        PUQajax('{{route('admin.api.invoice.to_draft.put',request()->get('edit'))}}', {}, 5000, null, 'PUT')
                            .then(function (response) {
                                loadFormData();
                                $invoiceItemsTable.ajax.reload(null, false);
                                $transactionsTable.ajax.reload(null, false);
                            });
                    }
                });
            });

            $("#resend_notification").on("click", function (event) {
                handleConfirmAction({
                    message: 'Are you sure you want to resend the notification?',
                    style: 'info',
                    onConfirm: function () {
                        PUQajax('{{route('admin.api.invoice.resend_notification.post',request()->get('edit'))}}', {}, 5000, null, 'POST');
                    }
                });
            });

            function showItemModal(title, data, saveCallback) {
                const $modal = $('#universalModal');
                const $modalTitle = $modal.find('.modal-title');
                const $modalBody = $modal.find('.modal-body');
                const $modalSaveButton = $('#modalSaveButton');

                $modalTitle.text(title);

                const formHtml = `
        <form id="invoiceItemForm" class="mx-auto">
            <div class="row">
                <div class="col-12 mb-3">
                    <label for="item_description" class="form-label">${translate('Description')}</label>
                    <textarea id="item_description" name="description" class="form-control" rows="3">${data.description || ''}</textarea>
                </div>
                <div class="col-12 mb-3">
                    <label for="item_amount" class="form-label">${translate('Net Amount')}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                        <input id="item_amount" name="amount" class="form-control input-mask-trigger" value="${data.amount || ''}"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                            inputmode="numeric" style="text-align: right;">
                    </div>
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="item_taxed" name="taxed" ${data.taxed !== false ? 'checked' : ''}>
                        <label class="form-check-label" for="item_taxed">${translate('Taxed')}</label>
                    </div>
                </div>
            </div>
        </form>
    `;

                $modalBody.html(formHtml);
                $('.input-mask-trigger').inputmask();
                $modal.modal('show');

                $modalSaveButton.off('click.itemSave').on('click.itemSave', function (event) {
                    event.preventDefault();
                    if ($('#invoiceItemForm').length) {
                        var $form = $('#invoiceItemForm');
                        var formData = serializeForm($form);
                        formData.taxed = $('#item_taxed').is(':checked') ? 1 : 0;
                        saveCallback(formData, $form);
                    }
                });
            }

            $('#add_item').on('click', function () {
                showItemModal(translate('Add Item'), {taxed: true}, function (formData, $form) {
                    PUQajax('{{route('admin.api.invoice.item.post',request()->get('edit'))}}', formData, 500, null, 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            loadFormData();
                            $invoiceItemsTable.ajax.reload(null, false);
                        });
                });
            });

            $('#invoice_items').on('click', '.edit-item-btn', function () {
                var uuid = $(this).data('uuid');
                var data = {
                    description: $(this).data('description'),
                    amount: $(this).data('amount'),
                    taxed: $(this).data('taxed') == '1'
                };
                showItemModal(translate('Edit Item'), data, function (formData, $form) {
                    var url = '{{route('admin.api.invoice.item.put',[request()->get('edit'), '__ITEM__'])}}'.replace('__ITEM__', uuid);
                    PUQajax(url, formData, 500, null, 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            loadFormData();
                            $invoiceItemsTable.ajax.reload(null, false);
                        });
                });
            });

            $('#invoice_items').on('click', '.delete-item-btn', function () {
                var uuid = $(this).data('uuid');
                handleConfirmAction({
                    message: 'Are you sure you want to delete this item?',
                    style: 'danger',
                    onConfirm: function () {
                        var url = '{{route('admin.api.invoice.item.delete',[request()->get('edit'), '__ITEM__'])}}'.replace('__ITEM__', uuid);
                        PUQajax(url, {}, 500, null, 'DELETE')
                            .then(function (response) {
                                loadFormData();
                                $invoiceItemsTable.ajax.reload(null, false);
                            });
                    }
                });
            });

            $('#pdf').on('click', function () {
                window.open(`{{route('admin.api.invoice.pdf.get', request()->get('edit'))}}`, '_blank');
            });
        });
    </script>
@endsection

