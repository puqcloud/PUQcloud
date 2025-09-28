@section('content')
    @parent
    <div class="container px-0">
        <div class="card shadow-sm mb-2">
            <div class="card-body">

                {{-- Interface Address --}}
                <div class="mb-3">
                    <div class="text-center my-4">
                        <label class="form-label fw-bold d-block mb-2 fs-4">
                            <i class="fas fa-globe me-2"></i>{{ __('Product.puqNextcloud.WEB Interface Address') }}
                        </label>
                        <a href="{{ $server_url }}" target="_blank"
                           class="d-inline-block text-decoration-none fs-1 fw-semibold text-primary">
                            {{ $server_url }}
                        </a>
                    </div>
                </div>

                <div class="row">
                    {{-- Username --}}
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user me-2"></i>{{ __('Product.puqNextcloud.Username') }}
                        </label>
                        <div class="input-group">
                            <input type="text" id="username" class="form-control"
                                   value="{{ $service_data['username'] }}" readonly>
                            <button onclick="copyUsername()" class="btn btn-outline-danger">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-lock me-2"></i>{{ __('Product.puqNextcloud.Password') }}
                        </label>
                        <div class="input-group">
                            <input type="password" id="password" class="form-control"
                                   value="{{ $service_data['password'] }}" disabled>
                            <button id="showPasswordButton" class="btn btn-outline-secondary">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="copyPassword()" class="btn btn-outline-danger">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Quota --}}
                <div id="quota-container" class="my-4">
                    <label class="form-label fw-bold"><i
                            class="fas fa-hdd me-2"></i>{{ __('Product.puqNextcloud.Storage usage') }}</label>

                    <div id="quota-legend" class="mb-1 fw-bold text-center">
                        {{ __('Product.puqNextcloud.Loading') }}...
                    </div>

                    <div class="position-relative">
                        <div class="progress" style="height: 30px; position: relative;">
                            <div id="quota-used"
                                 class="progress-bar bg-danger progress-bar-striped progress-bar-animated text-white fw-bold"
                                 role="progressbar" style="width: 0%;">
                            </div>
                            <div id="quota-free" class="progress-bar bg-success text-white fw-bold"
                                 role="progressbar" style="width: 100%;">
                            </div>
                        </div>

                        <div id="quota-spinner"
                             class="d-flex justify-content-center align-items-center position-absolute top-0 start-0 w-100"
                             style="z-index: 10; background: rgba(255,255,255,0.5);">
                            <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;">
                                <span class="visually-hidden">{{ __('Product.puqNextcloud.Loading') }}</span>
                            </div>
                        </div>

                        <div id="quota-error"
                             class="position-absolute top-50 start-50 translate-middle text-danger fw-bold d-none text-center w-100"
                             style="z-index: 20;">
                            {{ __('Product.puqNextcloud.Error loading quota') }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent

    <script>
        function formatSize(bytes) {
            const units = ['MB', 'GB', 'TB'];
            let size = bytes / 1024 / 1024;
            let i = 0;
            while (size >= 1024 && i < units.length - 1) {
                size /= 1024;
                i++;
            }
            return size.toFixed(1) + ' ' + units[i];
        }

        function loadQuota() {
            const url = "{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'user_quota']) }}";
            $("#quota-spinner").removeClass("d-none");
            $("#quota-error").addClass("d-none").text("");
            $("#quota-used").css("width", "0%").text("");
            $("#quota-free").css("width", "100%").text("");
            $("#quota-legend").text("{{ __('Product.puqNextcloud.Loading') }}...");

            $.ajax({
                url: url,
                method: "GET",
                success: function (response) {
                    $("#quota-spinner").addClass("d-none");

                    if (response.status !== 'success') {
                        const errorText = response.errors?.join(', ') ?? "{{ __('Product.puqNextcloud.Unknown error') }}";
                        $("#quota-error").removeClass("d-none").text(errorText);
                        $("#quota-legend").text("{{ __('Product.puqNextcloud.Error') }}");
                        return;
                    }

                    const data = response.data;
                    const percentUsed = parseFloat(data.relative).toFixed(1);
                    const percentFree = (100 - percentUsed).toFixed(1);
                    const used = formatSize(data.used);
                    const free = formatSize(data.free);
                    const total = formatSize(data.total);

                    $("#quota-used").css("width", percentUsed + "%").text(`${percentUsed}%`);
                    $("#quota-free").css("width", percentFree + "%").text(`${percentFree}%`);
                    $("#quota-legend").text(
                        "{{ __('Product.puqNextcloud.Used') }}: " + used + " | " +
                        "{{ __('Product.puqNextcloud.Free') }}: " + free + " | " +
                        "{{ __('Product.puqNextcloud.Total') }}: " + total
                    );
                },
                error: function () {
                    $("#quota-spinner").addClass("d-none");
                    $("#quota-error").removeClass("d-none").text("{{ __('Product.puqNextcloud.Failed to load quota. Please try again later.') }}");
                    $("#quota-legend").text("{{ __('Product.puqNextcloud.Error') }}");
                }
            });
        }

        $(document).ready(function () {
            loadQuota();
        });

        const passwordField = document.getElementById('password');
        const showPasswordButton = document.getElementById('showPasswordButton');

        function startShowPassword() {
            passwordField.type = 'text';
        }

        function endShowPassword() {
            passwordField.type = 'password';
        }

        showPasswordButton.addEventListener('mousedown', startShowPassword);
        showPasswordButton.addEventListener('mouseup', endShowPassword);
        showPasswordButton.addEventListener('mouseleave', endShowPassword);
        showPasswordButton.addEventListener('touchstart', startShowPassword);
        showPasswordButton.addEventListener('touchend', endShowPassword);
        showPasswordButton.addEventListener('touchcancel', endShowPassword);

        function copyUsername() {
            const input = document.getElementById('username');
            navigator.clipboard.writeText(input.value).then(() => {
                alert("{{ __('Product.puqNextcloud.Copied to clipboard') }}: " + input.value);
            }).catch(err => console.error('Copy error:', err));
        }

        function copyPassword() {
            const input = document.getElementById('password');
            navigator.clipboard.writeText(input.value).then(() => {
                alert("{{ __('Product.puqNextcloud.Copied to clipboard') }}: " + input.value);
            }).catch(err => console.error('Copy error:', err));
        }
    </script>
@endsection
