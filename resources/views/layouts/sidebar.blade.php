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
                       href="{{ route('invoices.index') }}">
                        Invoices
                    </a>
                </li>

                @role('owner')
                <li class="nav-item dropdown">

    <a
        class="nav-link dropdown-toggle

        {{ request()->routeIs('reports.*')
            ? 'show'
            : ''
        }}"

        href="#navbar-reports"

        data-bs-toggle="dropdown"

        data-bs-auto-close="outside"

        role="button"

        aria-expanded="{{ request()->routeIs('reports.*')
            ? 'true'
            : 'false'
        }}"
    >

        <span class="nav-link-title">
            Reports
        </span>

    </a>

    <div
        class="dropdown-menu

        {{ request()->routeIs('reports.*')
            ? 'show'
            : ''
        }}"
    >

        <a
            class="dropdown-item

            {{ request()->routeIs('reports.daily-sales')
                ? 'active'
                : ''
            }}"

            href="{{ route('reports.daily-sales') }}"
        >

            Daily Sales

        </a>

        <a
            class="dropdown-item

            {{ request()->routeIs('reports.monthly-sales')
                ? 'active'
                : ''
            }}"

            href="{{ route('reports.monthly-sales') }}"
        >

            Monthly Sales

        </a>

        <a
            class="dropdown-item

            {{ request()->routeIs('reports.stock')
                ? 'active'
                : ''
            }}"

            href="{{ route('reports.stock') }}"
        >

            Stock Report

        </a>

        <a
            class="dropdown-item

            {{ request()->routeIs('reports.low-stock')
                ? 'active'
                : ''
            }}"

            href="{{ route('reports.low-stock') }}"
        >

            Low Stock

        </a>

    </div>

</li>
                @endrole
            </ul>

        </div>

    </div>
</aside>