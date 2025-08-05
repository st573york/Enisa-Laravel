@component('mail::message')

# User Permissions

User permissions for <strong>{{ $maildata['name'] }}, {{ $maildata['email'] }}</strong> have been updated on the EU Cybersecurity Index platform.
User is <strong style="color:green;">{!! $maildata['status'] !!}</strong> and has role <strong>{{ $maildata['role'] }}</strong> for <strong>{{ $maildata['country'] }}</strong>.

Changes realised by <strong>{{ $maildata['author_name'] }}</strong>, <strong>{{ $maildata['author_email'] }}</strong>.

@component('mail::button', ['url' => $maildata['url']])
User Management
@endcomponent

With best regards,<br/>
ENISA

<br/>
<p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>

@endcomponent

