<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Meu Portal') · Point Carreiras</title>
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">
</head>
<body class="portal-body">
<a class="skip-link" href="#conteudo-principal">Pular para o conteúdo</a>
@include('components.loader')
<header class="portal-header">
    <nav class="portal-nav">
        <a class="brand brand-logo-link" href="{{ route('candidate.portal') }}"><span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span></a>
        <div class="portal-links">
            <a href="{{ route('candidate.portal') }}" class="{{ request()->routeIs('candidate.portal') ? 'active' : '' }}">Início</a>
            <a href="{{ route('vacancies.index') }}" class="hide-small">Vagas</a>
            <a href="{{ route('candidate.saved-vacancies.index') }}" class="hide-small {{ request()->routeIs('candidate.saved-vacancies.*') ? 'active' : '' }}">Salvas</a>
            @php($unreadMessagesNav = auth()->user()->candidate?->messages()->where('sender_role','admin')->whereNull('read_at')->count() ?? 0)
            <a href="{{ route('candidate.messages.index') }}" class="{{ request()->routeIs('candidate.messages.*') ? 'active' : '' }}">Mensagens @if($unreadMessagesNav)<span class="menu-count">{{ min($unreadMessagesNav,99) }}</span>@endif</a>
            <a href="{{ route('candidate.profile') }}" class="{{ request()->routeIs('candidate.profile*') ? 'active' : '' }}">Perfil</a>
            <a href="{{ route('candidate.security') }}" class="hide-small {{ request()->routeIs('candidate.security*') ? 'active' : '' }}">Segurança</a>
            <a href="{{ route('candidate.notifications') }}" class="{{ request()->routeIs('candidate.notifications*') ? 'active' : '' }}">🔔 @php($unreadNav = auth()->user()->candidate?->notifications()->whereNull('read_at')->count() ?? 0)@if($unreadNav > 0)<span class="notification-dot">{{ min($unreadNav, 9) }}</span>@endif</a>
            <form method="POST" action="{{ route('candidate.logout') }}">@csrf<button type="submit">Sair</button></form>
        </div>
    </nav>
</header>
<main id="conteudo-principal" class="portal-main" tabindex="-1">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Verifique:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
</body>
</html>
