<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/EstoqueService.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class TransferenciaStock
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
    // ARMAZÉNS
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

            WHERE a.empresa_id =
                :empresa_id

              AND s.nome =
                'Activo'

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
    // PRODUTOS COM STOCK
    // ==================================================

    public function listarProdutosPorArmazem(
        int $empresaId,
        int $armazemId
    ): array {

        $sql = "
            SELECT
                p.id,
                p.sku,
                p.nome,
                p.preco_custo,

                es.quantidade
                    AS stock_disponivel,

                es.custo_medio,

                u.codigo
                    AS unidade_codigo,

                u.nome
                    AS unidade_nome,

                u.casas_decimais

            FROM estoque_saldos es

            INNER JOIN produtos p
                ON p.id =
                    es.produto_id
               AND p.empresa_id =
                    es.empresa_id

            INNER JOIN status sp
                ON sp.id =
                    p.status_id
               AND sp.tipo =
                    'produto'

            LEFT JOIN unidades u
                ON u.id =
                    p.unidade_id
               AND u.empresa_id =
                    p.empresa_id

            WHERE es.empresa_id =
                :empresa_id

              AND es.armazem_id =
                :armazem_id

              AND es.quantidade > 0

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
            (int)$stmt
                ->fetchColumn()
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
    // TRANSFERIR
    // ==================================================

    public function transferir(
        int $empresaId,
        int $usuarioId,
        int $origemId,
        int $destinoId,
        int $statusId,
        string $dataMovimento,
        ?string $observacoes,
        array $itens
    ): int {

        if ($origemId === $destinoId) {

            throw new RuntimeException(
                'O armazém de origem deve ser diferente do destino.'
            );
        }


        $this->db
            ->beginTransaction();


        try {

            // ==========================================
            // 1. MOVIMENTO
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
                            :origem_id,
                            :destino_id,
                            :usuario_id,
                            :status_id,
                            'TRANSFERENCIA',
                            :observacoes,
                            :data_movimento
                        )
                    ");


            $stmtMovimento
                ->execute([
                    ':empresa_id' =>
                    $empresaId,

                    ':origem_id' =>
                    $origemId,

                    ':destino_id' =>
                    $destinoId,

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
                    'Não foi possível criar a transferência de stock.'
                );
            }


            // ==========================================
            // 2. ITENS
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


            foreach ($itens as $item) {

                $produtoId =
                    (int)(
                        $item['produto_id']
                        ?? 0
                    );


                $quantidade =
                    (float)(
                        $item['quantidade']
                        ?? 0
                    );


                if ($produtoId <= 0) {

                    throw new RuntimeException(
                        'Produto inválido na transferência.'
                    );
                }


                if ($quantidade <= 0) {

                    throw new RuntimeException(
                        'Quantidade inválida na transferência.'
                    );
                }


                /*
                 * Custo provisório.
                 *
                 * EstoqueService substituirá pelo
                 * CMP real do armazém de origem.
                 */
                $stmtItem
                    ->execute([
                        ':movimento_id' =>
                        $movimentoId,

                        ':produto_id' =>
                        $produtoId,

                        ':quantidade' =>
                        $quantidade,

                        ':custo_unitario' =>
                        0
                    ]);
            }


            // ==========================================
            // 3. APLICAR TRANSFERÊNCIA
            // ==========================================

            EstoqueService::aplicarMovimento(
                $movimentoId,
                $this->db
            );


            /*
             * Neste momento:
             *
             * - origem já foi reduzida;
             * - destino já foi aumentado;
             * - CMP da origem foi utilizado;
             * - mesmo CMP foi transferido ao destino;
             * - custo_unitario do item já foi atualizado.
             */


            // ==========================================
            // 4. RECARREGAR ITENS
            // ==========================================

            $stmtItens =
                $this->db
                ->prepare("
                        SELECT
                            mei.produto_id,
                            mei.quantidade,
                            mei.custo_unitario,

                            p.sku,
                            p.nome

                        FROM movimento_estoque_itens mei

                        INNER JOIN produtos p
                            ON p.id =
                                mei.produto_id

                           AND p.empresa_id =
                                :empresa_id

                        WHERE mei.movimento_id =
                            :movimento_id

                        ORDER BY
                            mei.id ASC
                    ");


            $stmtItens
                ->execute([
                    ':empresa_id' =>
                    $empresaId,

                    ':movimento_id' =>
                    $movimentoId
                ]);


            $itensBanco =
                $stmtItens
                ->fetchAll(
                    PDO::FETCH_ASSOC
                );


            if (!$itensBanco) {

                throw new RuntimeException(
                    'A transferência não possui produtos.'
                );
            }


            // ==========================================
            // 5. AUDITORIA - ITENS
            // ==========================================

            $itensAuditoria =
                [];


            $quantidadeTotal =
                0.0;


            $valorTotal =
                0.0;


            foreach (
                $itensBanco
                as $item
            ) {

                $quantidade =
                    (float)$item['quantidade'];


                $custoUnitario =
                    (float)$item['custo_unitario'];


                $subtotal =
                    round(
                        $quantidade
                            * $custoUnitario,
                        2
                    );


                $quantidadeTotal +=
                    $quantidade;


                $valorTotal +=
                    $subtotal;


                $itensAuditoria[] = [

                    'produto_id' =>
                    (int)$item['produto_id'],

                    'sku' =>
                    $item['sku']
                        ?? null,

                    'produto' =>
                    $item['nome']
                        ?? null,

                    'quantidade' =>
                    $quantidade,

                    'custo_unitario' =>
                    $custoUnitario,

                    'subtotal' =>
                    $subtotal
                ];
            }


            // ==========================================
            // 6. AUDITORIA
            // ==========================================

            $this->auditoria
                ->insercao(
                    $empresaId,
                    $usuarioId,
                    'movimentos_estoque',
                    $movimentoId,

                    [
                        'tipo' =>
                        'TRANSFERENCIA',

                        'armazem_origem_id' =>
                        $origemId,

                        'armazem_destino_id' =>
                        $destinoId,

                        'status_id' =>
                        $statusId,

                        'data_movimento' =>
                        $dataMovimento,

                        'observacoes' =>
                        $observacoes,

                        'quantidade_total' =>
                        $quantidadeTotal,

                        'valor_total' =>
                        round(
                            $valorTotal,
                            2
                        ),

                        'itens' =>
                        $itensAuditoria
                    ]
                );


            // ==========================================
            // 7. FILIAIS
            // ==========================================

            $filialOrigemId =
                $this->filialDoArmazem(
                    $empresaId,
                    $origemId
                );


            $filialDestinoId =
                $this->filialDoArmazem(
                    $empresaId,
                    $destinoId
                );


            // ==========================================
            // 8. LOG
            // ==========================================

            $descricao =
                'Registou a transferência de stock #'
                . $movimentoId
                . ' do armazém ID '
                . $origemId
                . ' para o armazém ID '
                . $destinoId
                . '.';


            if (
                $filialOrigemId !== null
                && $filialDestinoId !== null
                && $filialOrigemId
                !== $filialDestinoId
            ) {

                $descricao .=
                    ' Transferência entre filiais '
                    . $filialOrigemId
                    . ' e '
                    . $filialDestinoId
                    . '.';
            }


            /*
             * No log usamos a filial de origem
             * como filial principal da operação.
             */
            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'STOCK',
                    'TRANSFERENCIA',
                    $descricao,
                    $movimentoId,
                    $filialOrigemId
                );


            // ==========================================
            // 9. COMMIT
            // ==========================================

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
