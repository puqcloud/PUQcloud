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
            <table style="width: 100%;" id="lxc_presets"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th><i class="fas fa-tag"></i> {{__('Product.puqProxmox.Name')}}</th>
                    <th><i class="fas fa-microchip"></i> {{__('Product.puqProxmox.Cores')}}</th>
                    <th><i class="fas fa-memory"></i> {{__('Product.puqProxmox.RAM')}}</th>
                    <th><i class="fas fa-exchange-alt"></i> {{__('Product.puqProxmox.Swap')}}</th>
                    <th><i class="fas fa-hdd"></i> {{__('Product.puqProxmox.Disk')}}</th>
                    <th><i class="fas fa-tachometer-alt"></i> {{__('Product.puqProxmox.CPU Limit')}}</th>
                    <th><i class="fas fa-weight-hanging"></i>{{__('Product.puqProxmox.CPU Weight')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th><i class="fas fa-tag"></i> {{__('Product.puqProxmox.Name')}}</th>
                    <th><i class="fas fa-microchip"></i> {{__('Product.puqProxmox.Cores')}}</th>
                    <th><i class="fas fa-memory"></i> {{__('Product.puqProxmox.RAM')}}</th>
                    <th><i class="fas fa-exchange-alt"></i> {{__('Product.puqProxmox.Swap')}}</th>
                    <th><i class="fas fa-hdd"></i> {{__('Product.puqProxmox.Disk')}}</th>
                    <th><i class="fas fa-tachometer-alt"></i> {{__('Product.puqProxmox.CPU Limit')}}</th>
                    <th><i class="fas fa-weight-hanging"></i>{{__('Product.puqProxmox.CPU Weight')}}</th>
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

            var tableId = '#lxc_presets';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.lxc_presets.get') }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return data;
                    }
                },
                {
                    data: "cores",
                    name: "cores",
                },
                {
                    data: "memory",
                    name: "memory",
                    render: function (data) {
                        return `${formatSizeMB(data)}`;
                    }
                },
                {
                    data: "swap",
                    name: "swap",
                    render: function (data) {
                        return `${formatSizeMB(data)}`;
                    }
                },
                {
                    data: "rootfs_size",
                    name: "rootfs_size",
                    render: function (data) {
                        return `${formatSizeMB(data)}`;
                    }
                },
                {
                    data: "cpulimit",
                    name: "cpulimit",
                },
                {
                    data: "cpuunits",
                    name: "cpuunits",
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';

                        if (row.urls.edit) {
                            btn = btn + renderEditButton(row.urls.edit);
                        }
                        if (row.urls.delete) {
                            btn = btn + renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            function formatSizeMB(value) {
                if (!value || isNaN(value)) return '-';
                if (value < 1024) {
                    return value + ' MB';
                }
                return (value / 1024).toFixed(1) + ' GB';
            }

            $('#create').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{__('Product.puqProxmox.Create')}}');

                var formHtml = `
            <form id="createForm" class="col-md-10 mx-auto">
                <div class="mb-3">
                    <label class="form-label" for="name">{{__('Product.puqProxmox.Name')}}</label>
                    <div>
                        <input type="text" class="form-control input-mask-trigger" id="name" name="name" placeholder="{{__('Product.puqProxmox.Name')}}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="puq_pm_dns_zone_uuid" class="form-label">{{ __('Product.puqProxmox.DNS Zone') }}</label>
                    <select name="puq_pm_dns_zone_uuid" id="puq_pm_dns_zone_uuid" class="form-select mb-2 form-control"></select>
                </div>
            </form>`;
                $modalBody.html(formHtml);

                initializeSelect2(
                    $("#puq_pm_dns_zone_uuid"),
                    '{{ route('admin.api.Product.puqProxmox.dns_zones.forward.select.get') }}',
                    {},
                    'GET',
                    1000,
                    {
                        dropdownParent: $('#universalModal')
                    }
                );

                $('#universalModal').modal('show');
            });

            $dataTable.on('click', 'button.edit-btn', function (e) {
                e.preventDefault();
                window.location.href = $(this).data('model-url');
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{__('Product.puqProxmox.Are you sure you want to delete this record?')}}')) {
                    PUQajax(modelUrl, null, 1000, null, 'DELETE')
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

                    PUQajax('{{route('admin.api.Product.puqProxmox.lxc_preset.post')}}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

            });

        });
    </script>
@endsection
