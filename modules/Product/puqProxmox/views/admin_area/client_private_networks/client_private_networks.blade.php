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
            <table style="width: 100%;" id="client_private_networks"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.Client')}}</th>
                    <th>{{__('Product.puqProxmox.Type')}}</th>
                    <th>{{__('Product.puqProxmox.Cluster Croup')}}</th>
                    <th>{{__('Product.puqProxmox.Bridge')}}.{{__('Product.puqProxmox.Vlan')}}</th>
                    <th>{{__('Product.puqProxmox.IPv4 Network')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.Client')}}</th>
                    <th>{{__('Product.puqProxmox.Type')}}</th>
                    <th>{{__('Product.puqProxmox.Cluster Croup')}}</th>
                    <th>{{__('Product.puqProxmox.Bridge')}}.{{__('Product.puqProxmox.Vlan')}}</th>
                    <th>{{__('Product.puqProxmox.IPv4 Network')}}</th>
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

            var tableId = '#client_private_networks';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.client_private_networks.get') }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                },
                {
                    data: 'client_uuid',
                    render: function (data, type, row) {
                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">${row.client_firstname} ${row.client_lastname} ${row.client_company_name ? '(' + row.client_company_name + ')' : ''}</div>
                            <div class="widget-subheading">
                                    ${linkify(`client:${row.client_uuid}`, true)}
                            </div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: "type",
                    name: "type",
                },
                {
                    data: 'puq_pm_cluster_group_uuid',
                    render: function (data, type, row) {
                        const groupName = row.puq_pm_cluster_group_data && row.puq_pm_cluster_group_data.name
                            ? row.puq_pm_cluster_group_data.name
                            : 'All';

                        const groupUuid = row.puq_pm_cluster_group_uuid || '-----';

                        return `<div class="widget-content p-0">
                    <div class="widget-content-wrapper">
                        <div class="widget-content-left">
                            <div class="widget-heading">${groupName}</div>
                            <div class="widget-subheading">${groupUuid}</div>
                        </div>
                    </div>
                </div>`;
                    }
                },
                {
                    data: "bridge",
                    render: function (data, type, row) {
                        return `${row.bridge}.${row.vlan_tag}`;
                    }
                },
                {
                    data: "ipv4_network",
                    name: "ipv4_network",
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $('#create').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__('Product.puqProxmox.Create')}}');

                var formHtml = `
            <form id="createForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                </div>
               <div class="mb-3">
                    <label for="type" class="form-label">{{__('main.Type')}}</label>
                    <select name="type" id="type" class="form-select mb-2 form-control">
                        <option value="local">Local</option>
                        <option value="global">Global</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="puq_pm_cluster_group_uuid" class="form-label">{{__('Product.puqProxmox.Cluster Group')}}</label>
                    <select name="puq_pm_cluster_group_uuid" id="puq_pm_cluster_group_uuid" class="form-select mb-2 form-control"></select>
                </div>
                <div class="mb-3">
                    <label for="client_uuid" class="form-label">{{__('Product.puqProxmox.Client')}}</label>
                    <select name="client_uuid" id="client_uuid" class="form-select mb-2 form-control"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="bridge">{{ __('Product.puqProxmox.Bridge') }}</label>
                    <input type="text" class="form-control" id="bridge" name="bridge" placeholder="ex: vmbr0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="vlan_tag">{{ __('Product.puqProxmox.Vlan Tag') }}</label>
                    <input type="number" min=1 max=4096 class="form-control" id="vlan_tag" name="vlan_tag" placeholder="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ipv4_network">{{ __('Product.puqProxmox.IPv4 Network') }}</label>
                    <input type="text" class="form-control" id="ipv4_network" name="ipv4_network" placeholder="ex: 172.16.0.0/24" required>
                </div>

            </form>`;
                $modalBody.html(formHtml);

                initializeSelect2(
                    $("#client_uuid"),
                    '{{route('admin.api.clients.select.get')}}',
                    {},
                    'GET',
                    1000,
                    {
                        dropdownParent: $('#universalModal')
                    },
                    {}
                );

                initializeSelect2(
                    $("#puq_pm_cluster_group_uuid"),
                    '{{ route('admin.api.Product.puqProxmox.cluster_groups.select.get') }}',
                    {},
                    'GET',
                    1000,
                    {
                        dropdownParent: $('#universalModal')
                    },
                    {}
                );


                $('#universalModal').modal('show');
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

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{route('admin.api.Product.puqProxmox.client_private_network.post')}}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

        });
    </script>
@endsection
