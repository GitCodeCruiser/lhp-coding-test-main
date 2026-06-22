<x-mail::message>
# You're in, {{ $attendeeName }}!

Thanks for registering for **{{ $eventName }}**. Here are the details:

- **When:** {{ $when }}
- **Where:** {{ $venue ? $venue.' ('.$location.')' : $location }}

We'll send you a reminder **3 days** before and again **24 hours** before it starts.

<x-mail::button :url="$url">
View event
</x-mail::button>

See you there,<br>
{{ config('app.name') }}
</x-mail::message>
