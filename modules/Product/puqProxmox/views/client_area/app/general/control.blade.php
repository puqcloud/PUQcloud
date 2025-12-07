<style>
    @keyframes copyBlink {
        0%   { transform: scale(1); color: inherit; }
        16%  { transform: scale(1.1); color: #198754; }
        33%  { transform: scale(1); color: inherit; }
        50%  { transform: scale(1.1); color: #198754; }
        66%  { transform: scale(1); color: inherit; }
        83%  { transform: scale(1.1); color: #198754; }
        100% { transform: scale(1); color: inherit; }
    }

    .copy-text.copied,
    .copy-text span.text-monospace.copied {
        display: inline-block;
        animation: copyBlink 0.9s ease;
        font-weight: 600;
    }
</style>

<div class="card mb-2 shadow-sm border-0" style="min-height: 260px;">
    <div class="card-header bg-light d-flex align-items-center">
        <i class="fas fa-server text-primary me-2"></i>
        <span class="fw-bold">{{ __('Product.puqProxmox.Control') }}</span>
    </div>
    <div class="card-body" id="control">

        <!-- Functions -->
        <div id="functions-section" class="mb-3" style="min-height: 50px;">
            <h6 class="fw-bold"><i class="fas fa-cogs me-1"></i> {{ __('Product.puqProxmox.Functions') }}</h6>
            <div class="d-flex gap-2 flex-wrap" id="functions-list"></div>
        </div>

        <!-- Endpoints -->
        <div id="endpoints-section" class="mb-3">
            <h6 class="fw-bold"><i class="fas fa-link me-1"></i> {{ __('Product.puqProxmox.Endpoints') }}</h6>
            <div class="list-group list-group-flush" id="endpoints-list"></div>
        </div>

        <!-- Environment Variables -->
        <div id="env-section" class="d-none">
            <h6 class="fw-bold"><i class="fas fa-key me-1"></i> {{ __('Product.puqProxmox.Environment Variables') }}</h6>
            <div class="list-group list-group-flush" id="env-list"></div>
        </div>

    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadControlData() {
                blockUI('control');

                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getAppControl']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        unblockUI('control');
                        if (response.status !== 'success') return;

                        // --- Functions ---
                        const functionsList = $('#functions-list');
                        functionsList.empty();
                        if (response.data.functions && response.data.functions.length) {
                            response.data.functions.forEach(fn => {
                                const btn = $(`
                            <button class="btn btn-sm btn-${fn.color}" title="${fn.description}">
                                ${fn.name}
                            </button>
                        `);
                                btn.on('click', function () {
                                    blockUI('control');
                                    PUQajax('{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'postAppControl']) }}',
                                        { action: fn.action }, 3000, $(this), 'POST')
                                        .then(function () {
                                            unblockUI('control');
                                            loadControlData();
                                        })
                                        .catch(function (error) {
                                            unblockUI('control');
                                            console.error('Error sending action:', error);
                                        });
                                });
                                functionsList.append(btn);
                            });
                        }

                        // --- Endpoints ---
                        const endpointsList = $('#endpoints-list');
                        endpointsList.empty();
                        response.data.endpoints.forEach(ep => {
                            const endpointItem = $(`
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center copy-text">
                                <i class="fas fa-external-link-alt text-success me-2"></i>
                                <a href="${ep.url}" target="_blank" class="text-decoration-none">${ep.url}</a>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary copy-btn"><i class="fas fa-copy"></i></button>
                        </div>
                    `);
                            endpointsList.append(endpointItem);
                        });

                        // --- Environment Variables ---
                        const envSection = $('#env-section');
                        const envList = $('#env-list');
                        envList.empty();
                        if (response.data.env_variables && response.data.env_variables.length) {
                            envSection.removeClass('d-none');
                            response.data.env_variables.forEach(ev => {
                                const buttons = [];
                                if (ev.edit_by_client) {
                                    buttons.push(`<button class="btn btn-sm btn-outline-primary edit-env-btn" data-key="${ev.key}" data-value="${ev.value}"><i class="fas fa-edit"></i></button>`);
                                }
                                buttons.push(`<button class="btn btn-sm btn-outline-secondary copy-value-btn" data-value="${ev.value}" title="Copy value"><i class="fas fa-copy"></i></button>`);

                                const envItem = $(`
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="copy-text">
                                    <span class="fw-bold">${ev.custom_name || ev.key}</span>:
                                    <span class="text-monospace">${ev.value}</span>
                                </div>
                                <div class="d-flex gap-1">
                                    ${buttons.join('')}
                                </div>
                            </div>
                        `);
                                envList.append(envItem);
                            });
                        }

                    })
                    .catch(function () {
                        unblockUI('control');
                    });
            }

            loadControlData();

            // --- Copy buttons ---
            $(document).on('click', '.copy-btn', function() {
                const elem = $(this).closest('.list-group-item').find('.copy-text');
                navigator.clipboard.writeText(elem.text().trim()).then(() => {
                    elem.removeClass('copied');
                    void elem[0].offsetWidth;
                    elem.addClass('copied');
                });
            });

            $(document).on('click', '.copy-value-btn', function() {
                const value = $(this).data('value');
                const elem = $(this).closest('.list-group-item').find('.copy-text span.text-monospace');
                navigator.clipboard.writeText(value).then(() => {
                    elem.removeClass('copied');
                    void elem[0].offsetWidth;
                    elem.addClass('copied');
                });
            });

        });
    </script>
@endsection
