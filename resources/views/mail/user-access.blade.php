@component('mail::message')

# User Access

{!! $maildata['user'] !!} has been <strong>{!! $maildata['status'] !!}</strong> in the EU-CSI web app.

Changes realised by <strong>{{ $maildata['author_name'] }}</strong> (<strong>{{ $maildata['author_email'] }}</strong>).

@component('mail::button', ['url' => $maildata['url']])
EU CSI
@endcomponent

With best regards,<br>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent