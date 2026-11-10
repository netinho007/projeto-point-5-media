@extends('layouts.admin')

@section('title', 'Editar vaga')
@section('topbar', 'Vagas')

@section('content')
<div class="page-head">
    <div><h1>Editar vaga</h1><p>{{ $vacancy->title }}</p></div>
</div>

<section class="panel form-card">
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.vacancies.update', $vacancy) }}">
            @csrf
            @method('PUT')
            @include('admin.vacancies._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Atualizar vaga</button>
                <a class="btn btn-light" href="{{ route('admin.vacancies.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
</section>
@endsection
