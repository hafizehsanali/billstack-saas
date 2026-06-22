{{ platform_name() }} mail delivery is configured correctly.

Application: {{ config('app.name') }}
URL: {{ config('app.url') }}
Sent at: {{ now()->toDateTimeString() }}

{{ platform_company_name() }}
{{ platform_primary_email() }}
https://{{ platform_domain() }}
