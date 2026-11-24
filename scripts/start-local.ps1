param([switch]$Stop)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $root
$logDirectory = Join-Path $root 'storage\logs'
$stateFile = Join-Path $logDirectory 'point-processes.json'
$artisan = Join-Path $root 'artisan'
$server = Join-Path $root 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
$php = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $php -and (Test-Path -LiteralPath 'C:\xampp\php\php.exe')) { $php = 'C:\xampp\php\php.exe' }
if (-not $php) { throw 'PHP nao encontrado.' }
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$saved = if (Test-Path -LiteralPath $stateFile) { @(Get-Content -LiteralPath $stateFile -Raw | ConvertFrom-Json) } else { @() }

function Test-OwnedProcess($entry) {
    $process = Get-CimInstance Win32_Process -Filter "ProcessId=$($entry.id)" -ErrorAction SilentlyContinue
    if ($entry.name -eq 'site' -and $entry.command -eq '-S 127.0.0.1:8000') {
        return Test-SiteProcess $process
    }
    return $process -and $process.Name -eq 'php.exe' -and $process.CommandLine -like ('*' + $artisan + '*') -and $process.CommandLine -like ('*' + $entry.command + '*')
}

function Test-SiteProcess($process) {
    if (-not $process -or $process.Name -ne 'php.exe') { return $false }
    $commandLine = $process.CommandLine
    if ($commandLine -notlike '* -S 127.0.0.1:8000 *' -or $commandLine -notmatch '"(?<router>[^"]+server\.php)\s*"') { return $false }
    $router = [System.IO.Path]::GetFullPath($Matches.router.Trim())
    return $router -eq $server
}

$siteListener = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
if ($siteListener -and -not ($saved | Where-Object { $_.name -eq 'site' -and (Test-OwnedProcess $_) })) {
    $siteProcess = Get-CimInstance Win32_Process -Filter "ProcessId=$($siteListener.OwningProcess)" -ErrorAction SilentlyContinue
    if (Test-SiteProcess $siteProcess) {
        $saved += @{name='site'; command='-S 127.0.0.1:8000'; id=$siteProcess.ProcessId}
    } elseif (-not $Stop) {
        throw 'A porta 8000 esta ocupada por outro processo. Nenhum servidor de outro projeto foi encerrado.'
    }
}

if ($Stop) {
    foreach ($entry in $saved) {
        if (Test-OwnedProcess $entry) {
            Get-CimInstance Win32_Process -Filter "ParentProcessId=$($entry.id)" | Where-Object {
                $_.Name -eq 'php.exe' -and $_.CommandLine -like ('*' + $root + '*')
            } | ForEach-Object { Stop-Process -Id $_.ProcessId -ErrorAction SilentlyContinue }
            Stop-Process -Id $entry.id -ErrorAction SilentlyContinue
        }
    }
    Write-Host 'Servicos PHP iniciados por este script foram encerrados. MySQL e Ollama foram mantidos.'
    exit
}

& $php artisan point:database-start
if ($LASTEXITCODE -ne 0) { throw 'Banco indisponivel. Confira DB_PORT=3307 e o MySQL do XAMPP.' }
& $php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Nao foi possivel atualizar a estrutura do banco.' }

try { $null = Invoke-RestMethod 'http://127.0.0.1:11434/api/tags' -TimeoutSec 3 } catch {
    $ollama = Join-Path $env:LOCALAPPDATA 'Programs\Ollama\ollama.exe'
    if (Test-Path -LiteralPath $ollama) {
        Start-Process -FilePath $ollama -ArgumentList 'serve' -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logDirectory 'ollama.log') -RedirectStandardError (Join-Path $logDirectory 'ollama-error.log')
        Start-Sleep -Seconds 3
    }
}
& $php artisan point:ai-check
if ($LASTEXITCODE -ne 0) { Write-Warning 'O site abrira, mas a analise precisa do Ollama e do modelo qwen3:4b.' }

$services = @(
    @{name='site'; command='-S 127.0.0.1:8000'; args=@('-S','127.0.0.1:8000',('"' + $server + '"')); directory=(Join-Path $root 'public')},
    @{name='ia'; command='queue:work analysis'; args=@('queue:work','analysis','--queue=resumes','--sleep=2','--tries=1','--timeout=600')},
    @{name='notificacoes'; command='queue:work --queue=default'; args=@('queue:work','--queue=default','--sleep=2','--tries=1','--timeout=60')},
    @{name='scheduler'; command='schedule:work'; args=@('schedule:work')}
)
$current = @()
foreach ($service in $services) {
    $existing = $saved | Where-Object { $_.name -eq $service.name -and (Test-OwnedProcess $_) } | Select-Object -First 1
    if ($existing) { $current += $existing; continue }
    $arguments = if ($service.name -eq 'site') { $service.args } else { @('"' + $artisan + '"') + $service.args }
    $directory = if ($service.directory) { $service.directory } else { $root }
    $process = Start-Process -FilePath $php -ArgumentList $arguments -WorkingDirectory $directory -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $logDirectory ($service.name + '.log')) -RedirectStandardError (Join-Path $logDirectory ($service.name + '-error.log'))
    $current += @{name=$service.name; command=$service.command; id=$process.Id}
}
$current | ConvertTo-Json -Depth 3 | Set-Content -LiteralPath $stateFile -Encoding UTF8
Start-Sleep -Seconds 3
$response = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8000/' -TimeoutSec 15
if ($response.StatusCode -ne 200 -or $response.Content -notmatch 'Point Carreiras') { throw 'O site nao respondeu corretamente. Consulte storage\logs\site-error.log.' }
Write-Host 'Site: http://127.0.0.1:8000'
Write-Host 'Admin: http://127.0.0.1:8000/admin/login'
Write-Host 'IA, notificacoes e scheduler executam em segundo plano. Logs em storage\logs.'
