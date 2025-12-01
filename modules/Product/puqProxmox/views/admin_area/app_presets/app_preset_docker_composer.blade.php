@php
    $variables = [
//        ['name' => 'USER_NAME', 'desc' => 'System username'],
//        ['name' => 'USER_NEW_PASSWORD', 'desc' => 'System user new password'],
//        ['name' => 'ROOT_NEW_PASSWORD', 'desc' => 'System root user new password'],
    ];

    $type = 'docker_composer';
    $description = 'docker_composer';
@endphp
@section('buttons')
    @parent
@endsection

@section('js')
    @parent
@endsection

@include('modules.Product.puqProxmox.views.admin_area.app_presets.app_preset_script')
