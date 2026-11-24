@extends('layouts.candidate')
@section('title','Vagas salvas')
@section('content')
<div class="page-head"><div><span class="eyebrow">Favoritas</span><h1>Vagas salvas</h1><p>Guarde oportunidades para revisar depois.</p></div><a class="btn btn-primary" href="{{ route('vacancies.index') }}#vagas">Ver vagas</a></div>
<div class="jobs-grid">@forelse($vacancies as $vacancy)<article class="job-card"><span class="eyebrow">{{ $vacancy->department ?: 'Oportunidade' }}</span><h3>{{ $vacancy->title }}</h3><div class="tags">@if($vacancy->location)<span class="tag">{{ $vacancy->location }}</span>@endif @if($vacancy->work_mode)<span class="tag">{{ (['remoto'=>'Remoto','hibrido'=>'Híbrido','presencial'=>'Presencial','flexivel'=>'Flexível'][$vacancy->work_mode] ?? ucfirst($vacancy->work_mode)) }}</span>@endif</div><div class="actions"><a class="btn btn-primary btn-sm" href="{{ route('vacancies.show',$vacancy) }}">Ver vaga</a><form method="POST" action="{{ route('candidate.saved-vacancies.toggle',$vacancy) }}">@csrf<button class="btn btn-light btn-sm">Remover</button></form></div></article>@empty<div class="empty-state"><h3>Você ainda não salvou vagas.</h3></div>@endforelse</div>
@endsection
