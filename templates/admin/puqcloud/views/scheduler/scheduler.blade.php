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
                    <i class="fa fa-calendar-alt icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.Scheduler')}}
                    <div class="page-title-subheading">
                        {{__('main.Here you can set up a schedule for all automatic system actions')}}</div>
                </div>
            </div>
            <div class="page-title-actions">

            </div>
        </div>
    </div>

    <div id="scheduler"></div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            function loadData() {
                blockUI('scheduler');

                PUQajax('{{route('admin.api.scheduler.get')}}', {}, 500, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            var $schedulerDiv = $('#scheduler');
                            $schedulerDiv.empty();

                            var groupedSchedules = {};

                            response.data.forEach(function (schedule) {
                                if (!groupedSchedules[schedule.group]) {
                                    groupedSchedules[schedule.group] = [];
                                }
                                groupedSchedules[schedule.group].push(schedule);
                            });

                            var tabsHeader = `
                        <div class="mb-3 card">
                            <div class="card-header card-header-tab-animation">
                                <ul class="nav nav-justified" id="schedulerTabs" role="tablist">
                    `;

                            var tabsContent = `
                        <div class="card-body">
                            <div class="tab-content" id="schedulerTabContent">
                    `;

                            var index = 0;
                            for (var group in groupedSchedules) {
                                var isActive = index === 0 ? 'active' : '';

                                tabsHeader += `
                            <li class="nav-item">
                                <a data-bs-toggle="tab" href="#tab-${index}" class="nav-link ${isActive}" role="tab">
                                    ${group}
                                </a>
                            </li>
                        `;

                                tabsContent += `
                            <div class="tab-pane ${isActive}" id="tab-${index}" role="tabpanel">
                                <div class="row">
                        `;

                                groupedSchedules[group].forEach(function (schedule) {
                                    var checked = schedule.disable ? '' : 'checked';

                                    var lastRun = schedule.last_run_at ? formatDateWithoutTimezone(schedule.last_run_at) : '<span class="text-danger"><i class="fa fa-times"></i></span>';
                                    var nextRun = schedule.next_run_at ? formatDateWithoutTimezone(schedule.next_run_at) : '<span class="text-danger"><i class="fa fa-times"></i></span>';

                                    tabsContent += `
                                <div class="col-lg-6 col-xl-6">
                                    <div class="card mb-3 widget-content">
                                        <div class="row">
                                            <div class="col-lg-6 col-xl-6"><div class="menu-header-title">${schedule.artisan}</div></div>
                                            <div class="col-lg-3 col-xl-3">
                                                <div class="font-weight-bold text-muted">${translate('Last Run')}:</div>
                                                <div class="text-dark">
                                                    <i class="fa fa-clock"></i> ${lastRun}
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-xl-3">
                                                <div class="font-weight-bold text-muted">${translate('Next Run')}:</div>
                                                <div class="text-dark">
                                                    <i class="fa fa-arrow-right"></i> ${nextRun}
                                                </div>
                                            </div>

                                            <div class="col-lg-12 col-xl-12"><div class="widget-subheading">${schedule.description}</div></div>
                                            <div class="col-lg-6 col-xl-6"><input type="text" class="form-control mt-2 cron-input" value="${schedule.cron}" data-uuid="${schedule.uuid}"></div>
                                            <div class="col-lg-6 col-xl-6">
                                                <span class="float-end">
                                                    <input type="checkbox" data-toggle="toggle" ${checked} data-uuid="${schedule.uuid}" class="schedule-checkbox">
                                                    <button type="button" class="btn-icon btn-icon-only btn-outline-2x btn btn-outline-success save-btn"
                                                        data-uuid="${schedule.uuid}"
                                                        data-cron_default="${schedule.cron_default}"
                                                        data-disable_default="${schedule.disable_default}"
                                                        data-disable_old="${schedule.disable}"
                                                        data-url="${schedule.urls.put}">
                                                        <i class="fa fa-save"></i>
                                                     </button>
                                                    <button type="button" class="btn-icon btn-icon-only btn-outline-2x btn btn-outline-info default-btn"
                                                        data-uuid="${schedule.uuid}"
                                                        data-cron_default="${schedule.cron_default}"
                                                        data-disable_default="${schedule.disable_default}">
                                                        <i class="fa fa-fw"></i>
                                                     </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                                });

                                tabsContent += `</div></div>`;
                                index++;
                            }

                            tabsHeader += `
                                </ul>
                            </div>
                    `;

                            tabsContent += `
                            </div>
                        </div>
                    `;

                            $schedulerDiv.append(tabsHeader + tabsContent);

                            const activeTabIndex = localStorage.getItem('activeTabIndex') || 0;
                            $(`#schedulerTabs a[href="#tab-${activeTabIndex}"]`).tab('show');

                            $('[data-toggle="toggle"]').bootstrapToggle({
                                on: translate('On'),
                                off: translate('Off'),
                                onstyle: "success",
                                offstyle: "danger"
                            });

                            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                                const targetTabIndex = $(e.target).attr('href').split('-')[1];
                                localStorage.setItem('activeTabIndex', targetTabIndex);
                                $(`${e.target.hash} [data-toggle="toggle"]`).each(function () {
                                    $(this).bootstrapToggle('destroy');
                                });
                                $(`${e.target.hash} [data-toggle="toggle"]`).bootstrapToggle({
                                    on: translate('On'),
                                    off: translate('Off'),
                                    onstyle: "success",
                                    offstyle: "danger"
                                });
                            });

                            $('.save-btn').on('click', function () {
                                var uuid = $(this).data('uuid');
                                var url = $(this).data('url');
                                var cron_default = $(this).data('cron_default');
                                var disable_old = $(this).data('disable_old');

                                var cron = $(`.cron-input[data-uuid="${uuid}"]`).val();
                                var disable = !$(`.schedule-checkbox[data-uuid="${uuid}"]`).prop('checked');

                                var scheduleData = {
                                    cron: cron,
                                    disable: disable
                                };

                                PUQajax(url, scheduleData, 1000, $(this), 'PUT')
                                    .then(function () {
                                        loadData();
                                    })
                                    .catch(function () {
                                        $(`.cron-input[data-uuid="${uuid}"]`).val(cron_default);
                                        $(`.schedule-checkbox[data-uuid="${uuid}"]`).bootstrapToggle(disable_old ? 'off' : 'on');
                                    });
                            });

                            $('.default-btn').on('click', function () {
                                var uuid = $(this).data('uuid');
                                var cron_default = $(this).data('cron_default');
                                var disable_default = $(this).data('disable_default');
                                $(`.cron-input[data-uuid="${uuid}"]`).val(cron_default);
                                $(`.schedule-checkbox[data-uuid="${uuid}"]`).bootstrapToggle(disable_default ? 'off' : 'on');
                            });
                        }

                        unblockUI('scheduler');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            loadData();
        });
    </script>
@endsection
