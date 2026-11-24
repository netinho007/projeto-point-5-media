@extends('layouts.admin')

@section('title', 'Nova vaga')
@section('topbar', 'Vagas')

@section('content')
<div class="page-head">
    <div><h1>Nova vaga</h1><p>Cadastre a oportunidade e os critérios que serão usados na triagem assistida.</p></div>
</div>

<section class="panel form-card">
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.vacancies.store') }}">
            @csrf
            @include('admin.vacancies._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Salvar vaga</button>
                <a class="btn btn-light" href="{{ route('admin.vacancies.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
</section>
@endsection
