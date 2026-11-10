<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreatePointAdmin extends Command
{
    protected $signature = 'point:create-admin {email?}';
    protected $description = 'Cria ou atualiza um administrador do Point Carreiras';

    public function handle(): int
    {
        $email = mb_strtolower((string) ($this->argument('email') ?: $this->ask('E-mail do administrador')));
        $name = (string) $this->ask('Nome', 'Administrador');
        $password = (string) $this->secret('Senha (mínimo 8 caracteres)');

        if (mb_strlen($password) < 8) {
            $this->error('A senha precisa ter pelo menos 8 caracteres.');
            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = Hash::make($password);
        $user->is_admin = true;
        $user->email_verified_at = now();
        $user->save();

        $this->info('Administrador criado/atualizado com sucesso.');
        return self::SUCCESS;
    }
}
