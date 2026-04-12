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

<style>
    .location-widget {
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .location-widget:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    tr.selected-row {
        border: 2px solid #28a745 !important;
        border-radius: 8px;
        background-color: #f8fff9;
    }

    #products tbody tr.selected-row {
        outline: 2px solid #28a745;
        outline-offset: -2px;
        background-color: #f8fff9;
        border-radius: 6px;
    }
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
                @elseif (!$groupIcon)
                    <i class="fas fa-shield-alt icon-gradient bg-ripe-malin"></i>
                @endif
            </div>
            <div>
                {{$title}}
                <div class="page-title-subheading">
                    {{$product_group->short_description}}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-0">
    <div class="mx-auto" style="max-width: 1600px;">
        <div class="row">
            <div class="col-md-8 mb-2">
                @if(!empty($product_group->description))
                    <div class="card shadow-sm border-0 mb-2">
                        <div class="card-body">
                            <p class="card-text text-muted">
                                {{ $product_group->description }}
                            </p>
                        </div>
                    </div>
                @endif

                <div class="card mb-2" id="locations" style="min-height: 150px;">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>{{ __('main.Location') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                        </div>
                    </div>
                </div>

                <div class="main-card mb-2 card">
                    <div class="card-body">
                        <div class="alert alert-info align-items-start gap-2 rounded-4 shadow-sm mb-0" role="alert"
                             id="no-products">
                            <i class="fa fa-info-circle fs-3 text-primary mt-1"></i>
                            <div>
                                <p class="mb-1">{{ __('main.Please select a location to see available products') }}</p>
                            </div>
                        </div>

                        {{-- Billing cycle chooser — applies to the whole product list + options.
                             Rendered as a pill-button group so the user sees the cycles at a glance. --}}
                        <div id="billing_period_topbar" style="display:none;" class="mb-3">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="fw-bold text-muted small text-uppercase me-2">
                                    <i class="fa fa-calendar-alt text-primary me-1"></i>{{__('main.Billing Cycle')}}:
                                </span>
                                <div class="btn-group flex-wrap" role="group" id="billing_period_buttons"></div>
                            </div>
                            <div class="small text-muted mt-1">
                                <i class="fa fa-info-circle me-1"></i>{{__('main.Prices and options update based on the selected cycle')}}
                            </div>
                        </div>

                        <div id="productsTable" style="display: none;">
                            <table id="products" class="table table-hover table-striped table-bordered"
                                   style="width: 100%;">
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

                <div id="optionsCard" class="card mb-2" style="display: none;">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>{{ __('main.Options') }}</h5>
                    </div>
                    <div class="card-body">
                        <form id="orderForm" novalidate="novalidate">
                            <input type="hidden" name="selectedLocation" id="selectedLocation" value="">
                            <input type="hidden" name="selected_product_uuid" id="selected_product_uuid" value="">
                            <input type="hidden" name="selected_product_price_uuid" id="selected_product_price_uuid"
                                   value="">
                            <input type="hidden" name="location_product_option_uuid" id="location_product_option_uuid"
                                   value="">
                            <input type="hidden" name="billing_mode" id="billing_mode_hidden" value="">
                            <div id="product_data"></div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-2">
                <div class="position-sticky" style="top: 2rem;">
                    <div id="orderSummary" class="card" style="min-height: 250px;">
                        <div class="card-body position-relative">
                            <h5 class="card-title">{{ __('main.Summary') }}</h5>
                            <div id="orderSummaryContent" class="text-center text-muted py-5">
                            </div>
                            <hr>
                            <div id="billing_mode_selector" style="display:none;" class="mb-3">
                                <label class="form-label fw-bold">{{__('main.Billing Mode')}}</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="billing_mode" id="billing_mode_invoice" value="invoice" checked>
                                    <label class="form-check-label" for="billing_mode_invoice">{{__('main.Invoice')}}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="billing_mode" id="billing_mode_prepaid" value="prepaid">
                                    <label class="form-check-label" for="billing_mode_prepaid">{{__('main.Prepaid')}}</label>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button type="submit" id="btnCompleteProductConfig"
                                        class="btn-wide mb-0 me-0 btn btn-outline-2x btn-outline-primary btn-lg"
                                        disabled>
                                    <i class="fas fa-shield-alt me-2"></i> {{ __('main.Deploy') }}
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

            var $dataTable = '';
            var selectedLocation = '';
            var product_uuid = '';
            var product_price_uuid = '';
            // Global billing cycle selected at the top of the product list.
            // Defaults to monthly; gets clamped to whatever periods are actually available
            // in the returned product data after the DataTable is populated.
            var currentPeriod = 'monthly';

            const $optionsContainer = $("#product_data");
            const $summaryContent = $("#orderSummaryContent");

            clearSummary();

            function clearSummary() {
                $summaryContent.html(`
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-info-circle fa-4x mb-3 text-info"></i><br>
                        ${translate('Please select a product and configuration options')}<br>
                        ${translate('The total cost will appear here')}
                    </div>
                `);
                $('#btnCompleteProductConfig').prop('disabled', true);
            }

            function loadLocations() {
                blockUI('locations');

                PUQajax(
                    `{{ route('client.api.cloud.group.order.get',['uuid'=>$product_group->uuid,'method'=>'GetLocations']) }}`,
                    {},
                    50,
                    null,
                    'GET'
                ).then(response => {
                    const $container = $('#locations .row.g-3');
                    $container.empty();

                    if (response.data.length > 0) {
                        response.data.forEach(location => {
                            // Each widget carries its full per-period price map so the
                            // frontend can re-render prices whenever `currentPeriod` changes,
                            // without re-fetching locations from the server.
                            const card = `
<div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-4">
    <div class="location-widget border rounded p-1 mb-1 position-relative" data-value="${location.value}" style="cursor:pointer;">
        <div class="widget-content p-0 d-flex align-items-center">
            <div class="widget-content-left me-1">
                <div class="avatar-icon-wrapper">
                    <div class="avatar-icon rounded" style="width:64px; height:48px; overflow:hidden;">
                        ${location.icon
                            ? `<img src="${location.icon}" alt="${location.name}" style="width:100%; height:100%; object-fit:cover;">`
                            : `<i class="fas fa-map-marker-alt fa-2x text-primary d-flex align-items-center justify-content-center h-100"></i>`
                        }
                    </div>
                </div>
            </div>

            <div class="widget-content-left flex-grow-1">
                <div class="widget-heading d-flex align-items-center flex-wrap">
                    ${location.name}
                </div>
                <div class="widget-subheading">${location.value}</div>
            </div>

            <div class="ms-3 small text-muted location-price-block"></div>
        </div>

        <i class="fa fa-check text-white position-absolute d-none"
           style="top:0; right:0; transform: translate(50%, -50%);
                  background-color: #28a745;
                  border-radius: 50%;
                  padding: 6px;
                  font-size: 1.2rem;
                  box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
        </i>
    </div>
</div>
                            `;
                            const $card = $(card);
                            // Stash the full per-period price map on the widget so
                            // renderLocationPrices() can find it later.
                            $card.find('.location-widget').data('pricesByPeriod', location.prices_by_period || {});
                            $container.append($card);
                        });

                        // Initial render of prices for the currently selected billing cycle.
                        renderLocationPrices();

                        // Click bind
                        $('.location-widget').on('click', function () {
                            $('.location-widget').removeClass('border-3 border-success fw-bold');
                            $('.location-widget .fa-check').addClass('d-none');

                            $(this).addClass('border-3 border-success fw-bold');
                            $(this).find('.fa-check').removeClass('d-none');

                            selectedLocation = $(this).data('value');
                            $('#selectedLocation').val(selectedLocation);

                            // Reset product selection
                            product_uuid = '';
                            product_price_uuid = '';
                            $('#selected_product_uuid').val('');
                            $('#selected_product_price_uuid').val('');
                            $('#location_product_option_uuid').val('');

                            updateValues();
                        });

                        // Auto-select first
                        const $first = $('.location-widget').first();
                        if ($first.length) {
                            $first.trigger('click');
                        }
                    }

                    unblockUI('locations');
                }).catch(error => {
                    console.error(error);
                    unblockUI('locations');
                });
            }

            function loadProducts() {
                var tableId = '#products';
                var ajaxUrl = `{{ route('client.api.cloud.group.order.get',['uuid'=>$product_group->uuid,'method'=>'GetProducts']) }}`;

                var columnsConfig = [
                    {
                        data: "uuid",
                        orderable: false,
                        render: function (data, type, row) {
                            // Resolve the current-period price for this row so the radio button's
                            // data-product_price_uuid reflects what the user actually sees.
                            var bp = pickPeriod(row.billing_periods);
                            return `<div class="d-flex justify-content-center">
                                <input class="form-check-input select-product" type="radio" name="productSelect"
                                value="${row.uuid}" data-product_price_uuid="${bp ? bp.price_uuid : ''}" data-billing_mode="${row.billing_mode}" style="transform: scale(1.3);">
                            </div>`;
                        }
                    },
                    {
                        data: 'name',
                        orderable: false,
                        render: function (data, type, row) {
                            return `<span class="fw-semibold" style="font-size:1.01rem;">${data}</span>`;
                        }
                    },
                    {
                        data: 'b_download',
                        orderable: false,
                        render: function (data, type, row) {
                            return data > 0 ? `<span class="fw-bold">${data}</span> <small class="text-muted">Mbit/s</small>` : `<span class="text-muted">{{ __('Product.puqVPNCP.Unlimited') }}</span>`;
                        }
                    },
                    {
                        data: 'b_upload',
                        orderable: false,
                        render: function (data, type, row) {
                            return data > 0 ? `<span class="fw-bold">${data}</span> <small class="text-muted">Mbit/s</small>` : `<span class="text-muted">{{ __('Product.puqVPNCP.Unlimited') }}</span>`;
                        }
                    },
                    {
                        data: 'protocols',
                        orderable: false,
                        render: function (data, type, row) {
                            return data.map(p => `<span class="badge bg-primary me-1">${p}</span>`).join('');
                        }
                    },
                    {
                        data: 'price',
                        orderable: false,
                        render: function (data, type, row) {
                            // Price column reflects the currently selected billing cycle.
                            // When the user changes the cycle at the top, the DataTable is
                            // redrawn and this render function runs again with the new period.
                            var bp = pickPeriod(row.billing_periods);
                            var priceStr = bp ? bp.base_str : row.price_string;
                            var periodLabel = bp ? bp.label : translate('{{ __('Product.puqVPNCP.mo') }}');
                            // Setup fee is shown as small hint line under the main price — only
                            // when non-zero so free-setup products stay visually clean.
                            var setupLine = '';
                            if (bp && bp.setup > 0) {
                                setupLine = `<div class="text-muted text-nowrap" style="font-size:0.75rem; line-height:1; margin-top:2px;">
                                    + ${translate('Setup')}: <span class="fw-semibold">${bp.setup_str}</span>
                                </div>`;
                            }
                            return `<div class="d-flex justify-content-between align-items-center flex-nowrap text-nowrap">
                                <div class="text-nowrap">
                                    <div>
                                        <span class="fw-bold text-success text-nowrap" style="font-size:1.5rem; white-space:nowrap;">${priceStr}</span>
                                        <small class="text-muted text-nowrap" style="font-size:1rem; white-space:nowrap;">/ ${periodLabel}</small>
                                    </div>
                                    ${setupLine}
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
                    () => ({
                        location: selectedLocation
                    }),
                    {
                        serverSide: false,
                        paging: false,
                        searching: false,
                    }
                );

                $('#products tbody').on('click', 'tr', function (e) {
                    if ($(e.target).closest('td').index() === 0 || $(e.target).is('button, a')) return;
                    selectRow($(this));
                });

                $('#products tbody').on('click', 'input[type="radio"].select-product', function (e) {
                    e.stopPropagation();
                    var $row = $(this).closest('tr');
                    selectRow($row);
                });

                $dataTable.on('draw', function () {
                    // After every draw we (re)build the top billing-cycle bar from the
                    // union of periods across currently displayed products — if a
                    // different location has fewer periods, the bar adjusts on its own.
                    rebuildPeriodTopbar();

                    var $firstRow = $('#products tbody tr:first');
                    if ($firstRow.length) {
                        selectRow($firstRow);
                    }
                });

                function selectRow($row) {
                    $row.find('.select-product').prop('checked', true);
                    product_uuid = $row.find('.select-product').val();
                    $('#selected_product_uuid').val(product_uuid);

                    $('#products tbody tr').removeClass('selected-row');
                    $row.addClass('selected-row');

                    $('.check-icon').addClass('d-none');
                    $row.find('.check-icon').removeClass('d-none');

                    // Resolve the product_price_uuid for the CURRENT billing cycle from the
                    // row's full data (DataTables preserves the original JS object).
                    var rowData = $dataTable.row($row).data() || {};
                    var bp = pickPeriod(rowData.billing_periods);
                    product_price_uuid = bp ? bp.price_uuid : '';
                    $('#selected_product_price_uuid').val(product_price_uuid);

                    var billingMode = $row.find('.select-product').data('billing_mode');
                    if (billingMode === 'both') {
                        $('#billing_mode_selector').show();
                        $('#billing_mode_hidden').val($('input[name="billing_mode"]:checked').val());
                    } else {
                        $('#billing_mode_selector').hide();
                        $('#billing_mode_hidden').val(billingMode === 'invoice' ? 'invoice' : 'prepaid');
                    }

                    loadOptions();
                }
            }

            // Resolve the current-period entry from a product's billing_periods array.
            // Falls back to the first available period if `currentPeriod` isn't offered by
            // this particular product (e.g. weekly exists for some products but not others).
            function pickPeriod(periods) {
                if (!periods || periods.length === 0) return null;
                var found = periods.find(function (p) { return p.period === currentPeriod; });
                return found || periods[0];
            }

            // Re-render the small price block on each location widget using the current period.
            // Falls back gracefully if a location doesn't have that period configured.
            function renderLocationPrices() {
                $('.location-widget').each(function () {
                    var $widget = $(this);
                    var pricesByPeriod = $widget.data('pricesByPeriod') || {};

                    // Try exact currentPeriod first; fall back to any available period.
                    var entry = pricesByPeriod[currentPeriod];
                    if (!entry) {
                        var firstKey = Object.keys(pricesByPeriod)[0];
                        entry = firstKey ? pricesByPeriod[firstKey] : null;
                    }

                    var html = '';
                    if (entry) {
                        if (entry.setup) {
                            html += '<div class="text-nowrap">' + translate('Setup') + ': <span class="fw-bold text-success">' + entry.setup + '</span></div>';
                        }
                        if (entry.base) {
                            html += '<div class="text-nowrap"><span class="fw-bold text-success">' + entry.base + '</span> / ' + (entry.label || '') + '</div>';
                        }
                    }

                    $widget.find('.location-price-block').html(html);
                });
            }

            // Build the top billing-cycle button bar from the union of periods across all
            // currently displayed rows. Called from DataTable `draw` so location changes /
            // AJAX reloads automatically keep the bar in sync with what's on screen.
            function rebuildPeriodTopbar() {
                var $bar = $('#billing_period_buttons');
                $bar.empty();

                // Collect unique periods (keyed by period name) preserving the config order
                // from `getAvailableBillingPeriods` (already sorted by canonical PERIOD_ORDER).
                var seen = {};
                var periods = [];
                $dataTable.rows({filter: 'applied'}).data().each(function (row) {
                    (row.billing_periods || []).forEach(function (p) {
                        if (!seen[p.period]) {
                            seen[p.period] = true;
                            periods.push({period: p.period, label: p.label});
                        }
                    });
                });

                if (periods.length === 0) {
                    $('#billing_period_topbar').hide();
                    return;
                }

                // Clamp currentPeriod to something that's actually available.
                if (!seen[currentPeriod]) {
                    currentPeriod = periods[0].period;
                }

                // Render each period as a pill button. The active one gets primary color;
                // clicking a non-active button switches the whole view to that cycle.
                periods.forEach(function (p) {
                    var isActive = p.period === currentPeriod;
                    var $btn = $('<button>', {
                        type: 'button',
                        class: 'btn btn-sm ' + (isActive ? 'btn-primary' : 'btn-outline-primary'),
                        'data-period': p.period,
                        text: p.label,
                    });
                    $bar.append($btn);
                });

                // Only show the bar when there's more than one cycle available. A single-cycle
                // product group means there's nothing to pick from — hide the clutter.
                if (periods.length > 1) {
                    $('#billing_period_topbar').show();
                } else {
                    $('#billing_period_topbar').hide();
                }
            }

            // Click handler: user switches the global billing cycle at the top.
            // After the switch we:
            //   1) update `currentPeriod`
            //   2) redraw the DataTable so price cells + radio data_price_uuid update
            //   3) re-sync the selected row's product_price_uuid from its new period
            //   4) reload options (option prices are keyed by period, so they change too)
            //   5) updateOrderSummary fires from inside loadOptions → tax + total update
            $(document).on('click', '#billing_period_buttons button', function () {
                var newPeriod = $(this).data('period');
                if (newPeriod === currentPeriod) return;

                currentPeriod = newPeriod;

                // Swap button active state without full rebuild (avoids a visual flash).
                $('#billing_period_buttons button').each(function () {
                    var active = $(this).data('period') === currentPeriod;
                    $(this).toggleClass('btn-primary', active).toggleClass('btn-outline-primary', !active);
                });

                // Re-render location tile prices for the new cycle (setup fees + recurring rates
                // differ per period, so the tiles need to stay in sync with what's below them).
                renderLocationPrices();

                if (!$dataTable) return;

                // Redraw prices + radio price_uuid attributes with the new period. Using
                // draw(false) keeps the current page/scroll and does NOT trigger the `draw`
                // event's auto-select (which would reset selection to first row).
                $dataTable.rows().invalidate().draw(false);

                // If a product is already selected, re-sync its price_uuid from the new
                // period and reload options so option prices reflect the new cycle too.
                if (product_uuid) {
                    var $selectedRow = $('#products tbody tr.selected-row').first();
                    if ($selectedRow.length) {
                        var rowData = $dataTable.row($selectedRow).data() || {};
                        var bp = pickPeriod(rowData.billing_periods);
                        product_price_uuid = bp ? bp.price_uuid : '';
                        $('#selected_product_price_uuid').val(product_price_uuid);
                        $selectedRow.find('.select-product').attr('data-product_price_uuid', product_price_uuid);
                        loadOptions();
                    }
                }
            });

            function updateValues() {
                if (selectedLocation) {
                    if ($dataTable) {
                        $dataTable.ajax.reload();
                    } else {
                        loadProducts();
                    }

                    $('#no-products').hide();
                    $('#productsTable').show();

                    clearSummary();
                    $optionsContainer.empty();
                    $('#optionsCard').hide();
                    $('#billing_mode_selector').hide();
                    $('#billing_period_topbar').hide();
                }
            }

            function loadOptions() {
                if (!product_uuid || !product_price_uuid) return;

                blockUI('optionsCard');

                PUQajax(
                    `{{ route('client.api.cloud.group.order.get',['uuid'=>$product_group->uuid,'method'=>'ProductOptionGroupsByProduct']) }}?product_uuid=${product_uuid}&product_price_uuid=${product_price_uuid}`,
                    {},
                    50,
                    null,
                    'GET'
                ).then(response => {
                    $optionsContainer.empty();

                    let html = '';
                    let hasNonLocationOptions = false;

                    if (response.data.length > 0) {
                        response.data.forEach((group) => {
                            // Skip location option group — handled automatically
                            if (group.type === 'location') {
                                // Find matching option for selected location
                                group.product_options.forEach(opt => {
                                    if (opt.value === selectedLocation) {
                                        $('#location_product_option_uuid').val(opt.uuid);
                                    }
                                });
                                return;
                            }

                            hasNonLocationOptions = true;
                            const selectId = `option_${group.uuid}`;
                            html += `
                                <div class="mb-3">
                                    <label class="form-label fw-bold">${group.name}</label>
                                    <small class="form-text text-muted d-block mb-1">${group.short_description || ''}</small>
                                    <select class="form-select product-option" data-uuid="${group.uuid}" name="${group.uuid}" id="${selectId}">
                                        ${group.product_options.map(opt => `<option value="${opt.uuid}">${opt.key}</option>`).join('')}
                                    </select>
                                </div>`;
                        });
                    }

                    if (hasNonLocationOptions) {
                        $optionsContainer.html(html);
                        $('#optionsCard').show();
                        $(".product-option").on('change', updateOrderSummary);
                    } else {
                        $('#optionsCard').hide();
                    }

                    unblockUI('optionsCard');
                    updateOrderSummary();
                }).catch(error => {
                    console.error(error);
                    unblockUI('optionsCard');
                });
            }

            function updateOrderSummary() {
                if (!product_uuid || !product_price_uuid) return;

                const formData = {
                    selected_product_uuid: product_uuid,
                    selected_product_price_uuid: product_price_uuid,
                    selectedLocation: selectedLocation,
                    billing_mode: $('#billing_mode_hidden').val() || $('input[name="billing_mode"]:checked').val() || 'prepaid',
                };

                $(".product-option").each(function () {
                    const uuid = $(this).data('uuid');
                    const value = $(this).val();
                    if (value) formData[uuid] = value;
                });

                blockUI('orderSummary');

                PUQajax(
                    '{{ route('client.api.cloud.group.order.get', ['uuid' => $product_group->uuid, 'method' => 'CalculateSummary']) }}',
                    formData,
                    100,
                    null,
                    'GET'
                ).then(response => {
                    renderOrderSummary(response.data);
                    unblockUI('orderSummary');
                }).catch(err => {
                    console.error(err);
                    unblockUI('orderSummary');
                });
            }

            function renderOrderSummary(summary) {
                let html = `
                    <div class="mb-2"><strong>${summary.product_group}</strong></div>
                    <div class="d-flex justify-content-between"><span>${summary.product_name}</span><span class="text-nowrap fw-bold text-dark">${summary.price}</span></div>
                `;

                summary.options.forEach(opt => {
                    html += `<div class="d-flex justify-content-between text-muted"><span>&raquo; ${opt.label}</span><span class="text-nowrap">${opt.price}</span></div>`;
                });

                html += `
                    <hr>
                    <div class="d-flex justify-content-between"><span>${translate('Setup Fee')}:</span><span>${summary.setup_fee}</span></div>
                `;

                const hasTaxes = summary.taxes && summary.taxes.length > 0;
                if (hasTaxes) {
                    html += `<div class="d-flex justify-content-between mt-2"><span>${translate('Subtotal')}:</span><span class="text-nowrap">${summary.subtotal_str}</span></div>`;
                    summary.taxes.forEach(tax => {
                        html += `<div class="d-flex justify-content-between text-muted"><span>${tax.name} (${tax.rate}%)</span><span class="text-nowrap">${tax.amount_str}</span></div>`;
                    });
                    html += `
                    <div class="mt-4 text-center">
                        <div class="fw-bold fs-1 text-primary">${summary.total_str}</div>
                        <div class="text-muted">${translate('Total with taxes')} / ${summary.period}</div>
                    </div>
                    `;
                } else {
                    html += `
                    <div class="mt-4 text-center">
                        <div class="fw-bold fs-1 text-primary">${summary.total_str}</div>
                        <div class="text-muted">${summary.period}</div>
                    </div>
                    `;
                }

                $summaryContent.html(html);
                $('#btnCompleteProductConfig').prop('disabled', false);
            }

            $('input[name="billing_mode"]').on('change', function() {
                $('#billing_mode_hidden').val($(this).val());
                // Re-calculate so the tax block appears/disappears dynamically.
                updateOrderSummary();
            });

            $("#btnCompleteProductConfig").on("click", function (event) {
                event.preventDefault();
                if (!product_uuid || !product_price_uuid) return;

                const $form = $("#orderForm");
                const formData = serializeForm($form);
                PUQajax('{{ route('client.api.cloud.group.order.get', ['uuid' => $product_group->uuid, 'method' => 'CreateService']) }}', formData, 50, $(this), 'POST', $form)
                    .then(response => {
                        // success logic
                    });
            });

            // Init
            loadLocations();
        });
    </script>
@endsection
