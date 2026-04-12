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
                            <i class="fas fa-headset icon-gradient bg-primary"></i>
                        </span>
                        <span class="d-inline-block">{{__('main.Create Ticket')}}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('admin.web.tickets') }}">{{ __('main.Support Tickets') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.Create Ticket')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="saveDraft" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save as Draft')}}
                </button>
            </div>
        </div>
    </div>

    <form id="createForm" novalidate="novalidate">
        <div class="row">
            {{-- LEFT COLUMN --}}
            <div class="col-md-8">

                {{-- Subject & Message --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-edit me-1"></i> {{__('main.Ticket Details')}}
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="subject">{{__('main.Subject')}}</label>
                            <input type="text" class="form-control" id="subject" name="subject"
                                   placeholder="{{__('main.Subject')}}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="message">{{__('main.Message')}}</label>
                            <textarea class="form-control" id="message" name="message" rows="10"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Attachments --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-paperclip me-1"></i> {{__('main.Attachments')}}
                    </div>
                    <div class="card-body">
                        <input type="file" class="form-control" id="ticket_attachments" multiple>
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN --}}
            <div class="col-md-4">

                {{-- Settings --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-cogs me-1"></i> {{__('main.Settings')}}
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="department_uuid">{{__('main.Department')}}</label>
                            <select class="form-select" id="department_uuid" name="department_uuid" required></select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="priority">{{__('main.Priority')}}</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">{{__('main.Low')}}</option>
                                <option value="medium" selected>{{__('main.Medium')}}</option>
                                <option value="high">{{__('main.High')}}</option>
                                <option value="urgent">{{__('main.Urgent')}}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="client_uuid">{{__('main.Client')}}</label>
                            <select class="form-select" id="client_uuid" name="client_uuid"></select>
                        </div>

                        <div class="mb-3" id="non_client_fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="ticket_name">{{__('main.Name')}}</label>
                                        <input type="text" class="form-control" id="ticket_name" name="name"
                                               placeholder="{{__('main.Name')}}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="ticket_email">{{__('main.Email')}}</label>
                                        <input type="email" class="form-control" id="ticket_email" name="email"
                                               placeholder="{{__('main.Email')}}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="service_field" style="display: none;">
                            <label for="service_uuid" class="form-label">{{__('main.Service')}}</label>
                            <select name="service_uuid" id="service_uuid"
                                    class="form-select mb-2 form-control"></select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="cc_emails">{{__('main.CC Emails')}}</label>
                            <textarea class="form-control" id="cc_emails" name="cc_emails" rows="3"
                                      placeholder="{{__('main.One email per line')}}"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="tags">{{__('main.Tags')}}</label>
                            <input type="text" class="form-control" id="tags" name="tags"
                                   placeholder="{{__('main.Comma-separated tags')}}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="admin_notes">{{__('main.Admin Notes')}}</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"></textarea>
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

            var servicesSelectUrlTemplate = '{{ route('admin.api.client.services.select.get', '__UUID__') }}';

            // Initialize EasyMDE editor
            var easyMDE = new EasyMDE({
                element: document.getElementById('message'),
                spellChecker: false,
                status: false,
                minHeight: '200px',
                toolbar: [
                    'bold', 'italic', 'heading', '|',
                    'unordered-list', 'ordered-list', '|',
                    'link', 'quote', 'code', '|',
                    'preview', 'side-by-side', 'fullscreen', '|',
                    'guide'
                ]
            });

            // Initialize Select2 fields
            initializeSelect2($('#department_uuid'), '{{route('admin.api.ticket_departments.select.get')}}', null, 'GET', 1000, {});

            initializeSelect2($('#client_uuid'), '{{route('admin.api.clients.select.get')}}', null, 'GET', 1000, {
                allowClear: true,
                placeholder: '{{__('main.Select Client (optional)')}}'
            });

            // Client change handler
            $('#client_uuid').on('change', function () {
                var clientUuid = $(this).val();
                if (clientUuid) {
                    $('#non_client_fields').hide();
                    $('#service_field').show();

                    var serviceSelectUrl = servicesSelectUrlTemplate.replace('__UUID__', clientUuid);
                    initializeSelect2($('#service_uuid'), serviceSelectUrl, null, 'GET', 1000, {
                        allowClear: true,
                        placeholder: '{{__('main.No specific product')}}'
                    });
                } else {
                    $('#non_client_fields').show();
                    $('#service_field').hide();
                    $('#service_uuid').val(null).trigger('change');
                }
            });

            // Save as Draft
            $('#saveDraft').on('click', function (event) {
                event.preventDefault();
                var $btn = $(this);
                var $form = $('#createForm');

                if ($form.length === 0) {
                    return;
                }

                // Sync EasyMDE content to textarea
                easyMDE.codemirror.save();

                var formData = serializeForm($form);

                // Convert cc_emails textarea to array
                if (formData.cc_emails) {
                    formData.cc_emails = formData.cc_emails.split('\n').map(function (e) {
                        return e.trim();
                    }).filter(function (e) {
                        return e.length > 0;
                    });
                }

                // Convert tags to array
                if (formData.tags) {
                    formData.tags = formData.tags.split(',').map(function (t) {
                        return t.trim();
                    }).filter(function (t) {
                        return t.length > 0;
                    });
                }

                PUQajax('{{route('admin.api.ticket.post')}}', formData, 5000, $btn, 'POST', $form)
                    .then(function (response) {
                        var ticketUuid = response.data.uuid;

                        // Upload attachments if any
                        var fileInput = document.getElementById('ticket_attachments');
                        if (fileInput.files && fileInput.files.length > 0) {
                            var promises = [];
                            for (var i = 0; i < fileInput.files.length; i++) {
                                var fd = new FormData();
                                fd.append('ticket_uuid', ticketUuid);
                                fd.append('file', fileInput.files[i]);
                                promises.push(PUQajax('{{route('admin.api.ticket.attachment.post')}}', fd, 10000, null, 'POST'));
                            }
                            Promise.all(promises).then(function () {
                                window.location.href = response.data.redirect;
                            });
                        } else {
                            window.location.href = response.data.redirect;
                        }
                    });
            });
        });
    </script>
@endsection

