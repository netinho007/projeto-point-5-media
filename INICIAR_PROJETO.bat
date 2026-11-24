@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Point Carreiras
echo POINT CARREIRAS - INICIANDO AMBIENTE LOCAL
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "scripts\start-local.ps1"
if errorlevel 1 (
  echo ERRO: confira a mensagem acima.
) else (
  echo Ambiente iniciado. Abra http://127.0.0.1:8000 no navegador.
)
pause
