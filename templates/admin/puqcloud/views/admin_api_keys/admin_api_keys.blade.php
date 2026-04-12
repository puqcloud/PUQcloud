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
                        <i class="fas fa-key"></i>
                    </span>
                        <span class="d-inline-block">{{__('main.Admin API Keys')}}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav class="" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a><i class="fa fa-home"></i></a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.Admin API Keys')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('admin-api-keys-create'))
                    <button type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                            id="create">
                        <i class="fa fa-plus"></i>
                        {{__('main.Create')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="adminApiKeys"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Admin')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th>{{__('main.IPs')}}</th>
                    <th>{{__('main.Last Used')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Admin')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th>{{__('main.IPs')}}</th>
                    <th>{{__('main.Last Used')}}</th>
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

            var tableId = '#adminApiKeys';
            var ajaxUrl = '{{ route('admin.api.admin_api_keys.get') }}';
            var $dataTable = initializeDataTable(tableId, ajaxUrl, [
                {data: "name", name: "name"},
                {
                    data: 'admin',
                    orderable: false,
                    render: function (data, type, row) {

                        if (!row || !row.admin) {
                            return '-';
                        }

                        let adminName = row.admin ?? '';
                        let adminUuid = row.admin_uuid ?? null;

                        let html = adminName;

                        if (adminUuid) {
                            html += '<br>' + linkify('admin:' + adminUuid, true);
                        }

                        return html;
                    }
                },
                {data: "description", name: "description"},
                {data: "ips", name: "ips", render: data => data ? data.join(', ') : ''},
                {data: "last_used_at", name: "last_used_at", render: data => data ?? '-'},
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.put) btn += renderEditButton(row.urls.put);
                        if (row.urls.delete) btn += renderDeleteButton(row.urls.delete);
                        return btn;
                    }
                }
            ]);

            $('#create').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Create API Key'));

                var formHtml = `
<form id="createForm" class="col-md-10 mx-auto">
    <div class="mb-3">
        <label class="form-label" for="name">${translate('Name')}</label>
        <input type="text" class="form-control" id="name" name="name" placeholder="${translate('Name')}">
    </div>
    <div class="mb-3">
        <label class="form-label" for="admin_uuid">${translate('Admin')}</label>
        <select name="admin_uuid" id="admin_uuid" class="form-select mb-2 form-control"></select>
    </div>
    <div class="mb-3">
        <label class="form-label" for="description">${translate('Description')}</label>
        <textarea class="form-control" id="description" name="description" placeholder="${translate('Description')}"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label" for="ips">${translate('IPs (comma separated)')}</label>
        <input type="text" class="form-control" id="ips" name="ips" placeholder="192.168.0.1, 10.0.0.1">
    </div>
</form>
<div id="generatedKeyContainer" class="d-none mb-3">
    <label class="form-label">${translate('Generated Key')}</label>
    <div class="input-group">
        <input type="text" id="generatedKey" class="form-control" readonly>
        <button class="btn btn-outline-secondary" id="copyKeyBtn" type="button">${translate('Copy')}</button>
    </div>
</div>
`;

                $modalBody.html(formHtml);
                $('#universalModal').modal('show');

                initializeSelect2($('#admin_uuid'), '{{route('admin.api.admins.select.get')}}', null, 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });
                $('#modalSaveButton').show();
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.admin_api_key.post') }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            if (response.status === 'success' && response.data) {

                                $('#createForm').hide();

                                $('#generatedKey').val(response.data);
                                $('#generatedKeyContainer').removeClass('d-none');

                                $('#modalSaveButton').hide();

                                $('#copyKeyBtn').off('click').on('click', function () {
                                    navigator.clipboard.writeText(response.data).then(() => {
                                        alert(translate('Copied!'));
                                    });
                                });

                                $dataTable.ajax.reload(null, false);
                            }
                        })
                        .catch(console.error);
                }

                if ($('#editForm').length) {
                    var $form = $('#editForm');
                    var formData = serializeForm($form);
                    var modelUrl = $(this).data('modelUrl');

                    PUQajax(modelUrl, formData, 500, $(this), 'PUT', $form)
                        .then(function () {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                $('#modalSaveButton').show();
                $('#createForm').show();
                $('#createForm')[0]?.reset();
                $('#generatedKeyContainer').addClass('d-none');
                $('#generatedKey').val('');
            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text(translate('Edit API Key'));

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        var data = response.data;
                        var formHtml = `
<form id="editForm" class="col-md-10 mx-auto">
    <div class="mb-3">
        <label class="form-label">${translate('Name')}</label>
        <input type="text" class="form-control" id="name" name="name" value="${data.name}">
    </div>
    <div class="mb-3">
        <label class="form-label">${translate('Description')}</label>
        <textarea class="form-control" id="description" name="description">${data.description ?? ''}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">${translate('IPs (comma separated)')}</label>
        <input type="text" class="form-control" id="ips" name="ips" value="${data.ips ? data.ips.join(', ') : ''}">
    </div>
</form>`;
                        $modalBody.html(formHtml);
                        $('#modalSaveButton').data('modelUrl', modelUrl);
                        $('#universalModal').modal('show');
                    });
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                handleDelete($dataTable, this);
            });

        });
    </script>
@endsection

