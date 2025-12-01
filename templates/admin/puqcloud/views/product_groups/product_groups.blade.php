@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fa fa-cogs"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Product Groups')}}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav class="" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a>
                                        <i aria-hidden="true" class="fa fa-home"></i>
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.Product Groups')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('product-groups-management'))
                    <button type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                            id="create">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="product_groups" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Icon')}}</th>
                    <th>{{__('main.Key')}}</th>
                    <th>{{__('main.Visible')}}</th>
                    <th>{{__('main.Products')}}</th>
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
                    <th>{{__('main.Products')}}</th>
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
            var $tableId = $('#product_groups');
            var ajaxUrl = '{{ route('admin.api.product_groups.get') }}';
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
                        const iconClass = row.icon ?? 'lnr-diamond';
                        const isFlag = iconClass.startsWith('flag');

                        const iconHtml = isFlag
                            ? `<div class="${iconClass} large mx-auto"></div>`
                            : `<i class="${iconClass} text-info"></i>`;

                        return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left me-3">
                    <div class="widget-content-left">
                        <div class="icon-wrapper">
                            <div class="icon-wrapper-bg bg-info"></div>
                            ${iconHtml}
                        </div>
                    </div>
                </div>
                <div class="widget-content-left flex2">
                    <div class="widget-heading">${row.name}</div>
                    <div class="widget-subheading opacity-7">${row.key}</div>
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
                {name: "products_count", data: "products_count"},
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
                        if (row.urls.edit) {
                            btn += renderEditButton(row.urls.edit);
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
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $tableId.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
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

                    PUQajax('{{ route('admin.api.product_group.post') }}', formData, 50, $(this), 'POST', $form)
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

                PUQajax('{{ route('admin.api.product_groups.update_order.post') }}', data, 50, $button, 'POST')
                    .then(function (response) {
                        if (response.status === "success") {
                            $dataTable.ajax.reload(null, false);
                        }
                    });
            });
        });
    </script>
@endsection
