<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('message_templates') || DB::table('message_templates')->exists()) return;

        $now = now();
        DB::table('message_templates')->insert([
            ['name'=>'Convite para entrevista','channel'=>'portal','subject'=>'Convite para entrevista','body'=>'Olá! Gostaríamos de avançar com sua candidatura e conversar com você em uma entrevista. Confira os detalhes no seu portal.','active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Solicitar disponibilidade','channel'=>'portal','subject'=>'Disponibilidade para conversa','body'=>'Olá! Seu perfil chamou nossa atenção. Você poderia nos informar sua disponibilidade para uma conversa com o time de recrutamento?','active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Agradecimento','channel'=>'portal','subject'=>'Obrigado pela candidatura','body'=>'Obrigado pelo interesse e pelo tempo dedicado ao nosso processo seletivo. Seu perfil está sendo acompanhado pela equipe de recrutamento.','active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Processo encerrado','channel'=>'portal','subject'=>'Atualização do processo seletivo','body'=>'Agradecemos sua participação. Neste momento, o processo desta oportunidade foi encerrado para o seu perfil. Esperamos conversar novamente em futuras oportunidades.','active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ]);
    }

    public function down(): void {}
};
