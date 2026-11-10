@extends('layouts.public')
@section('title','Vagas · Point Carreiras')
@section('body-class','vacancies-page')

@section('content')
<section class="vacancies-showcase" id="vagas">
    <div class="vacancies-shell">
        <div class="vacancies-topline"></div>

        <div class="vacancies-kicker"><span class="point-dot" aria-hidden="true"></span> OPORTUNIDADES ABERTAS</div>

        <div class="vacancies-heading-row">
            <h1>Encontre sua vaga.<span>_</span></h1>

            <form class="vacancies-search" method="GET" action="{{ route('vacancies.index') }}">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cargo, área, local ou competência" aria-label="Buscar vagas">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <details class="vacancies-filters" @if(request()->except('q')) open @endif>
            <summary>Mais filtros</summary>
            <form method="GET" action="{{ route('vacancies.index') }}#vagas" class="vacancies-filter-grid">
                <input type="hidden" name="q" value="{{ request('q') }}">
                <label>Área
                    <select name="department">
                        <option value="">Todas</option>
                        @foreach($departments as $department)
                            <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Modelo
                    <select name="work_mode">
                        <option value="">Todos</option>
                        @foreach(['remoto'=>'Remoto','hibrido'=>'Híbrido','presencial'=>'Presencial','flexivel'=>'Flexível'] as $v=>$l)
                            <option value="{{ $v }}" @selected(request('work_mode') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Contrato
                    <select name="employment_type">
                        <option value="">Todos</option>
                        @foreach($employmentTypes as $type)
                            <option value="{{ $type }}" @selected(request('employment_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Local
                    <input name="location" value="{{ request('location') }}" placeholder="Cidade ou estado">
                </label>
                <div class="vacancies-filter-actions">
                    <button class="showcase-filter-button" type="submit">Aplicar filtros</button>
                    <a href="{{ route('vacancies.index') }}#vagas">Limpar</a>
                </div>
            </form>
        </details>

        <div class="showcase-jobs-grid">
            @forelse($vacancies as $vacancy)
                <article class="showcase-job-card">
                    <div class="showcase-category"><span class="point-dot" aria-hidden="true"></span>{{ strtoupper($vacancy->department ?: 'Oportunidade') }}</div>
                    <h2>{{ $vacancy->title }}</h2>

                    <div class="showcase-tags">
                        @if($vacancy->location)<span>{{ $vacancy->location }}</span>@endif
                        @if($vacancy->work_mode)<span>{{ ['remoto'=>'Remoto','hibrido'=>'Híbrido','presencial'=>'Presencial','flexivel'=>'Flexível'][$vacancy->work_mode] ?? ucfirst($vacancy->work_mode) }}</span>@endif
                        @if($vacancy->employment_type)<span>{{ $vacancy->employment_type }}</span>@endif
                    </div>

                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($vacancy->description),165) }}</p>
                    @if($vacancy->closes_at)<small class="showcase-deadline">Inscrições até {{ $vacancy->closes_at->format('d/m/Y') }}</small>@endif

                    <div class="showcase-card-action">
                        <a href="{{ route('vacancies.show',$vacancy) }}">Conhecer a vaga →</a>
                    </div>
                </article>
            @empty
                <div class="showcase-empty">
                    <h2>Nenhuma vaga encontrada.</h2>
                    <p>Ajuste sua busca ou volte em breve para conferir novas oportunidades.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection

@section('custom-footer')
<div class="showcase-footer-stripe"><span></span><b></b></div>
<div class="showcase-footer-inner">
    <span>© {{ date('Y') }} Point Carreiras</span>
    <span>Triagem assistida por tecnologia, sempre com revisão humana.</span>
</div>
@endsection
