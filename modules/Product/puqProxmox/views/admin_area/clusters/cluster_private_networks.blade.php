@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
@endsection

@section('buttons')
    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
            id="addPrivateNetwork">
        <i class="fa fa-plus"></i> <i
            class="fa fa-network-wired"></i> {{ __('Product.puqProxmox.Add Private Network') }}
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.clusters.cluster_header')

    <div id="container">

        <div class="main-card mb-3 card">
            <div class="card-body">
                <table style="width: 100%;" id="private_networks"
                       class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Type') }}</th>
                        <th>{{ __('Product.puqProxmox.MAC Pool') }}</th>
                        <th>{{ __('Product.puqProxmox.Bridge') }}</th>
                        <th>{{ __('Product.puqProxmox.Tags') }}</th>
                        <th>{{ __('Product.puqProxmox.Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th>{{ __('Product.puqProxmox.Name') }}</th>
                        <th>{{ __('Product.puqProxmox.Type') }}</th>
                        <th>{{ __('Product.puqProxmox.MAC Pool') }}</th>
                        <th>{{ __('Product.puqProxmox.Bridge') }}</th>
                        <th>{{ __('Product.puqProxmox.Tags') }}</th>
                        <th>{{ __('Product.puqProxmox.Actions') }}</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            var tableId = '#private_networks';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.cluster.private_networks.get', $uuid) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    render: function (data, type, row) {
                        return `<i class="fa fa-network-wired text-primary me-1"></i> <b>${data}</b>`;
                    }
                },
                {
                    data: "type",
                    name: "type",
                    render: function (data, type, row) {
                        let typeIcon = '';
                        let typeName = '';
                        if (row.type === 'local_private') {
                            typeIcon = '<i class="fa fa-house text-success me-1" title="Local network"></i>';
                            typeName = '{{ __('Product.puqProxmox.Local network')}}'
                        } else if (row.type === 'global_private') {
                            typeIcon = '<i class="fa fa-globe text-warning me-1" title="Global network"></i>';
                            typeName = '{{ __('Product.puqProxmox.Global network')}}'
                        }
                        return `${typeIcon}<i class="fa fa-network-wired text-primary me-1"></i> <b>${typeName}</b>`;
                    }
                },
                {
                    data: "mac_pool_name",
                    name: "mac_pool_name",
                    orderable: false,
                    render: function (data, type, row) {
                        if (!data) {
                            return '<span class="badge bg-secondary">Auto</span>';
                        }

                        return `
            <div>
                <strong>${data}</strong><br>
                <small class="text-muted">${row.first_mac} – ${row.last_mac}</small>
            </div>
        `;
                    }
                },
                {
                    data: "bridge",
                    name: "bridge",
                    orderable: false,
                    render: function (data, type, row) {
                        return `
            <div>
                <strong>${data}</strong>
            </div>
        `;
                    }
                },
                {
                    orderable: false,
                    data: "tags",
                    className: "w-25",
                    width: "100%",
                    render: function (data, type, row) {
                        const options = data.map(v => `<option value="${v}" selected>${v}</option>`).join('');
                        return `<select class="tag-select"
                                    data-uuid="${row.uuid}"
                                    data-model="${row.model}"
                                    multiple>${options}</select>`;
                    }
                },
                {
                    data: 'urls',
                    className: "text-center",
                    orderable: false,
                    render: function (data, type, row) {
                        let btn = '';
                        if (row.urls.edit) {
                            btn += renderEditButton(row.urls.edit);
                        }
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 1000, $(this), 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $('#addPrivateNetwork').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');

                $modalTitle.text('{{ __('Product.puqProxmox.Create') }}');

                var formHtml = `
                    <form id="createForm" class="col-md-10 mx-auto">
                        <input type="hidden" class="form-control" id="puq_pm_cluster_uuid" name="puq_pm_cluster_uuid" value="{{ $uuid }}">
                        <div class="mb-3">
                            <label class="form-label" for="name">{{ __('Product.puqProxmox.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="ex: private-network-01" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">{{ __('Product.puqProxmox.Type') }}</label>
                            <select name="type" id="type" class="form-select mb-2 form-control">
                                <option value="local_private">{{ __('Product.puqProxmox.Local network')}}</option>
                                <option value="global_private">{{ __('Product.puqProxmox.Global network')}}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="bridge">{{ __('Product.puqProxmox.Bridge') }}</label>
                            <input type="text" class="form-control" id="bridge" name="bridge" placeholder="ex: vmbr0" required>
                        </div>
                        <div class="mb-3">
                            <label for="puq_pm_mac_pool_uuid" class="form-label">{{ __('Product.puqProxmox.MAC Pool') }}</label>
                            <select name="puq_pm_mac_pool_uuid" id="puq_pm_mac_pool_uuid" class="form-select mb-2 form-control"></select>
                        </div>
                    </form>`;

                $modalBody.html(formHtml);

                initializeSelect2(
                    $("#puq_pm_mac_pool_uuid"),
                    '{{ route('admin.api.Product.puqProxmox.mac_pools.select.get') }}',
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

                var modelUrl = $(this).data('model-url');
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                var $modalSaveButton = $('#modalSaveButton');
                $modalSaveButton.data('modelUrl', modelUrl);

                $modalTitle.text('{{__('Product.puqProxmox.Edit')}}');

                const formHtml = `
            <form id="editForm" class="col-md-10 mx-auto">
                <input type="hidden" class="form-control" id="puq_pm_cluster_uuid" name="puq_pm_cluster_uuid" value="{{ $uuid }}">
                        <div class="mb-3">
                            <label class="form-label" for="name">{{ __('Product.puqProxmox.Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="ex: private-network-01" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">{{ __('Product.puqProxmox.Type') }}</label>
                            <select name="type" id="type" class="form-select mb-2 form-control">
                                <option value="local_private">{{ __('Product.puqProxmox.Local network')}}</option>
                                <option value="global_private">{{ __('Product.puqProxmox.Global network')}}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="bridge">{{ __('Product.puqProxmox.Bridge') }}</label>
                            <input type="text" class="form-control" id="bridge" name="bridge" placeholder="ex: vmbr0" required>
                        </div>
                        <div class="mb-3">
                            <label for="puq_pm_mac_pool_uuid" class="form-label">{{ __('Product.puqProxmox.MAC Pool') }}</label>
                            <select name="puq_pm_mac_pool_uuid" id="puq_pm_mac_pool_uuid" class="form-select mb-2 form-control"></select>
                        </div>
            </form>`;

                $modalBody.html(formHtml);

                PUQajax(modelUrl, {}, 50, $(this), 'GET')
                    .then(function (response) {
                        $('#name').val(response.data.name);
                        $('#bridge').val(response.data.bridge);
                        $('#type').val(response.data.type);

                        initializeSelect2(
                            $("#puq_pm_mac_pool_uuid"),
                            '{{ route('admin.api.Product.puqProxmox.mac_pools.select.get') }}',
                            response.data.puq_pm_mac_pool_data,
                            'GET',
                            1000,
                            {
                                dropdownParent: $('#universalModal')
                            }
                        );

                        $('#universalModal').modal('show');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.Product.puqProxmox.private_network.post') }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                            loadFormData();
                        });
                }

                if ($('#editForm').length) {
                    var $form = $('#editForm');
                    var formData = serializeForm($form);
                    var modelUrl = $(this).data('model-url');

                    PUQajax(modelUrl, formData, 500, $(this), 'PUT', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }

            });

            $dataTable.on('draw responsive-display', function () {
                $('.tag-select').each(function () {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        initializeTagSelect2(this, {
                            url: '{{ route('admin.api.Product.puqProxmox.tag-editor.tags.search.get') }}'
                        });
                    }
                });
            });

            $dataTable.on('change', '.tag-select', function () {
                const uuid = $(this).data('uuid');
                const model = $(this).data('model');
                const tags = $(this).val();

                PUQajax('{{route('admin.api.Product.puqProxmox.tag-editor.tags.update.get')}}', {
                    uuid: uuid,
                    model: model,
                    tags: tags
                }, 1000, null, 'POST')
                    .then(() => {
                        console.log('Tags updated');
                    })
                    .catch(errors => {
                        console.error('Error updating tags:', errors);
                    });
            });

        });

    </script>
@endsection
