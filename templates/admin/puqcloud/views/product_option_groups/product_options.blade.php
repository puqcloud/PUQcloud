@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('product-options-management'))
        <button type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                id="create">
            <i class="fa fa-plus"></i>
            {{__('main.Create')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.product_option_groups.product_option_group_header')

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="product_option" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Icon')}}</th>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Visible')}}</th>
                    <th>{{__('main.Order')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Icon')}}</th>
                    <th>{{__('main.Key')}}</th>
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
            var $tableId = $('#product_option');
            var ajaxUrl = '{{ route('admin.api.product_option_group.product_options.get', $uuid) }}';
            var columnsConfig = [
                {
                    data: "images",
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
                            btn += renderEditLink(row.urls.web_edit);
                        }
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
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

            $tableId.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to delete this record?'))) {
                    PUQajax(modelUrl, null, 3000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $('#create').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Create'));

                var formHtml = `
                <form id="createForm" class="col-md-10 mx-auto">
                    <div class="mb-3">
                        <label class="form-label" for="key">${translate('Key')}</label>
                        <div>
                            <input type="text" class="form-control" id="key" name="key" placeholder="${translate('Key')}">
                        </div>
                    </div>
                </form>`;

                $modalBody.html(formHtml);
                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.product_option_group.product_option.post', $uuid) }}', formData, 50, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            $tableId.on('click', '.move-up, .move-down', function () {
                var $button = $(this);
                var groupUUID = $button.data('uuid');
                var currentOrder = parseInt($button.data('order'), 10);
                var newOrder = currentOrder + ($button.hasClass('move-up') ? -1 : 1);
                var data = {
                    uuid: groupUUID,
                    new_order: newOrder
                };

                PUQajax('{{ route('admin.api.product_options.update_order.post') }}', data, 50, $button, 'POST')
                    .then(function (response) {
                        if (response.status === "success") {
                            $dataTable.ajax.reload(null, false);
                        }
                    });
            });

        });
    </script>
@endsection
