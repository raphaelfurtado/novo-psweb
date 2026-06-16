-- ============================================================================
--  Funcionalidade: Conciliação por OFX + Acordos
--  Banco: websi459_psweb (compartilhado com o psweb legado)
--
--  IMPORTANTE: este projeto NÃO usa `php artisan migrate` (a migration default
--  de `users` colidiria com a tabela legada). O schema é aplicado por SQL direto.
--  Tudo aqui é ADITIVO e idempotente — não altera nem apaga nada do legado.
--
--  Aplicar:  mysql -u root -h 127.0.0.1 websi459_psweb < database/sql/2026_06_conciliacao_acordo.sql
-- ============================================================================

-- 1) CPF do morador (chave do casamento OFX). Coluna nova, nullable.
--    O ADD COLUMN é protegido para poder rodar mais de uma vez.
SET @existe_cpf := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'cpf'
);
SET @sql := IF(@existe_cpf = 0,
    'ALTER TABLE users ADD COLUMN cpf VARCHAR(14) NULL AFTER telefone_2',
    'SELECT "coluna users.cpf ja existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Lote de conciliação (cada importação de um arquivo OFX).
CREATE TABLE IF NOT EXISTS conciliacao_lote (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    arquivo         VARCHAR(255) NULL,
    banco           VARCHAR(60)  NULL,
    conta           VARCHAR(40)  NULL,
    periodo_inicio  DATE NULL,
    periodo_fim     DATE NULL,
    qtd_transacoes  INT NOT NULL DEFAULT 0,
    total_creditos  DECIMAL(12,2) NOT NULL DEFAULT 0,
    id_importador   INT NULL,
    created_at      DATETIME NULL,
    updated_at      DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Recebimento = uma linha de crédito (PIX/TED) do extrato.
--    `fitid` é o identificador único da transação no OFX → dedupe de reimportação.
CREATE TABLE IF NOT EXISTS recebimento (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_lote       INT NULL,
    fitid         VARCHAR(120) NULL,
    data          DATE NOT NULL,
    valor         DECIMAL(10,2) NOT NULL,
    nome_pagador  VARCHAR(255) NULL,
    cpf_pagador   VARCHAR(14)  NULL,
    memo          VARCHAR(255) NULL,
    tipo          VARCHAR(20)  NULL,
    id_usuario    INT NULL,                 -- morador identificado (match)
    id_acordo     INT NULL,                 -- se quitou um acordo
    status        VARCHAR(20) NOT NULL DEFAULT 'PENDENTE', -- PENDENTE|CONCILIADO|IGNORADO
    valor_credito DECIMAL(10,2) NOT NULL DEFAULT 0,        -- sobra (troco) virou crédito
    observacao    VARCHAR(255) NULL,
    conciliado_em DATETIME NULL,
    id_conciliador INT NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    UNIQUE KEY uq_recebimento_fitid (fitid),
    KEY idx_recebimento_usuario (id_usuario),
    KEY idx_recebimento_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Razão da baixa: quanto de cada recebimento foi aplicado em cada parcela.
--    Permite pagamento parcial e uma parcela quitada por 2 recebimentos.
CREATE TABLE IF NOT EXISTS recebimento_parcela (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_recebimento INT NOT NULL,
    id_pagamento   INT NOT NULL,
    valor_aplicado DECIMAL(10,2) NOT NULL,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    UNIQUE KEY uq_receb_parcela (id_recebimento, id_pagamento),
    KEY idx_rp_pagamento (id_pagamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Acordo: negociação de um bloco de parcelas atrasadas (com desconto/parcelado).
CREATE TABLE IF NOT EXISTS acordo (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario     INT NOT NULL,
    valor_original DECIMAL(10,2) NOT NULL DEFAULT 0, -- soma das parcelas incluídas
    valor_acordado DECIMAL(10,2) NOT NULL DEFAULT 0, -- o que o morador vai pagar
    desconto       DECIMAL(10,2) NOT NULL DEFAULT 0, -- original - acordado
    num_parcelas   INT NOT NULL DEFAULT 1,           -- 1 = à vista
    valor_pago     DECIMAL(10,2) NOT NULL DEFAULT 0, -- acumulado dos recebimentos
    situacao       VARCHAR(20) NOT NULL DEFAULT 'ABERTO', -- ABERTO|QUITADO|CANCELADO
    data_acordo    DATE NULL,
    observacao     VARCHAR(512) NULL,
    id_criador     INT NULL,
    quitado_em     DATETIME NULL,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    KEY idx_acordo_usuario (id_usuario),
    KEY idx_acordo_situacao (situacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Parcelas (pagamento) cobertas por um acordo.
CREATE TABLE IF NOT EXISTS acordo_parcela (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_acordo    INT NOT NULL,
    id_pagamento INT NOT NULL,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL,
    UNIQUE KEY uq_acordo_parcela (id_acordo, id_pagamento),
    KEY idx_ap_pagamento (id_pagamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
