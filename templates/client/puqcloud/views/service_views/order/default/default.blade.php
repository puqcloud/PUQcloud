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
    .product-card {
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        border: 2px solid transparent;
    }
    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .product-card.selected {
        border-color: #28a745;
        background-color: #f8fff9;
        box-shadow: 0 2px 10px rgba(40, 167, 69, 0.15);
    }
    /* Fixed-height icon slot — every product card has exactly the same icon row height.
       Key detail: `height: 88px` is EXPLICIT on the image (not max-height), which forces
       the browser to scale smaller natural images UP to fill the slot. Using `max-height`
       alone would leave small images at their tiny natural size because max- rules only
       clamp the upper bound and browsers never upscale past natural dimensions on their own. */
    .product-card .product-icon-slot {
        height: 88px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .product-card .product-icon-slot img {
        height: 88px;         /* force — all icons become exactly 88px tall */
        width: auto;          /* aspect preserved */
        max-width: 100%;      /* but don't overflow the card column horizontally */
        object-fit: contain;
    }
    .product-card .product-icon-slot i {
        font-size: 3.5rem;    /* fallback fa icon — no product.icon uploaded */
        line-height: 88px;
    }

    /* Equal-height cards: every card in a row stretches to match the tallest one,
       and the price footer is pushed to the bottom via `mt-auto` so prices line up
       across cards regardless of how long each product description is. */
    .product-card {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .product-card .card-body {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
    }
    .product-card .product-price-footer {
        margin-top: auto;     /* pushes the price block to the bottom of the card */
        padding-top: 0.5rem;
    }
    .product-card .product-description {
        font-size: 0.85rem;
        color: #495057;
        line-height: 1.45;
        text-align: left;
    }
    /* Admin-authored HTML inside the description: keep basic elements looking sensible
       inside the narrow card column. Lists get tight spacing, images are constrained
       to the card width, and headings are scaled down so they don't dominate. */
    .product-card .product-description p { margin: 0 0 0.4rem; }
    .product-card .product-description p:last-child { margin-bottom: 0; }
    .product-card .product-description ul,
    .product-card .product-description ol { margin: 0 0 0.4rem 1.2rem; padding: 0; }
    .product-card .product-description li { margin-bottom: 0.15rem; }
    .product-card .product-description h1,
    .product-card .product-description h2,
    .product-card .product-description h3,
    .product-card .product-description h4,
    .product-card .product-description h5,
    .product-card .product-description h6 {
        font-size: 0.95rem;
        font-weight: 600;
        margin: 0.3rem 0;
        color: #212529;
    }
    .product-card .product-description img { max-width: 100%; height: auto; }
    .product-card .product-description a { color: #0d6efd; }
    .product-card .product-description table { width: 100%; font-size: 0.78rem; }
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
                    <i class="fas fa-cloud icon-gradient bg-ripe-malin"></i>
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
                            <p class="card-text text-muted mb-0">
                                {{ $product_group->description }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Products (card grid) --}}
                <div class="main-card mb-2 card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="mb-0"><i class="fas fa-cube me-2"></i>{{ __('main.Product') }}</h5>
                    </div>
                    <div class="card-body">
                        {{-- Billing cycle top-bar (shared pattern with puqVPNCP) — appears
                             only when more than one cycle is available across the products. --}}
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

                        <div id="products_grid" class="row g-3"></div>

                        <div id="no-products" class="alert alert-info align-items-start gap-2 rounded-4 shadow-sm mb-0 mt-2" role="alert" style="display:none;">
                            <i class="fa fa-info-circle fs-3 text-primary mt-1"></i>
                            <div>
                                <p class="mb-1">{{ __('main.No products available') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Options (shown after product + price is selected) --}}
                <div id="optionsCard" class="card mb-2" style="display: none;">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>{{ __('main.Options') }}</h5>
                    </div>
                    <div class="card-body">
                        <form id="orderForm" novalidate="novalidate">
                            <input type="hidden" name="product_uuid" id="product_uuid" value="">
                            <input type="hidden" name="product_price_uuid" id="product_price_uuid" value="">
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
                                    <i class="fas fa-cloud-upload-alt me-2"></i> {{__('main.Deploy')}}
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

            var productsData = [];
            var product_uuid = '';
            var product_price_uuid = '';
            // Global billing cycle — defaults to monthly, clamped to whatever periods the
            // loaded products actually have. Shared pattern with puqVPNCP order template.
            var currentPeriod = 'monthly';

            const $optionsContainer = $("#product_data");
            const $summaryContent = $("#orderSummaryContent");
            const $productsGrid = $("#products_grid");

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

            // Resolve the current-period entry from a product's billing_periods array.
            // Fallback to the first available period if currentPeriod isn't offered by
            // this particular product.
            function pickPeriod(periods) {
                if (!periods || periods.length === 0) return null;
                var found = periods.find(function (p) { return p.period === currentPeriod; });
                return found || periods[0];
            }

            // Load the universal product list with billing_periods attached and render
            // a card grid. Each card is clickable and shows the price for the current cycle
            // plus a setup hint when setup > 0.
            function loadProducts() {
                blockUI('products_grid');
                PUQajax(
                    '{{ route('client.api.cloud.group.order.get',['uuid'=>$product_group->uuid,'method'=>'GetProducts']) }}',
                    {},
                    50,
                    null,
                    'GET'
                ).then(response => {
                    // Extract rows from the DataTable-style response shape.
                    productsData = (response && response.data && response.data.original && response.data.original.data) || [];

                    if (productsData.length === 0) {
                        $productsGrid.empty();
                        $('#no-products').show();
                        $('#billing_period_topbar').hide();
                        unblockUI('products_grid');
                        return;
                    }

                    $('#no-products').hide();
                    rebuildPeriodTopbar();
                    renderProductCards();

                    // Auto-select the first product so the summary starts populated.
                    const firstUuid = productsData[0].uuid;
                    selectProduct(firstUuid);

                    unblockUI('products_grid');
                }).catch(err => {
                    console.error(err);
                    unblockUI('products_grid');
                });
            }

            // Escape HTML so admin-supplied descriptions can't inject markup.
            function escapeHtml(str) {
                if (str == null) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Render product cards for the CURRENT billing cycle. Called both on initial
            // load and whenever currentPeriod changes.
            function renderProductCards() {
                $productsGrid.empty();

                productsData.forEach(product => {
                    const bp = pickPeriod(product.billing_periods);
                    const priceStr = bp ? bp.base_str : product.price_string;
                    const periodLabel = bp ? bp.label : '';
                    const setupLine = (bp && bp.setup > 0)
                        ? `<div class="text-muted small mt-1">+ ${translate('Setup')}: <span class="fw-semibold">${bp.setup_str}</span></div>`
                        : '';
                    // Icon is wrapped in a fixed-height flex slot so every product card has
                    // the same icon row height — tallest image defines the row, shorter ones
                    // are vertically centered so the card bodies line up.
                    const iconInner = product.icon
                        ? `<img src="${escapeHtml(product.icon)}" alt="${escapeHtml(product.name)}">`
                        : `<i class="fas fa-cube text-primary"></i>`;
                    const iconHtml = `<div class="product-icon-slot">${iconInner}</div>`;

                    // Short description sits directly under the product name as a subtitle.
                    const shortDesc = product.short_description
                        ? `<div class="small text-muted mt-1">${escapeHtml(product.short_description)}</div>`
                        : '';

                    // Full description is rendered as raw HTML (admin can use the WYSIWYG editor
                    // to format it with paragraphs, lists, images, etc.). Always shown in full —
                    // no collapse/expand toggle. CSS scopes inner element styles so admin markup
                    // stays readable inside the narrow card column.
                    let descBlock = '';
                    if (product.description && product.description.trim() !== '') {
                        descBlock = `<div class="product-description mt-2">${product.description}</div>`;
                    }

                    const selectedClass = product.uuid === product_uuid ? 'selected' : '';
                    const card = `
<div class="col-12 col-sm-6 col-lg-4 d-flex">
    <div class="product-card card w-100 ${selectedClass}" data-product-uuid="${escapeHtml(product.uuid)}">
        <div class="card-body text-center">
            <div class="mb-2">${iconHtml}</div>
            <h6 class="fw-bold mb-1">${escapeHtml(product.name)}</h6>
            ${shortDesc}
            ${descBlock}
            <div class="product-price-footer">
                <hr class="my-2">
                <div>
                    <span class="h4 text-success fw-bold">${priceStr}</span>
                    <small class="text-muted d-block">/ ${periodLabel}</small>
                </div>
                ${setupLine}
            </div>
        </div>
    </div>
</div>`;
                    $productsGrid.append(card);
                });
            }

            // Build the billing-cycle pill button bar from the union of periods across
            // all loaded products. Hides itself when only one cycle is available.
            function rebuildPeriodTopbar() {
                var $bar = $('#billing_period_buttons');
                $bar.empty();

                var seen = {};
                var periods = [];
                productsData.forEach(function (product) {
                    (product.billing_periods || []).forEach(function (p) {
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

                // Clamp currentPeriod to something actually available across products.
                if (!seen[currentPeriod]) {
                    currentPeriod = periods[0].period;
                }

                periods.forEach(function (p) {
                    var isActive = p.period === currentPeriod;
                    $('<button>', {
                        type: 'button',
                        class: 'btn btn-sm ' + (isActive ? 'btn-primary' : 'btn-outline-primary'),
                        'data-period': p.period,
                        text: p.label,
                    }).appendTo($bar);
                });

                if (periods.length > 1) {
                    $('#billing_period_topbar').show();
                } else {
                    $('#billing_period_topbar').hide();
                }
            }

            // User picked a product card — update the selection state, resolve its
            // price UUID for the current cycle, wire up billing mode and load options.
            function selectProduct(uuid) {
                product_uuid = uuid;
                const product = productsData.find(p => p.uuid === uuid);
                if (!product) return;

                $('.product-card').removeClass('selected');
                $(`.product-card[data-product-uuid="${uuid}"]`).addClass('selected');

                $('#product_uuid').val(product_uuid);

                const bp = pickPeriod(product.billing_periods);
                product_price_uuid = bp ? bp.price_uuid : '';
                $('#product_price_uuid').val(product_price_uuid);

                // Billing mode: show selector only for products that allow both modes.
                if (product.billing_mode === 'both') {
                    $('#billing_mode_selector').show();
                    $('#billing_mode_hidden').val($('input[name="billing_mode"]:checked').val());
                } else {
                    $('#billing_mode_selector').hide();
                    $('#billing_mode_hidden').val(product.billing_mode === 'invoice' ? 'invoice' : 'prepaid');
                }

                loadOptions();
            }

            // Load + render the product's option groups for the CURRENT price_uuid.
            // Option prices match the period of the selected price row (handled by
            // Product::getOptionGroupsForPrice on the backend).
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

                    // Filter out groups with zero options (defensive) and render each as a select.
                    const groups = (response.data || []).filter(g => g.product_options && g.product_options.length > 0);

                    if (groups.length > 0) {
                        let html = '';
                        groups.forEach(group => {
                            const selectId = `option_${group.uuid}`;
                            html += `
                                <div class="mb-3">
                                    <label class="form-label fw-bold">${group.name}</label>
                                    ${group.short_description ? '<small class="form-text text-muted d-block mb-1">' + group.short_description + '</small>' : ''}
                                    <select class="form-select product-option" data-uuid="${group.uuid}" name="${group.uuid}" id="${selectId}">
                                        ${group.product_options.map(opt => `<option value="${opt.uuid}">${opt.key}</option>`).join('')}
                                    </select>
                                </div>`;
                        });
                        $optionsContainer.html(html);
                        $('#optionsCard').show();
                        $(".product-option").on('change', updateOrderSummary);
                    } else {
                        $('#optionsCard').hide();
                    }

                    unblockUI('optionsCard');
                    updateOrderSummary();
                }).catch(err => {
                    console.error(err);
                    unblockUI('optionsCard');
                });
            }

            function updateOrderSummary() {
                if (!product_uuid || !product_price_uuid) return;

                const selectedOptions = [];
                $(".product-option").each(function () {
                    const value = $(this).val();
                    if (value) selectedOptions.push(value);
                });

                blockUI('orderSummary');
                PUQajax(
                    '{{ route('client.api.cloud.group.order.get', ['uuid' => $product_group->uuid, 'method' => 'CalculateSummary']) }}',
                    {
                        product_uuid,
                        product_price_uuid,
                        option_uuids: selectedOptions,
                        billing_mode: $('#billing_mode_hidden').val() || $('input[name="billing_mode"]:checked').val() || 'prepaid'
                    },
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
                    <div class="d-flex justify-content-between">
                        <span>${summary.product_name}</span>
                        <span class="text-nowrap fw-bold text-dark">${summary.price}</span>
                    </div>
                `;

                summary.options.forEach(opt => {
                    html += `<div class="d-flex justify-content-between text-muted">
                        <span>» ${opt.label}</span>
                        <span class="text-nowrap">${opt.price}</span>
                    </div>`;
                });

                html += `
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>${translate('Setup Fee')}:</span>
                        <span>${summary.setup_fee}</span>
                    </div>
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
                        </div>`;
                } else {
                    html += `
                        <div class="mt-4 text-center">
                            <div class="fw-bold fs-1 text-primary">${summary.total_str}</div>
                            <div class="text-muted">${summary.period}</div>
                        </div>`;
                }

                $summaryContent.html(html);
                $('#btnCompleteProductConfig').prop('disabled', false);
            }

            // Product card click handler — click anywhere on the card selects the product.
            // Links inside the description still work normally (they get their own click
            // before bubbling, and since selectProduct is idempotent clicking a link on the
            // already-selected card is harmless).
            $(document).on('click', '.product-card', function (e) {
                // Let anchor tags inside description navigate instead of swallowing the click.
                if ($(e.target).is('a') || $(e.target).closest('a').length) return;
                selectProduct($(this).data('product-uuid'));
            });

            // Billing cycle switch: update currentPeriod, re-render cards (prices change),
            // re-sync the selected product's price_uuid from the new cycle, reload options
            // so their period prices match, and recalc the summary.
            $(document).on('click', '#billing_period_buttons button', function () {
                const newPeriod = $(this).data('period');
                if (newPeriod === currentPeriod) return;

                currentPeriod = newPeriod;

                $('#billing_period_buttons button').each(function () {
                    const active = $(this).data('period') === currentPeriod;
                    $(this).toggleClass('btn-primary', active).toggleClass('btn-outline-primary', !active);
                });

                renderProductCards();

                if (product_uuid) {
                    const product = productsData.find(p => p.uuid === product_uuid);
                    if (product) {
                        const bp = pickPeriod(product.billing_periods);
                        product_price_uuid = bp ? bp.price_uuid : '';
                        $('#product_price_uuid').val(product_price_uuid);
                        loadOptions();
                    }
                }
            });

            // Billing mode radio change: re-calculate summary so the tax block appears/disappears.
            $('input[name="billing_mode"]').on('change', function () {
                $('#billing_mode_hidden').val($(this).val());
                updateOrderSummary();
            });

            // Deploy button
            $("#btnCompleteProductConfig").on("click", function (event) {
                event.preventDefault();
                const $form = $("#orderForm");
                const formData = serializeForm($form);
                PUQajax('{{ route('client.api.cloud.group.order.get', ['uuid' => $product_group->uuid, 'method' => 'CreateService']) }}', formData, 50, $(this), 'POST', $form)
                    .then(response => {
                        // success logic handled by PUQajax redirect
                    });
            });

            // Init
            loadProducts();
        });
    </script>
@endsection
