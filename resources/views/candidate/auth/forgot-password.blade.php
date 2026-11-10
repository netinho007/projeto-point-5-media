<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha · Point Carreiras</title>
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">
</head>
<body class="login-page">
<div class="login-card">
    <span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span>
    <h1>Recuperar senha</h1>
    <p>Informe seu e-mail e enviaremos um link de redefinição.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('candidate.password.email') }}">
        @csrf
        <div class="field"><label>E-mail</label><input type="email" name="email" value="{{ old('email') }}" required autofocus></div>
        <button class="btn btn-primary" type="submit">Enviar link</button>
    </form>
    <div class="auth-switch"><a class="text-link" href="{{ route('candidate.login') }}">← Voltar para entrar</a></div>
</div>
</body>
</html>
