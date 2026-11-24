@echo off
cd /d "%~dp0"
echo Esta correcao pede permissao de administrador para o painel do XAMPP.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "scripts\repair-xampp-ports.ps1"
pause
