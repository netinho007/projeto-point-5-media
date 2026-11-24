param([switch]$Elevated)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$log = Join-Path $root 'storage\logs\xampp-ports-repair.log'
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$admin = ([Security.Principal.WindowsPrincipal]$identity).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $admin) {
    if ($Elevated) { throw 'Permissao de administrador nao concedida.' }
    $arguments = '-NoProfile -ExecutionPolicy Bypass -File "' + $PSCommandPath + '" -Elevated'
    $process = Start-Process -FilePath 'powershell.exe' -Verb RunAs -ArgumentList $arguments -WindowStyle Hidden -PassThru
    $process.WaitForExit()
    if ($process.ExitCode -ne 0) { throw 'A correcao do painel nao foi concluida.' }
    Get-Content -LiteralPath $log
    exit
}

try {
    $target = 'C:\xampp\xampp-control.ini'
    $mysql = 'C:\xampp\mysql\bin\my.ini'
    if (-not (Test-Path -LiteralPath $target) -or -not (Test-Path -LiteralPath $mysql)) { throw 'XAMPP nao encontrado.' }
    $envFile = Get-Content -LiteralPath (Join-Path $root '.env')
    if ($envFile -notcontains 'DB_PORT=3307') { throw 'O projeto precisa estar configurado na porta 3307 antes da correcao.' }
    $mysqlText = Get-Content -LiteralPath $mysql -Raw
    if ($mysqlText -notmatch '(?m)^port\s*=\s*3307\s*$') { throw 'my.ini ainda nao esta na porta 3307.' }
    $lines = Get-Content -LiteralPath $target
    $section = ''
    $foundApache = $false
    $foundMysql = $false
    $updated = foreach ($line in $lines) {
        if ($line -match '^\[(.+)\]$') { $section = $Matches[1] }
        if ($section -eq 'ServicePorts' -and $line -match '^Apache=\d+$') {
            $foundApache = $true
            'Apache=80'
        } elseif ($section -eq 'ServicePorts' -and $line -match '^MySQL=\d+$') {
            $foundMysql = $true
            'MySQL=3307'
        } else { $line }
    }
    if (-not $foundApache -or -not $foundMysql) { throw 'Configuracao de portas nao reconhecida; nenhum arquivo foi alterado.' }
    $backup = $target + '.point-backup-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
    Copy-Item -LiteralPath $target -Destination $backup
    Set-Content -LiteralPath $target -Value $updated -Encoding ASCII
    Set-Content -LiteralPath $log -Value @('OK: Apache=80; ApacheSSL=443; MySQL=3307.', ('Backup: ' + $backup), 'Reabra o painel do XAMPP para carregar as portas corretas.') -Encoding UTF8
} catch {
    Set-Content -LiteralPath $log -Value ('ERRO: ' + $_.Exception.Message) -Encoding UTF8
    exit 1
}
