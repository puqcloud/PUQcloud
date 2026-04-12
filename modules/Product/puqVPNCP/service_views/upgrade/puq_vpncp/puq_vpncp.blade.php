@php
    $iconUrl = $product->images['icon'] ?? null;
    $backgroundUrl = $product->images['background'] ?? null;
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

<style>
    #products tbody tr {
        cursor: pointer;
    }
    #products tbody tr.selected-row {
        outline: 2px solid #28a745;
        outline-offset: -2px;
        background-color: #f8fff9;
        border-radius: 6px;
    }
    .dir-badge-upgrade { background-color: #28a745; }
    .dir-badge-downgrade { background-color: #ffc107; color: #212529; }
</style>

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
                @else
                    <i class="fas fa-shield-alt icon-gradient bg-ripe-malin"></i>
                @endif
            </div>
            <div>
                {{ __('main.Upgrade / Downgrade') }}: {{ $service->client_label }}
                <div class="page-title-subheading">
                    {{ $product->name }}
                </div>
            </div>
        </div>
        <div class="page-title-actions">
            <a href="{{ route('client.web.panel.cloud.service', ['uuid' => $service->uuid, 'tab' => 'general']) }}"
               class="btn-wide mb-2 me-2 btn btn-outline-2x btn-outline-secondary btn-lg">
                <i class="fa fa-arrow-left me-2"></i>
                {{ __('main.Back to Service') }}
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-0">
    <div class="mx-auto" style="max-width: 1600px;">
        <div class="row">
            <div class="col-md-8 mb-2">
                {{-- Current Plan --}}
                <div class="main-card card mb-2">
                    <div class="card-header">
                        <i class="fas fa-shield-alt me-2 text-primary"></i>
                        {{ __('main.Current Plan') }}
                    </div>
                    <div class="card-body" id="current-plan-info">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> {{ __('main.Loading...') }}
                        </div>
                    </div>
                </div>

                {{-- Available Plans --}}
                <div class="main-card mb-2 card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>{{ __('main.Available Plans') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="no-products" class="alert alert-info align-items-start gap-2 rounded-4 shadow-sm mb-0" role="alert" style="display:none;">
                            <i class="fa fa-info-circle fs-3 text-primary mt-1"></i>
                            <div>
                                <p class="mb-1">{{ __('Product.puqVPNCP.No products available for upgrade/downgrade') }}</p>
                            </div>
                        </div>

                        <div id="productsTable">
                            <table id="products" class="table table-hover table-striped table-bordered" style="width: 100%;">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th><i class="fa fa-server"></i> {{ __('Product.puqVPNCP.Name') }}</th>
                                    <th><i class="fa fa-download"></i> {{ __('Product.puqVPNCP.Download Speed') }}</th>
                                    <th><i class="fa fa-upload"></i> {{ __('Product.puqVPNCP.Upload Speed') }}</th>
                                    <th><i class="fa fa-lock"></i> {{ __('Product.puqVPNCP.Protocols') }}</th>
                                    <th><i class="fa fa-dollar-sign"></i> {{ __('Product.puqVPNCP.Price') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-2">
                <div class="position-sticky" style="top: 2rem;">
                    <div id="orderSummary" class="card" style="min-height: 250px;">
                        <div class="card-body position-relative">
                            <h5 class="card-title">{{ __('main.Change Summary') }}</h5>
                            <div id="orderSummaryContent" class="text-center text-muted py-5">
                                <i class="fas fa-info-circle fa-4x mb-3 text-info"></i><br>
                                {{ __('main.Please select a plan to see the proration') }}
                            </div>
                            <div class="text-center mt-3" id="confirmBtnWrap" style="display:none;">
                                <button type="button" id="confirmChangeBtn" class="btn-wide mb-0 me-0 btn btn-outline-2x btn-outline-primary btn-lg">
                                    <i class="fas fa-check me-2"></i>{{ __('main.Confirm') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            var $dataTable = null;
            var selectedProductUuid = '';
            var selectedPriceUuid = '';
            var selectedDirection = '';

            function formatBw(v) {
                return v > 0
                    ? `<span class="fw-bold">${v}</span> <small class="text-muted">Mbit/s</small>`
                    : `<span class="text-muted">{{ __('Product.puqVPNCP.Unlimited') }}</span>`;
            }

            function loadCurrentPlan() {
                PUQajax(
                    '{{ route('client.api.cloud.service.upgrade.get', ['uuid' => $service->uuid, 'method' => 'GetCurrent']) }}',
                    null, 0, null, 'GET', null
                ).then(response => {
                    if (!response || !response.data) return;
                    const c = response.data;
                    const protocolsHtml = (c.protocols || []).map(p => `<span class="badge bg-primary me-1">${p}</span>`).join('');
                    const billingBadge = `<span class="badge bg-${c.billing_mode === 'invoice' ? 'info' : 'success'}">${translate(c.billing_mode)}</span>`;
                    const nextDue = c.billing_timestamp ? String(c.billing_timestamp).replace('T', ' ').substring(0, 19) : '-';

                    $('#current-plan-info').html(`
                        <div class="row align-items-center g-2">
                            <div class="col-md-4">
                                <div class="fw-semibold">${c.product_name}</div>
                                <div class="text-muted small">${c.base_str} / ${translate(c.period)}</div>
                            </div>
                            <div class="col-md-2">
                                <div class="small text-muted"><i class="fa fa-download text-success"></i> ${translate('Download')}</div>
                                ${formatBw(c.b_download)}
                            </div>
                            <div class="col-md-2">
                                <div class="small text-muted"><i class="fa fa-upload text-info"></i> ${translate('Upload')}</div>
                                ${formatBw(c.b_upload)}
                            </div>
                            <div class="col-md-2">${protocolsHtml}</div>
                            <div class="col-md-2 text-end">
                                ${billingBadge}
                                <div class="small text-muted mt-1">${translate('Next Due')}: ${nextDue}</div>
                            </div>
                        </div>
                    `);
                });
            }

            function loadProducts() {
                var tableId = '#products';
                var ajaxUrl = '{{ route('client.api.cloud.service.upgrade.get', ['uuid' => $service->uuid, 'method' => 'GetProducts']) }}';

                var columnsConfig = [
                    {
                        data: 'uuid',
                        orderable: false,
                        render: function (data, type, row) {
                            return `<div class="d-flex justify-content-center">
                                <input class="form-check-input select-product" type="radio" name="productSelect"
                                    value="${row.uuid}" data-product_price_uuid="${row.product_price_uuid}" data-direction="${row.direction}" style="transform: scale(1.3);">
                            </div>`;
                        }
                    },
                    {
                        data: 'name',
                        orderable: false,
                        render: function (data, type, row) {
                            const dirClass = row.direction === 'upgrade' ? 'dir-badge-upgrade' : 'dir-badge-downgrade';
                            const dirIcon = row.direction === 'upgrade' ? 'fa-arrow-up' : 'fa-arrow-down';
                            return `<span class="fw-semibold" style="font-size:1.01rem;">${data}</span>
                                <span class="badge ${dirClass} ms-2"><i class="fas ${dirIcon} me-1"></i>${translate(row.direction)}</span>`;
                        }
                    },
                    {
                        data: 'b_download',
                        orderable: false,
                        render: function (data) { return formatBw(data); }
                    },
                    {
                        data: 'b_upload',
                        orderable: false,
                        render: function (data) { return formatBw(data); }
                    },
                    {
                        data: 'protocols',
                        orderable: false,
                        render: function (data) {
                            return (data || []).map(p => `<span class="badge bg-primary me-1">${p}</span>`).join('');
                        }
                    },
                    {
                        data: 'price',
                        orderable: false,
                        render: function (data, type, row) {
                            return `<div class="d-flex justify-content-between align-items-center flex-nowrap text-nowrap">
                                <div class="text-nowrap">
                                    <span class="fw-bold text-success text-nowrap" style="font-size:1.5rem; white-space:nowrap;">${row.price_string}</span>
                                    <small class="text-muted text-nowrap" style="font-size:1rem; white-space:nowrap;">/ {{ __('Product.puqVPNCP.mo') }}</small>
                                </div>
                                <i class="fa fa-check text-white d-none check-icon ms-2"
                                   style="background-color:#28a745; border-radius:50%; padding:8px; font-size:1.4rem; box-shadow:0 2px 6px rgba(0,0,0,0.3);">
                                </i>
                            </div>`;
                        }
                    },
                ];

                $dataTable = initializeDataTable(
                    tableId,
                    ajaxUrl,
                    columnsConfig,
                    () => ({}),
                    {
                        serverSide: false,
                        paging: false,
                        searching: false,
                    }
                );

                $('#products tbody').on('click', 'tr', function (e) {
                    if ($(e.target).is('button, a')) return;
                    selectRow($(this));
                });

                $('#products tbody').on('click', 'input[type="radio"].select-product', function (e) {
                    e.stopPropagation();
                    selectRow($(this).closest('tr'));
                });

                $dataTable.on('draw', function () {
                    var rowCount = $('#products tbody tr').filter(function () {
                        return $(this).find('.select-product').length > 0;
                    }).length;

                    if (rowCount === 0) {
                        $('#productsTable').hide();
                        $('#no-products').show();
                        resetSummary();
                    } else {
                        $('#no-products').hide();
                        $('#productsTable').show();
                    }
                });
            }

            function selectRow($row) {
                const $radio = $row.find('.select-product');
                if (!$radio.length) return;

                $radio.prop('checked', true);
                selectedProductUuid = $radio.val();
                selectedPriceUuid = $radio.data('product_price_uuid');
                selectedDirection = $radio.data('direction');

                $('#products tbody tr').removeClass('selected-row');
                $row.addClass('selected-row');
                $('.check-icon').addClass('d-none');
                $row.find('.check-icon').removeClass('d-none');

                calculateProration();
            }

            function resetSummary() {
                $('#orderSummaryContent').html(`
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-info-circle fa-4x mb-3 text-info"></i><br>
                        ${translate('Please select a plan to see the proration')}
                    </div>
                `);
                $('#confirmBtnWrap').hide();
            }

            function calculateProration() {
                blockUI('orderSummary');
                PUQajax(
                    '{{ route('client.api.cloud.service.upgrade.get', ['uuid' => $service->uuid, 'method' => 'CalculateProration']) }}'
                    + '?product_uuid=' + selectedProductUuid + '&price_uuid=' + selectedPriceUuid,
                    null, 0, null, 'GET', null
                ).then(response => {
                    unblockUI('orderSummary');
                    if (response && response.data) {
                        renderProration(response.data);
                    }
                }).catch(() => unblockUI('orderSummary'));
            }

            function renderProration(data) {
                const isUpgrade = data.direction === 'upgrade';
                const dirClass = isUpgrade ? 'success' : 'warning';
                const dirLabel = isUpgrade ? translate('Upgrade') : translate('Downgrade');

                let html = `
                    <div class="mb-2"><span class="badge bg-${dirClass}">${dirLabel}</span></div>
                    <div class="d-flex justify-content-between"><span>${translate('Current Price')}</span><span class="text-nowrap">${data.old_price_str}</span></div>
                    <div class="d-flex justify-content-between"><span>${translate('New Price')}</span><span class="text-nowrap fw-bold">${data.new_price_str}</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>${translate('Remaining Credit')}</span><span class="text-nowrap">${data.credit_str}</span></div>
                `;

                if (data.full_periods_covered > 0) {
                    html += `<div class="d-flex justify-content-between text-muted"><span>${translate('Full periods covered by credit')}</span><span><strong>${data.full_periods_covered}</strong></span></div>`;
                }
                if (data.switch_fee > 0) {
                    html += `<div class="d-flex justify-content-between text-muted"><span>${translate('Switch Fee')}</span><span>${data.switch_fee}</span></div>`;
                }

                const hasTaxes = data.taxes && data.taxes.length > 0;
                html += `<hr>`;

                if (hasTaxes) {
                    html += `<div class="d-flex justify-content-between"><span>${translate('Subtotal')}</span><span class="text-nowrap">${data.total_to_pay_str}</span></div>`;
                    data.taxes.forEach(tax => {
                        html += `<div class="d-flex justify-content-between text-muted"><span>${tax.name} (${tax.rate}%)</span><span class="text-nowrap">${tax.amount_str}</span></div>`;
                    });
                    html += `
                        <div class="mt-3 text-center">
                            <div class="fw-bold fs-1 text-${dirClass}">${data.gross_total_str}</div>
                            <div class="text-muted">${translate('Total with taxes')}</div>
                        </div>`;
                } else {
                    html += `
                        <div class="mt-3 text-center">
                            <div class="fw-bold fs-1 text-${dirClass}">${data.total_to_pay_str}</div>
                            <div class="text-muted">${translate('Total')}</div>
                        </div>`;
                }

                if (data.new_billing_timestamp) {
                    html += `<div class="small text-muted text-center mt-2">${translate('New Billing Date')}: ${String(data.new_billing_timestamp).replace('T', ' ').substring(0, 19)}</div>`;
                }

                $('#orderSummaryContent').html(html);
                $('#confirmBtnWrap').show();
            }

            $(document).on('click', '#confirmChangeBtn', function () {
                const $btn = $(this);
                openConfirmActionModal({
                    actionUrl: '{{ route('client.api.cloud.service.upgrade.post', ['uuid' => $service->uuid, 'method' => 'ExecuteChange']) }}',
                    actionMethod: 'POST',
                    hiddenInputs: {
                        product_uuid: selectedProductUuid,
                        price_uuid: selectedPriceUuid
                    },
                    actionText: translate('Are you sure you want to change your plan?'),
                    actionType: 'info',
                    confirmButtonText: '<i class="fas fa-check"></i> ' + translate('Confirm'),
                    titleText: translate('Confirm Change'),
                    onSuccess: function (res) {
                        if (res && res.data && res.data.redirect) {
                            window.location.href = res.data.redirect;
                        } else {
                            window.location.href = '{{ route('client.web.panel.cloud.service', ['uuid' => $service->uuid, 'tab' => 'general']) }}';
                        }
                    },
                    button: $btn
                });
            });

            // Init
            loadCurrentPlan();
            loadProducts();
        });
    </script>
@endsection