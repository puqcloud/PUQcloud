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
            <table style="width: 100%;" id="clientTickets" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('main.Subject') }}</th>
                    <th>{{ __('main.Department') }}</th>
                    <th>{{ __('main.Status') }}</th>
                    <th>{{ __('main.Priority') }}</th>
                    <th>{{ __('main.Last Reply') }}</th>
                    <th>{{ __('main.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>#</th>
                    <th>{{ __('main.Subject') }}</th>
                    <th>{{ __('main.Department') }}</th>
                    <th>{{ __('main.Status') }}</th>
                    <th>{{ __('main.Priority') }}</th>
                    <th>{{ __('main.Last Reply') }}</th>
                    <th>{{ __('main.Actions') }}</th>
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
            var $tableId = $('#clientTickets');
            var ajaxUrl = '{{ route('admin.api.client.tickets.get', $uuid) }}';

            var priorityColors = {low: 'bg-info', medium: 'bg-primary', high: 'bg-warning', urgent: 'bg-danger'};

            var columnsConfig = [
                {data: "number", name: "number", render: function (data) { return '<strong>#' + data + '</strong>'; }},
                {
                    data: "subject", name: "subject",
                    render: function (data, type, row) {
                        var html = '<strong>' + data + '</strong>';
                        if (row.name || row.email) {
                            html += '<br><small class="text-muted">' + (row.name || '') + ' ' + (row.email || '') + '</small>';
                        }
                        return html;
                    }
                },
                {
                    data: "department_name", name: "department_name",
                    render: function (data) { return data ? '<span class="badge bg-secondary">' + data + '</span>' : ''; }
                },
                {
                    data: "status_data", name: "status_data",
                    render: function (data) {
                        if (data && data.title) {
                            return '<span class="badge" style="background-color:' + (data.color || '#6c757d') + '">' + data.title + '</span>';
                        }
                        return '';
                    }
                },
                {
                    data: "priority", name: "priority",
                    render: function (data) {
                        return '<span class="badge ' + (priorityColors[data] || 'bg-secondary') + '">' + (data || '').charAt(0).toUpperCase() + (data || '').slice(1) + '</span>';
                    }
                },
                {
                    data: "last_reply_at", name: "last_reply_at",
                    render: function (data) { return data ? formatDateWithoutTimezone(data) : ''; }
                },
                {
                    data: 'urls', className: "center", orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls && row.urls.edit) { btn += renderEditLink(row.urls.edit); }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, null, {
                order: [[5, 'desc']]
            });

        });
    </script>
@endsection
