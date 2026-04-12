@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        .product-section-card .card-header {
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.04), transparent);
            border-bottom: 1px solid rgba(0, 0, 0, 0.075);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-section-card .card-header i {
            color: #0d6efd;
        }

        .product-section-card .card-header h5 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .toggle-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 0.5rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .toggle-card:hover {
            border-color: rgba(13, 110, 253, 0.3);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .toggle-card .form-check {
            margin: 0;
            padding: 0;
            min-height: 0;
        }

        .toggle-card .form-check-input {
            width: 2.75em;
            height: 1.5em;
            margin: 0;
            cursor: pointer;
            flex-shrink: 0;
        }

        .toggle-card .toggle-info {
            flex: 1;
            min-width: 0;
        }

        .toggle-card .toggle-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #212529;
        }

        .toggle-card .toggle-hint {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.1rem;
        }

        .toggle-card.danger-toggle .form-check-input:checked {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        /* Expandable toggle card (Stock Control with embedded quantity input) */
        .toggle-card-expandable {
            flex-direction: column;
            align-items: stretch;
            gap: 0;
            padding: 0;
            overflow: hidden;
        }

        .toggle-card-expandable .toggle-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
        }

        .toggle-card-expandable .toggle-body {
            display: none;
            padding: 0.75rem 1rem;
            background: #f8f9fc;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .toggle-card-expandable.is-active {
            border-color: rgba(13, 110, 253, 0.35);
            box-shadow: 0 2px 10px rgba(13, 110, 253, 0.08);
        }

        .toggle-card-expandable.is-active .toggle-body {
            display: block;
        }

        .toggle-card-expandable .toggle-body .input-group-text {
            background: #fff;
        }
    </style>
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('products-management'))
        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.products.product_header')

    <form id="product" class="mx-auto" novalidate="novalidate">

        {{-- Basic Information --}}
        <div class="card mb-3 product-section-card">
            <div class="card-header">
                <i class="fa fa-cube"></i>
                <h5>{{__('main.Basic Information')}}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="key">{{__('main.Key')}}</label>
                        <input type="text" class="form-control input-mask-trigger"
                               id="key" name="key" placeholder="{{__('main.Key')}}">
                        <small class="form-text text-muted">{{__('main.Internal identifier')}}</small>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label" for="cancellation_delay_hours">{{__('main.Cancellation Delay')}}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-hourglass-half"></i></span>
                            <input class="form-control input-mask-trigger"
                                   id="cancellation_delay_hours"
                                   name="cancellation_delay_hours"
                                   data-inputmask="'alias': 'integer', 'groupSeparator': ',', 'autoGroup': true" im-insert="true"
                                   inputmode="numeric"
                                   style="text-align: right;">
                            <span class="input-group-text text-muted small">{{__('main.Hours')}}</span>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label" for="termination_delay_hours">{{__('main.Termination Delay')}}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-stopwatch"></i></span>
                            <input class="form-control input-mask-trigger"
                                   id="termination_delay_hours"
                                   name="termination_delay_hours"
                                   data-inputmask="'alias': 'integer', 'groupSeparator': ',', 'autoGroup': true" im-insert="true"
                                   inputmode="numeric"
                                   style="text-align: right;">
                            <span class="input-group-text text-muted small">{{__('main.Hours')}}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Billing Configuration --}}
        <div class="card mb-3 product-section-card">
            <div class="card-header">
                <i class="fa fa-credit-card"></i>
                <h5>{{__('main.Billing Configuration')}}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="billing_mode">{{__('main.Billing Mode')}}</label>
                        <select class="form-select" id="billing_mode" name="billing_mode">
                            <option value="prepaid">{{__('main.Prepaid')}}</option>
                            <option value="invoice">{{__('main.Invoice')}}</option>
                            <option value="both">{{__('main.Both')}}</option>
                        </select>
                        <small class="form-text text-muted">{{__('main.How the product is billed')}}</small>
                    </div>

                    <div class="col-12 col-md-8">
                        <label class="form-label d-block">&nbsp;</label>
                        <div class="row g-2">
                            <div class="col-12 col-sm-6 col-lg-4" id="hourly_billing_wrap">
                                <div class="toggle-card">
                                    <div class="toggle-info">
                                        <div class="toggle-label">{{__('main.Hourly Billing')}}</div>
                                        <div class="toggle-hint">{{__('main.Prepaid services billed by hour')}}</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="hourly_billing" name="hourly_billing" value="1">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="toggle-card">
                                    <div class="toggle-info">
                                        <div class="toggle-label">{{__('main.Convert Price')}}</div>
                                        <div class="toggle-hint">{{__('main.Auto-convert to other currencies')}}</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="convert_price" name="convert_price" value="1">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="toggle-card">
                                    <div class="toggle-info">
                                        <div class="toggle-label">{{__('main.Allow Idle')}}</div>
                                        <div class="toggle-hint">{{__('main.Reduced-rate idle state')}}</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="allow_idle" name="allow_idle" value="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Upgrade / Downgrade Rules --}}
        <div class="card mb-3 product-section-card">
            <div class="card-header">
                <i class="fa fa-exchange-alt"></i>
                <h5>{{__('main.Upgrade and Downgrade')}}</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="toggle-card">
                            <div class="toggle-info">
                                <div class="toggle-label">{{__('main.Allow Upgrade')}}</div>
                                <div class="toggle-hint">{{__('main.Clients can upgrade to this product')}}</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="allow_upgrade" name="allow_upgrade" value="1">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="toggle-card">
                            <div class="toggle-info">
                                <div class="toggle-label">{{__('main.Allow Downgrade')}}</div>
                                <div class="toggle-hint">{{__('main.Clients can downgrade to this product')}}</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="allow_downgrade" name="allow_downgrade" value="1">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="toggle-card">
                            <div class="toggle-info">
                                <div class="toggle-label">{{__('main.Allow Proration')}}</div>
                                <div class="toggle-hint">{{__('main.Allow billing date alignment')}}</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="allow_proration_alignment" name="allow_proration_alignment" value="1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inventory & Visibility --}}
        <div class="card mb-3 product-section-card">
            <div class="card-header">
                <i class="fa fa-boxes"></i>
                <h5>{{__('main.Inventory and Visibility')}}</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    {{-- Stock Control: expandable card — quantity input appears inside when enabled --}}
                    <div class="col-12 col-lg-6">
                        <div class="toggle-card toggle-card-expandable" id="stock_control_card">
                            <div class="toggle-head">
                                <div class="toggle-info">
                                    <div class="toggle-label">{{__('main.Stock Control')}}</div>
                                    <div class="toggle-hint">{{__('main.Limit total active services')}}</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           id="stock_control" name="stock_control" value="1">
                                </div>
                            </div>
                            <div class="toggle-body">
                                <label class="form-label small text-muted mb-1" for="quantity">{{__('main.Quantity')}}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fa fa-cubes"></i></span>
                                    <input id="quantity" name="quantity" type="number" min="0" step="1"
                                           class="form-control" placeholder="0">
                                    <span class="input-group-text text-muted small">{{__('main.units')}}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <div class="toggle-card danger-toggle">
                                    <div class="toggle-info">
                                        <div class="toggle-label">{{__('main.Hidden')}}</div>
                                        <div class="toggle-hint">{{__('main.Hide from client area')}}</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="hidden" name="hidden" value="1">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <div class="toggle-card danger-toggle">
                                    <div class="toggle-info">
                                        <div class="toggle-label">{{__('main.Retired')}}</div>
                                        <div class="toggle-hint">{{__('main.No new orders accepted')}}</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="retired" name="retired" value="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            $(".input-mask-trigger").inputmask();

            function loadFormData() {
                blockUI('product');
                const $form = $('#product');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.product.get', $uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if (!$element.length) return;

                            if ($element.is(':checkbox')) {
                                $element.prop('checked', !!value).trigger('change');
                                return;
                            }

                            $element.val(value);
                        });

                        toggleBillingModeFields();
                        toggleStockQuantity();

                        unblockUI('product');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('product');
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#product");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.product.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function () {
                        loadFormData();
                    });
            });

            loadFormData();

            // Hide Hourly Billing when billing mode is invoice/both (only makes sense for prepaid).
            function toggleBillingModeFields() {
                const mode = $('#billing_mode').val();
                if (mode === 'invoice' || mode === 'both') {
                    $('#hourly_billing_wrap').hide();
                } else {
                    $('#hourly_billing_wrap').show();
                }
            }

            // Expand the Stock Control card to reveal the Quantity input when enabled.
            function toggleStockQuantity() {
                $('#stock_control_card').toggleClass('is-active', $('#stock_control').is(':checked'));
            }

            $('#billing_mode').on('change', toggleBillingModeFields);
            $('#stock_control').on('change', toggleStockQuantity);
        });
    </script>
@endsection
