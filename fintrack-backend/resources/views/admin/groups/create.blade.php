@extends('layouts.admin')

@section('title', 'Create Group')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Create New Group</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.groups.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Group Type</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="family">Family</option>
                            <option value="friends">Friends</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="budget_limit" class="form-label">Budget Limit (Optional)</label>
                        <input type="number" class="form-control" id="budget_limit" name="budget_limit" step="0.01">
                    </div>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                    <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection