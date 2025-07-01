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
                    {{ __('main.Invoices') }}
                    <div class="page-title-subheading text-muted">
                        {{ __('main.All your billing history and invoice management') }}
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
                <table style="width: 100%;" id="invoices" class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{__('main.Type')}}/{{__('main.Status')}}</th>
                        <th>{{__('main.Number')}}</th>
                        <th>{{__('main.Issue Date')}}</th>
                        <th>{{__('main.Due Date')}}</th>
                        <th>{{__('main.Total')}}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>{{__('main.Type')}}/{{__('main.Status')}}</th>
                        <th>{{__('main.Number')}}</th>
                        <th>{{__('main.Issue Date')}}</th>
                        <th>{{__('main.Due Date')}}</th>
                        <th>{{__('main.Total')}}</th>
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

            var $tableId = $('#invoices');
            var ajaxUrl = '{{ route('client.api.client.invoices.get') }}';
            var columnsConfig = [
                {
                    data: 'type',
                    render: function (data, type, row) {
                        const typeLabel = renderInvoiceType(data);
                        const statusLabel = renderInvoiceStatus(row.status);

                        return `
            <div class="fw-bold mb-1">
               ${typeLabel}
            </div>
            <div class="fw-bold mb-0">
            ${statusLabel}
            </div>
        `;
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
                    data: 'total',
                    render: function (data, type, row) {
                        return row.total_str;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.payment) {
                            btn = btn + renderPaymentButton(row.urls.payment);
                        }
                        if (row.urls.pdf) {
                            btn = btn + renderDownloadPdfButton(row.urls.pdf);
                        }
                        if (row.urls.details) {
                            btn = btn + renderDetailsButton(row.urls.details);
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

            $dataTable.on('click', 'button.download-pdf-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

            $dataTable.on('click', 'button.details-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

            $dataTable.on('click', 'button.payment-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });
        });

    </script>
@endsection
