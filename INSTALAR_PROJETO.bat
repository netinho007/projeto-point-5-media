@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
title Instalar Point Carreiras - V5

echo =====================================================
echo   POINT CARREIRAS V5 - INSTALACAO
echo =====================================================
echo.
echo Banco recomendado: point_media
echo Este instalador NAO executa migrate:fresh.
echo.

if not exist artisan (
  echo ERRO: artisan nao encontrado. Extraia o ZIP corretamente.
  pause
  exit /b 1
)

if not exist .env (
  copy /Y .env.example .env >nul
  echo Arquivo .env criado.
  echo.
  set /p MYSQLPASS=Digite a senha LOCAL do usuario root do MySQL: 
  powershell -NoProfile -ExecutionPolicy Bypass -Command "$p='.env'; $c=Get-Content $p -Raw; $c=$c -replace '(?m)^DB_CONNECTION=.*$','DB_CONNECTION=mysql'; $c=$c -replace '(?m)^DB_HOST=.*$','DB_HOST=127.0.0.1'; $c=$c -replace '(?m)^DB_PORT=.*$','DB_PORT=3306'; $c=$c -replace '(?m)^DB_DATABASE=.*$','DB_DATABASE=point_media'; $c=$c -replace '(?m)^DB_USERNAME=.*$','DB_USERNAME=root'; $escaped=$env:MYSQLPASS.Replace('"','\"'); $c=$c -replace '(?m)^DB_PASSWORD=.*$',('DB_PASSWORD="'+$escaped+'"'); Set-Content -Path $p -Value $c -Encoding UTF8"
)

if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\app\private\curriculos" mkdir "storage\app\private\curriculos"
if not exist "storage\app\private\mensagens" mkdir "storage\app\private\mensagens"

echo.
echo [1/7] Dependencias...
call composer install --no-interaction
if errorlevel 1 goto :erro

call composer require smalot/pdfparser --no-interaction
if errorlevel 1 goto :erro

echo.
echo [2/7] Chave da aplicacao...
findstr /B /C:"APP_KEY=base64:" .env >nul 2>&1
if errorlevel 1 php artisan key:generate --force
if errorlevel 1 goto :erro

echo.
echo [3/7] Limpando configuracoes...
php artisan config:clear
if errorlevel 1 goto :erro

echo.
echo [4/7] Criando/atualizando tabelas...
php artisan migrate --force
if errorlevel 1 goto :erro

echo.
echo [5/7] Limpando caches...
php artisan optimize:clear
if errorlevel 1 goto :erro

echo.
echo [6/7] Manutencao inicial...
php artisan point:maintenance
if errorlevel 1 goto :erro

echo.
echo [7/7] Informacoes do ambiente...
php artisan about

echo.
echo =====================================================
echo   INSTALACAO CONCLUIDA
echo =====================================================
echo.
echo Para criar o administrador:
echo   php artisan point:create-admin

echo.
echo Para iniciar o site:
echo   php artisan serve

echo.
echo Site:  http://127.0.0.1:8000
echo Admin: http://127.0.0.1:8000/admin/login
echo.
pause
exit /b 0

:erro
echo.
echo =====================================================
echo   A INSTALACAO PAROU POR CAUSA DE UM ERRO
echo =====================================================
echo Tire uma foto desta tela antes de fechar.
pause
exit /b 1
