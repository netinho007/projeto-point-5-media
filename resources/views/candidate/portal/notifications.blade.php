@extends('layouts.candidate')

@section('title', 'Notificações')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Atualizações</span><h1>Notificações</h1><p>Mudanças importantes nas suas candidaturas aparecem aqui.</p></div>
    <form method="POST" action="{{ route('candidate.notifications.read-all') }}">@csrf @method('PATCH')<button class="btn btn-light" type="submit">Marcar todas como lidas</button></form>
</div>

<section class="portal-card">
    <div class="portal-card-body">
        @forelse($notifications as $notification)
            <form method="POST" action="{{ route('candidate.notifications.read', $notification) }}" class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
                @csrf @method('PATCH')
                <button type="submit" style="width:100%;text-align:left;border:0;background:transparent;padding:0;cursor:pointer">
                    <strong>{{ $notification->title }}</strong>
                    <p>{{ $notification->message }}</p>
                    <small class="muted">{{ $notification->created_at->format('d/m/Y H:i') }}</small>
                </button>
            </form>
        @empty
            <div class="empty-state" style="border:0"><p>Você ainda não recebeu notificações.</p></div>
        @endforelse
    </div>
    @if($notifications->hasPages())<div class="pagination">{{ $notifications->links() }}</div>@endif
</section>
@endsection
