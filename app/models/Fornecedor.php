<?php

declare(strict_types=1);

final class Fornecedor
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }

    public function listar(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            f.id,
            f.empresa_id,
            f.status_id,
            f.nome,
            f.nome_comercial,
            f.nuit,
            f.email,
            f.telefone,
            f.endereco,
            f.cidade,
            f.pais,
            f.contacto,
            f.observacoes,
            f.criado_em,
            f.atualizado_em,

            s.nome AS status_nome,
            s.descricao AS status_descricao,

            COALESCE(
                saldo.total_debito,
                0
            ) AS total_debito_fornecedor,

            COALESCE(
                saldo.total_credito,
                0
            ) AS total_credito_fornecedor,

            COALESCE(
                saldo.saldo,
                0
            ) AS saldo_fornecedor

        FROM fornecedores f

        INNER JOIN status s
            ON s.id = f.status_id
           AND s.tipo = 'fornecedor'

        LEFT JOIN (
            SELECT
                item.fornecedor_id,

                SUM(
                    item.debito
                ) AS total_debito,

                SUM(
                    item.credito
                ) AS total_credito,

                SUM(
                    item.debito
                    - item.credito
                ) AS saldo

            FROM lancamento_contabilistico_itens item

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

            INNER JOIN contas_contabilisticas conta
                ON conta.id =
                    item.conta_contabil_id

               AND conta.empresa_id =
                    lancamento.empresa_id

            WHERE lancamento.empresa_id =
                :empresa_saldo

              AND item.fornecedor_id
                    IS NOT NULL

              AND lancamento.contabilizado_em
                    IS NOT NULL

              AND conta.conta_fornecedor = 1

            GROUP BY
                item.fornecedor_id

        ) saldo
            ON saldo.fornecedor_id =
                f.id

        WHERE f.empresa_id =
            :empresa_id

        ORDER BY
            f.nome ASC,
            f.id DESC
    ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_saldo' =>
            $empresaId,

            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $sql = "
        SELECT
            f.*,

            s.nome AS status_nome,
            s.descricao AS status_descricao,

            COALESCE(
                saldo.total_debito,
                0
            ) AS total_debito_fornecedor,

            COALESCE(
                saldo.total_credito,
                0
            ) AS total_credito_fornecedor,

            COALESCE(
                saldo.saldo,
                0
            ) AS saldo_fornecedor

        FROM fornecedores f

        INNER JOIN status s
            ON s.id = f.status_id
           AND s.tipo = 'fornecedor'

        LEFT JOIN (
            SELECT
                item.fornecedor_id,

                SUM(
                    item.debito
                ) AS total_debito,

                SUM(
                    item.credito
                ) AS total_credito,

                SUM(
                    item.debito
                    - item.credito
                ) AS saldo

            FROM lancamento_contabilistico_itens item

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

            INNER JOIN contas_contabilisticas conta
                ON conta.id =
                    item.conta_contabil_id

               AND conta.empresa_id =
                    lancamento.empresa_id

            WHERE lancamento.empresa_id =
                :empresa_saldo

              AND item.fornecedor_id
                    IS NOT NULL

              AND lancamento.contabilizado_em
                    IS NOT NULL

              AND conta.conta_fornecedor = 1

            GROUP BY
                item.fornecedor_id

        ) saldo
            ON saldo.fornecedor_id =
                f.id

        WHERE f.id =
            :id

          AND f.empresa_id =
            :empresa_id

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([
            ':empresa_saldo' =>
            $empresaId,

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);

        $fornecedor =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $fornecedor ?: null;
    }

    public function extrato(
        int $fornecedorId,
        int $empresaId,
        ?string $dataInicial = null,
        ?string $dataFinal = null
    ): array {

        /*
     * Confirma que o fornecedor pertence
     * à empresa da sessão.
     */
        $fornecedor =
            $this->buscar(
                $fornecedorId,
                $empresaId
            );

        if (!$fornecedor) {
            throw new RuntimeException(
                'Fornecedor não encontrado.'
            );
        }


        // ==========================================
        // SALDO ANTERIOR
        // ==========================================

        $saldoAnterior = 0.0;

        if ($dataInicial !== null) {

            $sqlAnterior = "
            SELECT
                COALESCE(
                    SUM(
                        item.debito
                        - item.credito
                    ),
                    0
                ) AS saldo

            FROM lancamento_contabilistico_itens item

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

            INNER JOIN contas_contabilisticas conta
                ON conta.id =
                    item.conta_contabil_id

               AND conta.empresa_id =
                    lancamento.empresa_id

            WHERE lancamento.empresa_id =
                    :empresa_id

              AND item.fornecedor_id =
                    :fornecedor_id

              AND lancamento.contabilizado_em
                    IS NOT NULL

              AND conta.conta_fornecedor = 1

              AND lancamento.data_lancamento
                    < :data_inicial
        ";

            $stmtAnterior =
                $this->db->prepare(
                    $sqlAnterior
                );

            $stmtAnterior->execute([
                ':empresa_id' =>
                $empresaId,

                ':fornecedor_id' =>
                $fornecedorId,

                ':data_inicial' =>
                $dataInicial
            ]);

            $saldoAnterior =
                (float)$stmtAnterior
                    ->fetchColumn();
        }


        // ==========================================
        // MOVIMENTOS
        // ==========================================

        $sql = "
        SELECT
            item.id AS item_id,

            lancamento.id AS lancamento_id,
            lancamento.data_lancamento,
            lancamento.numero,
            lancamento.numero_documento,
            lancamento.referencia,
            lancamento.descricao,
            lancamento.origem,

            conta.id AS conta_id,
            conta.codigo AS conta_codigo,
            conta.nome AS conta_nome,

            item.descricao AS item_descricao,
            item.debito,
            item.credito

        FROM lancamento_contabilistico_itens item

        INNER JOIN lancamentos_contabilisticos lancamento
            ON lancamento.id =
                item.lancamento_id

        INNER JOIN contas_contabilisticas conta
            ON conta.id =
                item.conta_contabil_id

           AND conta.empresa_id =
                lancamento.empresa_id

        WHERE lancamento.empresa_id =
                :empresa_id

          AND item.fornecedor_id =
                :fornecedor_id

          AND lancamento.contabilizado_em
                IS NOT NULL

          AND conta.conta_fornecedor = 1
    ";

        $parametros = [
            ':empresa_id' =>
            $empresaId,

            ':fornecedor_id' =>
            $fornecedorId
        ];


        if ($dataInicial !== null) {

            $sql .= "
            AND lancamento.data_lancamento
                >= :data_inicial
        ";

            $parametros[':data_inicial'] =
                $dataInicial;
        }


        if ($dataFinal !== null) {

            $sql .= "
            AND lancamento.data_lancamento
                <= :data_final
        ";

            $parametros[':data_final'] =
                $dataFinal;
        }


        $sql .= "
        ORDER BY
            lancamento.data_lancamento ASC,
            lancamento.id ASC,
            item.id ASC
    ";


        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $parametros
        );

        $movimentos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        // ==========================================
        // SALDO ACUMULADO
        // ==========================================

        $saldoAcumulado =
            $saldoAnterior;

        $totalDebito = 0.0;
        $totalCredito = 0.0;


        foreach (
            $movimentos as &$movimento
        ) {

            $debito =
                (float)(
                    $movimento['debito']
                    ?? 0
                );

            $credito =
                (float)(
                    $movimento['credito']
                    ?? 0
                );


            $totalDebito +=
                $debito;

            $totalCredito +=
                $credito;


            $saldoAcumulado +=
                $debito
                - $credito;


            $movimento['saldo_acumulado'] =
                round(
                    $saldoAcumulado,
                    2
                );
        }

        unset($movimento);


        return [
            'fornecedor' =>
            $fornecedor,

            'saldo_anterior' =>
            round(
                $saldoAnterior,
                2
            ),

            'total_debito' =>
            round(
                $totalDebito,
                2
            ),

            'total_credito' =>
            round(
                $totalCredito,
                2
            ),

            'saldo_atual' =>
            round(
                $saldoAcumulado,
                2
            ),

            'movimentos' =>
            $movimentos
        ];
    }

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                tipo,
                nome,
                descricao

            FROM status

            WHERE tipo = 'fornecedor'

            ORDER BY
                CASE
                    WHEN nome IN (
                        'Activo',
                        'Ativo'
                    ) THEN 1

                    WHEN nome IN (
                        'Inactivo',
                        'Inativo'
                    ) THEN 2

                    WHEN nome = 'Bloqueado'
                    THEN 3

                    WHEN nome = 'Suspenso'
                    THEN 4

                    ELSE 5
                END,
                nome ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function statusExiste(
        int $statusId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :status_id
              AND tipo = 'fornecedor'
        ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function nuitExiste(
        int $empresaId,
        string $nuit,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM fornecedores

            WHERE empresa_id = :empresa_id
              AND nuit = :nuit
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':nuit' => $nuit
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function emailExiste(
        int $empresaId,
        string $email,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM fornecedores

            WHERE empresa_id = :empresa_id
              AND LOWER(email) = LOWER(:email)
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':email' => $email
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(
        int $empresaId,
        array $dados
    ): int {
        $sql = "
            INSERT INTO fornecedores (
                empresa_id,
                status_id,
                nome,
                nome_comercial,
                nuit,
                email,
                telefone,
                endereco,
                cidade,
                pais,
                contacto,
                observacoes
            ) VALUES (
                :empresa_id,
                :status_id,
                :nome,
                :nome_comercial,
                :nuit,
                :email,
                :telefone,
                :endereco,
                :cidade,
                :pais,
                :contacto,
                :observacoes
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':nome_comercial' =>
            $dados['nome_comercial'],

            ':nuit' =>
            $dados['nuit'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':endereco' =>
            $dados['endereco'],

            ':cidade' =>
            $dados['cidade'],

            ':pais' =>
            $dados['pais'],

            ':contacto' =>
            $dados['contacto'],

            ':observacoes' =>
            $dados['observacoes']
        ]);

        return (int) $this->db
            ->lastInsertId();
    }

    public function atualizar(
        int $id,
        int $empresaId,
        array $dados
    ): bool {
        $sql = "
            UPDATE fornecedores

            SET
                status_id = :status_id,
                nome = :nome,
                nome_comercial = :nome_comercial,
                nuit = :nuit,
                email = :email,
                telefone = :telefone,
                endereco = :endereco,
                cidade = :cidade,
                pais = :pais,
                contacto = :contacto,
                observacoes = :observacoes

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':nome_comercial' =>
            $dados['nome_comercial'],

            ':nuit' =>
            $dados['nuit'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':endereco' =>
            $dados['endereco'],

            ':cidade' =>
            $dados['cidade'],

            ':pais' =>
            $dados['pais'],

            ':contacto' =>
            $dados['contacto'],

            ':observacoes' =>
            $dados['observacoes'],

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    public function eliminar(
        int $id,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            DELETE FROM fornecedores

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        return $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);
    }
}
