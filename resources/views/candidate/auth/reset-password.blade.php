<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova senha · Point Carreiras</title>
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">
</head>
<body class="login-page">
<div class="login-card">
    <span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span>
    <h1>Defina uma nova senha</h1>
    <p>Use uma senha com pelo menos 8 caracteres, letras e números.</p>
    @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field"><label>E-mail</label><input type="email" name="email" value="{{ old('email', $email) }}" required></div>
        <div class="field"><label>Nova senha</label><input type="password" name="password" required></div>
        <div class="field"><label>Confirmar senha</label><input type="password" name="password_confirmation" required></div>
        <button class="btn btn-primary" type="submit">Redefinir senha</button>
    </form>
</div>
</body>
</html>
