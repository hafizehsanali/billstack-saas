<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BillStack</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon-64.png') }}">

      @vite(['resources/css/app.css', 'resources/js/app.js'])
      <style>
        @media print {
                .sidebar,
                .navbar,
                .btn,
                form {
                    display: none !important;
                }

                .card {
                    border: none !important;
                }

            }
        </style>
</head>

<body>

<div class="page">

    @include('layouts.sidebar')

    <div class="page-wrapper">

        @include('layouts.navbar')

        <div class="page-body">
            <div class="container-xl py-4">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning">
                        {{ session('warning') }}
                    </div>
                @endif
                @if ($errors->any())

                    <div class="alert alert-danger">

                        <ul class="mb-0">

                            @foreach ($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif
                @yield('content')

            </div>
        </div>

    </div>

</div>

@yield('scripts')

</body>
</html>
