# Moxarife Desktop

Cliente desktop multiplataforma em Python/PySide6.

Ele conversa somente com a API PHP. O primeiro fluxo contém login e listagem de inventário.

## Desenvolvimento

```bash
python3 -m venv .venv
. .venv/bin/activate
pip install -r requirements.txt
python main.py
```

A URL da API pode ser alterada por `MOXARIFE_API_URL`.

## Testes

```bash
python -m unittest discover -s tests -v
```

## Empacotamento

```bash
python -m pip install pyinstaller
python -m PyInstaller --clean --noconfirm moxarife.spec
```

O executável é colocado em `dist/`. Tokens de sessão são mantidos no keyring do sistema operacional quando disponível.
