<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark bg-dark">
<div class="container-fluid">

<h1 class="navbar-brand">
<a href="{{ route('dashboard') }}" class="text-white text-decoration-none">BillStack</a>
</h1>

<div class="navbar-collapse">
<ul class="navbar-nav pt-lg-3">

{{-- Dashboard --}}
<li class="nav-item">
<a class="nav-link text-white {{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">Dashboard</a>
</li>

{{-- Inventory --}}
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle {{ request()->routeIs('categories.*','products.*')?'show':'' }}" href="#inv" data-bs-toggle="dropdown">
<span class="nav-link-title">Inventory</span>
</a>
<div class="dropdown-menu {{ request()->routeIs('categories.*','products.*')?'show':'' }}">
<a class="dropdown-item {{ request()->routeIs('categories.*')?'active':'' }}" href="{{ route('categories.index') }}">Categories</a>
<a class="dropdown-item {{ request()->routeIs('products.*')?'active':'' }}" href="{{ route('products.index') }}">Products</a>
</div>
</li>

{{-- Sales --}}
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle {{ request()->routeIs('customers.*','invoices.*')?'show':'' }}" href="#sales" data-bs-toggle="dropdown">
<span class="nav-link-title">Sales</span>
</a>
<div class="dropdown-menu {{ request()->routeIs('customers.*','invoices.*')?'show':'' }}">
<a class="dropdown-item {{ request()->routeIs('customers.*')?'active':'' }}" href="{{ route('customers.index') }}">Customers</a>
<a class="dropdown-item {{ request()->routeIs('invoices.*')?'active':'' }}" href="{{ route('invoices.index') }}">Invoices</a>
</div>
</li>

{{-- Suppliers --}}
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle {{ request()->routeIs('suppliers.*')?'show':'' }}" href="#suppliers" data-bs-toggle="dropdown">
<span class="nav-link-title">Suppliers</span>
</a>
<div class="dropdown-menu {{ request()->routeIs('suppliers.*')?'show':'' }}">
<a class="dropdown-item {{ request()->routeIs('suppliers.index')?'active':'' }}" href="{{ route('suppliers.index') }}">Supplier List</a>
<a class="dropdown-item {{ request()->routeIs('suppliers.create')?'active':'' }}" href="{{ route('suppliers.create') }}">Add Supplier</a>
</div>
</li>

{{-- Purchases --}}
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle {{ request()->routeIs('purchases.*')?'show':'' }}" href="#purchases" data-bs-toggle="dropdown">
<span class="nav-link-title">Purchases</span>
</a>
<div class="dropdown-menu {{ request()->routeIs('purchases.*')?'show':'' }}">
<a class="dropdown-item {{ request()->routeIs('purchases.index')?'active':'' }}" href="{{ route('purchases.index') }}">Purchase List</a>
<a class="dropdown-item {{ request()->routeIs('purchases.create')?'active':'' }}" href="{{ route('purchases.create') }}">Add Purchase</a>
</div>
</li>

{{-- Payments --}}
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle {{ request()->routeIs('supplier-payments.*')?'show':'' }}" href="#payments" data-bs-toggle="dropdown">
<span class="nav-link-title">Supplier Payments</span>
</a>
<div class="dropdown-menu {{ request()->routeIs('supplier-payments.*')?'show':'' }}">
<a class="dropdown-item {{ request()->routeIs('supplier-payments.index')?'active':'' }}" href="{{ route('supplier-payments.index', ['supplier' => request()->route('supplier') ?? 1]) }}">Payment List</a>
</div>
</li>

{{-- Expenses --}}
<li class="nav-item">
<a class="nav-link text-white {{ request()->routeIs('expenses.*')?'active':'' }}" href="{{ route('expenses.index') }}">Expenses</a>
</li>

{{-- Reports --}}
@role('owner')
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle {{ request()->routeIs('reports.*')?'show':'' }}" href="#reports" data-bs-toggle="dropdown">
<span class="nav-link-title">Reports</span>
</a>
<div class="dropdown-menu {{ request()->routeIs('reports.*')?'show':'' }}">
<a class="dropdown-item {{ request()->routeIs('reports.daily-sales')?'active':'' }}" href="{{ route('reports.daily-sales') }}">Daily Sales</a>
<a class="dropdown-item {{ request()->routeIs('reports.monthly-sales')?'active':'' }}" href="{{ route('reports.monthly-sales') }}">Monthly Sales</a>
<a class="dropdown-item {{ request()->routeIs('reports.stock')?'active':'' }}" href="{{ route('reports.stock') }}">Stock Report</a>
<a class="dropdown-item {{ request()->routeIs('reports.low-stock')?'active':'' }}" href="{{ route('reports.low-stock') }}">Low Stock</a>
<a class="dropdown-item {{ request()->routeIs('reports.profit-loss')?'active':'' }}" href="{{ route('reports.profit-loss') }}">Profit & Loss</a>
</div>
</li>
@endrole

</ul>
</div>
</div>
</aside>