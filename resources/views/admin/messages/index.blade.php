@extends('layouts.admin')
@section('title','Mensagens')
@section('topbar','Mensagens')
@section('content')
<div class="page-head"><div><span class="eyebrow">Contato com candidatos</span><h1>Mensagens</h1><p>Centralize conversas entre recrutamento e candidatos.</p></div></div>
<form class="filters" method="GET"><div class="field"><label>Buscar candidato</label><input name="q" value="{{ request('q') }}" placeholder="Nome ou e-mail"></div><button class="btn btn-dark">Buscar</button></form>
<section class="panel"><div class="panel-head"><h2>Conversas</h2><span class="subtle">{{ $candidates->total() }} candidato(s)</span></div><div class="conversation-list">@forelse($candidates as $candidate)<a class="conversation-row" href="{{ route('admin.messages.show',$candidate) }}"><div class="avatar-mini">{{ mb_strtoupper(mb_substr($candidate->name,0,1)) }}</div><div class="conversation-copy"><strong>{{ $candidate->name }}</strong><span>{{ $candidate->messages->first()?->message ? \Illuminate\Support\Str::limit($candidate->messages->first()->message,100) : 'Conversa sem prévia' }}</span></div>@if($candidate->unread_messages_count)<span class="notification-dot">{{ min($candidate->unread_messages_count,9) }}</span>@endif</a>@empty<div class="empty-state"><h3>Nenhuma conversa ainda.</h3></div>@endforelse</div>@if($candidates->hasPages())<div class="pagination">{{ $candidates->links() }}</div>@endif</section>
@endsection
