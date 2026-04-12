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
                    <i class="fas fa-headset icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.Support Tickets')}}
                    <div class="page-title-subheading">
                        {{__('main.Manage all support tickets')}}</div>
                </div>
            </div>
            <div class="page-title-actions">
                <div class="d-flex align-items-center justify-content-between">
                    <a href="{{ route('admin.web.ticket_create') }}"
                       class="mb-0 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </a>

                    <div class="me-3">
                        <input type="checkbox" data-toggle="toggle" data-on="{{__('main.On')}}" id="autoReloadTable"
                               name="disable" data-off="{{__('main.Off')}}" data-onstyle="success"
                               data-offstyle="danger">
                    </div>
                    <div class="me-3">
                        <select id="intervalSelect" class="form-select">
                            <option value="10">10 {{__('main.seconds')}}</option>
                            <option value="20">20 {{__('main.seconds')}}</option>
                            <option value="30" selected>30 {{__('main.seconds')}}</option>
                            <option value="40">40 {{__('main.seconds')}}</option>
                            <option value="50">50 {{__('main.seconds')}}</option>
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
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label" for="filter_department">{{__('main.Department')}}</label>
                    <select class="form-select mb-2 form-control" id="filter_department" name="filter_department" style="width: 100%;"><option></option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filter_status">{{__('main.Status')}}</label>
                    <select class="form-select mb-2 form-control" id="filter_status" name="filter_status[]" multiple style="width: 100%;"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filter_priority">{{__('main.Priority')}}</label>
                    <select class="form-select mb-2 form-control" id="filter_priority" name="filter_priority" style="width: 100%;"><option></option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filter_assigned_to">{{__('main.Assigned To')}}</label>
                    <select class="form-select mb-2 form-control" id="filter_assigned_to" name="filter_assigned_to" style="width: 100%;"><option></option></select>
                </div>
            </div>
        </div>
    </div>

    <div id="bulkActions" class="mb-3" style="display: none;">
        <button id="bulkClose" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-danger"><i class="fas fa-times-circle me-1"></i>{{__('main.Close Selected')}}</button>
        <button id="bulkAssign" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"><i class="fas fa-user-plus me-1"></i>{{__('main.Assign Selected')}}</button>
        <button id="bulkChangeStatus" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-warning"><i class="fas fa-exchange-alt me-1"></i>{{__('main.Change Status')}}</button>
        <span id="bulkCount" class="text-muted ms-2"></span>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="tickets"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th style="width: 1%;"><input type="checkbox" id="selectAll"></th>
                    <th>#</th>
                    <th>{{__('main.Subject')}}</th>
                    <th>{{__('main.Department')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Priority')}}</th>
                    <th>{{__('main.Assigned To')}}</th>
                    <th>{{__('main.SLA')}}</th>
                    <th>{{__('main.Last Reply')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th></th>
                    <th>#</th>
                    <th>{{__('main.Subject')}}</th>
                    <th>{{__('main.Department')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th>{{__('main.Priority')}}</th>
                    <th>{{__('main.Assigned To')}}</th>
                    <th>{{__('main.SLA')}}</th>
                    <th>{{__('main.Last Reply')}}</th>
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
            var $tableId = $('#tickets');
            var ajaxUrl = '{{ route('admin.api.tickets.get') }}';

            var priorityColors = {
                'low': 'bg-secondary',
                'medium': 'bg-info',
                'high': 'bg-warning',
                'urgent': 'bg-danger'
            };

            var selectedTickets = [];

            function formatSlaTime(minutes) {
                var abs = Math.abs(minutes);
                if (abs < 60) return abs + 'm';
                var h = Math.floor(abs / 60);
                var m = abs % 60;
                return m > 0 ? h + 'h ' + m + 'm' : h + 'h';
            }

            var columnsConfig = [
                {
                    data: 'uuid',
                    orderable: false,
                    className: 'center',
                    render: function (data) {
                        return '<input type="checkbox" class="ticket-checkbox" value="' + data + '">';
                    }
                },
                {data: "number", name: "number", className: "center"},
                {
                    data: "subject",
                    name: "subject",
                    render: function (data, type, row) {
                        var html = `<strong>${data}</strong>`;
                        if (row.client_name) {
                            html += `<br><small class="text-muted">${row.client_name}</small>`;
                        }
                        if (row.email) {
                            html += ` <small class="text-muted">${row.email}</small>`;
                        }
                        return html;
                    }
                },
                {
                    data: "department_name",
                    name: "department_name",
                    render: function (data, type, row) {
                        return data ? `<span class="badge bg-primary">${data}</span>` : '';
                    }
                },
                {
                    data: "status_data",
                    name: "status_data",
                    className: "center",
                    render: function (data, type, row) {
                        if (!data) return '';
                        var color = data.color || '#6c757d';
                        return `<span class="badge" style="background-color: ${color}; color: #fff;">${data.title || ''}</span>`;
                    }
                },
                {
                    data: "priority",
                    name: "priority",
                    className: "center",
                    render: function (data, type, row) {
                        var badgeClass = priorityColors[data] || 'bg-secondary';
                        return `<span class="badge ${badgeClass}">${data ? translate(data.charAt(0).toUpperCase() + data.slice(1)) : ''}</span>`;
                    }
                },
                {
                    data: "assigned_admin_name",
                    name: "assigned_admin_name",
                    render: function (data, type, row) {
                        return data || '';
                    }
                },
                {
                    data: "sla_breach",
                    name: "sla_breach",
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        if (!data) return '';
                        if (data.breached) {
                            return '<span class="badge bg-danger">' + translate('Overdue') + ' ' + formatSlaTime(data.minutes) + '</span>';
                        }
                        return '<span class="badge bg-success">' + formatSlaTime(data.minutes) + ' ' + translate('remaining') + '</span>';
                    }
                },
                {
                    data: "last_reply_at",
                    name: "last_reply_at",
                    render: function (data, type, row) {
                        return data ? formatDateWithoutTimezone(data) : '';
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) {
                            btn = btn + renderEditLink(row.urls.edit);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            function DataTableAddData() {
                var statusVal = $('#filter_status').val();
                return {
                    department_uuid: $('#filter_department').val() || '',
                    status_uuid: (Array.isArray(statusVal) && statusVal.length > 0) ? statusVal.join(',') : '',
                    priority: $('#filter_priority').val() || '',
                    assigned_admin_uuid: $('#filter_assigned_to').val() || '',
                };
            }

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[8, 'desc']],
                createdRow: function (row, data) {
                    if (data.sla_breach && data.sla_breach.breached) {
                        $(row).addClass('table-danger');
                    }
                }
            });

            // Initialize filter select2 elements
            function initFilterSelect2($el, url, placeholder) {
                $el.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    allowClear: true,
                    placeholder: placeholder,
                    ajax: {
                        transport: function (params, success, failure) {
                            PUQajax(url, params.data || {}, 1000, null, 'GET')
                                .then(function (response) { success(response.data); })
                                .catch(function (error) { failure(error); });
                        },
                        delay: 250,
                        processResults: function (data) {
                            return { results: data.results, pagination: { more: data.pagination.more } };
                        }
                    }
                });
            }

            initFilterSelect2($('#filter_department'), '{{route('admin.api.ticket_departments.select.get')}}', translate('All Departments'));
            initFilterSelect2($('#filter_assigned_to'), '{{route('admin.api.admins.select.get')}}', translate('All Admins'));

            initializeSelect2($('#filter_status'), '{{route('admin.api.ticket_statuses.select.get')}}', null, 'GET', 1000, {allowClear: true, placeholder: translate('All Statuses'), multiple: true, closeOnSelect: false});

            $('#filter_priority').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: translate('All Priorities'),
                data: [
                    {id: '', text: ''},
                    {id: 'low', text: translate('Low')},
                    {id: 'medium', text: translate('Medium')},
                    {id: 'high', text: translate('High')},
                    {id: 'urgent', text: translate('Urgent')}
                ]
            }).val('').trigger('change');

            // Restore status filter from localStorage
            try {
                var savedStatuses = JSON.parse(localStorage.getItem('ticket_filter_statuses') || '[]');
                if (savedStatuses.length > 0) {
                    savedStatuses.forEach(function (s) {
                        if ($('#filter_status').find('option[value="' + s.id + '"]').length === 0) {
                            $('#filter_status').append(new Option(s.text, s.id, true, true));
                        }
                    });
                    $('#filter_status').trigger('change');
                    $dataTable.ajax.reload();
                }
            } catch (e) {}

            // Save status filter to localStorage on change
            $('#filter_status').on('change', function () {
                var selected = [];
                $(this).find('option:selected').each(function () {
                    selected.push({id: $(this).val(), text: $(this).text()});
                });
                localStorage.setItem('ticket_filter_statuses', JSON.stringify(selected));
            });

            // Reload table on filter change
            $('#filter_department, #filter_status, #filter_priority, #filter_assigned_to').on('change', function () {
                $dataTable.ajax.reload();
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                handleDelete($dataTable, this);
            });

            // ---------------------------------------------------------------
            // Bulk actions
            // ---------------------------------------------------------------
            var bulkActionUrl = '{{ route('admin.api.tickets.bulk_action.post') }}';

            function updateBulkUI() {
                selectedTickets = [];
                $tableId.find('.ticket-checkbox:checked').each(function () {
                    selectedTickets.push($(this).val());
                });
                if (selectedTickets.length > 0) {
                    $('#bulkActions').show();
                    $('#bulkCount').text(selectedTickets.length + ' ' + translate('selected'));
                } else {
                    $('#bulkActions').hide();
                }
            }

            $tableId.on('change', '.ticket-checkbox', updateBulkUI);

            $('#selectAll').on('change', function () {
                $tableId.find('.ticket-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
            });

            function executeBulkAction(data) {
                data.ticket_uuids = selectedTickets;
                PUQajax(bulkActionUrl, data, 5000, null, 'POST').then(function () {
                    $('#selectAll').prop('checked', false);
                    $dataTable.ajax.reload(null, false);
                    $('#bulkActions').hide();
                });
            }

            $('#bulkClose').on('click', function () {
                handleConfirmAction({
                    message: 'Are you sure you want to close the selected tickets?',
                    style: 'danger',
                    onConfirm: function () {
                        executeBulkAction({action: 'close'});
                    }
                });
            });

            $('#bulkAssign').on('click', function () {
                var $modal = $('#universalModal');
                $modal.find('#universalModalLabel').text(translate('Assign Selected'));
                $modal.find('.modal-body').html(
                    '<div class="mb-3"><label class="form-label">' + translate('Assigned To') + '</label>' +
                    '<select id="bulkAssignAdmin" class="form-select" style="width:100%;"></select></div>'
                );
                initializeSelect2($('#bulkAssignAdmin'), '{{route('admin.api.admins.select.get')}}', null, 'GET', 1000, {placeholder: translate('Select...'), dropdownParent: $('#universalModal')});
                $('#modalSaveButton').off('click.bulk').on('click.bulk', function () {
                    var adminUuid = $('#bulkAssignAdmin').val();
                    if (!adminUuid) return;
                    $modal.modal('hide');
                    executeBulkAction({action: 'assign', assigned_admin_uuid: adminUuid});
                });
                $modal.modal('show');
            });

            $('#bulkChangeStatus').on('click', function () {
                var $modal = $('#universalModal');
                $modal.find('#universalModalLabel').text(translate('Change Status'));
                $modal.find('.modal-body').html(
                    '<div class="mb-3"><label class="form-label">' + translate('Status') + '</label>' +
                    '<select id="bulkStatusSelect" class="form-select" style="width:100%;"></select></div>'
                );
                initializeSelect2($('#bulkStatusSelect'), '{{route('admin.api.ticket_statuses.select.get')}}', null, 'GET', 1000, {placeholder: translate('Select...'), dropdownParent: $('#universalModal')});
                $('#modalSaveButton').off('click.bulk').on('click.bulk', function () {
                    var statusUuid = $('#bulkStatusSelect').val();
                    if (!statusUuid) return;
                    $modal.modal('hide');
                    executeBulkAction({action: 'change_status', status_uuid: statusUuid});
                });
                $modal.modal('show');
            });

            initializeAutoReloadTable({
                autoReloadCheckboxId: '#autoReloadTable',
                intervalSelectId: '#intervalSelect',
                circleProgressId: '#circleProgress',
                progressColor: '#3f6ad8',
                dataTable: $dataTable
            });

        });
    </script>

@endsection

