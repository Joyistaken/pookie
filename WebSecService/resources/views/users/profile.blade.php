@extends('layouts.master')
@section('title', 'User Profile')
@section('content')
<div class="row">
    <div class="m-4 col-sm-6">
        <table class="table table-striped">
            <tr>
                <th>Name</th><td>{{$user->name}}</td>
            </tr>
            <tr>
                <th>Email</th><td>{{$user->email}}</td>
            </tr>
            <tr>
                <th>Credit</th><td>${{number_format($user->credit ?? 0, 2)}}</td>
            </tr>
            <tr>
                <th>Roles</th>
                <td>
                    @foreach($user->roles as $role)
                        <span class="badge bg-primary">{{$role->name}}</span>
                    @endforeach
                </td>
            </tr>
            <tr>
                <th>Permissions</th>
                <td>
                    @foreach($permissions as $permission)
                        <span class="badge bg-success">{{$permission->display_name}}</span>
                    @endforeach
                </td>
            </tr>
        </table>

        <div class="row">
            <div class="col col-6">
                @if(auth()->user()->hasPermissionTo('manage_customer_credit') && $user->hasRole('Customer'))
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addCreditModal">
                        Add Credit
                    </button>
                @endif
            </div>
            @if(auth()->user()->hasPermissionTo('admin_users')||auth()->id()==$user->id)
            <div class="col col-4">
                <a class="btn btn-primary" href='{{route('edit_password', $user->id)}}'>Change Password</a>
            </div>
            @else
            <div class="col col-4">
            </div>
            @endif
            @if(auth()->user()->hasPermissionTo('edit_users')||auth()->id()==$user->id)
            <div class="col col-2">
                <a href="{{route('users_edit', $user->id)}}" class="btn btn-success form-control">Edit</a>
            </div>
            @endif
        </div>
    </div>
</div>

@if($user->hasRole('Customer'))
<div class="row mt-4">
    <div class="col-12">
        <h3>Purchase History</h3>
        @if(isset($purchasedProducts) && count($purchasedProducts) > 0)
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price Paid</th>
                        <th>Purchase Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchasedProducts as $purchase)
                    <tr>
                        <td>{{$purchase->product->name}}</td>
                        <td>${{number_format($purchase->price_paid, 2)}}</td>
                        <td>{{$purchase->created_at->format('M d, Y')}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="alert alert-info">No purchases yet.</div>
        @endif
    </div>
</div>
@endif

<!-- Add Credit Modal -->
@if(auth()->user()->hasPermissionTo('manage_customer_credit') && $user->hasRole('Customer'))
<div class="modal fade" id="addCreditModal" tabindex="-1" aria-labelledby="addCreditModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('add_credit', $user->id) }}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addCreditModalLabel">Add Credit to {{$user->name}}'s Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount to Add ($)</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" required>
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
@endif
@endsection
