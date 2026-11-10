@extends('layouts.admin')

@section('title', 'Visão geral')
@section('topbar', 'Visão geral')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Point Carreiras</span><h1>Visão geral</h1><p>Uma visão rápida do movimento das vagas e dos candidatos.</p></div>
    <a href="{{ route('admin.vacancies.create') }}" class="btn btn-primary">+ Nova vaga</a>
</div>

<section class="stats">
    <div class="stat-card"><span>Vagas abertas</span><strong>{{ $openVacancies }}</strong></div>
    <div class="stat-card"><span>Candidatos</span><strong>{{ $totalCandidates }}</strong></div>
    <div class="stat-card"><span>Candidaturas</span><strong>{{ $totalApplications }}</strong></div>
    <div class="stat-card"><span>Aguardando análise</span><strong>{{ $waitingAnalysis }}</strong></div>
    <div class="stat-card highlight"><span>Banco de Talentos</span><strong>{{ $talentPoolCount }}</strong></div>
    <div class="stat-card"><span>Mensagens novas</span><strong>{{ $unreadMessages }}</strong><a class="text-link" href="{{ route('admin.messages.index') }}">Abrir central →</a></div>
    <div class="stat-card"><span>Entrevistas próximas</span><strong>{{ $upcomingInterviewCount }}</strong><small>Próximos 14 dias</small></div>
    <div class="stat-card"><span>Aprovados no mês</span><strong>{{ $monthlyApproved }}</strong><small>Processos concluídos com aprovação</small></div>
</section>

<section class="panel dashboard-analytics-teaser" style="margin-bottom:18px">
    <div class="panel-head">
        <div><h2>Movimento do site · este mês</h2><p class="subtle">Acompanhe audiência e envio de currículos com filtro mensal.</p></div>
        <a class="btn btn-light btn-sm" href="{{ route('admin.analytics.index') }}">Abrir gráficos →</a>
    </div>
    <div class="dashboard-analytics-row">
        <div><span>Visitantes únicos estimados</span><strong>{{ number_format($monthlyVisitors, 0, ',', '.') }}</strong></div>
        <div><span>Currículos enviados</span><strong>{{ number_format($monthlyResumes, 0, ',', '.') }}</strong></div>
        <div><span>Conversão</span><strong>{{ $monthlyVisitors > 0 ? number_format(($monthlyResumes / $monthlyVisitors) * 100, 1, ',', '.') : '0,0' }}%</strong></div>
    </div>
</section>

<section class="panel integration-status-panel" style="margin-bottom:18px">
    <div class="panel-head">
        <div><h2>Comunicação do sistema</h2><p class="subtle">Estado das integrações sem exibir senhas ou chaves.</p></div>
        <a class="btn btn-light btn-sm" href="{{ route('admin.messages.index') }}">Abrir mensagens</a>
    </div>
    <div class="integration-status-grid">
        <div class="integration-status-item"><span>E-mail</span><strong>{{ $mailReady ? 'Ativo' : 'Modo de teste' }}</strong><small>{{ $mailReady ? 'Envio externo configurado' : 'Configure SMTP/Resend no .env para envio real' }}</small></div>
        <div class="integration-status-item"><span>Telefone</span><strong>{{ $smsReady ? 'SMS ativo' : 'Modo local' }}</strong><small>{{ $smsReady ? 'Twilio Verify configurado' : 'Código local disponível em desenvolvimento' }}</small></div>
        <div class="integration-status-item"><span>WhatsApp</span><strong>Acesso rápido</strong><small>Contato manual pelo número cadastrado; automação pode ser integrada depois.</small></div>
    </div>
</section>

<div class="detail-grid" style="margin-bottom:18px">
    <section class="panel">
        <div class="panel-head"><h2>Candidaturas recentes</h2><a class="btn btn-light btn-sm" href="{{ route('admin.applications.index') }}">Ver todas</a></div>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Candidato</th><th>Vaga</th><th>Situação</th><th>Análise interna</th><th></th></tr></thead>
                <tbody>
                @forelse($recentApplications as $application)
                    @php($statusClass = match($application->status) {'approved'=>'badge-green','archived'=>'badge-gray','shortlisted','interview'=>'badge-purple','reviewing'=>'badge-yellow',default=>'badge-brand'})
                    <tr>
                        <td><strong>{{ $application->candidate?->name }}</strong><div class="subtle">{{ $application->candidate?->email }}</div></td>
                        <td>{{ $application->vacancy?->title }}</td>
                        <td><span class="badge {{ $statusClass }}">{{ $application->status_label }}</span></td>
                        <td>
                            @if($application->analysis?->status === 'completed')<span class="badge badge-green">{{ number_format((float)$application->analysis->match_score,0) }}% textual</span>
                            @elseif($application->analysis?->status === 'failed')<span class="badge badge-red">Falhou</span>
                            @else<span class="badge badge-gray">Pendente</span>@endif
                        </td>
                        <td><a class="btn btn-light btn-sm" href="{{ route('admin.applications.show', $application) }}">Abrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px" class="muted">Nenhuma candidatura recebida.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="stack">
        <section class="panel">
            <div class="panel-head"><h2>Entrevistas · próximos 14 dias</h2></div>
            <div class="panel-body">
                @forelse($upcomingInterviews as $interview)
                    <div style="padding:12px 0;border-bottom:1px solid var(--border)">
                        <strong>{{ $interview->scheduled_at->format('d/m · H:i') }} · {{ $interview->application?->candidate?->name }}</strong>
                        <div class="subtle">{{ $interview->application?->vacancy?->title }} · {{ ['online'=>'Videoconferência','presencial'=>'Presencial','telefone'=>'Telefone'][$interview->format] ?? ucfirst($interview->format) }}</div>
                    </div>
                @empty
                    <p class="muted">Nenhuma entrevista agendada para os próximos dias.</p>
                @endforelse
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Processos por etapa</h2></div>
            <div class="panel-body stack">
                @foreach(\App\Models\Application::STATUSES as $status => $label)
                    <div style="display:flex;justify-content:space-between;gap:12px"><span class="muted">{{ $label }}</span><strong>{{ $statusCounts[$status] ?? 0 }}</strong></div>
                @endforeach
            </div>
        </section>
    </aside>
</div>
@endsection
