<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal de carreiras Point Carreiras.">
    <title>@yield('title', 'Point Carreiras')</title>
    @include('components.theme-init')
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">@include('components.ui-assets')
    <script defer src="{{ asset('js/theme-toggle.js') }}"></script>
</head>
<body class="@yield('body-class')">
<a class="skip-link" href="#conteudo-principal">Pular para o conteúdo</a>
<header class="public-header">
    <nav class="public-nav">
        <a class="brand brand-logo-link" href="{{ route('vacancies.index') }}">
            <span class="career-brand">
                <span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span>
                <span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span>
            </span>
        </a>
        <button class="nav-menu-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="public-menu" aria-label="Abrir menu" title="Menu"><i data-lucide="menu" aria-hidden="true"></i></button>
        <div class="public-links" id="public-menu">
            <a href="{{ route('vacancies.index') }}#vagas">Vagas</a>
            <a href="{{ route('career') }}" class="hide-mobile">Carreiras</a>
            <a href="{{ route('process') }}" class="hide-mobile">Processo</a>
            <a href="{{ route('faq') }}" class="hide-mobile">Dúvidas</a>
            @include('components.theme-toggle')
            @auth
                @if(! auth()->user()->is_admin)
                    <a href="{{ route('candidate.portal') }}" class="nav-cta">Meu portal</a>
                @else
                    <a href="{{ route('admin.dashboard') }}" class="nav-cta">Painel RH</a>
                @endif
            @else
                <a href="{{ route('candidate.login') }}">Entrar</a>
                <a href="{{ route('candidate.register') }}" class="nav-cta">Criar conta</a>
            @endauth
        </div>
    </nav>
</header>
<main id="conteudo-principal" class="public-main" tabindex="-1">
    @if(session('success'))
        <div class="container" style="padding-top:18px">
            <div class="alert alert-success">{{ session('success') }}</div>
        </div>
    @endif
    @yield('content')
</main>
<footer class="public-footer">
    @hasSection('custom-footer')
        @yield('custom-footer')
    @else
        <div class="public-footer-inner">
            <div>
                <span class="career-brand">
                    <span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span>
                    <span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span>
                </span>
                <p>Onde talento, tecnologia e boas oportunidades se encontram.</p>
            </div>
            <div class="footer-links">
                <a href="{{ route('career') }}">Carreiras</a>
                <a href="{{ route('process') }}">Processo seletivo</a>
                <a href="{{ route('faq') }}">Dúvidas</a>
                <a href="{{ route('privacy') }}">Privacidade</a>
            </div>
        </div>
    @endif
</footer>
</body>
</html>
