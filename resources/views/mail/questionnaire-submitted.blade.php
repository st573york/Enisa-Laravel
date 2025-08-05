@component('mail::message')

# MS Survey Submitted - {{ $maildata['country'] }}

The MS <strong>{{ $maildata['questionnaire'] }}</strong> for <strong>{{ $maildata['country'] }}</strong> has been submitted by <strong>{{ $maildata['author'] }}</strong>.

With best regards,<br>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent
