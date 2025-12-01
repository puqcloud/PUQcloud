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
                    <i class="fa fa-users icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.Administrators')}}
                    <div class="page-title-subheading">
                        {{__('main.This is where you configure the users which you want to allow to access the admin area')}}</div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('admins-create'))
                    <button type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                            data-bs-toggle="modal" data-bs-target="#universalModal">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="admins" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Email')}}</th>
                    <th>{{__('main.Firstname')}}</th>
                    <th>{{__('main.Lastname')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Email')}}</th>
                    <th>{{__('main.Firstname')}}</th>
                    <th>{{__('main.Lastname')}}</th>
                    <th>{{__('main.Status')}}</th>
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
            var $tableId = $('#admins');
            var ajaxUrl = '{{ route('admin.api.admins.get') }}';
            var columnsConfig = [
                {
                    data: 'email',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left me-3">
                            <div class="avatar-icon-wrapper">
                                <div class="badge badge-bottom"></div>
                                <div class="avatar-icon rounded">
                                    <img src="${row.urls.gravatar}" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.email}</div>
                            <div class="widget-subheading">${row.uuid}</div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {data: "firstname", name: "firstname"},
                {data: "lastname", name: "lastname"},
                {
                    data: "disable", name: "disable",
                    render: function (data, type, row) {
                        return renderStatus(data);
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) {
                            btn = btn + renderEditButton(row.urls.edit);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            $tableId.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm(translate('Are you sure you want to delete this record?'))) {
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $tableId.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

            $('#universalModal').on('show.bs.modal', function (event) {
                var $modalTitle = $(this).find('.modal-title');
                var $modalBody = $(this).find('.modal-body');

                $modalTitle.text(translate('Create Administrator'));

                var formHtml = `
            <form id="createAdmin" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="email">${translate('Email')}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="email" name="email"
                            inputmode="email" placeholder="${translate('Email')}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="firstname">${translate('Firstname')}</label>
                    <div>
                        <input type="text" class="form-control" id="firstname" name="firstname" placeholder="${translate('Firstname')}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="lastname">${translate('Lastname')}</label>
                    <div>
                        <input type="text" class="form-control" id="lastname" name="lastname" placeholder="${translate('Lastname')}">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="language" class="form-label">${translate('Language')}</label>
                            <select name="language" id="language" class="form-select mb-2 form-control"></select>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-1">
                    <div class="mb-3">
                        <div class="position-relative mb-3">
                            <div>
                                <label for="groups" class="form-label">${translate('Groups')}</label>
                                <select multiple name="groups" id="groups"
                                        class="form-select mb-2 form-control"></select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">${translate('Password')}</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="${translate('Password')}">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">${translate('Confirm password')}</label>
                    <div>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="${translate('Confirm password')}">
                    </div>
                </div>
            </form>`;

                $modalBody.html(formHtml);

                var $form = $('#createAdmin');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });

                var $elementLanguage = $modalBody.find('[name="language"]');
                initializeSelect2($elementLanguage, '{{route('admin.api.languages.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                var $elementgroups = $modalBody.find('[name="groups"]');
                initializeSelect2($elementgroups, '{{route('admin.api.groups.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createAdmin');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.admin.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                    });
            });
        });
    </script>

@endsection
