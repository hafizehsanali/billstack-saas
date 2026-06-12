{{ platform_name() }} mail delivery is configured correctly.

Application: {{ config('app.name') }}
URL: {{ config('app.url') }}
Sent at: {{ now()->toDateTimeString() }}
