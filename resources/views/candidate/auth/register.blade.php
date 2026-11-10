<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta · Point Carreiras</title>
    <link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">
</head>
<body>
<div class="auth-page">
    <section class="auth-art">
        <a class="brand brand-logo-link" href="{{ route('vacancies.index') }}"><span class="career-brand"><span class="career-brand-mark" aria-hidden="true"><i></i><b></b></span><span class="career-brand-copy"><strong>Point</strong><small>Carreiras</small></span></span></a>
        <div>
            <span class="eyebrow">Um perfil, várias oportunidades</span>
            <h1>Faça parte do movimento.</h1>
            <p>Crie seu perfil, candidate-se com menos etapas e acompanhe o processo seletivo sem perder atualizações.</p>
        </div>
        <p>Você controla se deseja permanecer no Banco de Talentos e pode retirar esse consentimento a qualquer momento.</p>
    </section>

    <main class="auth-wrap">
        <div class="auth-card">
            <h2>Crie seu perfil.</h2>
            <p>Depois do cadastro você poderá adicionar currículo, experiência resumida e links profissionais.</p>

            @if($errors->any())
                <div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('candidate.register.post') }}">
                @csrf
                <div class="field">
                    <label for="name">Nome completo</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
                </div>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="password">Senha</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password">
                        <div class="help">Mínimo de 8 caracteres, com letras e números.</div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirmar senha</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                    </div>
                </div>
                <label class="check-line">
                    <input type="checkbox" name="talent_pool" value="1" @checked(old('talent_pool'))>
                    <span>Quero participar do <strong>Banco de Talentos</strong> e autorizo o uso do meu perfil para contato sobre futuras oportunidades.</span>
                </label>
                <label class="check-line">
                    <input type="checkbox" name="privacy_consent" value="1" required @checked(old('privacy_consent'))>
                    <span>Li e concordo com a <a class="text-link" target="_blank" href="{{ route('privacy') }}">Política de Privacidade</a> e com o tratamento dos meus dados para recrutamento.</span>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Criar minha conta</button>
            </form>
            <div class="auth-switch">Já tem uma conta? <a class="text-link" href="{{ route('candidate.login') }}">Entrar</a></div>
        </div>
    </main>
</div>
</body>
</html>
