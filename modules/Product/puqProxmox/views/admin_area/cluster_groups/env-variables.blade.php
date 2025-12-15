<div class="row">
    <div class="col-12 col-md-6 col-lg-8">
        <div class="card mb-3" id="envVariablesComponent">
            <div class="card-body">
                <label class="form-label">
                    <i class="fa fa-code me-1"></i>
                    {{ __('Product.puqProxmox.Environment Variables') }}
                </label>

                <small class="form-text text-muted mb-2 d-block">
                    {{ __('Product.puqProxmox.These variables will be passed to the LXC and Docker container. Add key-value pairs') }}
                </small>

                <div id="envVariablesContainer"></div>

                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addEnvVariable">
                    <i class="fa fa-plus me-1"></i>{{ __('Product.puqProxmox.Add Variable') }}
                </button>

                <input type="hidden" name="env_variables" id="env_variables">
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="border rounded bg-light p-3 h-100 overflow-auto" style="max-height: 600px">
            <div class="mb-2 fw-bold"><i class="fas fa-list me-1"></i>{{ __('Product.puqProxmox.Available Variables') }}
            </div>
            <div id="availableVariables" class="d-flex flex-column gap-2 small"></div>
            @foreach($cluster_group->getEnvironmentMacros() as $var)
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

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            const container = $("#envVariablesContainer");
            const hiddenInput = $("#env_variables");
            let lastFocused = null;

            $(document).on('focus', 'input, textarea', function () {
                lastFocused = this;
            });

            function getEnvData() {
                let data = [];
                try {
                    const val = hiddenInput.val();
                    if (val) {
                        const parsed = JSON.parse(val);
                        if (Array.isArray(parsed)) data = parsed;
                    }
                } catch (e) { data = []; }
                return data;
            }

            function renderEnvVariables() {
                container.empty();
                let data = getEnvData();
                if (data.length === 0) return;

                data.forEach((item, index) => {
                    const row = $("<div>").addClass("env-variable-row mb-2 p-2 rounded")
                        .css("background-color", index % 2 === 0 ? '#f8f9fa' : '#e9ecef');

                    const r = $('<div class="row g-2 align-items-center"></div>');

                    const colKey = $('<div class="col-5"></div>');
                    const inputKey = $('<input type="text" class="form-control" data-key placeholder="Key">').val(item.key || '');
                    colKey.append(inputKey);

                    const colValue = $('<div class="col-5"></div>');
                    const inputValue = $('<input type="text" class="form-control" data-value placeholder="Value">').val(item.value || '');
                    colValue.append(inputValue);

                    const colRemove = $('<div class="col-2 text-end"></div>');
                    const btnRemove = $('<button type="button" class="btn btn-outline-danger btn-sm removeEnvVariable"><i class="fa fa-trash-alt"></i></button>');
                    colRemove.append(btnRemove);

                    r.append(colKey, colValue, colRemove);
                    row.append(r);
                    container.append(row);
                });
            }

            function updateHiddenInput() {
                const data = [];
                container.find(".env-variable-row").each(function () {
                    const key = $(this).find("[data-key]").val();
                    if (!key) return;
                    const value = $(this).find("[data-value]").val();
                    data.push({key, value});
                });
                hiddenInput.val(JSON.stringify(data));
            }

            $("#addEnvVariable").on("click", function () {
                let data = getEnvData();
                data.push({key: '', value: ''});
                hiddenInput.val(JSON.stringify(data));
                renderEnvVariables();
            });

            $(document).on("click", ".removeEnvVariable", function () {
                $(this).closest(".env-variable-row").remove();
                updateHiddenInput();
                if (getEnvData().length === 0) container.empty();
            });

            $(document).on("input", "#envVariablesContainer input", function () {
                updateHiddenInput();
            });

            $(document).on("click", ".insert-variable", function (e) {
                e.preventDefault();
                const variable = $(this).data('value');
                if (!lastFocused) return;
                const el = lastFocused;
                const start = el.selectionStart;
                const end = el.selectionEnd;
                const text = el.value;
                el.value = text.substring(0, start) + variable + text.substring(end);
                const pos = start + variable.length;
                el.selectionStart = pos;
                el.selectionEnd = pos;
                el.focus();
            });

            let observerInterval = setInterval(() => {
                try {
                    const val = hiddenInput.val();
                    if (val) {
                        const parsed = JSON.parse(val);
                        if (Array.isArray(parsed) && parsed.length > 0) {
                            renderEnvVariables();
                            clearInterval(observerInterval);
                        }
                    }
                } catch (e) {}
            }, 20);
        });
    </script>
@endsection

