@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('product-groups-management'))
        <button id="addProduct" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary">
            <i class="fa fa-plus"></i> {{ __('main.Add Product') }}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.product_groups.product_group_header')
    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="products" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Visible')}}</th>
                    <th>{{__('main.Order')}}</th>
                    <th>{{__('main.Stock')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Visible')}}</th>
                    <th>{{__('main.Order')}}</th>
                    <th>{{__('main.Stock')}}</th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            var $tableId = $('#products');
            var ajaxUrl = '{{ route('admin.api.product_group_products.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: "key",
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                                    <div class="widget-content-wrapper">
                                        <div class="widget-content-left">
                                            <div class="widget-heading">${row.name}</div>
                                            <div class="widget-subheading">${row.key}</div>
                                        </div>
                                    </div>
                                </div>`;
                    }
                },

                {
                    data: "hidden",
                    render: function (data) {
                        return renderStatus(data);
                    }
                },
                {
                    data: "order",
                    render: function (data, type, row) {
                        return renderOrderButtons(row);
                    }
                },
                {
                    data: "stock_control",
                    render: function (data, type, row) {
                        let stockStatus = data
                            ? `${renderStatus(!data)}<div class="badge rounded-pill bg-dark">${row.quantity}</div>`
                            : '';
                        return `
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    ${stockStatus}
                                </div>
                                `;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.web_edit) {
                            btn += renderEditButton(row.urls.web_edit);
                        }
                        if (row.urls.delete) {
                            btn += renderDisconnectButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                "paging": false,
                "searching": false,
                "ordering": false,
            });

            function DataTableAddData() {
                return {};
            }


            $('#addProduct').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Add Product'));

                var formHtml = `
                <form id="addProductForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="product_uuid" class="form-label">${translate('Product')}</label>
                            <select name="product_uuid" id="product_uuid" class="form-select mb-2 form-control"></select>
                        </div>
                    </div>
                </div>
                </form>`;

                $modalBody.html(formHtml);
                var $form = $('#addProductForm');

                var $elementProduct = $form.find('[name="product_uuid"]');
                initializeSelect2($elementProduct, '{{route('admin.api.product_group_products.select.get',$uuid)}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#addProductForm').length) {
                    var $form = $('#addProductForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.product_group_product.post',$uuid) }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            $tableId.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                var url = $(this).data('model-url');
                window.open(url, '_blank');
            });

            $tableId.on('click', 'button.disconnect-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to unlink this product?'))) {
                    PUQajax(modelUrl, null, 3000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $tableId.on('click', '.move-up, .move-down', function () {
                var $button = $(this);
                var productUUID = $button.data('uuid');
                var currentOrder = parseInt($button.data('order'), 10);
                var newOrder = currentOrder + ($button.hasClass('move-up') ? -1 : 1);
                var data = {
                    product_uuid: productUUID,
                    new_order: newOrder
                };

                PUQajax('{{ route('admin.api.product_group_products.update_order.post',$uuid) }}', data, 500, $button, 'POST')
                    .then(function (response) {
                        if (response.status === "success") {
                            $dataTable.ajax.reload(null, false);
                        }
                    });
            });
        });
    </script>
@endsection
