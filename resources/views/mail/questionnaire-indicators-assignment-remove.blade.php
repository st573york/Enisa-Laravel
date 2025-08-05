@component('mail::message')

# EU Cybersecurity Index Survey - Indicator Delegation

The following MS <strong>{{ $maildata['questionnaire'] }}</strong> indicators for <strong>{{ $maildata['country'] }}</strong> have been delegated to another user by <strong>{{ $maildata['author'] }}</strong>.

Indicators:
<ul style="list-style: none;">
@foreach ($maildata['indicators'] as $indicator)
<li>{!! $indicator !!}</li>
@endforeach
</ul>
With best regards,<br/>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent


