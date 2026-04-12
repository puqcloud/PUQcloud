<div class="mb-2">
    <label class="form-label text-muted mb-1" style="font-size: 11px; text-transform: uppercase;">
        <i class="fa fa-lock"></i> {{ __('main.Admin Notes') }}
    </label>
    <div class="p-2 bg-light rounded" style="font-size: 13px; min-height: 60px; white-space: pre-wrap;">{{ $adminNotes ?: '---' }}</div>
</div>

<div>
    <label class="form-label text-muted mb-1" style="font-size: 11px; text-transform: uppercase;">
        <i class="fa fa-sticky-note"></i> {{ __('main.Client Notes') }}
    </label>
    <div class="p-2 bg-light rounded" style="font-size: 13px; min-height: 60px; white-space: pre-wrap;">{{ $clientNotes ?: '---' }}</div>
</div>
