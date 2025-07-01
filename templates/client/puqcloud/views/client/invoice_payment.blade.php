@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        .gateway-card {
            transition: 0.2s;
            border: 2px solid transparent;
        }

        .gateway-card:hover {
            background-color: #f8f9fa;
        }

        .gateway-card.border-primary {
            border-color: #0d6efd !important;
        }

        .gateway-card img {
            width: 120px;
            height: 120px;
            margin: 10px;
        }

        .gateway-card .card-body {
            display: flex;
            align-items: center;
        }

        .gateway-card .card-title {
            margin-bottom: 0;
        }

    </style>

@endsection

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fas fa-credit-card icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{ __('main.Payment') }}
                    <div class="page-title-subheading text-muted"></div>
                </div>
            </div>
            <div class="page-title-actions">
                <a href="{{ route('client.web.panel.client.invoices') }}"
                   class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
                    <i class="fa fa-arrow-left"></i> {{ __('main.Back to Invoices') }}
                </a>
            </div>
        </div>
    </div>

    <div class="container px-0">
        <div id="paymentData">
            <div class="main-card card mb-1">
                <div class="card-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                            <div class="position-relative mb-2 w-100">
                                <label for="number" class="form-label" id="type"></label>
                                <label for="number" class="form-label" id="status"></label>
                                <div class="input-group">
                                    <div class="input-group-text datepicker-trigger fw-bold">#</div>
                                    <input name="number" id="number" type="text" class="form-control" disabled>
                                </div>
                            </div>
                            <div id="credit_note_buttons" class="d-flex flex-wrap gap-2 mb-2"></div>
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-4">

                            <div class="position-relative mb-3">
                                <table class="table table-striped">
                                    <tbody>
                                    <tr>
                                        <td>{{__('main.Tax')}}</td>
                                        <td>
                                            <div id="tax"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{__('main.Net')}}</td>
                                        <td>
                                            <div id="subtotal"></div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>{{__('main.Gross')}}</td>
                                        <td>
                                            <div id="total" class="fw-bold"></div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                            <div class="position-relative mb-3">
                                <table class="table table-striped">
                                    <tbody>
                                    <tr>
                                        <td>{{__('main.Due')}}</td>
                                        <td>
                                            <div id="due_amount" class="fw-bold text-danger"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{__('main.Net Paid')}}</td>
                                        <td>
                                            <div id="paid_net_amount"></div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>{{__('main.Gross Paid')}}</td>
                                        <td>
                                            <div id="paid_gross_amount"></div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-card mb-3 card">
                <div class="card-body">
                    <div class="row" id="payment_gateways">
                        <div class="d-flex justify-content-center align-items-center" style="height: 400px;">
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="selected_gateway" id="selected_gateway">
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function setupInvoiceData() {
                blockUI('paymentData');

                PUQajax('{{route('client.api.client.invoice.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {

                        $("#number").val(response.data?.number);
                        $("#type").html(renderInvoiceType(response.data?.type));
                        $("#status").html(renderInvoiceStatus(response.data?.status));

                        $("#total").html(response.data?.total_str);
                        $("#subtotal").html(response.data?.subtotal_str);
                        $("#tax").html(response.data?.tax_str);
                        $("#paid_net_amount").html(response.data?.paid_net_amount_str);
                        $("#paid_gross_amount").html(response.data?.paid_gross_amount_str);
                        $("#due_amount").html(response.data?.due_amount_str);

                    })

                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function setupPaymentGateways() {
                PUQajax('{{ route("client.api.client.invoice.payment_gateways.get", $uuid) }}', null, 5000, null, 'GET', null)
                    .then(function (response) {
                        unblockUI('paymentData');

                        if (response.status === 'success') {
                            const $container = $('#payment_gateways').empty();

                            response.data.forEach(function (gateway) {
                                const img = gateway.img || '';
                                const card = `
                <div class="col-md-6 mb-3">
                    <div class="card border shadow-sm gateway-card"
                         data-id="${gateway.uuid}"
                         data-url="${gateway.url}"
                         style="cursor:pointer;">
                        <div class="card-body p-0">
                            <img src="${img}" alt="${gateway.name}">
                            <div>
                                <div class="widget-content-left flex2">
                                    <div class="widget-heading">${gateway.name}</div>
                                    <div class="widget-subheading opacity-7">
                                        ${gateway.description || ''}
                                    </div>
                                    <div class="payment-button-container mt-2" style="display:none;">
                                           ${renderPayNowButton(gateway.url)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
                                $container.append(card);
                            });

                            attachGatewayClickHandler();

                        } else {
                            $('#payment_gateways').html(`<div class="alert alert-warning">${translate('No payment gateways available')}</div>`);
                        }
                    })
                    .catch(function () {
                        unblockUI('paymentData');
                        $('#payment_gateways').html(`<div class="alert alert-danger">${translate('Failed to load payment gateways')}</div>`);
                    });
            }

            function attachGatewayClickHandler() {
                $('.gateway-card').on('click', function () {
                    $('.gateway-card').removeClass('border-primary');
                    $('.payment-button-container').hide();
                    $(this).addClass('border-primary');
                    $(this).find('.payment-button-container').show();
                });
            }

            $(document).on('click', '.pay-now-btn', function (e) {
                e.preventDefault();
                const $btn = $(this);
                window.lastClickedPayNowBtn = $btn;
                const getUrl = $btn.data('model-url');
                const $modalTitle = $('#universalModal .modal-title');
                const $modalBody = $('#universalModal .modal-body');
                const $modalConfirmButton = $('#modalConfirmButton');
                $modalConfirmButton.hide();

                PUQajax(getUrl, {}, 50, $btn, 'GET')
                    .then(function (res) {
                        $modalBody.html(res.data?.html || '');
                        $modalTitle.text(res.data?.name || '');
                        $('#universalModal').modal('show');
                    })
                    .catch(function (err) {
                        console.error('Error loading form data:', err);
                    });
            });

            setupInvoiceData();
            setupPaymentGateways();
        });

    </script>
@endsection
