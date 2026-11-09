<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class InventarioStock
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


    public function armazens(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    a.id,
                    a.nome,
                    a.codigo

                FROM armazens a

                INNER JOIN status s
                    ON s.id = a.status_id
                   AND s.tipo = 'armazem'

                WHERE a.empresa_id = :empresa_id
                  AND s.nome = 'Activo'

                ORDER BY a.nome
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    public function statusId(
        string $nome
    ): ?int {

        $stmt =
            $this->db->prepare("
                SELECT id

                FROM status

                WHERE tipo = 'inventario_stock'
                  AND nome = :nome

                LIMIT 1
            ");


        $stmt->execute([
            ':nome' => $nome
        ]);


        $id =
            $stmt->fetchColumn();


        return $id !== false
            ? (int)$id
            : null;
    }


    public function statusMovimentoConfirmado(): ?int
    {
        $stmt =
            $this->db->prepare("
                SELECT id

                FROM status

                WHERE tipo = 'movimento_estoque'
                  AND nome = 'Confirmado'

                LIMIT 1
            ");


        $stmt->execute();


        $id =
            $stmt->fetchColumn();


        return $id !== false
            ? (int)$id
            : null;
    }


    public function listar(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                i.id,
                i.codigo,
                i.data_abertura,
                i.data_finalizacao,
                i.descricao,
                i.movimento_ajuste_id,

                a.nome AS armazem_nome,

                s.nome AS status_nome,

                u.nome AS usuario_nome,

                COUNT(ii.id) AS total_produtos,

                SUM(
                    CASE
                        WHEN ii.contado = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS total_contados,

                SUM(
                    CASE
                        WHEN ii.contado = 1
                        AND ABS(ii.diferenca) > 0.000001
                        THEN 1
                        ELSE 0
                    END
                ) AS total_diferencas

                FROM inventarios_stock i

                INNER JOIN armazens a
                    ON a.id = i.armazem_id

                INNER JOIN status s
                    ON s.id = i.status_id

                INNER JOIN usuarios u
                    ON u.id = i.usuario_abertura_id

                LEFT JOIN inventario_stock_itens ii
                    ON ii.inventario_id = i.id

                WHERE i.empresa_id = :empresa_id

                GROUP BY
                i.id,
                i.codigo,
                i.data_abertura,
                i.data_finalizacao,
                i.descricao,
                i.movimento_ajuste_id,
                a.nome,
                s.nome,
                u.nome

                ORDER BY
                    i.id DESC
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    public function resumo(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT

                    SUM(
                        CASE
                            WHEN s.nome = 'Aberto'
                            THEN 1
                            ELSE 0
                        END
                    ) AS abertos,

                    SUM(
                        CASE
                            WHEN s.nome = 'Finalizado'
                            THEN 1
                            ELSE 0
                        END
                    ) AS finalizados,

                    SUM(
                        CASE
                            WHEN s.nome = 'Cancelado'
                            THEN 1
                            ELSE 0
                        END
                    ) AS cancelados

                FROM inventarios_stock i

                INNER JOIN status s
                    ON s.id = i.status_id
                   AND s.tipo = 'inventario_stock'

                WHERE i.empresa_id = :empresa_id
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return
            $stmt->fetch(
                PDO::FETCH_ASSOC
            )
            ?: [];
    }


    public function criar(
        int $empresaId,
        int $armazemId,
        int $usuarioId,
        string $dataAbertura,
        ?string $descricao
    ): int {

        $statusAberto =
            $this->statusId(
                'Aberto'
            );


        if ($statusAberto === null) {

            throw new RuntimeException(
                'Estado Aberto do inventário não configurado.'
            );
        }


        $this->db
            ->beginTransaction();


        try {

            /*
             * Evitar dois inventários abertos
             * no mesmo armazém.
             */

            $stmtExiste =
                $this->db->prepare("
                    SELECT COUNT(*)

                    FROM inventarios_stock i

                    INNER JOIN status s
                        ON s.id = i.status_id
                       AND s.tipo = 'inventario_stock'

                    WHERE i.empresa_id = :empresa_id
                      AND i.armazem_id = :armazem_id
                      AND s.nome = 'Aberto'
                ");


            $stmtExiste->execute([
                ':empresa_id' =>
                $empresaId,

                ':armazem_id' =>
                $armazemId
            ]);


            if (
                (int)$stmtExiste
                    ->fetchColumn()
                > 0
            ) {

                throw new RuntimeException(
                    'Já existe um inventário aberto para este armazém.'
                );
            }


            /*
             * Código
             */

            $codigo =
                'INV-'
                . date('YmdHis')
                . '-'
                . random_int(
                    100,
                    999
                );


            /*
             * Cabeçalho
             */

            $stmt =
                $this->db->prepare("
                    INSERT INTO inventarios_stock
                    (
                        empresa_id,
                        armazem_id,
                        status_id,
                        usuario_abertura_id,
                        codigo,
                        descricao,
                        data_abertura
                    )
                    VALUES
                    (
                        :empresa_id,
                        :armazem_id,
                        :status_id,
                        :usuario_id,
                        :codigo,
                        :descricao,
                        :data_abertura
                    )
                ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':armazem_id' =>
                $armazemId,

                ':status_id' =>
                $statusAberto,

                ':usuario_id' =>
                $usuarioId,

                ':codigo' =>
                $codigo,

                ':descricao' =>
                $descricao,

                ':data_abertura' =>
                $dataAbertura
            ]);


            $inventarioId =
                (int)$this->db
                    ->lastInsertId();


            /*
             * Capturar fotografia do stock.
             */

            $stmtProdutos =
                $this->db->prepare("
        SELECT
            p.id,

            COALESCE(
                es.quantidade,
                0
            ) AS quantidade,

            COALESCE(
                es.custo_medio,
                p.preco_custo,
                0
            ) AS custo_unitario

        FROM produtos p

        INNER JOIN status sp
            ON sp.id = p.status_id
           AND sp.tipo = 'produto'
           AND sp.nome = 'Activo'

        LEFT JOIN estoque_saldos es
            ON es.empresa_id = p.empresa_id
           AND es.produto_id = p.id
           AND es.armazem_id = :armazem_id

        WHERE p.empresa_id = :empresa_id

        ORDER BY p.id
    ");


            $stmtProdutos->execute([
                ':empresa_id' =>
                $empresaId,

                ':armazem_id' =>
                $armazemId
            ]);


            $produtos =
                $stmtProdutos->fetchAll(
                    PDO::FETCH_ASSOC
                );


            $stmtItem =
                $this->db->prepare("
                    INSERT INTO inventario_stock_itens
                    (
                        inventario_id,
                        produto_id,
                        quantidade_sistema,
                        custo_unitario
                    )
                    VALUES
                    (
                        :inventario_id,
                        :produto_id,
                        :quantidade_sistema,
                        :custo_unitario
                    )
                ");


            foreach (
                $produtos as $produto
            ) {

                $stmtItem->execute([
                    ':inventario_id' =>
                    $inventarioId,

                    ':produto_id' =>
                    $produto['id'],

                    ':quantidade_sistema' =>
                    $produto['quantidade'],

                    ':custo_unitario' =>
                    $produto['custo_unitario']
                ]);
            }


            $totalProdutos =
                count($produtos);


            $this->auditoria
                ->insercao(
                    $empresaId,
                    $usuarioId,
                    'inventarios_stock',
                    $inventarioId,

                    [
                        'codigo' =>
                        $codigo,

                        'armazem_id' =>
                        $armazemId,

                        'status_id' =>
                        $statusAberto,

                        'data_abertura' =>
                        $dataAbertura,

                        'descricao' =>
                        $descricao,

                        'total_produtos' =>
                        $totalProdutos
                    ]
                );


            $filialId =
                $this->filialDoArmazem(
                    $empresaId,
                    $armazemId
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'STOCK',
                    'ABRIR_INVENTARIO',

                    'Abriu o inventário '
                        . $codigo
                        . ' no armazém ID '
                        . $armazemId
                        . '.',

                    $inventarioId,
                    $filialId
                );


            $this->db->commit();


            return $inventarioId;
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


    public function buscar(
        int $inventarioId,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT
                    i.*,

                    a.nome
                        AS armazem_nome,

                    s.nome
                        AS status_nome

                FROM inventarios_stock i

                INNER JOIN armazens a
                    ON a.id = i.armazem_id

                INNER JOIN status s
                    ON s.id = i.status_id

                WHERE i.id = :id
                  AND i.empresa_id = :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
            $inventarioId,

            ':empresa_id' =>
            $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }


    public function itens(
        int $inventarioId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    ii.id,
                    ii.produto_id,
                    ii.quantidade_sistema,
                    ii.quantidade_fisica,
                    ii.diferenca,
                    ii.custo_unitario,
                    ii.contado,
                    ii.observacoes,

                    p.sku,
                    p.nome AS produto_nome,

                    u.codigo AS unidade_codigo

                FROM inventario_stock_itens ii

                INNER JOIN produtos p
                    ON p.id = ii.produto_id

                LEFT JOIN unidades u
                    ON u.id = p.unidade_id
                   AND u.empresa_id = p.empresa_id

                WHERE ii.inventario_id =
                    :inventario_id

                ORDER BY
                    p.nome
            ");


        $stmt->execute([
            ':inventario_id' =>
            $inventarioId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    public function guardarContagem(
        int $inventarioId,
        int $empresaId,
        int $usuarioId,
        array $itens
    ): void {

        $this->db
            ->beginTransaction();


        try {

            $inventario =
                $this->buscar(
                    $inventarioId,
                    $empresaId
                );


            if (!$inventario) {

                throw new RuntimeException(
                    'Inventário não encontrado.'
                );
            }


            if (
                $inventario['status_nome']
                !== 'Aberto'
            ) {

                throw new RuntimeException(
                    'Apenas inventários abertos podem receber contagens.'
                );
            }


            $stmt =
                $this->db
                ->prepare("
                    UPDATE inventario_stock_itens

                    SET
                        quantidade_fisica =
                            :quantidade,

                        contado = 1,

                        observacoes =
                            :observacoes

                    WHERE id =
                        :id

                      AND inventario_id =
                        :inventario_id
                ");


            foreach ($itens as $item) {

                $stmt->execute([
                    ':quantidade' =>
                    $item['quantidade_fisica'],

                    ':observacoes' =>
                    $item['observacoes'],

                    ':id' =>
                    $item['id'],

                    ':inventario_id' =>
                    $inventarioId
                ]);


                if (
                    $stmt->rowCount()
                    === 0
                ) {

                    /*
                 * rowCount() também pode ser zero
                 * quando os valores são exatamente
                 * iguais aos já existentes.
                 *
                 * Portanto confirmamos a existência.
                 */
                    $stmtExiste =
                        $this->db
                        ->prepare("
                            SELECT COUNT(*)

                            FROM inventario_stock_itens

                            WHERE id =
                                :id

                              AND inventario_id =
                                :inventario_id
                        ");


                    $stmtExiste->execute([
                        ':id' =>
                        $item['id'],

                        ':inventario_id' =>
                        $inventarioId
                    ]);


                    if (
                        (int)$stmtExiste
                            ->fetchColumn()
                        === 0
                    ) {

                        throw new RuntimeException(
                            'Foi encontrado um item inválido na contagem.'
                        );
                    }
                }
            }


            $filialId =
                $this->filialDoArmazem(
                    $empresaId,
                    (int)$inventario['armazem_id']
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'STOCK',
                    'CONTAGEM_INVENTARIO',

                    'Guardou a contagem do inventário '
                        . $inventario['codigo']
                        . '. Itens enviados: '
                        . count($itens)
                        . '.',

                    $inventarioId,
                    $filialId
                );


            $this->db
                ->commit();
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

    public function todosContados(
        int $inventarioId
    ): bool {

        $stmt =
            $this->db->prepare("
            SELECT COUNT(*)

            FROM inventario_stock_itens

            WHERE inventario_id = :inventario_id
              AND contado = 0
        ");

        $stmt->execute([
            ':inventario_id' =>
            $inventarioId
        ]);

        return
            (int)$stmt->fetchColumn()
            === 0;
    }


    public function finalizar(
        int $inventarioId,
        int $empresaId,
        int $usuarioId
    ): int {

        $inventario =
            $this->buscar(
                $inventarioId,
                $empresaId
            );

        if (!$inventario) {

            throw new RuntimeException(
                'Inventário não encontrado.'
            );
        }

        if (
            $inventario['status_nome']
            !== 'Aberto'
        ) {

            throw new RuntimeException(
                'Apenas inventários abertos podem ser finalizados.'
            );
        }

        if (
            !$this->todosContados(
                $inventarioId
            )
        ) {

            throw new RuntimeException(
                'Existem produtos que ainda não foram contados.'
            );
        }


        $statusFinalizado =
            $this->statusId(
                'Finalizado'
            );

        if ($statusFinalizado === null) {

            throw new RuntimeException(
                'Estado Finalizado não configurado.'
            );
        }


        $statusMovimento =
            $this->statusMovimentoConfirmado();

        if ($statusMovimento === null) {

            throw new RuntimeException(
                'Estado Confirmado dos movimentos de stock não configurado.'
            );
        }


        $armazemId =
            (int)$inventario['armazem_id'];


        $this->db
            ->beginTransaction();


        try {

            /*
        |--------------------------------------------------------------------------
        | BLOQUEAR O INVENTÁRIO
        |--------------------------------------------------------------------------
        */

            $stmtInventario =
                $this->db->prepare("
        SELECT
            i.status_id,
            s.nome AS status_nome

        FROM inventarios_stock i

        INNER JOIN status s
            ON s.id = i.status_id
           AND s.tipo = 'inventario_stock'

        WHERE i.id = :id
          AND i.empresa_id = :empresa_id

        FOR UPDATE
    ");


            $stmtInventario->execute([
                ':id' =>
                $inventarioId,

                ':empresa_id' =>
                $empresaId
            ]);


            $inventarioBloqueado =
                $stmtInventario->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$inventarioBloqueado) {

                throw new RuntimeException(
                    'Inventário não encontrado.'
                );
            }


            if (
                $inventarioBloqueado['status_nome']
                !== 'Aberto'
            ) {

                throw new RuntimeException(
                    'Este inventário já não está aberto.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | ITENS CONTADOS
        |--------------------------------------------------------------------------
        */

            $stmtItens =
                $this->db->prepare("
                SELECT
                    ii.produto_id,
                    ii.quantidade_fisica,
                    ii.custo_unitario,

                    p.nome AS produto_nome

                FROM inventario_stock_itens ii

                INNER JOIN produtos p
                    ON p.id = ii.produto_id
                   AND p.empresa_id = :empresa_id

                WHERE ii.inventario_id =
                    :inventario_id

                ORDER BY ii.produto_id
            ");


            $stmtItens->execute([
                ':empresa_id' =>
                $empresaId,

                ':inventario_id' =>
                $inventarioId
            ]);


            $itens =
                $stmtItens->fetchAll(
                    PDO::FETCH_ASSOC
                );


            /*
        |--------------------------------------------------------------------------
        | CRIAR MOVIMENTO DE AJUSTE
        |--------------------------------------------------------------------------
        */

            $stmtMovimento =
                $this->db->prepare("
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
                    NOW()
                )
            ");


            $stmtMovimento->execute([
                ':empresa_id' =>
                $empresaId,

                ':armazem_id' =>
                $armazemId,

                ':usuario_id' =>
                $usuarioId,

                ':status_id' =>
                $statusMovimento,

                ':observacoes' =>
                'Ajuste automático gerado pelo inventário '
                    . $inventario['codigo']
            ]);


            $movimentoId =
                (int)$this->db
                    ->lastInsertId();


            /*
        |--------------------------------------------------------------------------
        | BLOQUEAR SALDO
        |--------------------------------------------------------------------------
        */

            $stmtSaldoAtual =
                $this->db->prepare("
                SELECT quantidade

                FROM estoque_saldos

                WHERE empresa_id = :empresa_id
                  AND armazem_id = :armazem_id
                  AND produto_id = :produto_id

                FOR UPDATE
            ");


            /*
        |--------------------------------------------------------------------------
        | ITEM DO MOVIMENTO
        |--------------------------------------------------------------------------
        */

            $stmtMovimentoItem =
                $this->db->prepare("
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


            /*
        |--------------------------------------------------------------------------
        | SALDO FINAL
        |--------------------------------------------------------------------------
        */

            $stmtAtualizarSaldo =
                $this->db->prepare("
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
                    :quantidade
                )

                ON DUPLICATE KEY UPDATE

                    quantidade =
                        VALUES(quantidade),

                    atualizado_em =
                        CURRENT_TIMESTAMP
            ");


            $totalAjustes = 0;

            $ajustesAuditoria = [];


            foreach ($itens as $item) {

                $produtoId =
                    (int)$item['produto_id'];


                $quantidadeFisica =
                    (float)$item['quantidade_fisica'];


                /*
             * Lemos o saldo REAL no momento
             * da finalização.
             */

                $stmtSaldoAtual->execute([
                    ':empresa_id' =>
                    $empresaId,

                    ':armazem_id' =>
                    $armazemId,

                    ':produto_id' =>
                    $produtoId
                ]);


                $saldo =
                    $stmtSaldoAtual
                    ->fetchColumn();


                $quantidadeAtual =
                    $saldo !== false
                    ? (float)$saldo
                    : 0.0;


                $diferenca =
                    $quantidadeFisica
                    - $quantidadeAtual;


                /*
             * Sem diferença:
             * não geramos item de ajuste.
             */

                if (
                    abs($diferenca)
                    < 0.000001
                ) {

                    continue;
                }


                $ajustesAuditoria[] = [

                    'produto_id' =>
                    $produtoId,

                    'produto' =>
                    $item['produto_nome']
                        ?? null,

                    'quantidade_antes' =>
                    $quantidadeAtual,

                    'quantidade_fisica' =>
                    $quantidadeFisica,

                    'diferenca' =>
                    $diferenca,

                    'custo_unitario' =>
                    (float)$item['custo_unitario']
                ];


                $stmtMovimentoItem
                    ->execute([
                        ':movimento_id' =>
                        $movimentoId,

                        ':produto_id' =>
                        $produtoId,

                        ':quantidade' =>
                        $diferenca,

                        ':custo_unitario' =>
                        (float)$item['custo_unitario']
                    ]);


                $stmtAtualizarSaldo
                    ->execute([
                        ':empresa_id' =>
                        $empresaId,

                        ':armazem_id' =>
                        $armazemId,

                        ':produto_id' =>
                        $produtoId,

                        ':quantidade' =>
                        $quantidadeFisica
                    ]);


                $totalAjustes++;
            }


            /*
        |--------------------------------------------------------------------------
        | SE NÃO HOUVE DIFERENÇAS
        |--------------------------------------------------------------------------
        */

            if ($totalAjustes === 0) {

                /*
             * O movimento foi criado antes de sabermos
             * se haveria diferenças.
             * Podemos removê-lo porque ainda está dentro
             * da mesma transação.
             */

                $stmtExcluirMovimento =
                    $this->db->prepare("
                    DELETE FROM movimentos_estoque
                    WHERE id = :id
                ");


                $stmtExcluirMovimento
                    ->execute([
                        ':id' =>
                        $movimentoId
                    ]);


                $movimentoId = 0;
            }


            /*
        |--------------------------------------------------------------------------
        | FINALIZAR INVENTÁRIO
        |--------------------------------------------------------------------------
        */

            $stmtFinalizar =
                $this->db->prepare("
                UPDATE inventarios_stock

                SET
                    status_id =
                        :status_id,

                    usuario_finalizacao_id =
                        :usuario_id,

                    data_finalizacao =
                        NOW(),

                    movimento_ajuste_id =
                        :movimento_id

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");


            $stmtFinalizar->execute([
                ':status_id' =>
                $statusFinalizado,

                ':usuario_id' =>
                $usuarioId,

                ':movimento_id' =>
                $movimentoId > 0
                    ? $movimentoId
                    : null,

                ':id' =>
                $inventarioId,

                ':empresa_id' =>
                $empresaId
            ]);

            $dadosAnteriores = [

                'status' =>
                'Aberto',

                'movimento_ajuste_id' =>
                null,

                'data_finalizacao' =>
                null
            ];


            $dadosNovos = [

                'status' =>
                'Finalizado',

                'status_id' =>
                $statusFinalizado,

                'usuario_finalizacao_id' =>
                $usuarioId,

                'movimento_ajuste_id' =>
                $movimentoId > 0
                    ? $movimentoId
                    : null,

                'total_ajustes' =>
                $totalAjustes,

                'ajustes' =>
                $ajustesAuditoria
            ];


            $this->auditoria
                ->atualizacao(
                    $empresaId,
                    $usuarioId,
                    'inventarios_stock',
                    $inventarioId,
                    $dadosAnteriores,
                    $dadosNovos
                );

            if ($movimentoId > 0) {

                $this->auditoria
                    ->insercao(
                        $empresaId,
                        $usuarioId,
                        'movimentos_estoque',
                        $movimentoId,

                        [
                            'tipo' =>
                            'AJUSTE',

                            'origem' =>
                            'INVENTARIO',

                            'inventario_id' =>
                            $inventarioId,

                            'codigo_inventario' =>
                            $inventario['codigo'],

                            'armazem_id' =>
                            $armazemId,

                            'total_ajustes' =>
                            $totalAjustes,

                            'itens' =>
                            $ajustesAuditoria
                        ]
                    );
            }

            $filialId =
                $this->filialDoArmazem(
                    $empresaId,
                    $armazemId
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'STOCK',
                    'FINALIZAR_INVENTARIO',

                    $movimentoId > 0

                        ? 'Finalizou o inventário '
                        . $inventario['codigo']
                        . ' com '
                        . $totalAjustes
                        . ' ajuste(s) de stock.'

                        : 'Finalizou o inventário '
                        . $inventario['codigo']
                        . ' sem diferenças de stock.',

                    $inventarioId,
                    $filialId
                );


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

    public function cancelar(
        int $inventarioId,
        int $empresaId,
        int $usuarioId
    ): void {

        $statusCancelado =
            $this->statusId(
                'Cancelado'
            );

        if ($statusCancelado === null) {
            throw new RuntimeException(
                'Estado Cancelado não configurado.'
            );
        }

        $this->db->beginTransaction();

        try {

            $stmt =
                $this->db->prepare("
                SELECT
                    i.id,
                    s.nome AS status_nome

                FROM inventarios_stock i

                INNER JOIN status s
                    ON s.id = i.status_id
                   AND s.tipo = 'inventario_stock'

                WHERE i.id = :id
                  AND i.empresa_id = :empresa_id

                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $inventarioId,
                ':empresa_id' => $empresaId
            ]);

            $inventario =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$inventario) {
                throw new RuntimeException(
                    'Inventário não encontrado.'
                );
            }

            if (
                $inventario['status_nome']
                !== 'Aberto'
            ) {
                throw new RuntimeException(
                    'Apenas inventários abertos podem ser cancelados.'
                );
            }

            $stmtCancelar =
                $this->db->prepare("
                UPDATE inventarios_stock

                SET
                    status_id = :status_id,
                    usuario_finalizacao_id = :usuario_id,
                    data_finalizacao = NOW()

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmtCancelar->execute([
                ':status_id' => $statusCancelado,
                ':usuario_id' => $usuarioId,
                ':id' => $inventarioId,
                ':empresa_id' => $empresaId
            ]);

            $inventarioCompleto =
                $this->buscar(
                    $inventarioId,
                    $empresaId
                );


            $this->auditoria
                ->atualizacao(
                    $empresaId,
                    $usuarioId,
                    'inventarios_stock',
                    $inventarioId,

                    [
                        'status' =>
                        'Aberto'
                    ],

                    [
                        'status' =>
                        'Cancelado',

                        'status_id' =>
                        $statusCancelado,

                        'usuario_finalizacao_id' =>
                        $usuarioId
                    ]
                );


            $armazemId =
                $inventarioCompleto
                ? (int)$inventarioCompleto['armazem_id']
                : 0;


            $filialId =
                $armazemId > 0
                ? $this->filialDoArmazem(
                    $empresaId,
                    $armazemId
                )
                : null;


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'STOCK',
                    'CANCELAR_INVENTARIO',

                    'Cancelou o inventário #'
                        . $inventarioId
                        . '.',

                    $inventarioId,
                    $filialId
                );


            $this->db->commit();
        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

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
