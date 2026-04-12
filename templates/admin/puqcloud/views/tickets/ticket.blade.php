@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        .reply-item { border-left: 3px solid #007bff; padding: 12px 16px; margin-bottom: 16px; background: #f8f9fa; border-radius: 0 6px 6px 0; }
        .reply-item.admin-reply { border-left-color: #007bff; }
        .reply-item.client-reply { border-left-color: #28a745; }
        .reply-item.admin-note { border-left-color: #ffc107; background: #fff8e1; }
        .reply-item .reply-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .reply-item .reply-body { word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; max-width: 100%; overflow: hidden; }
        .reply-item .reply-body p:last-child { margin-bottom: 0; }
    </style>
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
                        <span class="d-inline-block" id="pageTitle">{{ __('main.Ticket') }}</span>
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
                                    {{ $uuid }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">

                <button id="publishTicket" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary"
                        style="display: none;">
                    <i class="fas fa-paper-plane"></i> {{__('main.Publish')}}
                </button>

                <button id="scrollToReply" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fas fa-reply"></i> {{__('main.Reply')}}
                </button>

                <button id="save" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        style="display: none;">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>

                <div class="mb-2 me-2 btn-group">
                    <button type="button" data-bs-toggle="dropdown"
                            class="dropdown-toggle btn btn-primary" aria-expanded="false">
                        <i class="fas fa-cogs"></i> {{__('main.Actions')}}
                    </button>
                    <div class="dropdown-menu">
                        <button id="closeTicket" type="button" class="dropdown-item">
                            <i class="fas fa-times-circle me-2"></i> {{__('main.Close Ticket')}}
                        </button>
                        <button id="reopenTicket" type="button" class="dropdown-item" style="display: none;">
                            <i class="fas fa-redo me-2"></i> {{__('main.Reopen Ticket')}}
                        </button>
                        <button id="addToBlacklist" type="button" class="dropdown-item">
                            <i class="fas fa-ban me-2"></i> {{__('main.Add to Blacklist')}}
                        </button>
                        <div class="dropdown-divider"></div>
                        <button id="deleteTicket" type="button" class="dropdown-item text-danger">
                            <i class="fa fa-trash me-2"></i> {{__('main.Delete')}}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        {{-- LEFT COLUMN --}}
        <div class="col-md-8">

            {{-- Subject + Replies --}}
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-comments me-2 text-muted flex-shrink-0"></i>
                    <div class="flex-grow-1 min-width-0">
                        <span id="ticket_subject_display" class="fw-bold"></span>
                        <input type="text" class="form-control form-control-sm" id="subject" name="subject" style="display:none;">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-icon-only ms-2 flex-shrink-0" id="editSubjectBtn">
                        <i class="fas fa-pencil-alt" id="editSubjectIcon"></i>
                    </button>
                </div>
                <div class="card-body" id="repliesContainer">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin"></i> {{__('main.Loading...')}}
                    </div>
                </div>
            </div>

            {{-- Draft Message Editor (anchor — editor moves here when not editing) --}}
            <div id="draftEditorAnchor" style="display: none;">
                <div id="draftEditorWrapper">
                    <textarea class="form-control" id="draft_message" name="draft_message" rows="10"></textarea>
                </div>
            </div>

            {{-- Activity Timeline --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#activityTimeline" aria-expanded="false">
                    <span><i class="fas fa-history me-1"></i> {{__('main.Activity Timeline')}}</span>
                    <i class="fas fa-chevron-down" id="activityToggleIcon"></i>
                </div>
                <div class="collapse" id="activityTimeline">
                    <div class="card-body py-2" id="activityTimelineBody">
                        <div class="text-muted text-center py-2"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                </div>
            </div>

            {{-- Reply Form --}}
            <div class="card mb-3" id="replyFormCard">
                <div class="card-header">
                    <i class="fas fa-reply me-1"></i> {{__('main.Reply')}}
                </div>
                <div class="card-body">
                    <form id="replyForm">
                        <div class="mb-3">
                            <label class="form-label" for="canned_response">{{__('main.Canned Response')}}</label>
                            <select id="canned_response" class="form-select" style="width: 100%;"></select>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" id="reply_message" name="message" rows="6"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="reply_time_spent">{{__('main.Time Spent (minutes)')}}</label>
                                    <input type="number" min="0" step="1" class="form-control"
                                           id="reply_time_spent" name="time_spent_minutes" value="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 pt-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="is_admin_note" name="is_admin_note" value="1">
                                        <label class="form-check-label" for="is_admin_note">{{__('main.Internal Note')}}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label" for="reply_attachments">{{__('main.Attachments')}}</label>
                                    <input type="file" class="form-control" id="reply_attachments"
                                           name="attachments" multiple>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button" id="submitReply" class="btn-icon btn-outline-2x btn btn-outline-success">
                                <i class="fas fa-paper-plane me-1"></i> {{__('main.Submit Reply')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-md-4">

            {{-- Client & Service Card --}}
            <div class="card mb-3" id="clientCard" style="display: none;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user me-1"></i> {{__('main.Client & Service')}}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-icon-only" id="editClientServiceBtn">
                        <i class="fas fa-pencil-alt" id="editClientServiceIcon"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="clientServiceDisplay">
                        <p><strong>{{__('main.Client')}}:</strong> <a href="#" id="client_link"><span id="client_name"></span></a></p>
                        <p><strong>{{__('main.Email')}}:</strong> <span id="client_email"></span></p>
                        <p class="mb-0" id="service_display_row" style="display: none;">
                            <strong>{{__('main.Service')}}:</strong> <span id="service_name"></span>
                        </p>
                    </div>
                    <div id="clientServiceEdit" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label" for="client_uuid">{{__('main.Client')}}</label>
                            <select name="client_uuid" id="client_uuid" class="form-select"></select>
                        </div>
                        <div class="mb-3" id="service_edit_field" style="display: none;">
                            <label for="service_uuid" class="form-label">{{__('main.Service')}}</label>
                            <select name="service_uuid" id="service_uuid" class="form-select"></select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- No Client Card --}}
            <div class="card mb-3" id="noClientCard" style="display: none;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user me-1"></i> {{__('main.Client & Service')}}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-icon-only" id="assignClientBtn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="noClientDisplay">
                        <p class="text-muted mb-0">{{__('main.No client assigned')}}</p>
                    </div>
                    <div id="noClientEdit" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label" for="no_client_select">{{__('main.Client')}}</label>
                            <select name="client_uuid" id="no_client_select" class="form-select"></select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ticket Info --}}
            <form id="ticketForm" novalidate="novalidate">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-1"></i> {{__('main.Ticket Info')}}
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label" for="status_uuid">{{__('main.Status')}}</label>
                            <select name="status_uuid" id="status_uuid"
                                    class="form-select mb-2 form-control"></select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="department_uuid">{{__('main.Department')}}</label>
                            <select name="department_uuid" id="department_uuid"
                                    class="form-select mb-2 form-control"></select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="priority">{{__('main.Priority')}}</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">{{__('main.Low')}}</option>
                                <option value="medium">{{__('main.Medium')}}</option>
                                <option value="high">{{__('main.High')}}</option>
                                <option value="urgent">{{__('main.Urgent')}}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="assigned_admin_uuid" class="form-label">{{__('main.Assigned To')}}</label>
                            <select name="assigned_admin_uuid" id="assigned_admin_uuid"
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
            </form>

            {{-- SLA --}}
            <div class="card mb-3" id="slaCard" style="display: none;">
                <div class="card-header">
                    <i class="fas fa-hourglass-half me-1"></i> {{__('main.SLA')}}
                </div>
                <div class="card-body text-center">
                    <div id="slaTimer" class="mb-1" style="font-size: 1.4rem; font-weight: bold;"></div>
                    <small class="text-muted" id="slaDeadlineText"></small>
                </div>
            </div>

            {{-- Time Tracking --}}
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-clock me-1"></i> {{__('main.Time Tracking')}}
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h4 id="total_time_spent">0</h4>
                        <small class="text-muted">{{__('main.Total Minutes Spent')}}</small>
                    </div>
                </div>
            </div>

            {{-- Ticket Attachments --}}
            <div class="card mb-3" id="ticketAttachmentsCard" style="display: none;">
                <div class="card-header">
                    <i class="fas fa-paperclip me-1"></i> {{__('main.Attachments')}}
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0" id="ticketAttachmentsList"></ul>
                </div>
            </div>

            {{-- Merge --}}
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-code-branch me-1"></i> {{__('main.Merge Ticket')}}
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="text" class="form-control" id="merge_ticket_uuid"
                               placeholder="{{__('main.Ticket UUID')}}">
                        <button type="button" class="btn btn-outline-warning" id="mergeBtn">
                            <i class="fas fa-code-branch"></i> {{__('main.Merge')}}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Feedback --}}
            <div class="card mb-3" id="feedbackCard" style="display: none;">
                <div class="card-header">
                    <i class="fas fa-star me-1"></i> {{__('main.Feedback')}}
                </div>
                <div class="card-body text-center">
                    <div id="feedbackStarsDisplay" class="mb-2"></div>
                    <div id="feedbackComment" class="text-muted small"></div>
                </div>
            </div>

            {{-- Related Tickets --}}
            <div class="card mb-3" id="relatedTicketsCard" style="display: none;">
                <div class="card-header">
                    <i class="fas fa-link me-1"></i> {{__('main.Related Tickets')}}
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" id="relatedTicketsList"></ul>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var ticketData = null;

            var getTicketUrl = '{{ route('admin.api.ticket.get', $uuid) }}';
            var putTicketUrl = '{{ route('admin.api.ticket.put', $uuid) }}';
            var deleteTicketUrl = '{{ route('admin.api.ticket.delete', $uuid) }}';
            var getRepliesUrl = '{{ route('admin.api.ticket.replies.get', $uuid) }}';
            var replyUrl = '{{ route('admin.api.ticket.reply.post', $uuid) }}';
            var noteUrl = '{{ route('admin.api.ticket.note.post', $uuid) }}';
            var mergeUrl = '{{ route('admin.api.ticket.merge.post', $uuid) }}';
            var attachmentUrl = '{{ route('admin.api.ticket_reply.attachment.post') }}';
            var attachmentDownloadUrl = '{{ route('admin.web.ticket.attachment.download', '__UUID__') }}';
            var imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            function isImage(filename) {
                if (!filename) return false;
                return imageExtensions.indexOf(filename.split('.').pop().toLowerCase()) !== -1;
            }
            var publishUrl = '{{ route('admin.api.ticket.publish.post', $uuid) }}';
            var ticketsListUrl = '{{ route('admin.web.tickets') }}';
            var statusesSelectUrl = '{{ route('admin.api.ticket_statuses.select.get') }}';
            var departmentsSelectUrl = '{{ route('admin.api.ticket_departments.select.get') }}';
            var adminsSelectUrl = '{{ route('admin.api.admins.select.get') }}';
            var clientsSelectUrl = '{{ route('admin.api.clients.select.get') }}';
            var servicesSelectUrlTemplate = '{{ route('admin.api.client.services.select.get', '__UUID__') }}';
            var replyPutUrlTemplate = '{{ route('admin.api.ticket_reply.put', '__UUID__') }}';

            // Initialize EasyMDE editors
            var easyMDE = new EasyMDE({
                element: document.getElementById('reply_message'),
                spellChecker: false,
                status: false,
                minHeight: '150px',
                toolbar: ['bold', 'italic', 'heading', '|', 'unordered-list', 'ordered-list', '|', 'link', 'quote', 'code', '|', 'preview', 'side-by-side', 'fullscreen', '|', 'guide']
            });

            var draftMDE = new EasyMDE({
                element: document.getElementById('draft_message'),
                spellChecker: false,
                status: false,
                minHeight: '200px',
                toolbar: ['bold', 'italic', 'heading', '|', 'unordered-list', 'ordered-list', '|', 'link', 'quote', 'code', '|', 'preview', 'side-by-side', 'fullscreen', '|', 'guide']
            });

            // ---------------------------------------------------------------
            // Canned Responses
            // ---------------------------------------------------------------
            var cannedResponseGetUrl = '{{ route('admin.api.ticket_canned_response.get', '__UUID__') }}';
            initializeSelect2(
                document.getElementById('canned_response'),
                '{{ route('admin.api.ticket_canned_responses.select.get') }}',
                null, 'GET', 1000,
                {allowClear: true, placeholder: translate('Select canned response...')}
            );

            $('#canned_response').on('change', function () {
                var uuid = $(this).val();
                if (!uuid) return;
                PUQajax(cannedResponseGetUrl.replace('__UUID__', uuid), {}, 5000, null, 'GET').then(function (response) {
                    if (response.data && response.data.message) {
                        var current = easyMDE.value();
                        easyMDE.value(current ? current + '\n\n' + response.data.message : response.data.message);
                    }
                    $('#canned_response').val(null).trigger('change');
                });
            });

            // ---------------------------------------------------------------
            // Helper: toggle edit button state (pencil ↔ check)
            // ---------------------------------------------------------------
            function setEditBtn($btn, editing) {
                var $icon = $btn.find('i');
                if (editing) {
                    $icon.removeClass('fa-pencil-alt fa-plus').addClass('fa-check');
                    $btn.removeClass('btn-outline-primary').addClass('btn-outline-success');
                } else {
                    $icon.removeClass('fa-check').addClass('fa-pencil-alt');
                    $btn.removeClass('btn-outline-success').addClass('btn-outline-primary');
                }
            }

            // ---------------------------------------------------------------
            // Subject: pencil → input replaces span, check → save
            // ---------------------------------------------------------------
            var subjectEditing = false;

            $('#editSubjectBtn').on('click', function () {
                var $btn = $(this);
                if (subjectEditing) {
                    var val = $('#subject').val();
                    if (!val || !val.trim()) return;
                    PUQajax(putTicketUrl, {subject: val}, 5000, $btn, 'PUT').then(function () {
                        ticketData.subject = val;
                        $('#ticket_subject_display').text(val).show();
                        $('#subject').hide();
                        subjectEditing = false;
                        setEditBtn($btn, false);
                    });
                } else {
                    subjectEditing = true;
                    $('#ticket_subject_display').hide();
                    $('#subject').show().focus().select();
                    setEditBtn($(this), true);
                }
            });

            $('#subject').on('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); $('#editSubjectBtn').click(); }
                if (e.key === 'Escape') {
                    subjectEditing = false;
                    $('#subject').hide();
                    $('#ticket_subject_display').show();
                    setEditBtn($('#editSubjectBtn'), false);
                }
            });

            // ---------------------------------------------------------------
            // Client & Service: pencil → edit selects, check → save
            // ---------------------------------------------------------------
            var clientEditing = false;

            $('#editClientServiceBtn').on('click', function () {
                var $btn = $(this);
                if (clientEditing) {
                    var newClientUuid = $('#client_uuid').val() || null;
                    var newServiceUuid = $('#service_uuid').val() || null;
                    var newClientText = newClientUuid ? $('#client_uuid').select2('data')[0].text : '';
                    var newServiceText = newServiceUuid ? $('#service_uuid').select2('data')[0].text : '';

                    PUQajax(putTicketUrl, {client_uuid: newClientUuid, service_uuid: newServiceUuid}, 5000, $btn, 'PUT').then(function () {
                        clientEditing = false;
                        setEditBtn($('#editClientServiceBtn'), false);

                        // Update display inline
                        ticketData.client_uuid = newClientUuid;
                        ticketData.service_data = newServiceUuid ? {id: newServiceUuid, text: newServiceText} : null;
                        ticketData.client_name = newClientText;

                        if (newClientUuid) {
                            $('#client_name').text(newClientText);
                            var clientUrl = window.routes.adminRedirect.replace('__label__', 'client').replace('__uuid__', newClientUuid);
                            $('#client_link').attr('href', clientUrl);
                            if (newServiceUuid) { $('#service_display_row').show(); $('#service_name').text(newServiceText); }
                            else { $('#service_display_row').hide(); }
                            $('#clientCard').show(); $('#noClientCard').hide();
                        } else {
                            $('#clientCard').hide(); $('#noClientCard').show();
                            $('#noClientDisplay').show(); $('#noClientEdit').hide();
                        }
                        $('#clientServiceDisplay').show();
                        $('#clientServiceEdit').hide();
                    });
                } else {
                    clientEditing = true;
                    $('#clientServiceDisplay').hide();
                    $('#clientServiceEdit').show();
                    setEditBtn($(this), true);

                    var cd = ticketData.client_uuid ? {id: ticketData.client_uuid, text: (ticketData.client_name || '')} : null;
                    initializeSelect2($('#client_uuid'), clientsSelectUrl, cd, 'GET', 1000, {allowClear: true, placeholder: translate('Select Client')});
                    if (ticketData.client_uuid) {
                        $('#service_edit_field').show();
                        initializeSelect2($('#service_uuid'), servicesSelectUrlTemplate.replace('__UUID__', ticketData.client_uuid), ticketData.service_data, 'GET', 1000, {allowClear: true, placeholder: translate('No specific product')});
                    } else {
                        $('#service_edit_field').hide();
                    }
                }
            });

            // Cascade: when client changes, destroy old service select2 and reinit
            $(document).on('change', '#client_uuid', function () {
                var uuid = $(this).val();
                if (uuid) {
                    $('#service_edit_field').show();
                    if ($('#service_uuid').data('select2')) {
                        $('#service_uuid').select2('destroy').empty();
                    }
                    initializeSelect2($('#service_uuid'), servicesSelectUrlTemplate.replace('__UUID__', uuid), null, 'GET', 1000, {allowClear: true, placeholder: translate('No specific product')});
                } else {
                    $('#service_edit_field').hide();
                    if ($('#service_uuid').data('select2')) {
                        $('#service_uuid').select2('destroy').empty();
                    }
                }
            });

            $('#assignClientBtn').on('click', function () {
                var $d = $('#noClientDisplay'), $e = $('#noClientEdit'), $btn = $(this);
                if ($e.is(':visible')) {
                    var newClientUuid = $('#no_client_select').val() || null;
                    if (!newClientUuid) return;
                    var newClientText = $('#no_client_select').select2('data')[0].text;
                    PUQajax(putTicketUrl, {client_uuid: newClientUuid}, 5000, $btn, 'PUT').then(function () {
                        ticketData.client_uuid = newClientUuid;
                        ticketData.client_name = newClientText;
                        setEditBtn($btn, false);
                        // Switch to client card
                        $('#noClientCard').hide();
                        $('#clientCard').show();
                        $('#client_name').text(newClientText);
                        var clientUrl = window.routes.adminRedirect.replace('__label__', 'client').replace('__uuid__', newClientUuid);
                        $('#client_link').attr('href', clientUrl);
                        $('#service_display_row').hide();
                        $('#clientServiceDisplay').show();
                        $('#clientServiceEdit').hide();
                    });
                } else {
                    $d.hide(); $e.show();
                    setEditBtn($(this), true);
                    initializeSelect2($('#no_client_select'), clientsSelectUrl, null, 'GET', 1000, {allowClear: true, placeholder: translate('Select Client')});
                }
            });

            // ---------------------------------------------------------------
            // Load ticket data
            // ---------------------------------------------------------------
            function loadFormData() {
                blockUI('ticketForm');

                PUQajax(getTicketUrl, {}, 5000, null, 'GET')
                    .then(function (response) {
                        ticketData = response.data;

                        $('#pageTitle').text(translate('Ticket') + ' #' + (ticketData.number || ''));

                        // Reset all edit states
                        subjectEditing = false;
                        clientEditing = false;
                        draftMessageEditing = false;
                        setEditBtn($('#editSubjectBtn'), false);
                        setEditBtn($('#editClientServiceBtn'), false);

                        $('#ticket_subject_display').text(ticketData.subject || '').show();
                        $('#subject').val(ticketData.subject || '').hide();
                        $('#priority').val(ticketData.priority || 'medium');
                        $('#cc_emails').val(Array.isArray(ticketData.cc_emails) ? (ticketData.cc_emails || []).join("\n") : (ticketData.cc_emails || ''));
                        $('#tags').val(Array.isArray(ticketData.tags) ? (ticketData.tags || []).join(', ') : (ticketData.tags || ''));
                        $('#admin_notes').val(ticketData.admin_notes || '');
                        $('#total_time_spent').text(ticketData.total_time_spent || 0);

                        // SLA Card
                        if (ticketData.sla) {
                            $('#slaCard').show();
                            var slaDeadline = new Date(ticketData.sla.deadline);
                            $('#slaDeadlineText').text(translate('Deadline') + ': ' + slaDeadline.toLocaleString());

                            function updateSlaTimer() {
                                var now = new Date();
                                var diff = slaDeadline - now;
                                var absDiff = Math.abs(diff);
                                var h = Math.floor(absDiff / 3600000);
                                var m = Math.floor((absDiff % 3600000) / 60000);
                                var timeStr = h > 0 ? h + 'h ' + m + 'm' : m + 'm';

                                if (diff < 0) {
                                    $('#slaTimer').html('<span class="text-danger">' + translate('Overdue') + ' ' + timeStr + '</span>');
                                    $('#slaCard').removeClass('border-success').addClass('border-danger');
                                } else {
                                    $('#slaTimer').html('<span class="text-success">' + timeStr + ' ' + translate('remaining') + '</span>');
                                    $('#slaCard').removeClass('border-danger').addClass('border-success');
                                }
                            }

                            updateSlaTimer();
                            setInterval(updateSlaTimer, 60000);
                        } else {
                            $('#slaCard').hide();
                        }

                        initializeSelect2($('#status_uuid'), statusesSelectUrl, ticketData.status_data, 'GET', 1000, {});
                        initializeSelect2($('#department_uuid'), departmentsSelectUrl, ticketData.department_data, 'GET', 1000, {});
                        initializeSelect2($('#assigned_admin_uuid'), adminsSelectUrl, ticketData.assigned_admin_data, 'GET', 1000, {
                            allowClear: true, placeholder: translate('Unassigned')
                        });

                        // Client & Service
                        if (ticketData.client_uuid) {
                            $('#clientCard').show(); $('#noClientCard').hide();
                            $('#client_name').text(ticketData.client_name || '');
                            $('#client_email').text(ticketData.client_email || '');
                            var clientUrl = window.routes.adminRedirect.replace('__label__', 'client').replace('__uuid__', ticketData.client_uuid);
                            $('#client_link').attr('href', clientUrl);
                            if (ticketData.service_data) { $('#service_display_row').show(); $('#service_name').text(ticketData.service_data.text || ''); }
                            else { $('#service_display_row').hide(); }
                            $('#clientServiceDisplay').show(); $('#clientServiceEdit').hide();
                        } else {
                            $('#clientCard').hide(); $('#noClientCard').show();
                            $('#noClientDisplay').show(); $('#noClientEdit').hide();
                        }

                        var isClosed = ticketData.status && ticketData.status.title === 'Closed';
                        var isDraft = ticketData.is_draft || false;
                        $('#closeTicket').toggle(!isClosed && !isDraft);
                        $('#reopenTicket').toggle(isClosed);
                        $('#save').show();
                        $('#publishTicket').toggle(isDraft);
                        $('#replyFormCard').toggle(!isDraft);
                        $('#scrollToReply').toggle(!isDraft);

                        // Attachments
                        if (ticketData.attachments && ticketData.attachments.length > 0) {
                            $('#ticketAttachmentsCard').show();
                            var attHtml = '';
                            ticketData.attachments.forEach(function (att) {
                                attHtml += '<li><a href="' + (att.download_url || '#') + '"><i class="fas fa-file me-1"></i>' + (att.name || '') + '</a>';
                                if (att.size) attHtml += ' <small class="text-muted">(' + Math.round(att.size / 1024) + ' KB)</small>';
                                attHtml += '</li>';
                            });
                            $('#ticketAttachmentsList').html(attHtml);
                        } else { $('#ticketAttachmentsCard').hide(); }

                        // Feedback
                        if (ticketData.feedback) {
                            var stars = '';
                            for (var s = 1; s <= 5; s++) {
                                stars += '<i class="fa fa-star fa-lg ' + (s <= ticketData.feedback.rating ? 'text-warning' : 'text-muted') + ' mx-1"></i>';
                            }
                            $('#feedbackStarsDisplay').html(stars);
                            $('#feedbackComment').text(ticketData.feedback.comment || '');
                            $('#feedbackCard').show();
                        } else {
                            $('#feedbackCard').hide();
                        }

                        // Related tickets
                        if (ticketData.related_tickets && ticketData.related_tickets.length > 0) {
                            var rtHtml = '';
                            ticketData.related_tickets.forEach(function (rt) {
                                var badge = rt.status ? '<span class="badge ms-1" style="background-color:' + rt.status.color + ';">' + rt.status.title + '</span>' : '';
                                rtHtml += '<li class="list-group-item py-1 px-3"><a href="' + rt.url + '">#' + rt.number + '</a> <small>' + rt.subject + '</small> ' + badge + '</li>';
                            });
                            $('#relatedTicketsList').html(rtHtml);
                            $('#relatedTicketsCard').show();
                        } else {
                            $('#relatedTicketsCard').hide();
                        }

                        unblockUI('ticketForm');
                        loadReplies();
                    })
                    .catch(function (error) {
                        console.error('Error loading ticket data:', error);
                        unblockUI('ticketForm');
                    });
            }

            // ---------------------------------------------------------------
            // Load and render replies
            // ---------------------------------------------------------------
            function loadReplies() {
                parkDraftEditor();
                var $container = $('#repliesContainer');
                $container.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> ' + translate('Loading...') + '</div>');

                PUQajax(getRepliesUrl, {}, 5000, null, 'GET')
                    .then(function (response) {
                        var replies = response.data || [];
                        var isDraft = ticketData && ticketData.is_draft;

                        if (isDraft && replies.length > 0) {
                            draftMDE.value(replies[0].message || '');
                            window._draftReplyUuid = replies[0].uuid;
                        }

                        renderReplies(replies, isDraft);
                    })
                    .catch(function (error) {
                        console.error('Error loading replies:', error);
                        $container.html('<div class="text-center text-danger py-4">' + translate('Error loading replies') + '</div>');
                    });
            }

            function renderReplies(replies, isDraft) {
                var $container = $('#repliesContainer');

                if (!replies || replies.length === 0) {
                    $container.html('<div class="text-center text-muted py-4">' + translate('No replies yet') + '</div>');
                    return;
                }

                var html = '';
                replies.forEach(function (reply, index) {
                    var isNote = !!reply.is_admin_note;
                    var cssClass = isNote ? 'admin-note' : (reply.admin_uuid ? 'admin-reply' : 'client-reply');

                    html += '<div class="reply-item ' + cssClass + '" id="reply-item-' + reply.uuid + '">';

                    // Header
                    html += '<div class="reply-header">';
                    html += '<div>';
                    html += '<strong>' + (reply.name || '') + '</strong>';
                    if (reply.email) html += ' <small class="text-muted">&lt;' + reply.email + '&gt;</small>';
                    if (reply.admin_uuid) html += ' <span class="badge bg-primary ms-1">' + translate('Admin') + '</span>';
                    else html += ' <span class="badge bg-secondary ms-1">' + translate('Client') + '</span>';
                    if (isNote) html += ' <span class="badge bg-warning text-dark ms-1">' + translate('Internal Note') + '</span>';
                    html += '</div>';
                    html += '<div class="d-flex align-items-center">';
                    if (reply.time_spent_minutes && parseInt(reply.time_spent_minutes) > 0) {
                        html += '<span class="badge bg-info me-2"><i class="fas fa-clock me-1"></i>' + reply.time_spent_minutes + ' ' + translate('min') + '</span>';
                    }
                    html += '<small class="text-muted">' + (reply.created_at ? formatDateWithoutTimezone(reply.created_at) : '') + '</small>';
                    if (isDraft && index === 0) {
                        html += '<button type="button" class="btn btn-sm btn-outline-primary btn-icon-only ms-2 edit-draft-reply-btn" data-reply-uuid="' + reply.uuid + '" title="' + translate('Edit') + '"><i class="fas fa-pencil-alt"></i></button>';
                    }
                    html += '</div>';
                    html += '</div>';

                    // Body
                    html += '<div class="reply-body" id="reply-body-' + reply.uuid + '">' + (typeof marked !== 'undefined' ? marked.parse(reply.message || '') : (reply.message || '')) + '</div>';
                    html += '<div class="reply-editor-wrapper" id="reply-editor-' + reply.uuid + '" style="display:none;"></div>';

                    // Attachments
                    if (reply.attachments && reply.attachments.length > 0) {
                        html += '<div class="mt-2 pt-2 border-top">';
                        html += '<small class="text-muted d-block mb-2"><i class="fas fa-paperclip me-1"></i>' + translate('Attachments') + ':</small>';
                        html += '<div class="d-flex flex-wrap gap-2 align-items-start">';
                        reply.attachments.forEach(function (att) {
                            var dlUrl = attachmentDownloadUrl.replace('__UUID__', att.uuid);
                            var viewUrl = dlUrl + '?view=1';
                            var name = att.filename || att.name || '';
                            if (isImage(name)) {
                                html += '<div style="display:inline-flex;flex-direction:column;align-items:center;max-width:140px;">';
                                html += '<a href="' + dlUrl + '" style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;display:block;text-align:center;" class="mb-1"><i class="fas fa-download me-1"></i>' + name + '</a>';
                                html += '<div class="admin-att-thumb" data-view-url="' + viewUrl + '" data-download-url="' + dlUrl + '" data-name="' + name + '" style="cursor:pointer;border:1px solid #dee2e6;border-radius:4px;overflow:hidden;">';
                                html += '<img src="' + viewUrl + '" alt="' + name + '" style="display:block;max-width:120px;max-height:80px;object-fit:cover;">';
                                html += '</div></div>';
                            } else {
                                html += '<a href="' + dlUrl + '" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download me-1"></i>' + name + '</a>';
                            }
                        });
                        html += '</div></div>';
                    }

                    html += '</div>';
                });

                $container.html(html);
            }

            // ---------------------------------------------------------------
            // Draft reply inline edit — pencil toggles to check
            // ---------------------------------------------------------------
            var draftMessageEditing = false;

            // Return editor wrapper to its safe anchor (before DOM is rebuilt)
            function parkDraftEditor() {
                $('#draftEditorWrapper').detach().appendTo('#draftEditorAnchor');
                draftMessageEditing = false;
            }

            $(document).on('click', '.edit-draft-reply-btn', function () {
                var uuid = $(this).data('reply-uuid');
                var $btn = $(this);

                if (draftMessageEditing) {
                    // Save
                    draftMDE.codemirror.save();
                    var msg = draftMDE.value();
                    if (!msg || !msg.trim()) return;

                    PUQajax(replyPutUrlTemplate.replace('__UUID__', uuid), {message: msg}, 5000, $btn, 'PUT')
                        .then(function () {
                            parkDraftEditor();
                            loadReplies();
                        });
                } else {
                    // Open editor
                    draftMessageEditing = true;
                    setEditBtn($btn, true);

                    var $body = $('#reply-body-' + uuid);
                    var $editor = $('#reply-editor-' + uuid);
                    $body.hide();
                    $editor.show();
                    $('#draftEditorWrapper').detach().appendTo($editor).show();
                    setTimeout(function () { draftMDE.codemirror.refresh(); }, 50);
                }
            });

            // ---------------------------------------------------------------
            // Attachments helper
            // ---------------------------------------------------------------
            function handleAttachments(replyUuid) {
                var fileInput = document.getElementById('reply_attachments');
                if (!fileInput.files || fileInput.files.length === 0) {
                    loadReplies(); resetReplyForm(); return;
                }
                var uploaded = 0;
                var total = fileInput.files.length;
                for (var i = 0; i < total; i++) {
                    var fd = new FormData();
                    fd.append('reply_uuid', replyUuid);
                    fd.append('file', fileInput.files[i]);
                    $.ajax({
                        url: attachmentUrl,
                        method: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false,
                        headers: { 'Authorization': 'Bearer ' + $('meta[name="api_token"]').attr('content') },
                        complete: function () {
                            uploaded++;
                            if (uploaded >= total) { loadReplies(); resetReplyForm(); }
                        }
                    });
                }
            }

            // Image preview
            $(document).on('click', '.admin-att-thumb', function (e) {
                e.preventDefault();
                var viewUrl = $(this).data('view-url');
                var downloadUrl = $(this).data('download-url');
                var name = $(this).data('name');
                var $modal = $('#universalModal');
                $modal.find('.modal-dialog').removeClass('modal-sm').addClass('modal-xl');
                $modal.find('#universalModalLabel').text(name);
                $modal.find('.modal-body').html(
                    '<div class="text-center p-2"><img src="' + viewUrl + '" style="max-width:100%;max-height:75vh;object-fit:contain;"></div>'
                );
                $modal.find('#modalConfirmButton').hide();
                $modal.find('.modal-footer').find('.att-modal-download').remove();
                $modal.find('.modal-footer').prepend(
                    '<a href="' + downloadUrl + '" class="att-modal-download mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-primary">' +
                    '<i class="fa fa-download"></i> ' + translate('Download') + '</a>'
                );
                $modal.modal('show');
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                $(this).find('.modal-dialog').removeClass('modal-xl');
                $(this).find('.modal-body').html('');
                $(this).find('#modalConfirmButton').show();
                $(this).find('.att-modal-download').remove();
            });

            // ---------------------------------------------------------------
            // Activity Timeline
            // ---------------------------------------------------------------
            var activityUrl = '{{ route('admin.api.ticket.activity.get', $uuid) }}';
            var activityLoaded = false;

            var fieldLabels = {
                'status_uuid': translate('Status'),
                'assigned_admin_uuid': translate('Assigned To'),
                'department_uuid': translate('Department'),
                'priority': translate('Priority'),
                'subject': translate('Subject'),
                'closed_at': translate('Closed'),
            };

            function formatActivityChange(key, value) {
                if (value === null || value === undefined || value === '') return '-';
                if (key === 'priority') {
                    return translate(String(value).charAt(0).toUpperCase() + String(value).slice(1));
                }
                return String(value);
            }

            $('#activityTimeline').on('show.bs.collapse', function () {
                $('#activityToggleIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                if (activityLoaded) return;
                PUQajax(activityUrl, {}, 5000, null, 'GET').then(function (response) {
                    activityLoaded = true;
                    var logs = response.data || [];
                    if (logs.length === 0) {
                        $('#activityTimelineBody').html('<div class="text-muted text-center py-2">' + translate('No activity') + '</div>');
                        return;
                    }
                    var html = '<div class="list-group list-group-flush">';
                    logs.forEach(function (log) {
                        var icon, changes = '';
                        switch (log.action) {
                            case 'create':
                                icon = 'fa-plus text-success';
                                changes = '<div class="small text-success">' + translate('Ticket created') + '</div>';
                                break;
                            case 'delete':
                                icon = 'fa-trash text-danger';
                                break;
                            case 'reply_staff':
                                icon = 'fa-reply text-primary';
                                changes = '<div class="small text-primary">' + translate('Staff reply') + '</div>';
                                break;
                            case 'reply_client':
                                icon = 'fa-comment text-success';
                                changes = '<div class="small text-success">' + translate('Client reply') + '</div>';
                                break;
                            case 'note':
                                icon = 'fa-sticky-note text-warning';
                                changes = '<div class="small text-warning">' + translate('Internal note') + '</div>';
                                break;
                            default:
                                icon = 'fa-pencil-alt text-info';
                                if (log.new_data) {
                                    Object.keys(log.new_data).forEach(function (key) {
                                        if (['updated_at', 'last_reply_at', 'time_spent_minutes'].indexOf(key) !== -1) return;
                                        var label = fieldLabels[key] || key;
                                        var oldVal = log.old_data ? formatActivityChange(key, log.old_data[key]) : '-';
                                        var newVal = formatActivityChange(key, log.new_data[key]);
                                        changes += '<div class="small"><strong>' + label + ':</strong> <span class="text-muted">' + oldVal + '</span> → <span>' + newVal + '</span></div>';
                                    });
                                }
                        }
                        html += '<div class="list-group-item px-2 py-1">';
                        html += '<div class="d-flex justify-content-between align-items-center">';
                        html += '<div><i class="fas ' + icon + ' me-2"></i><small class="fw-bold">' + (log.admin_name || translate('System')) + '</small></div>';
                        html += '<small class="text-muted">' + (log.created_at ? formatDateWithoutTimezone(log.created_at) : '') + '</small>';
                        html += '</div>';
                        html += changes;
                        html += '</div>';
                    });
                    html += '</div>';
                    $('#activityTimelineBody').html(html);
                });
            });

            $('#activityTimeline').on('hide.bs.collapse', function () {
                $('#activityToggleIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });

            function resetReplyForm() {
                easyMDE.value('');
                easyMDE.codemirror.refresh();
                $('#reply_time_spent').val(0);
                $('#is_admin_note').prop('checked', false);
                $('#reply_attachments').val('');
                $('#replySpinner').remove();
                $('#replyForm').show();
            }

            function showReplySpinner() {
                $('#replyForm').hide();
                $('#replyFormCard .card-body').append(
                    '<div id="replySpinner" class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i>' +
                    '<div class="mt-2 text-muted">' + translate('Loading...') + '</div></div>'
                );
            }

            // ---------------------------------------------------------------
            // Submit reply / note
            // ---------------------------------------------------------------
            $(document).on('click', '#submitReply', function (event) {
                event.preventDefault();
                var $btn = $(this);
                easyMDE.codemirror.save();
                var message = easyMDE.value();
                if (!message || !message.trim()) return;

                var isNote = $('#is_admin_note').is(':checked');
                var confirmMsg = isNote ? translate('Are you sure you want to add this internal note?') : translate('Are you sure you want to send this reply?');

                handleConfirmAction({
                    message: confirmMsg,
                    style: isNote ? 'warning' : 'info',
                    onConfirm: function () {
                        var formData = { message: message, time_spent_minutes: $('#reply_time_spent').val() || 0 };
                        easyMDE.value('');
                        $('#reply_message').val('');
                        showReplySpinner();

                        if (isNote) {
                            PUQajax(noteUrl, formData, 5000, null, 'POST').then(function (r) { handleAttachments(r.data.uuid); });
                        } else {
                            PUQajax(replyUrl, formData, 5000, null, 'POST').then(function (r) { handleAttachments(r.data.uuid); });
                        }
                    }
                });
            });

            // ---------------------------------------------------------------
            // Save ticket metadata
            // ---------------------------------------------------------------
            $('#save').on('click', function (event) {
                event.preventDefault();
                var $form = $('#ticketForm');
                var formData = serializeForm($form);

                PUQajax(putTicketUrl, formData, 5000, $(this), 'PUT', $form).then(function () { loadFormData(); });
            });

            // ---------------------------------------------------------------
            // Scroll to reply
            // ---------------------------------------------------------------
            $('#scrollToReply').on('click', function () {
                $('html, body').animate({ scrollTop: $('#replyFormCard').offset().top - 80 }, 300);
                $('#reply_message').focus();
            });

            // ---------------------------------------------------------------
            // Close / Reopen / Publish / Delete / Merge
            // ---------------------------------------------------------------
            $('#closeTicket').on('click', function () {
                handleConfirmAction({ message: 'Are you sure you want to close this ticket?', style: 'warning', onConfirm: function () {
                    PUQajax(statusesSelectUrl, {}, 1000, null, 'GET').then(function (r) {
                        var id = null;
                        if (r.data) r.data.forEach(function (s) { if (s.text === 'Closed') id = s.id; });
                        if (id) PUQajax(putTicketUrl, {status_uuid: id}, 5000, null, 'PUT').then(function () { loadFormData(); loadReplies(); });
                    });
                }});
            });

            $('#reopenTicket').on('click', function () {
                handleConfirmAction({ message: 'Are you sure you want to reopen this ticket?', style: 'info', onConfirm: function () {
                    PUQajax(statusesSelectUrl, {}, 1000, null, 'GET').then(function (r) {
                        var id = null;
                        if (r.data) r.data.forEach(function (s) { if (s.text === 'Open') id = s.id; });
                        if (id) PUQajax(putTicketUrl, {status_uuid: id}, 5000, null, 'PUT').then(function () { loadFormData(); loadReplies(); });
                    });
                }});
            });

            $('#publishTicket').on('click', function () {
                handleConfirmAction({ message: translate('Publish this ticket? This will send notifications and set status to Open.'), style: 'info', onConfirm: function () {
                    PUQajax(publishUrl, {}, 5000, null, 'POST').then(function () { loadFormData(); loadReplies(); });
                }});
            });

            $('#deleteTicket').on('click', function () {
                handleConfirmAction({ message: 'Are you sure you want to delete this ticket?', style: 'danger', onConfirm: function () {
                    PUQajax(deleteTicketUrl, {}, 5000, null, 'DELETE').then(function () { window.location.href = ticketsListUrl; });
                }});
            });

            $('#addToBlacklist').on('click', function () {
                var email = ticketData.email || ticketData.client_email || '';
                if (!email) return;
                handleConfirmAction({
                    message: translate('Are you sure you want to blacklist this email?') + ' (' + email + ')',
                    style: 'warning',
                    onConfirm: function () {
                        PUQajax('{{ route('admin.api.ticket_email_blacklist.post') }}', {email: email, reason: 'Blacklisted from ticket #' + ticketData.number}, 5000, null, 'POST');
                    }
                });
            });

            $('#mergeBtn').on('click', function () {
                var mergeUuid = $('#merge_ticket_uuid').val();
                if (!mergeUuid) return;
                handleConfirmAction({ message: 'Are you sure you want to merge this ticket?', style: 'warning', onConfirm: function () {
                    PUQajax(mergeUrl, {merge_with_uuid: mergeUuid}, 5000, null, 'POST').then(function () {
                        $('#merge_ticket_uuid').val(''); loadReplies(); loadFormData();
                    });
                }});
            });

            // ---------------------------------------------------------------
            // Init
            // ---------------------------------------------------------------
            loadFormData();
        });
    </script>
@endsection
