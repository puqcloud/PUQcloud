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
        <button id="createFundsProformaInvoice" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-plus"></i>
            {{__('main.Create Add Funds Proforma Invoice')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="invoices" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Type')}}</th>
                    <th>{{__('main.Number')}}</th>
                    <th>{{__('main.Issue Date')}}</th>
                    <th>{{__('main.Due Date')}}</th>
                    <th>{{__('main.Paid Date')}}</th>
                    <th>{{__('main.Total')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Home Company')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Type')}}</th>
                    <th>{{__('main.Number')}}</th>
                    <th>{{__('main.Issue Date')}}</th>
                    <th>{{__('main.Due Date')}}</th>
                    <th>{{__('main.Paid Date')}}</th>
                    <th>{{__('main.Total')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Home Company')}}</th>
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

            var $tableId = $('#invoices');
            var ajaxUrl = '{{ route('admin.api.client.invoices.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: 'type',
                    render: function (data, type, row) {
                        return renderInvoiceType(data);
                    }
                },
                {data: 'number', name: 'number'},
                {
                    data: 'issue_date',
                    render: function (data, type, row) {
                        return formatDateWithoutTimezone(data);
                    }
                },
                {
                    data: 'due_date',
                    render: function (data, type, row) {
                        return formatDateWithoutTimezone(data);
                    }
                },
                {
                    data: 'paid_date',
                    render: function (data, type, row) {
                        return formatDateWithoutTimezone(data);
                    }
                },
                {
                    data: 'total',
                    render: function (data, type, row) {
                        return renderCurrencyAmount(data, row.currency_code);
                    }
                },
                {
                    data: 'status',
                    render: function (data, type, row) {
                        return renderInvoiceStatus(data);
                    }
                },
                {data: 'home_company', render: function (data, type, row) {
                        return data.name;
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
                order: [[2, 'desc']]
            });

            function DataTableAddData() {
                return {};
            }

            $('#createFundsProformaInvoice').on('click', function () {
                const modelUrl = $(this).data('model-url');
                const $modal = $('#universalModal');
                const $modalTitle = $modal.find('.modal-title');
                const $modalBody = $modal.find('.modal-body');
                const $modalSaveButton = $('#modalSaveButton');

                $modalSaveButton.data('modelUrl', modelUrl);
                $modalTitle.text(translate('Create Add Funds Proforma Invoice'));

                const formHtml = `
        <form id="createFundsProformaInvoiceForm" class="mx-auto">
            <div class="row">
                <div class="col-12 mb-3">
                    <label for="amount" class="form-label">${translate('Net Amount')}</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-money-bill"></i>
                        </span>
                        <input id="amount" name="amount" class="form-control input-mask-trigger"
                            data-inputmask="'alias': 'numeric', 'groupSeparator': '', 'autoGroup': true, 'digits': 2, 'digitsOptional': false, 'prefix': '', 'placeholder': '0'"
                            inputmode="numeric" style="text-align: right;">
                    </div>
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
                if ($('#createFundsProformaInvoiceForm').length) {
                    var $form = $('#createFundsProformaInvoiceForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.client.invoice.proforma.add_funds.post',$uuid)}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            $tableId.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

        });
    </script>
@endsection

