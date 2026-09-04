# PG Estética — Plataforma Clínica PHP + MySQL

Evolução do site institucional para uma plataforma de gestão da clínica, mantendo a identidade visual da logomarca em champagne, dourado, bronze, rosé e marrom.

## Stack
- PHP 8.1+ (PDO, cURL, Fileinfo)
- MySQL 8+
- HTML5 + CSS3 + JavaScript nativo
- Mercado Pago Checkout Pro via Preferences API
- Webhooks Mercado Pago com validação HMAC-SHA256 quando `MP_WEBHOOK_SECRET` está configurado
- Integração opcional com WhatsApp Cloud API

## Funcionalidades
- Site responsivo com serviços e pacotes promocionais
- Agenda com horários realmente disponíveis conforme jornada, duração do serviço, bloqueios e agendamentos existentes
- Cadastro automático e administrativo de clientes
- Confirmação de agendamento por link individual
- WhatsApp: botão de contato, link direto por cliente e envio automático quando a Cloud API estiver configurada
- Lembrete automático de consulta via script de cron
- Ficha de anamnese por cliente
- Histórico de procedimentos, produtos/técnicas e recomendações
- Upload de fotos de antes/depois com marcação de consentimento de publicação
- Pacotes promocionais e vínculo do pacote ao cliente
- Checkout de pacotes com Mercado Pago
- Webhook para atualizar pagamentos, ativar pacote e lançar receita financeira
- Painel financeiro com receitas, despesas e resultado mensal
- Gestão de horários de funcionamento e bloqueios da agenda
- Painel administrativo com dashboard
- CSRF, prepared statements, password_hash/password_verify e validação de upload

## Instalação rápida
1. Importe o banco (o arquivo recria as tabelas):
   ```bash
   mysql -u root -p < database/schema.sql
   ```
2. Configure as variáveis do arquivo `.env.example` no Apache/Nginx/PHP-FPM, Docker, Supervisor ou ambiente de hospedagem. Para desenvolvimento local, copie o arquivo para `.env`; a configuração de banco carrega esse arquivo automaticamente.
3. Garanta permissão de escrita apenas em `uploads/before_after/`:
   ```bash
   chmod 775 uploads/before_after
   ```
4. Rode localmente:
   ```bash
   php -S localhost:8000
   ```
5. Acesse `http://localhost:8000` e `/admin/login.php`.

### Banco local desta instalação

O arquivo `.env` local aponta para uma instância MySQL de desenvolvimento na porta `3307`, com o banco `pg_estetica` já importado. Não use as credenciais padrão `root` sem senha na porta `3306`, pois elas pertencem ao MySQL do sistema e podem não ter acesso. Em outra máquina, crie um usuário próprio para a aplicação e preencha `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env`.

## Acesso administrativo inicial
- E-mail: `admin@pgestetica.local`
- Senha: `Admin@123`

Troque a senha e as credenciais antes da produção.

## Mercado Pago
Defina `APP_URL` com uma URL pública HTTPS e configure:
- `MP_ACCESS_TOKEN`
- `MP_PUBLIC_KEY`
- `MP_WEBHOOK_SECRET`
- `MP_SANDBOX=1` em testes e `0` em produção

O checkout é criado em `actions/package_checkout.php`. O webhook fica em:
`https://seu-dominio/webhooks/mercadopago.php`

O sistema usa uma preferência nova para cada compra de pacote, salva `external_reference`, `preference_id`, `provider_payment_id` e atualiza o financeiro quando o pagamento chega como aprovado.

## WhatsApp e confirmação automática
Sem credenciais da API, o sistema mantém links `wa.me`. Para envio automático, configure as credenciais da WhatsApp Cloud API no ambiente.

O agendamento público gera um `confirmation_token` e envia a URL `/confirm.php?token=...`. Ao abrir o link, o status passa de `pendente` para `confirmado`.

Para lembretes, execute a cada hora:
```cron
0 * * * * /usr/bin/php /var/www/pg_estetica/cron/reminders.php >> /var/log/pg-estetica-reminders.log 2>&1
```
O script procura consultas confirmadas entre 20 e 28 horas no futuro e registra `reminder_sent_at` após envio bem-sucedido.

> Para mensagens iniciadas pela empresa fora da janela permitida pelo WhatsApp, configure e aprove templates na Meta e preencha `WA_TEMPLATE_REMINDER` / `WA_TEMPLATE_CONFIRMATION`.

## LGPD / prontuário
A ficha de anamnese contém dados pessoais e de saúde. Em produção, use HTTPS, contas individuais por funcionário, menor privilégio, backups criptografados, política de retenção, logs de acesso, termo de consentimento adequado e controles de acesso no servidor. O checkbox incluído no sistema é apenas um registro técnico e não substitui a análise jurídica da clínica.

## Diretórios principais
- `admin/` — gestão da clínica
- `actions/` — agendamento, disponibilidade e checkout
- `webhooks/` — notificações externas
- `cron/` — automações de lembrete
- `uploads/before_after/` — imagens clínicas
- `database/schema.sql` — banco completo
- `config/` — banco, app e integrações

## Produção
Use Nginx/Apache + PHP-FPM, HTTPS obrigatório, credenciais fora do repositório, desative exibição de erros, configure backups e limite acesso ao painel administrativo. Não publique o projeto com a senha padrão.
