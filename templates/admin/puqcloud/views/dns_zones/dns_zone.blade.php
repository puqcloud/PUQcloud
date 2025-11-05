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
                            <i class="fas fa-globe icon-gradient bg-primary"></i>
                    </span>
                        <span class="d-inline-block">{{ __('main.Edit DNS Zone') }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('admin.web.dns_zones') }}">{{ __('main.DNS Zones') }}</a>
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

                <button id="createDnsRecord" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info">
                    <i class="fas fa-plus"></i> {{__('main.Create Record')}}
                </button>

                <button id="reloadZone" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-primary">
                    <i class="fa fa-sync-alt"></i> {{ __('main.Reload Zone') }}
                </button>

                <button id="moveTo" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
                    <i class="fas fa-arrow-circle-right"></i> {{ __('main.Move To') }}
                </button>

                <div class="mb-2 me-2 btn-group">
                    <button type="button"
                            class="btn-icon btn-outline-2x btn btn-outline-warning dropdown-toggle d-flex align-items-center"
                            data-bs-toggle="dropdown">
                        <i class="fas fa-exchange-alt me-2"></i>
                        {{ __('main.Export / Import') }}
                    </button>
                    <ul class="dropdown-menu p-2">
                        <li>
                            <h6 class="dropdown-header">{{ __('main.Export') }}</h6>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" id="exportBind">
                                <i class="fas fa-file-alt me-2"></i> BIND / Zone File
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" id="exportJson">
                                <i class="fas fa-file-code me-2"></i> JSON File
                            </button>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <h6 class="dropdown-header">{{ __('main.Import') }}</h6>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" id="import_bind">
                                <i class="fas fa-file-import me-2"></i> BIND / Zone File
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" id="import_json">
                                <i class="fas fa-file-import me-2"></i> JSON File
                            </button>
                        </li>
                    </ul>
                </div>


                <button id="save" type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                    <i class="fa fa-save"></i> {{__('main.Save')}}
                </button>
            </div>
        </div>
    </div>

    <form id="editDnsZoneForm" class="mx-auto" novalidate="novalidate">
        <div class="card shadow-sm border-primary mb-4">
            <div class="card-body">
                <div class="row g-3">

                    <!-- Name -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-3">
                        <label class="form-label" for="name"><i class="fas fa-globe"></i> {{ __('main.Name') }}</label>
                        <input type="text" class="form-control border-info" id="name" name="name" disabled>
                    </div>

                    <!-- DNS Server Group -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <label class="form-label" for="dns_server_group_name"><i
                                class="fas fa-server"></i> {{ __('main.DNS Server Group') }}</label>
                        <input type="text" class="form-control border-info" id="dns_server_group_name"
                               name="dns_server_group_name" disabled>
                    </div>

                    <!-- SOA Primary NS -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <label class="form-label" for="soa_primary_ns"><i
                                class="fas fa-network-wired"></i> {{ __('main.Primary NS') }}</label>
                        <input type="text" class="form-control border-info" id="soa_primary_ns" name="soa_primary_ns"
                               disabled>
                    </div>

                    <!-- NS Domains -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-5 col-xl-5 col-xxl-5">
                        <label class="form-label"><i class="fas fa-list-ol"></i> {{ __('main.NS Domains') }}</label>
                        <div id="ns_domains_list">
                            <!-- Filled by JS -->
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <label class="form-label" for="soa_admin_email"><i
                                class="fas fa-envelope"></i> {{ __('main.Admin Email') }}</label>
                        <input type="email" class="form-control" id="soa_admin_email" name="soa_admin_email">
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <label class="form-label" for="soa_ttl"><i
                                class="fas fa-clock"></i> {{ __('main.SOA TTL') }}</label>
                        <input type="number" min="30" step="1" class="form-control" id="soa_ttl" name="soa_ttl">
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <label class="form-label" for="soa_refresh"><i
                                class="fas fa-sync-alt"></i> {{ __('main.Refresh') }}</label>
                        <input type="number" min="30" step="1" class="form-control" id="soa_refresh" name="soa_refresh">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <label class="form-label" for="soa_retry"><i class="fas fa-redo-alt"></i> {{ __('main.Retry') }}
                        </label>
                        <input type="number" min="30" step="1" class="form-control" id="soa_retry" name="soa_retry">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <label class="form-label" for="soa_expire"><i
                                class="fas fa-hourglass-end"></i> {{ __('main.Expire') }}</label>
                        <input type="number" min="30" step="1" class="form-control" id="soa_expire" name="soa_expire">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <label class="form-label" for="soa_minimum"><i
                                class="fas fa-clock"></i> {{ __('main.Minimum TTL') }}</label>
                        <input type="number" min="30" step="1" class="form-control" id="soa_minimum" name="soa_minimum">
                    </div>

                </div>
            </div>
        </div>
    </form>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="dns_records" class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{ __('main.Name') }}</th>
                    <th>{{ __('main.Content') }}</th>
                    <th>{{ __('main.Type') }}</th>
                    <th>{{ __('main.TTL') }}</th>
                    <th>{{ __('main.Description') }}</th>
                    <th>{{ __('main.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{ __('main.Name') }}</th>
                    <th>{{ __('main.Content') }}</th>
                    <th>{{ __('main.Type') }}</th>
                    <th>{{ __('main.TTL') }}</th>
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

            var zoneName;

            function loadFormData() {
                blockUI('editDnsZoneForm');
                const $form = $('#editDnsZoneForm');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{ route('admin.api.dns_zone.get', $uuid) }}', {}, 5000, null, 'GET')
                    .then(function (response) {
                        if (response.data) {
                            const data = response.data;

                            $('#name').val(data.name || '');
                            zoneName = data.name;
                            $('#dns_server_group_name').val(data.dns_server_group_name || '');
                            $('#soa_primary_ns').val(data.soa_primary_ns || '');
                            $('#soa_admin_email').val(data.soa_admin_email || '');
                            $('#soa_ttl').val(data.soa_ttl || '');
                            $('#soa_refresh').val(data.soa_refresh || '');
                            $('#soa_retry').val(data.soa_retry || '');
                            $('#soa_expire').val(data.soa_expire || '');
                            $('#soa_minimum').val(data.soa_minimum || '');

                            const nsContainer = $('#ns_domains_list');
                            nsContainer.empty();
                            if (Array.isArray(data.ns_domains)) {
                                data.ns_domains.forEach(ns => {
                                    nsContainer.append(
                                        `<span class="badge bg-info text-dark me-1 mb-1"><i class="fas fa-server me-1"></i>${ns}</span>`
                                    );
                                });
                            }

                            unblockUI('editDnsZoneForm');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                        unblockUI('editDnsZoneForm');
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#editDnsZoneForm");
                event.preventDefault();

                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.dns_zone.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();

            var $tableId = $('#dns_records');
            var ajaxUrl = '{{ route('admin.api.dns_zone.dns_records.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return '<strong class="highlight-name">' + data + '</strong>';
                    }
                },
                {
                    data: "content",
                    name: "content",
                    render: function (data, type, row) {
                        if (!data) return '';
                        const short = data.length > 50 ? data.substring(0, 50) + "..." : data;
                        return `<span title="${data.replace(/"/g, '&quot;')}">${short}</span>`;
                    }
                },
                {data: "type", name: "type"},
                {data: "ttl", name: "ttl"},
                                {
                    data: "description",
                    name: "description",
                    render: function (data, type, row) {
                        if (!data) return '';
                        const short = data.length > 50 ? data.substring(0, 50) + "..." : data;
                        return `<span title="${data.replace(/"/g, '&quot;')}">${short}</span>`;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.get) {
                            btn += renderEditButton(row.urls.get);
                        }
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                },
            ];
            var $dataTable = initializeDataTable($tableId, ajaxUrl, columnsConfig, DataTableAddData, {
                lengthMenu: [50, 100, 200, 500, 1000],
                pageLength: 50,
                order: [[2, 'asc']]
            });

            function DataTableAddData() {
                return {};
            }

            function updateDynamicFields(recordType, existingData = {}) {
                let html = '';

                switch (recordType) {
                    case 'A':
                        html = `
                <div class="col-12">
                    <label for="ipv4" class="form-label fw-semibold">${translate('IPv4 address')}</label>
                    <input type="text" class="form-control" id="ipv4" name="ipv4" value="${existingData.ipv4 || ''}">
                    <div class="form-text">${translate('E.g. 192.168.0.1')}</div>
                </div>`;
                        break;

                    case 'AAAA':
                        html = `
                <div class="col-12">
                    <label for="ipv6" class="form-label fw-semibold">${translate('IPv6 address')}</label>
                    <input type="text" class="form-control" id="ipv6" name="ipv6" value="${existingData.ipv6 || ''}">
                    <div class="form-text">${translate('E.g. 2001:0db8::7334')}</div>
                </div>`;
                        break;

                    case 'MX':
                        html = `
                <div class="col-12 col-md-8">
                    <label for="mailServer" class="form-label fw-semibold">${translate('Mail server')}</label>
                    <input type="text" class="form-control" id="mailServer" name="mailServer" value="${existingData.mailServer || ''}">
                    <div class="form-text">${translate('E.g. mx1.example.com')}</div>
                </div>
                <div class="col-12 col-md-4">
                    <label for="priority" class="form-label fw-semibold">${translate('Priority')}</label>
                    <input type="number" min="0" max="65535" step="1" class="form-control" id="priority" name="priority" value="${existingData.priority || 10}">
                    <div class="form-text">0 - 65535</div>
                </div>`;
                        break;

                    case 'CNAME':
                    case 'ALIAS':
                    case 'NS':
                        html = `
                <div class="col-12">
                    <label for="target" class="form-label fw-semibold">${translate('Target')}</label>
                    <input type="text" class="form-control" id="target" name="target" value="${existingData.target || ''}">
                    <div class="form-text">${translate('E.g. www.example.com')}</div>
                </div>`;
                        break;

                    case 'TXT':
                        html = `
                <div class="col-12">
                    <label for="txt" class="form-label fw-semibold">${translate('Content')}</label>
                    <textarea class="form-control" id="txt" name="txt" rows="6" required>${existingData.txt || ''}</textarea>
                </div>`;
                        break;

                    case 'SRV':
                        html = `
                <div class="col-12 col-md-4">
                    <label for="priority" class="form-label fw-semibold">${translate('Priority')}</label>
                    <input type="number" min="0" max="65535" step="1" class="form-control" id="priority" name="priority" value="${existingData.priority || 65535}">
                </div>
                <div class="col-12 col-md-4">
                    <label for="weight" class="form-label fw-semibold">${translate('Weight')}</label>
                    <input type="number" min="0" max="65535" step="1" class="form-control" id="weight" name="weight" value="${existingData.weight || 0}">
                </div>
                <div class="col-12 col-md-4">
                    <label for="port" class="form-label fw-semibold">${translate('Port')}</label>
                    <input type="number" min="0" max="65535" step="1" class="form-control" id="port" name="port" value="${existingData.port || 1}">
                </div>
                <div class="col-12">
                    <label for="target" class="form-label fw-semibold">${translate('Target')}</label>
                    <textarea class="form-control" id="target" name="target" rows="3" required>${existingData.target || ''}</textarea>
                    <div class="form-text">${translate('E.g. www.example.com')}</div>
                </div>`;
                        break;

                    case 'CAA':
                        html = `
                <div class="col-12 col-md-4">
                    <label for="flag" class="form-label fw-semibold">${translate('Flag')}</label>
                    <input type="number" min="0" max="255" step="1" class="form-control" id="flag" name="flag" value="${existingData.flag || 0}">
                </div>
                <div class="col-12 col-md-4">
                    <label for="tag" class="form-label fw-semibold">${translate('Tag')}</label>
                    <select class="form-select" id="tag" name="tag">
                        <option value="issue" ${existingData.tag === 'issue' ? 'selected' : ''}>issue</option>
                        <option value="issuewild" ${existingData.tag === 'issuewild' ? 'selected' : ''}>issuewild</option>
                        <option value="iodef" ${existingData.tag === 'iodef' ? 'selected' : ''}>iodef</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="value" class="form-label fw-semibold">${translate('Value')}</label>
                    <input type="text" class="form-control" id="value" name="value" value="${existingData.value || ''}">
                    <div class="form-text">${translate('E.g. "letsencrypt.org"')}</div>
                </div>`;
                        break;

                    case 'PTR':
                        html = `
                <div class="col-12">
                    <label for="ptrdname" class="form-label fw-semibold">${translate('PTR Target')}</label>
                    <input type="text" class="form-control" id="ptrdname" name="ptrdname" value="${existingData.ptrdname || ''}">
                    <div class="form-text">${translate('FQDN the IP should resolve to, e.g. host.example.com')}</div>
                </div>`;
                        break;

                    default:
                        html = `<div class="col-12"><div class="alert alert-secondary mb-0">${translate('No fields available for this record type.')}</div></div>`;
                }

                return html;
            }

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();

                var modelUrl = $(this).data('model-url');

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $('#universalModal .modal-dialog').addClass('modal-lg');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text(translate('Edit DNS Record'));

                const formHtml = `<form id="editDnsRecordForm" class="mx-auto needs-validation" novalidate>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label for="type" class="form-label fw-semibold">
                <i class="fa-solid fa-layer-group me-1 text-primary"></i> ${translate('Type')}
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light">
                    <i class="fa-solid fa-server text-secondary"></i>
                </span>
                <select class="form-select" id="type" name="type" disabled>
                    <option value="A">A (IPv4)</option>
                    <option value="AAAA">AAAA (IPv6)</option>
                    <option value="CNAME">CNAME (Alias)</option>
                    <option value="MX">MX (Mail)</option>
                    <option value="TXT">TXT (Text)</option>
                    <option value="SRV">SRV (Service)</option>
                    <option value="ALIAS">ALIAS</option>
                    <option value="NS">NS (Subdomain only)</option>
                    <option value="CAA">CAA (Certificate Authority)</option>
                    <option value="PTR">PTR (Reverse DNS)</option>
                </select>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label for="ttl" class="form-label fw-semibold">
                <i class="fa-solid fa-hourglass-half me-1 text-primary"></i> ${translate('TTL')}
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light">
                    <i class="fa-solid fa-clock text-secondary"></i>
                </span>
                <select class="form-select" id="ttl" name="ttl">
                    <option value="300">${translate('5 min')}</option>
                    <option value="600">${translate('10 min')}</option>
                    <option value="900">${translate('15 min')}</option>
                    <option value="1800">${translate('30 min')}</option>
                    <option value="3600">${translate('1 hr')}</option>
                    <option value="7200">${translate('2 hr')}</option>
                    <option value="18000">${translate('5 hr')}</option>
                    <option value="43200">${translate('12 hr')}</option>
                    <option value="86400">${translate('24 hr')}</option>
                </select>
            </div>
        </div>

    <div class="col-12 col-md-12 mt-3">
        <label for="name" class="form-label fw-semibold">
            <i class="fa-solid fa-circle-nodes me-1 text-primary"></i> ${translate('Name')}
        </label>
        <div class="d-flex">
            <div class="col">
                <div class="input-group">
                    <input type="text" class="form-control flex-grow-1" id="recordName" name="name" disabled>
                </div>
            </div>
            <span class="border border-start-0 rounded-end px-2 pt-2 pb-1 fw-semibold d-flex align-items-center"
                style="background-color: var(--bs-secondary-bg); height: 100%;">
                .${zoneName}
            </span>
        </div>
        <div class="form-text">${translate('Use @ for root')}</div>
    </div>

    <hr class="my-1">

    <div id="dynamicFields" class="row g-3"></div>
</form>`;

                $modalBody.html(formHtml);

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        $('#type').val(response.data.type);
                        $('#recordName').val(response.data.name);
                        $('#ttl').val(response.data.ttl);
                        $('#universalModal').modal('show');

                        const $dynamicFieldsContainer = $('#dynamicFields');

                        var html = updateDynamicFields(response.data.type, response.data);
                        $dynamicFieldsContainer.html(html);
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

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

            $('#createDnsRecord').on('click', function () {
                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $('#universalModal .modal-dialog').addClass('modal-lg');

                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text(translate('Create DNS Record'));

                const isReverseZone = zoneName.endsWith('.in-addr.arpa') || zoneName.endsWith('.ip6.arpa');

                let recordTypes = [
                    {value: 'A', text: 'A (IPv4)'},
                    {value: 'AAAA', text: 'AAAA (IPv6)'},
                    {value: 'CNAME', text: 'CNAME (Alias)'},
                    {value: 'MX', text: 'MX (Mail)'},
                    {value: 'TXT', text: 'TXT (Text)'},
                    {value: 'SRV', text: 'SRV (Service)'},
                    {value: 'ALIAS', text: 'ALIAS'},
                    {value: 'NS', text: 'NS (Subdomain only)'},
                    {value: 'CAA', text: 'CAA (Certificate Authority)'},
                    {value: 'PTR', text: 'PTR (Reverse DNS)'}
                ];

                if (isReverseZone) {
                    recordTypes = recordTypes.filter(t => t.value === 'PTR');
                } else {
                    recordTypes = recordTypes.filter(t => t.value !== 'PTR');
                }

                let optionsHtml = recordTypes.map(t => `<option value="${t.value}">${translate(t.text)}</option>`).join('');

                const nameValue = isReverseZone ? '' : '@';
                const nameDescription = isReverseZone ? translate('Enter host for reverse DNS') : translate('Use @ for root');

                const formHtml = `
<form id="createDnsRecordForm" class="mx-auto needs-validation" novalidate>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label for="type" class="form-label fw-semibold">
                <i class="fa-solid fa-layer-group me-1 text-primary"></i> ${translate('Type')}
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light">
                    <i class="fa-solid fa-server text-secondary"></i>
                </span>
                <select class="form-select" id="type" name="type">
                    ${optionsHtml}
                </select>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <label for="ttl" class="form-label fw-semibold">
                <i class="fa-solid fa-hourglass-half me-1 text-primary"></i> ${translate('TTL')}
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light">
                    <i class="fa-solid fa-clock text-secondary"></i>
                </span>
                <select class="form-select" id="ttl" name="ttl">
                    <option value="300">${translate('5 min')}</option>
                    <option value="600">${translate('10 min')}</option>
                    <option value="900">${translate('15 min')}</option>
                    <option value="1800">${translate('30 min')}</option>
                    <option value="3600" selected>${translate('1 hr')}</option>
                    <option value="7200">${translate('2 hr')}</option>
                    <option value="18000">${translate('5 hr')}</option>
                    <option value="43200">${translate('12 hr')}</option>
                    <option value="86400">${translate('24 hr')}</option>
                </select>
            </div>
        </div>
        <div class="col-12 col-md-12 mt-3">
            <label for="name" class="form-label fw-semibold">
                <i class="fa-solid fa-circle-nodes me-1 text-primary"></i> ${translate('Name')}
            </label>
            <div class="d-flex">
                <div class="col">
                    <div class="input-group">
                        <input type="text" class="form-control flex-grow-1" id="name" name="name" value="${nameValue}" required>
                    </div>
                </div>
                <span class="border border-start-0 rounded-end px-2 pt-2 pb-1 fw-semibold d-flex align-items-center"
                    style="background-color: var(--bs-secondary-bg); height: 100%;">
                    .${zoneName}
                </span>
            </div>
            <div class="form-text">${nameDescription}</div>
        </div>
        <hr class="my-1">
        <div id="dynamicFields" class="row g-3"></div>
    </div>
</form>
`;

                $modalBody.html(formHtml);
                $('#universalModal').modal('show');

                const $recordTypeSelect = $('#type');
                const $dynamicFieldsContainer = $('#dynamicFields');

                var html = updateDynamicFields($recordTypeSelect.val());
                $dynamicFieldsContainer.html(html);

                $recordTypeSelect.on('change', function () {
                    $dynamicFieldsContainer.html(`
                        <div class="d-flex justify-content-center align-items-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden"></span>
                            </div>
                        </div>
                    `);

                    setTimeout(() => {
                        var html = updateDynamicFields($recordTypeSelect.val());
                        $dynamicFieldsContainer.html(html);
                    }, 300);
                });
            });

            $('#reloadZone').on('click', function (event) {
                event.preventDefault();
                PUQajax('{{route('admin.api.dns_zone.reload.get',$uuid)}}', null, 3000, $(this), 'GET')
                    .then(function (response) {
                        loadFormData();
                        $dataTable.ajax.reload(null, false);
                    });
            });

            $('#exportBind').on('click', function (event) {
                event.preventDefault();

                PUQajax('{{route('admin.api.dns_zone.export.bind.get',$uuid)}}', null, 3000, $(this), 'GET', null)
                    .then(function (response) {
                        if (response.status === 'success' && response.data.file_content) {
                            const byteCharacters = atob(response.data.file_content);
                            const byteNumbers = new Array(byteCharacters.length);
                            for (let i = 0; i < byteCharacters.length; i++) {
                                byteNumbers[i] = byteCharacters.charCodeAt(i);
                            }
                            const byteArray = new Uint8Array(byteNumbers);
                            const blob = new Blob([byteArray], {type: 'text/plain'});

                            const link = document.createElement('a');
                            link.href = URL.createObjectURL(blob);
                            link.download = response.data.file_name || 'zonefile.zone';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            alert('Error exporting zone file');
                        }
                    })
                    .catch(function () {
                        alert('Server error');
                    });
            });

            $('#exportJson').on('click', function (event) {
                event.preventDefault();

                PUQajax('{{route('admin.api.dns_zone.export.json.get',$uuid)}}', null, 3000, $(this), 'GET', null)
                    .then(function (response) {
                        if (response.status === 'success' && response.data) {
                            const jsonString = JSON.stringify(response.data, null, 4);
                            const blob = new Blob([jsonString], {type: 'application/json'});
                            const link = document.createElement('a');
                            link.href = URL.createObjectURL(blob);
                            link.download = response.data.file_name || zoneName + '.json';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            alert('Error exporting JSON');
                        }
                    })
                    .catch(function () {
                        alert('Server error');
                    });
            });

            $('#moveTo').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');

                $('#universalModal .modal-dialog').removeClass('modal-lg');
                $('#universalModal .modal-dialog').addClass('modal-md');

                $modalTitle.text(translate('Move to another server group'));

                const formHtml = `
<form id="moveToForm" class="mx-auto needs-validation" novalidate>
    <div class="mb-3">
        <label class="form-label" for="dns_server_group_uuid">${translate('DNS Server Group')}</label>
        <select name="dns_server_group_uuid" id="dns_server_group_uuid" class="form-select">
            <option value="">${translate('Select group')}</option>
        </select>
    </div>
</form>
`;
                $modalBody.html(formHtml);
                $('#universalModal').modal('show');

                var $elementDnsServerGroup = $modalBody.find('[name="dns_server_group_uuid"]');
                initializeSelect2($elementDnsServerGroup, '{{route('admin.api.dns_server_groups.select.get')}}', '', 'GET', 1000, {
                    dropdownParent: $('#universalModal')
                });
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createDnsRecordForm').length) {
                    var $form = $('#createDnsRecordForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.dns_zone.dns_record.post',$uuid)}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#editDnsRecordForm').length) {
                    var $form = $('#editDnsRecordForm');
                    var formData = serializeForm($form);
                    var modelUrl = $(this).data('modelUrl');

                    PUQajax(modelUrl, formData, 500, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

                if ($('#moveToForm').length) {
                    var $form = $('#moveToForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.dns_zone.move_to.put',$uuid)}}', formData, 500, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            loadFormData();
                            $dataTable.ajax.reload(null, false);
                        });
                }

            });

        });

    </script>
@endsection
