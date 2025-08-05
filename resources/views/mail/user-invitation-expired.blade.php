@component('mail::message')

# User Invitation - Expired

Dear {{ $maildata['user'] }},

The invitation sent to <strong>{{ $maildata['name'] }}</strong> has expired, therefore the invitee can no longer register on the EU-CSI platform via the shared link.

You may send a new invitation by following <a href="{{ $maildata['url'] }}">Management -> Invitation</a> on the EU-CSI platform.

With best regards,<br>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent