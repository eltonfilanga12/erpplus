<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

require_once __DIR__
    . '/../services/ContabilidadeService.php';


final class EntradaStock
{
    private PDO $db;

    private AuditoriaService $auditoria;

    private LogService $log;

    private ContabilidadeService $contabilidade;


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

        $this->contabilidade =
            new ContabilidadeService(
                $this->db
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ARMAZÉNS
    |--------------------------------------------------------------------------
    */

    public function listarArmazens(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                a.id,
                a.nome,
                a.codigo,
                a.filial_id,

                s.nome AS status_nome

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


    /*
    |--------------------------------------------------------------------------
    | PRODUTOS
    |--------------------------------------------------------------------------
    */

    public function listarProdutos(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                p.id,
                p.sku,
                p.nome,
                p.preco_custo,

                u.codigo
                    AS unidade_codigo,

                u.nome
                    AS unidade_nome,

                u.casas_decimais

            FROM produtos p

            INNER JOIN status s
                ON s.id = p.status_id
               AND s.tipo = 'produto'

            LEFT JOIN unidades u
                ON u.id = p.unidade_id
               AND u.empresa_id =
                    p.empresa_id

            WHERE p.empresa_id =
                :empresa_id

              AND s.nome =
                'Activo'

            ORDER BY
                p.nome ASC
        ";


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


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo =
                'movimento_estoque'

            ORDER BY
                CASE nome

                    WHEN 'Confirmado'
                        THEN 1

                    WHEN 'Rascunho'
                        THEN 2

                    WHEN 'Cancelado'
                        THEN 3

                    ELSE 4

                END,

                nome ASC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute();


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR ARMAZÉM
    |--------------------------------------------------------------------------
    */

    public function armazemPertenceEmpresa(
        int $armazemId,
        int $empresaId
    ): bool {

        $stmt =
            $this->db->prepare("
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


    /*
    |--------------------------------------------------------------------------
    | VALIDAR PRODUTO
    |--------------------------------------------------------------------------
    */

    public function produto(
        int $produtoId,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db->prepare("
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


    /*
    |--------------------------------------------------------------------------
    | VALIDAR STATUS
    |--------------------------------------------------------------------------
    */

    public function statusExiste(
        int $statusId
    ): bool {

        $stmt =
            $this->db->prepare("
                SELECT COUNT(*)

                FROM status

                WHERE id = :id

                  AND tipo =
                    'movimento_estoque'
            ");


        $stmt->execute([
            ':id' =>
            $statusId
        ]);


        return
            (int)$stmt->fetchColumn()
            > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS CONFIRMADO
    |--------------------------------------------------------------------------
    */

    public function statusConfirmado(): ?int
    {
        $stmt =
            $this->db->prepare("
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


    /*
    |--------------------------------------------------------------------------
    | CRIAR ENTRADA
    |--------------------------------------------------------------------------
    */

    public function criar(
        int $empresaId,
        int $usuarioId,
        int $armazemId,
        int $statusId,
        string $dataMovimento,
        ?string $observacoes,
        array $itens,
        string $origemContabilistica
    ): int {

        $this->db
            ->beginTransaction();


        try {

            /*
            |--------------------------------------------------------------------------
            | 1. CABEÇALHO
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
                        NULL,
                        :armazem_destino_id,
                        :usuario_id,
                        :status_id,
                        'ENTRADA',
                        :observacoes,
                        :data_movimento
                    )
                ");


            $stmtMovimento->execute([
                ':empresa_id' =>
                $empresaId,

                ':armazem_destino_id' =>
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
                    'Não foi possível criar o movimento de entrada.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 2. PREPARAR ITEM
            |--------------------------------------------------------------------------
            */

            $stmtItem =
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
            | 3. ITENS
            |--------------------------------------------------------------------------
            */

            $itensAuditoria =
                [];


            $quantidadeTotal =
                0.0;


            $valorTotal =
                0.0;


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


                $custoUnitario =
                    (float)(
                        $item['custo_unitario']
                        ?? 0
                    );


                if ($produtoId <= 0) {

                    throw new RuntimeException(
                        'Produto inválido na entrada de stock.'
                    );
                }


                if ($quantidade <= 0) {

                    throw new RuntimeException(
                        'Quantidade inválida na entrada de stock.'
                    );
                }


                if ($custoUnitario <= 0) {

                    throw new RuntimeException(
                        'O custo unitário deve ser superior a zero.'
                    );
                }


                /*
                 * Histórico do movimento.
                 */
                $stmtItem->execute([
                    ':movimento_id' =>
                    $movimentoId,

                    ':produto_id' =>
                    $produtoId,

                    ':quantidade' =>
                    $quantidade,

                    ':custo_unitario' =>
                    $custoUnitario
                ]);


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


                /*
                 * Informação rica para auditoria.
                 */
                $itensAuditoria[] = [

                    'produto_id' =>
                    $produtoId,

                    'sku' =>
                    $item['produto_sku']
                        ?? null,

                    'produto' =>
                    $item['produto_nome']
                        ?? null,

                    'quantidade' =>
                    $quantidade,

                    'custo_unitario' =>
                    $custoUnitario,

                    'subtotal' =>
                    $subtotal
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | 4. APLICAR MOVIMENTO AO STOCK
            |--------------------------------------------------------------------------
            |
            | Mantemos o mecanismo já existente.
            |
            */

            EstoqueService::aplicarMovimento(
                $movimentoId,
                $this->db
            );

            $filialId =
                $this->filialDoArmazem(
                    $empresaId,
                    $armazemId
                );

            $lancamentoId =
                $this->contabilizarEntrada(
                    $empresaId,
                    $usuarioId,
                    $filialId,
                    $movimentoId,
                    $dataMovimento,
                    $origemContabilistica,
                    round($valorTotal, 2),
                    $observacoes
                );


            /*
            |--------------------------------------------------------------------------
            | 5. AUDITORIA
            |--------------------------------------------------------------------------
            */

            $this->auditoria
                ->insercao(
                    $empresaId,
                    $usuarioId,
                    'movimentos_estoque',
                    $movimentoId,

                    [
                        'tipo' =>
                        'ENTRADA',

                        'armazem_origem_id' =>
                        null,

                        'armazem_destino_id' =>
                        $armazemId,

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


            /*
            |--------------------------------------------------------------------------
            | 6. FILIAL DO ARMAZÉM
            |--------------------------------------------------------------------------
            */

            $filialId =
                $this->filialDoArmazem(
                    $empresaId,
                    $armazemId
                );


            /*
            |--------------------------------------------------------------------------
            | 7. LOG
            |--------------------------------------------------------------------------
            */

            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'STOCK',
                    'ENTRADA',

                    'Registou a entrada de stock #'
                        . $movimentoId
                        . ' no armazém ID '
                        . $armazemId
                        . '.',

                    $movimentoId,
                    $filialId
                );


            /*
            |--------------------------------------------------------------------------
            | 8. COMMIT
            |--------------------------------------------------------------------------
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


    /*
    |--------------------------------------------------------------------------
    | CONTABILIZAR ENTRADA MANUAL DE STOCK
    |--------------------------------------------------------------------------
    */

    private function contabilizarEntrada(
        int $empresaId,
        int $usuarioId,
        ?int $filialId,
        int $movimentoId,
        string $dataMovimento,
        string $origemContabilistica,
        float $valorTotal,
        ?string $observacoes
    ): int {

        $origemContabilistica =
            strtoupper(trim($origemContabilistica));

        if (
            !in_array(
                $origemContabilistica,
                [
                    'STOCK_INICIAL',
                    'CAPITAL_SOCIOS',
                    'AJUSTE_POSITIVO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Origem contabilística da entrada inválida.'
            );
        }

        if ($valorTotal <= 0) {
            throw new RuntimeException(
                'O valor contabilístico da entrada deve ser superior a zero.'
            );
        }

        $origemLancamento =
            $origemContabilistica === 'AJUSTE_POSITIVO'
            ? 'STOCK'
            : 'ABERTURA';

        if (
            $this->contabilidade
            ->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                $origemLancamento,
                $movimentoId
            )
        ) {
            throw new RuntimeException(
                'Esta entrada de stock já foi contabilizada.'
            );
        }

        $data =
            substr($dataMovimento, 0, 10);

        $contexto =
            $this->contabilidade
            ->contextoData(
                $empresaId,
                $data
            );

        $diario =
            $this->contabilidade
            ->diarioPorTipo(
                $empresaId,
                'STOCK'
            );

        $contaStock =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'STOCK'
            );

        $contaContrapartida =
            $this->contaContrapartidaEntrada(
                $empresaId,
                $origemContabilistica
            );

        $descricao =
            match ($origemContabilistica) {
                'STOCK_INICIAL' =>
                'Reconhecimento de stock inicial',

                'CAPITAL_SOCIOS' =>
                'Entrada de stock como capital dos sócios',

                'AJUSTE_POSITIVO' =>
                'Ajuste positivo de inventário'
            };

        if (
            $observacoes !== null
            && trim($observacoes) !== ''
        ) {
            $descricao .=
                ' - ' . trim($observacoes);
        }

        return $this->contabilidade
            ->criarLancamento(
                [
                    'empresa_id' => $empresaId,
                    'filial_id' => $filialId,
                    'exercicio_id' => (int)$contexto['exercicio_id'],
                    'periodo_id' => (int)$contexto['periodo_id'],
                    'diario_id' => (int)$diario['id'],
                    'numero_documento' => 'ENT-STOCK-' . $movimentoId,
                    'referencia' => 'ENTRADA-STOCK-' . $movimentoId,
                    'data_lancamento' => $data,
                    'data_documento' => $data,
                    'descricao' => $descricao,
                    'origem' => $origemLancamento,
                    'origem_id' => $movimentoId,
                    'usuario_id' => $usuarioId
                ],
                [
                    [
                        'conta_contabil_id' => (int)$contaStock['id'],
                        'centro_custo_id' => null,
                        'cliente_id' => null,
                        'fornecedor_id' => null,
                        'descricao' => $descricao,
                        'debito' => $valorTotal,
                        'credito' => 0
                    ],
                    [
                        'conta_contabil_id' => (int)$contaContrapartida['id'],
                        'centro_custo_id' => null,
                        'cliente_id' => null,
                        'fornecedor_id' => null,
                        'descricao' => 'Contrapartida - ' . $descricao,
                        'debito' => 0,
                        'credito' => $valorTotal
                    ]
                ]
            );
    }


    private function contaContrapartidaEntrada(
        int $empresaId,
        string $origemContabilistica
    ): array {

        if ($origemContabilistica === 'STOCK_INICIAL') {
            return $this->contabilidade
                ->contaIntegracao(
                    $empresaId,
                    'RESULTADOS_TRANSITADOS'
                );
        }

        $codigoConta =
            $origemContabilistica === 'CAPITAL_SOCIOS'
            ? '5.1'
            : '7.4.1.1';

        $stmt = $this->db->prepare("
            SELECT id, codigo, nome
            FROM contas_contabilisticas
            WHERE empresa_id = :empresa_id
              AND codigo = :codigo
              AND tipo = 'ANALITICA'
              AND aceita_lancamento = 1
            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':codigo' => $codigoConta
        ]);

        $conta =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conta) {
            throw new RuntimeException(
                "A conta contabilística {$codigoConta} não está configurada para lançamentos."
            );
        }

        return $conta;
    }


    /*
    |--------------------------------------------------------------------------
    | FILIAL DO ARMAZÉM
    |--------------------------------------------------------------------------
    */

    private function filialDoArmazem(
        int $empresaId,
        int $armazemId
    ): ?int {

        $stmt =
            $this->db->prepare("
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
