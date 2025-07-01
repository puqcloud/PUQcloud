@if(request()->has('edit'))
    @include(config('template.admin.view') .'.clients.service.service_edit')
@else
    @include(config('template.admin.view') .'.clients.client_services_list')
@endif
