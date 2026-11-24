@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
title Configurar Brevo Facil - Point Carreiras

echo =====================================================
echo   BREVO FACIL - POINT CARREIRAS
echo =====================================================
echo.
echo Este arquivo configura o Laravel para enviar e-mail pela Brevo.
echo Ja deixei preenchido:
echo   Servidor: smtp-relay.brevo.com
echo   Porta: 587
echo   Login SMTP: baad98001@smtp-brevo.com
echo   Remetente: miguelantonioneto333@gmail.com
echo.
echo Voce so precisa colar a SMTP key completa da Brevo.
echo Se ela aparece com asteriscos, crie uma nova chave SMTP na Brevo.
echo.

set "BREVO_SMTP_LOGIN=baad98001@smtp-brevo.com"
set "BREVO_FROM_EMAIL=miguelantonioneto333@gmail.com"
set /p BREVO_SMTP_KEY=Cole a SMTP key completa da Brevo: 
set /p TEST_EMAIL=Digite seu e-mail para receber o teste: 

if "%BREVO_SMTP_KEY%"=="" (
  echo.
  echo ERRO: a SMTP key ficou vazia.
  pause
  exit /b 1
)

if not exist .env (
  echo.
  echo ERRO: arquivo .env nao encontrado nesta pasta.
  pause
  exit /b 1
)

echo.
echo Configurando o Laravel para usar Brevo...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$p='.env'; $login=$env:BREVO_SMTP_LOGIN; $key=$env:BREVO_SMTP_KEY; $from=$env:BREVO_FROM_EMAIL; $c=Get-Content -LiteralPath $p -Raw; function SetEnvValue($name, $value) { $pattern='(?m)^' + [regex]::Escape($name) + '=.*$'; $line=$name + '=' + $value; if ($script:c -match $pattern) { $script:c = [regex]::Replace($script:c, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $line }) } else { $script:c = $script:c.TrimEnd() + \"`r`n\" + $line + \"`r`n\" } }; function QuoteEnv($value) { return '\"' + ($value -replace '\"','\\\"') + '\"' }; SetEnvValue 'MAIL_MAILER' 'smtp'; SetEnvValue 'MAIL_SCHEME' 'null'; SetEnvValue 'MAIL_HOST' 'smtp-relay.brevo.com'; SetEnvValue 'MAIL_PORT' '587'; SetEnvValue 'MAIL_USERNAME' (QuoteEnv $login); SetEnvValue 'MAIL_PASSWORD' (QuoteEnv $key); SetEnvValue 'MAIL_ENCRYPTION' 'null'; SetEnvValue 'MAIL_FROM_ADDRESS' (QuoteEnv $from); SetEnvValue 'MAIL_FROM_NAME' '\"Point Carreiras\"'; SetEnvValue 'QUEUE_CONNECTION' 'sync'; Set-Content -LiteralPath $p -Value $c -Encoding UTF8"
if errorlevel 1 goto :erro

echo.
echo Limpando cache do Laravel...
php artisan optimize:clear
if errorlevel 1 goto :erro

echo.
echo Conferindo se o Laravel carregou a Brevo:
php artisan tinker --execute "echo 'mail='.config('mail.default').' host='.config('mail.mailers.smtp.host').' from='.config('mail.from.address').' queue='.config('queue.default').PHP_EOL;"
if errorlevel 1 goto :erro

if not "%TEST_EMAIL%"=="" (
  echo.
  echo Enviando teste para %TEST_EMAIL%...
  php artisan point:mail-test "%TEST_EMAIL%"
  if errorlevel 1 goto :erro
)

echo.
echo =====================================================
echo   PRONTO
echo =====================================================
echo Agora teste a recuperacao de senha de novo:
echo   http://127.0.0.1:8000/esqueci-a-senha
echo.
pause
exit /b 0

:erro
echo.
echo =====================================================
echo   DEU ERRO
echo =====================================================
echo Confira:
echo  1. A SMTP key precisa estar completa.
echo  2. O IP precisa estar autorizado na Brevo.
echo  3. O remetente miguelantonioneto333@gmail.com precisa estar verificado.
echo.
pause
exit /b 1
