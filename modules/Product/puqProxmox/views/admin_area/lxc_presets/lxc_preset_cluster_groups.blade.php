@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
            id="addLxcPresetClusterGroup">
        <i class="fa fa-plus"></i> <i class="fa fa-server"></i> {{ __('Product.puqProxmox.Add ClusterGroup') }}
    </button>

@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.lxc_presets.lxc_preset_header')

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="lxcPresetClusterGroups"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{ __('Product.puqProxmox.Name') }}</th>
                    <th>{{ __('Product.puqProxmox.Node') }}</th>
                    <th>{{ __('Product.puqProxmox.ROOTFS Storage') }}</th>
                    <th>{{ __('Product.puqProxmox.Additional storage') }}</th>
                    <th>{{ __('Product.puqProxmox.Backup Storage') }}</th>
                    <th>{{ __('Product.puqProxmox.Public Network') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                <tr>
                    <th>{{ __('Product.puqProxmox.Name') }}</th>
                    <th>{{ __('Product.puqProxmox.Node') }}</th>
                    <th>{{ __('Product.puqProxmox.ROOTFS Storage') }}</th>
                    <th>{{ __('Product.puqProxmox.Additional storage') }}</th>
                    <th>{{ __('Product.puqProxmox.Backup Storage') }}</th>
                    <th>{{ __('Product.puqProxmox.Public Network') }}</th>
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

            var tableId = '#lxcPresetClusterGroups';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.lxc_preset.lxc_preset_cluster_groups.get',$uuid) }}';
            var columnsConfig = [
                {
                    data: "name",
                    name: "name",
                    width: '20%'
                },

                {
                    orderable: false,
                    data: "node_tags",
                    width: '15%',
                    render: function (data, type, row) {
                        const options = data.map(v => `<option value="${v}" selected>${v}</option>`).join('');
                        return `<select class="tag-select"
                                    data-uuid="${row.uuid}"
                                    data-model="${row.model}"
                                    data-type="node"
                                    multiple>${options}</select>`;
                    }
                },

                {
                    orderable: false,
                    data: "rootfs_storage_tags",
                    width: '15%',
                    render: function (data, type, row) {
                        const options = data.map(v => `<option value="${v}" selected>${v}</option>`).join('');
                        return `<select class="tag-select"
                                    data-uuid="${row.uuid}"
                                    data-model="${row.model}"
                                    data-type="rootfs_storage"
                                    multiple>${options}</select>`;
                    }
                },

                {
                    orderable: false,
                    data: "additional_storage_tags",
                    width: '15%',
                    render: function (data, type, row) {
                        const options = data.map(v => `<option value="${v}" selected>${v}</option>`).join('');
                        return `<select class="tag-select"
                                    data-uuid="${row.uuid}"
                                    data-model="${row.model}"
                                    data-type="additional_storage"
                                    multiple>${options}</select>`;
                    }
                },

                {
                    orderable: false,
                    data: "backup_storage_tags",
                    width: '15',
                    render: function (data, type, row) {
                        const options = data.map(v => `<option value="${v}" selected>${v}</option>`).join('');
                        return `<select class="tag-select"
                                    data-uuid="${row.uuid}"
                                    data-model="${row.model}"
                                    data-type="backup_storage"
                                    multiple>${options}</select>`;
                    }
                },

                {
                    orderable: false,
                    data: "public_network_tags",
                    width: '15%',
                    render: function (data, type, row) {
                        const options = data.map(v => `<option value="${v}" selected>${v}</option>`).join('');
                        return `<select class="tag-select"
                                    data-uuid="${row.uuid}"
                                    data-model="${row.model}"
                                    data-type="public_network"
                                    multiple>${options}</select>`;
                    }
                },
                {
                    data: 'urls',
                    className: "center",
                    orderable: false,
                    render: function (data, type, row) {
                        var btn = '';
                        if (row.urls.delete) {
                            btn += renderDeleteButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $('#addLxcPresetClusterGroup').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');

                $modalTitle.text('{{ __('Product.puqProxmox.Create') }}');

                var formHtml = `
                    <form id="createForm" class="col-md-10 mx-auto">
                        <input type="hidden" class="form-control" id="puq_pm_lxc_preset_uuid" name="puq_pm_lxc_preset_uuid" value="{{ $uuid }}">
                            <label for="puq_pm_cluster_group_uuid" class="form-label">
                                {{ __('Product.puqProxmox.Cluster Group') }}
                </label>
                <select name="puq_pm_cluster_group_uuid" id="puq_pm_cluster_group_uuid" class="form-select mb-2 form-control"></select>
        </form>
`;

                $modalBody.html(formHtml);

                initializeSelect2(
                    $("#puq_pm_cluster_group_uuid"),
                    '{{ route('admin.api.Product.puqProxmox.cluster_groups.select.get') }}',
                    {},
                    'GET',
                    1000,
                    {
                        dropdownParent: $('#universalModal')
                    }
                );

                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_preset_cluster_group.post') }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                            loadFormData();
                        });
                }
            });

            $dataTable.on('click', 'button.delete-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 1000, null, 'DELETE')
                        .then(function (response) {
                            if (response.status === "success") {
                                $dataTable.ajax.reload(null, false);
                            }
                        });
                }
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                $('#modalSaveButton').show();
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
                const type = $(this).data('type');
                const tags = $(this).val();

                PUQajax('{{route('admin.api.Product.puqProxmox.tag-editor.tags.update.get')}}', {
                    uuid: uuid,
                    model: model,
                    type: type,
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
