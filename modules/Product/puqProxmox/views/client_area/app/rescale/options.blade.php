<div class="container-fluid px-0">
    <div class="mx-auto">
        <div class="row">
            <div class="col-md-8 mb-2">
                <div id="productOptions" style="min-height: 250px;">
                    <form id="rescaleForm" novalidate="novalidate">
                        <div id="product_data"></div>
                    </form>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="position-sticky" style="top: 2rem;">
                    <div id="orderSummary" class="card" style="min-height: 250px;">
                        <div class="card-body position-relative">
                            <h5 class="card-title">{{__('Product.puqProxmox.Summary')}}</h5>
                            <div id="orderSummaryContent" class="text-center text-muted py-5"></div>
                            <hr>
                            <div class="text-center mt-3">
                                <button type="submit" id="btnCompleteProductConfig"
                                        class="btn-wide mb-0 me-0 btn btn-outline-2x btn-outline-primary btn-lg">
                                    <i class="fas fa-cloud-upload-alt me-2"></i> {{__('Product.puqProxmox.Rescale')}}
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
            const $optionsContainer = $("#product_data");
            const $summaryContent = $("#orderSummaryContent");

            function clearSummary() {
                $summaryContent.html(`
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-info-circle fa-4x mb-3 text-info"></i><br>
                        ${translate('The total cost will appear here')}
                    </div>
                `);
            }

            function renderOptions(data) {
                let html = '';

                data.forEach(group => {
                    const currency_code = group.currency_code;
                    const groupName = group.product_option_group.name;
                    const groupId = group.product_option_group.uuid;
                    const current = group.current;
                    const currentBase = current.price && current.price ? parseFloat(current.price) : 0;
                    let options = [];
                    if (group.down && group.down.length > 0) {
                        group.down.forEach(opt => {
                            const base = opt.price && opt.price ? parseFloat(opt.price) : 0;
                            const diff = base - currentBase;
                            options.push({
                                uuid: opt.uuid,
                                name: opt.name,
                                diff: diff,
                                base: base,
                                label: `${opt.name} [${base.toFixed(2)} ${currency_code}] (${diff >= 0 ? '+' : ''}${diff.toFixed(2)} ${currency_code})`
                            });
                        });
                    }
                    options.push({
                        uuid: current.uuid,
                        name: current.name,
                        diff: 0,
                        base: currentBase,
                        label: `${current.name} [${currentBase.toFixed(2)} ${currency_code}] ⭐ {{__('Product.puqProxmox.Current')}}`,
                        selected: true
                    });
                    if (group.up && group.up.length > 0) {
                        group.up.forEach(opt => {
                            const base = opt.price && opt.price ? parseFloat(opt.price) : 0;
                            const diff = base - currentBase;
                            options.push({
                                uuid: opt.uuid,
                                name: opt.name,
                                diff: diff,
                                base: base,
                                label: `${opt.name} [${base.toFixed(2)} ${currency_code}] (${diff >= 0 ? '+' : ''}${diff.toFixed(2)} ${currency_code})`
                            });
                        });
                    }
                    const optionsHtml = options.map(o => `<option value="${o.uuid}" ${o.selected ? 'selected' : ''}>${o.label}</option>`).join('');
                    html += `
        <div class="card mb-2">
            <div class="card-header"><h5 class="mb-0">${groupName}</h5></div>
            <div class="card-body">
                <select class="form-select product-option" name="${groupId}">
                    ${optionsHtml}
                </select>
            </div>
        </div>
    `;
                });

                $optionsContainer.html(html);
                $(".product-option").on("change", function () {
                    updateOrderSummary();
                });
            }

            function updateProductOptions() {
                blockUI('productOptions');
                PUQajax(`{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getAppRescaleOptions']) }}`, {}, 50, null, 'GET')
                    .then(response => {
                        $optionsContainer.empty();
                        if (response.data.length > 0) {
                            renderOptions(response.data);
                        }
                        unblockUI('productOptions');
                        updateOrderSummary();
                    }).catch(error => {
                    unblockUI('productOptions');
                });
            }

            function updateOrderSummary() {
                blockUI('orderSummary');
                const $form = $("#rescaleForm");
                const formData = serializeForm($form);
                PUQajax('{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getAppRescaleCalculateSummary']) }}', formData, 100, null, 'GET')
                    .then(response => {
                        renderOrderSummary(response.data);
                        unblockUI('orderSummary');
                    }).catch(err => {
                    unblockUI('orderSummary');
                });
            }

            function renderOrderSummary(summary) {
                let html = ``;

                summary.options.forEach(opt => {
                    html += `
            <div class="mb-2">
                <div class="fw-bold text-secondary">${opt.group_lable}</div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">${opt.old.label}</span>
                    <span class="text-muted text-decoration-line-through">${opt.old.price}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-success">${opt.new.label}</span>
                    <span class="fw-bold text-success">${opt.new.price}</span>
                </div>
            </div>
        `;
                });

                html += `
        <hr>
        <div class="d-flex justify-content-between mb-2">
            <span>{{__('Product.puqProxmox.Switch Fee')}}:</span>
            <span>${summary.switch_fee}</span>
        </div>
        <div class="mt-4 text-center">
            <div class="fs-6 text-muted text-decoration-line-through">${summary.old_price}</div>
            <div class="fw-bold fs-2 text-primary">${summary.new_price}</div>
            <div class="text-muted">${summary.period}</div>
        </div>
    `;

                $summaryContent.html(html);
            }

            $("#btnCompleteProductConfig").on("click", function (event) {
                event.preventDefault();
                const $form = $("#rescaleForm");
                const formData = serializeForm($form);
                PUQajax('{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'postAppRescale']) }}', formData, 50, $(this), 'POST', $form)
                    .then(response => {
                        unblockUI('productOptions');
                        clearSummary();
                        updateProductOptions();
                    }).catch(err => {
                    unblockUI('productOptions');
                });
            });

            clearSummary();
            updateProductOptions();
        });
    </script>
@endsection
