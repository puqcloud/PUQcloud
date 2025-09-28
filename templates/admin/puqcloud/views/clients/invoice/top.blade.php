<div class="main-card card mb-1">
    <div class="card-body">
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-4">
                <div class="position-relative mb-2 w-100">
                    <label for="number" class="form-label" id="type"></label>
                    <label for="number" class="form-label" id="status"></label>
                    <div class="input-group">
                        <div class="input-group-text datepicker-trigger fw-bold">#</div>
                        <input name="number" id="number" type="text" class="form-control" disabled>
                        <button id="go_to_invoice" type="button"
                                class="btn-icon btn-outline-2x btn btn-outline-success" style="display: none;">
                            <i class="fa fa-file-invoice-dollar"></i> {{__('main.Invoice')}}
                        </button>

                        <button id="go_to_proforma" type="button"
                                class="btn-icon btn-outline-2x btn btn-outline-warning" style="display: none;">
                            <i class="fa fa-file-invoice"></i> {{__('main.Proforma')}}
                        </button>
                    </div>
                </div>
                <div id="credit_note_buttons" class="d-flex flex-wrap gap-2 mb-2"></div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2">
                <div class="position-relative mb-3 w-100">
                    <div class="input-group">
                        <input name="tax_1_name" id="tax_1_name" type="text" class="form-control text-end" disabled
                               style="max-width: 50%;">
                        <input name="tax_1" id="tax_1" type="text" class="form-control text-end" disabled>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="position-relative mb-3 w-100">
                    <div class="input-group">
                        <input name="tax_2_name" id="tax_2_name" type="text" class="form-control text-end" disabled
                               style="max-width: 50%;">
                        <input name="tax_2" id="tax_2" type="text" class="form-control text-end" disabled>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="position-relative mb-3 w-100">
                    <div class="input-group">
                        <input name="tax_3_name" id="tax_3_name" type="text" class="form-control text-end" disabled
                               style="max-width: 50%;">
                        <input name="tax_3" id="tax_3" type="text" class="form-control text-end" disabled>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">

                <div class="position-relative mb-3">
                    <table class="table table-striped">
                        <tbody>
                        <tr>
                            <td>{{__('main.Tax')}}</td>
                            <td>
                                <div id="tax"></div>
                            </td>
                        </tr>
                        <tr>
                            <td>{{__('main.Net')}}</td>
                            <td>
                                <div id="subtotal"></div>
                            </td>
                        </tr>

                        <tr>
                            <td>{{__('main.Gross')}}</td>
                            <td>
                                <div id="total" class="fw-bold"></div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-3">
                <div class="position-relative mb-3">
                    <table class="table table-striped">
                        <tbody>
                        <tr>
                            <td>{{__('main.Due')}}</td>
                            <td>
                                <div id="due_amount" class="fw-bold text-danger"></div>
                            </td>
                        </tr>
                        <tr>
                            <td>{{__('main.Net Paid')}}</td>
                            <td>
                                <div id="paid_net_amount"></div>
                            </td>
                        </tr>

                        <tr>
                            <td>{{__('main.Gross Paid')}}</td>
                            <td>
                                <div id="paid_gross_amount"></div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2">
                <div class="position-relative mb-2 w-100">
                    <label for="issue_date" class="form-label">{{__('main.Issue Date')}}</label>
                    <div class="input-group">
                        <div class="input-group-text datepicker-trigger">
                            <i class="fa fa-calendar-alt"></i>
                        </div>
                        <input name="issue_date" id="issue_date" type="text" class="form-control"
                               data-toggle="datepicker-icon" disabled>
                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2">
                <div class="position-relative mb-2 w-100">
                    <label for="due_date" class="form-label">{{__('main.Due Date')}}</label>
                    <div class="input-group">
                        <div class="input-group-text datepicker-trigger">
                            <i class="fa fa-calendar-alt"></i>
                        </div>
                        <input name="due_date" id="due_date" type="text" class="form-control"
                               data-toggle="datepicker-icon" disabled>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2">
                <div class="position-relative mb-2 w-100">
                    <label for="paid_date" class="form-label">{{__('main.Paid Date')}}</label>
                    <div class="input-group">
                        <div class="input-group-text datepicker-trigger">
                            <i class="fa fa-calendar-alt"></i>
                        </div>
                        <input name="paid_date" id="paid_date" type="text" class="form-control"
                               data-toggle="datepicker-icon" disabled>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2">
                <div class="position-relative mb-2 w-100">
                    <label for="refunded_date" class="form-label">{{__('main.Refunded Date')}}</label>
                    <div class="input-group">
                        <div class="input-group-text datepicker-trigger">
                            <i class="fa fa-calendar-alt"></i>
                        </div>
                        <input name="refunded_date" id="refunded_date" type="text" class="form-control"
                               data-toggle="datepicker-icon" disabled>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3 col-xl-3 col-xxl-2">
                <div class="position-relative mb-2 w-100">
                    <label for="canceled_date" class="form-label">{{__('main.Canceled Date')}}</label>
                    <div class="input-group">
                        <div class="input-group-text datepicker-trigger">
                            <i class="fa fa-calendar-alt"></i>
                        </div>
                        <input name="canceled_date" id="canceled_date" type="text" class="form-control"
                               data-toggle="datepicker-icon" disabled>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
