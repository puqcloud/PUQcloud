@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('buttons')
    @parent
    @if($admin->hasPermission('finance-edit'))
        <button id="save" type="button"
                class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
            <i class="fa fa-save"></i> {{__('main.Save')}}
        </button>
    @endif
@endsection

@section('content')
    @include(config('template.admin.view') .'.home_companies.home_company_header')
    <form id="home_company" class="mx-auto" novalidate>
        <input type="hidden" class="form-control" id="name" name="name">

        <div class="card mb-3">
            <div class="card-header">{{ __('main.Invoice Settings') }}</div>
            <div class="card-body">
                <div class="row mb-3 g-3">
                    <!-- Proforma Section -->
                    <div class="col-md-4">
                        <div class="border rounded bg-light p-3">
                            <h6 class="text-primary mb-3">{{ __('main.Proforma Settings') }}</h6>
                            <div class="row">

                                <div class="col-12">
                                    <label class="form-label"
                                           for="proforma_invoice_number_format">{{ __('main.Proforma Number Format') }}</label>
                                    <input type="text" class="form-control" id="proforma_invoice_number_format"
                                           name="proforma_invoice_number_format">
                                    <small
                                        class="opacity-5">{{ __('main.Available Tags: {YEAR} {MONTH} {DAY} {NUMBER}') }}</small>
                                </div>
                                <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"
                                           for="proforma_invoice_number_next">{{ __('main.Next Proforma Number') }}</label>
                                    <input type="number" class="form-control" id="proforma_invoice_number_next"
                                           name="proforma_invoice_number_next">
                                </div>
                                <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"
                                           for="proforma_invoice_number_reset">{{ __('main.Proforma Number Reset') }}</label>
                                    <select class="form-select" id="proforma_invoice_number_reset"
                                            name="proforma_invoice_number_reset">
                                        <option value="never">{{ __('main.Never') }}</option>
                                        <option value="monthly">{{ __('main.Monthly') }}</option>
                                        <option value="yearly">{{ __('main.Yearly') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Section -->
                    <div class="col-md-4">
                        <div class="border rounded bg-light p-3">
                            <h6 class="text-primary mb-3">{{ __('main.Invoice Settings') }}</h6>
                            <div class="row">
                                <div class="col-12 ">
                                    <label class="form-label"
                                           for="invoice_number_format">{{ __('main.Invoice Number Format') }}</label>
                                    <input type="text" class="form-control" id="invoice_number_format"
                                           name="invoice_number_format">
                                    <small
                                        class="opacity-5">{{ __('main.Available Tags: {YEAR} {MONTH} {DAY} {NUMBER}') }}</small>
                                </div>

                                <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"
                                           for="invoice_number_next">{{ __('main.Next Invoice Number') }}</label>
                                    <input type="number" class="form-control" id="invoice_number_next"
                                           name="invoice_number_next">
                                </div>
                                <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"
                                           for="invoice_number_reset">{{ __('main.Invoice Number Reset') }}</label>
                                    <select class="form-select" id="invoice_number_reset" name="invoice_number_reset">
                                        <option value="never">{{ __('main.Never') }}</option>
                                        <option value="monthly">{{ __('main.Monthly') }}</option>
                                        <option value="yearly">{{ __('main.Yearly') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Note Section-->
                    <div class="col-md-4">
                        <div class="border rounded bg-light p-3">
                            <h6 class="text-primary mb-3">{{ __('main.Credit Note Settings') }}</h6>
                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label"
                                           for="credit_note_number_format">{{ __('main.Credit Note Number Format') }}</label>
                                    <input type="text" class="form-control" id="credit_note_number_format"
                                           name="credit_note_number_format">
                                    <small
                                        class="opacity-5">{{ __('main.Available Tags: {YEAR} {MONTH} {DAY} {NUMBER}') }}</small>
                                </div>

                                <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"
                                           for="credit_note_number_next">{{ __('main.Next Credit Note Number') }}</label>
                                    <input type="number" class="form-control" id="credit_note_number_next"
                                           name="credit_note_number_next">
                                </div>
                                <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                                    <label class="form-label"
                                           for="credit_note_number_reset">{{ __('main.Credit Note Number Reset') }}</label>
                                    <select class="form-select" id="credit_note_number_reset"
                                            name="credit_note_number_reset">
                                        <option value="never">{{ __('main.Never') }}</option>
                                        <option value="monthly">{{ __('main.Monthly') }}</option>
                                        <option value="yearly">{{ __('main.Yearly') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Balance Credit Purchase --}}
                    <div class="col-12 col-lg-6">
                        <div class="border rounded bg-light p-3">
                            <div class="mb-3">
                                <label class="form-label" for="balance_credit_purchase_item_name">
                                    {{ __('main.Balance Credit Purchase Item Name') }}
                                </label>
                                <input type="text" class="form-control" id="balance_credit_purchase_item_name"
                                       name="balance_credit_purchase_item_name"
                                       placeholder="{{ __('main.e.g., Account Credit Purchase') }}">
                                <small class="text-muted">
                                    {{ __('main.Available Tags: {YEAR} {MONTH} {DAY}') }}
                                </small>
                            </div>

                            <div>
                                <label class="form-label" for="balance_credit_purchase_item_description">
                                    {{ __('main.Balance Credit Purchase Item Description') }}
                                </label>
                                <input type="text" class="form-control" id="balance_credit_purchase_item_description"
                                       name="balance_credit_purchase_item_description"
                                       placeholder="{{ __('main.e.g., Prepayment for future usage of cloud services') }}">
                                <small class="text-muted">
                                    {{ __('main.Available Tags: {YEAR} {MONTH} {DAY}') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Refund Item --}}
                    <div class="col-12 col-lg-6">
                        <div class="border rounded bg-light p-3">
                            <div class="mb-3">
                                <label class="form-label" for="refund_item_name">
                                    {{ __('main.Refund Item Name') }}
                                </label>
                                <input type="text" class="form-control" id="refund_item_name"
                                       name="refund_item_name"
                                       placeholder="{{ __('main.e.g., Refund of Unused Credit') }}">
                                <small class="text-muted">
                                    {{ __('main.Available Tags: {YEAR} {MONTH} {DAY} {INVOICE_NUMBER}') }}
                                </small>
                            </div>

                            <div>
                                <label class="form-label" for="refund_item_description">
                                    {{ __('main.Refund Item Description') }}
                                </label>
                                <input type="text" class="form-control" id="refund_item_description"
                                       name="refund_item_description"
                                       placeholder="{{ __('main.e.g., Refund of remaining account balance') }}">
                                <small class="text-muted">
                                    {{ __('main.Available Tags: {YEAR} {MONTH} {DAY} {INVOICE_NUMBER}') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            <div class="card mb-3">
                <div class="card-header">{{ __('main.Invoice Customization Settings') }}</div>
                <div class="card-body">
                    <div class="row g-3">

                        <!-- Font Select -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pdf_font" class="form-label">{{ __('main.PDF Font') }}</label>
                                <select name="pdf_font" id="pdf_font" class="form-select">
                                    <!-- Unicode-friendly fonts -->
                                    <option value="DejaVu Sans">DejaVu Sans</option>
                                    <option value="DejaVu Sans Condensed">DejaVu Sans Condensed</option>
                                    <option value="DejaVu Sans Mono">DejaVu Sans Mono</option>
                                    <option value="DejaVu Serif">DejaVu Serif</option>

                                    <!-- Standard DomPDF fonts -->
                                    <option value="Courier">Courier</option>
                                    <option value="Courier-Bold">Courier Bold</option>
                                    <option value="Courier-Oblique">Courier Oblique</option>
                                    <option value="Courier-BoldOblique">Courier Bold Oblique</option>

                                    <option value="Helvetica">Helvetica</option>
                                    <option value="Helvetica-Bold">Helvetica Bold</option>
                                    <option value="Helvetica-Oblique">Helvetica Oblique</option>
                                    <option value="Helvetica-BoldOblique">Helvetica Bold Oblique</option>

                                    <option value="Times-Roman">Times Roman</option>
                                    <option value="Times-Bold">Times Bold</option>
                                    <option value="Times-Italic">Times Italic</option>
                                    <option value="Times-BoldItalic">Times Bold Italic</option>

                                    <option value="Symbol">Symbol</option>
                                    <option value="ZapfDingbats">ZapfDingbats</option>
                                </select>
                            </div>
                        </div>

                        <!-- Paper Size Select -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pdf_paper" class="form-label">{{ __('main.Paper Size') }}</label>
                                <select name="pdf_paper" id="pdf_paper" class="form-select">
                                    <option value="a4">A4 (210 × 297 mm)</option>
                                    <option value="letter">Letter (8.5 × 11 in)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Pay To Text -->
                        <div class="col-md-6">
                            <div class="border rounded bg-light p-3">
                                <div class="mb-3">
                                    <label class="form-label" for="pay_to_text">{{ __('main.Pay To Text') }}</label>
                                    <textarea class="form-control" id="pay_to_text" name="pay_to_text" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Footer Text -->
                        <div class="col-md-6">
                            <div class="border rounded bg-light p-3">
                                <div class="mb-3">
                                    <label class="form-label" for="invoice_footer_text">{{ __('main.Invoice Footer Text') }}</label>
                                    <textarea class="form-control" id="invoice_footer_text" name="invoice_footer_text" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@section('js')
    @parent

    <script>
        $(document).ready(function () {

            $(".input-mask-trigger").inputmask();

            function loadFormData() {
                blockUI('home_company');
                const $form = $('#home_company');

                $form[0].reset();
                resetFormValidation($form);

                PUQajax('{{route('admin.api.home_company.get', $uuid)}}', {}, 1500, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {

                                if ($element.is(':checkbox')) {
                                    $element.prop('checked', !!value).trigger('click');
                                    return;
                                }

                                if ($element.is('textarea')) {
                                    if (value !== null) {
                                        $element.val(value);
                                    }
                                    return;
                                }

                                $element.val(value);
                            }
                        });

                        if (response.data) {
                            unblockUI('home_company');
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            $("#save").on("click", function (event) {
                const $form = $("#home_company");
                event.preventDefault();
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.home_company.put', $uuid)}}', formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData();
                    });
            });

            loadFormData();
        });
    </script>
@endsection
