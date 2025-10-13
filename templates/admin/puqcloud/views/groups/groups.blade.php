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
                    {{__('main.Groups')}}
                    <div class="page-title-subheading">
                        {{__('main.This is where you configure the groups')}}</div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('groups-create'))
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
            <table style="width: 100%;" id="groups" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th>{{__('main.Type')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Name')}}</th>
                    <th>{{__('main.Description')}}</th>
                    <th>{{__('main.Type')}}</th>
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
            var tableId = '#groups';
            var ajaxUrl = '{{ route('admin.api.groups.get') }}';
            var columnsConfig = [
                {
                    data: "name", name: "name",
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">` + row.name + `</div>
                            <div class="widget-subheading">` + row.uuid + `</div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {data: "description", name: "description"},
                {
                    data: "type", name: "type",
                    render: function (data, type, row) {
                        return row.type_data && row.type_data.name ? row.type_data.name : 'none';
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if(row.urls.web_edit) {
                            btn = btn + renderEditButton(row.urls.web_edit);
                        }
                        if(row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $(tableId).on('click', 'button.delete-btn', function (e) {
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

            $(tableId).on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

            $('#universalModal').on('show.bs.modal', function (event) {
                var $modalElement = $(this);
                var $modalTitle = $modalElement.find('.modal-title');
                var $modalBody = $modalElement.find('.modal-body');

                $modalTitle.text(translate('Create Group'));

                var formHtml = `
        <form id="createGroup" class="col-md-10 mx-auto">
            <div class="mb-3">
                <label class="form-label" for="name">` + translate('Name') + `</label>
                <div>
                    <input type="text" class="form-control input-mask-trigger" id="name" name="name"
                           inputmode="name" placeholder="` + translate('Name') + `">
                </div>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">` + translate('Type') + `</label>
                <select class="multiselect-dropdown form-control" name="type" id="type"></select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="description">` + translate('Description') + `</label>
                <textarea name="description" id="description" class="form-control" rows="1"></textarea>
            </div>
        </form>`;

                $modalBody.html(formHtml);

                var $form = $('#createGroup');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });

                var $description = $modalElement.find('[name="description"]');

                function textareaAutoSize($element) {
                    $element.css('height', 'auto');
                    $element.css('height', $element[0].scrollHeight + 'px');
                }

                textareaAutoSize($description);
                $description.on('input', function () {
                    textareaAutoSize($(this));
                });

                var $elementType = $modalElement.find('[name="type"]');
                initializeSelect2($elementType, '{{route('admin.api.group_types.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                var $form = $('#createGroup');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.group.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                    });
            });
        });
    </script>

@endsection
