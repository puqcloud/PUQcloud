@if(count($activities) === 0)
    <div class="text-center text-muted py-3">
        <i class="fa fa-history" style="font-size: 24px;"></i>
        <p class="mt-2 mb-0" style="font-size: 12px;">No recent activity</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size: 12px;">
            <tbody>
                @foreach($activities as $activity)
                    <tr>
                        <td class="text-nowrap" style="width: 1%; padding: 4px 8px;">
                            <span class="text-muted">{{ \Carbon\Carbon::parse($activity->created_at)->format('H:i') }}</span>
                        </td>
                        <td style="padding: 4px 8px;">
                            @if($activity->admin_name)
                                <b>{{ $activity->admin_name }}</b>
                            @else
                                <b>System</b>
                            @endif
                            <span class="text-muted">{{ $activity->description }}</span>
                        </td>
                        <td class="text-end text-nowrap" style="width: 1%; padding: 4px 8px;">
                            <span class="badge bg-light text-dark">{{ $activity->model_type ?? '' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
