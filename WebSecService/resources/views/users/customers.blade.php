@extends('layouts.master')
@section('title', 'Customers')
@section('content')
<div class="row mt-2">
    <div class="col col-10">
        <h1>Customers</h1>
    </div>
</div>

<form method="get">
    <div class="row">
        <div class="col col-sm-4">
            <input name="keywords" type="text" class="form-control" placeholder="Search by name" value="{{ request()->keywords }}" />
        </div>
        <div class="col col-sm-2">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
        <div class="col col-sm-2">
            <a href="{{ route('customers') }}" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="table-responsive mt-4">
    <table class="table table-striped">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Credit</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
            <tr>
                <td>{{ $customer->id }}</td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email }}</td>
                <td>${{ number_format($customer->credit ?? 0, 2) }}</td>
                <td>
                    <a href="{{ route('profile', $customer->id) }}" class="btn btn-sm btn-info">View Profile</a>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCreditModal-{{ $customer->id }}">
                        Add Credit
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@foreach($customers as $customer)
<!-- Add Credit Modal for each customer -->
<div class="modal fade" id="addCreditModal-{{ $customer->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('add_credit', $customer->id) }}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Credit to {{ $customer->name }}'s Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="amount-{{ $customer->id }}" class="form-label">Amount to Add ($)</label>
                        <input type="number" class="form-control" id="amount-{{ $customer->id }}" name="amount" step="0.01" min="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Credit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection 