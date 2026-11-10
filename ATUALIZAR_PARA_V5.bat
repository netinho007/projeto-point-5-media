@echo off
setlocal
cd /d "%~dp0"
title Atualizar Point Carreiras - V5

echo =====================================================
echo   POINT CARREIRAS - ATUALIZACAO SEGURA PARA V5
echo =====================================================
echo.
echo Esta atualizacao NAO apaga o banco de dados.
echo NUNCA use php artisan migrate:fresh neste projeto.
echo.

if not exist artisan (
  echo ERRO: artisan nao encontrado. Extraia o ZIP corretamente.
  pause
  exit /b 1
)

if not exist .env (
  echo ERRO: arquivo .env nao encontrado.
  echo Copie seu .env atual para esta pasta antes de atualizar.
  echo Se esta for uma instalacao nova, use INSTALAR_PROJETO.bat.
  pause
  exit /b 1
)

echo [1/6] Instalando dependencias do Laravel...
call composer install --no-interaction
if errorlevel 1 goto :erro

echo.
echo [2/6] Garantindo leitor de PDF para analise de curriculo...
call composer require smalot/pdfparser --no-interaction
if errorlevel 1 goto :erro

echo.
echo [3/6] Criando pastas necessarias...
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\app\private\curriculos" mkdir "storage\app\private\curriculos"
if not exist "storage\app\private\mensagens" mkdir "storage\app\private\mensagens"

echo.
echo [4/6] Atualizando tabelas sem apagar dados...
php artisan migrate --force
if errorlevel 1 goto :erro

echo.
echo [5/6] Limpando caches...
php artisan optimize:clear
if errorlevel 1 goto :erro

echo.
echo [6/6] Executando manutencao de prazos...
php artisan point:maintenance
if errorlevel 1 goto :erro

echo.
echo =====================================================
echo   V5 ATUALIZADA COM SUCESSO
echo =====================================================
echo.
echo Para iniciar:
echo   php artisan serve
echo.
echo Para testar e-mail depois de configurar o .env:
echo   php artisan point:mail-test seuemail@exemplo.com
pause
exit /b 0

:erro
echo.
echo =====================================================
echo   A ATUALIZACAO PAROU POR CAUSA DE UM ERRO
echo =====================================================
echo Tire uma foto desta tela antes de fechar.
pause
exit /b 1
