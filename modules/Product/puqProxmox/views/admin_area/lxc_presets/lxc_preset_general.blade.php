@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('buttons')

    <button type="button"
            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success"
            id="save">
        <i class="fa fa-save"></i>
        {{ __('Product.puqProxmox.Save') }}
    </button>

@endsection

@section('content')
    @include('modules.Product.puqProxmox.views.admin_area.lxc_presets.lxc_preset_header')

    <div id="container">
        <form id="lxcPresetForm" method="POST" action="" novalidate>

            <div class="row g-2 align-items-stretch">

                <div class="col-md-2 d-flex">
                    <div class="card mb-2 border w-100">
                        <div class="card-header bg-light py-1 px-2 small fw-bold">
                            <i class="fas fa-info-circle me-1"></i> {{ __('Product.puqProxmox.Basic Settings') }}
                        </div>
                        <div class="card-body py-2 px-2">
                            <div class="form-group mb-2">
                                <label for="name" class="form-label small">
                                    <i class="fas fa-tag me-1 text-muted"></i> {{ __('Product.puqProxmox.Name') }}
                                </label>
                                <input type="text" class="form-control form-control-sm" id="name" name="name"
                                       placeholder="e.g. WebServer">
                            </div>

                            <div class="form-group mb-2">
                                <label for="description" class="form-label small">
                                    <i class="fas fa-align-left me-1 text-muted"></i> {{ __('Product.puqProxmox.Description') }}
                                </label>
                                <textarea class="form-control form-control-sm" id="description" name="description"
                                          rows="5"
                                          placeholder="{{ __('Product.puqProxmox.Optional description...') }}"></textarea>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-md-2 d-flex">
                    <div class="card mb-2 border w-100 shadow-sm">
                        <div class="card-header bg-light py-1 px-2 small fw-bold">
                            <i class="fas fa-info-circle me-1 text-primary"></i> {{ __('Product.puqProxmox.Host Configuration') }}
                        </div>

                        <div class="card-body py-2 px-2">
                            <div class="form-group mb-3">
                                <label for="hostname" class="form-label small">
                                    <i class="fas fa-tag me-1 text-muted"></i> {{ __('Product.puqProxmox.Hostname') }}
                                </label>
                                <input type="text" class="form-control form-control-sm" id="hostname" name="hostname"
                                       placeholder="web-{TIMESTAMP}-{RSTR:4}">
                                <div class="form-text text-muted small mt-1">
                                    {{ __('Product.puqProxmox.You can use dynamic tags') }}:<br>
                                    <code>{YEAR}</code>, <code>{MONTH}</code>, <code>{DAY}</code>, <code>{HOUR}</code>,
                                    <code>{MINUTE}</code>, <code>{SECOND}</code><br>
                                    <code>{TIMESTAMP}</code>, <code>{RAND:X}</code>, <code>{RSTR:X}</code>, <code>{COUNTRY}</code>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="puq_pm_dns_zone_uuid" class="form-label small">
                                    <i class="fas fa-globe me-1 text-muted"></i> {{ __('Product.puqProxmox.DNS Zone') }}
                                </label>
                                <select name="puq_pm_dns_zone_uuid" id="puq_pm_dns_zone_uuid"
                                        class="form-select form-select-sm"></select>
                            </div>

                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="onboot" name="onboot" checked>
                                <label class="form-check-label small" for="onboot">
                                    <i class="fas fa-power-off me-1 text-muted"></i> {{ __('Product.puqProxmox.Start on node boot') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2 d-flex">
                    <div class="card mb-2 border w-100">
                        <div class="card-header bg-light py-1 px-2 small fw-bold">
                            <i class="fas fa-microchip me-1"></i> {{ __('Product.puqProxmox.CPU & Memory') }}
                        </div>
                        <div class="card-body py-2 px-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label for="arch" class="form-label small">
                                        <i class="fas fa-cubes me-1 text-muted"></i> {{ __('Product.puqProxmox.Arch') }}
                                    </label>
                                    <select class="form-select form-select-sm" id="arch" name="arch">
                                        <option value="amd64">amd64</option>
                                        <option value="i386">i386</option>
                                        <option value="arm64">arm64</option>
                                        <option value="armhf">armhf</option>
                                        <option value="riscv32">riscv32</option>
                                        <option value="riscv64">riscv64</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="cores" class="form-label small">
                                        <i class="fas fa-microchip me-1 text-muted"></i> {{ __('Product.puqProxmox.Cores') }}
                                    </label>
                                    <input type="number" class="form-control form-control-sm" id="cores" name="cores"
                                           min="1" placeholder="1">
                                </div>

                                <div class="col-md-6">
                                    <label for="cpulimit" class="form-label small">
                                        <i class="fas fa-tachometer-alt me-1 text-muted"></i> {{ __('Product.puqProxmox.Limit') }}
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="cpulimit" name="cpulimit" min="1"
                                               placeholder="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="cpuunits" class="form-label small">
                                        <i class="fas fa-balance-scale me-1 text-muted"></i> {{ __('Product.puqProxmox.Weight') }}
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="cpuunits" name="cpuunits" min="1"
                                               placeholder="1024">
                                        <span class="input-group-text">pts</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="memory" class="form-label small">
                                        <i class="fas fa-memory me-1 text-muted"></i> {{ __('Product.puqProxmox.RAM') }}
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="memory" name="memory" min="0"
                                               placeholder="2048">
                                        <span class="input-group-text">MB</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="swap" class="form-label small">
                                        <i class="fas fa-exchange-alt me-1 text-muted"></i> {{ __('Product.puqProxmox.Swap') }}
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="swap" name="swap" min="0"
                                               placeholder="1024">
                                        <span class="input-group-text">MB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2 d-flex">
                    <div class="card mb-2 border w-100">
                        <div class="card-header bg-light py-1 px-2 small fw-bold">
                            <i class="fas fa-save me-1"></i> {{ __('Product.puqProxmox.Backup Settings') }}
                        </div>
                        <div class="card-body py-2 px-2">
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label for="vzdump_mode"
                                           class="form-label small">{{ __('Product.puqProxmox.Mode') }}</label>
                                    <select class="form-select form-select-sm" id="vzdump_mode" name="vzdump_mode">
                                        <option value="snapshot">snapshot</option>
                                        <option value="suspend" selected>suspend</option>
                                        <option value="stop">stop</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="vzdump_compress"
                                           class="form-label small">{{ __('Product.puqProxmox.Compression') }}</label>
                                    <select class="form-select form-select-sm" id="vzdump_compress"
                                            name="vzdump_compress">
                                        <option value="0">None</option>
                                        <option value="gzip">gzip</option>
                                        <option value="lzo">lzo</option>
                                        <option value="zstd" selected>zstd</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="vzdump_bwlimit"
                                           class="form-label small">{{ __('Product.puqProxmox.Bandwidth Limit') }}</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control form-control-sm" id="vzdump_bwlimit"
                                               name="vzdump_bwlimit" placeholder="0 = no limit">
                                        <span class="input-group-text">KB/s</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="backup_count"
                                           class="form-label small">{{ __('Product.puqProxmox.Backup Count') }}</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control form-control-sm" id="backup_count"
                                               name="backup_count">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2 d-flex">
                    <div class="card mb-2 border w-100">
                        <div class="card-header bg-light py-1 px-2 small fw-bold">
                            <i class="fas fa-shield-alt me-1"></i> {{ __('Product.puqProxmox.Firewall') }}
                        </div>
                        <div class="card-body py-2 px-2">
                            <div class="row g-2">
                                <div class="col-md-6 form-check">
                                    <input class="form-check-input" type="checkbox" name="firewall_enable"
                                           id="firewall_enable" value="1">
                                    <label class="form-check-label small" for="firewall_enable">
                                        <i class="fas fa-toggle-on me-1 text-muted"></i> {{ __('Product.puqProxmox.Enable') }}
                                    </label>
                                </div>

                                <div class="col-md-6 form-check">
                                    <input class="form-check-input" type="checkbox" name="firewall_dhcp"
                                           id="firewall_dhcp">
                                    <label class="form-check-label small" for="firewall_dhcp">
                                        <i class="fas fa-network-wired me-1 text-muted"></i> {{ __('Product.puqProxmox.DHCP') }}
                                    </label>
                                </div>

                                <div class="col-md-6 form-check">
                                    <input class="form-check-input" type="checkbox" name="firewall_ipfilter"
                                           id="firewall_ipfilter">
                                    <label class="form-check-label small" for="firewall_ipfilter">
                                        <i class="fas fa-filter me-1 text-muted"></i> {{ __('Product.puqProxmox.IP Filter') }}
                                    </label>
                                </div>

                                <div class="col-md-6 form-check">
                                    <input class="form-check-input" type="checkbox" name="firewall_macfilter"
                                           id="firewall_macfilter">
                                    <label class="form-check-label small" for="firewall_macfilter">
                                        <i class="fas fa-microchip me-1 text-muted"></i> {{ __('Product.puqProxmox.MAC Filter') }}
                                    </label>
                                </div>

                                <div class="col-md-6 form-check">
                                    <input class="form-check-input" type="checkbox" name="firewall_ndp"
                                           id="firewall_ndp">
                                    <label class="form-check-label small" for="firewall_ndp">
                                        <i class="fas fa-project-diagram me-1 text-muted"></i> {{ __('Product.puqProxmox.NDP') }}
                                    </label>
                                </div>

                                <div class="col-md-6 form-check">
                                    <input class="form-check-input" type="checkbox" name="firewall_radv"
                                           id="firewall_radv">
                                    <label class="form-check-label small" for="firewall_radv">
                                        <i class="fas fa-random me-1 text-muted"></i> {{ __('Product.puqProxmox.RADV') }}
                                    </label>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">
                                        <i class="fas fa-level-up-alt me-1 text-muted"></i> {{ __('Product.puqProxmox.Log Level In') }}
                                    </label>
                                    <select class="form-select form-select-sm" name="firewall_log_level_in" id="firewall_log_level_in">
                                        <option value="nolog">nolog</option>
                                        <option value="emerg">emerg</option>
                                        <option value="alert">alert</option>
                                        <option value="crit">crit</option>
                                        <option value="err">err</option>
                                        <option value="warning">warning</option>
                                        <option value="notice">notice</option>
                                        <option value="info">info</option>
                                        <option value="debug">debug</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">
                                        <i class="fas fa-level-down-alt me-1 text-muted"></i> {{ __('Product.puqProxmox.Log Level Out') }}
                                    </label>
                                    <select class="form-select form-select-sm" name="firewall_log_level_out" id="firewall_log_level_out">
                                        <option value="nolog">nolog</option>
                                        <option value="emerg">emerg</option>
                                        <option value="alert">alert</option>
                                        <option value="crit">crit</option>
                                        <option value="err">err</option>
                                        <option value="warning">warning</option>
                                        <option value="notice">notice</option>
                                        <option value="info">info</option>
                                        <option value="debug">debug</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">
                                        <i class="fas fa-arrow-up me-1 text-muted"></i> {{ __('Product.puqProxmox.Policy In') }}
                                    </label>
                                    <select class="form-select form-select-sm" name="firewall_policy_in" id="firewall_policy_in">
                                        <option value="ACCEPT">ACCEPT</option>
                                        <option value="REJECT">REJECT</option>
                                        <option value="DROP">DROP</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">
                                        <i class="fas fa-arrow-down me-1 text-muted"></i> {{ __('Product.puqProxmox.Policy Out') }}
                                    </label>
                                    <select class="form-select form-select-sm" name="firewall_policy_out" id="firewall_policy_out">
                                        <option value="ACCEPT">ACCEPT</option>
                                        <option value="REJECT">REJECT</option>
                                        <option value="DROP">DROP</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2 d-flex">
                    <div class="card mb-2 border w-100">
                        <div class="card-header bg-light py-1 px-2 small fw-bold">
                            <i class="fas fa-cubes me-1"></i> {{ __('Product.puqProxmox.LXC Features') }}
                        </div>

                        <div class="card-body py-2 px-2">
                            <div class="row g-2">

                                <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" name="ha_managed" id="ha_managed" value="1">
                                    <label class="form-check-label small" for="ha_managed">
                                        <i class="fas fa-hands-helping me-1 text-muted"></i>
                                        {{ __('Product.puqProxmox.HA Managed') }}
                                    </label>
                                </div>

                                <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" name="unprivileged" id="unprivileged" value="1">
                                    <label class="form-check-label small" for="unprivileged">
                                        <i class="fas fa-user-shield me-1 text-muted"></i>
                                        {{ __('Product.puqProxmox.Unprivileged') }}
                                    </label>
                                </div>

                                <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" name="nesting" id="nesting" value="1">
                                    <label class="form-check-label small" for="nesting">
                                        <i class="fas fa-layer-group me-1 text-muted"></i>
                                        {{ __('Product.puqProxmox.Nesting') }}
                                    </label>
                                </div>

                                <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" name="fuse" id="fuse" value="1">
                                    <label class="form-check-label small" for="fuse">
                                        <i class="fas fa-plug me-1 text-muted"></i>
                                        {{ __('Product.puqProxmox.Fuse') }}
                                    </label>
                                </div>

                                <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" name="keyctl" id="keyctl" value="1">
                                    <label class="form-check-label small" for="keyctl">
                                        <i class="fas fa-key me-1 text-muted"></i>
                                        {{ __('Product.puqProxmox.Keyctl') }}
                                    </label>
                                </div>

                                <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" name="mknod" id="mknod" value="1">
                                    <label class="form-check-label small" for="mknod">
                                        <i class="fas fa-cogs me-1 text-muted"></i>
                                        {{ __('Product.puqProxmox.Mknod') }}
                                    </label>
                                </div>

                                <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" name="mount_nfs" id="mount_nfs" value="1">
                                    <label class="form-check-label small" for="mount_nfs">
                                        <i class="fas fa-network-wired me-1 text-muted"></i>
                                        {{ __('Product.puqProxmox.Mount NFS') }}
                                    </label>
                                </div>

                                <div class="col-12 form-check">
                                    <input class="form-check-input" type="checkbox" name="mount_cifs" id="mount_cifs" value="1">
                                    <label class="form-check-label small" for="mount_cifs">
                                        <i class="fas fa-server me-1 text-muted"></i>
                                        {{ __('Product.puqProxmox.Mount CIFS') }}
                                    </label>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>



            </div>

            <div class="row g-2 align-items-stretch">
                <div class="col-md-6 d-flex">
                    <div class="card mb-2 border w-100">
                        <div class="card-header bg-light py-1 px-2 small fw-bold">
                            <i class="fas fa-hdd me-1"></i> {{ __('Product.puqProxmox.Rootfs') }}
                        </div>
                        <div class="card-body py-2 px-2">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label for="rootfs_size" class="form-label small">
                                        <i class="fas fa-database me-1 text-muted"></i> {{ __('Product.puqProxmox.Disk Size') }}
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="rootfs_size" name="rootfs_size"
                                               min="0" placeholder="10240">
                                        <span class="input-group-text">MB</span>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <label for="rootfs_mountoptions" class="form-label small">
                                        <i class="fas fa-cogs me-1 text-muted"></i> {{ __('Product.puqProxmox.Mount Options') }}
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="rootfs_mountoptions"
                                           name="rootfs_mountoptions"
                                           placeholder="nosuid, noexec, nodev, noatime, lazytime, discard">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 d-flex">
                    <div class="card mb-2 border w-100">
                        <div class="card-header bg-light py-1 px-2 small fw-bold">
                            <i class="fas fa-folder me-1"></i> {{ __('Product.puqProxmox.Mount Point') }}
                        </div>
                        <div class="card-body py-2 px-2">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label for="mp" class="form-label small">
                                        <i class="fas fa-folder-open me-1 text-muted"></i> {{ __('Product.puqProxmox.Mount Path') }}
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="mp" name="mp"
                                           placeholder="/mnt/volume">
                                </div>

                                <div class="col-md-3">
                                    <label for="mp_size" class="form-label small">
                                        <i class="fas fa-database me-1 text-muted"></i> {{ __('Product.puqProxmox.Disk Size') }}
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="mp_size" name="mp_size" min="0"
                                               placeholder="5120">
                                        <span class="input-group-text">MB</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="mp_mountoptions" class="form-label small">
                                        <i class="fas fa-cogs me-1 text-muted"></i> {{ __('Product.puqProxmox.Mount Options') }}
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="mp_mountoptions"
                                           name="mp_mountoptions"
                                           placeholder="nosuid, noexec, nodev, noatime, lazytime, discard">
                                </div>

                                <div class="form-check small mt-2 ms-1">
                                    <input class="form-check-input" type="checkbox" id="mp_backup" name="mp_backup"
                                           checked>
                                    <label class="form-check-label" for="mp_backup">
                                        <i class="fas fa-archive me-1 text-muted"></i> {{ __('Product.puqProxmox.Include in Backup') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-2 align-items-stretch">
                @foreach ([
    'pn' => __('Product.puqProxmox.Public Network'),
    'lpn' => __('Product.puqProxmox.Local Private Network'),
    'gpn' => __('Product.puqProxmox.Global Private Network')
    ] as $prefix => $label)
                    <div class="col-md-4">
                        <div class="card mb-3 border">
                            <div class="card-header bg-light py-1 px-2 small fw-bold">
                                <i class="fas fa-network-wired me-1"></i> {{ $label }}
                            </div>
                            <div class="card-body py-2 px-2 row g-2">
                                <div class="col-md-4">
                                    <label for="{{ $prefix }}_name"
                                           class="form-label small">{{ __('Product.puqProxmox.Interface Name') }}</label>
                                    <input type="text" class="form-control form-control-sm" id="{{ $prefix }}_name"
                                           name="{{ $prefix }}_name" placeholder="ethX">
                                </div>

                                <div class="col-md-4">
                                    <label for="{{ $prefix }}_rate"
                                           class="form-label small">{{ __('Product.puqProxmox.Rate Limit') }}</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control form-control-sm"
                                               id="{{ $prefix }}_rate" name="{{ $prefix }}_rate"
                                               placeholder="0 = unlimited">
                                        <span class="input-group-text">Mbps</span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="{{ $prefix }}_mtu"
                                           class="form-label small">{{ __('Product.puqProxmox.MTU') }}</label>
                                    <input type="number" class="form-control form-control-sm" id="{{ $prefix }}_mtu"
                                           name="{{ $prefix }}_mtu" placeholder="0 = auto">
                                </div>

                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="{{ $prefix }}_firewall"
                                           name="{{ $prefix }}_firewall" checked>
                                    <label class="form-check-label small"
                                           for="{{ $prefix }}_firewall">{{ __('Product.puqProxmox.Enable Firewall') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>

@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData() {
                blockUI('container');

                PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_preset.get', $uuid) }}', {}, 50, null, 'GET')
                    .then(function (response) {
                        // Basic Settings
                        $("#name").val(response.data?.name);
                        $("#description").val(response.data?.description);

                        $("#hostname").val(response.data?.hostname);
                        initializeSelect2(
                            $("#puq_pm_dns_zone_uuid"),
                            '{{ route('admin.api.Product.puqProxmox.dns_zones.forward.select.get') }}',
                            response.data?.puq_pm_dns_zone_data,
                            'GET',
                            1000,
                            {}
                        );
                        $("#onboot").prop("checked", response.data?.onboot);
                        // CPU & Memory
                        $("#arch").val(response.data?.arch);
                        $("#cores").val(response.data?.cores);
                        $("#cpulimit").val(response.data?.cpulimit);
                        $("#cpuunits").val(response.data?.cpuunits);
                        $("#memory").val(response.data?.memory);
                        $("#swap").val(response.data?.swap);

                        // Backup Settings
                        $("#vzdump_mode").val(response.data?.vzdump_mode);
                        $("#vzdump_compress").val(response.data?.vzdump_compress);
                        $("#vzdump_bwlimit").val(response.data?.vzdump_bwlimit);
                        $("#backup_count").val(response.data?.backup_count);


                        // Rootfs
                        $("#rootfs_size").val(response.data?.rootfs_size);
                        $("#rootfs_mountoptions").val(response.data?.rootfs_mountoptions);

                        // Mount Point
                        $("#mp").val(response.data?.mp);
                        $("#mp_size").val(response.data?.mp_size);
                        $("#mp_mountoptions").val(response.data?.mp_mountoptions);
                        $("#mp_backup").prop("checked", response.data?.mp_backup);

                        // Network Settings
                        $("#pn_name").val(response.data?.pn_name);
                        $("#pn_rate").val(response.data?.pn_rate);
                        $("#pn_mtu").val(response.data?.pn_mtu);
                        $("#pn_firewall").prop("checked", response.data?.pn_firewall);

                        $("#lpn_name").val(response.data?.lpn_name);
                        $("#lpn_rate").val(response.data?.lpn_rate);
                        $("#lpn_mtu").val(response.data?.lpn_mtu);
                        $("#lpn_firewall").prop("checked", response.data?.lpn_firewall);

                        $("#gpn_name").val(response.data?.gpn_name);
                        $("#gpn_rate").val(response.data?.gpn_rate);
                        $("#gpn_mtu").val(response.data?.gpn_mtu);
                        $("#gpn_firewall").prop("checked", response.data?.gpn_firewall);

                        // Firewall Settings
                        $("#firewall_enable").prop("checked", response.data?.firewall_enable);
                        $("#firewall_dhcp").prop("checked", response.data?.firewall_dhcp);
                        $("#firewall_ipfilter").prop("checked", response.data?.firewall_ipfilter);
                        $("#firewall_macfilter").prop("checked", response.data?.firewall_macfilter);
                        $("#firewall_ndp").prop("checked", response.data?.firewall_ndp);
                        $("#firewall_radv").prop("checked", response.data?.firewall_radv);

                        $("#firewall_log_level_in").val(response.data?.firewall_log_level_in);
                        $("#firewall_log_level_out").val(response.data?.firewall_log_level_out);
                        $("#firewall_policy_in").val(response.data?.firewall_policy_in);
                        $("#firewall_policy_out").val(response.data?.firewall_policy_out);

                        $("#ha_managed").prop("checked", response.data?.ha_managed);
                        $("#unprivileged").prop("checked", response.data?.unprivileged);
                        $("#nesting").prop("checked", response.data?.nesting);
                        $("#fuse").prop("checked", response.data?.fuse);
                        $("#keyctl").prop("checked", response.data?.keyctl);
                        $("#mknod").prop("checked", response.data?.mknod);
                        $("#mount_nfs").prop("checked", response.data?.mount_nfs);
                        $("#mount_cifs").prop("checked", response.data?.mount_cifs);

                        unblockUI('container');
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                event.preventDefault();
                const $form = $("#lxcPresetForm");
                const formData = serializeForm($form);

                PUQajax('{{ route('admin.api.Product.puqProxmox.lxc_preset.put', $uuid) }}', formData, 1000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
