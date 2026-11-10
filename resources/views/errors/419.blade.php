@extends('layouts.public')
@section('title','Sessão expirada')
@section('content')
<section class="public-section"><div class="container"><div class="error-state"><span class="eyebrow">Sessão expirada</span><h1>Por segurança, sua sessão precisa ser renovada.</h1><p>Atualize a página e tente novamente. Se você estava entrando no painel, faça o login outra vez.</p><div class="actions"><a class="btn btn-primary" href="{{ url()->current() }}">Atualizar página</a><a class="btn btn-light" href="{{ route('vacancies.index') }}">Ir para o início</a></div></div></div></section>
@endsection
