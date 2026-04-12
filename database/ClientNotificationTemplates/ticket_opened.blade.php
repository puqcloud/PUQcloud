<table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td style="padding: 20px;">
            <p style="font-size: 16px;">
                <strong>Ticket #{{ $ticket->number }} Opened</strong>
            </p>

            <p style="font-size: 15px;">
                Your support ticket has been successfully created. We will review it and get back to you shortly.
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
                    <td><strong>Department:</strong></td>
                    <td>{{ $ticket->department->name ?? '' }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>{{ $ticket->status->title ?? '' }}</td>
                </tr>
            </table>

            @if($ticket->replies->first())
            <div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #007bff;">
                <p style="font-size: 14px; margin: 0;">{{ $ticket->replies->first()->message }}</p>
            </div>
            @endif

            <p style="margin-top: 20px; font-size: 15px;">
                <a href="{{ route('client.web.panel.support.ticket.detail', $ticket->uuid) }}" style="color: #3366cc;">
                    View Ticket in Client Area
                </a>
            </p>
        </td>
    </tr>
</table>
