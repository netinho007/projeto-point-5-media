# Point Carreiras V5

Sistema de recrutamento desenvolvido em Laravel 12 + MySQL, com site público de vagas, portal do candidato e painel do RH.

## Instalação nova

1. Crie o banco MySQL `point_media`.
2. Extraia o projeto.
3. Execute `INSTALAR_PROJETO.bat`.
4. Crie o administrador com `php artisan point:create-admin`.
5. Inicie com `INICIAR_PROJETO.bat` no Windows. Ele verifica o banco, inicia o MySQL local do XAMPP se estiver parado, aplica migrations pendentes e inicia servidor, filas e scheduler em segundo plano.

Para encerrar os serviços PHP abertos pelo iniciador, use `PARAR_PROJETO.bat`. Ele não encerra outros projetos, o MySQL ou o Ollama. Os logs ficam em `storage/logs`.

O MySQL do XAMPP em `C:\xampp\mysql\bin\my.ini` deve usar a mesma porta de `DB_PORT` no `.env`. No painel do XAMPP, em **Config > Service and Port Settings**, configure a porta do MySQL na aba **MySQL**. A porta principal do Apache continua sendo 80.

Para conferir a conexão sem iniciar nenhum serviço, execute `php artisan point:database-start --check`.

Neste computador, o XAMPP usa **3307**, pois outro MySQL ocupa **3306**. Não apague arquivos de `mysql/data` nem use `migrate:fresh`. Para alinhar também o painel do XAMPP, feche o painel, execute `CORRIGIR_PORTAS_XAMPP.bat` e aceite a permissão de administrador. O script cria um backup e altera apenas `ServicePorts`: Apache=80 e MySQL=3307. Reabra o painel depois.

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

Com `QUEUE_CONNECTION=database`, mantenha a fila ativa com `php artisan queue:listen --tries=1 --timeout=60` ou use `INICIAR_PROJETO.bat`. Recuperação de senha, verificação de e-mail e notificações do processo são enviadas pela fila para evitar travar a navegação quando o SMTP demora.

A verificação por telefone usa Twilio Verify quando as credenciais estão configuradas. Em desenvolvimento local, pode trabalhar em modo de teste.

## Análise de currículo

A análise agora usa um modelo real local, **Ollama + qwen3:4b**, sem chave de API. Os currículos não são enviados a um serviço de IA externo; a integração aceita apenas endereços locais e não segue redirecionamentos. O download inicial do programa/modelo precisa de internet. Depois, a inferência funciona localmente.

1. Instale o [Ollama para Windows](https://ollama.com/download/windows).
2. Abra o Ollama e execute `ollama pull qwen3:4b` uma vez.
3. Confirme com `php artisan point:ai-check`.
4. Inicie o projeto com `INICIAR_PROJETO.bat`.
5. Envie uma candidatura com PDF de texto ou DOCX. A análise entra automaticamente na fila `resumes`.
6. No painel do RH, abra **Análises assistidas** e a candidatura. O resultado mostra resumo, competências, citações por requisito, formação/experiência, perguntas para entrevista e limites.

A fila de IA usa a conexão persistente `analysis`, independentemente de `QUEUE_CONNECTION`. Para iniciá-la manualmente: `php artisan queue:work analysis --queue=resumes --tries=1 --timeout=600`. Não use um worker de 60 segundos para essa fila. O retry da conexão é 720 segundos.

Configuração opcional no `.env`: `RESUME_AI_URL=http://127.0.0.1:11434`, `RESUME_AI_MODEL=qwen3:4b` e `RESUME_AI_TIMEOUT=480`. Em outro computador, ajuste o modelo somente após testar qualidade e velocidade.

A pontuação é calculada pelo servidor: requisito comprovado vale 1; parcial vale 0,5; não identificado vale 0. Evidências sem uma citação presente no texto são descartadas. A porcentagem não representa chance de contratação. Requisitos pessoais são excluídos da comparação, e o modelo é instruído a ignorar características protegidas. A IA pode errar: a decisão final e a revisão das evidências permanecem humanas. As competências do perfil e a etapa pública da candidatura não são alteradas pela IA.

Limites atuais: PDFs escaneados precisam de OCR (não implementado), arquivos até 5 MB, até 20 requisitos e 24 mil caracteres profissionais por análise. Truncamentos aparecem no relatório. No computador de desenvolvimento, os testes reais levaram aproximadamente 79 a 150 segundos; documentos maiores e outros computadores podem demorar mais.

Resultados anteriores por palavras-chave ficam identificados como antigos. Execute **Reanalisar** para substituí-los por uma análise local real.

Currículo fictício para testar: `output/pdf/curriculo-demonstracao.pdf`. `php artisan point:ai-check --demo` lê esse PDF, executa a IA e exibe o resultado, sem criar candidatos ou candidaturas no banco.

Guia de apresentação: [docs/APRESENTACAO.md](docs/APRESENTACAO.md).

## Rotina de prazos

O comando `php artisan point:maintenance` encerra vagas com prazo vencido e trata expiração do Banco de Talentos. O Laravel Scheduler está configurado para executá-lo a cada hora quando o scheduler estiver ativo no servidor.
