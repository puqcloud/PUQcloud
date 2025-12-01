@if(request('page') == null)
    @include(config('template.client.view') . '.service_views.list.'.$product_group->list_template.'.list')
@endif
