@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
    <style>
        .error-indicator {
            color: #dc3545;
            font-size: 1.8rem;
            animation: blink 1s infinite;
            cursor: pointer;
        }
        @keyframes blink {
            0%, 50%, 100% { opacity: 1; }
            25%, 75% { opacity: 0.3; }
        }
    </style>
@endsection

@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="fas fa-shield-alt icon-gradient bg-primary"></i>
                </div>
                <div>
                    {{__('main.SSL Certificates')}}
                    <div class="page-title-subheading">
                        {{__('main.This is where you configure the SSL Certificates')}}</div>
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
            <table style="width: 100%;" id="ssl_certificates"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('main.Domain')}}</th>
                    <th>{{__('main.Certificate Authority')}}</th>
                    <th>{{__('main.Status')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('main.Domain')}}</th>
                    <th>{{__('main.Certificate Authority')}}</th>
                    <th>{{__('main.Status')}}</th>
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
            var $tableId = $('#ssl_certificates');
            const statusColors = {
                draft: {color: 'secondary', icon: 'fa-circle'},
                pending: {color: 'warning', icon: 'fa-hourglass-half'},
                active: {color: 'success', icon: 'fa-check-circle'},
                expired: {color: 'danger', icon: 'fa-times-circle'},
                revoked: {color: 'dark', icon: 'fa-ban'},
                failed: {color: 'danger', icon: 'fa-exclamation-triangle'}
            };

            var ajaxUrl = '{{ route('admin.api.ssl_certificates.get') }}';
            var columnsConfig = [
                {
                    data: null,
                    name: "domain",
                    render: function(data, type, row) {
                        let aliasesHtml = '';
                        if (row.aliases && row.aliases.length) {
                            aliasesHtml = '<br><small class="text-muted">' +
                                row.aliases.join('<br>') +
                                '</small>';
                        }
                        return `<strong>${row.domain}</strong>${aliasesHtml}`;
                    }
                },
                {
                    data: null,
                    name: "certificate_authority",
                    render: function(data, type, row) {
                        if (row.certificate_authority_data) {
                            return `<strong>${row.certificate_authority_data.name}</strong><br>` +
                                `<small>${row.certificate_authority_data.module_name}</small>`;
                        }
                        return '';
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function (data, type, row) {
                        const statusInfo = statusColors[data] || { color: 'secondary', icon: 'fa-circle' };

                        let errorHtml = '';
                        if (row.last_error && row.last_error.trim() !== '') {
                            errorHtml = `
                <span class="error-indicator"
                      data-bs-toggle="tooltip"
                      title="${row.last_error.replace(/"/g, '&quot;')}">
                    <i class="fa fa-exclamation-triangle"></i>
                </span>`;
                        }

                        const statusHtml = `
            <div class="mb-1 d-flex align-items-center">
                ${errorHtml}
                <span class="badge bg-${statusInfo.color} ms-2">
                    <i class="fa ${statusInfo.icon} me-1"></i>${data}
                </span>
            </div>`;

                        const daysRemaining = row.days_emaining;
                        const autoRenew = row.auto_renew_days;
                        let daysColor = 'secondary';
                        let daysIcon = 'fa-question-circle';

                        if (daysRemaining === null) {
                            daysColor = 'secondary';
                            daysIcon = 'fa-question-circle';
                        } else if (autoRenew === 0) {
                            daysColor = 'success';
                            daysIcon = 'fa-check-circle';
                        } else if (daysRemaining <= (autoRenew / 2)) {
                            daysColor = 'danger';
                            daysIcon = 'fa-times-circle';
                        } else if (daysRemaining <= autoRenew) {
                            daysColor = 'warning';
                            daysIcon = 'fa-exclamation-triangle';
                        } else {
                            daysColor = 'success';
                            daysIcon = 'fa-check-circle';
                        }

                        const daysHtml = `
            <div>
                <span class="badge bg-${daysColor} me-2">
                    <i class="fa ${daysIcon} me-1"></i>${daysRemaining !== null ? daysRemaining + ' days' : '--'}
                </span>
                <span class="badge bg-info text-dark me-2">
                    <i class="fa fa-calendar-alt me-1"></i>${translate('Auto Renew')}: ${autoRenew}
                </span>
            </div>`;

                        return `<div>${statusHtml}${daysHtml}</div>`;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.web_edit) btn += renderEditLink(row.urls.web_edit);
                        if (row.urls.delete) btn += renderDeleteButton(row.urls.delete);
                        return btn;
                    }
                }
            ];


            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig);

            function escapeHtml(text) {
                return $('<div>').text(text).html();
            }

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

            $('#universalModal').on('show.bs.modal', function (event) {
                var $modalTitle = $(this).find('.modal-title');
                var $modalBody = $(this).find('.modal-body');

                $modalTitle.text(translate('Create SSL Certificate'));

                var formHtml = `
<form id="createSslCertificate" class="col-md-10 mx-auto">
    <div class="mb-3">
        <label class="form-label" for="domain">${translate('Domain')}</label>
        <div>
            <input type="text" class="form-control input-mask-trigger" id="domain" name="domain">
        </div>
    </div>

    <div class="mb-3">
        <div class="position-relative mb-3">
            <div>
                <label for="certificate_authority_uuid" class="form-label">${translate('Certificate Authority')}</label>
                <select name="certificate_authority_uuid" id="certificate_authority_uuid" class="form-select mb-2 form-control"></select>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="aliases">{{ __('main.Aliases') }}</label>
        <textarea name="aliases" id="aliases" class="form-control" rows="3"></textarea>
        <small class="form-text text-muted">${translate('Each alias should be entered on a new line (one domain per line)')}</small>
    </div>
</form>`;


                $modalBody.html(formHtml);

                var $elementCA = $modalBody.find('[name="certificate_authority_uuid"]');
                initializeSelect2($elementCA, '{{route('admin.api.certificate_authorities.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });

                var $form = $('#createSslCertificate');
                $form.on('keydown', function (event) {
                    if (event.key === 'Enter' && !$(event.target).is('textarea')) {
                        event.preventDefault();
                    }
                });
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();
                var $form = $('#createSslCertificate');

                if ($form.length === 0) {
                    console.error("Form not found");
                    return;
                }

                var formData = serializeForm($form);

                PUQajax('{{route('admin.api.ssl_certificate.post')}}', formData, 500, $(this), 'POST', $form)
                    .then(function (response) {
                        $('#universalModal').modal('hide');
                    });
            });
        });

    </script>

@endsection
