<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class AjusteStock
{
    private PDO $db;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct(
        ?PDO $db = null
    ) {
        $this->db =
            $db
            ?? Database::conn();


        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );


        $this->auditoria =
            new AuditoriaService(
                $this->db
            );


        $this->log =
            new LogService(
                $this->db
            );
    }


    // ==================================================
    // LISTAR ARMAZÉNS
    // ==================================================

    public function listarArmazens(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                a.id,
                a.nome,
                a.codigo,
                a.filial_id

            FROM armazens a

            INNER JOIN status s
                ON s.id = a.status_id
               AND s.tipo = 'armazem'

            WHERE a.empresa_id = :empresa_id
              AND s.nome = 'Activo'

            ORDER BY
                a.nome ASC
        ";


        $stmt =
            $this->db
            ->prepare(
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


    // ==================================================
    // LISTAR PRODUTOS
    // ==================================================

    public function listarProdutos(
        int $empresaId,
        int $armazemId
    ): array {

        $sql = "
            SELECT
                p.id,
                p.sku,
                p.nome,
                p.preco_custo,

                COALESCE(
                    es.quantidade,
                    0
                ) AS stock_sistema,

                u.codigo
                    AS unidade_codigo,

                u.nome
                    AS unidade_nome,

                u.casas_decimais

            FROM produtos p

            INNER JOIN status sp
                ON sp.id = p.status_id
               AND sp.tipo = 'produto'

            LEFT JOIN estoque_saldos es
                ON es.empresa_id =
                    p.empresa_id
               AND es.produto_id =
                    p.id
               AND es.armazem_id =
                    :armazem_id

            LEFT JOIN unidades u
                ON u.id =
                    p.unidade_id
               AND u.empresa_id =
                    p.empresa_id

            WHERE p.empresa_id =
                :empresa_id

              AND sp.nome =
                'Activo'

            ORDER BY
                p.nome ASC
        ";


        $stmt =
            $this->db
            ->prepare(
                $sql
            );


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':armazem_id' =>
            $armazemId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    // ==================================================
    // VALIDAR ARMAZÉM
    // ==================================================

    public function armazemPertenceEmpresa(
        int $armazemId,
        int $empresaId
    ): bool {

        $stmt =
            $this->db
            ->prepare("
                    SELECT COUNT(*)

                    FROM armazens

                    WHERE id =
                        :id

                      AND empresa_id =
                        :empresa_id
                ");


        $stmt->execute([
            ':id' =>
            $armazemId,

            ':empresa_id' =>
            $empresaId
        ]);


        return
            (int)$stmt->fetchColumn()
            > 0;
    }


    // ==================================================
    // PRODUTO
    // ==================================================

    public function produto(
        int $produtoId,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db
            ->prepare("
                    SELECT
                        id,
                        nome,
                        sku,
                        preco_custo

                    FROM produtos

                    WHERE id =
                        :id

                      AND empresa_id =
                        :empresa_id

                    LIMIT 1
                ");


        $stmt->execute([
            ':id' =>
            $produtoId,

            ':empresa_id' =>
            $empresaId
        ]);


        $produto =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $produto
            ?: null;
    }


    // ==================================================
    // SALDO
    // ==================================================

    public function saldo(
        int $empresaId,
        int $armazemId,
        int $produtoId
    ): float {

        $stmt =
            $this->db
            ->prepare("
                    SELECT quantidade

                    FROM estoque_saldos

                    WHERE empresa_id =
                        :empresa_id

                      AND armazem_id =
                        :armazem_id

                      AND produto_id =
                        :produto_id

                    LIMIT 1
                ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':armazem_id' =>
            $armazemId,

            ':produto_id' =>
            $produtoId
        ]);


        $saldo =
            $stmt->fetchColumn();


        return $saldo !== false
            ? (float)$saldo
            : 0.0;
    }


    // ==================================================
    // STATUS CONFIRMADO
    // ==================================================

    public function statusConfirmado(): ?int
    {
        $stmt =
            $this->db
            ->prepare("
                    SELECT id

                    FROM status

                    WHERE tipo =
                        'movimento_estoque'

                      AND nome =
                        'Confirmado'

                    LIMIT 1
                ");


        $stmt->execute();


        $id =
            $stmt->fetchColumn();


        return $id !== false
            ? (int)$id
            : null;
    }


    // ==================================================
    // AJUSTAR STOCK
    // ==================================================

    public function ajustar(
        int $empresaId,
        int $usuarioId,
        int $armazemId,
        int $statusId,
        string $dataMovimento,
        string $observacoes,
        array $itens
    ): int {

        /*
         * Aqui guardamos os saldos reais
         * encontrados dentro da transação.
         */
        $saldosAnteriores =
            [];


        $this->db
            ->beginTransaction();


        try {

            // ==========================================
            // BLOQUEAR SALDOS
            // ==========================================

            $stmtBloquear =
                $this->db
                ->prepare("
                        SELECT quantidade

                        FROM estoque_saldos

                        WHERE empresa_id =
                            :empresa_id

                          AND armazem_id =
                            :armazem_id

                          AND produto_id =
                            :produto_id

                        FOR UPDATE
                    ");


            // ==========================================
            // CRIAR MOVIMENTO
            // ==========================================

            $stmtMovimento =
                $this->db
                ->prepare("
                        INSERT INTO movimentos_estoque
                        (
                            empresa_id,
                            armazem_origem_id,
                            armazem_destino_id,
                            usuario_id,
                            status_id,
                            tipo,
                            observacoes,
                            data_movimento
                        )
                        VALUES
                        (
                            :empresa_id,
                            :armazem_id,
                            NULL,
                            :usuario_id,
                            :status_id,
                            'AJUSTE',
                            :observacoes,
                            :data_movimento
                        )
                    ");


            $stmtMovimento
                ->execute([
                    ':empresa_id' =>
                    $empresaId,

                    ':armazem_id' =>
                    $armazemId,

                    ':usuario_id' =>
                    $usuarioId,

                    ':status_id' =>
                    $statusId,

                    ':observacoes' =>
                    $observacoes,

                    ':data_movimento' =>
                    $dataMovimento
                ]);


            $movimentoId =
                (int)$this->db
                    ->lastInsertId();


            if ($movimentoId <= 0) {

                throw new RuntimeException(
                    'Não foi possível criar o movimento de ajuste.'
                );
            }


            // ==========================================
            // ITEM DO MOVIMENTO
            // ==========================================

            $stmtItem =
                $this->db
                ->prepare("
                        INSERT INTO movimento_estoque_itens
                        (
                            movimento_id,
                            produto_id,
                            quantidade,
                            custo_unitario
                        )
                        VALUES
                        (
                            :movimento_id,
                            :produto_id,
                            :quantidade,
                            :custo_unitario
                        )
                    ");


            // ==========================================
            // SALDO FINAL
            // ==========================================

            $stmtSaldo =
                $this->db
                ->prepare("
                        INSERT INTO estoque_saldos
                        (
                            empresa_id,
                            armazem_id,
                            produto_id,
                            quantidade
                        )
                        VALUES
                        (
                            :empresa_id,
                            :armazem_id,
                            :produto_id,
                            :quantidade_fisica
                        )

                        ON DUPLICATE KEY UPDATE

                            quantidade =
                                VALUES(
                                    quantidade
                                ),

                            atualizado_em =
                                CURRENT_TIMESTAMP
                    ");


            // ==========================================
            // PROCESSAR ITENS
            // ==========================================

            foreach (
                $itens as $item
            ) {

                $produtoId =
                    (int)(
                        $item['produto_id']
                        ?? 0
                    );


                if ($produtoId <= 0) {

                    throw new RuntimeException(
                        'Produto inválido no ajuste de stock.'
                    );
                }


                /*
                 * Bloqueia o saldo real do produto
                 * no momento da transação.
                 */
                $stmtBloquear
                    ->execute([
                        ':empresa_id' =>
                        $empresaId,

                        ':armazem_id' =>
                        $armazemId,

                        ':produto_id' =>
                        $produtoId
                    ]);


                $saldoBanco =
                    $stmtBloquear
                    ->fetchColumn();


                $stockSistema =
                    $saldoBanco !== false
                    ? (float)$saldoBanco
                    : 0.0;


                /*
                 * Guardar o valor antes da alteração.
                 */
                $saldosAnteriores[$produtoId] =
                    $stockSistema;


                $stockFisico =
                    (float)(
                        $item['stock_fisico']
                        ?? 0
                    );


                $diferenca =
                    $stockFisico
                    - $stockSistema;


                /*
                 * Se não existe diferença,
                 * não cria item de movimento.
                 */
                if (
                    abs(
                        $diferenca
                    ) < 0.000001
                ) {

                    continue;
                }


                // ======================================
                // HISTÓRICO DO MOVIMENTO
                // ======================================

                $stmtItem
                    ->execute([
                        ':movimento_id' =>
                        $movimentoId,

                        ':produto_id' =>
                        $produtoId,

                        /*
                         * AJUSTE:
                         * pode ser positivo
                         * ou negativo.
                         */
                        ':quantidade' =>
                        $diferenca,

                        ':custo_unitario' =>
                        (float)(
                            $item['custo_unitario']
                            ?? 0
                        )
                    ]);


                // ======================================
                // ATUALIZAR SALDO
                // ======================================

                $stmtSaldo
                    ->execute([
                        ':empresa_id' =>
                        $empresaId,

                        ':armazem_id' =>
                        $armazemId,

                        ':produto_id' =>
                        $produtoId,

                        ':quantidade_fisica' =>
                        $stockFisico
                    ]);
            }


            // ==========================================
            // NÃO PERMITIR MOVIMENTO VAZIO
            // ==========================================

            $stmtContar =
                $this->db
                ->prepare("
                        SELECT COUNT(*)

                        FROM movimento_estoque_itens

                        WHERE movimento_id =
                            :movimento_id
                    ");


            $stmtContar
                ->execute([
                    ':movimento_id' =>
                    $movimentoId
                ]);


            if (
                (int)$stmtContar
                    ->fetchColumn()
                === 0
            ) {

                throw new RuntimeException(
                    'Nenhuma diferença de stock foi encontrada.'
                );
            }


            // ==========================================
            // PREPARAR ITENS PARA AUDITORIA
            // ==========================================

            $itensAuditoria =
                [];


            foreach (
                $itens as $item
            ) {

                $produtoId =
                    (int)$item['produto_id'];


                $stockAnterior =
                    (float)(
                        $saldosAnteriores[$produtoId]
                        ?? 0
                    );


                $stockFisico =
                    (float)$item['stock_fisico'];


                $diferenca =
                    $stockFisico
                    - $stockAnterior;


                if (
                    abs(
                        $diferenca
                    ) < 0.000001
                ) {

                    continue;
                }


                $itensAuditoria[] = [

                    'produto_id' =>
                    $produtoId,

                    'sku' =>
                    $item['produto_sku']
                        ?? null,

                    'produto' =>
                    $item['produto_nome']
                        ?? null,

                    'stock_anterior' =>
                    $stockAnterior,

                    'stock_fisico' =>
                    $stockFisico,

                    'diferenca' =>
                    $diferenca,

                    'custo_unitario' =>
                    (float)(
                        $item['custo_unitario']
                        ?? 0
                    )
                ];
            }


            // ==========================================
            // AUDITORIA
            // ==========================================

            $this->auditoria
                ->insercao(
                    $empresaId,
                    $usuarioId,
                    'movimentos_estoque',
                    $movimentoId,

                    [
                        'tipo' =>
                        'AJUSTE',

                        'armazem_id' =>
                        $armazemId,

                        'status_id' =>
                        $statusId,

                        'data_movimento' =>
                        $dataMovimento,

                        'observacoes' =>
                        $observacoes,

                        'itens' =>
                        $itensAuditoria
                    ]
                );


            // ==========================================
            // FILIAL DO ARMAZÉM
            // ==========================================

            $filialId =
                $this->filialDoArmazem(
                    $empresaId,
                    $armazemId
                );


            // ==========================================
            // LOG
            // ==========================================

            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'STOCK',
                    'AJUSTAR',

                    'Registou o ajuste de stock #'
                        . $movimentoId
                        . ' no armazém ID '
                        . $armazemId
                        . '.',

                    $movimentoId,
                    $filialId
                );


            /*
             * Auditoria e log já foram
             * gravados com sucesso.
             *
             * Agora podemos confirmar tudo.
             */
            $this->db
                ->commit();


            return $movimentoId;
        } catch (Throwable $e) {

            if (
                $this->db
                ->inTransaction()
            ) {

                $this->db
                    ->rollBack();
            }


            throw $e;
        }
    }


    // ==================================================
    // FILIAL DO ARMAZÉM
    // ==================================================

    private function filialDoArmazem(
        int $empresaId,
        int $armazemId
    ): ?int {

        $stmt =
            $this->db
            ->prepare("
                    SELECT filial_id

                    FROM armazens

                    WHERE id =
                        :armazem_id

                      AND empresa_id =
                        :empresa_id

                    LIMIT 1
                ");


        $stmt->execute([
            ':armazem_id' =>
            $armazemId,

            ':empresa_id' =>
            $empresaId
        ]);


        $filialId =
            $stmt->fetchColumn();


        if (
            $filialId === false
            || $filialId === null
            || (int)$filialId <= 0
        ) {

            return null;
        }


        return (int)$filialId;
    }
}
