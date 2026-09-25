# Análise do sistema legado

## Formato

O material em `/home/enzovilas/Documentos/moxarifePHP` é uma aplicação PHP web procedural, extraída do arquivo `moxarifePHP.zip`.

- `index.php`: entrada autenticada e layout principal.
- `Sistema::getHome()`: roteia a URL para `controller/<rota>.php`.
- `Sistema::api()`: reutiliza controllers quando a última parte da URL é `api`.
- `controller/`: regras de presentation e orquestração.
- `model/`: classes de acesso a dados por `mysqli`.
- `view/`: páginas PHP renderizadas no servidor.
- `config/.env`: configuração local, incluída no material original.
- `assets/PhpSpreadsheet/`: biblioteca de planilhas.
- `model/utils/mail/`: biblioteca de e-mail.

## Módulos identificados

- autenticação e perfil;
- inventário interno e externo;
- movimentações de estoque e importação CSV/XLS;
- obras, serviços e equipe;
- vínculos entre usuários e obras;
- liberações por quadra/lote/etapa;
- concreto e rompimentos;
- kits;
- impressão, etiquetas e relatórios.

## Tabelas e views inferidas

O código referencia, entre outras, `users`, `itens`, `movimentacoes`, `obras`, `concreto`, `kits`, `vinculos_obras`, `menu_categories`, `menu_items`, `liberacoes`, `liberacoes_servico`, `resumo_estoque`, `view_vinculos_obras` e `view_liberacoes`.

Não há um dump de schema no ZIP. Antes de uma migração de dados será necessário exportar `SHOW CREATE TABLE`, índices, triggers, permissões e dados de referência do banco real.

## Riscos que não devem ser reproduzidos

1. O acesso a dados monta SQL com valores recebidos em várias Queries.
2. O login legado usa cookie JSON persistente e sessão PHP; o cliente desktop usará tokens revogáveis.
3. Há rotas que misturam controller, HTML e resposta de API sem contrato estável.
4. Uploads são processados diretamente e precisam de validação de tipo, tamanho, nome e armazenamento.
5. O material original contém credenciais em texto claro. Elas devem ser consideradas comprometidas.
6. O modo debug e os logs de erro podem expor dados de operação; a nova API terá configuração explícita por ambiente.

## Estratégia de migração

- Preservar o sistema legado durante a transição.
- Criar a API e o banco novo em paralelo.
- Migrar primeiro cadastros e autenticação.
- Migrar inventário e movimentações com validação de saldos.
- Migrar obras, equipe e liberações por etapas.
- Fazer reconciliação dos dados e somente então desativar o legado.

Não copiar o ZIP inteiro para o novo repositório. Apenas código aprovado, documentação e migrations versionadas devem entrar no Git.
