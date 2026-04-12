<table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; color: #333;">
    <tr>
        <td style="padding: 20px;">
            <p style="font-size: 16px;">
                <strong>Re: [Ticket #{{ $ticket->number }}] {{ $ticket->subject }}</strong>
            </p>

            <p style="font-size: 15px;">
                A client has replied to a support ticket.
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
            </table>

            @if(!empty($reply))
            <div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #ffc107;">
                <p style="font-size: 14px; font-weight: bold; margin: 0 0 5px 0;">{{ $reply->name ?? 'Client' }}:</p>
                <p style="font-size: 14px; margin: 0;">{{ $reply->message ?? '' }}</p>
            </div>
            @endif

            <p style="margin-top: 20px; font-size: 15px;">
                <a href="{{ route('admin.web.ticket', $ticket->uuid) }}" style="color: #3366cc;">
                    View Ticket in Admin Area
                </a>
            </p>
        </td>
    </tr>
</table>
