# Apresentação do Point Carreiras

## Antes de apresentar

1. Execute `INICIAR_PROJETO.bat` e confira as mensagens de banco e modelo disponível.
2. Abra http://127.0.0.1:8000 e http://127.0.0.1:8000/admin/login.
3. Entre com sua conta de administrador. Para redefinir o acesso local, use `php artisan point:create-admin seu-email` e informe a nova senha no terminal. Senhas não ficam disponíveis em texto aberto.
4. Crie uma vaga identificada como **Demonstração**, com prazo futuro. Vagas encerradas ou com prazo vencido não aparecem no site público.
5. Cadastre os requisitos em linhas separadas. Exemplo:

```text
Experiência com Laravel
5 anos de experiência em Laravel
Conhecimento em MySQL
Inglês fluente
Experiência com Docker
```

6. Com uma conta normal já verificada, envie `output/pdf/curriculo-demonstracao.pdf` para a vaga de demonstração.
7. Abra a candidatura no admin e aguarde o resultado. Neste computador, uma análise real do PDF levou cerca de 150 segundos. Faça uma análise antes da apresentação e deixe o relatório pronto para consultar; depois demonstre também o envio de uma nova candidatura.

## Sequência sugerida

1. Site público: busca, filtros e detalhes da vaga.
2. Conta normal: perfil profissional, envio do currículo e acompanhamento da candidatura.
3. Admin: lista de candidaturas, leitura do documento privado e análise com evidências.
4. Mostre que citar Laravel não comprova cinco anos, e inglês intermediário não comprova fluência.
5. Recrutador: atualização manual da etapa, mensagem e agendamento de entrevista.
6. Conta normal: nova etapa, mensagem e resposta da entrevista.
7. Alterne os temas e abra o menu no celular.

## O que é real e o que é demonstração

- A IA é real, local e gratuita por uso. O Ollama e o modelo estão instalados neste computador. Não exige uma chave de API.
- O currículo fornecido é fictício. Nenhuma pessoa real está representada nele.
- A IA ajuda a revisar informações profissionais. Não aprova ou rejeita candidatos automaticamente e pode errar.
- PDFs com texto e DOCX são suportados. OCR para documentos escaneados não está implementado.
- E-mails dependem da configuração SMTP e da autorização do IP na Brevo. Teste entrega antes da apresentação, incluindo spam.
- Códigos de telefone em modo local são apenas testes. SMS real depende de um provedor configurado; não é uma integração gratuita pronta para produção.
- O site funciona neste computador. Para demonstrar em outro, instale PHP/extensões, banco, dependências, Ollama/modelo e configure `.env` antes.

## Diagnóstico rápido

```powershell
php artisan point:database-start --check
php artisan point:ai-check
php artisan point:ai-check --demo
composer test
```

Se a análise ficar na fila, confira `storage/logs/ia.log` e `ia-error.log` e inicie o projeto novamente. Resultados com mais de 15 minutos pendentes podem ser reenviados pelo botão Reanalisar.

O banco usa 3307; o Apache usa 80/443; o site Laravel usa 8000; o Ollama usa 11434. O iniciador não para o outro MySQL da porta 3306 e não apaga tabelas.

Se o painel do XAMPP ainda mostrar as portas erradas, feche-o, execute `CORRIGIR_PORTAS_XAMPP.bat`, aceite a permissão de administrador e reabra-o. O iniciador do projeto já usa a porta correta independentemente desse ajuste visual.

Para publicar na internet, desligue `APP_DEBUG`, use HTTPS, proteja o banco e faça uma revisão específica de implantação e dados pessoais. Esta configuração foi preparada para apresentação local.
