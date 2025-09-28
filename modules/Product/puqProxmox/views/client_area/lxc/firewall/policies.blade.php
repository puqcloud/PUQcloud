<div class="main-card card mb-2">
    <div class="card-body" id="policy_card">
        <form id="policy_form">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="p-4 d-flex flex-column align-items-center border rounded" id="policy_in_block">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-arrow-down fs-2 me-2 text-primary"></i>
                            <span class="fw-bold fs-4">{{ __('Product.puqProxmox.Incoming Policy') }}</span>
                        </div>
                        <div class="fw-bold fs-5 mb-2" id="policy_in_info">
                            <i class="fas fa-circle me-2 text-muted"></i><span id="policy_in_text">{{ __('Product.puqProxmox.Loading...') }}</span>
                        </div>
                        <select class="form-select text-center" id="policy_in" name="policy_in">
                            <option value="ACCEPT">{{ __('Product.puqProxmox.ACCEPT - Allow all incoming traffic') }}</option>
                            <option value="REJECT">{{ __('Product.puqProxmox.REJECT - Deny traffic with notification') }}</option>
                            <option value="DROP">{{ __('Product.puqProxmox.DROP - Silently drop incoming traffic') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="p-4 d-flex flex-column align-items-center border rounded" id="policy_out_block">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-arrow-up fs-2 me-2 text-success"></i>
                            <span class="fw-bold fs-4">{{ __('Product.puqProxmox.Outgoing Policy') }}</span>
                        </div>
                        <div class="fw-bold fs-5 mb-2" id="policy_out_info">
                            <i class="fas fa-circle me-2 text-muted"></i><span id="policy_out_text">{{ __('Product.puqProxmox.Loading...') }}</span>
                        </div>
                        <select class="form-select text-center" id="policy_out" name="policy_out">
                            <option value="ACCEPT">{{ __('Product.puqProxmox.ACCEPT - Allow all outgoing traffic') }}</option>
                            <option value="REJECT">{{ __('Product.puqProxmox.REJECT - Deny traffic with notification') }}</option>
                            <option value="DROP">{{ __('Product.puqProxmox.DROP - Silently drop outgoing traffic') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <button type="button" class="btn btn-success btn-lg px-4" id="policy_save">
                    <i class="fas fa-save me-2"></i>{{ __('Product.puqProxmox.Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            function getPolicyColor(policy) {
                switch (policy) {
                    case 'ACCEPT': return 'text-success';
                    case 'REJECT': return 'text-warning';
                    case 'DROP':   return 'text-danger';
                    default:       return 'text-muted';
                }
            }

            function updatePolicyUI(type, value) {
                let color = getPolicyColor(value);
                $('#' + type + '_info i')
                    .removeClass('text-success text-warning text-danger text-muted')
                    .addClass(color);
                $('#' + type + '_text').text(value);
            }

            function loadFirewallPolicies() {
                blockUI('policy_card');
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcFirewallPolicies']) }}", {}, 50, null, 'GET')
                    .then(function(response) {
                        let data = response.data;

                        if (data.policy_in) {
                            $('#policy_in').val(data.policy_in);
                            updatePolicyUI('policy_in', data.policy_in);
                        }
                        if (data.policy_out) {
                            $('#policy_out').val(data.policy_out);
                            updatePolicyUI('policy_out', data.policy_out);
                        }

                        unblockUI('policy_card');
                    });
            }

            loadFirewallPolicies();

            $('#policy_in, #policy_out').on('change', function() {
                let id = $(this).attr('id');
                updatePolicyUI(id, $(this).val());
            });

            $('#policy_save').on('click', function (event) {
                event.preventDefault();
                var formData = {
                    policy_in: $('#policy_in').val(),
                    policy_out: $('#policy_out').val()
                };
                PUQajax('{{ route('client.api.cloud.service.module.post', ['uuid' => $service_uuid, 'method' => 'postLxcFirewallPolicies']) }}', formData, 500, $(this), 'POST')
                    .then(function (response) {
                        loadFirewallPolicies();
                    });
            });
        });
    </script>
@endsection
