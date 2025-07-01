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
                                                <i class="fas fa-mail-bulk"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.My Notifications')}}</span>
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
                                    <a href="{{route('admin.web.my_account')}}">{{__('main.My Account')}}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.My Notifications')}}
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
            <table style="width: 100%;" id="notificationHistories"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Date')}}</th>
                    <th>{{__('main.Notification')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Date')}}</th>
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
                    $dataTable.ajax.reload(null, false);
                }
            );
            var tableId = '#notificationHistories';
            var ajaxUrl = '{{ route('admin.api.my_account.notifications.get') }}';
            var columnsConfig = [
                {
                    data: "created_at", name: "created_at",
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">` + formatDateWithoutTimezone(row.created_at) + `</div>
                            <div class="widget-subheading"></div>
                        </div>
                     </div>
                </div>`;
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
                $modalTitle.text(data.subject);

                const formattedData = `
<div class="row">
    <div class="col">${linkify(data.text_mini)}</div>
    <div id="html_preview" class="border p-3" style="position: sticky; top: 100px; background-color: #fff;">${data.layout}</div>
</div>
    `;
                $modalBody.html(formattedData);

                $('#universalModal').modal('show');
            }

        });
    </script>
@endsection
