@component('mail::message')

# EU Cybersecurity Index Survey - Request for revision

<strong>{{ $maildata['author'] }}</strong> is kindly requesting the review and, if necessary, update to the following indicators of the EU Cybersecurity Index 
<strong>{{ $maildata['questionnaire'] }}</strong>. 
Please review your input and resubmit the survey by <strong>{{ $maildata['deadline'] }}</strong>.

Indicators:

<ul style="list-style: none;">
@foreach ($maildata['indicators'] as $indicator)
<li>{!! $indicator !!}</li>
@endforeach
</ul>
Please use the following link to fill in the information.

@component('mail::button', ['url' => $maildata['url']])
MS Survey
@endcomponent

With best regards,<br/>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent
