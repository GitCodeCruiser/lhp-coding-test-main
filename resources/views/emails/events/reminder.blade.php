<x-mail::message>
# See you in {{ $lead }}, {{ $attendeeName }}!

This is a friendly reminder that **{{ $eventName }}** is coming up in **{{ $lead }}**.

- **When:** {{ $when }}
- **Where:** {{ $venue ? $venue.' ('.$location.')' : $location }}

<x-mail::button :url="$url">
View event
</x-mail::button>

See you there,<br>
{{ config('app.name') }}
</x-mail::message>
