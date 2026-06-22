<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ platform_name() }}</title>
    <link rel="icon" type="image/png" href="{{ platform_favicon_asset() }}">

      <script>
        try {
            if (localStorage.getItem('zephrant-erp.sidebar.collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            // The sidebar still works when browser storage is unavailable.
        }
      </script>
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

<body class="antialiased">

<div class="page">

    @include('layouts.sidebar')

    <div class="page-wrapper">

        @include('layouts.navbar')

        <div class="page-body">
            <div class="container-xl py-4 py-lg-4">
                @if(session('success'))
                    <div class="alert alert-success d-flex align-items-center gap-2">
                        <i data-lucide="circle-check"></i>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning d-flex align-items-center gap-2">
                        <i data-lucide="triangle-alert"></i>
                        {{ session('warning') }}
                    </div>
                @endif
                @if ($errors->any())

                    <div class="alert alert-danger d-flex gap-2">
                        <i data-lucide="circle-alert" class="mt-1"></i>

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
