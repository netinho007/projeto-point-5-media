@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
title Configurar Gmail SMTP - Point Carreiras

echo =====================================================
echo   CONFIGURAR EMAIL GMAIL - POINT CARREIRAS
echo =====================================================
echo.
echo Antes de continuar, crie uma SENHA DE APP no Google:
echo Conta Google ^> Seguranca ^> Verificacao em duas etapas ^> Senhas de app.
echo.
echo Nao use sua senha normal do Gmail. Use a senha de app de 16 caracteres.
echo.
set /p GMAIL_ADDRESS=Digite o Gmail remetente: 
set /p GMAIL_APP_PASSWORD=Digite a senha de app do Gmail: 
set /p TEST_EMAIL=Digite um e-mail para receber o teste: 

if "%GMAIL_ADDRESS%"=="" (
  echo ERRO: Gmail remetente vazio.
  pause
  exit /b 1
)

if "%GMAIL_APP_PASSWORD%"=="" (
  echo ERRO: senha de app vazia.
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
powershell -NoProfile -ExecutionPolicy Bypass -Command "$p='.env'; $gmail=$env:GMAIL_ADDRESS; $pass=$env:GMAIL_APP_PASSWORD; $c=Get-Content -LiteralPath $p -Raw; $c=$c -replace '(?m)^MAIL_MAILER=.*$','MAIL_MAILER=smtp'; $c=$c -replace '(?m)^MAIL_HOST=.*$','MAIL_HOST=smtp.gmail.com'; $c=$c -replace '(?m)^MAIL_PORT=.*$','MAIL_PORT=587'; $c=$c -replace '(?m)^MAIL_USERNAME=.*$',('MAIL_USERNAME=' + $gmail); $c=$c -replace '(?m)^MAIL_PASSWORD=.*$',('MAIL_PASSWORD="' + $pass + '"'); $c=$c -replace '(?m)^MAIL_ENCRYPTION=.*$','MAIL_ENCRYPTION=tls'; $c=$c -replace '(?m)^MAIL_FROM_ADDRESS=.*$',('MAIL_FROM_ADDRESS="' + $gmail + '"'); $c=$c -replace '(?m)^MAIL_FROM_NAME=.*$','MAIL_FROM_NAME="Point Carreiras"'; $c=$c -replace '(?m)^QUEUE_CONNECTION=.*$','QUEUE_CONNECTION=sync'; Set-Content -LiteralPath $p -Value $c -Encoding UTF8"
if errorlevel 1 goto :erro

echo.
echo Limpando cache de configuracao...
php artisan optimize:clear
if errorlevel 1 goto :erro

echo.
echo Configuracao atual carregada pelo Laravel:
php artisan tinker --execute "echo 'mail='.config('mail.default').' host='.config('mail.mailers.smtp.host').' from='.config('mail.from.address').' queue='.config('queue.default').PHP_EOL;"
if errorlevel 1 goto :erro

if not "%TEST_EMAIL%"=="" (
  echo.
  echo Enviando e-mail de teste para %TEST_EMAIL%...
  php artisan point:mail-test "%TEST_EMAIL%"
  if errorlevel 1 goto :erro
)

echo.
echo =====================================================
echo   GMAIL CONFIGURADO COM SUCESSO
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
echo Confira se a senha de app esta correta e se a verificacao em duas etapas esta ativa.
pause
exit /b 1
