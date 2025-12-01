<div class="row g-3 align-items-end">

    <!-- Username -->
    <div class="col-md-2">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" value="{{ $username }}">
    </div>

    <!-- Password -->
    <div class="col-md-2">
        <label for="password" class="form-label">Password</label>
        <input type="text" class="form-control" id="password" name="password" value="{{ $password }}">
    </div>

    <!-- Root Password -->
    <div class="col-md-2">
        <label for="root_password" class="form-label">Root Password</label>
        <input type="text" class="form-control" id="root_password" name="root_password" value="{{ $root_password }}">
    </div>

    <!-- SSH Public Key -->
    <div class="col-md-2">
        <label for="puq_pm_ssh_public_key_uuid" class="form-label">SSH Public Key</label>
        <select class="form-select" id="puq_pm_ssh_public_key_uuid" name="puq_pm_ssh_public_key_uuid">
            @foreach($puq_pm_ssh_public_keys as $puq_pm_ssh_public_key)
                <option value="{{ $puq_pm_ssh_public_key->uuid }}"
                    {{ $puq_pm_ssh_public_key_uuid == $puq_pm_ssh_public_key->uuid ? 'selected' : '' }}>
                    {{ $puq_pm_ssh_public_key->name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Show Password Once -->
    <div class="col-md-2 d-flex">
        <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="show_password_once"
                   name="show_password_once" {{ $show_password_once ? 'checked' : '' }}>
            <label class="form-check-label" for="show_password_once">
                Show Password Once
            </label>
        </div>
    </div>

    <!-- Backup Storage -->
    <div class="col-md-2">
        <label for="backup_storage_name" class="form-label">Backup Storage</label>
        <select class="form-select" id="backup_storage_name" name="backup_storage_name">
            @foreach($backup_storages as $backup_storage)
                <option value="{{ $backup_storage->name }}"
                    {{ $backup_storage_name == $backup_storage->name ? 'selected' : '' }}>
                    {{ $backup_storage->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>


<!-- Instance Info Section -->
<div class="row g-3 mt-3">
    <!-- Status Card -->
    <div class="col-md-4">
        <div class="card border-success shadow-sm">
            <div class="card-header bg-success text-white d-flex align-items-center">
                <i class="fas fa-server me-2"></i> LXC Status
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $lxc_instance_status['name'] ?? 'n/a' }}</p>
                <p><strong>VMID:</strong> {{ $lxc_instance_status['vmid'] ?? 'n/a' }}</p>
                <p><strong>Status:</strong>
                    <span class="badge bg-{{ $lxc_instance_status['status_btn'] ?? 'secondary' }}">
                        {{ ucfirst($lxc_instance_status['status'] ?? 'n/a') }}
                    </span>
                </p>
                <p><i class="fas fa-memory me-1"></i> Memory: {{ $lxc_instance_status['memory'] ?? 'n/a' }} %</p>
                <p><i class="fas fa-hdd me-1"></i> Disk: {{ $lxc_instance_status['disk'] ?? 'n/a' }} %</p>
                <p><i class="fas fa-microchip me-1"></i> CPU: {{ $lxc_instance_status['cpu'] ?? 'n/a' }} %</p>
                <p><i class="fas fa-network-wired me-1"></i> Net In: {{ $lxc_instance_status['netin'] ?? 'n/a' }} / Out: {{ $lxc_instance_status['netout'] ?? 'n/a' }}</p>
                <p><i class="fas fa-clock me-1"></i> Uptime: {{ $lxc_instance_status['uptime'] ?? 'n/a' }}</p>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="col-md-4">
        <div class="card border-primary shadow-sm">
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i> LXC Info
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <img src="{{ $lxc_instance_info['os']['icon_url'] ?? '' }}" alt="OS" width="24" class="me-2">
                    <strong>OS:</strong> {{ $lxc_instance_info['os']['name'] ?? 'n/a' }}
                </div>
                <p><i class="fas fa-microchip me-1"></i> Cores: {{ $lxc_instance_info['cores'] ?? 'n/a' }}</p>
                <p><i class="fas fa-memory me-1"></i> RAM: {{ $lxc_instance_info['ram'] ?? 'n/a' }}</p>
                <p><i class="fas fa-hdd me-1"></i> Main Disk: {{ $lxc_instance_info['main_disk'] ?? 'n/a' }}</p>
                <p><i class="fas fa-hdd me-1"></i> Additional Disk: {{ $lxc_instance_info['addition_disk'] ?? 'n/a' }}</p>
                <p><i class="fas fa-server me-1"></i> Backups: {{ $lxc_instance_info['backups'] ?? 'n/a' }}</p>
                <p><i class="fas fa-globe me-1"></i> Domain: {{ $lxc_instance_info['domain'] ?? 'n/a' }}</p>
                <p><i class="fas fa-network-wired me-1"></i> IPv4: {{ $lxc_instance_info['ipv4'] ?? 'n/a' }} / IPv6: {{ $lxc_instance_info['ipv6'] ?? 'n/a' }}</p>
            </div>
        </div>
    </div>

    <!-- Location Card -->
    <div class="col-md-4">
        <div class="card border-info shadow-sm">
            <div class="card-header bg-info text-white d-flex align-items-center">
                <i class="fas fa-map-marker-alt me-2"></i> Data Center
            </div>
            <div class="card-body text-center">
                <img src="{{ $lxc_instance_location['icon_url'] ?? '' }}" alt="Location" class="mb-2" width="50">
                <h5>{{ $lxc_instance_location['name'] ?? 'n/a' }}</h5>
                <p><strong>Data Center ID:</strong> {{ $lxc_instance_location['data_center'] ?? 'n/a' }}</p>
                <p>{{ $lxc_instance_location['short_description'] ?? 'n/a' }}</p>
            </div>
        </div>
    </div>
</div>
