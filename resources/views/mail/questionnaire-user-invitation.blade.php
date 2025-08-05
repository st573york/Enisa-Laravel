@component('mail::message')

# EU Cybersecurity Index Survey - Invitation

ENISA has developed the EU European Cybersecurity Index (EU CSI) to provide countries with valuable insights into their cybersecurity maturity and posture. The EU CSI is meant to serve as a resource for informed decision-making by offering a clear assessment of the Union's overall cybersecurity capabilities, as well as those of individual countries.

To ensure the accuracy and relevance of the index, we are seeking your valuable input through an online survey. Your participation will play a vital role in helping collect data for key indicators that would reflect the state of cybersecurity in the EU.

Please click the following button to access the survey.

@component('mail::button', ['url' => $maildata['url']])
Survey Link
@endcomponent

Thank you in advance for your input.<br/>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent