@extends('layouts.admin')

@section('title', 'Atividades')
@section('topbar', 'Auditoria')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Auditoria</span><h1>Atividades administrativas</h1><p>Histórico das principais alterações realizadas no processo.</p></div>
</div>

<form class="filters compact" method="GET">
    <div class="field"><label>Buscar</label><input name="q" value="{{ request('q') }}" placeholder="Ação ou administrador"></div>
    <div class="field"><label>De</label><input type="date" name="date_from" value="{{ request('date_from') }}"></div>
    <div class="field"><label>Até</label><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
    <button class="btn btn-dark" type="submit">Filtrar</button>
</form>

<section class="panel">
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>Data</th><th>Administrador</th><th>Ação</th><th>Objeto</th><th>Detalhes</th></tr></thead><tbody>
    @forelse($logs as $log)
        <tr>
            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
            <td>{{ $log->user?->name ?: 'Sistema' }}<div class="subtle">{{ $log->user?->email }}</div></td>
            <td><span class="badge badge-gray">{{ $log->action }}</span></td>
            <td>{{ class_basename($log->subject_type ?? '') }} @if($log->subject_id)#{{ $log->subject_id }}@endif</td>
            <td><span class="subtle">{{ $log->metadata ? json_encode($log->metadata, JSON_UNESCAPED_UNICODE) : '—' }}</span></td>
        </tr>
    @empty<tr><td colspan="5" style="text-align:center;padding:30px" class="muted">Nenhuma atividade registrada.</td></tr>@endforelse
    </tbody></table></div>
    @if($logs->hasPages())<div class="pagination">{{ $logs->links() }}</div>@endif
</section>
@endsection
