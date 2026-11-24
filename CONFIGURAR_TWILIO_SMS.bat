@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
title Configurar Twilio SMS - Point Carreiras

echo =====================================================
echo   CONFIGURAR TWILIO SMS - POINT CARREIRAS
echo =====================================================
echo.
echo Este arquivo configura SMS para:
echo  - codigo de verificacao de telefone no cadastro
echo  - recuperacao de senha por telefone verificado
echo  - aviso por SMS quando o e-mail da conta for alterado
echo.
echo Na Twilio voce precisa de:
echo  1. Account SID
echo  2. Auth Token
echo  3. Verify Service SID, comecando com VA
echo  4. Numero Twilio para SMS, no formato +5511999999999
echo.
echo Se sua conta Twilio for trial, o numero que vai receber SMS
echo precisa estar verificado na Twilio antes do teste.
echo.
echo Nao cole essas credenciais em prints ou conversas.
echo.

set /p TWILIO_ACCOUNT_SID=Digite o Account SID: 
set /p TWILIO_AUTH_TOKEN=Digite o Auth Token: 
set /p TWILIO_VERIFY_SERVICE_SID=Digite o Verify Service SID: 
set /p TWILIO_SMS_FROM=Digite o numero Twilio para SMS: 
set /p TEST_PHONE=Digite um telefone para receber o SMS de teste: 

if "%TWILIO_ACCOUNT_SID%"=="" (
  echo ERRO: Account SID vazio.
  pause
  exit /b 1
)

if "%TWILIO_AUTH_TOKEN%"=="" (
  echo ERRO: Auth Token vazio.
  pause
  exit /b 1
)

if "%TWILIO_VERIFY_SERVICE_SID%"=="" (
  echo ERRO: Verify Service SID vazio.
  pause
  exit /b 1
)

if /i not "%TWILIO_VERIFY_SERVICE_SID:~0,2%"=="VA" (
  echo ERRO: Verify Service SID precisa comecar com VA.
  echo Voce provavelmente colou uma API Key SID, que comeca com SK.
  pause
  exit /b 1
)

if "%TWILIO_SMS_FROM%"=="" (
  echo ERRO: numero Twilio vazio.
  pause
  exit /b 1
)

if not "%TWILIO_SMS_FROM:~0,1%"=="+" (
  echo ERRO: o numero Twilio precisa estar no formato internacional, por exemplo +5511999999999.
  echo Nao use seu telefone pessoal como remetente.
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
powershell -NoProfile -ExecutionPolicy Bypass -Command "$p='.env'; $sid=$env:TWILIO_ACCOUNT_SID; $token=$env:TWILIO_AUTH_TOKEN; $verify=$env:TWILIO_VERIFY_SERVICE_SID; $from=$env:TWILIO_SMS_FROM; $c=Get-Content -LiteralPath $p -Raw; function SetEnvValue($name, $value) { $pattern='(?m)^' + [regex]::Escape($name) + '=.*$'; $line=$name + '=' + $value; if ($script:c -match $pattern) { $script:c = [regex]::Replace($script:c, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $line }) } else { $script:c = $script:c.TrimEnd() + \"`r`n\" + $line + \"`r`n\" } }; function QuoteEnv($value) { return '\"' + ($value -replace '\"','\\\"') + '\"' }; SetEnvValue 'TWILIO_ACCOUNT_SID' (QuoteEnv $sid); SetEnvValue 'TWILIO_AUTH_TOKEN' (QuoteEnv $token); SetEnvValue 'TWILIO_VERIFY_SERVICE_SID' (QuoteEnv $verify); SetEnvValue 'TWILIO_SMS_FROM' (QuoteEnv $from); Set-Content -LiteralPath $p -Value $c -Encoding UTF8"
if errorlevel 1 goto :erro

echo.
echo Limpando cache de configuracao...
php artisan optimize:clear
if errorlevel 1 goto :erro

echo.
echo Configuracao carregada pelo Laravel:
php artisan tinker --execute "echo 'twilio_sid='.(filled(config('services.twilio.sid')) ? 'ok' : 'vazio').' verify='.(filled(config('services.twilio.verify_sid')) ? 'ok' : 'vazio').' sms_from='.config('services.twilio.sms_from').PHP_EOL;"
if errorlevel 1 goto :erro

if not "%TEST_PHONE%"=="" (
  echo.
  echo Enviando SMS de teste para %TEST_PHONE%...
  php artisan point:sms-test "%TEST_PHONE%"
  if errorlevel 1 goto :erro
)

echo.
echo =====================================================
echo   TWILIO CONFIGURADO COM SUCESSO
echo =====================================================
echo Agora cadastros novos e recuperacao por telefone podem enviar SMS real.
echo.
pause
exit /b 0

:erro
echo.
echo =====================================================
echo   A CONFIGURACAO FALHOU
echo =====================================================
echo Confira:
echo  1. As credenciais da Twilio estao corretas.
echo  2. O numero Twilio tem SMS habilitado.
echo  3. Em conta trial, o telefone de destino esta verificado.
echo.
pause
exit /b 1
