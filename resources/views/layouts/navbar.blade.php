<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl">

        <div class="navbar-nav flex-row ms-auto">

            <!-- User Name -->
            <div class="nav-item me-3 d-flex align-items-center">
                <span>
                    {{ auth()->user()->name }}
                </span>
            </div>

            <!-- Logout -->
            <div class="nav-item">

                <form method="POST"
                      action="{{ route('logout') }}">

                    @csrf

                    <button type="submit"
                            class="btn btn-danger btn-sm">
                        Logout
                    </button>

                </form>

            </div>

        </div>

    </div>
</header>