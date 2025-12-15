@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('header')

@endsection

@section('buttons')

    <div class="mb-2 me-2 btn-group">
        <button type="button"
                class="btn-icon btn-outline-2x btn btn-outline-warning dropdown-toggle d-flex align-items-center"
                data-bs-toggle="dropdown">
            <i class="fas fa-exchange-alt me-2"></i>
            {{ __('Product.puqProxmox.Export / Import') }}
        </button>
        <ul class="dropdown-menu p-2">
            <li>
                <h6 class="dropdown-header">{{ __('Product.puqProxmox.Export') }}</h6>
            </li>
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center" id="export_json">
                    <i class="fas fa-file-code me-2"></i> JSON File
                </button>
            </li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li>
                <h6 class="dropdown-header">{{ __('Product.puqProxmox.Import') }}</h6>
            </li>
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center" id="import_json">
                    <i class="fas fa-file-import me-2"></i> JSON File
                </button>
            </li>
        </ul>
    </div>

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger"
            data-model-url="{{ route('admin.api.Product.puqProxmox.app_preset.delete', $uuid) }}"
            id="deleteAppPreset">
        <i class="fa fa-trash-alt"></i>
    </button>
@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.app_presets.app_preset_header')

    <div id="container">

        <form id="appPresetForm" method="POST" action="" novalidate="novalidate">

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="name" class="form-label">
                                <i class="fa fa-server me-1"></i>
                                {{ __('Product.puqProxmox.Name') }}
                            </label>
                            <input type="text" class="form-control" id="name" name="name">
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="version" class="form-label">
                                <i class="fa fa-server me-1"></i>
                                {{ __('Product.puqProxmox.Version') }}
                            </label>
                            <input type="text" class="form-control" id="version" name="version">
                        </div>

                        <div class="col-12 col-md-6 col-lg-6">
                            <label for="description" class="form-label">
                                <i class="fa fa-info-circle me-1"></i>
                                {{ __('Product.puqProxmox.Description') }}
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="puq_pm_lxc_preset_uuid" class="form-label">
                                <i class="fa fa-object-group me-1"></i>
                                {{ __('Product.puqProxmox.LXC Preset') }}
                            </label>
                            <select name="puq_pm_lxc_preset_uuid" id="puq_pm_lxc_preset_uuid"
                                    class="form-select"></select>
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="puq_pm_lxc_os_template_uuid" class="form-label">
                                <i class="fa fa-object-group me-1"></i>
                                {{ __('Product.puqProxmox.LXC OS Template') }}
                            </label>
                            <select name="puq_pm_lxc_os_template_uuid" id="puq_pm_lxc_os_template_uuid"
                                    class="form-select"></select>
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="puq_pm_dns_zone_uuid" class="form-label">
                                <i class="fa fa-object-group me-1"></i>
                                {{ __('Product.puqProxmox.DNS Zone') }}
                            </label>
                            <select name="puq_pm_dns_zone_uuid" id="puq_pm_dns_zone_uuid"
                                    class="form-select"></select>
                        </div>


                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="certificate_authority_uuid"
                                   class="form-label">{{ __('Product.puqProxmox.Certificate Authority') }} </label>
                            <select name="certificate_authority_uuid" id="certificate_authority_uuid"
                                    class="form-select mb-2 form-control"></select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-md-6 col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div>
                                <label class="form-label">
                                    <i class="fa fa-code me-1"></i>
                                    {{ __('Product.puqProxmox.Environment Variables') }}
                                </label>
                            </div>
                            <small class="form-text text-muted mb-2">
                                {{ __('Product.puqProxmox.These variables will be passed to the LXC and Docker container. Add key-value pairs') }}
                            </small>

                            <div id="envVariablesContainer"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2"
                                    id="addEnvVariable">
                                <i class="fa fa-plus me-1"></i>{{ __('Product.puqProxmox.Add Variable') }}
                            </button>
                            <input type="hidden" name="env_variables" id="env_variables">
                        </div>
                    </div>
                </div>

                <!-- Variables -->
                <div class="col-lg-4">
                    <div class="border rounded bg-light p-3 h-100 overflow-auto" style="max-height: 600px">
                        <div class="mb-2 fw-bold"><i
                                class="fas fa-list me-1"></i>{{ __('Product.puqProxmox.Available Variables') }}
                        </div>
                        <div class="d-flex flex-column gap-2 small">
                            @foreach($app_preset->getEnvironmentMacros() as $var)
                                <div class="d-flex justify-content-between align-items-start">
                                    <a href="#" class="insert-variable text-decoration-none"
                                       data-value="{{ '{' . $var['name'] . '}' }}">
                                        <code class="text-primary">{{ '{' . $var['name'] . '}' }}</code>
                                    </a>
                                    <div class="text-muted ms-3 text-end flex-grow-1">
                                        {{ $var['description'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function renderEnvVariables(data = []) {
                const container = $("#envVariablesContainer");
                container.empty();

                data.forEach((item, index) => {
                    const row = $("<div>").addClass("env-variable-row mb-2 p-2 rounded");
                    // alternate background
                    row.css("background-color", index % 2 === 0 ? '#f8f9fa' : '#e9ecef');

                    const r1 = $(`<div class="row g-2 mb-1"></div>`);
                    const colKey = $('<div class="col-6"></div>');
                    const inputKey = $('<input type="text" class="form-control" data-key>');
                    inputKey.attr("placeholder", "{{ __('Product.puqProxmox.Key') }}");
                    inputKey.val(item.key || '');
                    colKey.append(inputKey);

                    const colValue = $('<div class="col-6"></div>');
                    const inputValue = $('<input type="text" class="form-control" data-value>');
                    inputValue.attr("placeholder", "{{ __('Product.puqProxmox.Value') }}");
                    inputValue.val(item.value || '');
                    colValue.append(inputValue);

                    r1.append(colKey, colValue);
                    row.append(r1);

                    const r2 = $(`<div class="row g-2 align-items-center"></div>`);
                    const colCustom = $('<div class="col-6"></div>');
                    const inputCustom = $('<input type="text" class="form-control" data-custom-name>');
                    inputCustom.attr("placeholder", "{{ __('Product.puqProxmox.Custom Name') }}");
                    inputCustom.val(item.custom_name || '');
                    colCustom.append(inputCustom);

                    const colChecks = $('<div class="col-4 d-flex align-items-center"></div>');
                    const chkShowDiv = $('<div class="form-check me-2"></div>');
                    const chkShow = $('<input class="form-check-input" type="checkbox" data-show-to-client>');
                    chkShow.prop('checked', !!item.show_to_client);
                    const lblShow = $('<label class="form-check-label">{{ __('Product.puqProxmox.Show to Client') }}</label>');
                    chkShowDiv.append(chkShow, lblShow);

                    const chkEditDiv = $('<div class="form-check"></div>');
                    const chkEdit = $('<input class="form-check-input" type="checkbox" data-edit-by-client>');
                    chkEdit.prop('checked', !!item.edit_by_client);
                    const lblEdit = $('<label class="form-check-label">{{ __('Product.puqProxmox.Edit by Client') }}</label>');
                    chkEditDiv.append(chkEdit, lblEdit);

                    colChecks.append(chkShowDiv, chkEditDiv);

                    const colRemove = $('<div class="col-2 text-end"></div>');
                    const btnRemove = $('<button type="button" class="btn btn-outline-danger btn-sm removeEnvVariable"><i class="fa fa-trash-alt"></i></button>');
                    colRemove.append(btnRemove);

                    r2.append(colCustom, colChecks, colRemove);
                    row.append(r2);

                    container.append(row);
                });

                updateEnvVariablesInput();
            }
            function updateEnvVariablesInput() {
                const data = [];
                $("#envVariablesContainer .env-variable-row").each(function () {
                    const key = $(this).find("[data-key]").val();
                    const value = $(this).find("[data-value]").val();
                    const custom_name = $(this).find("[data-custom-name]").val();
                    const show_to_client = $(this).find("[data-show-to-client]").is(':checked');
                    const edit_by_client = $(this).find("[data-edit-by-client]").is(':checked');
                    if (key) {
                        data.push({key, value, custom_name, show_to_client, edit_by_client});
                    }
                });
                $("#env_variables").val(JSON.stringify(data));
            }


            $("#addEnvVariable").on("click", function () {
                renderEnvVariables([...(JSON.parse($("#env_variables").val() || "[]")),
                    {key: '', value: '', custom_name: '', show_to_client: false, edit_by_client: false}]);
            });

            $(document).on("click", ".removeEnvVariable", function () {
                $(this).closest(".env-variable-row").remove();
                updateEnvVariablesInput();
            });

            $(document).on("input", "#envVariablesContainer input", function () {
                updateEnvVariablesInput();
            });

            function loadFormData() {
                blockUI('container');

                PUQajax('{{ route('admin.api.Product.puqProxmox.app_preset.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#version").val(response.data?.version);
                        $("#description").val(response.data?.description || '');
                        renderEnvVariables(response.data?.env_variables || []);

                        initializeSelect2(
                            $("#puq_pm_lxc_preset_uuid"),
                            '{{ route('admin.api.Product.puqProxmox.lxc_presets.select.get') }}',
                            response.data?.puq_pm_lxc_preset_data,
                            'GET',
                            1000
                        );

                        function initOsTemplateSelect() {
                            initializeSelect2(
                                $("#puq_pm_lxc_os_template_uuid"),
                                '{{ route('admin.api.Product.puqProxmox.lxc_os_templates.select.get') }}',
                                response.data?.puq_pm_lxc_os_template_data,
                                'GET',
                                1000,
                                {},
                                {
                                    lxc_preset_uuid: $("#puq_pm_lxc_preset_uuid").val(),
                                    lxc_preset_all: true
                                }
                            );
                        }

                        initOsTemplateSelect();

                        $("#puq_pm_lxc_preset_uuid").on("change", function () {
                            let osTemplateSelect = $("#puq_pm_lxc_os_template_uuid");
                            osTemplateSelect.select2('destroy');
                            osTemplateSelect.val(null).trigger('change');
                            response.data.puq_pm_lxc_os_template_data = null;
                            initOsTemplateSelect();
                        });

                        initializeSelect2(
                            $("#puq_pm_dns_zone_uuid"),
                            '{{ route('admin.api.Product.puqProxmox.dns_zones.forward.select.get') }}',
                            response.data?.puq_pm_dns_zone_data,
                            'GET',
                            1000
                        );

                        initializeSelect2(
                            $("#certificate_authority_uuid"),
                            '{{route('admin.api.Product.puqProxmox.certificate_authorities.select.get')}}',
                            response.data?.certificate_authority_data,
                            'GET',
                            1000
                        );

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#appPresetForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqProxmox.app_preset.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            $('#deleteAppPreset').on('click', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqProxmox.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 50, $(this), 'DELETE');
                }
            });

            $('#universalModal').on('hidden.bs.modal', function () {
                $('#modalSaveButton').show();
            });

            let lastFocused = null;

            $(document).on('focus', 'input, textarea', function () {
                lastFocused = this;
            });

            $(document).on('click', '.insert-variable', function (e) {
                e.preventDefault();
                const variable = $(this).data('value');

                if (window.editor && window.editor.hasFocus()) {
                    const doc = window.editor.getDoc();
                    const cursor = doc.getCursor();
                    doc.replaceRange(variable, cursor);
                    window.editor.focus();
                    updateEnvVariablesInput();
                    return;
                }

                if (lastFocused) {
                    const el = lastFocused;
                    const start = el.selectionStart;
                    const end = el.selectionEnd;

                    const text = el.value;
                    el.value = text.substring(0, start) + variable + text.substring(end);

                    const pos = start + variable.length;
                    el.selectionStart = pos;
                    el.selectionEnd = pos;

                    el.focus();
                    updateEnvVariablesInput();
                }
            });

            $('#export_json').on('click', function (event) {
                event.preventDefault();

                PUQajax('{{ route('admin.api.Product.puqProxmox.app_preset.export.json.get', $uuid) }}', null, 3000, $(this), 'GET', null)
                    .then(function (response) {
                        if (response.status === 'success' && response.data) {
                            const jsonString = JSON.stringify(response.data, null, 4);
                            const fileName = response.data.file_name ?? 'export.json';
                            const blob = new Blob([jsonString], { type: 'application/json' });
                            const link = document.createElement('a');
                            link.href = URL.createObjectURL(blob);
                            link.download = fileName;
                            link.click();
                        } else {
                            alert('Error exporting JSON');
                        }
                    })
                    .catch(function () {
                        alert('Server error');
                    });
            });

            $('#import_json').on('click', function () {

                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');

                $modalTitle.text('{{ __('Product.puqProxmox.Import') }}');

                var formHtml = `
        <form id="importForm" class="col-md-10 mx-auto">
            <div class="mb-3">
                <label class="form-label">{{ __('Product.puqProxmox.Choose JSON file') }}</label>
                <input type="file" id="import_file" name="import_file" accept="application/json" class="form-control">
            </div>
        </form>
    `;

                $modalBody.html(formHtml);
                $('#universalModal').modal('show');
            });

            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if (!$('#importForm').length) return;

                let fileInput = document.getElementById('import_file');

                if (!fileInput.files.length) {
                    alert('Please select JSON file');
                    return;
                }

                let file = fileInput.files[0];
                let reader = new FileReader();

                reader.onload = function (e) {
                    let fileContent = e.target.result;

                    let data = {
                        import: fileContent
                    };

                    PUQajax(
                        '{{ route('admin.api.Product.puqProxmox.app_preset.import.json.post', $uuid) }}',
                        data,
                        1000,
                        $('#modalSaveButton'),
                        'POST'
                    )
                        .then(function (response) {
                            if (response.status === 'success') {
                                $('#universalModal').modal('hide');
                                loadFormData();
                            } else {
                                alert(response.message ?? 'Import failed');
                            }
                        })
                        .catch(function () {
                            alert('Server error');
                        });
                };

                reader.readAsText(file);
            });

            loadFormData();
        });
    </script>
@endsection
