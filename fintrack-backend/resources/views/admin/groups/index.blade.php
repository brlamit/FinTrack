@extends('layouts.admin')

@section('title', 'Groups Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Groups Management</h2>
    <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">Create New Group</a>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Owner</th>
                <th>Members</th>
                <th>Budget Limit</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $group)
            <tr>
                <td>{{ $group->name }}</td>
                <td>{{ ucfirst($group->type) }}</td>
                <td>{{ $group->owner->name }}</td>
                <td>{{ $group->members->count() }}</td>
                <td>{{ $group->budget_limit ? '$' . number_format($group->budget_limit, 2) : 'N/A' }}</td>
                <td>
                    <a href="{{ route('admin.groups.show', $group) }}" class="btn btn-sm btn-info">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection