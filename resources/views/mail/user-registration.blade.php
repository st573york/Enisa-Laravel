@component('mail::message')

# User Registration

A new user with email <strong>{{ $maildata['email'] }}</strong> has registered to the EU-CSI platform.

With best regards,<br>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent