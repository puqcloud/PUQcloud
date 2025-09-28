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
        <button id="addProductAttribute" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary">
            <i class="fa fa-plus"></i> {{ __('main.Add Attribute') }}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.products.product_header')
    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="productAttributes" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Icon')}}</th>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Group')}}</th>
                    <th>{{__('main.Visible')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Icon')}}</th>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Group')}}</th>
                    <th>{{__('main.Visible')}}</th>
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
            var $tableId = $('#productAttributes');
            var ajaxUrl = '{{ route('admin.api.product.product_attributes.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: "images",
                    orderable: false,
                    render: function (data, type, row) {
                        if (row.images && row.images.icon) {
                            return `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                    <img src="${row.images.icon}" alt="icon" style="max-height: 32px;">
                </div>
            `;
                        }
                        return '';
                    }
                },
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
                    data: "group_key",
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                                    <div class="widget-content-wrapper">
                                        <div class="widget-content-left">
                                            <div class="widget-heading">${row.group_name}</div>
                                            <div class="widget-subheading">${row.group_key}</div>
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

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {});

            function DataTableAddData() {
                return {};
            }


            $('#addProductAttribute').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Add Product Attribute'));

                var formHtml = `
                <form id="addProductAttributeForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="product_attribute_uuid" class="form-label">${translate('Product Attribute')}</label>
                            <select name="product_attribute_uuid" id="product_attribute_uuid" class="form-select mb-2 form-control"></select>
                        </div>
                    </div>
                </div>
                </form>`;

                $modalBody.html(formHtml);
                var $form = $('#addProductAttributeForm');

                var $elementAttribute = $form.find('[name="product_attribute_uuid"]');
                initializeSelect2($elementAttribute, '{{route('admin.api.product.product_attributes.select.get',$uuid)}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#addProductAttributeForm').length) {
                    var $form = $('#addProductAttributeForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.product.product_attribute.post',$uuid) }}', formData, 500, $(this), 'POST', $form)
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

                if (confirm(translate('Are you sure you want to unlink this Attribute?'))) {
                    PUQajax(modelUrl, null, 3000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });
        });
    </script>
@endsection
