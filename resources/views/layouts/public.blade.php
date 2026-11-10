<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal de carreiras Point Carreiras.">
    <title>@yield('title', 'Point Carreiras')</title>
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">
</head>

<body class="@yield('body-class')">
    <header class="public-header">
    <div class="public-header-container">

        {{-- LOGO --}}
        <a href="{{ url('/') }}" class="public-brand">
            <div class="public-brand-symbol">
                <span class="brand-pink"></span>
                <span class="brand-blue"></span>
                <span class="brand-dot"></span>
            </div>

            <div class="public-brand-text">
                <strong>Point.</strong>
                <span>CARREIRAS</span>
            </div>
        </a>

        {{-- MENU DESKTOP --}}
        <nav class="public-nav">
            <a href="{{ url('/vagas') }}"
               class="{{ request()->is('vagas*') ? 'active' : '' }}">
                Vagas
            </a>

            <a href="{{ url('/carreiras') }}"
               class="{{ request()->is('carreiras*') ? 'active' : '' }}">
                Carreiras
            </a>

            <a href="{{ url('/processo') }}"
               class="{{ request()->is('processo*') ? 'active' : '' }}">
                Processo
            </a>

            <a href="{{ url('/duvidas') }}"
               class="{{ request()->is('duvidas*') ? 'active' : '' }}">
                Dúvidas
            </a>

            @auth
                <a href="{{ url('/portal') }}" class="portal-button">
                    Meu portal
                </a>
            @else
                <a href="{{ url('/login') }}" class="portal-button">
                    Meu portal
                </a>
            @endauth
        </nav>

        {{-- BOTÃO MOBILE --}}
        <button
            class="mobile-menu-button"
            id="mobileMenuButton"
            type="button"
            aria-label="Abrir menu"
            aria-expanded="false"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>

    {{-- MENU MOBILE --}}
    <div class="mobile-menu" id="mobileMenu">

        <a href="{{ url('/vagas') }}">
            Vagas
        </a>

        <a href="{{ url('/carreiras') }}">
            Carreiras
        </a>

        <a href="{{ url('/processo') }}">
            Processo
        </a>

        <a href="{{ url('/duvidas') }}">
            Dúvidas
        </a>

        @auth
            <a href="{{ url('/portal') }}" class="mobile-portal">
                Meu portal
            </a>
        @else
            <a href="{{ url('/login') }}" class="mobile-portal">
                Meu portal
            </a>
        @endauth

    </div>
</header>
<a class="skip-link" href="#conteudo-principal">Pular para o conteúdo</a>
@include('components.loader')
<header class="public-header">
    <nav class="public-nav">
        <a class="brand brand-logo-link" href="{{ route('vacancies.index') }}"><span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span></a>
        <div class="public-links">
            <a href="{{ route('vacancies.index') }}#vagas">Vagas</a>
            <a href="{{ route('career') }}" class="hide-mobile">Carreiras</a>
            <a href="{{ route('process') }}" class="hide-mobile">Processo</a>
            <a href="{{ route('faq') }}" class="hide-mobile">Dúvidas</a>
            @auth
                @if(! auth()->user()->is_admin)<a href="{{ route('candidate.portal') }}" class="nav-cta">Meu portal</a>@else<a href="{{ route('admin.dashboard') }}" class="nav-cta">Painel RH</a>@endif
            @else
                <a href="{{ route('candidate.login') }}">Entrar</a><a href="{{ route('candidate.register') }}" class="nav-cta">Criar conta</a>
            @endauth
        </div>
    </nav>
</header>
<main id="conteudo-principal" class="public-main" tabindex="-1">@if(session('success'))<div class="container" style="padding-top:18px"><div class="alert alert-success">{{ session('success') }}</div></div>@endif @yield('content')</main>
<footer class="public-footer">@hasSection('custom-footer') @yield('custom-footer') @else <div class="public-footer-inner"><div><span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span><p>Onde talento, tecnologia e boas oportunidades se encontram.</p></div><div class="footer-links"><a href="{{ route('career') }}">Carreiras</a><a href="{{ route('process') }}">Processo seletivo</a><a href="{{ route('faq') }}">Dúvidas</a><a href="{{ route('privacy') }}">Privacidade</a></div></div> @endif</footer>
<body>

@include('components.loader')

<header class="public-header">

    {{-- código do cabeçalho que te passei --}}

</header>


<main>
    @yield('content')
</main>


<footer>
    {{-- seu footer atual --}}
</footer>


<script>
    // menu mobile
</script>

</body>
</body>
</html>
