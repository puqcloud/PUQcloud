@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fa fa-server icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.Create New Service')}}
                    <div class="page-title-subheading"></div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('clients-edit'))
                    <button id="save" type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-save"></i> {{__('main.Save')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div id="createCard" class="main-card mb-3 card col-md-10 mx-auto">
        <div class="card-body">
            <form id="createForm" novalidate="novalidate">

                <div class="position-relative mb-3">
                    <label for="client_uuid" class="form-label">{{__('main.Client')}}</label>
                    <select name="client_uuid" id="client_uuid" class="form-select mb-2 form-control"></select>
                </div>
                <div id="client_data"></div>

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
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            blockUI('createCard');

            const $client_select = $("#client_uuid");
            const $product_select = $("#product_uuid");
            const $product_price_select = $("#product_price_uuid");
            const $product_data = $("#product_data");

            let client_uuid = '';
            let product_uuid = '';
            let product_price_uuid = '';

            const urlParams = new URLSearchParams(window.location.search);
            const preselectedClientUUID = urlParams.get('client_uuid');

            function resetBelowClient() {
                product_uuid = '';
                product_price_uuid = '';
                $product_select.val(null).trigger('change').prop('disabled', true);
                $product_price_select.val(null).trigger('change').prop('disabled', true);
                $product_data.empty();
            }

            function resetBelowProduct() {
                product_price_uuid = '';
                $product_price_select.val(null).trigger('change').prop('disabled', true);
                $product_data.empty();
            }

            function resetBelowPrice() {
                $product_data.empty();
            }

            if (preselectedClientUUID) {
                PUQajax(`{{ route('admin.api.clients.select.get') }}`, { q: preselectedClientUUID }, 1000, null, 'GET')
                    .then(response => {
                        const client = response.data.results.find(c => c.id === preselectedClientUUID);
                        if (client) {
                            initializeSelect2(
                                $client_select,
                                '{{route('admin.api.clients.select.get')}}',
                                client,
                                'GET',
                                1000,
                                {},
                                {},
                                () => {
                                    $client_select.prop('disabled', true);
                                    $client_select.val(client.id).trigger('change');
                                }
                            );
                        }
                        unblockUI('createCard');
                    })
                    .catch(console.error);
            } else {
                initializeSelect2($client_select, '{{route('admin.api.clients.select.get')}}', null, 'GET', 1000);
                unblockUI('createCard');
            }



            $client_select.on('change', function () {
                client_uuid = $(this).val();
                resetBelowClient();

                if (client_uuid) {
                    $product_select.prop('disabled', false);
                    initializeSelect2($product_select, '{{route('admin.api.products.select.get')}}', '', 'GET', 1000, {}, {
                        client_uuid: () => client_uuid
                    });
                }
            });

            $product_select.on('change', function () {
                product_uuid = $(this).val();
                resetBelowProduct();

                if (product_uuid) {
                    $product_price_select.prop('disabled', false);
                    initializeSelect2($product_price_select, `{{route('admin.api.product.prices.select.get')}}`, '', 'GET', 1000, {}, {
                        product_uuid: () => product_uuid,
                        client_uuid: () => client_uuid
                    });
                }
            });

            $product_price_select.on('change', function () {
                product_price_uuid = $(this).val();
                resetBelowPrice();

                if (product_price_uuid) {
                    updateProductData();
                }
            });

            function updateProductData() {
                blockUI('product_data');
                PUQajax(
                    `{{route('admin.api.product_option_groups.by_product.get')}}?product_uuid=${product_uuid}&product_price_uuid=${product_price_uuid}`,
                    {},
                    50,
                    null,
                    'GET'
                )
                    .then(response => {
                        $product_data.empty();
                        if (response.data.length > 0) {
                            let html = '';
                            response.data.forEach((group, index) => {
                                const select = `
                                <div class="position-relative mb-3">
                                    <label for="${group.uuid}" class="form-label">${group.key}</label>
                                    <select name="${group.uuid}" id="${group.uuid}" class="form-select mb-2 form-control">
                                        ${group.product_options.map(opt => `<option value="${opt.uuid}">${opt.key}</option>`).join('')}
                                    </select>
                                </div>`;
                                html += select;
                            });
                            $product_data.html(html);
                        }
                        unblockUI('product_data');
                    })
                    .catch(error => {
                        console.error(error);
                        unblockUI('product_data');
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#createForm");
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.service.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(response => {
                        // success logic
                    });
            });

        });
    </script>
@endsection

