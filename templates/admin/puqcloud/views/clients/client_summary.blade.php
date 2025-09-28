@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <link rel="stylesheet" href="{{ asset_admin('vendors/gridstack/dist/gridstack.css') }}">
    <script src="{{ asset_admin('vendors/gridstack/dist/gridstack-all.js') }}"></script>
@endsection

@section('before-app-main')
    <div class="ui-theme-settings">
        <button type="button" class="btn-open-options btn btn-warning">
            <i class="fa fa-cog fa-w-16 fa-spin fa-2x"></i>
        </button>
        <div class="theme-settings__inner">
            <div class="scrollbar-container ps ps--active-y">
                <div class="theme-settings__options-wrapper">
                    <div class="p-3">
                        <ul id="setting" class="list-group">
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include(config('template.admin.view') .'.clients.client_header')
    <div id="gridStack" class="grid-stack"></div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            let isLoading = true;
            let loadingWidgets = new Set(); // Отслеживаем загружающиеся виджеты
            let refreshIntervals = {};

            let grid = GridStack.init({
                resizable: {handles: 'e, se, s, sw, w'},
                draggable: {
                    handle: '.widget-header'
                },
                column: 12,
                margin: '5px',
                dragOut: true,
                acceptWidgets: true,
                float: true,
                cellHeight: 10,
                animate: false,
                columnOpts: {
                    breakpointForWindow: true,
                    breakpoints: [{w: 767, c: 1}]
                }
            });

            function loadSettings() {
                PUQajax('{{route('admin.api.client.summary.widgets.get', $uuid)}}', {}, 500, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            var $ul = $('#setting');
                            response.data.forEach(function (widget) {
                                var li = `
                            <li class="list-group-item">
                                <div class="widget-content p-0">
                                        <div class="widget-content-left me-5">
                                        <div class="widget-content-wrapper">
                                        <div class="widget-content-left">
                                            <div class="widget-heading">${widget.name}</div>
                                            <div class="widget-subheading">${widget.description}</div>
                                        </div>
                                        <div class="widget-content-right">
                                        <input data-toggle="toggle" type="checkbox"
                                            class="widget-checkbox"
                                            data-key="${widget.key}"
                                            data-width="${widget.width}"
                                            data-height="${widget.height}"
                                            data-name="${widget.name}"
                                            data-icon="${widget.icon}">
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        `;
                                $ul.append(li);
                                initializeToggleButtons();
                            });
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function initializeToggleButtons() {
                $('#setting [data-toggle="toggle"]').each(function () {
                    $(this).bootstrapToggle('destroy');
                });

                $('#setting [data-toggle="toggle"]').bootstrapToggle({
                    on: translate('On'),
                    off: translate('Off'),
                    onstyle: "success",
                    offstyle: "danger",
                    width: null,
                    height: null
                });
            }

            function loadWidgets() {
                isLoading = true;

                PUQajax('{{ route('admin.api.client.summary.dashboard.get', $uuid) }}', {}, 500, null, 'GET')
                    .then(function (response) {
                        if (response.data && response.data.length > 0) {
                            // Добавляем все виджеты в Set загружающихся
                            response.data.forEach(function (widget) {
                                loadingWidgets.add(widget.key);
                            });

                            response.data.forEach(function (widget) {
                                addWidgetToGrid(widget.key, widget.width, widget.height, widget.name, widget.icon, widget.x, widget.y, widget.autoRefresh, true);
                            });
                        } else {
                            // Если нет виджетов для загрузки, сразу завершаем загрузку
                            finishLoading();
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading widgets:', error);
                        finishLoading();
                    });
            }

            function finishLoading() {
                isLoading = false;
                loadingWidgets.clear();
                unblockUI('gridStack');
                console.log('Dashboard loading completed');
            }

            function saveDashboardState() {
                // Не сохраняем, если идет загрузка или есть загружающиеся виджеты
                if (isLoading || loadingWidgets.size > 0) {
                    console.log('Skipping save - loading in progress');
                    return;
                }

                let widgets = [];
                grid.engine.nodes.forEach(function (node) {
                    let widget = {
                        key: node.el.id,
                        width: node.w,
                        height: node.h,
                        x: node.x,
                        y: node.y,
                        autoRefresh: $(node.el).find('.auto-refresh-checkbox').is(':checked')
                    };
                    widgets.push(widget);
                });

                PUQajax('{{ route('admin.api.client.summary.dashboard.put', $uuid) }}', {widgets: widgets}, 500, null, 'PUT')
                    .then(function () {
                        console.log('Dashboard state saved');
                    })
                    .catch(function (error) {
                        console.error('Error saving dashboard state:', error);
                    });
            }

            function addWidgetToGrid(key, width, height, name, icon, x = null, y = null, autoRefresh = false, isInitialLoad = false) {
                blockUI('appMainInner');
                blockUI('setting');

                PUQajax(`{{ route('admin.api.client.summary.widget.get', $uuid) }}?key=${key}`, {}, 500, null, 'GET')
                    .then(function (response) {
                        let widget = $(`

<div class="grid-stack-item">
    <div class="grid-stack-item-content">
        <div class="scrollbar-container card ">
            <div class="widget-header d-flex justify-content-between align-items-center bg-light p-0 m-0">

                <div class="d-flex align-items-center mt-0" style="cursor: move;">
                    <div class="form-check">
                        <i class="header-icon ${icon} icon-gradient bg-plum-plate"></i>
                        <span>${name}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center mt-2">
                    <div class="form-check">
                        <input name="autoRefresh${key}" id="autoRefresh${key}" type="checkbox" class="auto-refresh-checkbox form-check-input" ${autoRefresh ? 'checked' : ''}>
                        <label for="autoRefresh${key}" class=" form-label form-check-label"><i class="header-icon fa fa-fw icon-gradient bg-plum-plate"></i></label>
                    </div>
                </div>

            </div>
            <div class="card-body m-1 p-0">
                ${response.data}
            </div>
        </div>
    </div>
</div>

            `);

                        widget.attr('id', key);

                        grid.makeWidget(widget[0]);

                        grid.update(widget[0], {
                            w: width,
                            h: height,
                            id: key,
                            x: x !== null ? x : undefined,
                            y: y !== null ? y : undefined
                        });

                        initPerfectScrollbar();
                        $('#setting [data-key="' + key + '"]').prop('checked', true);
                        initializeToggleButtons();

                        if (autoRefresh) {
                            setAutoRefresh(key);
                        }

                        // Убираем виджет из Set загружающихся
                        if (isInitialLoad) {
                            loadingWidgets.delete(key);

                            // Если это был последний загружающийся виджет, завершаем загрузку
                            if (loadingWidgets.size === 0) {
                                finishLoading();
                            }
                        } else {
                            // Для новых виджетов (не при инициализации) сохраняем состояние
                            saveDashboardState();
                        }

                        unblockUI('setting');
                        unblockUI('appMainInner');

                    })
                    .catch(function (error) {
                        console.error('Error: ', error);

                        // В случае ошибки тоже убираем из загружающихся
                        if (isInitialLoad) {
                            loadingWidgets.delete(key);
                            if (loadingWidgets.size === 0) {
                                finishLoading();
                            }
                        }

                        unblockUI('setting');
                        unblockUI('appMainInner');
                    });
            }

            function setAutoRefresh(key) {
                if (refreshIntervals[key]) {
                    clearInterval(refreshIntervals[key]);
                }
                refreshIntervals[key] = setInterval(function () {
                    updateWidget(key);
                }, 5000);
            }

            function stopAutoRefresh(key) {
                if (refreshIntervals[key]) {
                    clearInterval(refreshIntervals[key]);
                    delete refreshIntervals[key];
                }
            }

            function updateWidget(key) {
                PUQajax(`{{ route('admin.api.client.summary.widget.get', $uuid) }}?key=${key}`, {}, 500, null, 'GET')
                    .then(function (response) {
                        $('#' + key).find('.card-body').html(response.data);
                        console.log('Widget ' + key + ' refreshed');
                    })
                    .catch(function (error) {
                        console.error('Error refreshing widget:', error);
                    });
            }

            // Обработчик изменения автообновления - НЕ сохраняет состояние автоматически
            $(document).on('change', '.auto-refresh-checkbox', function () {
                let key = $(this).closest('.grid-stack-item').attr('id');

                if ($(this).is(':checked')) {
                    setAutoRefresh(key);
                } else {
                    stopAutoRefresh(key);
                }

                // Сохраняем состояние только если не идет загрузка
                if (!isLoading && loadingWidgets.size === 0) {
                    saveDashboardState();
                }
            });

            $(document).on('change', '.widget-checkbox', function () {
                let checkbox = $(this);
                let key = checkbox.data('key');
                let width = checkbox.data('width');
                let height = checkbox.data('height');
                let name = checkbox.data('name');
                let icon = checkbox.data('icon');

                if (checkbox.is(':checked')) {
                    addWidgetToGrid(key, width, height, name, icon);
                } else {
                    let widget = grid.engine.nodes.find(node => node.el.id === key);
                    if (widget) {
                        grid.removeWidget(widget.el);
                        stopAutoRefresh(key);
                        console.log('Widget removed:', key);
                    } else {
                        console.warn('Widget not found for removal:', key);
                    }
                    saveDashboardState();
                }
            });

            $(".btn-open-options").click(function () {
                $(".ui-theme-settings").toggleClass("settings-open");
            });

            // Сохраняем состояние только при изменении размера/позиции виджетов
            grid.on('change', function (event, items) {
                saveDashboardState();
            });

            loadSettings();
            loadWidgets();

        });
    </script>
@endsection

