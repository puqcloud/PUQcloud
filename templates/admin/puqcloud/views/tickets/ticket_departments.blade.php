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
                    <i class="fas fa-building icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.Ticket Departments')}}
                    <div class="page-title-subheading">
                        {{__('main.Manage support ticket departments')}}</div>
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
            <table style="width: 100%;" id="ticket_departments"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Email')}}</th>
                    <th>{{__('main.Admins')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Email')}}</th>
                    <th>{{__('main.Admins')}}</th>
                    <th>{{__('main.Description')}}</th>
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
            var $tableId = $('#ticket_departments');
            var ajaxUrl = '{{ route('admin.api.ticket_departments.get') }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `<strong>${data}</strong>`;
                    }
                },
                {data: "email", name: "email"},
                {
                    data: "admins_count",
                    name: "admins_count",
                    className: "center",
                    render: function (data, type, row) {
                        return `<span class="badge bg-primary">${data || 0}</span>`;
                    }
                },
                {
                    data: "description",
                    name: "description",
                    render: function (data, type, row) {
                        if (!data) return '';
                        return data.length > 80 ? data.substring(0, 80) + '...' : data;
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

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                handleDelete($dataTable, this);
            });

            $('#universalModal').on('show.bs.modal', function (event) {
                var $modalTitle = $(this).find('.modal-title');
                var $modalBody = $(this).find('.modal-body');

                $modalTitle.text(translate('Create Ticket Department'));

                var formHtml = `
<form id="createTicketDepartment" class="col-md-10 mx-auto">
    <div class="mb-3">
        <label class="form-label" for="name">${translate('Name')}</label>
        <div>
            <input type="text" class="form-control" id="name" name="name"
                placeholder="${translate('Name')}" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="description">${translate('Description')}</label>
        <div>
            <textarea class="form-control" id="description" name="description"
                placeholder="${translate('Description')}" rows="3"></textarea>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="email">${translate('Email')}</label>
        <div>
            <input type="email" class="form-control" id="email" name="email"
                placeholder="${translate('Email')}">
        </div>
    </div>
</form>`;

                $modalBody.html(formHtml);

                var $form = $('#createTicketDepartment');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createTicketDepartment');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.ticket_department.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                        $dataTable.ajax.reload(null, false);
                    });
            });
        });
    </script>

@endsection
