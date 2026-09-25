# Moxarife

Base do novo Moxarife: um aplicativo desktop para Linux e Windows conectado a uma API PHP.

## Arquitetura

- **Desktop:** Python + PySide6
- **API:** PHP 8.3 + Slim Framework
- **Banco:** MySQL 8 em um banco novo, separado do legado
- **Comunicação:** HTTPS + JSON em `/api/v1`
- **Autenticação:** tokens de acesso curtos e refresh tokens revogáveis

O aplicativo desktop nunca acessa o banco diretamente. A API é a única camada responsável por autenticação, validação, regras de negócio e persistência.

## Estrutura

```text
apps/desktop/       Cliente executável
services/api/       API PHP
database/           Schema e migrations
docs/               Arquitetura e análise do legado
```

## Desenvolvimento local

### 1. Banco e API com Docker

```bash
cp .env.example .env
# Edite .env e defina DB_PASSWORD, MYSQL_ROOT_PASSWORD e um JWT_SECRET forte.
docker compose up --build
```

A API ficará disponível em `http://127.0.0.1:8080` e o health check em `http://127.0.0.1:8080/health`.

O schema inicial é montado automaticamente pelo MySQL na primeira criação do volume. O volume é reutilizado nas próximas execuções; para recomeçar do zero durante o desenvolvimento, execute `docker compose down -v` somente após confirmar que não há dados que precise preservar.

### 2. Criar o primeiro usuário

```bash
docker compose exec api php bin/create-user.php admin@example.com "Administrador"
```

O script solicita a senha no terminal e armazena somente o hash Argon2id. Alternativamente, defina `MOXARIFE_INITIAL_PASSWORD` no ambiente do comando para evitar a digitação interativa.

### 3. Aplicativo desktop

```bash
cd apps/desktop
python3 -m venv .venv
. .venv/bin/activate
pip install -r requirements.txt
python main.py
```

No Windows PowerShell, ative o ambiente com `.venv\Scripts\Activate.ps1`.

Por padrão, o desktop procura a API publicada em `https://utilidades.enzovilasboas.com.br/api/v1`. A URL pode ser alterada pela variável `MOXARIFE_API_URL` para desenvolvimento local.

## Empacotamento

```bash
cd apps/desktop
python -m PyInstaller --clean --noconfirm moxarife.spec
```

Os artefatos são gerados em `apps/desktop/dist/` e não são versionados.

## Segurança do material legado

O ZIP original contém um arquivo `.env` com credenciais aparentes. Esses valores não fazem parte deste repositório. Revogue e gere novas credenciais do banco e do serviço de e-mail antes de usar qualquer cópia do legado em um ambiente novo.

## Documentação

- `docs/architecture.md` — decisões e desenho da solução
- `docs/legacy-analysis.md` — análise do sistema anterior
- `docs/api-v1.md` — contrato inicial da API
