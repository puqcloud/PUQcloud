@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('clients-edit'))
        <a href="{{route('admin.web.service.create',['client_uuid'=>$uuid])}}" type="button"
           class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
            <i class="fa fa-plus"></i>
            {{__('main.Add Service')}}
        </a>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="services" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Product')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Order Date')}}</th>
                    <th>{{__('main.Activation Date')}}</th>
                    <th>{{__('main.Price')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Product')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Order Date')}}</th>
                    <th>{{__('main.Activation Date')}}</th>
                    <th>{{__('main.Price')}}</th>
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
            var $tableId = $('#services');
            var ajaxUrl = '{{ route('admin.api.client.services.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: 'product_key',
                    render: function (data, type, row) {
                        var html = '';
                        html += '<div class="widget-title opacity-10 text-uppercase">' + row.product_key + '</div>';
                        html += '<div class="mb-1 me-1 badge bg-info">' + row.admin_label + '</div>';

                        var uniqueId = 'countdown-' + row.uuid;
                        html += '<div class="mt-1 text-danger small fw-bold" id="' + uniqueId + '"></div>';

                        return html;
                    },
                    createdCell: function (td, cellData, rowData, row, col) {
                        let seconds = null;
                        let label = '';

                        if (rowData.termination_request) {
                            seconds = rowData.termination_time.seconds_left+1;
                            label = translate('Terminates in');
                        } else if (rowData.status === 'pending' && rowData.cancellation_time?.seconds_left) {
                            seconds = rowData.cancellation_time.seconds_left;
                            label = translate('Cancels in');
                        } else if (rowData.status === 'suspended' && rowData.termination_time?.seconds_left) {
                            seconds = rowData.termination_time.seconds_left;
                            label = translate('Terminates in');
                        }

                        if (seconds !== null) {
                            const el = td.querySelector('#countdown-' + rowData.uuid);
                            if (el) {
                                startCountdown(el, seconds, label);
                            }
                        }
                    }
                },
                {
                    data: 'status',
                    render: function (data, type, row) {
                        let html = `<div>${renderServiceStatus(row.status)}</div>`;

                        if (row.termination_request) {
                            html += `
                <div class="mt-1 small fw-semibold d-flex align-items-center text-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <span>${translate('Termination Requested')}</span>
                </div>`;
                        }

                        if (row.create_error) {
                            html += `
                <div class="mt-1 small fw-semibold d-flex align-items-center text-danger">
                    <i class="fas fa-times-circle me-1"></i>
                    <span title="${translate(row.create_error)}">
                        ${translate(row.create_error)}
                    </span>
                </div>`;
                        }

                        if (row.status === 'suspended' && row.suspended_reason) {
                            html += `
                <div class="mt-1 small fw-semibold d-flex align-items-center">
                    <i class="fas fa-pause-circle me-1 text-warning"></i>
                    <span title="${translate(row.suspended_reason)}">
                        ${translate(row.suspended_reason)}
                    </span>
                </div>`;
                        }

                        html += `<div class="mt-1">${renderServiceStatus(row.provision_status)}</div>`;
                        return html;
                    }
                },
                {data: 'order_date', name: 'order_date'},
                {
                    data: 'activated_date',
                    render: function (data, type, row) {
                        if (data) {
                            return data;
                        }
                        return '';
                    }
                },
                {
                    data: 'price',
                    render: function (data, type, row) {
                        if (!data || !data.amount) return '';

                        const code = data.code || '';
                        const amount = data.amount;
                        const period = data.period ? data.period : '';

                        let hourly_billing = '';
                        if (row.product.hourly_billing) {
                            hourly_billing = `
                <span class="ms-2" title="${translate('Hourly billing')}">
                    <i class="fas fa-clock text-success"></i>
                </span>`;
                        }

                        return `
            <div class="widget-chart-content">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex">
                            <div class="fsize-1">
                                <span>${amount}</span>
                                <small class="opacity-5">${code}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <h6 class="widget-subheading mb-0 opacity-5">${translate(period)} ${hourly_billing}</h6>
            </div>
        `;
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

            $tableId.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

        });
    </script>
@endsection

