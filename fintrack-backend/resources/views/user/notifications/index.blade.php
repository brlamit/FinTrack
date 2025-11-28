@extends('layouts.user')

@section('title', 'Notifications')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h3 class="mb-3">Notifications</h3>

            <div class="list-group">
                @forelse($notifications as $n)
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $n->title }}</div>
                            <div class="small text-muted">{{ $n->message }}</div>
                            <div class="small text-muted mt-1">{{ $n->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="text-end">
                            @if(!$n->is_read)
                                <form method="POST" action="{{ route('user.notifications.mark-read', ['notification' => $n->id]) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-primary">Mark read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted">No notifications</div>
                @endforelse
            </div>

            <div class="mt-3">{{ $notifications->links() }}</div>
        </div>
    </div>
</div>
@endsection
