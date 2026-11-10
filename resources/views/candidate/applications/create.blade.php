@extends('layouts.candidate')
@section('title', 'Candidatar-se · '.$vacancy->title)
@section('content')
<div class="page-head"><div><span class="eyebrow">Confirmar candidatura</span><h1>{{ $vacancy->title }}</h1><p>{{ $vacancy->department }} @if($vacancy->location) · {{ $vacancy->location }} @endif</p></div><a class="btn btn-light" href="{{ route('vacancies.show', $vacancy) }}">Voltar</a></div>
@if($existing)
<div class="alert alert-warning">Você já se candidatou para esta vaga. <a class="text-link" href="{{ route('candidate.applications.show',$existing) }}">Acompanhar candidatura</a>.</div>
@else
<form method="POST" action="{{ route('vacancies.apply',$vacancy) }}" enctype="multipart/form-data">@csrf
<div class="portal-grid"><section class="portal-card"><div class="portal-card-head"><h2>Sobre sua candidatura</h2></div><div class="portal-card-body">
<div class="info-list"><div class="info-box"><span>Candidato</span><strong>{{ $candidate->name }}</strong></div><div class="info-box"><span>E-mail</span><strong>{{ $candidate->email }}</strong></div><div class="info-box"><span>Localização</span><strong>{{ $candidate->location_label }}</strong></div><div class="info-box"><span>Perfil</span><strong>{{ $candidate->profile_completion }}% completo</strong></div></div>
<div class="divider"></div><div class="field"><label>Carta de apresentação (opcional)</label><textarea name="cover_letter" maxlength="3000" placeholder="Conte por que esta oportunidade faz sentido para você.">{{ old('cover_letter') }}</textarea></div>
@if($vacancy->referral_enabled)<div class="field"><label>Você foi indicado por alguém? (opcional)</label><input name="referral_name" value="{{ old('referral_name') }}" placeholder="Nome da pessoa que indicou"></div>@endif
<div class="field"><label>Como conheceu esta vaga?</label><select name="source"><option value="site">Site de carreiras</option><option value="linkedin">LinkedIn</option><option value="instagram">Instagram</option><option value="indicacao">Indicação</option><option value="outro">Outro</option></select></div>
<p class="help">A análise automática usa apenas informações profissionais relacionadas à vaga. Foto e características pessoais não entram na análise.</p>
</div></section>
<aside class="stack"><section class="portal-card"><div class="portal-card-head"><h2>Currículo desta vaga</h2></div><div class="portal-card-body">@if($candidate->resume_path)<div class="alert alert-success">Currículo do perfil disponível: <strong>{{ $candidate->resume_original_name }}</strong></div><p class="help">Se não enviar outro arquivo, usaremos uma cópia do currículo do perfil.</p>@else<div class="alert alert-warning">Seu perfil ainda não possui currículo.</div>@endif<div class="field"><label>{{ $candidate->resume_path ? 'Enviar outro currículo (opcional)' : 'Enviar currículo' }}</label><input type="file" name="resume" accept=".pdf,.docx" {{ $candidate->resume_path ? '' : 'required' }}><div class="help">PDF ou DOCX, até 5 MB.</div></div><label class="check-line"><input type="checkbox" name="save_as_profile_resume" value="1"><span>Usar o novo arquivo também como meu currículo principal.</span></label></div></section><button class="btn btn-primary btn-block" type="submit">Enviar candidatura</button></aside></div>
</form>
@endif
@endsection
