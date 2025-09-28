@php
    $variables = [
        ['name' => 'USER_NAME', 'desc' => 'System username'],
        ['name' => 'USER_PASSWORD', 'desc' => 'System user password'],
    ];

    $type = 'post_install';
    $description = 'post_install_script';
@endphp

@include('modules.Product.puqProxmox.views.admin_area.lxc_os_templates.lxc_os_template_script')
