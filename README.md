# Point Carreiras V5

Sistema de recrutamento desenvolvido em Laravel 12 + MySQL, com site público de vagas, portal do candidato e painel do RH.

## Instalação nova

1. Crie o banco MySQL `point_media`.
2. Extraia o projeto.
3. Execute `INSTALAR_PROJETO.bat`.
4. Crie o administrador com `php artisan point:create-admin`.
5. Inicie com `php artisan serve`.

## Atualização de uma versão anterior

Copie/preserve o seu `.env` e execute `ATUALIZAR_PARA_V5.bat`.

Não use `migrate:fresh` para atualizar, pois esse comando apaga as tabelas.

## Principais módulos

### Candidato
Cadastro, login, verificação de e-mail e telefone, recuperação/troca de senha, foto de perfil, currículo, experiências, formação, idiomas, competências, cursos, certificações, projetos, documentos, Banco de Talentos, vagas salvas, candidaturas, timeline, entrevistas, notificações, mensagens, preferências de comunicação e controles de privacidade.

### RH / Administrador
Vagas, candidatos, candidaturas, análise assistida, entrevistas, mensagens, modelos de mensagem, Banco de Talentos, tags, favoritos, filtros salvos, indicadores, auditoria e atalhos de contato.

### Site público
Busca e filtros de vagas, detalhes, compartilhamento, oportunidades relacionadas, página de carreiras, processo seletivo, dúvidas frequentes e privacidade.

## E-mail e telefone

Por padrão, o envio de e-mail pode permanecer em `MAIL_MAILER=log` durante desenvolvimento. Para envio real, configure SMTP/Resend no `.env`.

A verificação por telefone usa Twilio Verify quando as credenciais estão configuradas. Em desenvolvimento local, pode trabalhar em modo de teste.

## Análise de currículo

A análise é apenas apoio ao recrutador. Ela utiliza conteúdo profissional do currículo/perfil e requisitos objetivos da vaga. Foto e características pessoais não são utilizadas para pontuação ou decisão. A decisão final permanece humana.

## Rotina de prazos

O comando `php artisan point:maintenance` encerra vagas com prazo vencido e trata expiração do Banco de Talentos. O Laravel Scheduler está configurado para executá-lo a cada hora quando o scheduler estiver ativo no servidor.
