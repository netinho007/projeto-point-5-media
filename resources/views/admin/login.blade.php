<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do RH · Point Carreiras</title>
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">
</head>
<body class="login-page">
<div class="login-card">
    <span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span>
    <h1>Área administrativa</h1>
    <p>Gerencie vagas, processos, entrevistas e o Banco de Talentos.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf
        <div class="field"><label>E-mail</label><input type="email" name="email" value="{{ old('email') }}" required autofocus></div>
        <div class="field"><label>Senha</label><input type="password" name="password" required></div>
        <button class="btn btn-primary" type="submit">Entrar no painel</button>
    </form>
    <div class="auth-switch"><a class="text-link" href="{{ route('vacancies.index') }}">← Voltar ao site</a></div>
</div>
</body>
</html>
