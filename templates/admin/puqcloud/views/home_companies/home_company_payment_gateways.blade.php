@if(request()->has('edit'))
    @include(config('template.admin.view') .'.home_companies.payment_gateway.payment_gateway_edit')
@else
    @include(config('template.admin.view') .'.home_companies.home_company_payment_gateways_list')
@endif
