@if(request()->has('edit'))
    @include(config('template.admin.view') .'.clients.invoice.invoice_edit')
@else
    @include(config('template.admin.view') .'.clients.client_invoices_list')
@endif
