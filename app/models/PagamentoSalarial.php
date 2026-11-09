<?php

declare(strict_types=1);

class PagamentoSalarial
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conn();
    }

    /*
    |--------------------------------------------------------------------------
    | Listar pagamentos
    |--------------------------------------------------------------------------
    */

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {

        $sql = "
        SELECT
            pg.id,
            pg.uuid,
            pg.codigo,
            pg.numero,
            pg.empresa_id,

            pg.processamento_id,
            pg.recibo_id,
            pg.conta_contabil_id,

            pg.forma_pagamento,
            pg.forma_pagamento
                AS metodo_pagamento,

            pg.data_pagamento,
            pg.moeda,
            pg.valor_processamento,
            pg.valor_pago,

            pg.banco,
            pg.conta_bancaria,
            pg.iban,
            pg.referencia,
            pg.comprovativo,
            pg.observacoes,

            pg.status_id,

            st.nome AS status_nome,
            st.descricao AS status_descricao,

            cc.codigo AS conta_contabil_codigo,
            cc.nome AS conta_contabil_nome,

            pr.funcionario_id,
            pr.periodo_id,

            f.numero_funcionario,

            CONCAT_WS(
                ' ',
                f.primeiro_nome,
                f.apelido
            ) AS funcionario_nome,

            per.codigo AS periodo_codigo,
            per.descricao AS periodo_descricao,
            per.ano AS periodo_ano,
            per.mes AS periodo_mes,

            rs.codigo AS recibo_codigo,
            rs.numero_funcionario
                AS recibo_numero_funcionario,
            rs.funcionario_nome
                AS recibo_funcionario_nome,

            pg.registado_por,
            pg.confirmado_por,
            pg.confirmado_em,
            pg.cancelado_por,
            pg.cancelado_em,
            pg.motivo_cancelamento,

            pg.criado_em,
            pg.atualizado_em

        FROM pagamentos_salariais pg

        INNER JOIN status st
            ON st.id = pg.status_id
           AND st.tipo =
                'PAGAMENTO_SALARIAL'

        INNER JOIN processamentos_salariais pr
            ON pr.id = pg.processamento_id
           AND pr.empresa_id = pg.empresa_id

        INNER JOIN funcionarios f
            ON f.id = pr.funcionario_id
           AND f.empresa_id = pg.empresa_id

        INNER JOIN periodos_salariais per
            ON per.id = pr.periodo_id
           AND per.empresa_id = pg.empresa_id

        LEFT JOIN recibos_salarios rs
            ON rs.id = pg.recibo_id
           AND rs.empresa_id = pg.empresa_id

        LEFT JOIN contas_contabilisticas cc
            ON cc.id = pg.conta_contabil_id
           AND cc.empresa_id = pg.empresa_id

        WHERE pg.empresa_id =
            :empresa_id
    ";


        $parametros = [
            ':empresa_id' =>
            $empresaId
        ];


        /*
    |--------------------------------------------------------------------------
    | Período
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['periodo_id'])) {

            $sql .= "
            AND pr.periodo_id =
                :periodo_id
        ";

            $parametros[':periodo_id'] =
                (int)$filtros['periodo_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Estado
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['status_id'])) {

            $sql .= "
            AND pg.status_id =
                :status_id
        ";

            $parametros[':status_id'] =
                (int)$filtros['status_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Forma de pagamento
    |--------------------------------------------------------------------------
    */

        $formaPagamento =
            $filtros['forma_pagamento']
            ?? $filtros['metodo_pagamento']
            ?? '';

        if (
            trim(
                (string)$formaPagamento
            ) !== ''
        ) {

            $sql .= "
            AND pg.forma_pagamento =
                :forma_pagamento
        ";

            $parametros[':forma_pagamento'] =
                strtoupper(
                    trim(
                        (string)$formaPagamento
                    )
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Processamento
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['processamento_id'])) {

            $sql .= "
            AND pg.processamento_id =
                :processamento_id
        ";

            $parametros[':processamento_id'] =
                (int)$filtros['processamento_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Recibo
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['recibo_id'])) {

            $sql .= "
            AND pg.recibo_id =
                :recibo_id
        ";

            $parametros[':recibo_id'] =
                (int)$filtros['recibo_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Pesquisa
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['pesquisa'])) {

            $pesquisa =
                '%'
                . trim(
                    (string)$filtros['pesquisa']
                )
                . '%';

            $sql .= "
            AND (
                pg.numero
                    LIKE :pesquisa

                OR pg.codigo
                    LIKE :pesquisa

                OR pg.referencia
                    LIKE :pesquisa

                OR f.numero_funcionario
                    LIKE :pesquisa

                OR CONCAT_WS(
                    ' ',
                    f.primeiro_nome,
                    f.apelido
                ) LIKE :pesquisa

                OR rs.codigo
                    LIKE :pesquisa
            )
        ";

            $parametros[':pesquisa'] =
                $pesquisa;
        }


        $sql .= "
        ORDER BY
            pg.data_pagamento DESC,
            pg.id DESC
    ";


        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $parametros
        );


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar por ID
    |--------------------------------------------------------------------------
    */

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $sql = "
        SELECT
            pg.id,
            pg.uuid,
            pg.codigo,
            pg.numero,
            pg.empresa_id,

            pg.processamento_id,
            pg.recibo_id,
            pg.conta_contabil_id,

            pg.forma_pagamento,
            pg.forma_pagamento AS metodo_pagamento,

            pg.data_pagamento,
            pg.moeda,
            pg.valor_processamento,
            pg.valor_pago,

            pg.banco,
            pg.banco AS banco_nome,

            pg.conta_bancaria,
            pg.conta_bancaria AS numero_conta_destino,

            pg.iban,

            pg.referencia,
            pg.referencia AS referencia_pagamento,

            pg.comprovativo,
            pg.observacoes,

            pg.status_id,

            st.nome AS status_nome,
            st.descricao AS status_descricao,
            st.tipo AS status_tipo,

            cc.codigo AS conta_contabil_codigo,
            cc.nome AS conta_contabil_nome,

            /* Processamento */
            pr.funcionario_id,
            pr.periodo_id,

            /* Funcionário */
            f.numero_funcionario,

            CONCAT_WS(
                ' ',
                f.primeiro_nome,
                f.apelido
            ) AS funcionario_nome,

            /* Período */
            per.codigo AS periodo_codigo,
            per.descricao AS periodo_descricao,
            per.ano AS periodo_ano,
            per.mes AS periodo_mes,

            /* Recibo */
            rs.codigo AS recibo_codigo,

            /* Alias para compatibilidade com o JS */
            rs.codigo AS numero_recibo,

            rs.numero_funcionario
                AS recibo_numero_funcionario,

            rs.funcionario_nome
                AS recibo_funcionario_nome,

            rs.salario_bruto,
            rs.total_descontos,
            rs.salario_liquido,

            /* Auditoria */
            pg.registado_por,
            pg.confirmado_por,
            pg.confirmado_em,
            pg.cancelado_por,
            pg.cancelado_em,
            pg.motivo_cancelamento,

            pg.criado_em,
            pg.atualizado_em

        FROM pagamentos_salariais pg

        INNER JOIN status st
            ON st.id = pg.status_id
           AND st.tipo = 'PAGAMENTO_SALARIAL'

        INNER JOIN processamentos_salariais pr
            ON pr.id = pg.processamento_id
           AND pr.empresa_id = pg.empresa_id

        INNER JOIN funcionarios f
            ON f.id = pr.funcionario_id
           AND f.empresa_id = pg.empresa_id

        INNER JOIN periodos_salariais per
            ON per.id = pr.periodo_id
           AND per.empresa_id = pg.empresa_id

        LEFT JOIN recibos_salarios rs
            ON rs.id = pg.recibo_id
           AND rs.empresa_id = pg.empresa_id

        LEFT JOIN contas_contabilisticas cc
            ON cc.id = pg.conta_contabil_id
           AND cc.empresa_id = pg.empresa_id

        WHERE pg.id = :id
          AND pg.empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar pagamento pelo recibo
    |--------------------------------------------------------------------------
    */

    public function buscarPorRecibo(
        int $reciboId,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                ps.id,
                ps.uuid,
                ps.codigo,
                ps.numero,
                ps.recibo_id,
                ps.status_id,
                s.nome AS status_nome

            FROM pagamentos_salariais ps

            INNER JOIN status s
                ON s.id = ps.status_id

            WHERE ps.recibo_id = :recibo_id
              AND ps.empresa_id = :empresa_id
              AND s.tipo = 'PAGAMENTO_SALARIAL'

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':recibo_id' => $reciboId,
            ':empresa_id' => $empresaId
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Criar pagamento
    |--------------------------------------------------------------------------
    */

    public function criar(array $dados): int
    {
        $sql = "
        INSERT INTO pagamentos_salariais (
            uuid,
            codigo,
            empresa_id,
            processamento_id,
            recibo_id,
            conta_contabil_id,
            numero,
            forma_pagamento,
            data_pagamento,
            moeda,
            valor_processamento,
            valor_pago,
            banco,
            conta_bancaria,
            iban,
            referencia,
            comprovativo,
            observacoes,
            status_id,
            registado_por
        )
        VALUES (
            :uuid,
            :codigo,
            :empresa_id,
            :processamento_id,
            :recibo_id,
            :conta_contabil_id,
            :numero,
            :forma_pagamento,
            :data_pagamento,
            :moeda,
            :valor_processamento,
            :valor_pago,
            :banco,
            :conta_bancaria,
            :iban,
            :referencia,
            :comprovativo,
            :observacoes,
            :status_id,
            :registado_por
        )
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':uuid' =>
            $dados['uuid'],

            ':codigo' =>
            $dados['codigo'] ?? null,

            ':empresa_id' =>
            $dados['empresa_id'],

            ':processamento_id' =>
            $dados['processamento_id'],

            ':recibo_id' =>
            $dados['recibo_id'] ?? null,

            ':conta_contabil_id' =>
            $dados['conta_contabil_id'] ?? null,

            ':numero' =>
            $dados['numero'],

            ':forma_pagamento' =>
            $dados['forma_pagamento'],

            ':data_pagamento' =>
            $dados['data_pagamento'],

            ':moeda' =>
            $dados['moeda'] ?? 'MZN',

            ':valor_processamento' =>
            $dados['valor_processamento'],

            ':valor_pago' =>
            $dados['valor_pago'],

            ':banco' =>
            $dados['banco'] ?? null,

            ':conta_bancaria' =>
            $dados['conta_bancaria'] ?? null,

            ':iban' =>
            $dados['iban'] ?? null,

            ':referencia' =>
            $dados['referencia'] ?? null,

            ':comprovativo' =>
            $dados['comprovativo'] ?? null,

            ':observacoes' =>
            $dados['observacoes'] ?? null,

            ':status_id' =>
            $dados['status_id'],

            ':registado_por' =>
            $dados['registado_por'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | Atualizar pagamento pendente
    |--------------------------------------------------------------------------
    */

    public function atualizar(
        int $id,
        int $empresaId,
        array $dados
    ): bool {
        $sql = "
        UPDATE pagamentos_salariais

        SET
            conta_contabil_id =
                :conta_contabil_id,

            forma_pagamento =
                :forma_pagamento,

            data_pagamento =
                :data_pagamento,

            moeda =
                :moeda,

            valor_processamento =
                :valor_processamento,

            valor_pago =
                :valor_pago,

            banco =
                :banco,

            conta_bancaria =
                :conta_bancaria,

            iban =
                :iban,

            referencia =
                :referencia,

            comprovativo =
                :comprovativo,

            observacoes =
                :observacoes

        WHERE id = :id
          AND empresa_id = :empresa_id
    ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':conta_contabil_id' =>
            $dados['conta_contabil_id'] ?? null,

            ':forma_pagamento' =>
            $dados['forma_pagamento'],

            ':data_pagamento' =>
            $dados['data_pagamento'],

            ':moeda' =>
            $dados['moeda'] ?? 'MZN',

            ':valor_processamento' =>
            $dados['valor_processamento'],

            ':valor_pago' =>
            $dados['valor_pago'],

            ':banco' =>
            $dados['banco'] ?? null,

            ':conta_bancaria' =>
            $dados['conta_bancaria'] ?? null,

            ':iban' =>
            $dados['iban'] ?? null,

            ':referencia' =>
            $dados['referencia'] ?? null,

            ':comprovativo' =>
            $dados['comprovativo'] ?? null,

            ':observacoes' =>
            $dados['observacoes'] ?? null,

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    public function listarContasPagamento(
        int $empresaId,
        ?string $formaPagamento = null
    ): array {

        $formaPagamento =
            strtoupper(
                trim(
                    (string)$formaPagamento
                )
            );


        /*
     * Dinheiro utiliza a conta de caixa
     * configurada no RH.
     *
     * Os restantes métodos utilizam
     * a conta bancária padrão.
     */

        if ($formaPagamento === 'NUMERARIO') {

            $sql = "
            SELECT
                cc.id,
                cc.codigo,
                cc.nome,
                cc.conta_caixa,
                cc.conta_banco,
                1 AS conta_padrao

            FROM configuracoes_contabilisticas_rh cfg

            INNER JOIN contas_contabilisticas cc
                ON cc.id =
                    cfg.conta_caixa_padrao_id

            WHERE cfg.empresa_id =
                :empresa_id

              AND cc.empresa_id =
                cfg.empresa_id

              AND cc.tipo =
                'ANALITICA'

              AND cc.aceita_lancamento = 1

              AND cc.conta_caixa = 1

            LIMIT 1
        ";
        } elseif ($formaPagamento !== '') {

            $sql = "
            SELECT
                cc.id,
                cc.codigo,
                cc.nome,
                cc.conta_caixa,
                cc.conta_banco,
                1 AS conta_padrao

            FROM configuracoes_contabilisticas_rh cfg

            INNER JOIN contas_contabilisticas cc
                ON cc.id =
                    cfg.conta_banco_padrao_id

            WHERE cfg.empresa_id =
                :empresa_id

              AND cc.empresa_id =
                cfg.empresa_id

              AND cc.tipo =
                'ANALITICA'

              AND cc.aceita_lancamento = 1

              AND cc.conta_banco = 1

            LIMIT 1
        ";
        } else {

            /*
         * Quando nenhuma forma foi informada,
         * devolvemos banco e caixa configurados.
         */

            $sql = "
            SELECT DISTINCT
                cc.id,
                cc.codigo,
                cc.nome,
                cc.conta_caixa,
                cc.conta_banco,
                1 AS conta_padrao

            FROM configuracoes_contabilisticas_rh cfg

            INNER JOIN contas_contabilisticas cc
                ON (
                    cc.id =
                        cfg.conta_banco_padrao_id

                    OR cc.id =
                        cfg.conta_caixa_padrao_id
                )

            WHERE cfg.empresa_id =
                :empresa_id

              AND cc.empresa_id =
                cfg.empresa_id

              AND cc.tipo =
                'ANALITICA'

              AND cc.aceita_lancamento = 1

              AND (
                    cc.conta_banco = 1
                    OR cc.conta_caixa = 1
              )

            ORDER BY
                cc.codigo ASC,
                cc.nome ASC
        ";
        }


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function validarContaPagamento(
        int $contaId,
        int $empresaId,
        string $formaPagamento
    ): bool {

        if (
            $contaId <= 0
            || $empresaId <= 0
        ) {
            return false;
        }


        $formaPagamento =
            strtoupper(
                trim($formaPagamento)
            );


        if ($formaPagamento === 'NUMERARIO') {

            $sql = "
            SELECT COUNT(*)

            FROM configuracoes_contabilisticas_rh cfg

            INNER JOIN contas_contabilisticas cc
                ON cc.id =
                    cfg.conta_caixa_padrao_id

            WHERE cfg.empresa_id =
                :empresa_id

              AND cc.id =
                :conta_id

              AND cc.empresa_id =
                cfg.empresa_id

              AND cc.tipo =
                'ANALITICA'

              AND cc.aceita_lancamento = 1

              AND cc.conta_caixa = 1
        ";
        } else {

            $sql = "
            SELECT COUNT(*)

            FROM configuracoes_contabilisticas_rh cfg

            INNER JOIN contas_contabilisticas cc
                ON cc.id =
                    cfg.conta_banco_padrao_id

            WHERE cfg.empresa_id =
                :empresa_id

              AND cc.id =
                :conta_id

              AND cc.empresa_id =
                cfg.empresa_id

              AND cc.tipo =
                'ANALITICA'

              AND cc.aceita_lancamento = 1

              AND cc.conta_banco = 1
        ";
        }


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':conta_id' =>
            $contaId
        ]);


        return (int)$stmt->fetchColumn()
            > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Confirmar pagamento
    |--------------------------------------------------------------------------
    */

    public function confirmar(
        int $id,
        int $empresaId,
        int $statusPagoId,
        int $usuarioId,
        string $dataPagamento,
        ?string $referencia = null
    ): bool {
        $sql = "
            UPDATE pagamentos_salariais

            SET
                status_id = :status_id,
                data_pagamento = :data_pagamento,
                referencia = COALESCE(
                    :referencia,
                    referencia
                ),
                confirmado_por = :confirmado_por,
                confirmado_em = NOW(),

                cancelado_por = NULL,
                cancelado_em = NULL,
                motivo_cancelamento = NULL

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':status_id' => $statusPagoId,
            ':data_pagamento' => $dataPagamento,
            ':referencia' => $referencia,
            ':confirmado_por' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);
    }

    /*
|--------------------------------------------------------------------------
| Sincronizar valores pagos do recibo
|--------------------------------------------------------------------------
*/

    public function sincronizarRecibo(
        int $reciboId,
        int $empresaId
    ): bool {
        if ($reciboId <= 0 || $empresaId <= 0) {
            return false;
        }

        /*
     * Recalcula o valor pago usando somente
     * pagamentos que estão realmente no estado Pago.
     */
        $sql = "
        UPDATE recibos_salarios rs

        SET
            rs.valor_pago = (
                SELECT COALESCE(
                    SUM(pg.valor_pago),
                    0
                )

                FROM pagamentos_salariais pg

                INNER JOIN status st
                    ON st.id = pg.status_id
                   AND st.tipo = 'PAGAMENTO_SALARIAL'

                WHERE pg.recibo_id = rs.id
                  AND pg.empresa_id = rs.empresa_id
                  AND st.nome = 'Pago'
            ),

            rs.saldo_pendente = GREATEST(
                rs.salario_liquido - (
                    SELECT COALESCE(
                        SUM(pg2.valor_pago),
                        0
                    )

                    FROM pagamentos_salariais pg2

                    INNER JOIN status st2
                        ON st2.id = pg2.status_id
                       AND st2.tipo = 'PAGAMENTO_SALARIAL'

                    WHERE pg2.recibo_id = rs.id
                      AND pg2.empresa_id = rs.empresa_id
                      AND st2.nome = 'Pago'
                ),
                0
            ),

            rs.atualizado_em = NOW()

        WHERE rs.id = :recibo_id
          AND rs.empresa_id = :empresa_id
    ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':recibo_id' => $reciboId,
            ':empresa_id' => $empresaId
        ]);
    }


    /*
|--------------------------------------------------------------------------
| Sincronizar processamento salarial
|--------------------------------------------------------------------------
*/

    public function sincronizarProcessamento(
        int $processamentoId,
        int $empresaId
    ): bool {
        if (
            $processamentoId <= 0
            || $empresaId <= 0
        ) {
            return false;
        }

        $sql = "
        UPDATE processamentos_salariais pr

        SET
            pr.valor_pago = (
                SELECT COALESCE(
                    SUM(pg.valor_pago),
                    0
                )

                FROM pagamentos_salariais pg

                INNER JOIN status st
                    ON st.id = pg.status_id
                   AND st.tipo = 'PAGAMENTO_SALARIAL'

                WHERE pg.processamento_id = pr.id
                  AND pg.empresa_id = pr.empresa_id
                  AND st.nome = 'Pago'
            ),

            pr.saldo_pendente = GREATEST(
                pr.salario_liquido - (
                    SELECT COALESCE(
                        SUM(pg2.valor_pago),
                        0
                    )

                    FROM pagamentos_salariais pg2

                    INNER JOIN status st2
                        ON st2.id = pg2.status_id
                       AND st2.tipo = 'PAGAMENTO_SALARIAL'

                    WHERE pg2.processamento_id = pr.id
                      AND pg2.empresa_id = pr.empresa_id
                      AND st2.nome = 'Pago'
                ),
                0
            )

        WHERE pr.id = :processamento_id
          AND pr.empresa_id = :empresa_id
    ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':processamento_id' =>
            $processamentoId,

            ':empresa_id' =>
            $empresaId
        ]);
    }


    /*
|--------------------------------------------------------------------------
| Sincronizar totais da folha salarial
|--------------------------------------------------------------------------
*/

    public function sincronizarFolhaPorRecibo(
        int $reciboId,
        int $empresaId
    ): bool {
        if ($reciboId <= 0 || $empresaId <= 0) {
            return false;
        }

        /*
     * Descobrir a folha correspondente ao recibo.
     */
        $stmt = $this->db->prepare("
        SELECT folha_salarial_id

        FROM recibos_salarios

        WHERE id = :recibo_id
          AND empresa_id = :empresa_id

        LIMIT 1
    ");

        $stmt->execute([
            ':recibo_id' => $reciboId,
            ':empresa_id' => $empresaId
        ]);

        $folhaId = (int)$stmt->fetchColumn();

        if ($folhaId <= 0) {
            return false;
        }

        /*
     * Recalcular a folha através dos recibos.
     */
        $sql = "
        UPDATE folhas_salariais fs

        SET
            fs.total_pago = (
                SELECT COALESCE(
                    SUM(rs.valor_pago),
                    0
                )

                FROM recibos_salarios rs

                WHERE rs.folha_salarial_id = fs.id
                  AND rs.empresa_id = fs.empresa_id
            ),

            fs.saldo_pendente = GREATEST(
                fs.total_liquido - (
                    SELECT COALESCE(
                        SUM(rs2.valor_pago),
                        0
                    )

                    FROM recibos_salarios rs2

                    WHERE rs2.folha_salarial_id = fs.id
                      AND rs2.empresa_id = fs.empresa_id
                ),
                0
            )

        WHERE fs.id = :folha_id
          AND fs.empresa_id = :empresa_id
    ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':folha_id' => $folhaId,
            ':empresa_id' => $empresaId
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelar pagamento
    |--------------------------------------------------------------------------
    */

    public function cancelar(
        int $id,
        int $empresaId,
        int $statusCanceladoId,
        int $usuarioId,
        string $motivo
    ): bool {
        $sql = "
            UPDATE pagamentos_salariais

            SET
                status_id = :status_id,
                cancelado_por = :cancelado_por,
                cancelado_em = NOW(),
                motivo_cancelamento =
                    :motivo_cancelamento

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':status_id' =>
            $statusCanceladoId,

            ':cancelado_por' =>
            $usuarioId,

            ':motivo_cancelamento' =>
            $motivo,

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar ID do estado
    |--------------------------------------------------------------------------
    */

    public function buscarStatusId(
        string $nome
    ): ?int {
        $sql = "
            SELECT id

            FROM status

            WHERE nome = :nome
              AND tipo = 'PAGAMENTO_SALARIAL'

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':nome' => $nome
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false
            ? (int) $id
            : null;
    }

    /*
|--------------------------------------------------------------------------
| Listar recibos disponíveis
|--------------------------------------------------------------------------
*/

    public function listarRecibosDisponiveis(
        int $empresaId,
        ?int $periodoId = null
    ): array {

        $sql = "
        SELECT
            rs.id,
            rs.codigo,
            rs.processamento_id,
            rs.periodo_id,
            rs.funcionario_id,

            rs.numero_funcionario,
            rs.funcionario_nome,

            rs.moeda,
            rs.salario_bruto,
            rs.total_descontos,
            rs.salario_liquido,
            rs.valor_pago,
            rs.saldo_pendente,

            ps.ano AS periodo_ano,
            ps.mes AS periodo_mes,
            ps.descricao AS periodo_descricao,

            pr.id AS processamento_id_real,

            /* ==========================================
               DADOS BANCÁRIOS DO FUNCIONÁRIO
            ========================================== */

            f.banco_nome
                AS funcionario_banco,

            f.numero_conta_bancaria
                AS funcionario_conta_bancaria,

            f.nib
                AS funcionario_nib

        FROM recibos_salarios rs

        INNER JOIN processamentos_salariais pr
            ON pr.id = rs.processamento_id
           AND pr.empresa_id = rs.empresa_id

        INNER JOIN funcionarios f
            ON f.id = rs.funcionario_id
           AND f.empresa_id = rs.empresa_id

        INNER JOIN periodos_salariais ps
            ON ps.id = rs.periodo_id
           AND ps.empresa_id = rs.empresa_id

        LEFT JOIN pagamentos_salariais pg
            ON pg.recibo_id = rs.id
           AND pg.empresa_id = rs.empresa_id

        WHERE rs.empresa_id = :empresa_id

          AND pg.id IS NULL

          AND COALESCE(
                rs.saldo_pendente,
                rs.salario_liquido,
                0
              ) > 0
    ";

        $parametros = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($periodoId !== null) {

            $sql .= "
            AND rs.periodo_id =
                :periodo_id
        ";

            $parametros[':periodo_id'] =
                $periodoId;
        }

        $sql .= "
        ORDER BY
            ps.ano DESC,
            ps.mes DESC,
            rs.funcionario_nome ASC
    ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $parametros
        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function buscarDadosBancariosFuncionarioPorRecibo(
        int $empresaId,
        int $reciboId
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT
                f.id AS funcionario_id,

                f.banco_nome,
                f.numero_conta_bancaria,
                f.nib

            FROM recibos_salarios rs

            INNER JOIN funcionarios f
                ON f.id = rs.funcionario_id
               AND f.empresa_id = rs.empresa_id

            WHERE rs.id =
                :recibo_id

              AND rs.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':recibo_id' =>
            $reciboId,

            ':empresa_id' =>
            $empresaId
        ]);

        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $dados ?: null;
    }

    public function listarDisponiveisLote(
        int $empresaId,
        ?int $periodoId = null
    ): array {

        $sql = "
        SELECT
            rs.id AS recibo_id,
            rs.codigo AS recibo_codigo,

            rs.processamento_id,
            rs.periodo_id,
            rs.funcionario_id,

            rs.numero_funcionario,
            rs.funcionario_nome,

            rs.moeda,
            rs.salario_liquido,
            rs.saldo_pendente,

            f.banco_nome
                AS banco,

            f.numero_conta_bancaria
                AS conta_bancaria,

            f.nib,

            ps.ano AS periodo_ano,
            ps.mes AS periodo_mes,
            ps.descricao AS periodo_descricao

        FROM recibos_salarios rs

        INNER JOIN funcionarios f
            ON f.id = rs.funcionario_id
           AND f.empresa_id = rs.empresa_id

        INNER JOIN periodos_salariais ps
            ON ps.id = rs.periodo_id
           AND ps.empresa_id = rs.empresa_id

        INNER JOIN processamentos_salariais pr
            ON pr.id = rs.processamento_id
           AND pr.empresa_id = rs.empresa_id

        LEFT JOIN pagamentos_salariais pg
            ON pg.recibo_id = rs.id
           AND pg.empresa_id = rs.empresa_id

        WHERE rs.empresa_id =
            :empresa_id

          AND pg.id IS NULL

          AND COALESCE(
                rs.saldo_pendente,
                rs.salario_liquido,
                0
              ) > 0
    ";

        $parametros = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            $periodoId !== null
            && $periodoId > 0
        ) {

            $sql .= "
            AND rs.periodo_id =
                :periodo_id
        ";

            $parametros[':periodo_id'] =
                $periodoId;
        }


        $sql .= "
        ORDER BY
            f.banco_nome ASC,
            rs.funcionario_nome ASC
    ";


        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $parametros
        );


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function buscarReciboDisponivelLote(
        int $empresaId,
        int $reciboId
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT
                rs.id AS recibo_id,
                rs.processamento_id,
                rs.funcionario_id,
                rs.moeda,
                rs.salario_liquido,
                rs.saldo_pendente,

                f.banco_nome,
                f.numero_conta_bancaria,
                f.nib

            FROM recibos_salarios rs

            INNER JOIN funcionarios f
                ON f.id = rs.funcionario_id
               AND f.empresa_id = rs.empresa_id

            LEFT JOIN pagamentos_salariais pg
                ON pg.recibo_id = rs.id
               AND pg.empresa_id = rs.empresa_id

            WHERE rs.id =
                :recibo_id

              AND rs.empresa_id =
                :empresa_id

              AND pg.id IS NULL

              AND COALESCE(
                    rs.saldo_pendente,
                    rs.salario_liquido,
                    0
                  ) > 0

            LIMIT 1
        ");


        $stmt->execute([
            ':recibo_id' =>
            $reciboId,

            ':empresa_id' =>
            $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }

    /*
|--------------------------------------------------------------------------
| Listar processamentos disponíveis
|--------------------------------------------------------------------------
*/

    public function listarProcessamentosDisponiveis(
        int $empresaId,
        ?int $periodoId = null
    ): array {
        $sql = "
        SELECT
            pr.id,
            pr.periodo_id,
            pr.funcionario_id,
            pr.moeda,
            pr.salario_bruto,
            pr.total_descontos,
            pr.salario_liquido,
            pr.valor_pago,
            pr.saldo_pendente,

            f.numero_funcionario,

            CONCAT_WS(
                ' ',
                f.primeiro_nome,
                f.apelido
            ) AS funcionario_nome,

            ps.ano AS periodo_ano,
            ps.mes AS periodo_mes,
            ps.descricao AS periodo_descricao,

            rs.id AS recibo_id,
            rs.codigo AS recibo_codigo

        FROM processamentos_salariais pr

        INNER JOIN funcionarios f
            ON f.id = pr.funcionario_id
           AND f.empresa_id = pr.empresa_id

        INNER JOIN periodos_salariais ps
            ON ps.id = pr.periodo_id
           AND ps.empresa_id = pr.empresa_id

        LEFT JOIN recibos_salarios rs
            ON rs.processamento_id = pr.id
           AND rs.empresa_id = pr.empresa_id

        LEFT JOIN pagamentos_salariais pg
            ON pg.processamento_id = pr.id
           AND pg.empresa_id = pr.empresa_id

        WHERE pr.empresa_id = :empresa_id

          AND pg.id IS NULL

          AND COALESCE(
                pr.saldo_pendente,
                pr.salario_liquido,
                0
              ) > 0
    ";

        $parametros = [
            ':empresa_id' => $empresaId
        ];

        if ($periodoId !== null) {
            $sql .= "
            AND pr.periodo_id = :periodo_id
        ";

            $parametros[':periodo_id'] =
                $periodoId;
        }

        $sql .= "
        ORDER BY
            ps.ano DESC,
            ps.mes DESC,
            funcionario_nome ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Listar estados
    |--------------------------------------------------------------------------
    */

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                nome,
                descricao,
                tipo

            FROM status

            WHERE tipo = 'PAGAMENTO_SALARIAL'

            ORDER BY id ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

    public function resumo(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                COUNT(*) AS total_pagamentos,

                COALESCE(
                    SUM(
                        CASE
                            WHEN s.nome = 'Pago'
                            THEN ps.valor_pago
                            ELSE 0
                        END
                    ),
                    0
                ) AS valor_total_pago,

                COALESCE(
                    SUM(
                        CASE
                            WHEN s.nome = 'Pendente'
                            THEN ps.valor_pago
                            ELSE 0
                        END
                    ),
                    0
                ) AS valor_total_pendente,

                SUM(
                    CASE
                        WHEN s.nome = 'Pago'
                        THEN 1
                        ELSE 0
                    END
                ) AS total_pagos,

                SUM(
                    CASE
                        WHEN s.nome = 'Pendente'
                        THEN 1
                        ELSE 0
                    END
                ) AS total_pendentes,

                SUM(
                    CASE
                        WHEN s.nome = 'Cancelado'
                        THEN 1
                        ELSE 0
                    END
                ) AS total_cancelados

            FROM pagamentos_salariais ps

            INNER JOIN status s
                ON s.id = ps.status_id

            WHERE ps.empresa_id = :empresa_id
              AND s.tipo = 'PAGAMENTO_SALARIAL'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: [];
    }

    /*
    |--------------------------------------------------------------------------
    | Gerar UUID
    |--------------------------------------------------------------------------
    */

    public function gerarUuid(): string
    {
        $dados = random_bytes(16);

        $dados[6] =
            chr(
                ord($dados[6])
                    & 0x0f
                    | 0x40
            );

        $dados[8] =
            chr(
                ord($dados[8])
                    & 0x3f
                    | 0x80
            );

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(
                bin2hex($dados),
                4
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Gerar número
    |--------------------------------------------------------------------------
    */

    public function gerarNumero(
        int $empresaId
    ): string {
        $prefixo =
            'PAG-' . date('Ym') . '-';

        $sql = "
            SELECT numero

            FROM pagamentos_salariais

            WHERE empresa_id = :empresa_id
              AND numero LIKE :prefixo

            ORDER BY id DESC

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':prefixo' => $prefixo . '%'
        ]);

        $ultimo =
            $stmt->fetchColumn();

        $sequencia = 1;

        if ($ultimo) {
            $partes =
                explode('-', $ultimo);

            $sequencia =
                ((int) end($partes)) + 1;
        }

        return $prefixo
            . str_pad(
                (string) $sequencia,
                5,
                '0',
                STR_PAD_LEFT
            );
    }

    public function gerarCodigo(
        int $empresaId
    ): string {
        return $this->gerarNumero(
            $empresaId
        );
    }
}
