<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>@yield('title','Painel RH') · Point Carreiras</title><link rel="stylesheet" href="{{ asset('css/recruitment.css') }}"></head>
<body>
<a class="skip-link" href="#conteudo-principal">Pular para o conteúdo</a>
@include('components.loader')
<div class="admin-shell"><aside class="admin-sidebar"><div class="admin-logo"><span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span><small>Painel de recrutamento</small></div><nav class="admin-menu">
<a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard')?'active':'' }}"><span class="icon">⌂</span><span class="label">Visão geral</span></a>
<a href="{{ route('admin.vacancies.index') }}" class="{{ request()->routeIs('admin.vacancies.*')?'active':'' }}"><span class="icon">▣</span><span class="label">Vagas</span></a>
<a href="{{ route('admin.applications.index') }}" class="{{ request()->routeIs('admin.applications.*')?'active':'' }}"><span class="icon">▤</span><span class="label">Candidaturas</span></a>
<a href="{{ route('admin.candidates.index') }}" class="{{ request()->routeIs('admin.candidates.*')?'active':'' }}"><span class="icon">◎</span><span class="label">Candidatos</span></a>
<a href="{{ route('admin.talent-pool.index') }}" class="{{ request()->routeIs('admin.talent-pool.*')?'active':'' }}"><span class="icon">★</span><span class="label">Banco de Talentos</span></a>
@php($adminUnreadMessages = \App\Models\CandidateMessage::where('sender_role','candidate')->whereNull('read_at')->count())
<a href="{{ route('admin.messages.index') }}" class="{{ request()->routeIs('admin.messages.*')?'active':'' }}"><span class="icon">✉</span><span class="label">Mensagens</span>@if($adminUnreadMessages)<span class="menu-count">{{ min($adminUnreadMessages,99) }}</span>@endif</a>
<a href="{{ route('admin.interviews.index') }}" class="{{ request()->routeIs('admin.interviews.*')?'active':'' }}"><span class="icon">◷</span><span class="label">Entrevistas</span></a>
<a href="{{ route('admin.analyses.index') }}" class="{{ request()->routeIs('admin.analyses.*')?'active':'' }}"><span class="icon">✦</span><span class="label">Análises assistidas</span></a>
<a href="{{ route('admin.analytics.index') }}" class="{{ request()->routeIs('admin.analytics.*')?'active':'' }}"><span class="icon">↗</span><span class="label">Indicadores</span></a>
<a href="{{ route('admin.message-templates.index') }}" class="{{ request()->routeIs('admin.message-templates.*')?'active':'' }}"><span class="icon">✎</span><span class="label">Modelos de mensagem</span></a>
<a href="{{ route('admin.activity-logs.index') }}" class="{{ request()->routeIs('admin.activity-logs.*')?'active':'' }}"><span class="icon">↺</span><span class="label">Atividades</span></a>
</nav><div class="admin-sidebar-footer"><div class="admin-user">{{ auth()->user()->name }}<br>{{ auth()->user()->email }}</div><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="btn btn-danger btn-sm" type="submit" style="width:100%">Sair</button></form></div></aside><div class="admin-main"><header class="admin-topbar"><strong>@yield('topbar','Painel de Recrutamento')</strong><a href="{{ route('vacancies.index') }}" target="_blank" class="btn btn-light btn-sm">Ver site público ↗</a></header><main id="conteudo-principal" class="admin-content" tabindex="-1">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="alert alert-danger"><strong>Verifique:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif @yield('content')</main></div></div></body>
</html>
