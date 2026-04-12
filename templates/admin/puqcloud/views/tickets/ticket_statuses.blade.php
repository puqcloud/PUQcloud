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
                    <i class="fas fa-tags icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.Ticket Statuses')}}
                    <div class="page-title-subheading">
                        {{__('main.Manage ticket status definitions')}}</div>
                </div>
            </div>
            <div class="page-title-actions">
                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        data-bs-toggle="modal" data-bs-target="#universalModal">
                    <i class="fa fa-plus"></i>
                    {{__('main.Create')}}
                </button>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="ticket_statuses"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Title')}}</th>
                    <th>{{__('main.Color')}}</th>
                    <th>{{__('main.Include in Active')}}</th>
                    <th>{{__('main.Include in Awaiting Reply')}}</th>
                    <th>{{__('main.Auto Close Days')}}</th>
                    <th>{{__('main.Sort Order')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Title')}}</th>
                    <th>{{__('main.Color')}}</th>
                    <th>{{__('main.Include in Active')}}</th>
                    <th>{{__('main.Include in Awaiting Reply')}}</th>
                    <th>{{__('main.Auto Close Days')}}</th>
                    <th>{{__('main.Sort Order')}}</th>
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
            var $tableId = $('#ticket_statuses');
            var ajaxUrl = '{{ route('admin.api.ticket_statuses.get') }}';
            var columnsConfig = [
                {data: "title", name: "title"},
                {
                    data: "color",
                    name: "color",
                    className: "center",
                    render: function (data, type, row) {
                        return `<span class="badge" style="background-color: ${data}; color: #fff;">${data}</span>`;
                    }
                },
                {
                    data: "include_in_active",
                    name: "include_in_active",
                    className: "center",
                    render: function (data, type, row) {
                        return data
                            ? `<span class="badge bg-success">${translate('Yes')}</span>`
                            : `<span class="badge bg-secondary">${translate('No')}</span>`;
                    }
                },
                {
                    data: "include_in_awaiting_reply",
                    name: "include_in_awaiting_reply",
                    className: "center",
                    render: function (data, type, row) {
                        return data
                            ? `<span class="badge bg-success">${translate('Yes')}</span>`
                            : `<span class="badge bg-secondary">${translate('No')}</span>`;
                    }
                },
                {data: "auto_close_days", name: "auto_close_days", className: "center"},
                {data: "sort_order", name: "sort_order", className: "center"},
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) {
                            btn = btn + renderEditLink(row.urls.edit);
                        }
                        if (row.urls.delete && !row.is_default) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                handleDelete($dataTable, this);
            });

            $('#universalModal').on('show.bs.modal', function (event) {
                var $modalTitle = $(this).find('.modal-title');
                var $modalBody = $(this).find('.modal-body');

                $modalTitle.text(translate('Create Ticket Status'));

                var formHtml = `
<form id="createTicketStatus" class="col-md-10 mx-auto">
    <div class="mb-3">
        <label class="form-label" for="title">${translate('Title')}</label>
        <div>
            <input type="text" class="form-control" id="title" name="title"
                placeholder="${translate('Title')}" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="color">${translate('Color')}</label>
        <div>
            <input type="color" class="form-control form-control-color" id="color" name="color"
                value="#0d6efd">
        </div>
    </div>

    <div class="mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="include_in_active" name="include_in_active" value="1">
            <label class="form-check-label" for="include_in_active">${translate('Include in Active')}</label>
        </div>
    </div>

    <div class="mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="include_in_awaiting_reply" name="include_in_awaiting_reply" value="1">
            <label class="form-check-label" for="include_in_awaiting_reply">${translate('Include in Awaiting Reply')}</label>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="auto_close_days">${translate('Auto Close Days')}</label>
        <div>
            <input type="number" min="0" step="1" class="form-control" id="auto_close_days" name="auto_close_days"
                placeholder="0" value="0">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="sort_order">${translate('Sort Order')}</label>
        <div>
            <input type="number" min="0" step="1" class="form-control" id="sort_order" name="sort_order"
                placeholder="0" value="0">
        </div>
    </div>
</form>`;

                $modalBody.html(formHtml);

                var $form = $('#createTicketStatus');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createTicketStatus');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.ticket_status.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                        $dataTable.ajax.reload(null, false);
                    });
            });
        });
    </script>

@endsection
