@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-shield-alt"></i>
                                            </span>
                        <span class="d-inline-block">{{ $title }}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav class="" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a>
                                        <i aria-hidden="true" class="fa fa-home"></i>
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('Product.puqVPNCP.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{ $title }}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="page-title-actions">
                <button type="button"
                        class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                        id="create">
                    <i class="fa fa-plus"></i>
                    {{__('Product.puqVPNCP.Create')}}
                </button>
            </div>

        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="servers"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqVPNCP.Name')}}</th>
                    <th>{{__('Product.puqVPNCP.API URL')}}</th>
                    <th>{{__('Product.puqVPNCP.Accounts')}}</th>
                    <th>{{__('Product.puqVPNCP.License')}}</th>
                    <th>{{__('Product.puqVPNCP.Protocols')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqVPNCP.Name')}}</th>
                    <th>{{__('Product.puqVPNCP.API URL')}}</th>
                    <th>{{__('Product.puqVPNCP.Accounts')}}</th>
                    <th>{{__('Product.puqVPNCP.License')}}</th>
                    <th>{{__('Product.puqVPNCP.Protocols')}}</th>
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

            var tableId = '#servers';
            var ajaxUrl = '{{ route('admin.api.Product.puqVPNCP.servers.get') }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `<span class="fw-bold">${data}</span>`;
                    }
                },
                {data: "api_url", name: "api_url"},
                {
                    data: "active_clients",
                    orderable: false,
                    render: function (data, type, row) {
                        let total = row.total_accounts || 0;
                        let free = row.free_slots || 0;
                        let used = total - free;
                        if (total == 0) return '<span class="text-muted">-</span>';
                        let pct = Math.round(used / total * 100);
                        let barClass = pct > 90 ? 'bg-danger' : (pct > 70 ? 'bg-warning' : 'bg-success');
                        return `<div>
                            <small>${used}/${total} <span class="text-muted">(${free} {{ __('Product.puqVPNCP.free') }})</span></small>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar ${barClass}" style="width: ${pct}%"></div>
                            </div>
                        </div>`;
                    }
                },
                {
                    data: "cached_status",
                    orderable: false,
                    render: function (data, type, row) {
                        if (!data || !data.license) return '<span class="text-muted">-</span>';
                        let license = data.license;
                        // Normalize ISO-8601 to "Y-m-d H:i:s"; short/plain strings pass through.
                        const fmtDt = v => v ? String(v).replace('T', ' ').substring(0, 19) : '';
                        let valid = license.valid;
                        if (valid) {
                            let expiry = license.expiration_date ? `<br><small class="text-muted">${fmtDt(license.expiration_date)}</small>` : '';
                            return `<span class="badge bg-success">{{ __('Product.puqVPNCP.Valid') }}</span>${expiry}`;
                        } else {
                            let reason = fmtDt(license.expiration_date);
                            return `<div>
                                <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>{{ __('Product.puqVPNCP.Invalid') }}</span>
                                ${reason ? `<br><small class="text-danger fw-bold">${reason}</small>` : ''}
                            </div>`;
                        }
                    }
                },
                {
                    data: "cached_status",
                    orderable: false,
                    render: function (data, type, row) {
                        if (!data) return '<span class="text-muted">-</span>';

                        let protocolsDef = [
                            {key: 'wireguard', label: 'WG', bg: 'primary'},
                            {key: 'openvpn', label: 'OVPN', bg: 'info'},
                            {key: 'ikev2', label: 'IKEv2', bg: 'warning text-dark'}
                        ];

                        let proto = data.protocols || {};
                        let lines = [];
                        let hasAny = false;

                        protocolsDef.forEach(p => {
                            if (!proto[p.key]) return;
                            hasAny = true;
                            lines.push(
                                `<span class="badge bg-${p.bg} me-1">${p.label}</span>`
                            );
                        });

                        if (!hasAny) return '<span class="text-muted">-</span>';

                        let sys = data.system || {};
                        let totalClients = parseInt(sys.count_accounts || 0);
                        let infoHtml = totalClients > 0
                            ? `<div class="mt-1"><small class="text-muted">${totalClients} {{ __('Product.puqVPNCP.clients') }}</small></div>`
                            : '';

                        return `<div>${lines.join('')}${infoHtml}</div>`;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.edit) {
                            btn = btn + renderEditLink(row.urls.edit);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig, undefined, {
                createdRow: function(row, data, dataIndex) {
                    if (data.cached_status && data.cached_status.license && !data.cached_status.license.valid) {
                        $(row).addClass('table-danger');
                        $(row).css({'border-left': '4px solid #dc3545'});
                    }
                }
            });

            $('#create').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__('Product.puqVPNCP.Create')}}');

                var formHtml = `
            <form id="createForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqVPNCP.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqVPNCP.Name')}}">
                    </div>
                </div>
            </form>`;
                $modalBody.html(formHtml);
                $('#universalModal').modal('show');
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{__('Product.puqVPNCP.Are you sure you want to delete this record?')}}')) {
                    PUQajax(modelUrl, null, 3000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.Product.puqVPNCP.server.post')}}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
