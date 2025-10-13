@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('products-management'))
        <button id="addProductOptionGroup" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary">
            <i class="fa fa-plus"></i> {{ __('main.Add Option') }}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.products.product_header')
    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="productOptionGroups" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Options')}}</th>
                    <th>{{__('main.Visible')}}</th>
                    <th>{{__('main.Order')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Options')}}</th>
                    <th>{{__('main.Visible')}}</th>
                    <th>{{__('main.Order')}}</th>
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
            var $tableId = $('#productOptionGroups');
            var ajaxUrl = '{{ route('admin.api.product.product_option_groups.get',$uuid) }}';
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
                {name: "options_count", data: "options_count"},
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
                "ordering": false,
            });

            function DataTableAddData() {
                return {};
            }


            $('#addProductOptionGroup').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Add Product Option'));

                var formHtml = `
                <form id="addProductOptionGroupForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="product_option_group_uuid" class="form-label">${translate('Product Option')}</label>
                            <select name="product_option_group_uuid" id="product_option_group_uuid" class="form-select mb-2 form-control"></select>
                        </div>
                    </div>
                </div>
                </form>`;

                $modalBody.html(formHtml);
                var $form = $('#addProductOptionGroupForm');

                var $elementOption = $form.find('[name="product_option_group_uuid"]');
                initializeSelect2($elementOption, '{{route('admin.api.product.product_option_groups.select.get',$uuid)}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#addProductOptionGroupForm').length) {
                    var $form = $('#addProductOptionGroupForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.product.product_option_group.post',$uuid) }}', formData, 500, $(this), 'POST', $form)
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

                if (confirm(translate('Are you sure you want to unlink this Option?'))) {
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
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

                PUQajax('{{ route('admin.api.product.product_option_group.update_order.post',$uuid) }}', data, 500, $button, 'POST')
                    .then(function (response) {
                        if (response.status === "success") {
                            $dataTable.ajax.reload(null, false);
                        }
                    });
            });

        });
    </script>
@endsection
