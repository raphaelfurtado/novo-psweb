# PSWeb — Nova versão (stack TALL)

Reconstrução do sistema de **gestão financeira de condomínio/associação** (originalmente em CodeIgniter 4, em `../psweb`) usando a stack **TALL**: **Tailwind CSS, Alpine.js, Laravel 12 e Livewire 3**.

Reaproveita o **mesmo banco MySQL** (`websi459_psweb`) do sistema legado — os dados e os usuários/senhas atuais continuam válidos.

## Requisitos

- PHP 8.3+ (extensões: `pdo_mysql, mbstring, zip, gd, intl, fileinfo, openssl`)
- Composer 2, Node 18+ / npm
- MySQL com o banco `websi459_psweb`

## Como rodar

```bash
composer install
npm install
npm run build         # ou: npm run dev (durante o desenvolvimento)
php artisan serve     # http://127.0.0.1:8000
```

O `.env` já aponta para o banco `websi459_psweb` (host `127.0.0.1`, user `root`, sem senha).
Sessão/cache em arquivo (`SESSION_DRIVER=file`, `CACHE_STORE=file`) — **não cria tabelas** no banco.

### Login
Por **telefone + senha** (mesmas credenciais do sistema antigo). Há dois perfis:
- **admin** — acesso total (cadastros, movimentações, relatórios);
- **morador** (`user`) — dashboard próprio, "Meus Pagamentos", anexos e troca de senha.

## Principais recursos

- **Dashboard** com KPIs, resumo de caixa (mensalidade e concreto) e **gráficos** (ApexCharts):
  entradas × saídas por mês, saldo acumulado, receita por categoria e % de inadimplência.
- **Cadastros**: moradores (+ endereço), funcionários (+ dados bancários), recebedores,
  tipos de pagamento/saída e formas de pagamento — todos com **filtros/busca/paginação reativos**.
- **Movimentações**: pagamentos (mensalidades e receitas), saídas/despesas, folha de funcionários,
  com **comprovantes** (anexos) e geração de mensalidades do ano em lote.
- **Relatórios** (filtro por mês único OU intervalo):
  Fluxo de Caixa, Inadimplência, Folha de Pagamento, Prestação de Contas, Receitas por Categoria,
  Resumo de Caixa, **Extrato do Morador**, **Demonstrativo Anual** e **Inadimplência Avançada**.
  Exportação em **PDF** (dompdf) e **Excel** (maatwebsite/excel).
- **Tema claro/escuro** e layout responsivo.

## Arquitetura

- `app/Models` — Eloquent mapeando as tabelas legadas (sem migrations destrutivas).
  Quirk preservado: `id_tipo_pagamento`/`id_forma_pagamento`/`id_tipo_saida` referenciam o
  **`codigo`** (não o `id`) das tabelas de domínio — relações usam `ownerKey` customizado.
- `app/Support` — `Referencia` (competência `MMYYYY`) e `Valor` (moeda BR).
- `app/Services/RelatorioService` — fonte única das consultas dos relatórios (tela e PDF/Excel iguais).
- `app/Livewire` — componentes full-page; `Concerns/` traz traits reutilizáveis
  (`ComPeriodo`, `WithSorting`, `HandlesAnexos`).
- `app/Http/Middleware` — `EnsureRole` (papéis) e `EnsureUserConsent` (LGPD).
- Anexos no disco `legacy_uploads` (config em `.env` `LEGACY_UPLOADS_PATH`), compartilhado com o psweb antigo.

## Observações

- A antiga procedure `gerar_pagamentos_ano_flexivel` **não existia** no banco; a geração de
  mensalidades foi **reimplementada em PHP** (configurável: ano, valor e dia de vencimento),
  pulando meses já lançados.

## Testes

```bash
php artisan test
```

Os testes usam a conexão MySQL real **somente leitura** (renderização, controle de acesso,
PDFs/Excel) e a **escrita é validada em transações revertidas** — nada é persistido no banco.
