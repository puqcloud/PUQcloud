@if(request('page') == 'ssh_public_keys')
    @include($serviceViewBase . '.ssh_public_keys')
@endif

@if(request('page') == 'private_networks')
    @include($serviceViewBase . '.private_networks')
@endif

@if(request('page') == null)
    @include($serviceViewBase . '.list')
@endif
