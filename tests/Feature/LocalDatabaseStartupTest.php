<?php

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Mockery;
use PDO;
use PDOException;
use Tests\TestCase;

class LocalDatabaseStartupTest extends TestCase
{
    public function test_available_database_does_not_start_another_process(): void
    {
        Process::fake();
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getPdo')->once()->andReturn(new PDO('sqlite::memory:'));
        DB::shouldReceive('connection')->once()->andReturn($connection);

        $this->artisan('point:database-start')
            ->expectsOutput('Conexao com o banco de dados confirmada.')
            ->assertSuccessful();

        Process::assertNothingRan();
    }

    public function test_check_mode_never_starts_a_database(): void
    {
        Process::fake();
        $this->mockUnavailableDatabase();

        $this->artisan('point:database-start', ['--check' => true])->assertFailed();

        Process::assertNothingRan();
    }

    public function test_remote_database_failure_does_not_start_local_mysql(): void
    {
        Process::fake();
        $this->mockUnavailableDatabase(host: 'database.example.com');

        $this->artisan('point:database-start')
            ->expectsOutput('Inicie o servidor de banco configurado no .env e tente novamente.')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_authentication_failure_does_not_start_another_server(): void
    {
        Process::fake();
        $this->mockUnavailableDatabase(errorCode: 1045);

        $this->artisan('point:database-start')->assertFailed();

        Process::assertNothingRan();
    }

    private function mockUnavailableDatabase(string $host = '127.0.0.1', int $errorCode = 2002): void
    {
        $exception = new PDOException('Database unavailable');
        $exception->errorInfo = ['HY000', $errorCode, 'Database unavailable'];
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getPdo')->once()->andThrow($exception);
        $connection->shouldReceive('getConfig')->once()->andReturn([
            'driver' => 'mysql',
            'host' => $host,
            'port' => 3307,
        ]);
        DB::shouldReceive('connection')->once()->andReturn($connection);
    }
}
