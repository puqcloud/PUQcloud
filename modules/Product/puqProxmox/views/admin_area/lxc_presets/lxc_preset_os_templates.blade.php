@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-info"
            id="linkLxcPresetClusterGroup">
        <i class="fa fa-link"></i> {{ __('Product.puqProxmox.Link LXC OS Template') }}
    </button>

@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.lxc_presets.lxc_preset_header')

    <div class="main-card mb-3 card">
        <div class="card-body">
            <table style="width: 100%;" id="lxcOsTemplates"
                   class="table table-hover table-striped table-bordered">
                <thead>
                <tr>
                    <th>{{__('Product.puqProxmox.Key')}}</th>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.LXC Template')}}</th>
                    <th>{{__('Product.puqProxmox.Distribution')}}</th>
                    <th>{{__('Product.puqProxmox.Version')}}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                <tr>
                    <th>{{__('Product.puqProxmox.Key')}}</th>
                    <th>{{__('Product.puqProxmox.Name')}}</th>
                    <th>{{__('Product.puqProxmox.LXC Template')}}</th>
                    <th>{{__('Product.puqProxmox.Distribution')}}</th>
                    <th>{{__('Product.puqProxmox.Version')}}</th>
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

            var tableId = '#lxcOsTemplates';
            var ajaxUrl = '{{ route('admin.api.Product.puqProxmox.lxc_preset.lxc_os_templates.get',$uuid) }}';
            var columnsConfig = [
                {data: "key", name: "key"},
                {data: "name", name: "name"},
                {data: "template_name", name: "template_name"},
                {data: "distribution", name: "distribution"},
                {data: "version", name: "version"},
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
                            btn = btn + renderDisconnectButton(row.urls.delete);
                        }
                        return btn;
                    }
                }
            ];

            var $dataTable = initializeDataTable(tableId, ajaxUrl, columnsConfig);

            $('#linkLxcPresetClusterGroup').on('click', function () {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');

                $modalTitle.text('{{ __('Product.puqProxmox.Link') }}');

                var formHtml = `
                    <form id="createForm" class="col-md-10 mx-auto">
                        <label for="puq_pm_lxc_os_template_uuid" class="form-label">{{ __('Product.puqProxmox.LXC OS Template') }}</label>
                        <select name="puq_pm_lxc_os_template_uuid" id="puq_pm_lxc_os_template_uuid" class="form-select mb-2 form-control"></select>
                    </form>
`;
                $modalBody.html(formHtml);

                initializeSelect2(
                    $("#puq_pm_lxc_os_template_uuid"),
                    '{{ route('admin.api.Product.puqProxmox.lxc_os_templates.select.get') }}',
                    {},
                    'GET',
                    1000,
                    {
                        dropdownParent: $('#universalModal')
                    },
                    {
                        lxc_preset_uuid: '{{$uuid}}'
                    }
                );

                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#createForm').length) {
                    var $form = $('#createForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_preset.lxc_os_template.post',$uuid) }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            $dataTable.ajax.reload(null, false);
                        });
                }
            });

            $dataTable.on('click', 'button.disconnect-btn', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to unlink this LXC OS template?') }}')) {
                    PUQajax(modelUrl, null, 1000, $(this), 'DELETE')
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

        });
    </script>
@endsection
