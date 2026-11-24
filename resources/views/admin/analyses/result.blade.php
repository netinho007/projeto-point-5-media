@php($busy = in_array($analysis?->status, ['pending', 'processing'], true) && $analysis?->run_id && $analysis->updated_at->gt(now()->subMinutes(15)))
<section class="panel analysis-panel" @if($busy) data-analysis-status="{{ route('admin.analyses.status', $application) }}" @endif>
    <div class="panel-head">
        <div><h2>Análise do currículo</h2><span class="subtle">Apoio à revisão humana · uso interno</span></div>
        <form method="POST" action="{{ route('admin.analyses.run', $application) }}">
            @csrf
            <button class="btn btn-primary btn-sm" type="submit" @disabled($busy)>{{ $busy ? 'Em andamento' : ($analysis ? 'Reanalisar' : 'Analisar') }}</button>
        </form>
    </div>
    <div class="panel-body">
        @if($busy)
            <div class="analysis-progress" role="status"><span class="analysis-spinner" aria-hidden="true"></span><div><strong>{{ $analysis->status === 'processing' ? 'Analisando currículo' : 'Na fila de análise' }}</strong><p class="muted">{{ $analysis->status === 'processing' ? 'O modelo local está lendo as informações profissionais.' : 'Aguardando o processador de currículos.' }}</p></div></div>
            <p class="help">Solicitado em {{ $analysis->updated_at->format('d/m/Y H:i') }}.</p>
        @elseif($analysis?->status === 'failed')
            <div class="alert alert-danger" role="alert"><strong>Análise não concluída</strong><p>{{ $analysis->error_message }}</p></div>
        @elseif($analysis?->status === 'completed')
            <div class="analysis-summary">
                <div class="analysis-score"><span class="eyebrow">Cobertura dos requisitos</span><strong>{{ $analysis->match_score !== null ? number_format((float) $analysis->match_score, 0).'%' : 'Sem nota' }}</strong><span class="help">{{ count($analysis->matched_requirements ?? []) }} comprovados de {{ count($analysis->requirement_evidence ?? []) ?: count($analysis->matched_requirements ?? []) + count($analysis->missing_requirements ?? []) }}</span></div>
                <div><p class="analysis-copy">{{ $analysis->summary }}</p><div class="analysis-meta"><span class="badge badge-blue">{{ $analysis->provider === 'ollama' ? 'IA local · '.$analysis->model : 'Análise antiga por palavras-chave' }}</span><span class="help">{{ $analysis->analyzed_at?->format('d/m/Y H:i') }} @if($analysis->duration_ms) · {{ number_format($analysis->duration_ms / 1000, 0) }} s @endif</span></div></div>
            </div>
            @if($analysis->provider !== 'ollama')<p class="alert alert-warning">Este resultado é da análise anterior. Execute novamente para obter evidências com IA local.</p>@endif
            <h3>Competências identificadas</h3>
            <div class="skill-list">@forelse($analysis->skills ?? [] as $skill)<span class="badge badge-blue">{{ $skill }}</span>@empty<span class="muted">Nenhuma competência com evidência verificável.</span>@endforelse</div>
            <h3>Requisitos e evidências</h3>
            <div class="analysis-requirements">
            @forelse($analysis->requirement_evidence ?? [] as $item)
                <article class="analysis-requirement">
                    <div class="analysis-requirement-head"><strong>{{ $item['requirement'] }}</strong><span class="badge {{ $item['status'] === 'met' ? 'badge-green' : ($item['status'] === 'partial' ? 'badge-yellow' : 'badge-gray') }}">{{ ['met' => 'Comprovado', 'partial' => 'Parcial', 'not_found' => 'Não identificado'][$item['status']] }}</span></div>
                    @if($item['evidence'])<blockquote>{{ $item['evidence'] }}</blockquote>@endif
                    <p class="muted">{{ $item['reason'] }}</p>
                </article>
            @empty
                <p class="muted">Sem comparação estruturada. Cadastre requisitos profissionais na vaga e execute a análise novamente.</p>
            @endforelse
            </div>
            <div class="detail-grid analysis-details">
                <div><h3>Experiência e formação</h3><ul>@foreach(array_merge($analysis->experience ?? [], $analysis->education ?? []) as $item)<li>{{ $item }}</li>@endforeach</ul>@if(empty($analysis->experience) && empty($analysis->education))<p class="muted">Não identificadas no documento.</p>@endif</div>
                <div><h3>Pontos para entrevista</h3><ol>@forelse($analysis->interview_questions ?? [] as $question)<li>{{ $question }}</li>@empty<li class="muted">Revise os requisitos não identificados com o candidato.</li>@endforelse</ol></div>
            </div>
            <div class="analysis-disclaimer"><strong>Limites da análise</strong><ul>@foreach($analysis->limitations ?? ['Revisão humana obrigatória.'] as $limit)<li>{{ $limit }}</li>@endforeach<li>A porcentagem é uma cobertura textual: comprovado vale 1, parcial vale 0,5. Não representa probabilidade de contratação.</li></ul></div>
            @if($analysis->extracted_text)<details class="analysis-source"><summary>Texto extraído do documento</summary><div class="code-preview">{{ $analysis->extracted_text }}</div></details>@endif
        @elseif(in_array($analysis?->status, ['pending', 'processing'], true) && $analysis?->run_id)
            <p class="alert alert-warning">A análise está demorando mais que o esperado. Confira o processador de currículos e tente novamente.</p>
        @else
            <p class="muted">Nenhuma análise disponível para este currículo.</p>
        @endif
        <p class="help" data-ai-health="{{ route('admin.analyses.engine') }}">Verificando motor local…</p>
    </div>
</section>
<script defer src="{{ asset('js/resume-analysis.js') }}"></script>
