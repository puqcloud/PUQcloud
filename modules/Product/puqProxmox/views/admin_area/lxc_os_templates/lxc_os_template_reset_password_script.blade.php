@php
    $variables = [
        ['name' => 'USER_NAME', 'desc' => 'System username'],
        ['name' => 'USER_NEW_PASSWORD', 'desc' => 'System user new password'],
        ['name' => 'ROOT_NEW_PASSWORD', 'desc' => 'System root user new password'],
    ];

    $type = 'reset_password';
    $description = 'reset_password';
@endphp

@include('modules.Product.puqProxmox.views.admin_area.lxc_os_templates.lxc_os_template_script')
