@extends('layouts.admin')

@section('title', 'Users Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Users Management</h2>
</div>

<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" class="form-control" name="search" placeholder="Search by name, email, or username" value="{{ request('search') }}">
        <button class="btn btn-outline-secondary" type="submit">Search</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->username }}</td>
                <td>
                    <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : 'secondary' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                <td>
                    <span class="badge bg-{{ $user->status === 'active' ? 'success' : 'warning' }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </td>
               {{-- <td>{{ $user->created_at->format('M d, Y H:i') }}</td> --}}
                <td>
                    <form method="POST" action="{{ route('admin.impersonate', $user) }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">Impersonate</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{ $users->appends(request()->query())->links() }}
@endsection