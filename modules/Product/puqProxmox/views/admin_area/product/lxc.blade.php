<div class="type-wrapper mb-3">
    <select name="type" class="form-select js-type-select" data-original="{{ $type }}">
        <option value="lxc" {{ $type === 'lxc' ? 'selected' : '' }}>LXC</option>
        <option value="vps" {{ $type === 'vps' ? 'selected' : '' }}>VPS</option>
        <option value="app" {{ $type === 'app' ? 'selected' : '' }}>App</option>
    </select>
</div>

<div class="row mb-4">

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="puq_pm_lxc_preset_uuid" class="form-label">
            {{ __('Product.puqProxmox.LXC Preset') }}
        </label>
        <select name="puq_pm_lxc_preset_uuid" id="puq_pm_lxc_preset_uuid" class="form-select"></select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="location_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Location Option Group') }}
        </label>
        <select name="location_product_option_group_uuid" id="location_product_option_group_uuid" class="form-select">
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['location_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="os_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.OS Option Group') }}
        </label>
        <select name="os_product_option_group_uuid" id="os_product_option_group_uuid" class="form-select">
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['os_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="ipv4_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.IPv4 Option Group') }}
        </label>
        <select name="ipv4_product_option_group_uuid" id="ipv4_product_option_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['ipv4_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['ipv4_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="ipv6_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.IPv6 Option Group') }}
        </label>
        <select name="ipv6_product_option_group_uuid" id="ipv6_product_option_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['ipv6_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['ipv6_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="local_private_network_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Local Private Network Option Group') }}
        </label>
        <select name="local_private_network_product_option_group_uuid"
                id="local_private_network_product_option_group_uuid" class="form-select">
            <option value=""
                    @if(empty($product_data['local_private_network_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['local_private_network_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="global_private_network_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Global Private Network Option Group') }}
        </label>
        <select name="global_private_network_product_option_group_uuid"
                id="global_private_network_product_option_group_uuid" class="form-select">
            <option value=""
                    @if(empty($product_data['global_private_network_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['global_private_network_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="cpu_cores_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.CPU Cores Option Group') }}
        </label>
        <select name="cpu_cores_product_option_group_uuid"
                id="cpu_cores_product_option_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['cpu_cores_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['cpu_cores_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="memory_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Memory Option Group') }}
        </label>
        <select name="memory_product_option_group_uuid"
                id="memory_product_option_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['memory_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['memory_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="rootfs_size_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Main Disk Size Option Group') }}
        </label>
        <select name="rootfs_size_product_option_group_uuid"
                id="rootfs_size_product_option_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['rootfs_size_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['rootfs_size_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="mp_size_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Additional Disk Size Option Group') }}
        </label>
        <select name="mp_size_product_option_group_uuid"
                id="mp_size_product_option_group_uuid" class="form-select">

            <option value="" @if(empty($product_data['mp_size_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['mp_size_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="backup_count_product_option_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Backup Count Option Group') }}
        </label>
        <select name="backup_count_product_option_group_uuid"
                id="backup_count_product_option_group_uuid" class="form-select">

            <option value="" @if(empty($product_data['backup_count_product_option_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product->productOptionGroups as $group)
                <option value="{{ $group['uuid'] }}"
                        @if (($product_data['backup_count_product_option_group_uuid'] ?? '') === $group['uuid']) selected @endif>
                    {{ $group->name ?: $group->key }}
                </option>
            @endforeach
        </select>
    </div>





















</div>
<div class="row">

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="cpu_product_attribute_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.CPU Attribute Group') }}
        </label>
        <select name="cpu_product_attribute_group_uuid"
                id="cpu_product_attribute_group_uuid" class="form-select">

            <option value="" @if(empty($product_data['cpu_product_attribute_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product_attribute_groups as $product_attribute_group)
                <option value="{{ $product_attribute_group['uuid'] }}"
                        @if (($product_data['cpu_product_attribute_group_uuid'] ?? '') === $product_attribute_group['uuid']) selected @endif>
                    {{ $product_attribute_group->name }} ({{ $product_attribute_group->key }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="memory_product_attribute_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Memory Attribute Group') }}
        </label>
        <select name="memory_product_attribute_group_uuid" id="memory_product_attribute_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['memory_product_attribute_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product_attribute_groups as $product_attribute_group)
                <option value="{{ $product_attribute_group['uuid'] }}"
                        @if (($product_data['memory_product_attribute_group_uuid'] ?? '') === $product_attribute_group['uuid']) selected @endif>
                    {{ $product_attribute_group->name }} ({{ $product_attribute_group->key }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="rootfs_product_attribute_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.RootFS Attribute Group') }}
        </label>
        <select name="rootfs_product_attribute_group_uuid" id="rootfs_product_attribute_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['rootfs_product_attribute_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product_attribute_groups as $product_attribute_group)
                <option value="{{ $product_attribute_group['uuid'] }}"
                        @if (($product_data['rootfs_product_attribute_group_uuid'] ?? '') === $product_attribute_group['uuid']) selected @endif>
                    {{ $product_attribute_group->name }} ({{ $product_attribute_group->key }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="mp_product_attribute_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.MP Attribute Group') }}
        </label>
        <select name="mp_product_attribute_group_uuid" id="mp_product_attribute_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['mp_product_attribute_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product_attribute_groups as $product_attribute_group)
                <option value="{{ $product_attribute_group['uuid'] }}"
                        @if (($product_data['mp_product_attribute_group_uuid'] ?? '') === $product_attribute_group['uuid']) selected @endif>
                    {{ $product_attribute_group->name }} ({{ $product_attribute_group->key }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6 col-xl-2 mb-3">
        <label for="public_network_product_attribute_group_uuid" class="form-label">
            {{ __('Product.puqProxmox.Public Network Attribute Group') }}
        </label>
        <select name="public_network_product_attribute_group_uuid" id="public_network_product_attribute_group_uuid" class="form-select">
            <option value="" @if(empty($product_data['public_network_product_attribute_group_uuid'])) selected @endif>
                {{ __('Product.puqProxmox.Do not use') }}
            </option>
            @foreach ($product_attribute_groups as $product_attribute_group)
                <option value="{{ $product_attribute_group['uuid'] }}"
                        @if (($product_data['public_network_product_attribute_group_uuid'] ?? '') === $product_attribute_group['uuid']) selected @endif>
                    {{ $product_attribute_group->name }} ({{ $product_attribute_group->key }})
                </option>
            @endforeach
        </select>
    </div>
</div>

@if(isset($puq_pm_lxc_preset))
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-cube me-2"></i> {{ __('Product.puqProxmox.Selected LXC Preset') }}
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush">
{{--                        <li class="list-group-item">--}}
{{--                            <i class="fas fa-tag me-2 text-muted"></i>--}}
{{--                            <strong>{{ __('Product.puqProxmox.Name') }}:</strong> {{ $puq_pm_lxc_preset->name }}--}}
{{--                        </li>--}}
                        <li class="list-group-item">
                            <i class="fas fa-server me-2 text-muted"></i>
                            <strong>{{ __('Product.puqProxmox.Hostname') }}:</strong> {{ $puq_pm_lxc_preset->hostname }}
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-flag me-2 text-muted"></i>
                            <strong>{{ __('Product.puqProxmox.DNS Zone') }}:</strong>
                            {{ $puq_pm_lxc_preset->puqPmDnsZone->name ?? __('Product.puqProxmox.Not assigned') }}
                        </li>

                        <li class="list-group-item">
                            <i class="fas fa-microchip me-2 text-muted"></i>
                            <strong>{{ __('Product.puqProxmox.CPU') }}:</strong>
                            {{ $puq_pm_lxc_preset->cores }} {{ __('Product.puqProxmox.Cores') }}
                            @foreach ($cpu_product_attributes as $cpu_product_attribute)
                                <span class="badge bg-success ms-2">{{ $cpu_product_attribute->name }}</span>
                            @endforeach
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-memory me-2 text-muted"></i>
                            <strong>{{ __('Product.puqProxmox.Memory') }}:</strong> {{ $puq_pm_lxc_preset->memory }} MB
                            @foreach ($memory_product_attributes as $memory_product_attribute)
                                <span class="badge bg-primary ms-2">{{ $memory_product_attribute->name }}</span>
                            @endforeach
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-hdd me-2 text-muted"></i>
                            <strong>{{ __('Product.puqProxmox.Main Disk') }}:</strong> {{ $puq_pm_lxc_preset->rootfs_size }} MB
                            @foreach ($rootfs_product_attributes as $rootfs_product_attribute)
                                <span class="badge bg-warning text-dark ms-2">{{ $rootfs_product_attribute->name }}</span>
                            @endforeach
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-hdd me-2 text-muted"></i>
                            <strong>{{ __('Product.puqProxmox.Additional Disk') }}:</strong> {{ $puq_pm_lxc_preset->mp_size }} MB
                            @foreach ($mp_product_attributes as $mp_product_attribute)
                                <span class="badge bg-info text-dark ms-2">{{ $mp_product_attribute->name }}</span>
                            @endforeach
                        </li>

                        <li class="list-group-item">
                            <i class="fas fa-hdd me-2 text-muted"></i>
                            <strong>{{ __('Product.puqProxmox.Public Network') }}:</strong> {{ $puq_pm_lxc_preset->pn_name }}
                            @foreach ($public_network_product_attributes as $public_network_product_attribute)
                                <span class="badge bg-deep-blue text-dark ms-2">{{ $public_network_product_attribute->name }}</span>
                            @endforeach
                        </li>

                    </ul>
                </div>

            </div>
        </div>

        <div class="col-md-4">
            @if (!empty($location_option_mapping))
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-map-marker-alt me-2"></i> {{ __('Product.puqProxmox.Location Mapping') }}
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach ($location_option_mapping as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="{{ $item['mapped'] ? 'text-success' : 'text-danger' }}">
                                            {{ $item['cluster_group']?->name ?? __('Product.puqProxmox.Unknown Cluster Group') }}
                                        </strong><br>
                                        <small class="{{ $item['mapped'] ? 'text-success' : 'text-danger' }}">
                                            {{ $item['value'] ?? __('Product.puqProxmox.Unknown Option') }}
                                        </small>
                                    </div>
                                    <div>
                                        @if ($item['mapped'])
                                            <i class="fas fa-check text-success fa-lg"></i>
                                        @else
                                            <i class="fas fa-times text-danger fa-lg"></i>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            @if (!empty($os_option_mapping))
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-map-marker-alt me-2"></i> {{ __('Product.puqProxmox.OS Template Mapping') }}
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach ($os_option_mapping as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="{{ $item['mapped'] ? 'text-success' : 'text-danger' }}">
                                            {{ $item['os_template']?->name ?? __('Product.puqProxmox.Unknown OS Template') }}
                                        </strong><br>
                                        <small class="{{ $item['mapped'] ? 'text-success' : 'text-danger' }}">
                                            {{ $item['value'] ?? __('Product.puqProxmox.Unknown Option') }}
                                        </small>
                                    </div>
                                    <div>
                                        @if ($item['mapped'])
                                            <i class="fas fa-check text-success fa-lg"></i>
                                        @else
                                            <i class="fas fa-times text-danger fa-lg"></i>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="alert alert-warning">
        <i class="fas fa-info-circle me-2"></i>
        {{ __('Product.puqProxmox.No LXC Preset selected') }}
    </div>
@endif

<script>
    initializeSelect2(
        $("#puq_pm_lxc_preset_uuid"),
        '{{ route('admin.api.Product.puqProxmox.lxc_presets.select.get') }}',
        {!! $puq_pm_lxc_preset_data !!},
        'GET',
        1000,
        {}
    );

    document.addEventListener('change', function (e) {
        const sel = e.target;
        if (!sel.matches('select.js-type-select')) return;

        if (!sel.dataset.original) sel.dataset.original = sel.value;
        const original = sel.dataset.original;

        let hidden = sel.parentNode.querySelector('input[name="type_new"]');

        if (sel.value !== original) {
            if (hidden) {
                hidden.value = sel.value;
            } else {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'type_new';
                hidden.value = sel.value;
                sel.parentNode.insertBefore(hidden, sel.nextSibling);
            }
        } else {
            if (hidden) hidden.remove();
        }
    });
</script>
