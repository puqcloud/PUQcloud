<div class="main-card card mb-2">
    <div class="card-body pt-0 pb-1" id="schedule_card">

        <form id="schedule_form">
            <div class="row">
                <div class="col border rounded ms-1 me-1 mt-2 mb-1 p-2 bg-light">
                    <div class="row">
                        <div class="col-12 d-flex align-items-center mb-1">
                            <i class="fas fa-calendar-check text-success me-2"></i>
                            <div class="form-check form-switch me-2">
                                <input class="form-check-input" type="checkbox" id="monday_enabled"
                                       name="monday_enabled">
                            </div>
                            <label for="monday_enabled"
                                   class="me-2 mb-0 small fw-bold">{{__('Product.puqProxmox.Monday')}}</label>
                        </div>
                        <div class="col-12">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-clock"></i></span>
                                <input type="text" class="form-control timepicker" id="monday_time" name="monday_time">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col border rounded ms-1 me-1 mt-2 mb-1 p-2 bg-light">
                    <div class="row">
                        <div class="col-12 d-flex align-items-center mb-1">
                            <i class="fas fa-calendar-check text-success me-2"></i>
                            <div class="form-check form-switch me-2">
                                <input class="form-check-input" type="checkbox" id="tuesday_enabled"
                                       name="tuesday_enabled">
                            </div>
                            <label for="tuesday_enabled"
                                   class="me-2 mb-0 small fw-bold">{{__('Product.puqProxmox.Tuesday')}}</label>
                        </div>
                        <div class="col-12">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-clock"></i></span>
                                <input type="text" class="form-control timepicker" id="tuesday_time"
                                       name="tuesday_time">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col border rounded ms-1 me-1 mt-2 mb-1 p-2 bg-light">
                    <div class="row">
                        <div class="col-12 d-flex align-items-center mb-1">
                            <i class="fas fa-calendar-check text-success me-2"></i>
                            <div class="form-check form-switch me-2">
                                <input class="form-check-input" type="checkbox" id="wednesday_enabled"
                                       name="wednesday_enabled">
                            </div>
                            <label for="wednesday_enabled"
                                   class="me-2 mb-0 small fw-bold">{{__('Product.puqProxmox.Wednesday')}}</label>
                        </div>
                        <div class="col-12">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-clock"></i></span>
                                <input type="text" class="form-control timepicker" id="wednesday_time"
                                       name="wednesday_time">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col border rounded ms-1 me-1 mt-2 mb-1 p-2 bg-light">
                    <div class="row">
                        <div class="col-12 d-flex align-items-center mb-1">
                            <i class="fas fa-calendar-check text-success me-2"></i>
                            <div class="form-check form-switch me-2">
                                <input class="form-check-input" type="checkbox" id="thursday_enabled"
                                       name="thursday_enabled">
                            </div>
                            <label for="thursday_enabled"
                                   class="me-2 mb-0 small fw-bold">{{__('Product.puqProxmox.Thursday')}}</label>
                        </div>
                        <div class="col-12">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-clock"></i></span>
                                <input type="text" class="form-control timepicker" id="thursday_time"
                                       name="thursday_time">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col border rounded ms-1 me-1 mt-2 mb-1 p-2 bg-light">
                    <div class="row">
                        <div class="col-12 d-flex align-items-center mb-1">
                            <i class="fas fa-calendar-check text-success me-2"></i>
                            <div class="form-check form-switch me-2">
                                <input class="form-check-input" type="checkbox" id="friday_enabled"
                                       name="friday_enabled">
                            </div>
                            <label for="friday_enabled"
                                   class="me-2 mb-0 small fw-bold">{{__('Product.puqProxmox.Friday')}}</label>
                        </div>
                        <div class="col-12">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-clock"></i></span>
                                <input type="text" class="form-control timepicker" id="friday_time" name="friday_time">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col d-flex align-items-center border rounded ms-1 me-1 mt-2 mb-1 p-2"
                     style="background-color: #fdecea !important;">
                    <i class="fas fa-umbrella-beach text-danger me-2"></i>
                    <div class="form-check form-switch me-2">
                        <input class="form-check-input" type="checkbox" id="saturday_enabled" name="saturday_enabled">
                    </div>
                    <label for="saturday_enabled"
                           class="me-2 mb-0 small fw-bold">{{__('Product.puqProxmox.Saturday')}}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-clock"></i></span>
                        <input type="text" class="form-control timepicker" id="saturday_time" name="saturday_time">
                    </div>
                </div>

                <div class="col d-flex align-items-center border rounded ms-1 me-1 mt-2 mb-1 p-2"
                     style="background-color: #fdecea !important;">
                    <i class="fas fa-umbrella-beach text-danger me-2"></i>
                    <div class="form-check form-switch me-2">
                        <input class="form-check-input" type="checkbox" id="sunday_enabled" name="sunday_enabled">
                    </div>
                    <label for="sunday_enabled"
                           class="me-2 mb-0 small fw-bold">{{__('Product.puqProxmox.Sunday')}}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-clock"></i></span>
                        <input type="text" class="form-control timepicker" id="sunday_time" name="sunday_time">
                    </div>
                </div>
            </div>
        </form>

        <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                @php
                    \Carbon\Carbon::setLocale(app()->getLocale());
                    $now = \Carbon\Carbon::now();
                @endphp
                <div class="text-center">
                    <i class="fas fa-clock text-primary me-2"></i>
                    <span class="fw-bold">{{ $now->isoFormat('dddd, D MMMM YYYY, HH:mm') }}</span>
                </div>
            </div>

            <div class="col-md-6 d-flex justify-content-center">
                <div class="text-center">
                    <button type="button"
                            class="btn-icon btn-2x btn btn-success"
                            id="schedule_save">
                        <i class="fa fa-save"></i>
                        {{__('Product.puqProxmox.Save')}}
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function initFlatpickr() {
                document.querySelectorAll(".timepicker").forEach(input => {
                    if (!input._flatpickr) {
                        flatpickr(input, {
                            enableTime: true,
                            noCalendar: true,
                            dateFormat: "H:i",
                            time_24hr: true,
                            minuteIncrement: 5,
                            allowInput: true,
                            defaultDate: input.value || "00:00"
                        });
                    }
                });
            }

            function updateTimeInputState(day) {
                let checkbox = $('#' + day + '_enabled');
                let input = $('#' + day + '_time');
                if (checkbox.is(':checked')) {
                    input.prop('disabled', false).removeClass('bg-light text-muted');
                } else {
                    input.prop('disabled', true).addClass('bg-light text-muted');
                }
            }

            function updateAllInputs() {
                ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].forEach(updateTimeInputState);
            }

            function loadScheduleData() {
                blockUI('schedule_card');
                PUQajax("{{ route('client.api.cloud.service.module.get', ['uuid' => $service_uuid, 'method' => 'getLxcBackupSchedule']) }}", {}, 50, null, 'GET')
                    .then(function (response) {
                        let data = response.data;
                        $.each(data, function (day, values) {
                            $('#' + day + '_enabled').prop('checked', values.enable);
                            $('#' + day + '_time').val(values.time);
                        });
                        initFlatpickr();
                        updateAllInputs();
                        unblockUI('schedule_card');
                    });
            }

            $('input.form-check-input').on('change', function () {
                let day = this.id.replace('_enabled', '');
                updateTimeInputState(day);
            });

            $('#schedule_save').on('click', function (event) {
                event.preventDefault();
                if ($('#schedule_form').length) {
                    var $form = $('#schedule_form');
                    var formData = serializeForm($form);
                    PUQajax('{{ route('client.api.cloud.service.module.post', ['uuid' => $service_uuid, 'method' => 'postLxcBackupSchedule']) }}', formData, 500, $(this), 'POST', $form)
                        .then(function (response) {
                            loadScheduleData();
                        });
                }
            });

            loadScheduleData();
        });

    </script>
@endsection
