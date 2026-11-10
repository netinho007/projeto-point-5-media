@extends('layouts.public')
@section('title','Página não encontrada')
@section('content')
<section class="public-section"><div class="container"><div class="error-state"><span class="eyebrow">Erro 404</span><h1>Essa página não foi encontrada.</h1><p>O endereço pode ter mudado ou não estar mais disponível.</p><div class="actions"><a class="btn btn-primary" href="{{ route('vacancies.index') }}">Voltar às vagas</a><a class="btn btn-light" href="{{ route('career') }}">Conhecer carreiras</a></div></div></div></section>
@endsection
