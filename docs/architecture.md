# Arquitetura inicial

## Objetivo

Construir um cliente desktop para Linux e Windows, mantendo a experiência e os módulos do sistema legado, mas isolando a aplicação da implementação PHP antiga e do banco de dados.

## Componentes

```text
┌──────────────────────────────┐
│ App desktop Python/PySide6    │
│                              │
│ sessão local + UI             │
└──────────────┬───────────────┘
               │ HTTPS / JSON
               │ Authorization: Bearer <access token>
┌──────────────▼───────────────┐
│ API PHP/Slim /api/v1          │
│                              │
│ autenticação                  │
│ validação                     │
│ regras de negócio             │
│ mapeamento de dados            │
└──────────────┬───────────────┘
               │ PDO preparado
┌──────────────▼───────────────┐
│ MySQL 8 — banco novo           │
└──────────────────────────────┘
```

## Decisões

### Desktop

Python e PySide6 foram escolhidos para produzir uma aplicação desktop multiplataforma sem exigir Node.js no ambiente de desenvolvimento. O código do cliente ficará em `apps/desktop` e a comunicação com a API será isolada em uma camada de cliente.

A API terá endpoints JSON versionados, respostas de erro consistentes e não reutilizará os controllers HTML do legado. A primeira entrega implementa autenticação, health check e inventário; os demais módulos serão adicionados por fatias verticais.

### API

Slim Framework será usado para manter a API pequena e explícita. A API terá controllers, repositories, services de autenticação e middleware de autorização. Não haverá SQL concatenation em código novo.

As requisições e respostas são JSON. A API usa `PDO` com prepared statements, senhas Argon2id, access tokens JWT e refresh tokens opacos, armazenados apenas como hash.

### Banco

A nova aplicação utiliza um banco MySQL novo. O legado será usado como fonte de análise e, quando o schema exportado estiver disponível, como origem de uma migração controlada. Não haverá migração automática de dados de produção sem backup, validação e plano de rollback.

### Segurança

- Senhas com `PASSWORD_ARGON2ID`.
- Access tokens JWT curtos.
- Refresh tokens aleatórios, armazenados apenas como hash no banco e revogáveis.
- Chave JWT somente em variável de ambiente.
- Consultas SQL com prepared statements.
- Validação de payload e de campos no servidor.
- Segredos, logs, uploads e arquivos de build fora do Git.

## Primeiro fluxo

1. Abrir a tela de login.
2. Autenticar na API e receber access/refresh tokens.
3. Armazenar tokens no cofre seguro do sistema operacional quando disponível.
4. Abrir a tela de inventário.
5. Listar itens paginados.
6. Cadastrar um item.
7. Registrar entradas e saídas de estoque em uma etapa posterior.

## Evolução

A ordem recomendada é:

1. health check e autenticação;
2. inventário e movimentações;
3. obras e vínculos de equipe;
4. liberações de serviço;
5. concreto e rompimentos;
6. kits, impressão, etiquetas e relatórios;
7. modo offline seletivo, com fila de operações e resolução de conflitos.

## Estado da primeira entrega

A base inicial contém:

- API Slim com health check, login, refresh, logout e perfil;
- schema MySQL novo para usuários, tokens e inventário;
- repositories com prepared statements;
- cliente PySide6 com login, sessão local e listagem de inventário;
- testes unitários de API e cliente;
- CI para PHP e Python;
- Dockerfile e Compose para subir MySQL + API.

Ainda não são incluídos dados reais do legado, uploads, impressão, obras, liberações ou concreto. Essas funcionalidades serão migradas por etapas.
