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


    .cpu-option-card {
        transition: all 0.2s;
        flex: 1 0 120px;
        min-width: 100px;
        cursor: pointer;
    }

    .cpu-option-card:hover {
        transform: scale(1.05);
        border-color: #0d6efd;
    }

    .cpu-option-card.selected {
        background-color: #e7f1ff;
        border-width: 2px;
    }

    .cpu-icon {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .cpu-price {
        font-size: 0.9em;
        color: #6c757d;
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
                            <p class="card-text text-muted">
                                {{ $product_group->description }}
                            </p>
                        </div>
                    </div>
                @endif

                <div class="card mb-2" id="locations" style="min-height: 150px;">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="mb-0">{{__('Product.puqProxmox.Location')}}</h5>
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
                                <p class="mb-1">
                                    {!! __('Product.puqProxmox.Please select <strong>location</strong> first') !!}
                                </p>
                                <p class="mb-0 text-muted">
                                    {{ __('Product.puqProxmox.After that, the list of available applications will appear here') }}
                                </p>
                            </div>
                        </div>
                        <table style="width: 100%; display: none;" id="products"
                               class="table table-hover table-striped table-bordered">
                            <thead>
                            <tr>
                                <th></th>
                                <th></th>
                                <th><i class="fa fa-server"></i> {{__('Product.puqProxmox.Name')}}</th>
                                <th><i class="fa fa-dollar-sign"></i> {{__('Product.puqProxmox.Price')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="productOptions" style="display: none; min-height: 150px;">
                    <form id="orderForm" novalidate="novalidate">

                        <input type="hidden" id="selectedLocation" name="selectedLocation" value="">

                        <input type="hidden" id="selected_product_uuid" name="selected_product_uuid" value="">
                        <input type="hidden" id="selected_product_price_uuid" name="selected_product_price_uuid"
                               value="">
                        <input type="hidden" id="location_product_option_uuid"
                               name="location_product_option_uuid" value="">
                        <input type="hidden" id="os_product_option_uuid" name="os_product_option_uuid"
                               value="">

                        <input type="hidden" id="cpu_cores_product_option_uuid"
                               name="cpu_cores_product_option_uuid" value="">
                        <input type="hidden" id="memory_product_option_uuid"
                               name="memory_product_option_uuid" value="">

                        <input type="hidden" id="mp_size_product_option_uuid"
                               name="mp_size_product_option_uuid" value="">

                        <input type="hidden" id="backup_count_product_option_uuid"
                               name="backup_count_product_option_uuid" value="">

                        <div id="product_data"></div>

                    </form>
                </div>

            </div>

            <div class="col-md-4 mb-2">
                <div class="position-sticky" style="top: 2rem;">
                    <div id="orderSummary" class="card" style="min-height: 250px;">
                        <div class="card-body position-relative">

                            <h5 class="card-title">{{__('Product.puqProxmox.Summary')}}</h5>
                            <div id="orderSummaryContent" class="text-center text-muted py-5">

                            </div>
                            <hr>
                            <div class="text-center mt-3">
                                <button type="submit" id="btnCompleteProductConfig"
                                        class="btn-wide mb-0 me-0 btn btn-outline-2x btn-outline-primary btn-lg">
                                    <i class="fas fa-cloud-upload-alt me-2"></i> {{__('Product.puqProxmox.Deploy')}}
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
            var selectedLocation;
            var product_uuid = '';
            var product_price_uuid = '';

            const $optionsContainer = $("#product_data");
            const $summaryContent = $("#orderSummaryContent");

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
                            const card = `
<div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-4">
    <div class="location-widget border rounded p-1 mb-1 position-relative" data-value="${location.value}" style="cursor:pointer;">
        <div class="widget-content p-0 d-flex align-items-center">
            <div class="widget-content-left me-1">
                <div class="avatar-icon-wrapper">
                    <div class="avatar-icon rounded" style="width:64px; height:48px; overflow:hidden;">
                        <img src="${location.icon}" alt="${location.name}" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                </div>
            </div>

            <div class="widget-content-left flex-grow-1">
                <div class="widget-heading d-flex align-items-center flex-wrap">
                    ${location.name}
                </div>
                <div class="widget-subheading">${location.value}</div>
            </div>

            ${location.price && Object.keys(location.price).length > 0
                                ? `<div class="ms-3 small text-muted">
                       <div class="text-nowrap">
                           {{__('Product.puqProxmox.Setup')}}:
                           <span class="fw-bold text-success">${location.price.setup || ''}</span>
                       </div>
                       <div class="text-nowrap">
                           <span class="fw-bold text-success">${location.price.base || ''}</span>
                           / {{__('Product.puqProxmox.mo')}}
                                </div>
                            </div>`
                                : ''
                            }
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
                            $container.append(card);
                        });

                        $('.location-widget').on('click', function () {
                            $('.location-widget').removeClass('border-3 border-success fw-bold');
                            $('.location-widget .fa-check').addClass('d-none');

                            $(this).addClass('border-3 border-success fw-bold');
                            $(this).find('.fa-check').removeClass('d-none');

                            $('#selectedLocation').val($(this).data('value'));
                            updateValues();
                        });

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

            function loadProduct() {
                var tableId = '#products';
                var ajaxUrl = `{{ route('client.api.cloud.group.order.get',['uuid'=>$product_group->uuid,'method'=>'GetProducts']) }}`;

                var columnsConfig = [
                    {
                        data: "uuid",
                        orderable: false,
                        render: function (data, type, row) {
                            return `
                    <div class="d-flex justify-content-center">
                        <input class="form-check-input select-product" type="radio" name="productSelect"
                        value="${row.uuid}"
                        data-product_price_uuid="${row.product_price_uuid}" style="transform: scale(1.3);">
                    </div>`;
                        }
                    },
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
                        data: "name",
                        orderable: false,
                        render: function (data) {
                            return `<span style="font-size: 1.01rem;" class="fw-semibold">${data}</span>`;
                        }
                    },
                    {
                        data: "price",
                        orderable: false,
                        render: function (data, type, row) {
                            return `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-success" style="font-size: 1.3rem;">${row.price_string}</span>
                            <small class="text-muted" style="font-size: 0.7rem;">/ {{__('Product.puqProxmox.Monthly')}}</small>
                        </div>
                        <i class="fa fa-check text-white d-none check-icon"
                           style="background-color: #28a745;
                                  border-radius: 50%;
                                  padding: 8px;
                                  font-size: 1.4rem;
                                  box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                        </i>
                    </div>`;
                        }
                    }
                ];

                $dataTable = initializeDataTable(
                    tableId,
                    ajaxUrl,
                    columnsConfig,
                    () => ({
                        location: selectedLocation,
                    }),
                    {
                        select: {
                            style: 'single'
                        },
                        serverSide: false,
                        paging: false,
                        searching: true,
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

                    product_price_uuid = $row.find('.select-product').data('product_price_uuid');
                    $('#selected_product_price_uuid').val(product_price_uuid);


                    updateProductOptions();
                    resetBelowPrice();
                    $('#productOptions').show();
                }
            }

            function updateValues() {
                var loc = document.getElementById('selectedLocation').value;

                if (loc) {
                    selectedLocation = loc;
                    if ($dataTable) {
                        $dataTable.ajax.reload();
                    } else {
                        loadProduct();
                    }

                    $('#no-products').hide();
                    $('#products').show();

                    clearSummary();
                    resetBelowPrice();
                    $('#productOptions').hide();
                }
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

            function resetBelowPrice() {
                $optionsContainer.empty();
                clearSummary();
            }

            function renderLocationGroup(group) {
                group.product_options.forEach(opt => {
                    if (opt.value === selectedLocation) {
                        $('#location_product_option_uuid').val(opt.uuid);
                        return 0;
                    }
                });
            }

            function renderCpuGroup(group) {
                let attributes = group.attributes
                    .map(a => `<span class="badge bg-primary px-1 py-1 me-1 mb-1" style="font-size: 0.8rem;">${a}</span>`)
                    .join('');

                let html = `<div class="card mb-2" id="group_${group.uuid}">`;

                html += `
        <div class="card-header">
            <h5 class="mb-0">${group.name} ${attributes}</h5>
        </div>
    `;

                html += `<div class="card-body"><div class="row g-3">
                        <p class="text-muted mb-0">${group.short_description || ''}</p>
                            `;

                group.product_options.forEach(opt => {
                    let priceBlock = '';
                    if (opt.price) {
                        priceBlock = `
                <div class="text-nowrap">
                    <span class="cpu-price text-success fw-bold">${opt.prise_string}</span>
                    <span class="text-muted" style="font-size:0.8rem;"> / {{__('Product.puqProxmox.mo')}}</span>
                </div>`;
                    }

                    html += `
            <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                <div class="card cpu-widget p-2 cursor-pointer position-relative"
                     data-uuid="${opt.uuid}" data-group-uuid="${group.uuid}" data-price="${opt.price}"
                        style="cursor:pointer;" >
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <div class="cpu-icon rounded d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                                <i class="fas fa-microchip fa-2x text-secondary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${opt.key}</div>
                            ${priceBlock}
                        </div>
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
            </div>`;
                });

                html += `</div></div></div>`;
                return html;
            }

            function attachCpuSelection() {
                $('.cpu-widget').on('click', function () {
                    const optionUuid = $(this).data('uuid');
                    const groupUuid = $(this).data('group-uuid');

                    $(`.cpu-widget[data-group-uuid="${groupUuid}"] i.fa-check`).addClass('d-none');
                    $(`.cpu-widget[data-group-uuid="${groupUuid}"]`).removeClass('border-3 border-success fw-bold');

                    $(this).addClass('border-3 border-success fw-bold');
                    $(this).find('i.fa-check').removeClass('d-none');

                    $('#cpu_cores_product_option_uuid').val(optionUuid);

                    updateOrderSummary();
                });

                $('.cpu-widget').each(function () {
                    const $first = $(this).closest('.row').find('.cpu-widget').first();

                    $first.addClass('border-3 border-success fw-bold');
                    $first.find('i.fa-check').removeClass('d-none');
                    $('#cpu_cores_product_option_uuid').val($first.data('uuid'));

                });
            }

            function renderRamGroup(group) {
                let html = `<div class="card mb-2" id="ram_group_${group.uuid}">`;
                let attributes = group.attributes
                    .map(a => `<span class="badge bg-primary px-1 py-1 me-1 mb-1" style="font-size: 0.8rem;">${a}</span>`)
                    .join('');

                html += `
    <div class="card-header">
        <h5 class="mb-0">${group.name} ${attributes}</h5>
    </div>
    `;

                html += `<div class="card-body"><div class="row g-3">
            <p class="text-muted mb-0">${group.short_description || ''}</p>
    `;

                group.product_options.forEach(opt => {
                    let priceBlock = '';
                    if (opt.price) {
                        priceBlock = `
            <div class="text-nowrap">
                <span class="ram-price text-success fw-bold">${opt.prise_string}</span>
                <span class="text-muted" style="font-size:0.8rem;"> / {{__('Product.puqProxmox.mo')}}</span>
            </div>`;
                    }

                    html += `
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card ram-widget p-2 cursor-pointer position-relative"
                 data-uuid="${opt.uuid}" data-group-uuid="${group.uuid}" data-price="${opt.price}"
                 style="cursor:pointer;">
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        <div class="ram-icon rounded d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                            <i class="fas fa-memory fa-2x text-secondary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${opt.key}</div>
                        ${priceBlock}
                    </div>
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
        </div>`;
                });

                html += `</div></div></div>`;
                return html;
            }

            function attachRamSelection() {
                $('.ram-widget').on('click', function () {
                    const optionUuid = $(this).data('uuid');
                    const groupUuid = $(this).data('group-uuid');

                    $(`.ram-widget[data-group-uuid="${groupUuid}"] i.fa-check`).addClass('d-none');
                    $(`.ram-widget[data-group-uuid="${groupUuid}"]`).removeClass('border-3 border-success fw-bold');

                    $(this).addClass('border-3 border-success fw-bold');
                    $(this).find('i.fa-check').removeClass('d-none');

                    $('#memory_product_option_uuid').val(optionUuid);

                    updateOrderSummary();
                });

                $('.ram-widget').each(function () {
                    const $first = $(this).closest('.row').find('.ram-widget').first();

                    $first.addClass('border-3 border-success fw-bold');
                    $first.find('i.fa-check').removeClass('d-none');
                    $('#memory_product_option_uuid').val($first.data('uuid'));
                });
            }

            function renderDiskGroup(group) {
                let html = `<div class="card mb-2" id="group_${group.uuid}">`;
                let attributes = group.attributes
                    .map(a => `<span class="badge bg-primary px-1 py-1 me-1 mb-1" style="font-size: 0.8rem;">${a}</span>`)
                    .join('');

                html += `
        <div class="card-header">
            <h5 class="mb-0">${group.name} ${attributes}</h5>
        </div>
    `;

                html += `<div class="card-body"><div class="row g-3">
                <p class="text-muted mb-0">${group.short_description || ''}</p>
            `;

                group.product_options.forEach(opt => {
                    let priceBlock = '';
                    if (opt.price) {
                        priceBlock = `
                <div class="text-nowrap">
                    <span class="disk-price text-success fw-bold">${opt.prise_string}</span>
                    <span class="text-muted" style="font-size:0.8rem;"> / {{__('Product.puqProxmox.mo')}}</span>
                </div>`;
                    }

                    html += `
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card additional-disk-widget p-2 cursor-pointer position-relative"
                 data-uuid="${opt.uuid}" data-group-uuid="${group.uuid}" data-price="${opt.price}"
style="cursor:pointer;">
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        <div class="disk-icon rounded d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                            <i class="fas fa-hdd fa-2x text-secondary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${opt.key}</div>
                        ${priceBlock}
                    </div>
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
        </div>`;
                });

                html += `</div></div></div>`;
                return html;
            }

            function attachDiskSelection() {
                $('.additional-disk-widget').on('click', function () {
                    const optionUuid = $(this).data('uuid');
                    const groupUuid = $(this).data('group-uuid');

                    $(`.additional-disk-widget[data-group-uuid="${groupUuid}"] i.fa-check`).addClass('d-none');
                    $(`.additional-disk-widget[data-group-uuid="${groupUuid}"]`).removeClass('border-3 border-success fw-bold');

                    $(this).addClass('border-3 border-success fw-bold');
                    $(this).find('i.fa-check').removeClass('d-none');

                    $('#mp_size_product_option_uuid').val(optionUuid);

                    updateOrderSummary();
                });

                $('.additional-disk-widget').each(function () {
                    const $first = $(this).closest('.row').find('.additional-disk-widget').first();

                    $first.addClass('border-3 border-success fw-bold');
                    $first.find('i.fa-check').removeClass('d-none');
                    $('#mp_size_product_option_uuid').val($first.data('uuid'));
                });
            }


            function renderAdditionOptions(additionOptions) {
                if (!additionOptions || additionOptions.length === 0) return '';

                let selectsHtml = additionOptions.map(group => {
                    const selectId = `option_${group.uuid}`;

                    const optionsHtml = group.product_options.map((opt, i) => {
                        let price = opt.price ? `(${opt.prise_string} / {{__('Product.puqProxmox.mo')}})` : '';
                        let selected = i === 0 ? 'selected' : '';
                        return `<option value="${opt.uuid}" ${selected}>${opt.key} ${price}</option>`;
                    }).join('');

                    return `
<div class="col-12 col-sm-6 col-md-6 col-lg-3 mb-3">
    <label for="${selectId}" class="form-label fw-bold">${group.name}</label>
    <select id="${selectId}" name="${group.uuid}" class="form-select product-option">
        ${optionsHtml}
    </select>
</div>`;
                }).join('');

                return `
<div class="card mb-2" id="addition_options_block">
    <div class="card-header">
        <h5 class="mb-0">{{__('Product.puqProxmox.Addition Options')}}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            ${selectsHtml}
        </div>
    </div>
</div>`;
            }

            function renderBackupCountGroup(group) {
                let html = `<div class="card mb-2" id="backup_group_${group.uuid}">`;
                let attributes = group.attributes
                    .map(a => `<span class="badge bg-primary px-1 py-1 me-1 mb-1" style="font-size: 0.8rem;">${a}</span>`)
                    .join('');

                html += `
        <div class="card-header">
            <h5 class="mb-0">${group.name} ${attributes}</h5>
        </div>
    `;

                html += `<div class="card-body"><div class="row g-3">
        <p class="text-muted mb-0">${group.short_description || ''}</p>
    `;

                group.product_options.forEach(opt => {
                    let priceBlock = '';
                    if (opt.price) {
                        priceBlock = `
            <div class="text-nowrap">
                <span class="disk-price text-success fw-bold">${opt.prise_string}</span>
                <span class="text-muted" style="font-size:0.8rem;"> / {{__('Product.puqProxmox.mo')}}</span>
            </div>`;
                    }

                    html += `
        <div class="col-12 col-sm-6 col-md-6 col-lg-3">
            <div class="card backup-count-widget p-2 cursor-pointer position-relative"
                 data-uuid="${opt.uuid}" data-group-uuid="${group.uuid}" data-price="${opt.price}"
                 style="cursor:pointer;">
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        <div class="disk-icon rounded d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                            <i class="fas fa-database fa-2x text-secondary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${opt.key}</div>
                        ${priceBlock}
                    </div>
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
        </div>`;
                });

                html += `</div></div></div>`;
                return html;
            }

            function attachBackupCountSelection() {
                $('.backup-count-widget').on('click', function () {
                    const optionUuid = $(this).data('uuid');
                    const groupUuid = $(this).data('group-uuid');

                    $(`.backup-count-widget[data-group-uuid="${groupUuid}"] i.fa-check`).addClass('d-none');
                    $(`.backup-count-widget[data-group-uuid="${groupUuid}"]`).removeClass('border-3 border-success fw-bold');

                    $(this).addClass('border-3 border-success fw-bold');
                    $(this).find('i.fa-check').removeClass('d-none');

                    $('#backup_count_product_option_uuid').val(optionUuid);

                    updateOrderSummary();
                });

                $('.backup-count-widget').each(function () {
                    const $first = $(this).closest('.row').find('.backup-count-widget').first();

                    $first.addClass('border-3 border-success fw-bold');
                    $first.find('i.fa-check').removeClass('d-none');
                    $('#backup_count_product_option_uuid').val($first.data('uuid'));
                });
            }

            function updateProductOptions() {
                blockUI('productOptions');

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
                        let networkGroups = [];
                        let additionOptions = [];


                        response.data.forEach(group => {
                            switch (group.type) {
                                case 'location':
                                    renderLocationGroup(group);
                                    break;
                                case 'image':
                                    renderImageGroup(group);
                                    break;
                                case 'cpu':
                                    html += renderCpuGroup(group);
                                    break;
                                case 'ram':
                                    html += renderRamGroup(group);
                                    break;
                                case 'disk':
                                    html += renderDiskGroup(group);
                                    break;
                                case 'backup_count':
                                    html += renderBackupCountGroup(group);
                                    break;
                                default:
                                    additionOptions.push(group);
                                    break;
                            }
                        });

                        if (networkGroups.length > 0) {
                            html += renderNetworkGroupAll(networkGroups);
                        }

                        if (additionOptions.length > 0) {
                            html += renderAdditionOptions(additionOptions);
                        }


                        $optionsContainer.html(html);

                        response.data.forEach(group => {
                            switch (group.type) {
                                case 'cpu':
                                    attachCpuSelection();
                                    break;
                                case 'ram':
                                    attachRamSelection();
                                    break;
                                case 'system_disk':
                                    attachRootfsSelection();
                                    break;
                                case 'disk':
                                    attachDiskSelection();
                                    break;
                                case 'backup_count':
                                    attachBackupCountSelection();
                                    break;
                            }
                        });

                        if (networkGroups.length > 0) {
                            attachNetworkSelectionAll();
                        }

                        $(".product-option").on('change', function () {
                            updateOrderSummary();
                        });
                    }

                    unblockUI('productOptions');
                    updateOrderSummary();
                }).catch(error => {
                    console.error(error);
                    unblockUI('productOptions');
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

                const $form = $("#orderForm");
                const formData = serializeForm($form);

                PUQajax(
                    '{{ route('client.api.cloud.group.order.get', ['uuid' => $product_group->uuid, 'method' => 'CalculateSummary']) }}', formData,
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
    <div class="mb-2 p-2 bg-light border rounded">
        <div class="text-secondary small mb-1"><strong>${summary.product_group}</strong></div>
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold">${summary.product_name}</span>
            <span class="text-nowrap fw-bold text-dark">${summary.price}</span>
        </div>
    </div>
`;

                summary.options.forEach(opt => {
                    html += `
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span>
                <span class="text-secondary fw-bold">${opt.group_label}: </span>
                <span class="ms-2">${opt.label}</span>
            </span>
            <span class="text-nowrap">${opt.price}</span>
        </div>
    `;
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
                checkButtonState();
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

            const requiredFields = [
                "selectedLocation",
                "selected_product_uuid",
                "selected_product_price_uuid",
                "location_product_option_uuid",
            ];

            function checkButtonState() {
                const allRequiredFilled = requiredFields.every(id => {
                    const val = document.getElementById(id)?.value;
                    return val && val.trim() !== "";
                });


                document.getElementById("btnCompleteProductConfig").disabled = !(allRequiredFilled);
            }

            clearSummary();
            checkButtonState();
            loadLocations();
        });

    </script>
@endsection
