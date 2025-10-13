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
                    <i class="fa fa-users icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.View/Search Clients')}}
                    <div class="page-title-subheading"></div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('clients-create'))
                    <button type="button" id="create" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="clients" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Owner')}}</th>
                    <th>{{__('main.Client')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Located')}}</th>
                    <th>{{__('main.Created')}}</th>
                    <th>{{__('main.Balance')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Owner')}}</th>
                    <th>{{__('main.Client')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Located')}}</th>
                    <th>{{__('main.Created')}}</th>
                    <th>{{__('main.Balance')}}</th>
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
            var $tableId = $('#clients');
            var ajaxUrl = '{{ route('admin.api.clients.get') }}';
            var columnsConfig = [
                {
                    data: 'owner_uuid',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left me-3">
                            <div class="avatar-icon-wrapper">
                                <div class="badge badge-bottom"></div>
                                <div class="avatar-icon rounded">
                                    <img src="${row.urls.gravatar}" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.owner_email}</div>
                            <div class="widget-subheading">${row.owner_firstname} ${row.owner_lastname}</div>
                        </div>
                    </div>
                </div>`;
                    }
                },

                {
                    data: 'uuid',
                    render: function (data, type, row) {
                        var company_name = '';
                        if(row.company_name){
                            company_name = `(${row.company_name})`;
                        }
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.firstname} ${row.lastname} ${company_name}</div>
                            <div class="widget-subheading">${row.tax_id || ''}</div>
                        </div>
                    </div>
                </div>`;
                    }
                },

                {
                    data: 'status',
                    render: function (data, type, row) {
                        return `<div class="badge bg-${getClientStatusLabelClass(data)}">${translate(data)}</div>`;
                    }
                },

                {
                    data: 'billing_address',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left me-3">
                            <div class="avatar-icon-wrapper">
                                <div class="badge badge-bottom"></div>

                                    <div class="flag ${row.billing_address.country.code} large mx-auto"></div>

                            </div>
                        </div>
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.billing_address.country.name}</div>
                            <div class="widget-subheading">${row.billing_address.city}</div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: 'created_at',
                    render: function (data, type, row) {
                        return formatDateWithoutTimezone(data);
                    }
                },
                {
                    data: 'balance',
                    render: function (data, type, row) {

                        const code = row.currency.code || '';
                        const balance = row.balance
                        const credit_limit = row.credit_limit

                        return `
            <div class="widget-chart-content ">
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex">
                            <div class="fsize-1">
                                <span>${balance}</span>
                                <small class="opacity-5">${code}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="widget-chart-flex">
                    <div class="widget-numbers">
                        <div class="widget-chart-flex">
                            <div class="fsize-1">
                                <span>${credit_limit}</span>
                                <small class="opacity-5">${code}</small>
                            </div>
                        </div>
                    </div>
                </div>
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
                            btn = btn + renderEditButton(row.urls.web_edit);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[4, 'desc']]
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
                window.location.href = '{{route('admin.web.client.create')}}';
            });

        });
    </script>

@endsection
