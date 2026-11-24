@if(session()->has('login_retry_at'))
    @php($remaining = max(0, session('login_retry_at') - now()->timestamp))
    <p class="help" role="status" data-login-retry-at="{{ session('login_retry_at') }}">{{ $remaining > 0 ? 'Tente novamente em '.$remaining.' segundos.' : 'Você já pode tentar entrar novamente.' }}</p>
    <script defer src="{{ asset('js/login-retry.js') }}"></script>
@endif
