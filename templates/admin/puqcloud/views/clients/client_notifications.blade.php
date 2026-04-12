@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')

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
            <table style="width: 100%;" id="clientNotifications"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Date')}}</th>
                    <th>{{__('main.Recipient')}}</th>
                    <th>{{__('main.Notification')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Date')}}</th>
                    <th>{{__('main.Recipient')}}</th>
                    <th>{{__('main.Notification')}}</th>
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

            var start = moment().startOf("hour").add(-30, "days");
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
                    $dataTable.ajax.reload(null, false);
                }
            );

            var tableId = '#clientNotifications';
            var ajaxUrl = '{{ route('admin.api.client.notifications.get', $uuid) }}';
            var columnsConfig = [
                {
                    data: "created_at", name: "created_at",
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">` + formatDateWithoutTimezone(row.created_at) + `</div>
                        </div>
                     </div>
                </div>`;
                    }
                },
                {
                    data: "model_type",
                    render: function (data, type, row) {
                        if (row.model_data && Object.keys(row.model_data).length > 0) {
                            return `<div class="widget-content p-0">
                <div class="widget-content-wrapper">
                    <div class="widget-content-left me-3">
                        <div class="avatar-icon-wrapper">
                            <div class="badge badge-bottom"></div>
                            <div class="avatar-icon rounded">
                                <img src="${row.model_data.gravatar}" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="widget-content-left">
                        <div class="widget-heading">${row.model_data.firstname} ${row.model_data.lastname}</div>
                        <div class="widget-subheading">${row.model_data.email}</div>
                    </div>
                </div>
            </div>`;
                        } else {
                            return `<div class="widget-content p-0">
                <div class="widget-content-wrapper">
                    <div class="widget-content-left">
                        <div class="widget-heading">UUID: ${row.model_uuid}</div>
                    </div>
                </div>
            </div>`;
                        }
                    }
                },
                {
                    data: "text_mini", name: "text_mini",
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">` + row.subject + `</div>
                            <div class="widget-subheading">` + linkify(row.text_mini) + `</div>
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
                        if (row.urls.get) {
                            btn = btn + renderViewButton(row.urls.get);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[0, 'desc']]
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
                $modalTitle.text(data.subject);

                const formattedData = `
<div class="row">
    <div class="col">${linkify(data.text_mini)}</div>
    <div id="html_preview" class="border p-3" style="position: sticky; top: 100px; background-color: #fff;">${linkify(data.layout)}</div>
</div>
    `;
                $modalBody.html(formattedData);
                $('#universalModal').modal('show');
            }

        });
    </script>
@endsection
