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
                        <span class="d-inline-block pe-2"><i class="fas fa-clipboard-list icon-gradient bg-primary"></i></span>
                        <span class="d-inline-block">{{ __('main.Edit DNS Zone Template') }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('admin.web.dns_zone_templates') }}">{{ __('main.DNS Zone Templates') }}</a></li>
                                <li class="active breadcrumb-item" aria-current="page">{{ $uuid }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="save" type="button" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            </div>
        </div>
    </div>

    <form id="editDnsZoneTemplateForm" class="mx-auto" novalidate="novalidate">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-3">
                        <div class="mb-3">
                            <label class="form-label" for="name"><i class="fas fa-tag"></i> {{ __('main.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description"><i class="fas fa-info-circle"></i> {{ __('main.Description') }}</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="alert alert-info mb-0">
                            <strong>{{ __('main.Available variables') }}:</strong><br>
                            <code>@{{DOMAIN}}</code> - {{ __('main.Zone domain name') }}<br>
                            <code>@{{IP}}</code> - {{ __('main.IPv4 address') }}<br>
                            <code>@{{IP6}}</code> - {{ __('main.IPv6 address') }}<br>
                            <code>@{{MAIL_SERVER}}</code> - {{ __('main.Mail server') }}<br>
                            <small class="text-muted">{{ __('main.You can use any custom variables like') }} <code>@{{MY_VAR}}</code></small>
                        </div>
                    </div>
                    <div class="col-12 col-md-9">
                        <div class="mb-3">
                            <label class="form-label" for="content"><i class="fas fa-code"></i> {{ __('main.Template Content (BIND format)') }}</label>
                            <textarea class="form-control font-monospace" id="content" name="content" rows="20" required></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var editor = null;

            function loadFormData() {
                blockUI('editDnsZoneTemplateForm');
                const $form = $('#editDnsZoneTemplateForm');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{ route('admin.api.dns_zone_template.get', $uuid) }}', {}, 5000, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            $('#name').val(response.data.name || '');
                            $('#description').val(response.data.description || '');

                            var content = response.data.content || '';

                            if (!editor) {
                                editor = CodeMirror.fromTextArea(document.getElementById('content'), {
                                    mode: "text/plain",
                                    lineNumbers: true,
                                    lineWrapping: true,
                                    matchBrackets: true,
                                    theme: "default",
                                    scrollbarStyle: "native"
                                });
                                editor.setSize('100%', '500px');
                                editor.on('change', function () {
                                    editor.save();
                                });
                            }
                            editor.setValue(content);
                        }
                        unblockUI('editDnsZoneTemplateForm');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('editDnsZoneTemplateForm');
                    });
            }

            loadFormData();

            $('#save').on('click', function (event) {
                event.preventDefault();
                const $form = $('#editDnsZoneTemplateForm');

                if (editor) {
                    editor.save();
                }

                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.dns_zone_template.put', $uuid) }}', formData, 5000, $(this), 'PUT', $form)
                    .then(function () {
                        loadFormData();
                    });
            });
        });
    </script>
@endsection
