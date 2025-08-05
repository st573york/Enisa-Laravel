@component('mail::message')

# {{ $maildata['title'] }}

The {{ $maildata['task'] }} <strong>{!! $maildata['status'] !!}</strong>.

@isset($maildata['message'])
{{ $maildata['message'] }}
@endisset

Thanks,<br>
{{ config('app.name') }}

@endcomponent