@extends('layouts.public')
@section('title','Erro inesperado')
@section('content')
<section class="public-section"><div class="container"><div class="error-state"><span class="eyebrow">Erro 500</span><h1>Não foi possível concluir essa ação.</h1><p>Tente novamente em alguns instantes. Se o problema continuar, a equipe responsável pode verificar os registros do sistema.</p><a class="btn btn-primary" href="{{ route('vacancies.index') }}">Voltar ao início</a></div></div></section>
@endsection
