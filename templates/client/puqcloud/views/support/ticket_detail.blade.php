@extends(config('template.client.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        .ticket-reply-card {
            margin-bottom: 1rem;
        }
        .ticket-reply-card.reply-staff {
            border-left: 4px solid #0d6efd;
        }
        .ticket-reply-card.reply-client {
            border-left: 4px solid #198754;
        }
        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .reply-attachments {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: flex-start;
        }
        .attachment-item {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            max-width: 140px;
        }
        .attachment-item .att-download {
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
            display: block;
            text-align: center;
        }
        .attachment-thumb {
            cursor: pointer;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .attachment-thumb:hover {
            border-color: #0d6efd;
        }
        .attachment-thumb img {
            display: block;
            max-width: 120px;
            max-height: 80px;
            object-fit: cover;
        }
    </style>
@endsection

@section('content')

    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fas fa-life-ring icon-gradient bg-tempting-azure"></i>
                </div>
                <div>
                    <span id="ticketTitle">{{ __('main.Ticket') }}</span>
                    <div class="page-title-subheading text-muted">
                        <span id="ticketDepartment"></span>
                        <span id="ticketStatus" class="ms-2"></span>
                        <span id="ticketPriority" class="ms-2"></span>
                        <span id="ticketAssignedTo" class="ms-2"></span>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="reopenTicketBtn" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success d-none">
                    <i class="fa fa-envelope-open me-1"></i> {{ __('main.Reopen Ticket') }}
                </button>
                <button id="closeTicketBtn" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-danger d-none">
                    <i class="fa fa-times-circle me-1"></i> {{ __('main.Close Ticket') }}
                </button>
                <a href="{{ route('client.web.panel.support.tickets') }}" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
                    <i class="fa fa-arrow-left me-1"></i> {{ __('main.Back to Tickets') }}
                </a>
            </div>
        </div>
    </div>

    <div class="container px-0">

        <div id="closedAlert" class="alert alert-secondary d-none" role="alert">
            <i class="fa fa-lock me-1"></i>
            {{ __('main.This ticket is closed.') }}
        </div>

        <div id="ticketReplies">
            <div class="text-center py-4">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
            </div>
        </div>

        <div id="replySection" class="card shadow-sm mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fa fa-reply me-1"></i> {{ __('main.Reply') }}</h6>
            </div>
            <div class="card-body">
                <form id="replyForm">
                    <div class="mb-3">
                        <textarea id="replyMessage" name="message" class="form-control" rows="5" maxlength="50000" placeholder="{{ __('main.Type your reply here...') }}" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-paperclip me-1"></i>{{ __('main.Attachments') }}</label>
                        <div id="fileInputsContainer"></div>
                        <small id="fileSettingsHint" class="form-text text-muted d-none"></small>
                    </div>
                    <div class="text-end">
                        <button type="submit" id="submitReply" class="btn-icon btn-outline-2x btn btn-outline-success">
                            <i class="fa fa-paper-plane me-1"></i> {{ __('main.Submit Reply') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var ticketUuid = '{{ $uuid }}';
            var apiUrl = '{{ route('client.api.support.ticket.get', '__UUID__') }}'.replace('__UUID__', ticketUuid);
            var replyUrl = '{{ route('client.api.support.ticket.reply.post', '__UUID__') }}'.replace('__UUID__', ticketUuid);
            var closeUrl = '{{ route('client.api.support.ticket.close.post', '__UUID__') }}'.replace('__UUID__', ticketUuid);
            var reopenUrl = '{{ route('client.api.support.ticket.reopen.post', '__UUID__') }}'.replace('__UUID__', ticketUuid);
            var attachmentUploadUrl = '{{ route('client.api.support.ticket.attachment.post') }}';
            var attachmentDownloadUrl = '{{ route('client.web.panel.support.ticket.attachment.download', '__UUID__') }}';

            var fileSettings = {};
            var feedbackOnClose = false;
            var replyFormHtml = $('#replySection .card-body').html();
            var imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];

            function isImage(filename) {
                if (!filename) return false;
                var ext = filename.split('.').pop().toLowerCase();
                return imageExtensions.indexOf(ext) !== -1;
            }

            function buildFileInputs(count) {
                var $container = $('#fileInputsContainer');
                $container.empty();
                var max = count || 3;
                for (var i = 0; i < max; i++) {
                    $container.append(
                        '<input type="file" name="attachment_' + i + '" class="form-control mb-1 reply-file-input">'
                    );
                }
            }

            function getSelectedFiles() {
                var files = [];
                $('.reply-file-input').each(function () {
                    if (this.files && this.files.length > 0) {
                        files.push(this.files[0]);
                    }
                });
                return files;
            }

            function loadTicket() {
                $.ajax({
                    url: apiUrl,
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + $('meta[name="api_token"]').attr('content')
                    },
                    success: function (response) {
                        renderTicket(response.data);
                    },
                    error: function () {
                        $('#ticketReplies').html('<div class="alert alert-danger">{{ __('error.Failed to load ticket') }}</div>');
                    }
                });
            }

            function renderTicket(data) {
                $('#ticketTitle').text('{{ __('main.Ticket') }} #' + data.number + ' - ' + data.subject);

                if (data.department) {
                    $('#ticketDepartment').html('<span class="badge bg-secondary">' + escapeHtml(data.department.name) + '</span>');
                }

                if (data.status) {
                    $('#ticketStatus').html('<span class="badge" style="background-color: ' + data.status.color + ';">' + escapeHtml(data.status.title) + '</span>');
                }

                if (data.priority) {
                    var priorityColors = {'low': 'bg-info', 'medium': 'bg-warning', 'high': 'bg-danger'};
                    var cls = priorityColors[data.priority] || 'bg-secondary';
                    var label = translate(data.priority.charAt(0).toUpperCase() + data.priority.slice(1));
                    $('#ticketPriority').html('<span class="badge ' + cls + '">' + label + '</span>');
                }

                feedbackOnClose = !!data.feedback_on_close;

                if (data.assigned_admin_name) {
                    $('#ticketAssignedTo').html('<i class="fas fa-user-shield me-1"></i>' + escapeHtml(data.assigned_admin_name));
                } else {
                    $('#ticketAssignedTo').html('');
                }

                if (data.is_closed) {
                    $('#closedAlert').removeClass('d-none');
                    $('#replySection').addClass('d-none');
                    $('#closeTicketBtn').addClass('d-none');
                    if (data.allow_reopen) {
                        $('#reopenTicketBtn').removeClass('d-none');
                    } else {
                        $('#reopenTicketBtn').addClass('d-none');
                    }
                } else {
                    $('#closedAlert').addClass('d-none');
                    $('#replySection .card-body').html(replyFormHtml);
                    $('#replySection').removeClass('d-none');
                    $('#closeTicketBtn').removeClass('d-none');
                    $('#reopenTicketBtn').addClass('d-none');
                }

                if (data.file_settings) {
                    fileSettings = data.file_settings;
                    var hints = [];
                    if (fileSettings.allowed_extensions) {
                        hints.push('{{ __('main.Allowed') }}: ' + fileSettings.allowed_extensions);
                    }
                    if (fileSettings.max_file_size_mb) {
                        hints.push('{{ __('main.Max size') }}: ' + fileSettings.max_file_size_mb + ' MB');
                    }
                    if (fileSettings.max_files_count) {
                        hints.push('{{ __('main.Max files') }}: ' + fileSettings.max_files_count);
                    }
                    if (hints.length > 0) {
                        $('#fileSettingsHint').text(hints.join(' | ')).removeClass('d-none');
                    }

                    buildFileInputs(fileSettings.max_files_count || 3);
                } else {
                    buildFileInputs(3);
                }

                var html = '';
                if (data.replies && data.replies.length > 0) {
                    data.replies.forEach(function (reply) {
                        var isStaff = reply.admin_uuid && reply.admin_uuid !== null;
                        var cardClass = isStaff ? 'reply-staff' : 'reply-client';
                        var badge = isStaff
                            ? '<span class="badge bg-primary">' + translate('Staff') + '</span>'
                            : '<span class="badge bg-success">' + translate('You') + '</span>';

                        html += '<div class="card shadow-sm ticket-reply-card ' + cardClass + '">';
                        html += '<div class="card-body">';
                        html += '<div class="reply-header mb-2">';
                        html += '<div><strong>' + escapeHtml(reply.name || '') + '</strong> ' + badge + '</div>';
                        html += '<small class="text-muted">' + (reply.created_at ? formatDateWithoutTimezone(reply.created_at) : '') + '</small>';
                        html += '</div>';
                        html += '<div class="reply-message mb-2">' + (typeof marked !== 'undefined' ? marked.parse(reply.message || '') : (reply.message || '')) + '</div>';

                        if (reply.attachments && reply.attachments.length > 0) {
                            html += '<div class="reply-attachments mt-2 pt-2 border-top">';
                            html += '<small class="text-muted d-block mb-2"><i class="fa fa-paperclip me-1"></i>{{ __('main.Attachments') }}:</small>';
                            reply.attachments.forEach(function (att) {
                                var downloadUrl = attachmentDownloadUrl.replace('__UUID__', att.uuid);
                                var viewUrl = downloadUrl + '?view=1';
                                if (isImage(att.name)) {
                                    html += '<div class="attachment-item">';
                                    html += '<a href="' + downloadUrl + '" class="att-download mb-1"><i class="fa fa-download me-1"></i>' + escapeHtml(att.name) + '</a>';
                                    html += '<div class="attachment-thumb" data-view-url="' + viewUrl + '" data-download-url="' + downloadUrl + '" data-name="' + escapeHtml(att.name) + '">';
                                    html += '<img src="' + viewUrl + '" alt="' + escapeHtml(att.name) + '">';
                                    html += '</div>';
                                    html += '</div>';
                                } else {
                                    html += '<a href="' + downloadUrl + '" class="btn btn-sm btn-outline-secondary">';
                                    html += '<i class="fa fa-download me-1"></i>' + escapeHtml(att.name);
                                    html += '</a>';
                                }
                            });
                            html += '</div>';
                        }

                        html += '</div></div>';
                    });
                } else {
                    html = '<div class="alert alert-info">' + translate('No replies yet') + '</div>';
                }

                $('#ticketReplies').html(html);
            }

            // Image preview via universalModal
            $(document).on('click', '.attachment-thumb', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var viewUrl = $(this).data('view-url');
                var downloadUrl = $(this).data('download-url');
                var name = $(this).data('name');

                var $modal = $('#universalModal');
                $modal.find('.modal-dialog').removeClass('modal-sm').addClass('modal-xl');
                $modal.find('#universalModalLabel').text(name);
                $modal.find('.modal-body').html(
                    '<div class="text-center p-2">' +
                    '<img src="' + viewUrl + '" alt="' + escapeHtml(name) + '" style="max-width:100%; max-height:75vh; object-fit:contain;">' +
                    '</div>'
                );
                $modal.find('#modalConfirmButton').hide();
                $modal.find('#modalCloseButton').show();

                var $footer = $modal.find('.modal-footer');
                $footer.find('.att-modal-download').remove();
                $footer.prepend(
                    '<a href="' + downloadUrl + '" class="att-modal-download mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-primary">' +
                    '<i class="fa fa-download"></i> {{ __('main.Download') }}' +
                    '</a>'
                );

                $modal.modal('show');
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                var $modal = $(this);
                $modal.find('.modal-dialog').removeClass('modal-xl');
                $modal.find('.modal-body').html('');
                $modal.find('#modalConfirmButton').show()
                    .removeClass('btn-outline-danger btn-outline-success btn-outline-warning')
                    .addClass('btn-outline-success');
                $modal.find('.att-modal-download').remove();
            });

            // Close ticket
            $('#closeTicketBtn').on('click', function () {
                var $btn = $(this);

                if (feedbackOnClose) {
                    var $modal = $('#universalModal');
                    $modal.find('#universalModalLabel').text('{{ __('main.Close Ticket') }}');
                    $modal.find('.modal-body').html(
                        '<p>{{ __('main.Please rate your experience') }}</p>' +
                        '<div class="text-center mb-3" id="feedbackStars">' +
                        [1,2,3,4,5].map(function(i) {
                            return '<i class="fa fa-star fa-2x text-muted mx-1" data-rating="' + i + '" style="cursor:pointer;"></i>';
                        }).join('') +
                        '</div>' +
                        '<input type="hidden" id="feedbackRating" value="0">' +
                        '<textarea id="feedbackComment" class="form-control" rows="3" placeholder="{{ __('main.Comment (optional)') }}"></textarea>'
                    );
                    $modal.find('#modalConfirmButton').show().text('{{ __('main.Close Ticket') }}');
                    $modal.find('#modalCloseButton').show();

                    // Star click handler
                    $modal.find('#feedbackStars .fa-star').on('click', function () {
                        var rating = $(this).data('rating');
                        $('#feedbackRating').val(rating);
                        $modal.find('#feedbackStars .fa-star').each(function () {
                            $(this).toggleClass('text-warning', $(this).data('rating') <= rating)
                                   .toggleClass('text-muted', $(this).data('rating') > rating);
                        });
                    });

                    $modal.find('#modalConfirmButton').off('click.feedback').on('click.feedback', function () {
                        var rating = parseInt($('#feedbackRating').val()) || 0;
                        var comment = $('#feedbackComment').val() || '';
                        var data = {};
                        if (rating > 0) { data.rating = rating; data.comment = comment; }
                        $modal.modal('hide');
                        PUQajax(closeUrl, data, 5000, $btn, 'POST').then(function (response) {
                            if (response && response.status === 'success') { loadTicket(); }
                        });
                    });

                    $modal.modal('show');
                } else {
                    var $modal = $('#universalModal');
                    $modal.find('#universalModalLabel').text('{{ __('main.Close Ticket') }}');
                    $modal.find('.modal-body').html(
                        '<p>{{ __('main.Are you sure you want to close this ticket?') }}</p>'
                    );
                    $modal.find('#modalConfirmButton').show().text('{{ __('main.Close Ticket') }}')
                        .removeClass('btn-outline-success').addClass('btn-outline-danger');
                    $modal.find('#modalCloseButton').show();

                    $modal.find('#modalConfirmButton').off('click.close').on('click.close', function () {
                        $modal.modal('hide');
                        PUQajax(closeUrl, {}, 5000, $btn, 'POST').then(function (response) {
                            if (response && response.status === 'success') { loadTicket(); }
                        });
                    });

                    $modal.modal('show');
                }
            });

            // Reopen ticket
            $('#reopenTicketBtn').on('click', function () {
                var $btn = $(this);
                var $modal = $('#universalModal');
                $modal.find('#universalModalLabel').text('{{ __('main.Reopen Ticket') }}');
                $modal.find('.modal-body').html(
                    '<p>{{ __('main.Are you sure you want to reopen this ticket?') }}</p>'
                );
                $modal.find('#modalConfirmButton').show().text('{{ __('main.Reopen Ticket') }}')
                    .removeClass('btn-outline-danger').addClass('btn-outline-success');
                $modal.find('#modalCloseButton').show();

                $modal.find('#modalConfirmButton').off('click.reopen').on('click.reopen', function () {
                    $modal.modal('hide');
                    PUQajax(reopenUrl, {}, 5000, $btn, 'POST').then(function (response) {
                        if (response && response.status === 'success') { loadTicket(); }
                    });
                });

                $modal.modal('show');
            });

            function escapeHtml(text) {
                if (!text) return '';
                return $('<span>').text(text).html();
            }

            function validateFiles(files) {
                if (!files || files.length === 0) return true;

                if (fileSettings.max_files_count && files.length > fileSettings.max_files_count) {
                    alert_error(translate('Error'), ['{{ __('error.Maximum number of files reached') }}'], 5000);
                    return false;
                }

                if (fileSettings.allowed_extensions) {
                    var allowed = fileSettings.allowed_extensions.split(',').map(function (ext) {
                        return ext.trim().toLowerCase();
                    });
                    for (var i = 0; i < files.length; i++) {
                        var ext = files[i].name.split('.').pop().toLowerCase();
                        if (allowed.indexOf(ext) === -1) {
                            alert_error(translate('Error'), ['{{ __('error.File extension not allowed') }}: ' + files[i].name], 5000);
                            return false;
                        }
                    }
                }

                if (fileSettings.max_file_size_mb) {
                    var maxBytes = fileSettings.max_file_size_mb * 1024 * 1024;
                    for (var i = 0; i < files.length; i++) {
                        if (files[i].size > maxBytes) {
                            alert_error(translate('Error'), ['{{ __('error.File size exceeds maximum allowed') }}: ' + files[i].name], 5000);
                            return false;
                        }
                    }
                }

                return true;
            }

            function uploadAttachments(replyUuid, files, callback) {
                if (!files || files.length === 0) {
                    if (callback) callback();
                    return;
                }

                var uploaded = 0;
                for (var i = 0; i < files.length; i++) {
                    var formData = new FormData();
                    formData.append('reply_uuid', replyUuid);
                    formData.append('file', files[i]);

                    $.ajax({
                        url: attachmentUploadUrl,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'Authorization': 'Bearer ' + $('meta[name="api_token"]').attr('content'),
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function () {
                            uploaded++;
                            if (uploaded >= files.length && callback) callback();
                        },
                        error: function () {
                            uploaded++;
                            if (uploaded >= files.length && callback) callback();
                        }
                    });
                }
            }

            function showReplySpinner() {
                $('#replySection .card-body').html(
                    '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i>' +
                    '<div class="mt-2 text-muted">{{ __('main.Uploading files...') }}</div></div>'
                );
            }

            $(document).on('submit', '#replyForm', function (e) {
                e.preventDefault();
                var $btn = $('#submitReply');
                var $form = $(this);
                var files = getSelectedFiles();

                if (!validateFiles(files)) return;

                var data = {
                    message: $('#replyMessage').val()
                };

                showReplySpinner();

                PUQajax(
                    replyUrl,
                    data,
                    5000,
                    null,
                    'POST',
                    null
                ).then(function (response) {
                    if (response && response.status === 'success') {
                        if (files.length > 0) {
                            $.ajax({
                                url: apiUrl,
                                method: 'GET',
                                headers: {
                                    'Authorization': 'Bearer ' + $('meta[name="api_token"]').attr('content')
                                },
                                success: function (ticketResponse) {
                                    var replies = ticketResponse.data.replies;
                                    if (replies && replies.length > 0) {
                                        var lastReply = replies[replies.length - 1];
                                        uploadAttachments(lastReply.uuid, files, function () {
                                            loadTicket();
                                        });
                                    } else {
                                        loadTicket();
                                    }
                                }
                            });
                        } else {
                            loadTicket();
                        }
                    }
                }).catch(function () {
                    loadTicket();
                });
            });

            loadTicket();
        });
    </script>
@endsection
