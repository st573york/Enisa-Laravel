@component('mail::message')

# EU Cybersecurity Index Survey - Indicator Assignment

You have been assigned the following indicators for the EU <strong>Cybersecurity Index {{ $maildata['questionnaire'] }}</strong>
by <strong>{{ $maildata['author'] }}</strong>. The deadline for your input is <strong>{{ $maildata['deadline'] }}</strong>.

Indicators:

<ul style="list-style: none;">
@foreach ($maildata['indicators'] as $indicator)
<li>{!! $indicator !!}</li>
@endforeach
</ul>
Please use the following button to fill in the information.

@component('mail::button', ['url' => $maildata['url']])
MS Survey
@endcomponent

With best regards,<br/>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent





