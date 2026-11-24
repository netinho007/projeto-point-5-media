@extends('layouts.admin')

@section('title', 'Análises IA')
@section('topbar', 'Análises assistidas')

@section('content')
<div class="page-head"><div><h1>Análises de currículos</h1><p data-ai-health="{{ route('admin.analyses.engine') }}">Verificando motor local…</p></div><span class="badge badge-blue">Revisão humana</span></div>

<form class="filters compact" method="GET">
    <div class="field"><label>Situação</label><select name="status"><option value="">Todas</option><option value="pending" @selected(request('status')==='pending')>Pendentes</option><option value="completed" @selected(request('status')==='completed')>Concluídas</option><option value="failed" @selected(request('status')==='failed')>Com falha</option></select></div>
    <div></div><div></div><button class="btn btn-light" type="submit">Filtrar</button>
</form>

<section class="panel"><div class="table-wrap"><table class="admin-table"><thead><tr><th>Candidato</th><th>Vaga</th><th>Situação</th><th>Aderência</th><th>Analisado em</th><th></th></tr></thead><tbody>
@forelse($applications as $application)
@php($busy = in_array($application->analysis?->status, ['pending', 'processing'], true) && $application->analysis?->run_id && $application->analysis->updated_at->gt(now()->subMinutes(15)))
<tr>
    <td><strong>{{ $application->candidate?->name }}</strong></td>
    <td>{{ $application->vacancy?->title }}</td>
    <td>@if($application->analysis?->status==='completed')<span class="badge badge-green">Concluída</span>@elseif($application->analysis?->status==='failed')<span class="badge badge-red">Falhou</span>@elseif($application->analysis?->status==='processing')<span class="badge badge-yellow">Processando</span>@else<span class="badge badge-gray">Pendente</span>@endif</td>
    <td>@if($application->analysis?->match_score !== null)<strong>{{ number_format((float)$application->analysis->match_score,0) }}%</strong>@else—@endif</td>
    <td>{{ $application->analysis?->analyzed_at?->format('d/m/Y H:i') ?? '—' }}</td>
    <td><div class="actions"><a class="btn btn-light btn-sm" href="{{ route('admin.applications.show', $application) }}">Abrir</a>@if($application->analysis?->status !== 'completed' && !$busy)<form method="POST" action="{{ route('admin.analyses.run', $application) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Analisar</button></form>@endif</div></td>
</tr>
@empty<tr><td colspan="6" style="text-align:center;padding:34px;color:#6b7280">Nenhuma candidatura nesta situação.</td></tr>@endforelse
</tbody></table></div>
@if($applications->hasPages())<div class="pagination"><span>Página {{ $applications->currentPage() }} de {{ $applications->lastPage() }}</span><div class="pagination-links">@if(!$applications->onFirstPage())<a class="btn btn-light btn-sm" href="{{ $applications->previousPageUrl() }}">Anterior</a>@endif @if($applications->hasMorePages())<a class="btn btn-light btn-sm" href="{{ $applications->nextPageUrl() }}">Próxima</a>@endif</div></div>@endif
</section>
<script defer src="{{ asset('js/resume-analysis.js') }}"></script>
@endsection
