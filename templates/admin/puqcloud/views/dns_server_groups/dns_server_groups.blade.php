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
                    <i class="fas fa-layer-group icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.DNS Server Groups')}}
                    <div class="page-title-subheading">
                        {{__('main.Classify your DNS Servers into groups')}}</div>
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
            <table style="width: 100%;" id="dns_server_groups"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th>{{__('main.NS Domains')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th>{{__('main.NS Domains')}}</th>
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
            var $tableId = $('#dns_server_groups');
            var ajaxUrl = '{{ route('admin.api.dns_server_groups.get') }}';
            var columnsConfig = [
                {data: "name", name: "name"},
                {data: "description", name: "description"},
                {
                    data: "ns_domains",
                    name: "ns_domains",
                    render: function(data, type, row) {
                        if (!data || data.length === 0) {
                            return `<span class="badge bg-secondary">-</span>`;
                        }
                        return data.map(ns =>
                            `<span class="badge bg-primary me-1">${ns}</span>`
                        ).join(' ');
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
                }
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

                $modalTitle.text(translate('Create DNS Server Group'));

                var formHtml = `
<form id="createDnsServerGroup" class="col-md-10 mx-auto">
    <div class="mb-3">
        <label class="form-label" for="name">${translate('Name')}</label>
        <div>
            <input type="text" class="form-control" id="name" name="name"
                placeholder="${translate('Name')}">
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
        <label class="form-label" for="ns_domains">${translate('NS Domains')}</label>
        <div>
            <textarea class="form-control" id="ns_domains" name="ns_domains"
                placeholder="${translate('Enter each domain on a new line')}" rows="4"></textarea>
            <small class="form-text text-muted">${translate('Enter each domain on a new line')}</small>
        </div>
    </div>
</form>`;

                $modalBody.html(formHtml);

                var $form = $('#createDnsServerGroup');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });

            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createDnsServerGroup');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.dns_server_group.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                    });
            });
        });
    </script>

@endsection
