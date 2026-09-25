# Contrato inicial da API v1

Base URL de desenvolvimento: `http://127.0.0.1:8080/api/v1`

Todas as respostas de erro seguem o formato:

```json
{
  "error": {
    "code": "validation_error",
    "message": "Verifique os campos informados.",
    "details": {}
  }
}
```

## Saúde

### `GET /health`

Não exige autenticação. Retorna o estado da API e do banco.

## Autenticação

### `POST /auth/login`

Request:

```json
{
  "email": "usuario@example.com",
  "password": "uma-senha-forte"
}
```

Response `200`:

```json
{
  "data": {
    "access_token": "...",
    "refresh_token": "...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
      "id": 1,
      "name": "Usuário",
      "email": "usuario@example.com",
      "role": "operator"
    }
  }
}
```

### `POST /auth/refresh`

Recebe `refresh_token` e devolve um novo par de tokens.

### `POST /auth/logout`

Recebe `refresh_token` e o revoga.

### `GET /auth/me`

Exige `Authorization: Bearer <access_token>`.

## Inventário

Todas as rotas abaixo exigem access token.

- `GET /inventory/items?page=1&per_page=20&search=`
- `GET /inventory/items/{id}`
- `POST /inventory/items`
- `PATCH /inventory/items/{id}`
- `DELETE /inventory/items/{id}` (desativação lógica)
- `GET /inventory/items/{id}/movements`
- `POST /inventory/movements`

Exemplo de item:

```json
{
  "code": "MAT-001",
  "name": "Cimento",
  "unit": "sc",
  "description": "CimentoPortland",
  "minimum_quantity": 10
}
```

Exemplo de movimentação:

```json
{
  "item_id": 1,
  "type": "entry",
  "quantity": 25,
  "note": "Recebimento"
}
```
