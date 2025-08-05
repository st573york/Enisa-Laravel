@component('mail::message')

# User Inactive - Reminder

Dear {{ $maildata['user'] }},

We have noticed that your EU-CSI platform account, registered with the current email address, has been inactive since <strong>{{ $maildata['last_login'] }}</strong>.
If you wish to maintain your account, please log in to EU-CSI platform before <strong>{{ $maildata['inactive_deadline'] }}</strong>, otherwise your account will be suspended.

For any questions, please contact <strong>security-index@enisa.europa.eu</strong>.

@component('mail::button', ['url' => $maildata['url']])
EU CSI
@endcomponent

With best regards,<br>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent