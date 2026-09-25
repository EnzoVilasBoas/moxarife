# API Moxarife

API PHP baseada em Slim Framework, organized em controllers, repositories e services.

## Executar localmente

1. Copie `services/api/.env.example` para `services/api/.env` quando executar a API diretamente no host.
2. Ajuste as credenciais do MySQL e gere um `JWT_SECRET` forte.
3. Instale as dependências com `composer install`.
4. Sirva a pasta `public` com um servidor PHP ou Docker.

O endpoint de saúde é `GET /health`. As rotas versionadas começam em `/api/v1`.

O banco novo é inicializado por `database/migrations/001_initial.sql`. O banco legado não deve ser conectado a esta API.
