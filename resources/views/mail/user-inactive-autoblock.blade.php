@component('mail::message')

# User Inactive - Auto Block

Dear {{ $maildata['user'] }},

The following EU-CSI account has been inactive for more than <strong>{{ $maildata['inactive_days'] }}</strong> days and has been automatically blocked by the system.

Account details:

- User: <strong>{{ $maildata['inactive_user'] }}</strong>
- Email: <strong>{{ $maildata['inactive_email'] }}</strong>
- Last Login: <strong>{{ $maildata['inactive_last_login'] }}</strong>

You may visit the Users page on the platform to take any further action, either by deleting or unblocking this account.

@component('mail::button', ['url' => $maildata['url']])
Users
@endcomponent

With best regards,<br>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent