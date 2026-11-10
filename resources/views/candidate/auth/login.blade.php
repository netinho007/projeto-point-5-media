<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar · Point Carreiras</title>
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">
</head>
<body>
<div class="auth-page">
    <section class="auth-art">
        <a class="brand brand-logo-link" href="{{ route('vacancies.index') }}"><span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span></a>
        <div>
            <span class="eyebrow">Seu processo, com clareza</span>
            <h1>Acompanhe cada movimento.</h1>
            <p>Veja suas candidaturas, atualizações de etapa, entrevistas e notificações em um único portal.</p>
        </div>
        <p>Processos seletivos com tecnologia como apoio e decisão humana.</p>
    </section>

    <main class="auth-wrap">
        <div class="auth-card">
            <h2>Bem-vindo de volta.</h2>
            <p>Entre com o e-mail usado no seu perfil.</p>

            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if($errors->any())
                <div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('candidate.login.post') }}">
                @csrf
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                </div>
                <div class="field">
                    <label for="password">Senha</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password">
                </div>
                <div class="check-line" style="justify-content:space-between;align-items:center">
                    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="remember" value="1"> Manter conectado</label>
                    <a class="text-link" href="{{ route('candidate.password.request') }}">Esqueci a senha</a>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Entrar no meu portal</button>
            </form>
            <div class="auth-switch">Ainda não tem conta? <a class="text-link" href="{{ route('candidate.register') }}">Criar perfil</a></div>
        </div>
    </main>
</div>
</body>
</html>
