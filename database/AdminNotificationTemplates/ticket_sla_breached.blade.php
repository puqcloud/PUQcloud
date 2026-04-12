<table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td style="padding: 20px;">
            <p style="font-size: 16px;">
                <strong style="color: #dc3545;">SLA Breached — Ticket #{{ $ticket->number }}</strong>
            </p>

            <p style="font-size: 15px;">
                The SLA response time has been exceeded for this support ticket. Immediate attention is required.
            </p>

            <table cellpadding="6" cellspacing="0" style="border: 1px solid #ddd; margin-top: 10px; font-size: 15px;">
                <tr>
                    <td><strong>Ticket Number:</strong></td>
                    <td>#{{ $ticket->number }}</td>
                </tr>
                <tr>
                    <td><strong>Subject:</strong></td>
                    <td>{{ $ticket->subject }}</td>
                </tr>
                <tr>
                    <td><strong>From:</strong></td>
                    <td>{{ $ticket->name ?? $ticket->email }}</td>
                </tr>
                <tr>
                    <td><strong>Department:</strong></td>
                    <td>{{ $ticket->department->name ?? '' }}</td>
                </tr>
                <tr>
                    <td><strong>SLA Limit:</strong></td>
                    <td>{{ $ticket->department->sla_response_hours ?? '' }} hours</td>
                </tr>
                <tr>
                    <td><strong>Last Reply:</strong></td>
                    <td>{{ $ticket->last_reply_at }}</td>
                </tr>
                <tr>
                    <td><strong>Priority:</strong></td>
                    <td>{{ $ticket->priority ?? '' }}</td>
                </tr>
            </table>

            <p style="margin-top: 20px; font-size: 15px;">
                <a href="{{ route('admin.web.ticket', $ticket->uuid) }}" style="color: #3366cc;">
                    View Ticket in Admin Area
                </a>
            </p>
        </td>
    </tr>
</table>
