<div class="main-card card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fa fa-sliders-h me-2 text-primary"></i> {{ __('main.Home Company') }}
        </h5>
        <button class="btn-icon btn-shadow btn-outline-2x btn btn-outline-info"
                type="button"
                data-bs-toggle="collapse" data-bs-target="#invoiceHomeCompanyData"
                aria-expanded="false" aria-controls="invoiceHomeCompanyData">
            <span class="me-1">{{ __('main.Open') }}</span>
            <i class="fa fa-chevron-down" data-bs-toggle-icon></i>
        </button>
    </div>
    <div class="collapse mb-3" id="invoiceHomeCompanyData">
        <div class="card-body">

            <div class="mb-3 row">
                <label for="home_company_company_name"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Company Name') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_company_name" id="home_company_company_name" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_address_1"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Address 1') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_address_1" id="home_company_address_1" type="text" class="form-control"
                           disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_address_2"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Address 2') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_address_2" id="home_company_address_2" type="text" class="form-control"
                           disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_city" class="form-label col-sm-4 text-end col-form-label">{{ __('main.City') }}
                    :</label>
                <div class="col-sm-8">
                    <input name="home_company_city" id="home_company_city" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_postcode"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Postcode') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_postcode" id="home_company_postcode" type="text" class="form-control"
                           disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_country"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Country') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_country" id="home_company_country" type="text" class="form-control"
                           disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_region"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Region') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_region" id="home_company_region" type="text" class="form-control"
                           disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_tax_local_id"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Local Tax ID') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_tax_local_id" id="home_company_tax_local_id" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_tax_local_id_name"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Local Tax ID Name') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_tax_local_id_name" id="home_company_tax_local_id_name" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_tax_eu_vat_id"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.EU VAT ID') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_tax_eu_vat_id" id="home_company_tax_eu_vat_id" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_tax_eu_vat_id_name"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.EU VAT ID Name') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_tax_eu_vat_id_name" id="home_company_tax_eu_vat_id_name" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_registration_number"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Registration Number') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_registration_number" id="home_company_registration_number" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_registration_number_name"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Registration Number Name') }}
                    :</label>
                <div class="col-sm-8">
                    <input name="home_company_registration_number_name" id="home_company_registration_number_name"
                           type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_us_ein"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.US EIN') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_us_ein" id="home_company_us_ein" type="text" class="form-control"
                           disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_us_state_tax_id"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.US State Tax ID') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_us_state_tax_id" id="home_company_us_state_tax_id" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_us_entity_type"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.US Entity Type') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_us_entity_type" id="home_company_us_entity_type" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_ca_gst_hst_number"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.CA GST/HST Number') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_ca_gst_hst_number" id="home_company_ca_gst_hst_number" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_ca_pst_qst_number"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.CA PST/QST Number') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_ca_pst_qst_number" id="home_company_ca_pst_qst_number" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_ca_entity_type"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.CA Entity Type') }}:</label>
                <div class="col-sm-8">
                    <input name="home_company_ca_entity_type" id="home_company_ca_entity_type" type="text"
                           class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_pay_to_text"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Pay To Text') }}:</label>
                <div class="col-sm-8">
                    <textarea class="form-control" id="home_company_pay_to_text" name="home_company_pay_to_text" rows="3" disabled></textarea>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="home_company_invoice_footer_text"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Invoice Footer Text') }}:</label>
                <div class="col-sm-8">
                    <textarea class="form-control" id="home_company_invoice_footer_text" name="home_company_invoice_footer_text"
                              rows="3" disabled></textarea>
                </div>
            </div>

        </div>
    </div>
</div>
