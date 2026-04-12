<table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td style="padding: 20px;">
            <p style="font-size: 16px;">
                <strong>Re: [Ticket #{{ $ticket->number }}] {{ $ticket->subject }}</strong>
            </p>

            <p style="font-size: 15px;">
                A staff member has replied to your support ticket.
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
                    <td><strong>Status:</strong></td>
                    <td>{{ $ticket->status->title ?? '' }}</td>
                </tr>
            </table>

            @if(!empty($reply))
            <div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #007bff;">
                <p style="font-size: 14px; font-weight: bold; margin: 0 0 5px 0;">{{ $reply->name ?? 'Staff' }}:</p>
                <p style="font-size: 14px; margin: 0;">{{ $reply->message ?? '' }}</p>
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
