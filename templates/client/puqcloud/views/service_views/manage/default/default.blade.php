@if($tab == 'general')
    @include(config('template.client.view') . '.service_views.manage.'.$product_group->manage_template.'.general.general')
@endif
@include(config('template.client.view') . '.service_views.manage.'.$product_group->manage_template.'.header')
