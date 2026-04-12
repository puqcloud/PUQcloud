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
                    <i class="fas fa-ban icon-gradient bg-danger"></i>
                </div>
                <div>
                    {{__('main.Email Blacklist')}}
                    <div class="page-title-subheading">
                        {{__('main.Manage blacklisted email addresses')}}</div>
                </div>
            </div>
            <div class="page-title-actions">
                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        data-bs-toggle="modal" data-bs-target="#universalModal">
                    <i class="fa fa-plus"></i>
                    {{__('main.Add')}}
                </button>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="blacklist"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Email')}}</th>
                    <th>{{__('main.Reason')}}</th>
                    <th>{{__('main.Added By')}}</th>
                    <th>{{__('main.Date')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Email')}}</th>
                    <th>{{__('main.Reason')}}</th>
                    <th>{{__('main.Added By')}}</th>
                    <th>{{__('main.Date')}}</th>
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
            var $tableId = $('#blacklist');
            var ajaxUrl = '{{ route('admin.api.ticket_email_blacklist.get') }}';
            var columnsConfig = [
                {data: "email", name: "email"},
                {
                    data: "reason", name: "reason",
                    render: function (data) {
                        return data || '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: "admin_name", name: "admin_name",
                    render: function (data) {
                        return data || '<span class="text-muted">' + translate('System') + '</span>';
                    }
                },
                {
                    data: "created_at", name: "created_at",
                    render: function (data) {
                        return data ? formatDateWithoutTimezone(data) : '';
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, undefined, {
                order: [[3, 'desc']]
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                handleDelete($dataTable, this);
            });

            $('#universalModal').on('show.bs.modal', function (event) {
                var $modalTitle = $(this).find('.modal-title');
                var $modalBody = $(this).find('.modal-body');

                $modalTitle.text(translate('Add to Blacklist'));

                var formHtml = `
<form id="createBlacklist" class="col-md-10 mx-auto">
    <div class="mb-3">
        <label class="form-label" for="email">${translate('Email')}</label>
        <input type="email" class="form-control" id="email" name="email"
            placeholder="${translate('Email')}" required>
    </div>
    <div class="mb-3">
        <label class="form-label" for="reason">${translate('Reason')}</label>
        <input type="text" class="form-control" id="reason" name="reason"
            placeholder="${translate('Reason')}">
    </div>
</form>`;

                $modalBody.html(formHtml);
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createBlacklist');
                if ($form.length === 0) return;

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.ticket_email_blacklist.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                        $dataTable.ajax.reload(null, false);
                    });
            });
        });
    </script>

@endsection