@extends('layouts.admin')

@section('title', 'Candidatos')
@section('topbar', 'Candidatos')

@section('content')
<div class="page-head">
    <div><h1>Candidatos</h1><p>Consulte perfis, competências identificadas e histórico de candidaturas.</p></div>
</div>

<form class="filters compact" method="GET">
    <div class="field"><label>Buscar</label><input name="q" value="{{ request('q') }}" placeholder="Nome, e-mail, telefone ou competência"></div>
    <div class="field"><label>Vaga</label><select name="vacancy_id"><option value="">Todas</option>@foreach($vacancies as $vacancy)<option value="{{ $vacancy->id }}" @selected((string)request('vacancy_id') === (string)$vacancy->id)>{{ $vacancy->title }}</option>@endforeach</select></div>
    <div class="field"><label>Situação</label><select name="status"><option value="">Todos</option>@foreach(\App\Models\Application::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="field"><label>Conta e privacidade</label><select name="privacy"><option value="">Todos</option><option value="deletion_requested" @selected(request('privacy')==='deletion_requested')>Exclusão solicitada</option><option value="inactive" @selected(request('privacy')==='inactive')>Conta desativada</option></select></div>
    <button class="btn btn-light" type="submit">Filtrar</button>
</form>

<section class="panel">
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Candidato</th><th>Localização</th><th>Competências</th><th>Candidaturas</th><th>Última vaga</th><th></th></tr></thead>
            <tbody>
            @forelse($candidates as $candidate)
                @php($lastApplication = $candidate->applications->sortByDesc('created_at')->first())
                <tr>
                    <td><strong>{{ $candidate->name }}</strong><div class="subtle">{{ $candidate->email }}</div><div class="skill-list" style="margin-top:5px">@if(!$candidate->account_active)<span class="badge badge-yellow">Conta desativada</span>@endif @if($candidate->deletion_requested_at)<span class="badge badge-red">Exclusão solicitada</span>@endif</div></td>
                    <td>{{ trim(($candidate->city ?? '').($candidate->city && $candidate->state ? ' / ' : '').($candidate->state ?? '')) ?: '—' }}</td>
                    <td>
                        @if($candidate->skills->isNotEmpty())
                            <div class="skill-list">@foreach($candidate->skills->take(4) as $skill)<span class="badge badge-blue">{{ $skill->name }}</span>@endforeach @if($candidate->skills->count() > 4)<span class="badge badge-gray">+{{ $candidate->skills->count()-4 }}</span>@endif</div>
                        @else
                            <span class="subtle">Ainda não analisado</span>
                        @endif
                    </td>
                    <td>{{ $candidate->applications_count }}</td>
                    <td>{{ $lastApplication?->vacancy?->title ?? '—' }}</td>
                    <td><a class="btn btn-light btn-sm" href="{{ route('admin.candidates.show', $candidate) }}">Ver candidato</a></td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:34px;color:#6b7280">Nenhum candidato encontrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($candidates->hasPages())
        <div class="pagination"><span>Página {{ $candidates->currentPage() }} de {{ $candidates->lastPage() }}</span><div class="pagination-links">@if(!$candidates->onFirstPage())<a class="btn btn-light btn-sm" href="{{ $candidates->previousPageUrl() }}">Anterior</a>@endif @if($candidates->hasMorePages())<a class="btn btn-light btn-sm" href="{{ $candidates->nextPageUrl() }}">Próxima</a>@endif</div></div>
    @endif
</section>
@endsection
