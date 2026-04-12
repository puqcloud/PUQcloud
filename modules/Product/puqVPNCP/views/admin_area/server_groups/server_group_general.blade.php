@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('buttons')
    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqVPNCP.Save') }}
    </button>

    <button type="button"
            class="mb-2 me-2 btn-icon-only btn-outline-2x btn btn-outline-danger"
            data-model-url="{{ route('admin.api.Product.puqVPNCP.server_group.delete', $uuid) }}"
            id="deleteServerGroup">
        <i class="fa fa-trash-alt"></i>
    </button>

@endsection

@section('content')
    @include('modules.Product.puqVPNCP.views.admin_area.server_groups.server_group_header')

    <form id="serverGroupForm" action="" novalidate="novalidate">

        <div id="container">
            <div class="card mb-3">
                <div class="card-body">

                    <div class="row">
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <label for="name" class="form-label">
                                <i class="fa fa-tag me-1 text-primary"></i> {{ __('Product.puqVPNCP.Name') }}
                            </label>
                            <input type="text" class="form-control" id="name" name="name"
                                   placeholder="{{ __('Product.puqVPNCP.Name') }}">
                        </div>

                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <label for="fill_type" class="form-label">
                                <i class="fa fa-balance-scale-left me-1 text-primary"></i> {{ __('Product.puqVPNCP.Fill Type') }}
                            </label>
                            <select id="fill_type" name="fill_type" class="form-select">
                                <option value="default">{{ __('Product.puqVPNCP.Fill First') }}</option>
                                <option value="least_used">{{ __('Product.puqVPNCP.Least Used') }}</option>
                                <option value="round_robin">{{ __('Product.puqVPNCP.Round Robin') }}</option>
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-12 mb-3">
                            <label for="country_uuid" class="form-label">
                                <i class="fa fa-globe-americas me-1 text-primary"></i> {{ __('main.Country') }}
                            </label>
                            <select name="country_uuid" id="country_uuid" class="form-select">
                                <option value="">{{ __('Select Country') }}</option>
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-12 mb-3">
                            <label for="region_uuid" class="form-label">
                                <i class="fa fa-map-marked-alt me-1 text-primary"></i> {{ __('main.State/Region') }}
                            </label>
                            <select name="region_uuid" id="region_uuid" class="form-select">
                                <option value="">{{ __('Select Region') }}</option>
                            </select>
                        </div>

                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                            <label for="data_center" class="form-label">
                                <i class="fa fa-database me-1 text-primary"></i> {{ __('Product.puqVPNCP.Data Center') }}
                            </label>
                            <input type="text" class="form-control" id="data_center" name="data_center"
                                   placeholder="ex: DC-1 Frankfurt">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="description" class="form-label">
                                <i class="fa fa-align-left me-1 text-primary"></i> {{ __('main.Description') }}
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="{{ __('Brief description or notes...') }}"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Servers in this group --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-server me-2"></i>{{ __('Product.puqVPNCP.Servers') }}</span>
                    <button type="button" class="btn btn-sm btn-outline-success" id="addServerToGroup">
                        <i class="fa fa-plus me-1"></i>{{ __('Product.puqVPNCP.Add Server') }}
                    </button>
                </div>
                <div class="card-body">
                    <div id="group-servers-container">
                        <div class="text-center my-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('serverGroupForm');

                PUQajax('{{ route('admin.api.Product.puqVPNCP.server_group.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        $("#name").val(response.data?.name);
                        $("#fill_type").val(response.data?.fill_type);
                        $("#data_center").val(response.data?.data_center);

                        $("#description").val(response.data?.description).textareaAutoSize().trigger('autosize');

                        var $element_country = $("#country_uuid");
                        initializeSelect2($element_country, '{{route('admin.api.countries.select.get')}}', response.data.country_data, 'GET', 1000, {});

                        var selected_country_uuid = $element_country.val();
                        var $element_region = $("#region_uuid");
                        initializeSelect2($element_region, '{{route('admin.api.regions.select.get')}}', response.data.region_data, 'GET', 1000, {},
                            {
                                selected_country_uuid: function () {
                                    return selected_country_uuid
                                }
                            });

                        function updateStateOptions() {
                            $element_region.empty().trigger('change');
                            initializeSelect2($element_region, '{{route('admin.api.regions.select.get')}}', '', 'GET', 1000, {},
                                {
                                    selected_country_uuid: function () {
                                        return selected_country_uuid
                                    }
                                });
                        }

                        $element_country.on('change', function () {
                            selected_country_uuid = $(this).val();
                            updateStateOptions();
                        });

                        unblockUI('serverGroupForm');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            function loadGroupServers() {
                PUQajax('{{ route('admin.api.Product.puqVPNCP.server_group.servers.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        renderGroupServers(response.data || []);
                    })
                    .catch(function () {
                        $('#group-servers-container').html('<span class="text-danger">{{ __('Product.puqVPNCP.Error') }}</span>');
                    });
            }

            function renderGroupServers(servers) {
                if (!servers.length) {
                    $('#group-servers-container').html('<div class="text-muted text-center py-3">{{ __('Product.puqVPNCP.No servers in this group') }}</div>');
                    return;
                }

                let html = '';
                servers.forEach(function(srv) {
                    let freeSlots = srv.free_slots || 0;
                    let maxSlots = srv.total_accounts || 0;
                    let activeClients = maxSlots - freeSlots;
                    let usagePct = maxSlots > 0 ? Math.round(activeClients / maxSlots * 100) : 0;
                    let barClass = usagePct > 90 ? 'bg-danger' : (usagePct > 70 ? 'bg-warning' : 'bg-success');

                    html += `<div class="card mb-3 border" id="srv-card-${srv.server_uuid}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <a href="${srv.server_url}" class="fw-bold">
                                    <i class="fas fa-server me-1"></i>${srv.server_name}
                                </a>
                                <span class="ms-2">
                                    <span class="badge bg-secondary">${activeClients}/${maxSlots} clients</span>
                                    <span class="badge ${freeSlots > 0 ? 'bg-success' : 'bg-danger'}">${freeSlots} {{ __('Product.puqVPNCP.free') }}</span>
                                </span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-server-btn"
                                    data-server-uuid="${srv.server_uuid}">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar ${barClass}" style="width: ${usagePct}%"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label fw-bold"><i class="fas fa-network-wired me-1"></i>{{ __('Product.puqVPNCP.Selected Networks') }}</label>
                                </div>
                            </div>
                            <div class="row">`;

                    if (srv.networks && srv.networks.length) {
                        srv.networks.forEach(function(net) {
                            let checked = net.selected ? 'checked' : '';
                            let clientInfo = net.client_count !== undefined ? `<small class="text-muted">(${net.client_count} clients${net.address ? ', ' + net.address : ''})</small>` : '';
                            html += `<div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input network-cb" type="checkbox"
                                           value="${net.name}" data-server-uuid="${srv.server_uuid}"
                                           id="net_${srv.server_uuid}_${net.name}" ${checked}>
                                    <label class="form-check-label" for="net_${srv.server_uuid}_${net.name}">
                                        ${net.name} ${clientInfo}
                                    </label>
                                </div>
                            </div>`;
                        });
                    } else {
                        html += `<div class="col-12"><span class="text-muted">{{ __('Product.puqVPNCP.No networks available') }}</span></div>`;
                    }

                    html += `</div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary save-networks-btn"
                                        data-server-uuid="${srv.server_uuid}">
                                    <i class="fa fa-save me-1"></i>{{ __('Product.puqVPNCP.Save Networks') }}
                                </button>
                            </div>`;

                    if (srv.synced_at) {
                        const syncedAt = String(srv.synced_at).replace('T', ' ').substring(0, 19);
                        html += `<div class="mt-2"><small class="text-muted"><i class="fas fa-clock me-1"></i>{{ __('Product.puqVPNCP.Last synced') }}: ${syncedAt}</small></div>`;
                    }

                    html += `</div></div>`;
                });

                $('#group-servers-container').html(html);
            }

            // Add server to group
            $('#addServerToGroup').on('click', function() {
                var $modalTitle = $('#universalModal .modal-title');
                var $modalBody = $('#universalModal .modal-body');
                $modalTitle.text('{{ __('Product.puqVPNCP.Add Server') }}');

                var formHtml = `
                    <form id="addServerForm" class="col-md-10 mx-auto">
                        <div class="mb-3">
                            <label class="form-label" for="add_server_uuid">{{ __('Product.puqVPNCP.Server') }}</label>
                            <select id="add_server_uuid" name="server_uuid" class="form-select"></select>
                        </div>
                    </form>
                `;
                $modalBody.html(formHtml);

                initializeSelect2(
                    $('#add_server_uuid'),
                    '{{ route('admin.api.Product.puqVPNCP.servers.select.get') }}',
                    '',
                    'GET',
                    1000,
                    { dropdownParent: $('#universalModal') },
                    { exclude_group_uuid: function() { return '{{ $uuid }}'; } }
                );

                $('#universalModal').modal('show');
            });

            // Save networks for a server in group
            $(document).on('click', '.save-networks-btn', function() {
                var serverUuid = $(this).data('server-uuid');
                var selectedNetworks = [];
                $(`.network-cb[data-server-uuid="${serverUuid}"]:checked`).each(function() {
                    selectedNetworks.push($(this).val());
                });

                PUQajax(
                    '{{ route('admin.api.Product.puqVPNCP.server_group.server.put', ['group_uuid' => $uuid, 'server_uuid' => '__SERVER_UUID__']) }}'.replace('__SERVER_UUID__', serverUuid),
                    { selected_networks: selectedNetworks },
                    1000,
                    $(this),
                    'PUT'
                ).then(function() {
                    loadGroupServers();
                });
            });

            // Remove server from group
            $(document).on('click', '.remove-server-btn', function() {
                var serverUuid = $(this).data('server-uuid');

                if (!confirm('{{ __('Product.puqVPNCP.Are you sure you want to remove this server from the group?') }}')) {
                    return;
                }

                PUQajax(
                    '{{ route('admin.api.Product.puqVPNCP.server_group.server.delete', ['group_uuid' => $uuid, 'server_uuid' => '__SERVER_UUID__']) }}'.replace('__SERVER_UUID__', serverUuid),
                    {},
                    1000,
                    $(this),
                    'DELETE'
                ).then(function() {
                    loadGroupServers();
                });
            });

            // Save group settings
            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#serverGroupForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqVPNCP.server_group.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            // Modal save for add server
            $('#modalSaveButton').on('click', function (event) {
                event.preventDefault();

                if ($('#addServerForm').length) {
                    var $form = $('#addServerForm');
                    var formData = serializeForm($form);

                    PUQajax('{{ route('admin.api.Product.puqVPNCP.server_group.server.post', $uuid) }}', formData, 1000, $(this), 'POST', $form)
                        .then(function (response) {
                            $('#universalModal').modal('hide');
                            loadGroupServers();
                        });
                }
            });

            $('#deleteServerGroup').on('click', function (e) {
                e.preventDefault();
                var modelUrl = $(this).data('model-url');

                if (confirm('{{ __('Product.puqVPNCP.Are you sure you want to delete this record?') }}')) {
                    PUQajax(modelUrl, null, 50, $(this), 'DELETE');
                }
            });

            loadFormData();
            loadGroupServers();
        });
    </script>
@endsection
