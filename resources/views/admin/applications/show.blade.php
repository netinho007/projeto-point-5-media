@extends('layouts.admin')

@section('title', 'Candidatura de '.$application->candidate?->name)
@section('topbar', 'Detalhes da candidatura')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Candidatura #{{ $application->id }}</span><h1>{{ $application->candidate?->name }}</h1><p>{{ $application->vacancy?->title }}</p></div>
    <div class="actions"><a class="btn btn-primary" href="{{ route('admin.messages.show', $application->candidate) }}">Enviar mensagem</a><a class="btn btn-light" href="mailto:{{ $application->candidate?->email }}">E-mail</a>@if($application->candidate?->phone)<a class="btn btn-light" target="_blank" rel="noopener" href="https://wa.me/55{{ preg_replace('/\D+/','',$application->candidate->phone) }}">WhatsApp ↗</a>@endif<a class="btn btn-light" href="{{ route('admin.applications.index') }}">← Voltar</a></div>
</div>

<div class="detail-grid" style="margin-bottom:18px">
    <section class="panel">
        <div class="panel-head"><h2>Candidato e currículo</h2><a class="text-link" href="{{ route('admin.candidates.show', $application->candidate) }}">Ver perfil completo →</a></div>
        <div class="panel-body">
            <div class="info-list">
                <div class="info-box"><span>E-mail</span><strong>{{ $application->candidate?->email }}</strong></div>
                <div class="info-box"><span>Telefone</span><strong>{{ $application->candidate?->phone ?: '—' }}</strong></div>
                <div class="info-box"><span>Localização</span><strong>{{ $application->candidate?->location_label ?? '—' }}</strong></div>
                <div class="info-box"><span>Área</span><strong>{{ $application->candidate?->preferred_area ?: '—' }}</strong></div>
            </div>
            @if($application->candidate?->headline)<p style="margin:16px 0 0"><strong>{{ $application->candidate->headline }}</strong></p>@endif
            <div class="actions" style="margin-top:16px">
                <a class="btn btn-primary" target="_blank" href="{{ route('admin.applications.resume', $application) }}">Abrir currículo</a>
                <a class="btn btn-light" href="{{ route('admin.applications.resume.download', $application) }}">Baixar</a>
            </div>
            <div class="help">{{ $application->resume_original_name ?: basename($application->resume_path) }} @if($application->resume_size) · {{ number_format($application->resume_size/1024,0,',','.') }} KB @endif</div>
            @if($application->cover_letter)<div class="divider"></div><h3>Carta de apresentação</h3><p class="prose">{{ $application->cover_letter }}</p>@endif
            @if($application->referral_name)<p class="help"><strong>Indicação:</strong> {{ $application->referral_name }}</p>@endif
            @if($application->source)<p class="help"><strong>Origem:</strong> {{ ucfirst($application->source) }}</p>@endif
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Etapa pública do processo</h2><span class="badge badge-brand">{{ $application->status_label }}</span></div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.applications.status', $application) }}">
                @csrf @method('PATCH')
                <div class="field"><label>Situação</label><select name="status">@foreach(\App\Models\Application::STATUSES as $value => $label)<option value="{{ $value }}" @selected($application->status === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="field"><label>Mensagem para o candidato (opcional)</label><textarea name="public_note" style="min-height:90px" placeholder="Ex.: Seu perfil avançou e entraremos em contato para os próximos passos."></textarea><div class="help">Esta mensagem aparece na linha do tempo do portal do candidato.</div></div>
                <button class="btn btn-primary" type="submit">Atualizar e notificar</button>
            </form>
            @if($application->statusHistories->isNotEmpty())
                <div class="divider"></div>
                <div class="timeline">
                    @foreach($application->statusHistories->take(-4) as $history)
                        <div class="timeline-item"><span class="timeline-dot"></span><h4>{{ $history->status_label }}</h4><p>{{ $history->public_note }}</p><time>{{ $history->created_at->format('d/m/Y H:i') }}</time></div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>

<div class="detail-grid" style="margin-bottom:18px">
    <section class="panel">
        <div class="panel-head"><h2>Entrevista</h2>@if($application->interview)<span class="badge badge-yellow">Agendada</span>@endif</div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.applications.interview.store', $application) }}">
                @csrf
                <div class="form-grid">
                    <div class="field"><label>Data e horário</label><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $application->interview?->scheduled_at?->format('Y-m-d\\TH:i')) }}" required></div>
                    <div class="field"><label>Formato</label><select name="format"><option value="online" @selected($application->interview?->format === 'online')>Videoconferência</option><option value="presencial" @selected($application->interview?->format === 'presencial')>Presencial</option><option value="telefone" @selected($application->interview?->format === 'telefone')>Telefone</option></select></div>
                </div>
                <div class="form-grid">
                    <div class="field"><label>Local</label><input name="location" value="{{ old('location', $application->interview?->location) }}" placeholder="Endereço ou sala"></div>
                    <div class="field"><label>Link da reunião</label><input type="url" name="meeting_url" value="{{ old('meeting_url', $application->interview?->meeting_url) }}" placeholder="https://..."></div>
                </div>
                <div class="field"><label>Orientações para o candidato</label><textarea name="candidate_instructions" style="min-height:90px">{{ old('candidate_instructions', $application->interview?->candidate_instructions) }}</textarea></div>
                <div class="field"><label>Observação interna</label><textarea name="internal_notes" style="min-height:80px">{{ old('internal_notes', $application->interview?->internal_notes) }}</textarea><div class="help">Nunca é exibida no portal do candidato.</div></div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Salvar entrevista</button></div>
            </form>
            @if($application->interview)
                @php($responseLabels=['confirmed'=>'Presença confirmada','needs_reschedule'=>'Solicitou reagendamento','declined'=>'Não poderá participar'])
                <div class="verification-row" style="margin-top:14px"><div><strong>Resposta do candidato</strong><span>{{ $application->interview->candidate_response_at ? 'Registrada em '.$application->interview->candidate_response_at->format('d/m/Y H:i') : 'Ainda não respondeu' }}</span></div><span class="badge {{ $application->interview->candidate_response==='confirmed' ? 'badge-green' : ($application->interview->candidate_response ? 'badge-yellow':'badge-gray') }}">{{ $responseLabels[$application->interview->candidate_response] ?? 'Aguardando' }}</span></div>
                <form method="POST" action="{{ route('admin.applications.interview.destroy', $application) }}" style="margin-top:10px">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Remover entrevista</button></form>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Anotações privadas</h2><span class="subtle">Somente admin</span></div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.applications.notes.store', $application) }}">@csrf<div class="field"><textarea name="note" style="min-height:90px" placeholder="Registre observações da equipe de recrutamento..." required></textarea></div><button class="btn btn-dark btn-sm" type="submit">Adicionar anotação</button></form>
            <div class="divider"></div>
            @forelse($application->notes as $note)
                <div class="note">
                    <p>{{ $note->note }}</p>
                    <small>{{ $note->user?->name ?: 'Usuário removido' }} · {{ $note->created_at->format('d/m/Y H:i') }}</small>
                    <form method="POST" action="{{ route('admin.applications.notes.destroy', $note) }}" style="float:right">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Excluir</button></form>
                </div>
            @empty
                <p class="muted">Nenhuma anotação interna.</p>
            @endforelse
        </div>
    </section>
</div>

<section class="panel" style="margin-bottom:18px">
    <div class="panel-head">
        <div><h2>Análise assistida do currículo</h2><div class="subtle">Uso interno. Não é exibida ao candidato e não decide a contratação.</div></div>
        <form method="POST" action="{{ route('admin.analyses.run', $application) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">{{ $application->analysis?->status === 'completed' ? 'Reanalisar currículo' : 'Analisar currículo' }}</button></form>
    </div>
    <div class="panel-body">
        @if(!$application->analysis)
            <p class="muted">Ainda não há análise. Execute a leitura para extrair competências e comparar com os requisitos objetivos da vaga.</p>
        @elseif($application->analysis->status === 'failed')
            <div class="alert alert-danger"><strong>A análise não foi concluída.</strong><br>{{ $application->analysis->error_message }}</div>
        @elseif($application->analysis->status === 'completed')
            <div class="detail-grid">
                <div>
                    <span class="eyebrow">Aderência textual interna</span>
                    @if($application->analysis->match_score !== null)<div class="score">{{ number_format((float)$application->analysis->match_score,0) }}<small>%</small></div>@else<div class="score">—</div>@endif
                    <p class="muted" style="line-height:1.6">{{ $application->analysis->summary }}</p>
                    <h3>Competências identificadas</h3>
                    <div class="skill-list">@forelse($application->analysis->skills ?? [] as $skill)<span class="badge badge-brand">{{ $skill }}</span>@empty<span class="subtle">Nenhuma competência do dicionário foi encontrada.</span>@endforelse</div>
                </div>
                <div>
                    <h3 style="margin-top:0">Requisitos encontrados</h3>
                    <div class="requirement-list">@forelse($application->analysis->matched_requirements ?? [] as $req)<div class="requirement ok">✓ {{ $req }}</div>@empty<div class="subtle">Nenhum requisito objetivo foi encontrado.</div>@endforelse</div>
                    <h3>Não identificados</h3>
                    <div class="requirement-list">@forelse($application->analysis->missing_requirements ?? [] as $req)<div class="requirement miss">× {{ $req }}</div>@empty<div class="subtle">Nenhum requisito pendente identificado.</div>@endforelse</div>
                </div>
            </div>
            @if(!empty($application->analysis->experience))<div class="divider"></div><h3>Experiência mencionada</h3><div class="skill-list">@foreach($application->analysis->experience as $skill => $years)<span class="badge badge-purple">{{ $skill }} · {{ $years }}</span>@endforeach</div>@endif
            @if(!empty($application->analysis->education))<h3>Formação identificada</h3><ul>@foreach($application->analysis->education as $item)<li>{{ $item }}</li>@endforeach</ul>@endif

            @if(!empty($application->analysis->languages))<h3>Idiomas do perfil</h3><div class="skill-list">@foreach($application->analysis->languages as $item)<span class="badge badge-blue">{{ $item['idioma'] ?? '' }} · {{ $item['nivel'] ?? '' }}</span>@endforeach</div>@endif
            @if(!empty($application->analysis->certifications))<h3>Certificações do perfil</h3><div class="skill-list">@foreach($application->analysis->certifications as $item)<span class="badge badge-green">{{ $item['nome'] ?? '' }}{{ !empty($item['instituicao']) ? ' · '.$item['instituicao'] : '' }}</span>@endforeach</div>@endif
            @if(!empty($application->analysis->profile_highlights))<h3>Destaques profissionais</h3><ul>@foreach($application->analysis->profile_highlights as $item)<li>{{ $item }}</li>@endforeach</ul>@endif
        @else
            <span class="badge badge-yellow">Processando</span>
        @endif
    </div>
</section>

@if($application->analysis?->status === 'completed' && $application->analysis->extracted_text)
<section class="panel"><div class="panel-head"><h2>Texto extraído do currículo</h2><span class="subtle">Uso interno</span></div><div class="panel-body"><div class="code-preview">{{ $application->analysis->extracted_text }}</div></div></section>
@endif
@endsection
