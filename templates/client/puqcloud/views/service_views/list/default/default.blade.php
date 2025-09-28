@php
    $iconUrl = $product_group->images['icon'] ?? null;
    $backgroundUrl = $product_group->images['background'] ?? null;

    $groupIcon = $product_group->icon ?? null;
    $isFlag = $groupIcon && strpos($groupIcon, 'flag') === 0;
@endphp

@if($backgroundUrl)
    @section('background')
        <style>
            .puq-background-blur {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5)),
                url('{{ $backgroundUrl }}') no-repeat center center fixed;
                background-size: cover;
                filter: blur(6px);
                z-index: 0;
            }
        </style>
    @endsection
@endif

<div id="header" class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon" style="{{ $iconUrl ? 'width: auto; padding: 0;' : '' }}">
                @if ($iconUrl)
                    <div class="p-1" style="display: flex; align-items: center; justify-content: center;">
                        <img src="{{ $iconUrl }}" alt="icon" style="max-height: 50px;">
                    </div>
                @elseif ($groupIcon)
                    @if ($isFlag)
                        <i class="{{ $groupIcon }} large"></i>
                    @else
                        <i class="{{ $groupIcon }} icon-gradient bg-ripe-malin"></i>
                    @endif
                @elseif (!$groupIcon)
                    <i class="fas fa-cloud icon-gradient bg-ripe-malin"></i>
                @endif
            </div>
            <div>
                {{$product_group->name}}
                <div class="page-title-subheading">
                    {{$product_group->short_description}}
                </div>
            </div>
        </div>
        <div class="page-title-actions">
            <a href="{{route('client.web.panel.cloud.group.order',$product_group->uuid)}}"
               class="btn-wide mb-2 me-2 btn btn-outline-2x btn-outline-primary btn-lg">
                <i class="fa fa-plus me-2"></i>
                {{ __('main.Deploy Service') }}
            </a>
        </div>
    </div>
</div>

<div class="container px-0">
    <div class="main-card card">
        <div class="card-body">
            <table id="services" class="table table-hover table-striped table-bordered" style="width: 100%;">
                <thead></thead>
                <tbody></tbody>
                <tfoot></tfoot>
            </table>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            blockUI('mainInner');
            var $table = $('#services');
            var ajaxUrl = '{{ route('client.api.cloud.group.list.get',['uuid'=>$product_group->uuid,'method'=>'ServiceList']) }}';
            var columnsConfig = [
                {
                    data: "icon",
                    orderable: true,
                    title: '',
                    render: function (data, type, row) {
                        if (row.icon) {
                            return `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                    <img src="${row.icon}" alt="icon" style="max-height: 32px;">
                </div>
            `;
                        }
                        return '';
                    }
                },
                {
                    data: 'client_label',
                    orderable: true,
                    title: translate('Service'),
                    render: function (data, type, row) {
                        var html = '';
                        html += '<div class="widget-title opacity-10">' + row.product_name + '</div>';
                        html += '<div class="mb-1 me-1 badge bg-dark">' + row.client_label + '</div>';
                        var uniqueId = 'countdown-' + row.uuid;
                        html += '<div class="mt-1 text-danger small fw-bold" id="' + uniqueId + '"></div>';

                        return html;
                    },
                    createdCell: function (td, cellData, rowData, row, col) {
                        let seconds = null;
                        let label = '';

                        if (rowData.termination_request) {
                            seconds = rowData.termination_time.seconds_left + 1;
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
                    title: translate('Status'),
                    orderable: true,
                    render: function (data, type, row) {
                        let html = renderServiceStatus(row.status);

                        if (row.termination_request) {
                            html += `
                <div class="mt-1 small fw-semibold d-flex align-items-center text-danger">
                    <i class="fas fa-exclamation-triangle me-1 fa-2x"></i>
                    <span>${translate('Termination Requested')}</span>
                </div>`;
                        } else if (row.create_error) {
                            html += `
                <div class="mt-1 small fw-semibold d-flex align-items-center text-danger">
                    <i class="fas fa-times-circle me-1 fa-2x"></i>
                    <span title="${translate(row.create_error)}">
                        ${translate(row.create_error)}
                    </span>
                </div>`;
                        } else if (row.status === 'suspended' && row.suspended_reason) {
                            html += `
                <div class="mt-1 small fw-semibold d-flex align-items-center">
                    <i class="fas fa-pause-circle me-1 fa-2x text-warning"></i>
                    <span title="${translate(row.suspended_reason)}">
                        ${translate(row.suspended_reason)}
                    </span>
                </div>`;
                        }

                        return html;
                    }
                },
                {
                    data: 'price',
                    title: translate('Price'),
                    render: function (data, type, row) {
                        if (!data || !data.amount) return '';

                        const amount_str = data.amount_str;
                        const period = data.period ? data.period : '';

                        let hourly_billing = '';
                        if (data.hourly_billing && period === 'monthly') {
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
                        <div class="fsize-1 text-nowrap">
                            <span>${amount_str}</span>
                        </div>
                    </div>
                </div>
            </div>
            <h6 class="widget-subheading mb-0 opacity-5 text-nowrap">${translate(period)} ${hourly_billing}</h6>
        </div>
    `;
                    }
                },
                {
                    data: 'urls',
                    title: '',
                    className: "center",
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.manage) {
                            btn = btn + renderManageButton(row.urls.manage);
                        }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTableDC($table, ajaxUrl, columnsConfig, DataTableAddData, {
                lengthMenu: [20, 50, 100, 200, 500, 1000],
                pageLength: 20,
                initComplete: function () {
                    unblockUI('mainInner');
                }
            });

            function DataTableAddData() {
                return {};
            }

            $table.on('click', 'button.manage-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

        });
    </script>
@endsection
