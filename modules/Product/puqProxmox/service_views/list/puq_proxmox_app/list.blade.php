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
            <a href="{{ route('client.web.panel.cloud.group.order', $product_group->uuid) }}"
               class="btn-wide mb-2 me-2 btn btn-outline-2x btn-outline-info btn-lg">
                <i class="fa fa-plus me-2"></i>
                {{ __('Product.puqProxmox.Deploy Web Application') }}
            </a>
        </div>
    </div>
</div>

<div class="container px-0">
    <div class="main-card card">
        <div class="card-body">
            <div id="no-servers" class="text-center py-5" style="display: none;">
                <div class="mb-4 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white"
                     style="width:160px; height:160px; font-size:4rem;">
                    <i class="fas fa-server"></i>
                </div>
                <h3 class="mb-3">{{ __("Product.puqProxmox.You don't have any web applications yet") }}</h3>
                <p class="text-muted mb-4">
                    {{ __("Product.puqProxmox.Deploy your first web application – it only takes a few seconds") }}
                </p>
                <a href="{{ route('client.web.panel.cloud.group.order', $product_group->uuid) }}"
                   class="btn btn-outline-primary btn-lg px-4">
                    <i class="fa fa-plus me-2"></i>
                    {{ __('Product.puqProxmox.Deploy Web Application') }}
                </a>
            </div>
            <div id="yes-servers" style="display: none;">
                <table id="services" class="table table-hover table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th></th>
                        <th>{{__('Product.puqProxmox.Service')}}</th>
                        <th>{{__('Product.puqProxmox.Status')}}</th>
                        <th>{{__('Product.puqProxmox.CPU')}}</th>
                        <th>{{__('Product.puqProxmox.RAM')}}</th>
                        <th>{{__('Product.puqProxmox.Disks')}}</th>
                        <th>{{__('Product.puqProxmox.Location')}}</th>
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
            function renderBadges(attributes, type) {
                if (!attributes || !attributes.length) return '';
                let color = 'secondary';
                switch(type) {
                    case 'cpu': color = 'primary'; break;
                    case 'ram': color = 'info'; break;
                    case 'system_disk': color = 'success'; break;
                }
                return attributes.map(a => `<span class="badge bg-${color} px-1 py-0.5 me-1 mb-1" style="font-size:0.65rem;">${a}</span>`).join('');
            }

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
                    data: 'status',
                    orderable: false,
                    render: function (data, type, row) {
                        let html = renderServiceStatus(row.status);

                        if (row.termination_request) {
                            html += `
                <div class="mt-1 small fw-semibold d-flex align-items-center text-danger">
                    <i class="fas fa-exclamation-triangle me-1 fa-2x"></i>
                    <span>${translate('Termination Requested')}</span>
                </div>`;
                        } else if (row.error) {
                            html += `
                <div class="mt-1 small fw-semibold d-flex align-items-center text-danger">
                    <i class="fas fa-times-circle me-1 fa-2x"></i>
                    <span title="${translate(row.error)}">
                        ${translate(row.error)}
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
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        let cpu = row.params.cpu;
                        if (!cpu || cpu === "") return `<span class="text-muted" style="white-space: nowrap;">{{__('Product.puqProxmox.No data')}}</span>`;
                        let badges = renderBadges(row.params.clu_attributes, 'cpu');
                        return `<span class="fw-bold" style="font-size:0.95rem; white-space: nowrap;">${cpu}</span>
                <small class="text-muted" style="white-space: nowrap;">${cpu === 1 ? '{{__('Product.puqProxmox.Core')}}' : '{{__('Product.puqProxmox.Cores')}}'}</small>
                <div class="mt-1" style="white-space: nowrap;">${badges}</div>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        let ram = row.params.ram;
                        if (!ram || ram === "") return `<span class="text-muted" style="white-space: nowrap;">{{__('Product.puqProxmox.No data')}}</span>`;
                        let gb = (ram / 1024).toFixed(0);
                        let badges = renderBadges(row.params.ram_attributes, 'ram');
                        return `<span style="white-space: nowrap;">
                    <span class="fw-bold" style="font-size:0.95rem; white-space: nowrap;">${gb}</span>
                    <small class="text-muted" style="white-space: nowrap;">{{__('Product.puqProxmox.GB')}}</small>
                    <div class="mt-1" style="white-space: nowrap;">${badges}</div>
                </span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        let main = row.params.addition_disk;

                        if (!main || main === "") return `<span class="text-muted" style="white-space: nowrap;">{{__('Product.puqProxmox.No data')}}</span>`;

                        let mainGb = (main / 1024).toFixed(0);
                        let html = `<div style="white-space: nowrap;">
                        <span class="fw-bold" style="white-space: nowrap;">${mainGb}</span>
                        <small class="text-muted" style="white-space: nowrap;">{{__('Product.puqProxmox.GB')}}</small>
                    </div>
                    <div class="mt-1" style="white-space: nowrap;">${renderBadges(row.params.addition_disk_attributes, 'system_disk')}</div>`;

                        return html;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        if (!row.params || !row.params.location || !row.params.location.country) {
                            return `<span class="text-muted">{{__('Product.puqProxmox.No data')}}</span>`;
                        }

                        return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left me-3">
                    <div class="avatar-icon-wrapper">
                        <div class="badge badge-bottom"></div>
                        <div class="avatar-icon-wrapper">
                            <div class="avatar-icon rounded" style="width:42.24px; height:31.68px; overflow:hidden;">
                                <img src="${row.params.location.img_url}"
                                     alt="${row.params.location.country}"
                                     style="width:100%; height:100%;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="widget-content-left">
                    <div class="widget-heading">${row.params.location.country}</div>
                    <div class="widget-subheading">${row.params.location.data_center}</div>
                </div>
            </div>
        </div>`;
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
                        $('#yes-servers').hide();
                        $('#no-servers').show();
                    } else {
                        $('#yes-servers').show();
                        $('#no-servers').hide();
                    }
                },
            });

            function DataTableAddData() {
                return {};
            }

        });
    </script>
@endsection
