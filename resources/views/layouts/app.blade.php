<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BillStack</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

</body>
</html>