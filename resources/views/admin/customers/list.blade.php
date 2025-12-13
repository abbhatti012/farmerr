@extends('admin.layout.app')
@section('content')
<div class="container">
    <h1>Customer List</h1>

    <form method="GET" action="{{ route('admin.customers.list') }}" class="mb-3">
        <input type="text" name="search" placeholder="Search customers..." value="{{ request('search') }}" class="form-control">
        <button type="submit" class="btn btn-primary mt-2">Search</button>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Total Orders</th>
                <th>Total Order Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $customer)
                <tr>
                    <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
                    <td>{{ $customer->first_name }} {{ $customer->last_name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->total_orders }}</td>
                    <td>{{ number_format($customer->total_order_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination Links -->
    <div class="d-flex justify-content-center">
        {{ $customers->links() }}
    </div>
</div>
@endsection
