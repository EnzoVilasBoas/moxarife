# API Moxarife

API PHP baseada em Slim Framework, organized em controllers, repositories e services.

## Executar localmente

1. Copie `services/api/.env.example` para `services/api/.env` quando executar a API diretamente no host.
2. Ajuste as credenciais do MySQL e gere um `JWT_SECRET` forte.
3. Instale as dependências com `composer install`.
4. Sirva a pasta `public` com um servidor PHP ou Docker.

O endpoint de saúde é `GET /health`. As rotas versionadas começam em `/api/v1`.

Para hospedagem compartilhada, publique o conteúdo de `services/api/public` na raiz do domínio e mantenha `src/`, `vendor/` e `.env` no diretório pai da raiz pública. O `.htaccess` incluído em `public/` encaminha as rotas para `index.php`. O `public/index.php` aceita as duas organizações: com a API no diretório pai da raiz pública ou com todos os arquivos da API diretamente na raiz.

O banco novo é inicializado por `database/migrations/001_initial.sql`. O banco legado não deve ser conectado a esta API.
