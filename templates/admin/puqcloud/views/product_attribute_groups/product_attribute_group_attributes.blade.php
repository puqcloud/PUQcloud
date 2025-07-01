@if(request()->has('edit'))
    @include(config('template.admin.view') .'.product_attribute_groups.product_attribute_edit')
@else
    @include(config('template.admin.view') .'.product_attribute_groups.product_attributes')
@endif
