<div class="main-card card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fa fa-sliders-h me-2 text-primary"></i> {{ __('main.Client') }}
        </h5>
        <button class="btn-icon btn-shadow btn-outline-2x btn btn-outline-info"
                type="button"
                data-bs-toggle="collapse" data-bs-target="#invoiceClientData"
                aria-expanded="false" aria-controls="invoiceClientData">
            <span class="me-1">{{ __('main.Open') }}</span>
            <i class="fa fa-chevron-down" data-bs-toggle-icon></i>
        </button>
    </div>
    <div class="collapse mb-3" id="invoiceClientData">
        <div class="card-body">
            <div class="mb-3 row">
                <label for="client_firstname"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Firstname') }}</label>
                <div class="col-sm-8">
                    <input name="client_firstname" id="client_firstname" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_lastname"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Lastname') }}</label>
                <div class="col-sm-8">
                    <input name="client_lastname" id="client_lastname" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_company_name"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Company Name') }}</label>
                <div class="col-sm-8">
                    <input name="client_company_name" id="client_company_name" type="text" class="form-control"
                           disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_country"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Country') }}</label>
                <div class="col-sm-8">
                    <input name="client_country" id="client_country" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_postcode"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Postcode') }}</label>
                <div class="col-sm-8">
                    <input name="client_postcode" id="client_postcode" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_address_1"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Address 1') }}</label>
                <div class="col-sm-8">
                    <input name="client_address_1" id="client_address_1" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_address_2"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Address 2') }}</label>
                <div class="col-sm-8">
                    <input name="client_address_2" id="client_address_2" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_city"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.City') }}</label>
                <div class="col-sm-8">
                    <input name="client_city" id="client_city" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_region"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Region') }}</label>
                <div class="col-sm-8">
                    <input name="client_region" id="client_region" type="text" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_email"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Email') }}</label>
                <div class="col-sm-8">
                    <input name="client_email" id="client_email" type="email" class="form-control" disabled>
                </div>
            </div>

            <div class="mb-3 row">
                <label for="client_tax_id"
                       class="form-label col-sm-4 text-end col-form-label">{{ __('main.Tax ID') }}</label>
                <div class="col-sm-8">
                    <input name="client_tax_id" id="client_tax_id" type="text" class="form-control" disabled>
                </div>
            </div>
        </div>
    </div>
</div>
