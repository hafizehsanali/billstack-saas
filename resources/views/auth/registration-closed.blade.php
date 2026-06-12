<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Unavailable | {{ $settings->platform_name }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
<main class="min-vh-100 d-flex align-items-center">
    <div class="container py-5">
        <div class="mx-auto text-center" style="max-width: 620px;">
            <h1 class="mb-3">New registrations are temporarily unavailable</h1>
            <p class="text-muted fs-3 mb-4">
                Existing businesses can continue signing in. Contact support if you need help creating a new account.
            </p>

            @if($settings->support_email || $settings->support_phone)
                <div class="mb-4">
                    @if($settings->support_email)
                        <div>{{ $settings->support_email }}</div>
                    @endif
                    @if($settings->support_phone)
                        <div>{{ $settings->support_phone }}</div>
                    @endif
                </div>
            @endif

            <a href="{{ route('login') }}" class="btn btn-primary">Sign In</a>
            <a href="{{ url('/') }}" class="btn btn-outline-secondary ms-2">Home</a>
        </div>
    </div>
</main>
</body>
</html>
