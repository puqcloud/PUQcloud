@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')
    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-building"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Home Companies')}}</span>
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
                                    {{__('main.Home Companies')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('finance-create'))
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
            <table style="width: 100%;" id="home_companies" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Logo')}}</th>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Company Name')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Logo')}}</th>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Company Name')}}</th>
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

            var $tableId = $('#home_companies');
            var ajaxUrl = '{{ route('admin.api.home_companies.get') }}';
            var columnsConfig = [
                {
                    data: "images",
                    render: function (data, type, row) {
                        if (row.images && row.images.logo) {
                            return `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                    <img src="${row.images.logo}" alt="logo" style="max-height: 32px;">
                </div>
            `;
                        }
                        return '';
                    }
                },
                {
                    data: "name", name: "name",
                    render: function (data, type, row) {
                        return renderStatus(!row.default) + ' ' + data;
                    }
                },
                {
                    data: 'company_name',
                    name: 'company_name'
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.web_edit) {
                            btn = btn + renderEditButton(row.urls.web_edit);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[0, 'desc']]
            });

            function DataTableAddData() {
                return {};
            }

            $('#create').on('click', function () {
                const modelUrl = $(this).data('model-url');
                const $modal = $('#universalModal');
                const $modalTitle = $modal.find('.modal-title');
                const $modalBody = $modal.find('.modal-body');
                const $modalSaveButton = $('#modalSaveButton');

                $modalSaveButton.data('modelUrl', modelUrl);
                $modalTitle.text(translate('Create'));

                const formHtml = `
        <form id="createForm" class="mx-auto">
            <div class="row">
                <div class="col-12 mb-3">
                    <label for="name" class="form-label">${translate('Name')}</label>
                    <input id="name" name="name" type="text" class="form-control">
                </div>
            </div>
        </form>
    `;

                $modalBody.html(formHtml);
                $('.input-mask-trigger').inputmask();
                $('#universalModal').modal('show');

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.home_company.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });
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

            $tableId.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });


        });
    </script>
@endsection
