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
                    <i class="fas fa-globe icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.DNS Servers')}}
                    <div class="page-title-subheading">
                        {{__('main.This is where you configure the DNS Servers')}}</div>
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
            <table style="width: 100%;" id="dns_servers"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Module')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Module')}}</th>
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
            var $tableId = $('#dns_servers');
            var ajaxUrl = '{{ route('admin.api.dns_servers.get') }}';
            var columnsConfig = [
                {data: "name", name: "name"},
                {
                    data: "module_data", name: "module_data",
                    render: function (data, type, row) {
                        if (row.module_data.name) {
                            return row.module_data.name;
                        } else {
                            return data;
                        }
                    }
                },
                {data: "description", name: "description"},
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.web_edit) {
                            btn = btn + renderEditButton(row.urls.web_edit);
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

                $modalTitle.text(translate('Create DNS Server'));

                var formHtml = `
            <form id="createDnsServer" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">${translate('Name')}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="${translate('Name')}">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="position-relative mb-3">
                        <div>
                            <label for="module" class="form-label">${translate('Module')}</label>
                            <select name="module" id="module" class="form-select mb-2 form-control"></select>
                        </div>
                    </div>
                </div>

            </form>`;

                $modalBody.html(formHtml);

                var $elementModule = $modalBody.find('[name="module"]');
                initializeSelect2($elementModule, '{{route('admin.api.dns_server_modules.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                var $form = $('#createDnsServer');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createDnsServer');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.dns_server.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                    });
            });
        });
    </script>

@endsection
