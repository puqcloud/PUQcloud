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
                                                <i class="fa fa-cubes"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Modules')}}</span>
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
                                    {{__('main.Modules')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="modules"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Type')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Type')}}</th>
                    <th>{{__('main.Description')}}</th>
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

            var $tableId = $('#modules');
            var ajaxUrl = '{{ route('admin.api.add_ons.modules.get') }}';
            var columnsConfig = [
                {
                    data: 'name',
                    render: function (data, type, row) {
                        var logo = row.module_data.logo
                            ? `<img src="${row.module_data.logo}" alt="${data}">`
                            : '';

                        return `<div class="widget-content p-0">
            <div class="widget-content-wrapper">
                <div class="widget-content-left me-3">
                    <div class="avatar-icon-wrapper">
                        <div class="badge badge-bottom"></div>
                        <div class="avatar-icon rounded">
                            ${logo}
                        </div>
                    </div>
                </div>
                <div class="widget-content-left">
                    <div class="widget-heading">${row.module_data.name ?? ''}</div>
                    <div class="widget-subheading">${row.uuid}</div>
                </div>
            </div>
        </div>`;
                    }
                },

                {
                    data: 'status',
                    render: function (data, type, row) {
                        const statusHtml = `
                            <div style="margin-left: 10px;" class="badge bg-${getModuleStatusLabelClass(row.status)}">
                                ${row.status}
                            </div>
                            `;

                        let versionHtml;
                        if (row.version === row.module_data.version) {
                            versionHtml = `<span>${row.version}</span>`;
                        } else {
                            versionHtml = `
                                <span style="color: red; font-weight: bold;">
                                    ${row.version} → ${row.module_data.version}
                                </span>
                            `;
                        }
                        return `
                                <div>
                                    ${statusHtml}
                                <br/>
                                    ${versionHtml}
                                </div>
                                `;
                    }
                },
                {data: "type", name: "type"},
                {
                    data: 'module_data',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.module_data.description ?? ''}</div>
                            <div class="widget-subheading"><b>${translate('Author')}: </b> ${row.module_data.author ?? ''}</div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.activate) {
                            btn = btn + renderActivateButton(row.urls.activate);
                        }

                        if (row.urls.deactivate) {
                            btn = btn + renderDeactivateButton(row.urls.deactivate);
                        }

                        if (row.urls.update) {
                            btn = btn + renderUpdateButton(row.urls.update);
                        }

                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }

                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            $tableId.on('click', 'button.activate-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to activate?'))) {
                    PUQajax(modelUrl, null, 3000, $(this), 'POST')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $tableId.on('click', 'button.deactivate-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to deactivate?'))) {
                    PUQajax(modelUrl, null, 3000, $(this), 'POST')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $tableId.on('click', 'button.update-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to update?'))) {
                    PUQajax(modelUrl, null, 3000, $(this), 'POST')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $tableId.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to delete?'))) {
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
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
