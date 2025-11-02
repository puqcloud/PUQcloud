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
                        <i class="fas fa-shield-alt icon-gradient bg-primary"></i>
                    </span>
                        <span class="d-inline-block">{{ __('main.Edit SSL Certificate') }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a><i class="fa fa-home"></i></a></li>
                                <li class="breadcrumb-item"><a
                                        href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a></li>
                                <li class="active breadcrumb-item"><a
                                        href="{{route('admin.web.ssl_certificates')}}">{{ __('main.SSL Certificates') }}</a>
                                </li>
                                <li class="active breadcrumb-item">{{ request()->route('uuid') }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                <button id="generateCsr" type="button" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary">
                    <i class="fa fa-key"></i> {{ __('main.Generate CSR') }}
                </button>

                <div class="btn-group">
                    <button type="button" data-bs-toggle="dropdown"
                            class="dropdown-toggle mb-2 me-2 btn-icon btn-outline-2x btn btn-warning">
                        {{ __('main.Change Status') }}
                    </button>
                    <div class="dropdown-menu">
                        <h6 class="dropdown-header">{{ __('main.Change Status') }}</h6>

                        <button type="button" class="dropdown-item force-status" data-type="status" data-value="draft">
                            Draft
                        </button>
                        <button type="button" class="dropdown-item force-status" data-type="status" data-value="pending">
                            Pending
                        </button>
                        <button type="button" class="dropdown-item force-status" data-type="status" data-value="processing">
                            Processing
                        </button>
                        <button type="button" class="dropdown-item force-status" data-type="status" data-value="active">
                            Active
                        </button>
                        <button type="button" class="dropdown-item force-status" data-type="status" data-value="expired">
                            Expired
                        </button>
                        <button type="button" class="dropdown-item force-status" data-type="status" data-value="revoked">
                            Revoked
                        </button>
                        <button type="button" class="dropdown-item force-status" data-type="status" data-value="failed">
                            Failed
                        </button>

                    </div>
                </div>

                <button id="save" type="button" class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{ __('main.Save') }}
                </button>
            </div>
        </div>
    </div>

    <form id="ssl_certificate" novalidate="novalidate">
        <div id="mainCard" class="mb-3 card">
            <div class="card-body">


                <div class="row">

                    <div class="col-12 col-md-6 mb-3">

                        <div class="row g-3 align-items-center">
                            <!-- Certificate Status -->
                            <div class="col-6 col-sm-4 col-md-6 col-lg-4 col-xl-4">
                                <small class="text-muted d-block mb-1">{{ __('main.Certificate Status') }}</small>
                                <span id="statusBadge" class="badge fs-6 d-flex align-items-center shadow-sm">
                                    <i id="statusIcon" class="me-1"></i>
                                    <span id="statusValue"></span>
                                </span>
                            </div>

                            <!-- Auto Renew Days -->
                            <div class="col-6 col-sm-4 col-md-6 col-lg-4 col-xl-4">
                                <small class="text-muted d-block mb-1">{{ __('main.Auto Renew Days') }}</small>
                                <div class="input-group" style="max-width: 150px;">
                                    <span class="input-group-text"><i class="fa fa-calendar-alt"></i></span>
                                    <input type="number" class="form-control" id="auto_renew_days" name="auto_renew_days" min="0">
                                </div>
                            </div>

                            <!-- Days Remaining -->
                            <div class="col-6 col-sm-4 col-md-6 col-lg-4 col-xl-4">
                                <small class="text-muted d-block mb-1">{{ __('main.Days Remaining') }}</small>
                                <span id="daysRemainingBadge" class="badge fs-5 d-flex align-items-center shadow-sm">
                                    <i id="daysRemainingIcon" class="me-1"></i>
                                        <span id="daysRemainingValue">--</span>
                                </span>
                            </div>

                            <!-- Last Error Alert -->
                            <div class="col-12 mt-2">
                                <div id="lastErrorAlert" class="alert alert-danger d-none shadow-sm" role="alert">
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                <label for="domain" class="form-label"><i
                                        class="fa fa-globe me-1"></i>{{ __('main.Domain') }}</label>
                                <input type="text" class="form-control" id="domain" name="domain">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="wildcard"
                                           name="wildcard">
                                    <label class="form-check-label" for="wildcard">
                                        {{ __('main.Wildcard') }} (*. <span id="wildcard-domain"></span>)
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                <label for="aliases" class="form-label"><i
                                        class="fa fa-link me-1"></i>{{ __('main.Aliases') }}</label>
                                <textarea name="aliases" id="aliases" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                <label for="email" class="form-label"><i
                                        class="fa fa-envelope me-1"></i>{{ __('main.Email') }}</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                <label for="organization" class="form-label"><i
                                        class="fa fa-building me-1"></i>{{ __('main.Organization') }}</label>
                                <input type="text" class="form-control" id="organization" name="organization">
                            </div>
                            <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                <label for="organizational_unit" class="form-label"><i
                                        class="fa fa-diagram-project me-1"></i>{{ __('main.Organizational Unit') }}
                                </label>
                                <input type="text" class="form-control" id="organizational_unit"
                                       name="organizational_unit">
                            </div>
                            <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                <label for="country" class="form-label"><i
                                        class="fa fa-flag me-1"></i>{{ __('main.Country') }}</label>
                                <input type="text" class="form-control" id="country" name="country">
                            </div>
                            <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                <label for="state" class="form-label"><i
                                        class="fa fa-map me-1"></i>{{ __('main.State') }}</label>
                                <input type="text" class="form-control" id="state" name="state">
                            </div>
                            <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                <label for="locality" class="form-label"><i
                                        class="fa fa-map-pin me-1"></i>{{ __('main.Locality') }}</label>
                                <input type="text" class="form-control" id="locality" name="locality">
                            </div>
                        </div>
                        <hr>

                        <div class="col-12 col-md-12 mb-3">

                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-shield-alt me-1"></i>{{ __('main.Certificate Authority') }}</label>
                                    <input type="text" class="form-control" readonly id="ca_name">
                                </div>
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-plug me-1"></i>{{ __('main.Module') }}</label>
                                    <input type="text" class="form-control" readonly id="module_name">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-key me-1"></i>{{ __('main.Key Size') }}</label>
                                    <input type="text" class="form-control" readonly id="key_size">
                                </div>
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-file-signature me-1"></i>{{ __('main.Signature Algorithm') }}</label>
                                    <input type="text" class="form-control" readonly id="signature_algorithm">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-calendar-plus me-1"></i>{{ __('main.Issued At') }}</label>
                                    <input type="text" class="form-control" readonly id="issued_at">
                                </div>
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-calendar-times me-1"></i>{{ __('main.Expires At') }}</label>
                                    <input type="text" class="form-control" readonly id="expires_at">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-calendar-check me-1"></i>{{ __('main.CSR Valid From') }}</label>
                                    <input type="text" class="form-control" readonly id="csr_valid_from">
                                </div>
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-redo-alt me-1"></i>{{ __('main.Renewed At') }}</label>
                                    <input type="text" class="form-control" readonly id="renewed_at">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-info-circle me-1"></i>{{ __('main.OCSP Status') }}</label>
                                    <input type="text" class="form-control" readonly id="ocsp_status">
                                </div>
                                <div class="col-12 col-sm-6 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"><i class="fa fa-clock me-1"></i>{{ __('main.OCSP Checked At') }}</label>
                                    <input type="text" class="form-control" readonly id="ocsp_checked_at">
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label"><i class="fa fa-certificate me-1"></i>{{ __('main.Issuer') }}</label>
                                <div id="issuer" class="p-2 border rounded bg-light font-monospace"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-hashtag me-1"></i>{{ __('main.Serial Number') }} DEC</label>
                                <input type="text" class="form-control" readonly id="serial_number_dec">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-hashtag me-1"></i>{{ __('main.Serial Number') }} HEX</label>
                                <input type="text" class="form-control" readonly id="serial_number_hex">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-fingerprint me-1"></i>{{ __('main.Fingerprint') }} MD5</label>
                                <input type="text" class="form-control" readonly id="fingerprint_md5">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-fingerprint me-1"></i>{{ __('main.Fingerprint') }} SHA1</label>
                                <input type="text" class="form-control" readonly id="fingerprint_sha1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-fingerprint me-1"></i>{{ __('main.Fingerprint') }} SHA256</label>
                                <input type="text" class="form-control" readonly id="fingerprint_sha256">
                            </div>


                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-file-alt me-1"></i>{{ __('main.Certificate PEM') }}</label>
                                <textarea class="form-control" rows="5" readonly id="certificate_pem"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-key me-1"></i>{{ __('main.Private Key PEM') }}</label>
                                <textarea class="form-control" rows="5" readonly id="private_key_pem"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-file-code me-1"></i>{{ __('main.CSR PEM') }}</label>
                                <textarea class="form-control" rows="5" readonly id="csr_pem"></textarea>
                            </div>

                        </div>

                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <div id="module_html" class="mb-3"></div>
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
            let currentStatus = null;
            const statusColors = {
                draft: {color: 'secondary', icon: 'fa-circle'},
                pending: {color: 'warning', icon: 'fa-hourglass-half'},
                active: {color: 'success', icon: 'fa-check-circle'},
                expired: {color: 'danger', icon: 'fa-times-circle'},
                revoked: {color: 'dark', icon: 'fa-ban'},
                failed: {color: 'danger', icon: 'fa-exclamation-triangle'}
            };

            const domainInput = document.getElementById('domain');
            const wildcardSpan = document.getElementById('wildcard-domain');
            domainInput.addEventListener('input', () => {
                wildcardSpan.textContent = domainInput.value || 'example.com';
            });

            function formatDateTime(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const pad = (n) => n.toString().padStart(2, '0');
                return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ` +
                    `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
            }

            function loadFormData() {
                blockUI('ssl_certificate');
                PUQajax('{{route('admin.api.ssl_certificate.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function (response) {
                        const data = response.data;

                        const statusBadge = $('#statusBadge');
                        const statusIcon = $('#statusIcon');
                        const status = data.status;
                        currentStatus = data.status;
                        statusBadge.removeClass().addClass('badge bg-' + (statusColors[status]?.color || 'secondary'));
                        statusIcon.removeClass().addClass('fa ' + (statusColors[status]?.icon || 'fa-circle'));
                        statusBadge.contents().filter(function () {
                            return this.nodeType === 3
                        }).remove();
                        statusBadge.append(' ' + status);

                        if (data.last_error && data.last_error.trim() !== '') {
                            const errors = data.last_error.split(';')
                                .map(e => e.trim())
                                .filter(e => e !== '')
                                .join('<br>');

                            $('#lastErrorAlert')
                                .removeClass('d-none')
                                .html(errors);
                        } else {
                            $('#lastErrorAlert')
                                .addClass('d-none')
                                .html('');
                        }

                        $('#auto_renew_days').val(data.auto_renew_days);

                        const daysBadge = $('#daysRemainingBadge');
                        const daysIcon = $('#daysRemainingIcon');
                        const daysValue = $('#daysRemainingValue');

                        const daysRemaining = data.days_remaining;
                        const autoRenew = data.auto_renew_days;

                        daysValue.text(daysRemaining !== null ? daysRemaining : '--');

                        if (daysRemaining === null) {
                            daysBadge.removeClass().addClass('badge bg-secondary');
                            daysIcon.removeClass().addClass('fa fa-question-circle me-1');
                        } else if (autoRenew === 0) {
                            daysBadge.removeClass().addClass('badge bg-success ');
                            daysIcon.removeClass().addClass('fa fa-check-circle me-1');
                        } else if (daysRemaining <= (autoRenew / 2)) {
                            daysBadge.removeClass().addClass('badge bg-danger');
                            daysIcon.removeClass().addClass('fa fa-times-circle me-1');
                        } else if (daysRemaining <= autoRenew) {
                            daysBadge.removeClass().addClass('badge bg-warning');
                            daysIcon.removeClass().addClass('fa fa-exclamation-triangle me-1');
                        } else {
                            daysBadge.removeClass().addClass('badge bg-success');
                            daysIcon.removeClass().addClass('fa fa-check-circle me-1');
                        }

                        $('#domain').val(data.domain);
                        wildcardSpan.textContent = data.domain;
                        $('#wildcard').prop('checked', !!data.wildcard);
                        $('#aliases').val(data.aliases.join("\n"));
                        $('#organization').val(data.organization);
                        $('#organizational_unit').val(data.organizational_unit);
                        $('#country').val(data.country);
                        $('#state').val(data.state);
                        $('#locality').val(data.locality);
                        $('#email').val(data.email);

                        $('#ca_name').val(data.certificate_authority?.name || '');
                        $('#module_name').val(data.certificate_authority?.module?.module_data?.name || '');
                        $('#key_size').val(data.key_size || '');
                        $('#signature_algorithm').val(data.signature_algorithm || '');
                        $('#issued_at').val(formatDateTime(data.issued_at));
                        $('#expires_at').val(formatDateTime(data.expires_at));
                        $('#csr_valid_from').val(formatDateTime(data.csr_valid_from));
                        $('#renewed_at').val(formatDateTime(data.renewed_at));
                        $('#ocsp_status').val(data.ocsp_status || (data.ocsp_checked ? 'Checked' : 'Not Checked'));
                        $('#ocsp_checked_at').val(formatDateTime(data.ocsp_checked_at));

                        let issuerHtml = '';
                        if (data.issuer && typeof data.issuer === 'object') {
                            const issuerParts = [];
                            if (data.issuer.C) issuerParts.push(`C=${data.issuer.C}`);
                            if (data.issuer.O) issuerParts.push(`O=${data.issuer.O}`);
                            if (data.issuer.CN) issuerParts.push(`CN=${data.issuer.CN}`);
                            issuerHtml = issuerParts.map(part => `<div>${part}</div>`).join('');
                        } else {
                            issuerHtml = `<div>${data.issuer || ''}</div>`;
                        }

                        $('#issuer').html(issuerHtml);

                        $('#serial_number_hex').val(data.serial_number_hex);
                        $('#serial_number_dec').val(data.serial_number_dec);
                        $('#fingerprint_md5').val(data.certificate_fingerprint_md5);
                        $('#fingerprint_sha1').val(data.certificate_fingerprint_sha1);
                        $('#fingerprint_sha256').val(data.certificate_fingerprint_sha256);
                        $('#certificate_pem').val(data.certificate_pem);
                        $('#private_key_pem').val(data.private_key_pem);
                        $('#csr_pem').val(data.csr_pem);
                        $('#chain_pem').val(data.chain_pem);
                        $('#module_html').html(data.module_html);

                        if (status !== 'draft') {
                            $('#domain, #wildcard, #aliases, #organization, #organizational_unit, #country, #state, #locality, #email').prop('readonly', true);
                            $('#wildcard').prop('disabled', true);
                        } else {
                            $('#domain, #wildcard, #aliases, #organization, #organizational_unit, #country, #state, #locality, #email').prop('readonly', false);
                            $('#wildcard').prop('disabled', false);
                        }

                        unblockUI('ssl_certificate');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const formData = serializeForm($("#ssl_certificate"));
                PUQajax('{{route('admin.api.ssl_certificate.put',$uuid)}}', formData, 5000, $(this), 'PUT', $("#ssl_certificate"))
                    .then(function () {
                        loadFormData();
                    });
            });

            $("#generateCsr").on("click", function (event) {
                event.preventDefault();
                PUQajax('{{route('admin.api.ssl_certificate.generate_csr.get',$uuid)}}', null, 5000, $(this), 'GET', null)
                    .then(function () {
                        loadFormData();
                    })
                    .catch(function (error) {
                    });
            });


            $('.force-status').on('click', function () {
                const status = $(this).data('value');

                PUQajax("{{ route('admin.api.ssl_certificate.status.put', $uuid) }}", {
                    status: status
                }, 5000, $(this), 'PUT')
                    .then(function (response) {
                        loadFormData();
                    });
            });



            function checkStatus() {
                PUQajax('{{route('admin.api.ssl_certificate.get',$uuid)}}', {}, 50, null, 'GET')
                    .then(function(response) {
                        const newStatus = response.data.status;

                        if (currentStatus !== newStatus) {
                            currentStatus = newStatus;
                            loadFormData();
                        }
                    })
                    .catch(function(err){
                        console.error('Status check failed:', err);
                    });
            }

            loadFormData();
            setInterval(checkStatus, 5000);
        });
    </script>
@endsection
