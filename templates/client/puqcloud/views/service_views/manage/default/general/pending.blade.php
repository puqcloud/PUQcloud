{{--
    Generic "deploying" / "pending" sub-view for the default manage template.

    This is rendered by general.blade.php when the core state machine
    ($service->getManageViewState()) reports phase = deploying or deploying_error.

    The JS here polls the GetStatus endpoint every N seconds and reloads the page
    as soon as the phase transitions out of deploying — so clients automatically see
    the active content without having to refresh manually.

    `error_message` + `error_is_insufficient_funds` come sanitized from the state
    machine: "Insufficient funds" shows a loud warning, every other error becomes a
    generic "Something went wrong" so internal details don't leak to the client.
--}}
@php
    $state = $manageState ?? $service->getManageViewState();
    $errorMessage = $state['error_message'] ?? null;
    $isInsufficientFunds = $state['error_is_insufficient_funds'] ?? false;
    $pollInterval = $state['poll_interval'] ?? 5;
@endphp

<div class="container px-0">
    <div class="card mb-2 shadow-sm border-0" style="min-height: 300px;">
        <div class="card-body text-center" id="deploying-status">
            @if($isInsufficientFunds)
                <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
                    <div class="alert alert-danger text-uppercase fw-bold d-flex align-items-center justify-content-center p-4 shadow-lg"
                         role="alert"
                         style="font-size:1.3rem; border: 2px solid #dc3545; animation: pulse 1.5s infinite;">
                        <i class="fa-solid fa-triangle-exclamation me-2" style="font-size:1.5rem;"></i>
                        {{ $errorMessage }}
                    </div>
                </div>
            @else
                @if($errorMessage)
                    <div class="alert alert-warning d-inline-flex align-items-center mt-3 mb-2 px-4 py-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ $errorMessage }}
                    </div>
                @endif

                <div class="mb-4">
                    <div class="spinner-border text-primary" style="width: 5rem; height: 5rem;" role="status"></div>
                </div>

                <h4 class="fw-bold text-dark">
                    {{ __('main.Deployment in progress') }}<br>
                    {{ __('main.Please Wait') }}...
                </h4>

                <p class="small text-muted mt-3">
                    {{ __('main.Your service is being set up. This may take a moment.') }}
                </p>

                <div class="progress mt-4" style="height: 8px; max-width: 400px; margin: 0 auto;">
                    <div id="deploying-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                         role="progressbar" style="width: 0%"></div>
                </div>

                <p class="small text-muted mt-2">
                    {{ __('main.Checking again in') }} <span id="deploying-countdown">{{ $pollInterval }}</span> {{ __('main.seconds') }}
                </p>
            @endif
        </div>
    </div>
</div>

@section('js')
    @parent
    @if(! $isInsufficientFunds)
        <script>
            $(document).ready(function () {
                // Polling settings come from the core state machine so every template stays consistent.
                const POLL_INTERVAL = {{ (int) $pollInterval }};
                let countdown = POLL_INTERVAL;
                let progress = 0;

                function updateTimer() {
                    $("#deploying-countdown").text(countdown);
                    countdown--;
                    if (countdown < 0) countdown = POLL_INTERVAL;
                }

                function updateProgress() {
                    progress += 20;
                    if (progress > 100) progress = 0;
                    $("#deploying-progress-bar").css("width", progress + "%");
                }

                // Poll the generic GetStatus endpoint (provided by ServiceViewManageActions trait).
                // When the phase flips out of any deploying state → reload so the active sub-view
                // takes over automatically.
                function checkStatus() {
                    PUQajax(
                        "{{ route('client.api.cloud.service.manage.get', ['uuid' => $service->uuid, 'method' => 'GetStatus']) }}",
                        {}, 50, null, 'GET'
                    ).then(function (response) {
                        var phase = response && response.data && response.data.phase;
                        // Reload on transition out of any deploying state (into active,
                        // suspended, terminated, awaiting_payment, etc.) so the correct
                        // sub-view takes over.
                        if (phase && phase !== 'deploying' && phase !== 'deploying_error') {
                            location.reload();
                        } else {
                            countdown = POLL_INTERVAL;
                        }
                    }).catch(function () {
                        countdown = POLL_INTERVAL;
                    });
                }

                setInterval(updateTimer, 1000);
                setInterval(updateProgress, 1000);
                setInterval(checkStatus, POLL_INTERVAL * 1000);
            });
        </script>
    @endif
@endsection
