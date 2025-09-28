<div class="row">
    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4 mb-3">
        <label class="form-label" for="username">{{__('Product.puqNextcloud.Username')}}</label>
        <div>
            <input type="text" class="form-control input-mask-trigger" id="username" name="username"
                   value="{{$service_data['username']}}"
                   inputmode="text">
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4 mb-3">
        <label class="form-label" for="password">{{__('Product.puqNextcloud.Password')}}</label>
        <div>
            <input type="text" class="form-control input-mask-trigger" id="password" name="password"
                   value="{{$service_data['password']}}"
                   inputmode="text">
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 col-xl-4 col-xxl-4 mb-3">
        <label for="server_uuid" class="form-label">{{ __('Product.puqNextcloud.Server') }}</label>
        <select name="server_uuid" id="server_uuid" class="form-select mb-2 form-control">
            @foreach($servers as $uuid => $name)
                <option value="{{ $uuid }}" {{ $service_data['server_uuid'] == $uuid ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>


    {{-- Quota --}}
    <div id="quota-container" class="my-4">
        <label class="form-label">{{ __('Product.puqNextcloud.Storage usage') }}</label>

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
        const url = "{{ route('admin.api.Product.puqNextcloud.service.user_quota.get', $service_uuid) }}";

        $("#quota-spinner").removeClass("d-none");
        $("#quota-error").addClass("d-none").text("");
        $("#quota-used").css("width", "0%").text("");
        $("#quota-free").css("width", "100%").text("");
        $("#quota-legend").text("{{ __('Product.puqNextcloud.Loading') }}...");

        PUQajax(url, {}, 50, null, 'GET')
            .then(function (response) {
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
            })
            .catch(function () {
                $("#quota-spinner").addClass("d-none");
                $("#quota-error").removeClass("d-none").text("{{ __('Product.puqNextcloud.Failed to load quota. Please try again later.') }}");
                $("#quota-legend").text("{{ __('Product.puqNextcloud.Error') }}");
            });
    }

    $(document).ready(function () {
        loadQuota();
    });
</script>
