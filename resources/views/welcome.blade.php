<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BillStack ERP</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-light">

<div class="container py-5">

    <div class="text-center">

        <h1 class="display-4 fw-bold mb-3">
            BillStack ERP
        </h1>

        <p class="lead text-muted mb-4">
            Inventory, Sales, Purchases & Accounting Management System
        </p>

        @auth

            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                Go To Dashboard
            </a>

        @else

            <div class="d-flex justify-content-center gap-3">

                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                    Login
                </a>

                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg">
                        Register
                    </a>
                @endif

            </div>

        @endauth

    </div>

</div>

</body>
</html>