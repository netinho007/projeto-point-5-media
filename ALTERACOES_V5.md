# Point Carreiras V5

Versão consolidada do sistema de recrutamento em Laravel + MySQL.

## O que foi incluído

- Verificação de conta por e-mail e telefone.
- Recuperação de senha e aviso de segurança após troca de senha.
- Estrutura para e-mails reais de verificação, candidatura, mudança de etapa, entrevista e mensagens.
- Perfil profissional com foto, currículo, experiências, formação, idiomas, competências, cursos, certificações, projetos e links profissionais.
- Documentos privados e carta de apresentação.
- Preferências de contratação, trabalho, viagens e mudança.
- Mensagens entre RH e candidato com anexos e cópia opcional por e-mail.
- Atalhos de contato por e-mail, telefone e WhatsApp para o RH.
- Dashboard do candidato com completude, candidaturas, vagas salvas, entrevistas e notificações.
- Dashboard do RH com indicadores, etapas, entrevistas, mensagens e estado das integrações.
- Timeline detalhada das candidaturas e confirmação/reagendamento de entrevista pelo candidato.
- Bloqueio de candidatura duplicada e opção de retirada pelo candidato.
- Banco de Talentos com consentimento, prazo, filtros, tags, favoritos e filtros salvos.
- Busca de vagas por cargo, área, local, modelo e contratação.
- Vagas salvas, compartilhamento e oportunidades relacionadas.
- Prazo de publicação/encerramento e estados Rascunho, Publicada, Pausada e Encerrada.
- Sistema de indicação.
- Modelos rápidos de mensagens para o RH.
- Página de carreiras, processo seletivo, dúvidas e privacidade/LGPD.
- Análise assistida do currículo com competências, formação, experiência, idiomas e certificações.
- Foto, aparência e características pessoais não entram na análise.
- Loader animado, layout responsivo, foco visível e melhorias de acessibilidade.
- Rotina automática para encerrar vagas vencidas e retirar consentimentos expirados do Banco de Talentos.
- Interface principal em português.

## Serviços externos

O sistema funciona localmente sem contas externas. Para envio real:

### E-mail / Resend via SMTP
No `.env`, use `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.resend.com`, `MAIL_PORT=587`, `MAIL_USERNAME=resend` e coloque a sua chave Resend em `MAIL_PASSWORD`.

Nunca compartilhe sua chave. Para testar depois de configurar:

```bash
php artisan point:mail-test seuemail@exemplo.com
```

### Telefone / Twilio Verify
Preencha no `.env`:

```env
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_VERIFY_SERVICE_SID=
```

Sem essas credenciais, em ambiente local o sistema gera um código de teste para desenvolvimento.

### WhatsApp
Atualmente existe contato rápido pelo WhatsApp do candidato. A automação oficial pode ser adicionada depois com WhatsApp Business Platform/Meta.

## Atualização segura

Se você já usa o banco `point_media`, preserve o seu `.env` e execute:

```text
ATUALIZAR_PARA_V5.bat
```

O atualizador usa apenas `php artisan migrate` e não apaga os dados.

**Nunca execute `php artisan migrate:fresh` em um banco que deseja preservar.**
