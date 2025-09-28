<p>Hello {{ $user->firstname }},</p>

<p>
    We received a request to reset your account password.
    If you did not request this, please ignore this message.
</p>

<p>
    To set a new password, please click the link below:
</p>

<p style="word-wrap: break-word;">
    <a href="{{ $reset_password_url }}" target="_blank" style="word-break: break-word;">
        {{ $reset_password_url }}
    </a>
</p>

<p>
    This link will expire in {{ $expire }} minutes.
    If you have any questions or need assistance, feel free to contact our support team.
</p>
