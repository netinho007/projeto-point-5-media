@extends('layouts.admin')

@section('title', 'Entrevistas')
@section('topbar', 'Entrevistas')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Agenda de recrutamento</span><h1>Entrevistas</h1><p>Acompanhe as entrevistas agendadas em todos os processos.</p></div>
</div>

<form class="filters compact" method="GET">
    <div class="field"><label>Buscar</label><input name="q" value="{{ request('q') }}" placeholder="Candidato ou vaga"></div>
    <div class="field"><label>Período</label><select name="period"><option value="">Todas</option><option value="upcoming" @selected(request('period')==='upcoming')>Próximas</option><option value="past" @selected(request('period')==='past')>Realizadas / passadas</option></select></div>
    <div></div>
    <button class="btn btn-dark" type="submit">Filtrar</button>
</form>

<section class="panel">
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>Data</th><th>Candidato</th><th>Vaga</th><th>Formato</th><th>Local / link</th><th>Resposta</th><th></th></tr></thead><tbody>
    @forelse($interviews as $interview)
        <tr>
            <td><strong>{{ $interview->scheduled_at->format('d/m/Y H:i') }}</strong>@if($interview->scheduled_at->isFuture())<div class="subtle">{{ $interview->scheduled_at->diffForHumans() }}</div>@endif</td>
            <td>{{ $interview->application?->candidate?->name }}</td>
            <td>{{ $interview->application?->vacancy?->title }}</td>
            <td><span class="badge badge-yellow">{{ ['online'=>'Videoconferência','presencial'=>'Presencial','telefone'=>'Telefone'][$interview->format] ?? ucfirst($interview->format) }}</span></td>
            <td><span class="subtle">{{ $interview->location ?: ($interview->meeting_url ? 'Link online' : '—') }}</span></td>
            <td>@php($responseLabels=['confirmed'=>'Confirmada','needs_reschedule'=>'Reagendar','declined'=>'Não participará'])<span class="badge {{ $interview->candidate_response==='confirmed' ? 'badge-green' : ($interview->candidate_response ? 'badge-yellow':'badge-gray') }}">{{ $responseLabels[$interview->candidate_response] ?? 'Aguardando' }}</span></td>
            <td><a class="btn btn-light btn-sm" href="{{ route('admin.applications.show', $interview->application) }}">Abrir processo</a></td>
        </tr>
    @empty<tr><td colspan="7" style="text-align:center;padding:30px" class="muted">Nenhuma entrevista encontrada.</td></tr>@endforelse
    </tbody></table></div>
    @if($interviews->hasPages())<div class="pagination">{{ $interviews->links() }}</div>@endif
</section>
@endsection
