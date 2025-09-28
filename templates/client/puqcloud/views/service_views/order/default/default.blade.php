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
                {{$title}}
                <div class="page-title-subheading">
                    {{$product_group->short_description}}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container px-0">

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

            <div id="mainCard" class="card shadow-sm">
                <div class="card-body">
                    <form id="orderForm" novalidate="novalidate">
                        <div class="position-relative mb-3">
                            <label for="product_uuid" class="form-label">{{__('main.Product')}}</label>
                            <select name="product_uuid" id="product_uuid" class="form-select mb-2 form-control"
                                    disabled></select>
                        </div>

                        <div class="position-relative mb-3">
                            <label for="product_price_uuid" class="form-label">{{__('main.Price')}}</label>
                            <select name="product_price_uuid" id="product_price_uuid" class="form-select mb-2 form-control"
                                    disabled></select>
                        </div>
                        <div id="product_price_data"></div>
                        <div id="product_data"></div>

                    </form>
                </div>
            </div>

        </div>

        <div class="col-md-4 mb-2">
            <div class="position-sticky" style="top: 2rem;">
                <div id="orderSummary" class="card" style="min-height: 250px;">
                    <div class="card-body position-relative">

                        <h5 class="card-title">{{__('main.Summary')}}</h5>
                        <div id="orderSummaryContent" class="text-center text-muted py-5">

                        </div>
                        <hr>
                        <div class="text-center mt-3">
                            <button type="submit" id="btnCompleteProductConfig"
                                    class="btn-wide mb-0 me-0 btn btn-outline-2x btn-outline-primary btn-lg">
                                <i class="fas fa-cloud-upload-alt me-2"></i> {{__('main.Deploy')}}
                            </button>
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
            const $productSelect = $("#product_uuid");
            const $priceSelect = $("#product_price_uuid");
            const $optionsContainer = $("#product_data");
            const $summaryContent = $("#orderSummaryContent");

            let product_uuid = '';
            let product_price_uuid = '';
            clearSummary();

            function resetBelowProduct() {
                product_price_uuid = '';
                $priceSelect.val(null).trigger('change').prop('disabled', true);
                $optionsContainer.empty();
                clearSummary();
            }

            function resetBelowPrice() {
                $optionsContainer.empty();
                clearSummary();
            }

            function clearSummary() {
                $summaryContent.html(`
        <div class="text-center text-muted py-5">
            <i class="fas fa-info-circle fa-4x mb-3 text-info"></i><br>
            ${translate('Please select a product and configuration options')}<br>
            ${translate('The total cost will appear here')}
                </div>
`);
            }

            $productSelect.prop('disabled', false);
            $priceSelect.prop('disabled', true);

            initializeSelect2($productSelect, '{{ route('client.api.cloud.group.order.get',['uuid'=>$product_group->uuid,'method'=>'ProductsSelect']) }}', '', 'GET', 1000);

            $productSelect.on('change', function () {
                product_uuid = $(this).val();
                resetBelowProduct();
                if (product_uuid) {
                    $priceSelect.prop('disabled', false);
                    initializeSelect2($priceSelect, '{{ route('client.api.cloud.group.order.get',['uuid'=>$product_group->uuid,'method'=>'ProductPricesSelect']) }}', '', 'GET', 1000, {}, {
                        product_uuid: () => product_uuid
                    });
                }
            });

            $priceSelect.on('change', function () {
                product_price_uuid = $(this).val();
                resetBelowPrice();
                updateOrderSummary();
                if (product_price_uuid) {
                    updateProductOptions();
                }
            });

            function updateProductOptions() {
                blockUI('mainCard');

                PUQajax(
                    `{{ route('client.api.cloud.group.order.get',['uuid'=>$product_group->uuid,'method'=>'ProductOptionGroupsByProduct']) }}?product_uuid=${product_uuid}&product_price_uuid=${product_price_uuid}`,
                    {},
                    50,
                    null,
                    'GET'
                ).then(response => {
                    $optionsContainer.empty();
                    if (response.data.length > 0) {
                        let html = '';
                        response.data.forEach((group, index) => {
                            const selectId = `option_${group.uuid}`;
                            html += `
                        <div class="mb-3">
                            <label class="form-label fw-bold">${group.name}</label>
                            <small class="form-text text-muted d-block mb-1">${group.short_description}</small>
                            <select class="form-select product-option" data-uuid="${group.uuid}" name="${group.uuid}" id="${selectId}">
                                ${group.product_options.map(opt => `<option value="${opt.uuid}">${opt.key}</option>`).join('')}
                            </select>
                        </div>`;
                        });
                        $optionsContainer.html(html);

                        $(".product-option").on('change', updateOrderSummary);
                    }

                    unblockUI('mainCard');
                    updateOrderSummary();
                }).catch(error => {
                    console.error(error);
                    unblockUI('mainCard');
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
                        option_uuids: selectedOptions
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
            <div class="d-flex justify-content-between"><span>${summary.product_name}</span><span class="text-nowrap fw-bold text-dark">${summary.price}</span></div>
        `;

                summary.options.forEach(opt => {
                    html += `<div class="d-flex justify-content-between text-muted"><span>» ${opt.label}</span><span class="text-nowrap">${opt.price}</span></div>`;
                });

                html += `
            <hr>
            <div class="d-flex justify-content-between"><span>${translate('Setup Fee')}:</span><span>${summary.setup_fee}</span></div>

            <div class="mt-4 text-center">
                <div class="fw-bold fs-1 text-primary">${summary.total}</div>
                <div class="text-muted">${summary.period}</div>
            </div>
        `;

                $summaryContent.html(html);
            }

            $("#btnCompleteProductConfig").on("click", function (event) {
                event.preventDefault();
                const $form = $("#orderForm");
                const formData = serializeForm($form);
                PUQajax('{{ route('client.api.cloud.group.order.get', ['uuid' => $product_group->uuid, 'method' => 'CreateService']) }}', formData, 50, $(this), 'POST', $form)
                    .then(response => {
                        // success logic
                    });
            });
        });

    </script>
@endsection
