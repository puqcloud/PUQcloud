<p>Hello {{ $user->firstname }},</p>

<p>
    Welcome to our platform! Your client account has been successfully created.
    You can now log in to your dashboard to view your services, manage billing, and access support.
    If you need any help, we’re here for you.
</p>

<p>Click the link below to log in to your dashboard:</p>

<a href="{{ route('client.web.panel.login') }}" target="_blank">Log in to Dashboard</a>

<p>Thank you for choosing us. We're happy to have you with us!</p>
