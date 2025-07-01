@php
    $incidents = $navigation->getIncidents();
    $counts = collect($incidents)->groupBy('type')->map->count();
@endphp

<div class="main-card card m-0 w-100">
    <div class="card-header bg-sunny-morning d-flex justify-content-between align-items-center"
         data-bs-toggle="collapse" data-bs-target="#ImportantStatusMessages" aria-expanded="false">
        <div class="d-flex align-items-center">
            <div class="me-3"><i class="fas fa-exclamation-triangle text-danger fs-2"></i></div>
            <div>
                <div class="fw-bold fs-5">
                    {{ __('main.Important Status Messages') }}
                    <span class="badge bg-danger">{{ $counts['danger'] ?? 0 }}</span>
                    <span class="badge bg-warning text-dark">{{ $counts['warning'] ?? 0 }}</span>
                    <span class="badge bg-info text-dark">{{ $counts['info'] ?? 0 }}</span>
                    <span class="badge bg-success">{{ $counts['success'] ?? 0 }}</span>
                </div>
                <div class="small text-dark">{{__('main.Real-time updates from the system')}}</div>
            </div>
        </div>
        <button type="button" class="btn toggle-icon" data-bs-toggle="collapse"
                data-bs-target="#ImportantStatusMessages" aria-expanded="false">
            <i class="fas fa-plus fa-2x"></i>
        </button>
    </div>

    <div class="collapse" id="ImportantStatusMessages">
        <div class="container-fluid p-3">
            @foreach ($navigation->getIncidents() as $incident)
                @php
                    $type = $incident['type'];
                    $config = match($type) {
            'info' => [
                'icon' => 'fa-info-circle',
                'alert_class' => 'alert-info',
                'border_class' => 'border-info',
                'text_class' => 'text-info',
                'btn_class' => 'btn-info'
            ],
            'success' => [
                'icon' => 'fa-check-circle',
                'alert_class' => 'alert-success',
                'border_class' => 'border-success',
                'text_class' => 'text-success',
                'btn_class' => 'btn-success'
            ],
            'warning' => [
                'icon' => 'fa-exclamation-triangle',
                'alert_class' => 'alert-warning',
                'border_class' => 'border-warning',
                'text_class' => 'text-warning',
                'btn_class' => 'btn-warning'
            ],
            'danger' => [
                'icon' => 'fa-exclamation-circle',
                'alert_class' => 'alert-danger',
                'border_class' => 'border-danger',
                'text_class' => 'text-danger',
                'btn_class' => 'btn-danger'
            ],
            default => [
                'icon' => 'fa-bell',
                'alert_class' => 'alert-secondary',
                'border_class' => 'border-secondary',
                'text_class' => 'text-secondary',
                'btn_class' => 'btn-secondary'
            ]
        };
                @endphp

                <div
                        class="alert {{ $config['alert_class'] }} {{ $config['border_class'] }} border-start border-4 shadow-sm mb-3"
                        role="alert">

                    <div class="d-flex align-items-start">
                        <div class="me-3 d-flex align-items-center">
                            <i class="fas {{ $config['icon'] }} {{ $config['text_class'] }} fs-2"></i>
                        </div>

                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2 fw-bold">{{ $incident['title'] }}</h5>

                            @if(isset($incident['description']) && !empty($incident['description']))
                                <p class="mb-3">{{ $incident['description'] }}</p>
                            @endif

                            <div class="row g-2 small mb-0">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-calendar-plus me-2 text-muted"></i>
                                        <strong class="me-2">{{__('main.Created')}}:</strong>
                                        <span class="text-muted">{{ $incident['created'] }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-arrow-clockwise me-2 text-muted"></i>
                                        <strong class="me-2">{{__('main.Updated')}}:</strong>
                                        <span class="text-muted">{{ $incident['updated'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ms-3 d-flex flex-column align-items-end gap-2">
                            @if(isset($incident['url']) && !empty($incident['url']))
                                <a href="{{ $incident['url'] }}"
                                   target="_blank"
                                   class="mb-2 me-2 btn-icon btn-shadow btn-outline-2x btn {{ $config['btn_class'] }}">
                                    <i class="bi bi-arrow-up-right-square me-1"></i>{{__('main.Details')}}
                                </a>
                            @endif

                            @if(isset($incident['dismiss_url']) && !empty($incident['dismiss_url']))
                                <a href="{{ $incident['dismiss_url'] }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-repeat me-1"></i>{{__('main.Dismiss')}}
                                </a>
                            @endif

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
<script>
    const toggleBtn = document.querySelector('.toggle-icon');
    const toggleIcon = toggleBtn.querySelector('i');
    const collapse = document.getElementById('ImportantStatusMessages');

    collapse.addEventListener('show.bs.collapse', () => {
        toggleIcon.classList.remove('fa-plus');
        toggleIcon.classList.add('fa-minus');
    });

    collapse.addEventListener('hide.bs.collapse', () => {
        toggleIcon.classList.remove('fa-minus');
        toggleIcon.classList.add('fa-plus');
    });
</script>
