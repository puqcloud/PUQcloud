<div class="row task-status-container">
    @foreach ($statuses as $status)
        @php
            $icon = '';
            $labelClass = '';
            switch ($status) {
                case 'queued':
                    $icon = '<i class="fa fa-clock status-icon text-secondary" style="font-size: 30px;"></i>';
                    $labelClass = 'text-secondary';
                    break;
                case 'duplicate':
                    $icon = '<i class="fa fa-copy status-icon text-info" style="font-size: 30px;"></i>';
                    $labelClass = 'text-info';
                    break;
                case 'pending':
                    $icon = '<i class="fa fa-hourglass-half status-icon text-warning" style="font-size: 30px;"></i>';
                    $labelClass = 'text-warning';
                    break;
                case 'processing':
                    $icon = '<i class="fa fa-spinner status-icon fa-spin text-primary" style="font-size: 30px;"></i>';
                    $labelClass = 'text-primary';
                    break;
                case 'completed':
                    $icon = '<i class="fa fa-check-circle status-icon text-success" style="font-size: 30px;"></i>';
                    $labelClass = 'text-success';
                    break;
                case 'failed':
                    $icon = '<i class="fa fa-times-circle status-icon text-danger" style="font-size: 30px;"></i>';
                    $labelClass = 'text-danger';
                    break;
                default:
                    $icon = '<i class="fa fa-info-circle status-icon text-info" style="font-size: 30px;"></i>';
                    $labelClass = 'text-info';
                    break;
            }

            $count = $taskCounts->get($status)->count ?? 0;
        @endphp

        <div class="col-6 col-md-6 text-center mb-0">
            <div>{!! $icon !!}</div>
            <div class="results-subtitle widget-title opacity-5 text-uppercase" style="font-size: 12px;">
                {{ ucfirst($status) }}
            </div>
            <div class="results-title {{ $labelClass }}" style="font-size: 16px;">
                {{ $count }} Tasks
            </div>
        </div>
    @endforeach
</div>
