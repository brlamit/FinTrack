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
                                <form method="POST" action="{{ route('user.notifications.mark-read', ['notification' => $n->id]) }}" class="mark-read-form">
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
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.mark-read-form').forEach(function (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btn = form.querySelector('button');
                btn.disabled = true;
                const originalHtml = btn.innerHTML;
                // show spinner inside button
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                try {
                    const resp = await fetch(form.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    if (resp.ok) {
                        form.outerHTML = '<span class="badge bg-secondary">Read</span>';
                        showNotifToastLocal('Notification marked read', 'success');
                    } else {
                        showNotifToastLocal('Could not mark notification read', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                } catch (err) {
                    console.error('Mark-read error', err);
                    showNotifToastLocal('Error marking notification read', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            });
        });

        function showNotifToastLocal(message, variant = 'success') {
            const toastEl = document.getElementById('notif-toast');
            if (!toastEl) { alert(message); return; }
            toastEl.querySelector('.toast-body').textContent = message;
            toastEl.classList.remove('bg-success', 'bg-danger');
            toastEl.classList.add(variant === 'success' ? 'bg-success' : 'bg-danger');
            const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
            toast.show();
        }
    });
    </script>
    @endpush

</div>
@endsection
