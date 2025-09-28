@if(request('page') == 'ssh_public_keys')
    @include(config('template.client.view') . '.service_views.list.'.$product_group->list_template.'.ssh_public_keys')
@endif

@if(request('page') == 'private_networks')
    @include(config('template.client.view') . '.service_views.list.'.$product_group->list_template.'.private_networks')
@endif

@if(request('page') == null)
    @include(config('template.client.view') . '.service_views.list.'.$product_group->list_template.'.list')
@endif
