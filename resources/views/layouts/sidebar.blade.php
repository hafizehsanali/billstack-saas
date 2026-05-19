<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">

        <!-- Brand -->
        <h1 class="navbar-brand">
            <a href="{{ route('dashboard') }}"
               class="text-white text-decoration-none">
                BillStack
            </a>
        </h1>

        <!-- Sidebar Menu -->
        <div class="navbar-collapse">

            <ul class="navbar-nav pt-lg-3">

                <li class="nav-item">
                    <a class="nav-link text-white"
                       href="{{ route('dashboard') }}">
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white"
                       href="{{ route('categories.index') }}">
                        Categories
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white"
                       href="{{ route('products.index') }}">
                        Products
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white"
                       href="{{ route('customers.index') }}">
                        Customers
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white"
                       href="#">
                        Invoices
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white"
                       href="#">
                        Reports
                    </a>
                </li>

            </ul>

        </div>

    </div>
</aside>