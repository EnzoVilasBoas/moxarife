# Banco de dados

O banco novo começa em `database/migrations/001_initial.sql`.

A migration cria:

- `users`;
- `refresh_tokens`;
- `inventory_items`;
- `inventory_movements`;
- a view `inventory_stock`.

Não execute a migration em um banco que já contém dados sem um dump e um plano de rollback. O banco legado não deve ser compartilhado com a nova API durante a transição.
