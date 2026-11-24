@extends('layouts.public')
@section('title','Processo seletivo · Point Carreiras')
@section('content')
<section class="content-hero"><div class="container"><span class="eyebrow">processo seletivo_</span><h1>Você sabe onde está e o que vem depois.</h1></div></section><section class="public-section"><div class="container process-public">@foreach([['01','Candidate-se','Escolha uma vaga e envie seu currículo.'],['02','Análise','O RH revisa seu perfil e informações profissionais.'],['03','Contato','A equipe pode falar com você pelo portal, e-mail ou telefone.'],['04','Entrevista','Se houver avanço, os detalhes ficam disponíveis no portal.'],['05','Resultado','Você recebe a atualização final do processo.']] as [$n,$t,$d])<article><b>{{ $n }}</b><div><h3>{{ $t }}</h3><p>{{ $d }}</p></div></article>@endforeach</div></section>
@endsection
