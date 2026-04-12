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
                    <i class="fas fa-clipboard-list icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.DNS Zone Templates')}}
                    <div class="page-title-subheading">
                        {{__('main.Predefined record sets for quick zone creation')}}</div>
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
            <table style="width: 100%;" id="dns_zone_templates" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{ __('main.Name') }}</th>
                    <th>{{ __('main.Description') }}</th>
                    <th>{{ __('main.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{ __('main.Name') }}</th>
                    <th>{{ __('main.Description') }}</th>
                    <th>{{ __('main.Actions') }}</th>
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

            var modalEditor = null;

            var defaultTemplate = `; Basic website template
; Variables: @{{DOMAIN}}, @{{IP}}
;
@ 3600 IN A @{{IP}}
www 3600 IN A @{{IP}}
@ 3600 IN MX 10 mail.@{{DOMAIN}}.
@ 3600 IN TXT "v=spf1 mx ~all"
_dmarc 3600 IN TXT "v=DMARC1; p=none"`;

            var $tableId = $('#dns_zone_templates');
            var ajaxUrl = '{{ route('admin.api.dns_zone_templates.get') }}';
            var columnsConfig = [
                {data: "name", name: "name", render: function(data) { return '<strong>' + data + '</strong>'; }},
                {data: "description", name: "description", render: function(data) { return data || ''; }},
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.edit) { btn += renderEditLink(row.urls.edit); }
                        if (row.urls.delete) { btn += renderDeleteButton(row.urls.delete); }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                handleDelete($dataTable, this);
            });

            $('#universalModal').on('show.bs.modal', function (event) {
                var $modalTitle = $(this).find('.modal-title');
                var $modalBody = $(this).find('.modal-body');
                $(this).find('.modal-dialog').addClass('modal-xl');

                $modalTitle.text(translate('Create DNS Zone Template'));

                var formHtml = `
<form id="createDnsZoneTemplate" class="mx-auto">
    <div class="row">
        <div class="col-12 col-md-4">
            <div class="mb-3">
                <label class="form-label" for="name">${translate('Name')}</label>
                <input type="text" class="form-control" id="name" name="name">
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">${translate('Description')}</label>
                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
            </div>
            <button type="button" id="fillDefault" class="btn btn-outline-info btn-sm mb-3">
                <i class="fas fa-magic me-1"></i> ${translate('Fill with default template')}
            </button>
            <div class="alert alert-info mb-0">
                <strong>${translate('Available variables')}:</strong><br>
                <code>@{{DOMAIN}}</code>, <code>@{{IP}}</code>, <code>@{{IP6}}</code>, <code>@{{MAIL_SERVER}}</code><br>
                <small class="text-muted">${translate('or any custom')} <code>@{{VARIABLE}}</code></small>
            </div>
        </div>
        <div class="col-12 col-md-8">
            <div class="mb-3">
                <label class="form-label" for="content">${translate('Template Content (BIND format)')}</label>
                <textarea class="form-control font-monospace" id="modal_content" name="content" rows="15"></textarea>
            </div>
        </div>
    </div>
</form>`;

                $modalBody.html(formHtml);

                setTimeout(function () {
                    modalEditor = CodeMirror.fromTextArea(document.getElementById('modal_content'), {
                        mode: "text/plain",
                        lineNumbers: true,
                        lineWrapping: true,
                        theme: "default",
                        scrollbarStyle: "native"
                    });
                    modalEditor.setSize('100%', '350px');
                }, 200);

                $('#fillDefault').on('click', function () {
                    if (modalEditor) {
                        modalEditor.setValue(defaultTemplate);
                    }
                    if (!$('#name').val()) {
                        $('#name').val('Basic Website');
                    }
                    if (!$('#description').val()) {
                        $('#description').val('Basic website with A, MX, SPF and DMARC records');
                    }
                });

                var $form = $('#createDnsZoneTemplate');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea') && !$(event.target).hasClass('CodeMirror-code')) {
                        event.preventDefault();
                    }
                });
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                if (modalEditor) {
                    modalEditor.toTextArea();
                    modalEditor = null;
                }
                $(this).find('.modal-dialog').removeClass('modal-xl');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createDnsZoneTemplate');
                if ($form.length === 0) return;

                if (modalEditor) {
                    modalEditor.save();
                }

                var formData = serializeForm($form);

                PUQajax('{{ route('admin.api.dns_zone_template.post') }}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                    });
            });
        });
    </script>
@endsection
