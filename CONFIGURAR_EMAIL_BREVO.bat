@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
title Configurar Brevo SMTP - Point Carreiras

echo =====================================================
echo   CONFIGURAR EMAIL BREVO - POINT CARREIRAS
echo =====================================================
echo.
echo Antes de continuar, no painel da Brevo:
echo  1. Crie/verifique um remetente em Settings ^> Senders, Domains, IPs.
echo  2. Gere uma SMTP key em Settings ^> SMTP ^& API ^> SMTP.
echo.
echo Use a SMTP key da Brevo. Nao use API key.
echo Nao cole essa chave em prints ou conversas.
echo.
set /p BREVO_SMTP_LOGIN=Digite o SMTP login da Brevo: 
set /p BREVO_SMTP_KEY=Digite a SMTP key da Brevo: 
set /p BREVO_FROM_EMAIL=Digite o e-mail remetente verificado na Brevo: 
set /p TEST_EMAIL=Digite um e-mail para receber o teste: 

if "%BREVO_SMTP_LOGIN%"=="" (
  echo ERRO: SMTP login vazio.
  pause
  exit /b 1
)

if "%BREVO_SMTP_KEY%"=="" (
  echo ERRO: SMTP key vazia.
  pause
  exit /b 1
)

if "%BREVO_FROM_EMAIL%"=="" (
  echo ERRO: e-mail remetente vazio.
  pause
  exit /b 1
)

if not exist .env (
  echo ERRO: arquivo .env nao encontrado.
  pause
  exit /b 1
)

echo.
echo Atualizando .env...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$p='.env'; $login=$env:BREVO_SMTP_LOGIN; $key=$env:BREVO_SMTP_KEY; $from=$env:BREVO_FROM_EMAIL; $c=Get-Content -LiteralPath $p -Raw; function SetEnvValue($name, $value) { $pattern='(?m)^' + [regex]::Escape($name) + '=.*$'; $line=$name + '=' + $value; if ($script:c -match $pattern) { $script:c = [regex]::Replace($script:c, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $line }) } else { $script:c = $script:c.TrimEnd() + \"`r`n\" + $line + \"`r`n\" } }; function QuoteEnv($value) { return '\"' + ($value -replace '\"','\\\"') + '\"' }; SetEnvValue 'MAIL_MAILER' 'smtp'; SetEnvValue 'MAIL_SCHEME' 'null'; SetEnvValue 'MAIL_HOST' 'smtp-relay.brevo.com'; SetEnvValue 'MAIL_PORT' '587'; SetEnvValue 'MAIL_USERNAME' (QuoteEnv $login); SetEnvValue 'MAIL_PASSWORD' (QuoteEnv $key); SetEnvValue 'MAIL_ENCRYPTION' 'null'; SetEnvValue 'MAIL_FROM_ADDRESS' (QuoteEnv $from); SetEnvValue 'MAIL_FROM_NAME' '\"Point Carreiras\"'; SetEnvValue 'QUEUE_CONNECTION' 'sync'; Set-Content -LiteralPath $p -Value $c -Encoding UTF8"
if errorlevel 1 goto :erro

echo.
echo Limpando cache de configuracao...
php artisan optimize:clear
if errorlevel 1 goto :erro

echo.
echo Configuracao atual carregada pelo Laravel:
php artisan tinker --execute "echo 'mail='.config('mail.default').' host='.config('mail.mailers.smtp.host').' port='.config('mail.mailers.smtp.port').' from='.config('mail.from.address').' queue='.config('queue.default').PHP_EOL;"
if errorlevel 1 goto :erro

if not "%TEST_EMAIL%"=="" (
  echo.
  echo Enviando e-mail de teste para %TEST_EMAIL%...
  php artisan point:mail-test "%TEST_EMAIL%"
  if errorlevel 1 goto :erro
)

echo.
echo =====================================================
echo   BREVO CONFIGURADO COM SUCESSO
echo =====================================================
echo Agora teste a recuperacao em:
echo   http://127.0.0.1:8000/esqueci-a-senha
echo.
pause
exit /b 0

:erro
echo.
echo =====================================================
echo   A CONFIGURACAO FALHOU
echo =====================================================
echo Confira se voce usou SMTP key, se o remetente esta verificado
echo na Brevo e se a conta ja esta liberada para envio transacional.
pause
exit /b 1
