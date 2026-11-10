@extends('layouts.admin')

@section('title', 'Candidaturas')
@section('topbar', 'Candidaturas')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Pipeline</span><h1>Candidaturas</h1><p>Filtre currículos por vaga, etapa, análise e período.</p></div>
</div>

<form class="filters" style="grid-template-columns:2fr 1.4fr 1fr 1fr 1fr 1fr auto" method="GET">
    <div class="field"><label>Buscar</label><input name="q" value="{{ request('q') }}" placeholder="Candidato, e-mail ou vaga"></div>
    <div class="field"><label>Vaga</label><select name="vacancy_id"><option value="">Todas</option>@foreach($vacancies as $vacancy)<option value="{{ $vacancy->id }}" @selected((string)request('vacancy_id') === (string)$vacancy->id)>{{ $vacancy->title }}</option>@endforeach</select></div>
    <div class="field"><label>Situação</label><select name="status"><option value="">Todos</option>@foreach(\App\Models\Application::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="field"><label>Análise</label><select name="analysis"><option value="">Todas</option><option value="pending" @selected(request('analysis') === 'pending')>Pendente</option><option value="completed" @selected(request('analysis') === 'completed')>Concluída</option></select></div>
    <div class="field"><label>De</label><input type="date" name="date_from" value="{{ request('date_from') }}"></div>
    <div class="field"><label>Até</label><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
    <button class="btn btn-dark" type="submit">Filtrar</button>
</form>

<section class="panel">
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>Candidato</th><th>Vaga</th><th>Situação</th><th>Análise interna</th><th>Entrevista</th><th>Recebida</th><th></th></tr></thead><tbody>
    @forelse($applications as $application)
        @php($statusClass = match($application->status) {'approved'=>'badge-green','archived','not_selected','withdrawn'=>'badge-gray','shortlisted','interview','interview_done','technical_test','decision'=>'badge-purple','reviewing'=>'badge-yellow',default=>'badge-brand'})
        <tr>
            <td><strong>{{ $application->candidate?->name }}</strong><div class="subtle">{{ $application->candidate?->email }}</div></td>
            <td>{{ $application->vacancy?->title }}</td>
            <td><span class="badge {{ $statusClass }}">{{ $application->status_label }}</span></td>
            <td>@if($application->analysis?->status === 'completed')<span class="badge badge-green">{{ number_format((float)$application->analysis->match_score,0) }}% textual</span>@elseif($application->analysis?->status === 'failed')<span class="badge badge-red">Falha</span>@else<span class="badge badge-gray">Pendente</span>@endif</td>
            <td>@if($application->interview)<span class="badge badge-yellow">{{ $application->interview->scheduled_at->format('d/m H:i') }}</span>@else<span class="subtle">—</span>@endif</td>
            <td>{{ $application->created_at->format('d/m/Y H:i') }}</td>
            <td><a class="btn btn-primary btn-sm" href="{{ route('admin.applications.show', $application) }}">Abrir processo</a></td>
        </tr>
    @empty<tr><td colspan="7" style="text-align:center;padding:34px" class="muted">Nenhuma candidatura encontrada.</td></tr>@endforelse
    </tbody></table></div>
    @if($applications->hasPages())<div class="pagination"><span>Página {{ $applications->currentPage() }} de {{ $applications->lastPage() }}</span><div class="pagination-links">@if(!$applications->onFirstPage())<a class="btn btn-light btn-sm" href="{{ $applications->previousPageUrl() }}">Anterior</a>@endif @if($applications->hasMorePages())<a class="btn btn-light btn-sm" href="{{ $applications->nextPageUrl() }}">Próxima</a>@endif</div></div>@endif
</section>
@endsection
