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
                    <i class="fas fa-shield-alt icon-gradient bg-ripe-malin"></i>
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
            <a href="{{ route('client.web.panel.cloud.group.order', $product_group->uuid) }}"
               class="btn-wide mb-2 me-2 btn btn-outline-2x btn-outline-info btn-lg">
                <i class="fa fa-plus me-2"></i>
                {{ __('main.Deploy Service') }}
            </a>
        </div>
    </div>
</div>

<div class="container px-0">
    <div class="main-card card">
        <div class="card-body">
            <div id="no-services" class="text-center py-5" style="display: none;">
                <div class="mb-4 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white"
                     style="width:160px; height:160px; font-size:4rem;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="mb-3">{{ __("Product.puqVPNCP.You don't have any VPN services yet") }}</h3>
                <p class="text-muted mb-4">
                    {{ __("Product.puqVPNCP.Deploy your first VPN service now") }}
                </p>
                <a href="{{ route('client.web.panel.cloud.group.order', $product_group->uuid) }}"
                   class="btn btn-outline-primary btn-lg px-4">
                    <i class="fa fa-plus me-2"></i>
                    {{ __('main.Deploy Service') }}
                </a>
            </div>
            <div id="yes-services" style="display: none;">
                <table id="services" class="table table-hover table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th>{{ __('Product.puqVPNCP.Service') }}</th>
                        <th>{{ __('Product.puqVPNCP.Bandwidth') }}</th>
                        <th>{{ __('Product.puqVPNCP.Status') }}</th>
                        <th>{{ __('Product.puqVPNCP.Location') }}</th>
                        <th>{{ __('Product.puqVPNCP.Price') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
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
                    data: 'client_label',
                    orderable: false,
                    render: function (data, type, row) {
                        var html = '';
                        html += '<div class="widget-title opacity-10">' + row.product_name + '</div>';
                        html += '<div class="mb-1 me-1 badge bg-dark">' + row.client_label + '</div>';
                        var uniqueId = 'countdown-' + row.uuid;
                        html += '<div class="mt-1 text-danger small fw-bold" id="' + uniqueId + '"></div>';
                        return html;
                    },
                    createdCell: function (td, cellData, rowData) {
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
                            if (el) startCountdown(el, seconds, label);
                        }
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        let dl = row.b_download || '0';
                        let ul = row.b_upload || '0';
                        let dlText = dl == '0' ? '{{ __('Product.puqVPNCP.Unlimited') }}' : dl + ' Mbit/s';
                        let ulText = ul == '0' ? '{{ __('Product.puqVPNCP.Unlimited') }}' : ul + ' Mbit/s';

                        return `<div class="small fw-semibold">
                            <div><i class="fas fa-arrow-down text-primary me-1"></i> <strong>{{ __('Product.puqVPNCP.Download') }}:</strong> ${dlText}</div>
                            <div><i class="fas fa-arrow-up text-success me-1"></i> <strong>{{ __('Product.puqVPNCP.Upload') }}:</strong> ${ulText}</div>
                        </div>`;
                    }
                },
                {
                    data: 'status',
                    orderable: false,
                    render: function (data, type, row) {
                        let html = renderServiceStatus(row.status);

                        if (row.termination_request) {
                            html += `<div class="mt-1 small fw-semibold d-flex align-items-center text-danger">
                                <i class="fas fa-exclamation-triangle me-1 fa-2x"></i>
                                <span>${translate('Termination Requested')}</span>
                            </div>`;
                        } else if (row.error) {
                            html += `<div class="mt-1 small fw-semibold d-flex align-items-center text-danger">
                                <i class="fas fa-times-circle me-1 fa-2x"></i>
                                <span title="${translate(row.error)}">${translate(row.error)}</span>
                            </div>`;
                        } else if (row.status === 'suspended' && row.suspended_reason) {
                            html += `<div class="mt-1 small fw-semibold d-flex align-items-center">
                                <i class="fas fa-pause-circle me-1 fa-2x text-warning"></i>
                                <span title="${translate(row.suspended_reason)}">${translate(row.suspended_reason)}</span>
                            </div>`;
                        }
                        return html;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        if (!row.location || !row.location.country) {
                            return `<span class="text-muted">-</span>`;
                        }

                        return `<div class="widget-content p-0">
                            <div class="widget-content-wrapper">
                                <div class="widget-content-left me-3">
                                    <div class="avatar-icon-wrapper">
                                        <div class="badge badge-bottom"></div>
                                        <div class="avatar-icon-wrapper">
                                            <div class="avatar-icon rounded" style="width:42.24px; height:31.68px; overflow:hidden;">
                                                ${row.location.img_url
                                                    ? `<img src="${row.location.img_url}" alt="${row.location.country}" style="width:100%; height:100%;">`
                                                    : `<i class="fas fa-map-marker-alt fa-2x text-primary d-flex align-items-center justify-content-center h-100"></i>`
                                                }
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="widget-content-left">
                                    <div class="widget-heading">${row.location.country}</div>
                                    <div class="widget-subheading">${row.location.data_center}</div>
                                </div>
                            </div>
                        </div>`;
                    }
                },
                {
                    data: 'price',
                    orderable: false,
                    render: function (data, type, row) {
                        let html = `<div class="fw-bold">${row.price}</div>`;

                        if (row.billing_mode === 'invoice') {
                            // Invoice mode: show "Paid until" date from billing_timestamp.
                            const paidUntil = row.billing_timestamp || '-';
                            html += `<div class="mt-1 small">
                                <span class="badge bg-info text-dark">
                                    <i class="fas fa-file-invoice-dollar me-1"></i>{{ __('Product.puqVPNCP.Invoice') }}
                                </span>
                                <div class="text-muted mt-1">
                                    <i class="fas fa-calendar-check me-1"></i>{{ __('Product.puqVPNCP.Paid until') }}: <span class="fw-semibold">${paidUntil}</span>
                                </div>
                            </div>`;
                        } else if (row.billing_mode === 'prepaid') {
                            // Prepaid mode: show prepaid badge and whether hourly billing is enabled.
                            const hourlyOn = row.hourly_billing === true || row.hourly_billing === 1 || row.hourly_billing === '1';
                            const hourlyBadge = hourlyOn
                                ? `<span class="badge bg-success ms-1"><i class="fas fa-clock me-1"></i>{{ __('Product.puqVPNCP.Hourly billing') }}</span>`
                                : `<span class="badge bg-secondary ms-1"><i class="fas fa-clock me-1"></i>{{ __('Product.puqVPNCP.Hourly billing off') }}</span>`;
                            html += `<div class="mt-1 small">
                                <span class="badge bg-primary">
                                    <i class="fas fa-wallet me-1"></i>{{ __('Product.puqVPNCP.Prepaid') }}
                                </span>
                                ${hourlyBadge}
                            </div>`;
                        }

                        return html;
                    }
                },
                {
                    data: 'urls',
                    title: '',
                    orderable: false,
                    className: "center",
                    render: function (data, type, row) {
                        return row.urls.manage ? renderManageLink(row.urls.manage) : '';
                    }
                }
            ];

            var $dataTable = initializeDataTable($table, ajaxUrl, columnsConfig, DataTableAddData, {
                lengthMenu: [20, 50, 100, 200, 500, 1000],
                pageLength: 20,
                initComplete: function () {
                    unblockUI('mainInner');
                    if ($dataTable.rows().count() === 0) {
                        $('#yes-services').hide();
                        $('#no-services').show();
                    } else {
                        $('#yes-services').show();
                        $('#no-services').hide();
                    }
                },
            });

            function DataTableAddData() {
                return {};
            }

        });
    </script>
@endsection
