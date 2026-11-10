@extends('layouts.candidate')
@section('title', 'Meu Portal')
@section('content')
<div class="portal-welcome">
    <div><span class="eyebrow">Meu portal</span><h1>Olá, {{ Str::before($candidate->name, ' ') }}.</h1><p>Acompanhe seu perfil, candidaturas, mensagens e entrevistas.</p></div>
    <a class="btn btn-primary" href="{{ route('vacancies.index') }}#vagas">Ver novas vagas</a>
</div>

@if(! auth()->user()->hasVerifiedEmail() || ! $candidate->phone_verified_at)
<div class="verification-strip">
    <div><strong>Segurança da conta</strong><span>{{ auth()->user()->hasVerifiedEmail() ? '✓ E-mail verificado' : '• E-mail pendente' }} &nbsp; {{ $candidate->phone_verified_at ? '✓ Telefone verificado' : '• Telefone pendente' }}</span></div>
    <a class="btn btn-light btn-sm" href="{{ route('candidate.security') }}">Verificar agora</a>
</div>
@endif

<div class="metric-grid candidate-metrics">
    <div class="metric-card"><span>Perfil completo</span><strong>{{ $candidate->profile_completion }}%</strong><small>Quanto mais completo, melhor para o RH.</small></div>
    <div class="metric-card"><span>Candidaturas</span><strong>{{ $candidate->applications->count() }}</strong><small>Processos vinculados à sua conta.</small></div>
    <div class="metric-card"><span>Vagas salvas</span><strong>{{ $candidate->savedVacancies()->count() }}</strong><small>Oportunidades para revisar depois.</small></div>
    <div class="metric-card"><span>Mensagens novas</span><strong>{{ $candidate->messages()->where('sender_role','admin')->whereNull('read_at')->count() }}</strong><small>Contato direto com recrutamento.</small></div>
</div>

<div class="portal-grid">
<div class="stack">
<section class="portal-card"><div class="portal-card-head"><h2>Minhas candidaturas</h2><span class="badge badge-brand">{{ $candidate->applications->count() }} processo(s)</span></div>
@forelse($candidate->applications as $application)
<article class="application-card"><div class="application-card-top"><div><h3>{{ $application->vacancy?->title ?? 'Vaga indisponível' }}</h3><div class="application-meta">{{ $application->vacancy?->department ?: 'Área não informada' }} · Enviada em {{ $application->created_at->format('d/m/Y') }}</div></div><span class="badge {{ $application->status === 'approved' ? 'badge-green' : (in_array($application->status,['archived','not_selected','withdrawn']) ? 'badge-gray' : 'badge-brand') }}">{{ $application->status_label }}</span></div><div class="progress-row"><div class="progress-track"><span style="width:{{ $application->progress_percent }}%"></span></div><small class="muted">{{ $application->progress_percent }}%</small></div><div class="form-actions"><a class="text-link" href="{{ route('candidate.applications.show', $application) }}">Acompanhar processo →</a>@if($application->interview && $application->interview->scheduled_at?->isFuture())<span class="badge badge-yellow">Entrevista {{ $application->interview->scheduled_at->format('d/m H:i') }}</span>@endif</div></article>
@empty<div class="empty-state"><h3>Você ainda não tem candidaturas.</h3><p>Explore as oportunidades e encontre uma que combine com seu momento.</p><a class="btn btn-primary" href="{{ route('vacancies.index') }}#vagas">Ver vagas</a></div>@endforelse
</section>
</div>
<aside class="stack">
@if($upcomingInterview)<section class="interview-banner"><span class="eyebrow">Próxima entrevista</span><strong>{{ $upcomingInterview->scheduled_at->format('d/m/Y · H:i') }}</strong><small>{{ ['online'=>'Videoconferência','presencial'=>'Presencial','telefone'=>'Telefone'][$upcomingInterview->format] ?? ucfirst($upcomingInterview->format) }} · {{ $upcomingInterview->application?->vacancy?->title }}</small><div style="margin-top:14px"><a class="btn btn-primary btn-sm" href="{{ route('candidate.applications.show', $upcomingInterview->application) }}">Ver detalhes</a></div></section>@endif
<section class="portal-card"><div class="portal-card-head"><h2>Completude do perfil</h2><span class="badge badge-brand">{{ $candidate->profile_completion }}%</span></div><div class="portal-card-body"><div class="profile-progress"><span style="width:{{ $candidate->profile_completion }}%"></span></div><div class="profile-checklist">@foreach($profileChecklist as $label => $done)<div class="profile-check-item {{ $done ? 'done' : '' }}"><span>{{ $done ? '✓' : '○' }}</span><span>{{ $label }}</span></div>@endforeach</div><a class="btn btn-light btn-sm" href="{{ route('candidate.profile') }}">Completar perfil</a></div></section>
<section class="portal-card"><div class="portal-card-head"><h2>Notificações</h2>@if($unreadNotifications)<span class="badge badge-brand">{{ $unreadNotifications }}</span>@endif</div><div class="portal-card-body">@forelse($notifications as $notification)<a class="notification-item {{ $notification->read_at ? '' : 'unread' }}" href="{{ route('candidate.notifications') }}"><strong>{{ $notification->title }}</strong><p>{{ Str::limit($notification->message,105) }}</p></a>@empty<p class="muted">Nenhuma notificação por enquanto.</p>@endforelse@if($notifications->isNotEmpty())<a class="text-link" href="{{ route('candidate.notifications') }}">Ver todas →</a>@endif</div></section>
</aside>
</div>
@endsection
