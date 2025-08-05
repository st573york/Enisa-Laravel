@component('mail::message')

# Invitation to join the EUCSI Platform

Dear <strong>{{ $maildata['name'] }}</strong>,

You have been invited by <strong>{{ $maildata['author'] }}</strong> to join the EU Cybersecurity Index (EU-CSI) platform.

You can register on the platform via the following link. Please note that the link will be valid for <strong>{{ $maildata['deadline'] }} hours</strong>.

@component('mail::button', ['url' => $maildata['url']])
Join EUCSI link
@endcomponent

Please note that you will need an <strong>EU Login account</strong> to access the EU-CSI platform. If you don't have one, you can create an account via the above link.

With best regards,<br>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent