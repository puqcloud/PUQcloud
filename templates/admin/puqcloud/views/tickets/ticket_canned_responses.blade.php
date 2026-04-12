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
                            <i class="fas fa-comment-dots icon-gradient bg-primary"></i>
                        </span>
                        <span class="d-inline-block">{{ __('main.Canned Responses') }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ __('main.Canned Responses') }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button type="button" data-bs-toggle="modal" data-bs-target="#universalModal"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-plus"></i> {{ __('main.Create') }}
                </button>
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="canned_responses"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{ __('main.Title') }}</th>
                    <th>{{ __('main.Department') }}</th>
                    <th>{{ __('main.Sort Order') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                <tr>
                    <th>{{ __('main.Title') }}</th>
                    <th>{{ __('main.Department') }}</th>
                    <th>{{ __('main.Sort Order') }}</th>
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
            var $tableId = $('#canned_responses');
            var ajaxUrl = '{{ route('admin.api.ticket_canned_responses.get') }}';

            var columnsConfig = [
                {data: 'title', name: 'title'},
                {
                    data: 'department_name',
                    name: 'department_name',
                    render: function (data) {
                        return data ? '<span class="badge bg-secondary">' + data + '</span>' : '<span class="text-muted">—</span>';
                    }
                },
                {data: 'sort_order', name: 'sort_order', className: 'center'},
                {
                    data: 'urls',
                    className: 'center',
                    orderable: false,
                    render: function (data, type, row) {
                        return (data.delete ? renderDeleteButton(data.delete) : '');
                    }
                }
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, function () { return {}; }, {
                order: [[2, 'asc']]
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                handleDelete($dataTable, this);
            });

            // Edit on row click
            $tableId.on('click', 'tbody tr td:not(:last-child)', function () {
                var data = $dataTable.row($(this).closest('tr')).data();
                if (!data) return;
                loadEditModal(data.uuid);
            });

            function loadEditModal(uuid) {
                PUQajax('{{ route('admin.api.ticket_canned_response.get', '__UUID__') }}'.replace('__UUID__', uuid), {}, 5000, null, 'GET')
                    .then(function (response) {
                        var item = response.data;
                        showModal(item);
                    });
            }

            function showModal(item) {
                var isEdit = !!item;
                var $modal = $('#universalModal');
                $modal.find('.modal-title').text(isEdit ? translate('Edit') : translate('Create'));

                var formHtml = `
<form id="cannedResponseForm" class="col-md-10 mx-auto">
    <div class="mb-3">
        <label class="form-label">${translate('Title')}</label>
        <input type="text" class="form-control" name="title" value="${isEdit ? (item.title || '') : ''}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">${translate('Department')}</label>
        <select name="department_uuid" id="cr_department" class="form-select" style="width:100%;"></select>
    </div>
    <div class="mb-3">
        <label class="form-label">${translate('Message')}</label>
        <textarea class="form-control" name="message" rows="8" required>${isEdit ? (item.message || '') : ''}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">${translate('Sort Order')}</label>
        <input type="number" min="0" class="form-control" name="sort_order" value="${isEdit ? (item.sort_order || 0) : 0}">
    </div>
    ${isEdit ? '<input type="hidden" name="uuid" value="' + item.uuid + '">' : ''}
</form>`;

                $modal.find('.modal-body').html(formHtml);
                $modal.find('#modalConfirmButton').show();

                initializeSelect2(
                    document.getElementById('cr_department'),
                    '{{ route('admin.api.ticket_departments.select.get') }}',
                    isEdit && item.department_data ? item.department_data : null,
                    'GET', 1000,
                    {allowClear: true, placeholder: '—'}
                );

                $modal.modal('show');
            }

            // Create button opens empty modal
            $('#universalModal').on('show.bs.modal', function (event) {
                if (event.relatedTarget) {
                    showModal(null);
                }
            });

            // Save
            $(document).on('click', '#modalConfirmButton', function (event) {
                event.preventDefault();
                var $form = $('#cannedResponseForm');
                if ($form.length === 0) return;

                var formData = serializeForm($form);
                var uuid = formData.uuid;
                delete formData.uuid;

                var url = uuid
                    ? '{{ route('admin.api.ticket_canned_response.put', '__UUID__') }}'.replace('__UUID__', uuid)
                    : '{{ route('admin.api.ticket_canned_response.post') }}';
                var method = uuid ? 'PUT' : 'POST';

                PUQajax(url, formData, 500, $(this), method, $form).then(function () {
                    $('#universalModal').modal('hide');
                    $dataTable.ajax.reload(null, false);
                });
            });
        });
    </script>
@endsection
