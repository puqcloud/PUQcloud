@if(request()->has('edit'))
    @include(config('template.admin.view') .'.product_option_groups.product_option_edit')
@else
    @include(config('template.admin.view') .'.product_option_groups.product_options')
@endif
