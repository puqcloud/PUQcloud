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
                                                <i class="fas fa-server"></i>
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
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('Product.puqProxmox.Dashboard') }}</a>
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
                    {{__('Product.puqProxmox.Create')}}
                </button>
            </div>

        </div>
    </div>

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="lxc_instances"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th></th>
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

            var tableId = '#lxc_instances';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.lxc_instances.get') }}';

            var columnsConfig = [

                {
                    data: 'uuid',
                    title: 'LXC Instance',
                    width: '30%',
                    orderable: false,
                    render: (d, t, r) => {

                        const statusColor = (status) => {
                            if (!status) return 'secondary';

                            switch (status.toUpperCase()) {
                                case 'COMPLETED':
                                case 'ACTIVE':
                                case 'RUNNING':
                                    return 'success';
                                case 'ERROR':
                                case 'STOPPED':
                                    return 'danger';
                                default:
                                    return 'info';
                            }
                        };

                        const lxcStatus = r.status?.status
                            ? `<span class="badge bg-${statusColor(r.status.status)}">${r.status.status}</span>`
                            : `<span class="badge bg-secondary">NO DATA</span>`;

                        return `
                <b>${r.hostname}</b><br>
                <small class="text-muted">
                    UUID: ${r.uuid}<br>
                    VMID: ${r.vmid}
                </small>
                <hr class="my-1">

                Node: <b>${r.status?.node ?? '—'}</b><br>
                Cluster: ${r.env_variables?.find(v => v.key === 'CLUSTER')?.value ?? '—'}
                <hr class="my-1">

                LXC: ${lxcStatus}<br>
            `;
                    }
                },

                {
                    data: 'service.uuid',
                    title: 'Service',
                    orderable: false,
                    render: (d, t, r) => {

                        const statusColor = (status) => {
                            if (!status) return 'secondary';

                            switch (status.toUpperCase()) {
                                case 'COMPLETED':
                                case 'ACTIVE':
                                    return 'success';
                                case 'ERROR':
                                    return 'danger';
                                default:
                                    return 'info';
                            }
                        };

                        const s = r.service;

                        if (!s) {
                            return `
                    <span class="badge bg-danger">NO SERVICE</span><br>
                    <small class="text-muted">
                        Instance exists in Proxmox<br>
                        but not linked to billing
                    </small>
                `;
                        }

                        return `
                <b>${s.admin_label}</b><br>
                <small class="text-muted">
                    ${linkify('Service:' + s.uuid)}<br>
                    ${linkify('Client:' + s.client_uuid)}
                </small>
                <hr class="my-1">

                Status:
                <span class="badge bg-${statusColor(s.status)}">${s.status}</span><br>

                Provision:
                <span class="badge bg-${statusColor(s.provision_status)}">
                    ${s.provision_status}
                </span>

                ${s.create_error
                            ? `<hr class="my-1">
                       <small class="text-danger">${s.create_error}</small>`
                            : ''}
            `;
                    }
                }

            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig, function () {
            }, {
                order: []
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{__('Product.puqProxmox.Are you sure you want to delete this record?')}}')) {
                    PUQajax(modelUrl, null, 1000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

        });
    </script>
@endsection
