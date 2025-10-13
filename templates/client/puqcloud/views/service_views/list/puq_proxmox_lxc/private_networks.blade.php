@php
    $backgroundUrl = $product_group->images['background'] ?? null;
@endphp

@if($backgroundUrl)
    @section('background')
        <style>
            .puq-background-blur {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5)),
                url('{{ $backgroundUrl }}') no-repeat center center fixed;
                background-size: cover;
                filter: blur(6px);
                z-index: 0;
            }
        </style>
    @endsection
@endif

<div id="header" class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="fa fa-network-wired icon-gradient bg-happy-green"></i>
            </div>
            <div>
                <div class="page-title-head center-elem">
                    <span class="d-inline-block">
                        {{ __('Product.puqProxmox.Private Networks') }}
                    </span>
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
                                <a href="{{ route('client.web.panel.cloud.group', $product_group->uuid) }}">{{ $title }}</a>
                            </li>
                            <li class="active breadcrumb-item" aria-current="page">
                                {{ __('Product.puqProxmox.Private Networks') }}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <div class="page-title-actions">

            <a href="{{ route('client.web.panel.cloud.group', $product_group->uuid) }}"
               class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i>
                {{ __('Product.puqProxmox.Back') }}
            </a>

            <button type="button"
                    class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
                    id="add">
                <i class="fa fa-plus"></i>
                {{__('Product.puqProxmox.Add Private Network')}}
            </button>
        </div>
    </div>
</div>

<div class="container px-0">
    <div class="main-card card">
        <div class="card-body">

            <table id="private_networks" class="table table-hover table-striped table-bordered w-100">
                <thead class="table-light">
                <tr>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.Type')}}</th>
                    <th>{{__('Product.puqProxmox.Location')}}</th>
                    <th>{{__('Product.puqProxmox.IPv4 Network')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            blockUI('mainInner');
            var $table = $('#private_networks');
            var ajaxUrl = '{{ route('client.api.cloud.group.list.get',['uuid'=>$product_group->uuid,'method'=>'GetPrivateNetworks']) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `
                <span class="badge bg-light text-dark px-3 py-1 shadow-sm">
                    <i class="fas fa-network-wired me-1"></i>${data}
                </span>
            `;
                    }
                },
                {
                    data: "type",
                    name: "type",
                    render: function (data, type, row) {
                        let icon = data === 'local_private' ? 'fas fa-home' : 'fas fa-globe';
                        let color = data === 'local_private' ? 'text-primary' : 'text-success';
                        let name = data === 'local_private' ? '{{__('Product.puqProxmox.Private Local')}}' : '{{__('Product.puqProxmox.Private Global')}}';
                        return `<span class="${color}"><i class="${icon} me-1"></i>${name}</span>`;
                    }
                },
                {
                    data: 'location',
                    render: function (data, type, row) {
                        const name = row.location_data && row.location_data.name ? row.location_data.name : 'All';
                        const value = row.location_data && row.location_data.value ? row.location_data.value : '-----';

                        return `
                <div class="d-flex flex-column">
                    <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>${name}</small>
                    <span>${value}</span>
                </div>
            `;
                    }
                },
                {
                    data: "ipv4_network",
                    name: "ipv4_network",
                    render: function (data, type, row) {
                        return `<span class="text-monospace"><i class="fas fa-sitemap me-1"></i>${data}</span>`;
                    }
                },
                {
                    data: 'urls',
                    title: '',
                    className: "text-center",
                    render: function (data, type, row) {
                        let btn = '';
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete, '<i class="fas fa-trash-alt"></i>');
                        }
                        return btn;
                    }
                },
            ];

            var $dataTable = initializeDataTable($table, ajaxUrl, columnsConfig, DataTableAddData, {});

            function DataTableAddData() {
                return {};
            }

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

            $('#add').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__('Product.puqProxmox.Create')}}');

                var formHtml = `
        <form id="addForm" class="col-md-10 mx-auto">
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
            <div class="mb-3" id="locationWrapper">
                <label for="location" class="form-label">{{__('Product.puqProxmox.Location')}}</label>
                <select name="location" id="location" class="form-select mb-2 form-control"></select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="ipv4_network">{{ __('Product.puqProxmox.IPv4 Network') }}</label>
                <input type="text" class="form-control" id="ipv4_network" name="ipv4_network" placeholder="ex: 172.16.0.0/24" required>
            </div>
        </form>`;
                $modalBody.html(formHtml);

                initializeSelect2(
                    $("#location"),
                    '{{ route('client.api.cloud.group.list.post',['uuid'=>$product_group->uuid,'method'=>'GetPrivateNetworkLocationsSelect']) }}',
                    {},
                    'GET',
                    1000,
                    {
                        dropdownParent: $('#universalModal')
                    },
                    {}
                );

                $('#type').on('change', function () {
                    if ($(this).val() === 'local') {
                        $('#locationWrapper').show();
                    } else {
                        $('#locationWrapper').hide();
                    }
                }).trigger('change');

                $('#universalModal').modal('show');
            });

            $('#modalConfirmButton').on('click', function (event) {
                event.preventDefault();

                if ($('#addForm').length) {
                    var $form = $('#addForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('client.api.cloud.group.list.post',['uuid'=>$product_group->uuid,'method'=>'PostPrivateNetwork']) }}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            unblockUI('mainInner');

        });
    </script>
@endsection
