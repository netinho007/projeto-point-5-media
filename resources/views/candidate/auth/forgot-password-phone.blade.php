<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar por telefone · Point Carreiras</title>
    @include('components.theme-init')
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">@include('components.ui-assets')
    <script defer src="{{ asset('js/theme-toggle.js') }}"></script>
</head>
<body class="login-page">
<div class="auth-theme-toggle">@include('components.theme-toggle')</div>
<div class="login-card">
    <span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span>
    <h1>Recuperar por telefone</h1>
    <p>Informe o telefone verificado no seu perfil. Enviaremos um código para você criar uma nova senha.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('candidate.password.phone.send') }}">
        @csrf
        <div class="field"><label>Telefone com DDD</label><input name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999" required autofocus></div>
        <button class="btn btn-primary" type="submit">Enviar código por SMS</button>
    </form>
    @if(session()->has('phone_password_reset'))
        <div class="divider"></div>
        <form method="POST" action="{{ route('candidate.password.phone.update') }}">
            @csrf
            <div class="field"><label>Código recebido</label><input name="code" maxlength="6" inputmode="numeric" placeholder="000000" required></div>
            <div class="field"><label>Nova senha</label><input type="password" name="password" autocomplete="new-password" required></div>
            <div class="field"><label>Confirmar nova senha</label><input type="password" name="password_confirmation" autocomplete="new-password" required></div>
            <button class="btn btn-primary" type="submit">Redefinir senha</button>
        </form>
    @endif
    <div class="auth-switch"><a class="text-link" href="{{ route('candidate.password.request') }}">Recuperar por e-mail</a></div>
    <div class="auth-switch"><a class="text-link" href="{{ route('candidate.login') }}">← Voltar para entrar</a></div>
</div>
</body>
</html>
