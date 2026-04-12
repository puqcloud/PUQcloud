@extends(config('template.client.view') . '.layout.layout')

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
                    <i class="fas fa-life-ring icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    {{ __('main.Support Tickets') }}
                    <div class="page-title-subheading text-muted">
                        {{ __('main.View and manage your support requests') }}
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <a href="{{ route('client.web.panel.support.ticket.create') }}" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-plus me-1"></i> {{ __('main.Open a Ticket') }}
                </a>
            </div>
        </div>
    </div>

    <div class="container px-0">
        <div id="mainCard" class="card shadow-sm">
            <div class="card-body">
                <table style="width: 100%;" id="tickets" class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('main.Subject') }}</th>
                        <th>{{ __('main.Department') }}</th>
                        <th>{{ __('main.Status') }}</th>
                        <th>{{ __('main.Priority') }}</th>
                        <th>{{ __('main.Last Reply') }}</th>
                        <th></th>
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
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var $tableId = $('#tickets');
            var ajaxUrl = '{{ route('client.api.support.tickets.get') }}';
            var columnsConfig = [
                {data: 'number', name: 'number'},
                {data: 'subject', name: 'subject'},
                {
                    data: 'department_name',
                    render: function (data) {
                        if (!data) return '';
                        return '<span class="badge bg-secondary">' + $('<span>').text(data).html() + '</span>';
                    }
                },
                {
                    data: 'status_data',
                    render: function (data) {
                        if (!data || !data.title) return '';
                        return '<span class="badge" style="background-color: ' + data.color + ';">' + $('<span>').text(data.title).html() + '</span>';
                    }
                },
                {
                    data: 'priority',
                    render: function (data) {
                        if (!data) return '';
                        var colors = {
                            'low': 'bg-info',
                            'medium': 'bg-warning',
                            'high': 'bg-danger'
                        };
                        var cls = colors[data] || 'bg-secondary';
                        var label = translate(data.charAt(0).toUpperCase() + data.slice(1));
                        return '<span class="badge ' + cls + '">' + label + '</span>';
                    }
                },
                {
                    data: 'last_reply_at',
                    render: function (data) {
                        return data ? formatDateWithoutTimezone(data) : '';
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data) {
                        if (data && data.detail) {
                            return renderDetailsButton(data.detail);
                        }
                        return '';
                    }
                },
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                order: [[5, 'desc']]
            });

            function DataTableAddData() {
                return {};
            }

            $dataTable.on('click', 'button.details-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });
        });
    </script>
@endsection
