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
                                                <i class="fas fa-tasks"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.Module Log')}}</span>
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
                                    {{__('main.Module Log')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <div class="d-flex align-items-center justify-content-between">
                    <button id="deleteAll" type="button"
                            class="mb-0 me-2 btn-icon btn-outline-2x btn btn-outline-danger">
                        <i class="fa fa-trash"></i> {{ __('main.Delete All') }}
                    </button>

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
            <table style="width: 100%;" id="moduleLogs" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Date')}}</th>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Action')}}</th>
                    <th>{{__('main.Level')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Date')}}</th>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Action')}}</th>
                    <th>{{__('main.Level')}}</th>
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

            var hideMe = 0;
            var start = moment().startOf("hour").add(-24, "hours");
            var end = moment().startOf("hour").add(24, "hours");

            function DataTableAddData() {
                return {
                    start_date: start.format('DD-MM-YYYY HH:mm:ss'),
                    end_date: end.format('DD-MM-YYYY HH:mm:ss'),
                    hide_me: hideMe
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

            var tableId = '#moduleLogs';
            var ajaxUrl = '{{ route('admin.api.module_logs.get') }}';
            var columnsConfig = [
                {
                    data: "created_at", name: "created_at",
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">` + formatDateWithoutTimezone(row.created_at) + `</div>
                            <div class="widget-subheading">` + row.type + `</div>
                        </div>
                     </div>
                </div>`;
                    }
                },
                {data: "name", name: "name"},
                {data: "action", name: "action"},
                {data: "level", name: "level",
                render: function (data, type, row) {
                    return `<div class="badge ${getLogLevelColor(row.level)}">${row.level}</div>`;
                }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.get) {
                            btn = btn + renderViewButton(row.urls.get);
                        }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[0, 'desc']]
            });

            initializeAutoReloadTable({
                autoReloadCheckboxId: '#autoReloadTable',
                intervalSelectId: '#intervalSelect',
                circleProgressId: '#circleProgress',
                progressColor: '#3f6ad8',
                dataTable: $dataTable
            });

            cb(start, end);

            $dataTable.on('click', 'button.view-btn', function (e) {
                e.preventDefault();
                const modelUrl = $(this).data('model-url');
                PUQajax(modelUrl, null, 500, $(this), 'GET', null)
                    .then(function (response) {
                        displayModalData(response.data);
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });


            function displayModalData(data) {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $('#universalModal #modalSaveButton').remove();
                $('#universalModal .modal-dialog').css({
                    'max-width': '90%',
                    'width': '90%'
                });
                $modalTitle.text(translate('Log Detail'));

                const formattedData = `<div class="row">
        <div class="col"><strong>${translate('Type')}:</strong> ${data.type}</div>
        <div class="col"><strong>${translate('Name')}:</strong> ${data.name}</div>
        <div class="col"><strong>${translate('Action')}:</strong> ${data.action}</div>
        <div class="col"><div class="badge ${getLogLevelColor(data.level)}">${data.level}</div></div>
        <div class="col"><strong>${translate('Created At')}:</strong> ${formatDateWithoutTimezone(data.created_at)}</p>
        </div>
        <strong>${translate('Request')}:</strong>
        <pre>${data.request}</pre>

        <strong>${translate('Response')}:</strong>
        <pre>${data.response}</pre>
    `;
                $modalBody.html(formattedData);

                $('#universalModal').modal('show');
            }


            $('#deleteAll').on('click', function (event) {
                event.preventDefault();
                if (confirm(translate('Are you sure you want to delete ALL record?'))) {
                    PUQajax('{{ route('admin.api.module_logs.delete_all.delete') }}', null, 3000, $(this), 'DELETE')
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
