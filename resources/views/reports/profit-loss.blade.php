@extends('layouts.app')
@section('content')
<div class="container mt-4">

    <h2 class="mb-4">
        Profit & Loss Report
    </h2>

    <div class="row">

        <div class="col-md-4">

            <div class="card shadow-sm mb-3">

                <div class="card-body">

                    <h5>Total Sales</h5>

                    <h2>
                        {{ number_format($sales, 2) }}
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm mb-3">

                <div class="card-body">

                    <h5>Total Expenses</h5>

                    <h2>
                        {{ number_format($expenses, 2) }}
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow-sm mb-3">

                <div class="card-body">

                    <h5>Net Profit</h5>

                    <h2>
                        {{ number_format($profit, 2) }}
                    </h2>

                </div>

            </div>

        </div>

    </div>

    <hr class="my-4">

    <h4>
        This Month Overview
    </h4>

    <table class="table table-bordered">

        <tr>

            <th>
                Monthly Sales
            </th>

            <td>
                {{ number_format($monthlySales, 2) }}
            </td>

        </tr>

        <tr>

            <th>
                Monthly Expenses
            </th>

            <td>
                {{ number_format($monthlyExpenses, 2) }}
            </td>

        </tr>

        <tr>

            <th>
                Monthly Profit
            </th>

            <td>
                {{ number_format($monthlyProfit, 2) }}
            </td>

        </tr>

    </table>

</div>
@endsection