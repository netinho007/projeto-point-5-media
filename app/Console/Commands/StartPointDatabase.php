<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use PDOException;

class StartPointDatabase extends Command
{
    protected $signature = 'point:database-start {--check : Apenas verifica a conexao}';

    protected $description = 'Verifica o banco e inicia o MySQL local do XAMPP quando estiver parado';

    public function handle(): int
    {
        $connection = DB::connection();

        try {
            $connection->getPdo();
            $this->info('Conexao com o banco de dados confirmada.');

            return self::SUCCESS;
        } catch (PDOException $exception) {
            $error = $exception;
        }

        $config = $connection->getConfig();

        if ($this->option('check') || ($error->errorInfo[1] ?? null) !== 2002) {
            $this->error('Nao foi possivel conectar ao banco. Confira o host, a porta e as credenciais no .env.');

            return self::FAILURE;
        }

        if (PHP_OS_FAMILY !== 'Windows'
            || ! in_array($config['driver'], ['mysql', 'mariadb'], true)
            || ! in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
            $this->error('Inicie o servidor de banco configurado no .env e tente novamente.');

            return self::FAILURE;
        }

        $binary = 'C:\\xampp\\mysql\\bin\\mysqld.exe';
        $ini = 'C:\\xampp\\mysql\\bin\\my.ini';

        if (! is_file($binary) || ! is_file($ini)) {
            $this->error('MySQL do XAMPP nao encontrado em C:\\xampp. Inicie seu banco manualmente.');

            return self::FAILURE;
        }

        $port = filter_var($config['port'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        if ($port === false) {
            $this->error('DB_PORT no .env precisa ser uma porta valida.');

            return self::FAILURE;
        }

        $quote = static fn (string $value): string => "'".str_replace("'", "''", $value)."'";
        $script = 'Start-Process -FilePath '.$quote($binary)
            .' -ArgumentList @('.$quote('--defaults-file='.$ini).', '.$quote('--port='.$port).", '--standalone', '--console')"
            .' -WorkingDirectory '.$quote(dirname($binary))
            .' -WindowStyle Hidden -ErrorAction Stop'
            .' -RedirectStandardOutput '.$quote(storage_path('logs/mysql-startup.log'))
            .' -RedirectStandardError '.$quote(storage_path('logs/mysql-startup-error.log'));

        $this->line('Iniciando o MySQL do XAMPP na porta '.$config['port'].'...');
        $result = Process::timeout(15)->run(['powershell.exe', '-NoProfile', '-NonInteractive', '-Command', $script]);

        if (! $result->successful()) {
            $this->error('Nao foi possivel iniciar o MySQL. Confira o painel do XAMPP.');

            return self::FAILURE;
        }

        $deadline = microtime(true) + 20;

        do {
            $connection->disconnect();

            try {
                $connection->getPdo();
                $this->info('MySQL iniciado e conexao com o banco confirmada.');

                return self::SUCCESS;
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) !== 2002) {
                    $this->error('O MySQL iniciou, mas o acesso ao banco falhou. Confira as credenciais e DB_DATABASE no .env.');

                    return self::FAILURE;
                }
            }

            usleep(250000);
        } while (microtime(true) < $deadline);

        $this->error('O MySQL nao ficou disponivel. Confira storage/logs/mysql-startup-error.log e os logs do XAMPP.');

        return self::FAILURE;
    }
}
