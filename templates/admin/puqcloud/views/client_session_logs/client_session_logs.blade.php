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
                                                <i class="fas fa-address-card"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Client Sessions')}}</span>
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
                                    {{__('main.Client Sessions')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="me-3">
                        <input type="checkbox" data-toggle="toggle" data-on="{{__('main.On')}}" id="autoReloadTable"
                               name="disable" data-off="{{__('main.Off')}}" data-onstyle="success"
                               data-offstyle="danger">
                    </div>
                    <div class="me-3">
                        <select id="intervalSelect" class="form-select">
                            <option value="5">5 {{__('main.seconds')}}</option>
                            <option value="10">10 {{__('main.seconds')}}</option>
                            <option value="30">30 {{__('main.seconds')}}</option>
                            <option value="60">60 {{__('main.seconds')}}</option>
                        </select>
                    </div>
                    <div>
                        <div class="progress-circle-wrapper">
                            <div id="circleProgress" class="circle-progress d-inline-block circle-progress-primary">
                                <small><span>0%</span></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <h5 class="card-title">{{__('main.Filter')}}</h5>
            <div class="row">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="datetimes"
                           placeholder="Select time range">
                </div>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="clientSessions" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Date')}}</th>
                    <th>{{__('main.Client')}}</th>
                    <th>{{__('main.User')}}</th>
                    <th>{{__('main.URL')}}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Date')}}</th>
                    <th>{{__('main.Client')}}</th>
                    <th>{{__('main.User')}}</th>
                    <th>{{__('main.URL')}}</th>
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

            var start = moment().startOf("hour").add(-24, "hours");
            var end = moment().startOf("hour").add(24, "hours");

            function DataTableAddData() {
                return {
                    start_date: start.format('DD-MM-YYYY HH:mm:ss'),
                    end_date: end.format('DD-MM-YYYY HH:mm:ss'),
                };
            }

            function cb(start, end) {
                $("input[name='datetimes']").val(
                    start.format("DD-MM-YYYY HH:mm") + " - " + end.format("DD-MM-YYYY HH:mm")
                );
            }

            $("input[name='datetimes']").daterangepicker(
                {
                    timePicker: true,
                    startDate: start,
                    endDate: end,
                    locale: {
                        format: "DD-MM-YYYY HH:mm",
                    },
                },
                function (selectedStart, selectedEnd) {
                    start = selectedStart;
                    end = selectedEnd;
                    cb(start, end);
                    dataTable.ajax.reload(null, false);
                }
            );

            var tableId = '#clientSessions';
            var ajaxUrl = '{{ route('admin.api.client_session_logs.get') }}';
            var columnsConfig = [
                {
                    data: "created_at", name: "created_at",
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">` + formatDateWithoutTimezone(row.created_at) + `</div>
                            <div class="widget-subheading">` + row.ip_address + `</div>
                        </div>
                     </div>
                </div>`;
                    }
                },
                {
                    data: 'client',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">` + data.firstname + ` ` + data.lastname + `</div>
                            <div class="widget-subheading">` + data.company_name + `</div>
                        </div>
                     </div>
                </div>`;
                    }
                },
                {
                    data: 'user',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">` + data.firstname + ` ` + data.lastname + `</div>
                            <div class="widget-subheading">` + data.email + `</div>
                        </div>
                     </div>
                </div>`;
                    }
                },
                {
                    data: "url", name: "url",
                    render: function (data, type, row) {
                        const actionColor = getActionColor(row.action);
                        const methodColor = getMethodColor(row.method);

                        return `
                    <div class="widget-content p-0">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left">
                                <div class="widget-heading">
                                    <div class="badge ${actionColor}">` + row.action + `</div>
                                    <div class="badge ${methodColor}">` + row.method + `</div>
                                </div>
                                <div class="widget-subheading">` + data.split('?')[0] + `</div>
                            </div>
                        </div>
                    </div>`;
                    }
                },
            ];

            var dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[0, 'desc']]
            });

            initializeAutoReloadTable({
                autoReloadCheckboxId: '#autoReloadTable',
                intervalSelectId: '#intervalSelect',
                circleProgressId: '#circleProgress',
                progressColor: '#3f6ad8',
                dataTable: dataTable
            });

            cb(start, end);

        });
    </script>
@endsection
