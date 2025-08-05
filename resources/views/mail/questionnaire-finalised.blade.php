@component('mail::message')

# EU Cybersecurity Index Survey - Survey Approved

Dear <strong>{{ $maildata['name'] }}</strong>,

The MS <strong>{{ $maildata['questionnaire'] }}</strong> for <strong>{{ $maildata['country'] }}</strong> has been approved by ENISA.

With best regards,<br/>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent


