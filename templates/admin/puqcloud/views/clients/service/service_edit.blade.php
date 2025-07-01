@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('clients-edit'))
        <div class="d-flex">
            <div style="width: 500px;">
                <select name="service" id="service" class="form-select mb-2 form-control"></select>
            </div>

            <button id="save" type="button"
                    class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                <i class="fa fa-save"></i> {{__('main.Save')}}
            </button>
        </div>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')

    <form id="serviceForm" novalidate="novalidate">
        <div class="row g-1 gy-1 d-flex align-items-stretch mb-2">

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3 d-flex">
                <div class="d-flex flex-column w-100">
                    <div class="card mb-1 widget-content">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left">
                                <div id="product_key" class="widget-heading"></div>
                                <div id="product_uuid" class="widget-subheading"></div>
                            </div>
                        </div>
                    </div>

                    <div class="main-card card flex-fill">
                        <div class="card-body">
                            <div class="position-relative mb-3">
                                <label for="uuid" class="form-label">{{__('main.UUID')}}</label>
                                <input name="uuid" id="uuid" type="text" class="form-control" disabled>
                            </div>
                            <div class="position-relative mb-3">
                                <label for="admin_label" class="form-label">{{__('main.Admin Label')}}</label>
                                <input name="admin_label" id="admin_label" type="text" class="form-control">
                            </div>
                            <div class="position-relative mb-3">
                                <label for="client_label" class="form-label">{{__('main.Client Label')}}</label>
                                <input name="client_label" id="client_label" type="text" class="form-control" disabled>
                            </div>
                            <div class="position-relative mb-3">
                                <label for="client_label" class="form-label">{{__('main.Notes')}}</label>
                                <textarea name="admin_notes" id="admin_notes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3 d-flex">
                <div class="d-flex flex-column w-100">

                    <div class="main-card card mb-1">
                        <div class="card-body">

                            <div class="position-relative mb-3">
                                <label for="order_date" class="form-label">{{__('main.Order Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="order_date" id="order_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon">
                                </div>
                            </div>

                            <div class="position-relative mb-3">
                                <label for="activated_date" class="form-label">{{__('main.Activation Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="activated_date" id="activated_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon">
                                </div>
                                <small id="create_error" class="opacity-5 text-danger"></small>
                            </div>

                            <div class="position-relative mb-3">
                                <label for="suspended_date" class="form-label">{{__('main.Suspended Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="suspended_date" id="suspended_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon">
                                </div>
                                <small id="suspended_reason" class="opacity-5 text-danger"></small>
                            </div>

                            <div class="position-relative mb-3">
                                <label for="terminated_date" class="form-label">{{__('main.Terminated Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="terminated_date" id="terminated_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon">
                                </div>
                                <small id="terminated_reason" class="opacity-5 text-danger"></small>
                            </div>

                            <div class="position-relative mb-3">
                                <label for="cancelled_date" class="form-label">{{__('main.Cancelled Date')}}</label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger">
                                        <i class="fa fa-calendar-alt"></i>
                                    </div>
                                    <input name="cancelled_date" id="cancelled_date" type="text" class="form-control"
                                           data-toggle="datepicker-icon">
                                </div>
                                <small id="cancelled_reason" class="opacity-5 text-danger"></small>
                            </div>

                            <div class="position-relative mb-3">
                                <table class="table table-striped">
                                    <tbody>
                                    <tr>
                                        <td>{{__('main.Service Status')}}</td>
                                        <td>
                                            <div id="status"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{__('main.Provision Status')}}</td>
                                        <td>
                                            <div id="provision_status"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{__('main.Idle')}}</td>
                                        <td>
                                            <div id="idle"></div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="main-card card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fa fa-sliders-h me-2 text-primary"></i> {{ __('main.Service Options') }}
                            </h5>
                            <button class="btn-icon btn-shadow btn-outline-2x btn btn-outline-info"
                                    type="button"
                                    data-bs-toggle="collapse" data-bs-target="#serviceOptions"
                                    aria-expanded="false" aria-controls="serviceOptions">
                                <span class="me-1">{{ __('main.Open') }}</span>
                                <i class="fa fa-chevron-down" data-bs-toggle-icon></i>
                            </button>
                        </div>
                        <div class="collapse" id="serviceOptions">
                            <div class="card-body">
                                <div id="product_option_groups" class="row"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3 d-flex">
                <div class="main-card card flex-fill w-100">
                    <div class="card-body">

                        <div class="position-relative mb-3">
                            <label for="billing_timestamp" class="form-label">{{__('main.Billing Timestamp')}}</label>
                            <div class="input-group">
                                <div class="input-group-text datepicker-trigger">
                                    <i class="fa fa-calendar-alt"></i>
                                </div>
                                <input name="billing_timestamp" id="billing_timestamp" type="text" class="form-control"
                                       data-toggle="datepicker-icon">
                            </div>
                        </div>

                        <table class="table table-bordered table-striped mb-0" id="price_table">
                            <thead>
                            <tr>
                                <th class="text-start" id="price_period_header"></th>
                                <th class="text-end">{{ __('main.Price') }}</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3 d-flex ">
                <div class="d-flex flex-column w-100">
                    <div class="main-card mb-3 card">
                        <div class="card-body">
                            <h5 class="card-title">{{__('main.Service Actions')}}</h5>

                            <div class="d-grid gap-2">
                                <button data-action="create"
                                        class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-success">
                                    <i class="lnr-plus-circle btn-icon-wrapper"></i>{{ __('main.Create') }}
                                </button>

                                <button data-action="idle"
                                        class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-dark">
                                    <i class="lnr-hourglass btn-icon-wrapper"></i>{{ __('main.Idle') }}
                                </button>

                                <button data-action="unidle"
                                        class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-primary">
                                    <i class="lnr-sync btn-icon-wrapper"></i>{{ __('main.Unidle') }}
                                </button>

                                <button data-action="suspend"
                                        class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-warning">
                                    <i class="pe-7s-lock btn-icon-wrapper"></i>{{ __('main.Suspend') }}
                                </button>

                                <button data-action="unsuspend"
                                        class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-info">
                                    <i class="pe-7s-unlock btn-icon-wrapper"></i>{{ __('main.Unsuspend') }}
                                </button>

                                <button data-action="change_package"
                                        class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-alternate">
                                    <i class="lnr-cog btn-icon-wrapper"></i>{{ __('main.Change Package') }}
                                </button>

                                <button data-action="terminate"
                                        class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-danger">
                                    <i class="lnr-trash btn-icon-wrapper"></i>{{ __('main.Terminate') }}
                                </button>

                                <div class="mb-2 me-2 btn-group">
                                    <button type="button" data-bs-toggle="dropdown"
                                            class="dropdown-toggle btn btn-danger">
                                        {{ __('main.Force Status') }}
                                    </button>
                                    <div class="dropdown-menu">
                                        <h6 class="dropdown-header">{{ __('main.Change Status') }}</h6>
                                        <button type="button" class="dropdown-item force-status" data-type="status"
                                                data-value="pending">Pending
                                        </button>
                                        <button type="button" class="dropdown-item force-status" data-type="status"
                                                data-value="active">Active
                                        </button>
                                        <button type="button" class="dropdown-item force-status" data-type="status"
                                                data-value="suspended">Suspended
                                        </button>
                                        <button type="button" class="dropdown-item force-status" data-type="status"
                                                data-value="terminated">Terminated
                                        </button>
                                        <button type="button" class="dropdown-item force-status" data-type="status"
                                                data-value="cancelled">Cancelled
                                        </button>
                                        <button type="button" class="dropdown-item force-status" data-type="status"
                                                data-value="fraud">Fraud
                                        </button>
                                        <button type="button" class="dropdown-item force-status" data-type="status"
                                                data-value="manual">Manual
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <h6 class="dropdown-header">{{ __('main.Change Idle') }}</h6>
                                        <button type="button" class="dropdown-item force-status" data-type="idle"
                                                data-value="1">Set Idle = true
                                        </button>
                                        <button type="button" class="dropdown-item force-status" data-type="idle"
                                                data-value="0">Set Idle = false
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="card mb-2 g-2 gy-2">
            <div class="card-body">
                <div id="module_html"></div>
            </div>
        </div>
    </form>

@endsection

@section('js')
    @parent

    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('serviceForm');

                PUQajax('{{route('admin.api.service.get',request()->get('edit'))}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        $("#product_key").html(response.data?.product?.key);

                        $("#product_uuid").html(linkify(`product:${response.data.product.uuid}`,true));

                        initializeDatePicker($("#order_date"), response.data?.order_date);

                        initializeDatePicker($("#activated_date"), response.data?.activated_date);
                        $("#create_error").html(translate(response.data?.create_error || ''));

                        initializeDatePicker($("#suspended_date"), response.data?.suspended_date);
                        $("#suspended_reason").html(translate(response.data?.suspended_reason || ''));

                        initializeDatePicker($("#terminated_date"), response.data?.terminated_date);
                        $("#terminated_reason").html(translate(response.data?.terminated_reason || ''));

                        initializeDatePicker($("#cancelled_date"), response.data?.cancelled_date);
                        $("#cancelled_reason").html(translate(response.data?.cancelled_reason || ''));

                        initializeDatePicker($("#billing_timestamp"), response.data?.billing_timestamp);

                        $("#uuid").val(response.data?.uuid);
                        $("#admin_label").val(response.data?.admin_label);
                        $("#client_label").val(response.data?.client_label);
                        $("#admin_notes").val(response.data?.admin_notes).textareaAutoSize().trigger('autosize');

                        $("#status").html(renderServiceStatus(response.data?.status));
                        $("#provision_status").html(renderServiceStatus(response.data?.provision_status));

                        $("#idle").html(idleStatus(response.data?.idle));

                        const groups = response.data?.product_option_groups;
                        const selectedOptions = response.data?.product_options || [];

                        if (groups && groups.length > 0) {
                            let html = '';

                            groups.forEach((group, index) => {
                                const selected = selectedOptions.find(opt => opt.product_option_group_uuid === group.uuid);
                                const selectedUuid = selected?.uuid;

                                const optionsHtml = group.product_options.map(opt => {
                                    const isSelected = opt.uuid === selectedUuid ? 'selected' : '';
                                    return `<option value="${opt.uuid}" ${isSelected}>${opt.key}</option>`;
                                }).join('');

                                html += `
            <div class="col-12">
                <div class="position-relative mb-3">
                    <label for="${group.uuid}" class="form-label">${group.key}</label>
                    <select name="option[${index}]" id="${group.uuid}" class="form-select mb-2 form-control">
                        ${optionsHtml}
                    </select>
                </div>
            </div>`;
                            });

                            $("#product_option_groups").html(html).closest('.card').removeClass('d-none');
                        } else {
                            $("#product_option_groups").html('').closest('.card').addClass('d-none');
                        }

                        const detailed = response.data?.price_detailed;

                        if (detailed) {
                            const currency = detailed.currency;
                            const period = detailed.period;
                            let rows = '';

                            rows += `<tr>
        <td><strong>${translate('Total')}</strong></td>
        <td class="text-end"><strong>
                                <div class="widget-chart-flex">
                                    <div class="widget-numbers">
                                        <div class="widget-chart-flex">
                                            <div class="fsize-1">
                                                <span>${detailed.total.base || "0.00"}</span>
                                                <small class="opacity-5">${currency.code}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </strong>
        </td>
    </tr>`;

                            rows += `<tr>
        <td>${translate('Service')}</td>
        <td class="text-end">
                                <div class="widget-chart-flex">
                                    <div class="widget-numbers">
                                        <div class="widget-chart-flex">
                                            <div class="fsize-1">
                                                <span>${detailed.service.base || "0.00"}</span>
                                                <small class="opacity-5">${currency.code}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        </td>
    </tr>`;

                            detailed.options.forEach(opt => {
                                const label = `${opt.product_option_group_key}/${opt.product_option_key}`;
                                rows += `<tr>
            <td>${label}</td>
            <td class="text-end">
                                <div class="widget-chart-flex">
                                    <div class="widget-numbers">
                                        <div class="widget-chart-flex">
                                            <div class="fsize-1">
                                                <span>${opt.price.base || "0.00"}</span>
                                                <small class="opacity-5">${currency.code}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
            </td>
        </tr>`;
                            });

                            $('#price_table tbody').html(rows);
                            var hourly_billing = '';

                            if (detailed.hourly_billing) {
                                hourly_billing = `
        <span class="ms-2" title="${translate('Hourly billing')}">
            <i class="fas fa-clock text-success"></i>
        </span>`;
                            }

                            $('#price_period_header').html(`
    ${translate(period)}
    <small class="text-muted">(${currency.code})</small>
    ${hourly_billing}
`);

                            $('#price_block').removeClass('d-none');
                        } else {
                            $('#price_block').addClass('d-none');
                        }


                        unblockUI('serviceForm');
                    })

                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function loadTopSelect() {
                const urlParams = new URLSearchParams(window.location.search);
                const serviceUUID = urlParams.get('edit');
                const $service_select = $("#service");
                let skipChangeEvent = true;

                if (serviceUUID) {
                    PUQajax(`{{ route('admin.api.client.services.select.get',$uuid) }}`, {term: serviceUUID}, 1000, null, 'GET')
                        .then(response => {
                            const client = response.data.results.find(c => c.id === serviceUUID);
                            if (client) {
                                initializeSelect2(
                                    $service_select,
                                    '{{route('admin.api.client.services.select.get',$uuid)}}',
                                    client,
                                    'GET',
                                    1000,
                                    {},
                                    {},
                                    () => {
                                        if ($service_select.find(`option[value="${client.id}"]`).length === 0) {
                                            const newOption = new Option(client.text, client.id, true, true);
                                            $service_select.append(newOption);
                                        }
                                        skipChangeEvent = true;
                                        $service_select.val(client.id).trigger('change');
                                        setTimeout(() => {
                                            skipChangeEvent = false;
                                        }, 100);
                                    }
                                );
                            } else {
                                skipChangeEvent = false;
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            skipChangeEvent = false;
                        });
                } else {
                    initializeSelect2($service_select, '{{route('admin.api.client.services.select.get',$uuid)}}', null, 'GET', 1000);
                    skipChangeEvent = false;
                }

                $service_select.on('change', function () {
                    if (skipChangeEvent) return;

                    const selectedId = $(this).val();
                    if (selectedId) {
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('edit', selectedId);
                        window.location.href = currentUrl.toString();
                    }
                });
            }

            function loadModuleHtml() {
                blockUI('module');
                PUQajax('{{route('admin.api.service.module.get',request()->get('edit'))}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $('#module_html').html(response.data);
                        unblockUI('module');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('module');
                    });
            }

            loadTopSelect();
            loadFormData();
            loadModuleHtml();

            $("#save").on("click", function (event) {
                const $form = $("#serviceForm");
                event.preventDefault();
                const formData = serializeForm($form);

                PUQajax('{{route('admin.api.service.put',request()->get('edit'))}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                        loadModuleHtml();
                    });
            });

            $("[data-action]").on("click", function (event) {
                event.preventDefault();

                const $form = $("#serviceForm");
                const formData = serializeForm($form);
                formData.action = $(this).data("action");

                PUQajax('{{ route('admin.api.service.action.post', request()->get('edit')) }}', formData, 10000, $(this), 'POST', $form)
                    .then(function (response) {
                        loadFormData();
                        loadModuleHtml();
                    });
            });

            $('.force-status').on('click', function () {
                const type = $(this).data('type');
                const value = $(this).data('value');

                PUQajax("{{ route('admin.api.service.status.put', request()->get('edit')) }}", {
                    type: type,
                    value: value
                }, 5000, $(this), 'PUT')
                    .then(function (response) {
                        loadFormData();
                        loadModuleHtml();
                    });
            });

        });
    </script>
@endsection

