<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/EstoqueService.php';

require_once __DIR__
    . '/../services/ContabilidadeService.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class Compra
{
    private PDO $pdo;

    private ContabilidadeService $contabilidade;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct(
        PDO $pdo
    ) {
        $this->pdo =
            $pdo;


        $this->contabilidade =
            new ContabilidadeService(
                $pdo
            );


        $this->auditoria =
            new AuditoriaService(
                $pdo
            );


        $this->log =
            new LogService(
                $pdo
            );
    }
    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId,
        ?int $filialId = null
    ): array {

        $this->validarEmpresa($empresaId);

        $sql = "
            SELECT
                compra.id,
                compra.empresa_id,
                compra.filial_id,
                compra.centro_custo_id,
                compra.fornecedor_id,
                compra.forma_pagamento_id,
                compra.armazem_id,
                compra.usuario_id,
                compra.status_id,

                compra.condicao_pagamento,
                compra.numero_documento,
                compra.data_compra,
                compra.data_vencimento,

                compra.subtotal,
                compra.desconto,
                compra.imposto,
                compra.total,

                compra.conta_pagar_id,
                compra.movimento_estoque_id,

                compra.observacoes,
                compra.criado_em,

                fornecedor.nome AS fornecedor_nome,

                armazem.nome AS armazem_nome,

                forma.nome AS forma_pagamento_nome,

                status.nome AS status_nome,

                COALESCE(
                    filial.nome,
                    'Sede Principal / Sem filial'
                ) AS filial_nome,

                COALESCE(
                    centro.nome,
                    'Sem centro de custo'
                ) AS centro_custo_nome,

                centro.codigo AS centro_custo_codigo

            FROM compras compra

            INNER JOIN fornecedores fornecedor
                ON fornecedor.id = compra.fornecedor_id

            INNER JOIN armazens armazem
                ON armazem.id = compra.armazem_id

            LEFT JOIN formas_pagamento forma
                ON forma.id = compra.forma_pagamento_id

            INNER JOIN status
                ON status.id = compra.status_id
                AND status.tipo = 'compra'

            LEFT JOIN filiais filial
                ON filial.id = compra.filial_id
                AND filial.empresa_id = compra.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id = compra.centro_custo_id
                AND centro.empresa_id = compra.empresa_id

            WHERE compra.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND compra.filial_id = :filial_id
            ";

            $params[':filial_id'] = $filialId;
        }

        $sql .= "
            ORDER BY compra.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $this->validarEmpresa($empresaId);

        if ($id <= 0) {
            throw new RuntimeException(
                'Compra inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                compra.*,

                fornecedor.nome AS fornecedor_nome,

                armazem.nome AS armazem_nome,

                forma.nome AS forma_pagamento_nome,

                status.nome AS status_nome,

                COALESCE(
                    filial.nome,
                    'Sede Principal / Sem filial'
                ) AS filial_nome,

                COALESCE(
                    centro.nome,
                    'Sem centro de custo'
                ) AS centro_custo_nome

            FROM compras compra

            INNER JOIN fornecedores fornecedor
                ON fornecedor.id = compra.fornecedor_id

            INNER JOIN armazens armazem
                ON armazem.id = compra.armazem_id

            LEFT JOIN formas_pagamento forma
                ON forma.id = compra.forma_pagamento_id

            INNER JOIN status
                ON status.id = compra.status_id
                AND status.tipo = 'compra'

            LEFT JOIN filiais filial
                ON filial.id = compra.filial_id
                AND filial.empresa_id = compra.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id = compra.centro_custo_id
                AND centro.empresa_id = compra.empresa_id

            WHERE compra.id = :id
              AND compra.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }

    // ==================================================
    // CONTABILIZAR COMPRA
    // ==================================================

    private function contabilizarCompra(
        array $compra
    ): ?int {

        $empresaId =
            (int)$compra['empresa_id'];

        $filialId =
            !empty($compra['filial_id'])
            ? (int)$compra['filial_id']
            : null;

        $compraId =
            (int)$compra['id'];

        if (
            $this->contabilidade
            ->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                'COMPRA',
                $compraId
            )
        ) {
            return null;
        }

        $dataCompra =
            substr(
                (string)$compra['data_compra'],
                0,
                10
            );

        $contexto =
            $this->contabilidade
            ->contextoData(
                $empresaId,
                $dataCompra
            );

        $diario =
            $this->contabilidade
            ->diarioPorTipo(
                $empresaId,
                'COMPRAS'
            );

        $contaFornecedores =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'FORNECEDORES'
            );

        $contaStock =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'STOCK'
            );

        $total =
            round(
                (float)$compra['total'],
                2
            );

        $imposto =
            round(
                (float)$compra['imposto'],
                2
            );

        $valorStock =
            round(
                $total - $imposto,
                2
            );

        if ($total <= 0) {
            throw new RuntimeException(
                'O total da compra é inválido para contabilização.'
            );
        }

        if ($valorStock <= 0) {
            throw new RuntimeException(
                'O valor do stock da compra é inválido.'
            );
        }

        $itens = [
            [
                'conta_contabil_id' =>
                (int)$contaStock['id'],

                'centro_custo_id' =>
                !empty($compra['centro_custo_id'])
                    ? (int)$compra['centro_custo_id']
                    : null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                null,

                'descricao' =>
                'Entrada de stock da compra '
                    . $compra['numero_documento'],

                'debito' =>
                $valorStock,

                'credito' =>
                0
            ]
        ];

        if ($imposto > 0) {

            $contaIvaDedutivel =
                $this->contabilidade
                ->contaIntegracao(
                    $empresaId,
                    'IVA_DEDUTIVEL'
                );

            $itens[] = [
                'conta_contabil_id' =>
                (int)$contaIvaDedutivel['id'],

                'centro_custo_id' =>
                null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                null,

                'descricao' =>
                'IVA dedutível da compra '
                    . $compra['numero_documento'],

                'debito' =>
                $imposto,

                'credito' =>
                0
            ];
        }

        $itens[] = [
            'conta_contabil_id' =>
            (int)$contaFornecedores['id'],

            'centro_custo_id' =>
            null,

            'cliente_id' =>
            null,

            'fornecedor_id' =>
            (int)$compra['fornecedor_id'],

            'descricao' =>
            'Valor a pagar ao fornecedor da compra '
                . $compra['numero_documento'],

            'debito' =>
            0,

            'credito' =>
            $total
        ];

        return $this->contabilidade
            ->criarLancamento(
                [
                    'empresa_id' =>
                    $empresaId,

                    'filial_id' =>
                    $filialId,

                    'exercicio_id' =>
                    (int)$contexto['exercicio_id'],

                    'periodo_id' =>
                    (int)$contexto['periodo_id'],

                    'diario_id' =>
                    (int)$diario['id'],

                    'numero_documento' =>
                    $compra['numero_documento']
                        ?? null,

                    'referencia' =>
                    'COMPRA-' . $compraId,

                    'data_lancamento' =>
                    $dataCompra,

                    'data_documento' =>
                    $dataCompra,

                    'descricao' =>
                    'Compra '
                        . (
                            $compra['numero_documento']
                            ?? '#' . $compraId
                        ),

                    'origem' =>
                    'COMPRA',

                    'origem_id' =>
                    $compraId,

                    'usuario_id' =>
                    (int)$compra['usuario_id']
                ],
                $itens
            );
    }

    // ==================================================
    // BUSCAR ITENS
    // ==================================================

    public function buscarItens(
        int $compraId,
        int $empresaId
    ): array {

        $this->validarEmpresa($empresaId);

        $stmt = $this->pdo->prepare("
            SELECT
                item.*,
                produto.nome AS produto_nome

            FROM compra_itens item

            INNER JOIN compras compra
                ON compra.id = item.compra_id

            INNER JOIN produtos produto
                ON produto.id = item.produto_id

            WHERE item.compra_id = :compra_id
              AND compra.empresa_id = :empresa_id

            ORDER BY item.id ASC
        ");

        $stmt->execute([
            ':compra_id' => $compraId,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // SALVAR
    // ==================================================

    public function salvar(
        array $dados
    ): int {

        $dados =
            $this->normalizarDados(
                $dados
            );


        $this->validarDados(
            $dados
        );


        $this->pdo
            ->beginTransaction();


        try {

            $novo =
                $dados['id'] === null;


            $dadosAnteriores =
                null;


            if (!$novo) {

                $dadosAnteriores =
                    $this->buscar(
                        (int)$dados['id'],
                        (int)$dados['empresa_id']
                    );


                if (!$dadosAnteriores) {

                    throw new RuntimeException(
                        'Compra não encontrada.'
                    );
                }


                $dadosAnteriores['itens'] =
                    $this->buscarItens(
                        (int)$dados['id'],
                        (int)$dados['empresa_id']
                    );
            }


            if ($novo) {

                $compraId =
                    $this->criarCompra(
                        $dados
                    );
            } else {

                $compraId =
                    $this->atualizarCompra(
                        $dados
                    );
            }


            $compra =
                $this->bloquearCompra(
                    $compraId,
                    $dados['empresa_id']
                );


            $statusConfirmada =
                $this->statusCompra(
                    'Confirmada'
                );


            if (
                (int)$compra['status_id']
                === $statusConfirmada
            ) {

                $this->confirmarCompra(
                    $compra,
                    $dados
                );
            }


            /*
         * Recarregamos depois de toda a integração.
         *
         * Assim auditoria também vê:
         * - movimento_estoque_id
         * - conta_pagar_id
         * - status
         * - totais definitivos
         */
            $dadosNovos =
                $this->buscar(
                    $compraId,
                    (int)$dados['empresa_id']
                );


            if (!$dadosNovos) {

                throw new RuntimeException(
                    'Não foi possível recarregar a compra.'
                );
            }


            $dadosNovos['itens'] =
                $this->buscarItens(
                    $compraId,
                    (int)$dados['empresa_id']
                );


            if ($novo) {

                $this->auditoria
                    ->insercao(
                        (int)$dados['empresa_id'],
                        (int)$dados['usuario_id'],
                        'compras',
                        $compraId,
                        $this->dadosAuditoriaCompra(
                            $dadosNovos
                        )
                    );
            } else {

                $this->auditoria
                    ->atualizacao(
                        (int)$dados['empresa_id'],
                        (int)$dados['usuario_id'],
                        'compras',
                        $compraId,
                        $this->dadosAuditoriaCompra(
                            $dadosAnteriores
                        ),
                        $this->dadosAuditoriaCompra(
                            $dadosNovos
                        )
                    );
            }


            $this->log
                ->registrar(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'COMPRAS',
                    $novo
                        ? 'CRIAR'
                        : 'EDITAR',

                    $novo
                        ? 'Registou a compra '
                        . (
                            $dadosNovos['numero_documento']
                            ?? '#' . $compraId
                        )
                        . '.'
                        : 'Atualizou a compra '
                        . (
                            $dadosNovos['numero_documento']
                            ?? '#' . $compraId
                        )
                        . '.',

                    $compraId,

                    !empty($dadosNovos['filial_id'])
                        ? (int)$dadosNovos['filial_id']
                        : null
                );


            $this->pdo
                ->commit();


            return $compraId;
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {

                $this->pdo
                    ->rollBack();
            }


            throw $e;
        }
    }

    private function dadosAuditoriaCompra(
        array $compra
    ): array {

        $itensAuditoria =
            [];


        foreach (
            $compra['itens']
                ?? []
            as $item
        ) {

            $itensAuditoria[] = [

                'produto_id' =>
                isset($item['produto_id'])
                    ? (int)$item['produto_id']
                    : null,

                'produto' =>
                $item['produto_nome']
                    ?? null,

                'quantidade' =>
                isset($item['quantidade'])
                    ? (float)$item['quantidade']
                    : 0,

                'preco_unitario' =>
                isset($item['preco_unitario'])
                    ? (float)$item['preco_unitario']
                    : 0,

                'desconto' =>
                isset($item['desconto'])
                    ? (float)$item['desconto']
                    : 0,

                'imposto' =>
                isset($item['imposto'])
                    ? (float)$item['imposto']
                    : 0,

                'subtotal' =>
                isset($item['subtotal'])
                    ? (float)$item['subtotal']
                    : 0
            ];
        }


        return [

            'empresa_id' =>
            isset($compra['empresa_id'])
                ? (int)$compra['empresa_id']
                : null,

            'filial_id' =>
            !empty($compra['filial_id'])
                ? (int)$compra['filial_id']
                : null,

            'centro_custo_id' =>
            !empty($compra['centro_custo_id'])
                ? (int)$compra['centro_custo_id']
                : null,

            'fornecedor_id' =>
            isset($compra['fornecedor_id'])
                ? (int)$compra['fornecedor_id']
                : null,

            'forma_pagamento_id' =>
            !empty($compra['forma_pagamento_id'])
                ? (int)$compra['forma_pagamento_id']
                : null,

            'condicao_pagamento' =>
            $compra['condicao_pagamento']
                ?? null,

            'armazem_id' =>
            isset($compra['armazem_id'])
                ? (int)$compra['armazem_id']
                : null,

            'status_id' =>
            isset($compra['status_id'])
                ? (int)$compra['status_id']
                : null,

            'status' =>
            $compra['status_nome']
                ?? null,

            'numero_documento' =>
            $compra['numero_documento']
                ?? null,

            'data_compra' =>
            $compra['data_compra']
                ?? null,

            'data_vencimento' =>
            $compra['data_vencimento']
                ?? null,

            'subtotal' =>
            isset($compra['subtotal'])
                ? (float)$compra['subtotal']
                : 0,

            'desconto' =>
            isset($compra['desconto'])
                ? (float)$compra['desconto']
                : 0,

            'imposto' =>
            isset($compra['imposto'])
                ? (float)$compra['imposto']
                : 0,

            'total' =>
            isset($compra['total'])
                ? (float)$compra['total']
                : 0,

            'conta_pagar_id' =>
            !empty($compra['conta_pagar_id'])
                ? (int)$compra['conta_pagar_id']
                : null,

            'movimento_estoque_id' =>
            !empty($compra['movimento_estoque_id'])
                ? (int)$compra['movimento_estoque_id']
                : null,

            'observacoes' =>
            $compra['observacoes']
                ?? null,

            'itens' =>
            $itensAuditoria
        ];
    }

    // ==================================================
    // CRIAR COMPRA
    // ==================================================

    private function criarCompra(
        array $dados
    ): int {

        $this->validarRelacionamentos($dados);

        $stmt = $this->pdo->prepare("
            INSERT INTO compras
            (
                empresa_id,
                filial_id,
                centro_custo_id,

                fornecedor_id,
                forma_pagamento_id,
                condicao_pagamento,

                armazem_id,
                usuario_id,
                status_id,

                numero_documento,
                data_compra,
                data_vencimento,

                observacoes,

                subtotal,
                desconto,
                imposto_habilitado,
                taxa_imposto,
                imposto,
                total
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :centro_custo_id,

                :fornecedor_id,
                :forma_pagamento_id,
                :condicao_pagamento,

                :armazem_id,
                :usuario_id,
                :status_id,

                :numero_documento,
                :data_compra,
                :data_vencimento,

                :observacoes,

                :subtotal,
                :desconto,
                :imposto_habilitado,
                :taxa_imposto,
                :imposto,
                :total
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $dados['empresa_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':centro_custo_id' =>
            $dados['centro_custo_id'],

            ':fornecedor_id' =>
            $dados['fornecedor_id'],

            ':forma_pagamento_id' =>
            $dados['forma_pagamento_id'],

            ':condicao_pagamento' =>
            $dados['condicao_pagamento'],

            ':armazem_id' =>
            $dados['armazem_id'],

            ':usuario_id' =>
            $dados['usuario_id'],

            ':status_id' =>
            $dados['status_id'],

            ':numero_documento' =>
            $dados['numero_documento'],

            ':data_compra' =>
            $dados['data_compra'],

            ':data_vencimento' =>
            $dados['data_vencimento'],

            ':observacoes' =>
            $dados['observacoes'],

            ':subtotal' =>
            $dados['subtotal'],

            ':desconto' =>
            $dados['desconto'],

            ':imposto_habilitado' =>
            $dados['imposto_habilitado']
                ? 1
                : 0,

            ':taxa_imposto' =>
            $dados['taxa_imposto'],

            ':imposto' =>
            $dados['imposto'],

            ':total' =>
            $dados['total'],
        ]);

        $compraId = (int)$this->pdo
            ->lastInsertId();

        $this->gravarItens(
            $compraId,
            $dados['itens']
        );

        return $compraId;
    }

    // ==================================================
    // ATUALIZAR COMPRA
    // ==================================================

    private function atualizarCompra(
        array $dados
    ): int {

        $compraAtual = $this->bloquearCompra(
            (int)$dados['id'],
            $dados['empresa_id']
        );

        $statusConfirmada =
            $this->statusCompra('Confirmada');

        $statusCancelada =
            $this->statusCompra('Cancelada');

        if (
            (int)$compraAtual['status_id']
            === $statusCancelada
        ) {
            throw new RuntimeException(
                'Uma compra cancelada não pode ser editada.'
            );
        }

        /*
         * Compra já confirmada não pode ter produtos,
         * valores ou dados financeiros modificados.
         */

        if (
            (int)$compraAtual['status_id']
            === $statusConfirmada
        ) {
            if (
                (int)$dados['status_id']
                !== $statusConfirmada
            ) {
                throw new RuntimeException(
                    'Uma compra confirmada não pode voltar para Rascunho ou ser cancelada por edição.'
                );
            }

            throw new RuntimeException(
                'A compra já está confirmada. Para alterar produtos ou valores será necessário criar um processo de estorno.'
            );
        }

        $this->validarRelacionamentos($dados);

        $stmt = $this->pdo->prepare("
            UPDATE compras
            SET
                filial_id =
                    :filial_id,

                centro_custo_id =
                    :centro_custo_id,

                fornecedor_id =
                    :fornecedor_id,

                forma_pagamento_id =
                    :forma_pagamento_id,

                condicao_pagamento =
                    :condicao_pagamento,

                armazem_id =
                    :armazem_id,

                status_id =
                    :status_id,

                numero_documento =
                    :numero_documento,

                data_compra =
                    :data_compra,

                data_vencimento =
                    :data_vencimento,

                observacoes =
                    :observacoes,

                subtotal =
                    :subtotal,

                desconto =
                    :desconto,

                imposto_habilitado =
                    :imposto_habilitado,

                taxa_imposto =
                    :taxa_imposto,

                imposto =
                    :imposto,

                total =
                    :total

            WHERE id = :id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':filial_id' =>
            $dados['filial_id'],

            ':centro_custo_id' =>
            $dados['centro_custo_id'],

            ':fornecedor_id' =>
            $dados['fornecedor_id'],

            ':forma_pagamento_id' =>
            $dados['forma_pagamento_id'],

            ':condicao_pagamento' =>
            $dados['condicao_pagamento'],

            ':armazem_id' =>
            $dados['armazem_id'],

            ':status_id' =>
            $dados['status_id'],

            ':numero_documento' =>
            $dados['numero_documento'],

            ':data_compra' =>
            $dados['data_compra'],

            ':data_vencimento' =>
            $dados['data_vencimento'],

            ':observacoes' =>
            $dados['observacoes'],

            ':subtotal' =>
            $dados['subtotal'],

            ':desconto' =>
            $dados['desconto'],

            ':imposto_habilitado' =>
            $dados['imposto_habilitado']
                ? 1
                : 0,

            ':taxa_imposto' =>
            $dados['taxa_imposto'],

            ':imposto' =>
            $dados['imposto'],

            ':total' =>
            $dados['total'],

            ':id' =>
            $dados['id'],

            ':empresa_id' =>
            $dados['empresa_id']
        ]);

        $stmt = $this->pdo->prepare("
            DELETE FROM compra_itens

            WHERE compra_id = :compra_id
        ");

        $stmt->execute([
            ':compra_id' => $dados['id']
        ]);

        $this->gravarItens(
            (int)$dados['id'],
            $dados['itens']
        );

        return (int)$dados['id'];
    }

    private function registarPagamentoAutomaticoCompra(
        array $compra,
        array $dados,
        int $contaPagarId
    ): void {

        $valor =
            round(
                (float)$compra['total'],
                2
            );

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor do pagamento da compra é inválido.'
            );
        }

        $origem =
            strtoupper(
                trim(
                    (string)(
                        $dados['origem_pagamento']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $origem,
                [
                    'CAIXA',
                    'BANCO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Origem do pagamento inválida.'
            );
        }


        if ($origem === 'CAIXA') {

            $this->registarSaidaCaixaCompra(
                $compra,
                $dados,
                $valor,
                $contaPagarId
            );
        } else {

            $this->registarSaidaBancoCompra(
                $compra,
                $dados,
                $valor,
                $contaPagarId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | LANÇAMENTO CONTABILÍSTICO DO PAGAMENTO
        |--------------------------------------------------------------------------
        |
        | A compra reconhece inicialmente:
        |   Débito  - Stock / IVA dedutível
        |   Crédito - Fornecedores
        |
        | O pagamento à vista precisa de um segundo lançamento:
        |   Débito  - Fornecedores
        |   Crédito - Caixa ou Banco
        |
        */

        $this->contabilizarPagamentoCompra(
            $compra,
            $dados,
            $contaPagarId,
            $valor
        );


        /*
    |--------------------------------------------------------------------------
    | HISTÓRICO DO PAGAMENTO
    |--------------------------------------------------------------------------
    */

        $stmt = $this->pdo->prepare("
        INSERT INTO pagamentos_contas_pagar
        (
            empresa_id,
            filial_id,
            centro_custo_id,
            conta_pagar_id,
            forma_pagamento_id,
            valor,
            data_pagamento,
            numero_documento,
            observacoes,
            criado_por
        )
        VALUES
        (
            :empresa_id,
            :filial_id,
            :centro_custo_id,
            :conta_pagar_id,
            :forma_pagamento_id,
            :valor,
            :data_pagamento,
            :numero_documento,
            :observacoes,
            :criado_por
        )
    ");

        $stmt->execute([
            ':empresa_id' =>
            $compra['empresa_id'],

            ':filial_id' =>
            $compra['filial_id'],

            ':centro_custo_id' =>
            $compra['centro_custo_id'],

            ':conta_pagar_id' =>
            $contaPagarId,

            ':forma_pagamento_id' =>
            $compra['forma_pagamento_id'],

            ':valor' =>
            $valor,

            ':data_pagamento' =>
            $compra['data_compra'],

            ':numero_documento' =>
            $compra['numero_documento'],

            ':observacoes' =>
            'Pagamento automático da compra à vista.',

            ':criado_por' =>
            $compra['usuario_id']
        ]);


        /*
    |--------------------------------------------------------------------------
    | LIQUIDAR CONTA A PAGAR
    |--------------------------------------------------------------------------
    */

        $statusPago =
            $this->statusPorTipoNome(
                'conta_pagar',
                'Pago'
            );

        $stmt = $this->pdo->prepare("
        UPDATE contas_pagar

        SET
            valor_pago = :valor,
            saldo = 0,
            status_id = :status_id,
            data_pagamento = :data_pagamento,
            atualizado_por = :usuario_id

        WHERE id = :id
          AND empresa_id = :empresa_id
    ");

        $stmt->execute([
            ':valor' =>
            $valor,

            ':status_id' =>
            $statusPago,

            ':data_pagamento' =>
            $compra['data_compra'],

            ':usuario_id' =>
            $compra['usuario_id'],

            ':id' =>
            $contaPagarId,

            ':empresa_id' =>
            $compra['empresa_id']
        ]);
    }


    // ==================================================
    // CONTABILIZAR PAGAMENTO AUTOMÁTICO DA COMPRA
    // ==================================================

    private function contabilizarPagamentoCompra(
        array $compra,
        array $dados,
        int $contaPagarId,
        float $valor
    ): ?int {

        $empresaId =
            (int)$compra['empresa_id'];

        $filialId =
            !empty($compra['filial_id'])
            ? (int)$compra['filial_id']
            : null;

        if ($contaPagarId <= 0) {
            throw new RuntimeException(
                'Conta a pagar inválida para contabilização do pagamento.'
            );
        }

        $valor =
            round($valor, 2);

        if ($valor <= 0) {
            throw new RuntimeException(
                'Valor inválido para contabilização do pagamento.'
            );
        }

        if (
            $this->contabilidade
            ->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                'PAGAMENTO_COMPRA',
                $contaPagarId
            )
        ) {
            return null;
        }

        $origem =
            strtoupper(
                trim(
                    (string)(
                        $dados['origem_pagamento']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $origem,
                ['CAIXA', 'BANCO'],
                true
            )
        ) {
            throw new RuntimeException(
                'Origem do pagamento inválida para contabilização.'
            );
        }

        $dataPagamento =
            substr(
                (string)$compra['data_compra'],
                0,
                10
            );

        $contexto =
            $this->contabilidade
            ->contextoData(
                $empresaId,
                $dataPagamento
            );

        $tipoDiario =
            $origem === 'CAIXA'
            ? 'CAIXA'
            : 'BANCOS';

        $diario =
            $this->contabilidade
            ->diarioPorTipo(
                $empresaId,
                $tipoDiario
            );

        $contaFornecedores =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'FORNECEDORES'
            );

        $contaPagamento =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                $origem === 'CAIXA'
                    ? 'CAIXA'
                    : 'BANCOS'
            );

        $documento =
            $compra['numero_documento']
            ?? 'COMPRA-' . $compra['id'];

        return $this->contabilidade
            ->criarLancamento(
                [
                    'empresa_id' =>
                    $empresaId,

                    'filial_id' =>
                    $filialId,

                    'exercicio_id' =>
                    (int)$contexto['exercicio_id'],

                    'periodo_id' =>
                    (int)$contexto['periodo_id'],

                    'diario_id' =>
                    (int)$diario['id'],

                    'numero_documento' =>
                    $documento,

                    'referencia' =>
                    'PAGAMENTO-COMPRA-'
                        . $compra['id'],

                    'data_lancamento' =>
                    $dataPagamento,

                    'data_documento' =>
                    $dataPagamento,

                    'descricao' =>
                    'Pagamento da compra '
                        . $documento,

                    'origem' =>
                    'PAGAMENTO_COMPRA',

                    'origem_id' =>
                    $contaPagarId,

                    'usuario_id' =>
                    (int)$compra['usuario_id']
                ],
                [
                    [
                        'conta_contabil_id' =>
                        (int)$contaFornecedores['id'],

                        'centro_custo_id' =>
                        null,

                        'cliente_id' =>
                        null,

                        'fornecedor_id' =>
                        !empty($compra['fornecedor_id'])
                            ? (int)$compra['fornecedor_id']
                            : null,

                        'descricao' =>
                        'Liquidação do fornecedor da compra '
                            . $documento,

                        'debito' =>
                        $valor,

                        'credito' =>
                        0
                    ],
                    [
                        'conta_contabil_id' =>
                        (int)$contaPagamento['id'],

                        'centro_custo_id' =>
                        !empty($compra['centro_custo_id'])
                            ? (int)$compra['centro_custo_id']
                            : null,

                        'cliente_id' =>
                        null,

                        'fornecedor_id' =>
                        null,

                        'descricao' =>
                        'Saída por pagamento da compra '
                            . $documento,

                        'debito' =>
                        0,

                        'credito' =>
                        $valor
                    ]
                ]
            );
    }

    private function registarSaidaCaixaCompra(
        array $compra,
        array $dados,
        float $valor,
        int $contaPagarId
    ): void {

        $caixaId =
            (int)(
                $dados['caixa_id']
                ?? 0
            );

        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
        SELECT
            c.id,
            c.saldo_atual,
            c.status_id,

            (
                SELECT ac.id

                FROM abertura_caixa ac

                WHERE ac.caixa_id = c.id
                  AND ac.empresa_id = c.empresa_id
                  AND ac.data_fechamento IS NULL

                ORDER BY ac.id DESC

                LIMIT 1
            ) AS abertura_id

        FROM caixas c

        WHERE c.id = :id
          AND c.empresa_id = :empresa_id

        FOR UPDATE
    ");

        $stmt->execute([
            ':id' =>
            $caixaId,

            ':empresa_id' =>
            $compra['empresa_id']
        ]);

        $caixa =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$caixa) {
            throw new RuntimeException(
                'Caixa não encontrado.'
            );
        }

        $statusAberto =
            $this->statusPorTipoNome(
                'caixa',
                'Aberto'
            );

        if (
            (int)$caixa['status_id']
            !== $statusAberto
        ) {
            throw new RuntimeException(
                'O caixa selecionado não está aberto.'
            );
        }

        if (empty($caixa['abertura_id'])) {
            throw new RuntimeException(
                'O caixa selecionado não possui abertura activa.'
            );
        }

        if (
            (float)$caixa['saldo_atual']
            < $valor
        ) {
            throw new RuntimeException(
                'Saldo insuficiente no caixa selecionado.'
            );
        }


        $stmt = $this->pdo->prepare("
        INSERT INTO movimentos_caixa
        (
            empresa_id,
            filial_id,
            centro_custo_id,
            caixa_id,
            abertura_id,
            usuario_id,
            tipo,
            origem,
            documento_id,
            descricao,
            valor,
            data_movimento
        )
        VALUES
        (
            :empresa_id,
            :filial_id,
            :centro_custo_id,
            :caixa_id,
            :abertura_id,
            :usuario_id,
            'SAIDA',
            'PAGAMENTO',
            :documento_id,
            :descricao,
            :valor,
            :data_movimento
        )
    ");

        $stmt->execute([
            ':empresa_id' =>
            $compra['empresa_id'],

            ':filial_id' =>
            $compra['filial_id'],

            ':centro_custo_id' =>
            $compra['centro_custo_id'],

            ':caixa_id' =>
            $caixaId,

            ':abertura_id' =>
            (int)$caixa['abertura_id'],

            ':usuario_id' =>
            $compra['usuario_id'],

            ':documento_id' =>
            $contaPagarId,

            ':descricao' =>
            'Pagamento automático da compra '
                . (
                    $compra['numero_documento']
                    ?? '#' . $compra['id']
                ),

            ':valor' =>
            $valor,

            ':data_movimento' =>
            $compra['data_compra']
                . ' '
                . date('H:i:s')
        ]);


        $stmt = $this->pdo->prepare("
        UPDATE caixas

        SET saldo_atual =
            saldo_atual - :valor

        WHERE id = :id
          AND empresa_id = :empresa_id
    ");

        $stmt->execute([
            ':valor' =>
            $valor,

            ':id' =>
            $caixaId,

            ':empresa_id' =>
            $compra['empresa_id']
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo do caixa.'
            );
        }
    }

    private function registarSaidaBancoCompra(
        array $compra,
        array $dados,
        float $valor,
        int $contaPagarId
    ): void {

        $bancoId =
            (int)(
                $dados['banco_id']
                ?? 0
            );

        if ($bancoId <= 0) {
            throw new RuntimeException(
                'Conta bancária inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
        SELECT
            id,
            saldo_atual,
            status_id

        FROM bancos

        WHERE id = :id
          AND empresa_id = :empresa_id

        FOR UPDATE
    ");

        $stmt->execute([
            ':id' =>
            $bancoId,

            ':empresa_id' =>
            $compra['empresa_id']
        ]);

        $banco =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$banco) {
            throw new RuntimeException(
                'Conta bancária não encontrada.'
            );
        }

        $statusActivo =
            $this->statusPorTipoNome(
                'banco',
                'Activo'
            );

        if (
            (int)$banco['status_id']
            !== $statusActivo
        ) {
            throw new RuntimeException(
                'A conta bancária selecionada não está activa.'
            );
        }

        if (
            (float)$banco['saldo_atual']
            < $valor
        ) {
            throw new RuntimeException(
                'Saldo insuficiente na conta bancária selecionada.'
            );
        }


        $stmt = $this->pdo->prepare("
        INSERT INTO movimentos_bancarios
        (
            empresa_id,
            filial_id,
            centro_custo_id,
            banco_id,
            usuario_id,
            tipo,
            origem,
            documento,
            referencia,
            descricao,
            valor,
            data_movimento
        )
        VALUES
        (
            :empresa_id,
            :filial_id,
            :centro_custo_id,
            :banco_id,
            :usuario_id,
            'SAIDA',
            'PAGAMENTO',
            :documento,
            :referencia,
            :descricao,
            :valor,
            :data_movimento
        )
    ");

        $stmt->execute([
            ':empresa_id' =>
            $compra['empresa_id'],

            ':filial_id' =>
            $compra['filial_id'],

            ':centro_custo_id' =>
            $compra['centro_custo_id'],

            ':banco_id' =>
            $bancoId,

            ':usuario_id' =>
            $compra['usuario_id'],

            ':documento' =>
            $compra['numero_documento'],

            ':referencia' =>
            'PAGAMENTO-COMPRA-'
                . $compra['id'],

            ':descricao' =>
            'Pagamento automático da compra '
                . (
                    $compra['numero_documento']
                    ?? '#' . $compra['id']
                ),

            ':valor' =>
            $valor,

            ':data_movimento' =>
            $compra['data_compra']
                . ' '
                . date('H:i:s')
        ]);


        $stmt = $this->pdo->prepare("
        UPDATE bancos

        SET saldo_atual =
            saldo_atual - :valor

        WHERE id = :id
          AND empresa_id = :empresa_id
    ");

        $stmt->execute([
            ':valor' =>
            $valor,

            ':id' =>
            $bancoId,

            ':empresa_id' =>
            $compra['empresa_id']
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo bancário.'
            );
        }
    }

    // ==================================================
    // CONFIRMAR COMPRA
    // ==================================================

    private function confirmarCompra(
        array $compra,
        array $dados
    ): void {

        $contaPagarId =
            !empty($compra['conta_pagar_id'])
            ? (int)$compra['conta_pagar_id']
            : null;

        /*
         * Cria o movimento de stock apenas se ainda
         * não existir.
         */

        if (empty($compra['movimento_estoque_id'])) {
            $movimentoId =
                $this->criarMovimentoEstoque(
                    $compra,
                    $dados['itens']
                );

            $stmt = $this->pdo->prepare("
                UPDATE compras
                SET movimento_estoque_id =
                    :movimento_id

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':movimento_id' => $movimentoId,
                ':id' => $compra['id'],
                ':empresa_id' => $compra['empresa_id']
            ]);
        }

        /*
         * Cria a Conta a Pagar apenas se ainda
         * não existir.
         */

        if ($contaPagarId === null) {
            /*
|--------------------------------------------------------------------------
| CONTA A PAGAR
|--------------------------------------------------------------------------
*/

            $contaPagarId =
                $this->criarContaPagar(
                    $compra
                );

            $stmt = $this->pdo->prepare("
                    UPDATE compras

                    SET conta_pagar_id =
                        :conta_pagar_id

                    WHERE id = :id
                    AND empresa_id = :empresa_id
                ");

            $stmt->execute([
                ':conta_pagar_id' =>
                $contaPagarId,

                ':id' =>
                $compra['id'],

                ':empresa_id' =>
                $compra['empresa_id']
            ]);
        }

        $this->contabilizarCompra(
            $compra
        );
        /*
        |--------------------------------------------------------------------------
        | PAGAMENTO AUTOMÁTICO À VISTA
        |--------------------------------------------------------------------------
        */

        if (
            strtoupper(
                (string)$compra['condicao_pagamento']
            ) === 'AVISTA'
        ) {

            $this->registarPagamentoAutomaticoCompra(
                $compra,
                $dados,
                $contaPagarId
            );
        }
    }

    // ==================================================
    // MOVIMENTO DE STOCK
    // ==================================================

    private function criarMovimentoEstoque(
        array $compra,
        array $itens
    ): int {

        /*
     * O movimento deve usar um status
     * pertencente a movimento_estoque,
     * e não o status da compra.
     */
        $statusMovimento =
            $this->statusPorTipoNome(
                'movimento_estoque',
                'Confirmado'
            );


        $stmt =
            $this->pdo->prepare("
            INSERT INTO movimentos_estoque
            (
                empresa_id,
                tipo,
                armazem_destino_id,
                usuario_id,
                status_id,
                data_movimento,
                observacoes
            )
            VALUES
            (
                :empresa_id,
                'ENTRADA',
                :armazem_destino_id,
                :usuario_id,
                :status_id,
                NOW(),
                :observacoes
            )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $compra['empresa_id'],

            ':armazem_destino_id' =>
            $compra['armazem_id'],

            ':usuario_id' =>
            $compra['usuario_id'],

            ':status_id' =>
            $statusMovimento,

            ':observacoes' =>
            'Entrada automática referente à compra #'
                . $compra['id']
        ]);


        $movimentoId =
            (int)$this->pdo
                ->lastInsertId();


        if ($movimentoId <= 0) {

            throw new RuntimeException(
                'Não foi possível criar o movimento de stock da compra.'
            );
        }


        $stmtItem =
            $this->pdo->prepare("
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

            $stmtItem->execute([
                ':movimento_id' =>
                $movimentoId,

                ':produto_id' =>
                $item['produto_id'],

                ':quantidade' =>
                $item['quantidade'],

                ':custo_unitario' =>
                $item['preco_unitario']
            ]);
        }


        EstoqueService::aplicarMovimento(
            $movimentoId,
            $this->pdo
        );


        return $movimentoId;
    }

    // ==================================================
    // CRIAR CONTA A PAGAR
    // ==================================================

    private function criarContaPagar(
        array $compra
    ): int {

        $statusPendente =
            $this->statusPorTipoNome(
                'conta_pagar',
                'Pendente'
            );

        $dataVencimento =
            $compra['data_vencimento']
            ?: $compra['data_compra'];

        $stmt = $this->pdo->prepare("
            INSERT INTO contas_pagar
            (
                empresa_id,
                filial_id,
                centro_custo_id,

                fornecedor_id,
                forma_pagamento_id,
                status_id,

                numero_documento,
                referencia,
                descricao,

                valor,
                desconto,
                juros,
                multa,

                valor_pago,
                saldo,

                data_emissao,
                data_vencimento,
                data_pagamento,

                observacoes,

                criado_por
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :centro_custo_id,

                :fornecedor_id,
                :forma_pagamento_id,
                :status_id,

                :numero_documento,
                :referencia,
                :descricao,

                :valor,
                0,
                0,
                0,

                0,
                :saldo,

                :data_emissao,
                :data_vencimento,
                NULL,

                :observacoes,

                :criado_por
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $compra['empresa_id'],

            ':filial_id' =>
            $compra['filial_id'],

            ':centro_custo_id' =>
            $compra['centro_custo_id'],

            ':fornecedor_id' =>
            $compra['fornecedor_id'],

            ':forma_pagamento_id' =>
            $compra['forma_pagamento_id'],

            ':status_id' =>
            $statusPendente,

            ':numero_documento' =>
            $compra['numero_documento'],

            ':referencia' =>
            'COMPRA-' . $compra['id'],

            ':descricao' =>
            'Conta gerada automaticamente pela compra #'
                . $compra['id'],

            ':valor' =>
            $compra['total'],

            ':saldo' =>
            $compra['total'],

            ':data_emissao' =>
            $compra['data_compra'],

            ':data_vencimento' =>
            $dataVencimento,

            ':observacoes' =>
            'Gerada automaticamente pelo módulo de Compras.',

            ':criado_por' =>
            $compra['usuario_id']
        ]);

        return (int)$this->pdo
            ->lastInsertId();
    }

    // ==================================================
    // GRAVAR ITENS
    // ==================================================

    private function gravarItens(
        int $compraId,
        array $itens
    ): void {

        foreach ($itens as $item) {
            $stmt = $this->pdo->prepare("
                INSERT INTO compra_itens
                (
                    compra_id,
                    produto_id,
                    quantidade,
                    preco_unitario,
                    desconto,
                    imposto,
                    subtotal
                )
                VALUES
                (
                    :compra_id,
                    :produto_id,
                    :quantidade,
                    :preco_unitario,
                    :desconto,
                    :imposto,
                    :subtotal
                )
            ");

            $stmt->execute([
                ':compra_id' =>
                $compraId,

                ':produto_id' =>
                $item['produto_id'],

                ':quantidade' =>
                $item['quantidade'],

                ':preco_unitario' =>
                $item['preco_unitario'],

                ':desconto' =>
                $item['desconto'],

                ':imposto' =>
                $item['imposto'],

                ':subtotal' =>
                $item['subtotal']
            ]);
        }
    }

    // ==================================================
    // BLOQUEAR COMPRA
    // ==================================================

    private function bloquearCompra(
        int $id,
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT *

            FROM compras

            WHERE id = :id
              AND empresa_id = :empresa_id

            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $compra = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$compra) {
            throw new RuntimeException(
                'Compra não encontrada.'
            );
        }

        return $compra;
    }


    public function cancelar(
        int $compraId,
        int $empresaId,
        int $usuarioId,
        string $motivo
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        if ($compraId <= 0) {
            throw new RuntimeException(
                'Compra inválida.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        $motivo =
            trim($motivo);

        if (mb_strlen($motivo) < 5) {
            throw new RuntimeException(
                'Informe o motivo do cancelamento.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | 1. BLOQUEAR COMPRA
        |--------------------------------------------------------------------------
        */

            $compra =
                $this->bloquearCompra(
                    $compraId,
                    $empresaId
                );


            $statusConfirmada =
                $this->statusCompra(
                    'Confirmada'
                );


            $statusCancelada =
                $this->statusCompra(
                    'Cancelada'
                );


            if (
                (int)$compra['status_id']
                === $statusCancelada
            ) {
                throw new RuntimeException(
                    'Esta compra já está cancelada.'
                );
            }


            if (
                (int)$compra['status_id']
                !== $statusConfirmada
            ) {
                throw new RuntimeException(
                    'Apenas compras confirmadas podem ser canceladas por estorno.'
                );
            }


            if (
                empty($compra['movimento_estoque_id'])
            ) {
                throw new RuntimeException(
                    'A compra não possui movimento de entrada de stock.'
                );
            }


            if (
                empty($compra['conta_pagar_id'])
            ) {
                throw new RuntimeException(
                    'A compra não possui conta a pagar associada.'
                );
            }


            if (
                !empty($compra['movimento_estorno_id'])
            ) {
                throw new RuntimeException(
                    'Esta compra já possui um movimento de estorno.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | 2. VERIFICAR CONTA A PAGAR
        |--------------------------------------------------------------------------
        */

            $this->validarContaPagarParaCancelamento(
                (int)$compra['conta_pagar_id'],
                $empresaId
            );


            /*
        |--------------------------------------------------------------------------
        | 3. ITENS DA COMPRA
        |--------------------------------------------------------------------------
        */

            $itens =
                $this->buscarItens(
                    $compraId,
                    $empresaId
                );


            if (!$itens) {
                throw new RuntimeException(
                    'A compra não possui itens para estornar.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | 4. VERIFICAR STOCK DISPONÍVEL
        |--------------------------------------------------------------------------
        */

            $this->validarStockParaEstorno(
                $compra,
                $itens
            );


            /*
        |--------------------------------------------------------------------------
        | 5. CRIAR SAÍDA DE ESTORNO
        |--------------------------------------------------------------------------
        */

            $movimentoEstornoId =
                $this->criarMovimentoEstornoStock(
                    $compra,
                    $itens,
                    $usuarioId
                );


            /*
        |--------------------------------------------------------------------------
        | 6. CANCELAR CONTA A PAGAR
        |--------------------------------------------------------------------------
        */

            $this->cancelarContaPagarCompra(
                (int)$compra['conta_pagar_id'],
                $empresaId,
                $usuarioId,
                $motivo
            );


            /*
        |--------------------------------------------------------------------------
        | 7. ESTORNO CONTABILÍSTICO
        |--------------------------------------------------------------------------
        */

            $lancamentoEstornoId =
                $this->estornarContabilidadeCompra(
                    $compra,
                    $usuarioId,
                    $motivo
                );


            /*
        |--------------------------------------------------------------------------
        | 8. CANCELAR COMPRA
        |--------------------------------------------------------------------------
        */

            $stmt =
                $this->pdo->prepare("
                UPDATE compras

                SET
                    status_id =
                        :status_id,

                    movimento_estorno_id =
                        :movimento_estorno_id,

                    lancamento_estorno_id =
                        :lancamento_estorno_id,

                    cancelada_em =
                        NOW(),

                    cancelada_por =
                        :cancelada_por,

                    motivo_cancelamento =
                        :motivo

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':status_id' =>
                $statusCancelada,

                ':movimento_estorno_id' =>
                $movimentoEstornoId,

                ':lancamento_estorno_id' =>
                $lancamentoEstornoId,

                ':cancelada_por' =>
                $usuarioId,

                ':motivo' =>
                $motivo,

                ':id' =>
                $compraId,

                ':empresa_id' =>
                $empresaId
            ]);

            /*
 * Recarregar compra já cancelada.
 */
            $compraCancelada =
                $this->buscar(
                    $compraId,
                    $empresaId
                );


            if (!$compraCancelada) {

                throw new RuntimeException(
                    'Não foi possível recarregar a compra cancelada.'
                );
            }


            $compraCancelada['itens'] =
                $itens;


            /*
 * ==================================================
 * AUDITORIA DA COMPRA
 * ==================================================
 */

            $dadosAntes =
                $this->dadosAuditoriaCompra(
                    array_merge(
                        $compra,
                        [
                            'itens' =>
                            $itens
                        ]
                    )
                );


            $dadosDepois =
                $this->dadosAuditoriaCompra(
                    $compraCancelada
                );


            $dadosDepois['motivo_cancelamento'] =
                $motivo;


            $dadosDepois['movimento_estorno_id'] =
                $movimentoEstornoId;


            $dadosDepois['lancamento_estorno_id'] =
                $lancamentoEstornoId;


            $this->auditoria
                ->atualizacao(
                    $empresaId,
                    $usuarioId,
                    'compras',
                    $compraId,
                    $dadosAntes,
                    $dadosDepois
                );


            /*
 * ==================================================
 * AUDITORIA DO MOVIMENTO DE ESTORNO
 * ==================================================
 */

            $this->auditoria
                ->insercao(
                    $empresaId,
                    $usuarioId,
                    'movimentos_estoque',
                    $movimentoEstornoId,

                    [
                        'tipo' =>
                        'SAIDA',

                        'origem' =>
                        'ESTORNO_COMPRA',

                        'compra_id' =>
                        $compraId,

                        'movimento_original_id' =>
                        !empty($compra['movimento_estoque_id'])
                            ? (int)$compra['movimento_estoque_id']
                            : null,

                        'armazem_id' =>
                        (int)$compra['armazem_id'],

                        'motivo' =>
                        $motivo,

                        'itens' =>
                        array_map(
                            static function (
                                array $item
                            ): array {

                                return [

                                    'produto_id' =>
                                    (int)$item['produto_id'],

                                    'produto' =>
                                    $item['produto_nome']
                                        ?? null,

                                    'quantidade' =>
                                    (float)$item['quantidade'],

                                    'custo_unitario' =>
                                    (float)(
                                        $item['preco_unitario']
                                        ?? 0
                                    )
                                ];
                            },
                            $itens
                        )
                    ]
                );


            /*
 * ==================================================
 * LOG
 * ==================================================
 */

            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'COMPRAS',
                    'CANCELAR',

                    'Cancelou a compra '
                        . (
                            $compra['numero_documento']
                            ?? '#' . $compraId
                        )
                        . '. Movimento de estorno: #'
                        . $movimentoEstornoId
                        . '. Lançamento de estorno: #'
                        . $lancamentoEstornoId
                        . '. Motivo: '
                        . $motivo,

                    $compraId,

                    !empty($compra['filial_id'])
                        ? (int)$compra['filial_id']
                        : null
                );



            $this->pdo->commit();


            return [
                'movimento_estorno_id' =>
                $movimentoEstornoId,

                'lancamento_estorno_id' =>
                $lancamentoEstornoId
            ];
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {
                $this->pdo
                    ->rollBack();
            }

            throw $e;
        }
    }

    private function validarContaPagarParaCancelamento(
        int $contaPagarId,
        int $empresaId
    ): void {

        $stmt =
            $this->pdo->prepare("
            SELECT
                id,
                valor,
                valor_pago,
                saldo,
                status_id

            FROM contas_pagar

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id

            FOR UPDATE
        ");


        $stmt->execute([
            ':id' =>
            $contaPagarId,

            ':empresa_id' =>
            $empresaId
        ]);


        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$conta) {
            throw new RuntimeException(
                'Conta a pagar da compra não encontrada.'
            );
        }


        if (
            (float)$conta['valor_pago']
            > 0
        ) {
            throw new RuntimeException(
                'Não é possível cancelar esta compra porque a conta a pagar já possui pagamento. Primeiro será necessário estornar o pagamento.'
            );
        }


        $stmt =
            $this->pdo->prepare("
            SELECT COUNT(*)

            FROM pagamentos_contas_pagar

            WHERE conta_pagar_id =
                :conta_pagar_id

              AND empresa_id =
                :empresa_id
        ");


        $stmt->execute([
            ':conta_pagar_id' =>
            $contaPagarId,

            ':empresa_id' =>
            $empresaId
        ]);


        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'A conta a pagar possui histórico de pagamentos e a compra não pode ser cancelada automaticamente.'
            );
        }
    }

    private function validarStockParaEstorno(
        array $compra,
        array $itens
    ): void {

        $stmt =
            $this->pdo->prepare("
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


        foreach ($itens as $item) {

            $stmt->execute([
                ':empresa_id' =>
                $compra['empresa_id'],

                ':armazem_id' =>
                $compra['armazem_id'],

                ':produto_id' =>
                $item['produto_id']
            ]);


            $saldo =
                $stmt->fetchColumn();


            $saldoAtual =
                $saldo !== false
                ? (float)$saldo
                : 0.0;


            $quantidade =
                (float)$item['quantidade'];


            if (
                $saldoAtual
                + 0.000001
                < $quantidade
            ) {

                throw new RuntimeException(
                    'Não é possível estornar a compra. Stock insuficiente para o produto '
                        . $item['produto_nome']
                        . '. Stock atual: '
                        . number_format(
                            $saldoAtual,
                            3,
                            ',',
                            '.'
                        )
                        . ', necessário para estorno: '
                        . number_format(
                            $quantidade,
                            3,
                            ',',
                            '.'
                        )
                        . '.'
                );
            }
        }
    }

    private function criarMovimentoEstornoStock(
        array $compra,
        array $itens,
        int $usuarioId
    ): int {

        $statusMovimento =
            $this->statusPorTipoNome(
                'movimento_estoque',
                'Confirmado'
            );


        $stmt =
            $this->pdo->prepare("
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
                :armazem_origem_id,
                NULL,
                :usuario_id,
                :status_id,
                'SAIDA',
                :observacoes,
                NOW()
            )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $compra['empresa_id'],

            ':armazem_origem_id' =>
            $compra['armazem_id'],

            ':usuario_id' =>
            $usuarioId,

            ':status_id' =>
            $statusMovimento,

            ':observacoes' =>
            'Estorno da entrada de stock da compra #'
                . $compra['id']
        ]);


        $movimentoId =
            (int)$this->pdo
                ->lastInsertId();


        if ($movimentoId <= 0) {
            throw new RuntimeException(
                'Não foi possível gerar o movimento de estorno do stock.'
            );
        }


        $stmtItem =
            $this->pdo->prepare("
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

            $stmtItem->execute([
                ':movimento_id' =>
                $movimentoId,

                ':produto_id' =>
                $item['produto_id'],

                ':quantidade' =>
                $item['quantidade'],

                ':custo_unitario' =>
                $item['preco_unitario']
            ]);
        }


        EstoqueService::aplicarMovimento(
            $movimentoId,
            $this->pdo
        );


        return $movimentoId;
    }

    private function cancelarContaPagarCompra(
        int $contaPagarId,
        int $empresaId,
        int $usuarioId,
        string $motivo
    ): void {

        $statusCancelado =
            $this->statusPorTipoNome(
                'conta_pagar',
                'Cancelado'
            );


        $stmt =
            $this->pdo->prepare("
            UPDATE contas_pagar

            SET
                status_id =
                    :status_id,

                saldo =
                    0,

                observacoes =
                    CONCAT(
                        COALESCE(observacoes, ''),
                        CASE
                            WHEN COALESCE(observacoes, '') = ''
                            THEN ''
                            ELSE '\n'
                        END,
                        'Cancelada por estorno da compra. Motivo: ',
                        :motivo
                    ),

                atualizado_por =
                    :usuario_id

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id
        ");


        $stmt->execute([
            ':status_id' =>
            $statusCancelado,

            ':motivo' =>
            $motivo,

            ':usuario_id' =>
            $usuarioId,

            ':id' =>
            $contaPagarId,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    private function estornarContabilidadeCompra(
        array $compra,
        int $usuarioId,
        string $motivo
    ): int {

        $empresaId =
            (int)$compra['empresa_id'];


        $filialId =
            !empty($compra['filial_id'])
            ? (int)$compra['filial_id']
            : null;


        $compraId =
            (int)$compra['id'];


        /*
     * Apenas um estorno contabilístico por compra.
     */
        if (
            $this->contabilidade
            ->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                'ESTORNO',
                $compraId
            )
        ) {
            throw new RuntimeException(
                'Esta compra já possui estorno contabilístico.'
            );
        }


        /*
     * Localizar lançamento original.
     */
        $sql = "
        SELECT id

        FROM lancamentos_contabilisticos

        WHERE empresa_id =
            :empresa_id

          AND origem =
            'COMPRA'

          AND origem_id =
            :origem_id
    ";


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':origem_id' =>
            $compraId
        ];


        if ($filialId === null) {

            $sql .= "
            AND filial_id IS NULL
        ";
        } else {

            $sql .= "
            AND filial_id =
                :filial_id
        ";

            $params[':filial_id'] =
                $filialId;
        }


        $sql .= "
        LIMIT 1
    ";


        $stmt =
            $this->pdo->prepare($sql);


        $stmt->execute($params);


        $lancamentoOriginalId =
            (int)$stmt->fetchColumn();


        if ($lancamentoOriginalId <= 0) {
            throw new RuntimeException(
                'Lançamento contabilístico original da compra não encontrado.'
            );
        }


        /*
     * O estorno deve ser contabilizado na data
     * em que está a acontecer.
     */
        $dataEstorno =
            date('Y-m-d');


        $contexto =
            $this->contabilidade
            ->contextoData(
                $empresaId,
                $dataEstorno
            );


        $diario =
            $this->contabilidade
            ->diarioPorTipo(
                $empresaId,
                'COMPRAS'
            );


        $contaFornecedores =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'FORNECEDORES'
            );


        $contaStock =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'STOCK'
            );


        $total =
            round(
                (float)$compra['total'],
                2
            );


        $imposto =
            round(
                (float)$compra['imposto'],
                2
            );


        $valorStock =
            round(
                $total - $imposto,
                2
            );


        $itens = [];


        /*
     * Débito:
     * elimina a obrigação com fornecedor.
     */
        $itens[] = [
            'conta_contabil_id' =>
            (int)$contaFornecedores['id'],

            'centro_custo_id' =>
            null,

            'cliente_id' =>
            null,

            'fornecedor_id' =>
            (int)$compra['fornecedor_id'],

            'descricao' =>
            'Estorno da obrigação da compra '
                . (
                    $compra['numero_documento']
                    ?? '#' . $compraId
                ),

            'debito' =>
            $total,

            'credito' =>
            0
        ];


        /*
     * Crédito:
     * retira contabilisticamente o stock.
     */
        $itens[] = [
            'conta_contabil_id' =>
            (int)$contaStock['id'],

            'centro_custo_id' =>
            !empty($compra['centro_custo_id'])
                ? (int)$compra['centro_custo_id']
                : null,

            'cliente_id' =>
            null,

            'fornecedor_id' =>
            null,

            'descricao' =>
            'Estorno do stock da compra '
                . (
                    $compra['numero_documento']
                    ?? '#' . $compraId
                ),

            'debito' =>
            0,

            'credito' =>
            $valorStock
        ];


        if ($imposto > 0) {

            $contaIva =
                $this->contabilidade
                ->contaIntegracao(
                    $empresaId,
                    'IVA_DEDUTIVEL'
                );


            $itens[] = [
                'conta_contabil_id' =>
                (int)$contaIva['id'],

                'centro_custo_id' =>
                null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                null,

                'descricao' =>
                'Estorno do IVA dedutível da compra '
                    . (
                        $compra['numero_documento']
                        ?? '#' . $compraId
                    ),

                'debito' =>
                0,

                'credito' =>
                $imposto
            ];
        }


        $lancamentoId =
            $this->contabilidade
            ->criarLancamento(
                [
                    'empresa_id' =>
                    $empresaId,

                    'filial_id' =>
                    $filialId,

                    'exercicio_id' =>
                    (int)$contexto['exercicio_id'],

                    'periodo_id' =>
                    (int)$contexto['periodo_id'],

                    'diario_id' =>
                    (int)$diario['id'],

                    'numero_documento' =>
                    $compra['numero_documento']
                        ?? null,

                    'referencia' =>
                    'ESTORNO-COMPRA-'
                        . $compraId,

                    'data_lancamento' =>
                    $dataEstorno,

                    'data_documento' =>
                    $dataEstorno,

                    'descricao' =>
                    'Estorno da compra '
                        . (
                            $compra['numero_documento']
                            ?? '#' . $compraId
                        )
                        . '. Motivo: '
                        . $motivo,

                    'origem' =>
                    'ESTORNO',

                    'origem_id' =>
                    $compraId,

                    'usuario_id' =>
                    $usuarioId
                ],

                $itens
            );


        /*
     * Relacionar com o lançamento original.
     */
        $stmt =
            $this->pdo->prepare("
            UPDATE lancamentos_contabilisticos

            SET lancamento_estornado_id =
                :original_id

            WHERE id =
                :estorno_id
        ");


        $stmt->execute([
            ':original_id' =>
            $lancamentoOriginalId,

            ':estorno_id' =>
            $lancamentoId
        ]);


        return $lancamentoId;
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        $this->validarEmpresa(
            $empresaId
        );


        if ($id <= 0) {

            throw new RuntimeException(
                'Compra inválida.'
            );
        }


        if ($usuarioId <= 0) {

            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }


        $this->pdo
            ->beginTransaction();


        try {

            /*
         * Bloquear para impedir alteração concorrente.
         */
            $compra =
                $this->bloquearCompra(
                    $id,
                    $empresaId
                );


            /*
         * Compra integrada nunca é eliminada fisicamente.
         */
            if (
                !empty($compra['movimento_estoque_id'])
                || !empty($compra['conta_pagar_id'])
            ) {

                throw new RuntimeException(
                    'Não é possível eliminar uma compra confirmada. Cancele e estorne a compra.'
                );
            }


            /*
         * Capturar itens antes da exclusão.
         */
            $itens =
                $this->buscarItens(
                    $id,
                    $empresaId
                );


            $dadosAuditoria =
                $this->dadosAuditoriaCompra(
                    array_merge(
                        $compra,
                        [
                            'itens' =>
                            $itens
                        ]
                    )
                );


            /*
         * Primeiro os itens.
         */
            $stmt =
                $this->pdo
                ->prepare("
                    DELETE FROM compra_itens

                    WHERE compra_id =
                        :compra_id
                ");


            $stmt->execute([
                ':compra_id' =>
                $id
            ]);


            /*
         * Depois o cabeçalho.
         */
            $stmt =
                $this->pdo
                ->prepare("
                    DELETE FROM compras

                    WHERE id =
                        :id

                      AND empresa_id =
                        :empresa_id
                ");


            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount()
                !== 1
            ) {

                throw new RuntimeException(
                    'Não foi possível eliminar a compra.'
                );
            }


            /*
         * Auditoria antes do commit.
         */
            $this->auditoria
                ->exclusao(
                    $empresaId,
                    $usuarioId,
                    'compras',
                    $id,
                    $dadosAuditoria
                );


            /*
         * Log.
         */
            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'COMPRAS',
                    'ELIMINAR',

                    'Eliminou a compra '
                        . (
                            $compra['numero_documento']
                            ?? '#' . $id
                        )
                        . '.',

                    $id,

                    !empty($compra['filial_id'])
                        ? (int)$compra['filial_id']
                        : null
                );


            $this->pdo
                ->commit();


            return true;
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {

                $this->pdo
                    ->rollBack();
            }


            throw $e;
        }
    }

    // ==================================================
    // FORNECEDORES
    // ==================================================

    public function fornecedores(
        int $empresaId
    ): array {

        $this->validarEmpresa($empresaId);

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome

            FROM fornecedores

            WHERE empresa_id = :empresa_id

            ORDER BY nome ASC
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function filiais(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt =
            $this->pdo->prepare("
            SELECT
                id,
                nome,
                codigo

            FROM filiais

            WHERE empresa_id = :empresa_id

            ORDER BY nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // ARMAZÉNS
    // ==================================================

    public function armazens(
        int $empresaId
    ): array {

        $this->validarEmpresa($empresaId);

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome

            FROM armazens

            WHERE empresa_id = :empresa_id

            ORDER BY nome ASC
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // PRODUTOS
    // ==================================================

    public function produtos(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt =
            $this->pdo->prepare("
            SELECT
                id,
                sku,
                nome,
                preco_custo

            FROM produtos

            WHERE empresa_id =
                :empresa_id

            ORDER BY nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // STATUS DE COMPRA
    // ==================================================

    public function listarStatus(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo = 'compra'

            ORDER BY
                CASE
                    WHEN LOWER(nome) = 'rascunho'
                        THEN 1

                    WHEN LOWER(nome) = 'confirmada'
                        THEN 2

                    WHEN LOWER(nome) = 'cancelada'
                        THEN 3

                    ELSE 4
                END
        ");

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // NORMALIZAR
    // ==================================================

    private function normalizarDados(
        array $dados
    ): array {

        $itens =
            $dados['itens'] ?? [];

        if (!is_array($itens)) {
            $itens = [];
        }

        $itensNormalizados = [];

        $subtotal = 0.00;

        /*
     * Primeiro normaliza os itens
     * e simultaneamente calcula o subtotal.
     */
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

            $precoUnitario =
                (float)(
                    $item['preco_unitario']
                    ?? 0
                );

            $descontoItem =
                (float)(
                    $item['desconto']
                    ?? 0
                );

            $impostoItem =
                (float)(
                    $item['imposto']
                    ?? 0
                );

            /*
         * Melhor recalcular no servidor,
         * em vez de confiar no subtotal vindo do JS.
         */
            $subtotalItem =
                max(
                    0,
                    ($quantidade * $precoUnitario)
                        - $descontoItem
                        + $impostoItem
                );

            $subtotalItem =
                round(
                    $subtotalItem,
                    2
                );

            $itensNormalizados[] = [
                'produto_id' =>
                $produtoId,

                'quantidade' =>
                $quantidade,

                'preco_unitario' =>
                $precoUnitario,

                'desconto' =>
                $descontoItem,

                'imposto' =>
                $impostoItem,

                'subtotal' =>
                $subtotalItem
            ];

            $subtotal +=
                $subtotalItem;
        }

        $subtotal =
            round(
                $subtotal,
                2
            );

        $desconto =
            round(
                max(
                    0,
                    (float)(
                        $dados['desconto']
                        ?? 0
                    )
                ),
                2
            );

        if ($desconto > $subtotal) {
            throw new RuntimeException(
                'O desconto não pode ser superior ao subtotal da compra.'
            );
        }

        $impostoHabilitado =
            isset(
                $dados['imposto_habilitado']
            )
            && (string)$dados['imposto_habilitado']
            === '1';

        $taxaImposto =
            $impostoHabilitado
            ? 16.00
            : 0.00;

        $baseTributavel =
            round(
                max(
                    0,
                    $subtotal - $desconto
                ),
                2
            );

        $imposto =
            $impostoHabilitado
            ? round(
                $baseTributavel
                    * $taxaImposto
                    / 100,
                2
            )
            : 0.00;

        $total =
            round(
                $baseTributavel
                    + $imposto,
                2
            );

        return [
            'id' =>
            !empty($dados['id'])
                ? (int)$dados['id']
                : null,

            'empresa_id' =>
            (int)(
                $dados['empresa_id']
                ?? 0
            ),

            'filial_id' =>
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : null,

            'centro_custo_id' =>
            !empty($dados['centro_custo_id'])
                ? (int)$dados['centro_custo_id']
                : null,

            'fornecedor_id' =>
            (int)(
                $dados['fornecedor_id']
                ?? 0
            ),

            'forma_pagamento_id' =>
            !empty($dados['forma_pagamento_id'])
                ? (int)$dados['forma_pagamento_id']
                : null,

            'condicao_pagamento' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['condicao_pagamento']
                        ?? 'APRAZO'
                    )
                )
            ),

            'origem_pagamento' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['origem_pagamento']
                        ?? ''
                    )
                )
            ),

            'caixa_id' =>
            !empty($dados['caixa_id'])
                ? (int)$dados['caixa_id']
                : null,

            'banco_id' =>
            !empty($dados['banco_id'])
                ? (int)$dados['banco_id']
                : null,

            'armazem_id' =>
            (int)(
                $dados['armazem_id']
                ?? 0
            ),

            'usuario_id' =>
            (int)(
                $dados['usuario_id']
                ?? 0
            ),

            'status_id' =>
            (int)(
                $dados['status_id']
                ?? 0
            ),

            'numero_documento' =>
            $this->textoOuNull(
                $dados['numero_documento']
                    ?? null
            ),

            'data_compra' =>
            trim(
                (string)(
                    $dados['data_compra']
                    ?? ''
                )
            ),

            'data_vencimento' =>
            $this->textoOuNull(
                $dados['data_vencimento']
                    ?? null
            ),

            'observacoes' =>
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            ),

            'subtotal' =>
            $subtotal,

            'desconto' =>
            $desconto,

            'imposto_habilitado' =>
            $impostoHabilitado,

            'taxa_imposto' =>
            $taxaImposto,

            'imposto' =>
            $imposto,

            'total' =>
            $total,

            'itens' =>
            $itensNormalizados
        ];
    }

    // ==================================================
    // VALIDAR DADOS
    // ==================================================

    private function validarDados(
        array $dados
    ): void {

        $this->validarEmpresa(
            $dados['empresa_id']
        );

        if ($dados['usuario_id'] <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        if ($dados['fornecedor_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o fornecedor.'
            );
        }

        if ($dados['armazem_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o armazém.'
            );
        }

        if ($dados['centro_custo_id'] === null) {
            throw new RuntimeException(
                'Selecione o centro de custo.'
            );
        }

        if ($dados['forma_pagamento_id'] === null) {
            throw new RuntimeException(
                'Selecione a forma de pagamento.'
            );
        }

        if ($dados['status_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o status da compra.'
            );
        }

        if (
            !in_array(
                $dados['condicao_pagamento'],
                ['AVISTA', 'APRAZO'],
                true
            )
        ) {
            throw new RuntimeException(
                'Condição de pagamento inválida.'
            );
        }

        $this->validarData(
            $dados['data_compra'],
            'Data da compra'
        );

        if (
            $dados['condicao_pagamento']
            === 'APRAZO'
        ) {
            if ($dados['data_vencimento'] === null) {
                throw new RuntimeException(
                    'Informe a data de vencimento.'
                );
            }

            $this->validarData(
                $dados['data_vencimento'],
                'Data de vencimento'
            );

            if (
                $dados['data_vencimento']
                < $dados['data_compra']
            ) {
                throw new RuntimeException(
                    'A data de vencimento não pode ser anterior à compra.'
                );
            }
        }

        if (
            $dados['condicao_pagamento']
            === 'AVISTA'
        ) {

            if (
                !in_array(
                    $dados['origem_pagamento'],
                    [
                        'CAIXA',
                        'BANCO'
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Selecione se o pagamento da compra sairá do Caixa ou do Banco.'
                );
            }

            if (
                $dados['origem_pagamento']
                === 'CAIXA'
                && (
                    $dados['caixa_id'] === null
                    || $dados['caixa_id'] <= 0
                )
            ) {
                throw new RuntimeException(
                    'Selecione o caixa utilizado no pagamento.'
                );
            }

            if (
                $dados['origem_pagamento']
                === 'BANCO'
                && (
                    $dados['banco_id'] === null
                    || $dados['banco_id'] <= 0
                )
            ) {
                throw new RuntimeException(
                    'Selecione a conta bancária utilizada no pagamento.'
                );
            }
        }

        if (empty($dados['itens'])) {
            throw new RuntimeException(
                'Adicione pelo menos um produto à compra.'
            );
        }

        foreach ($dados['itens'] as $item) {
            if ($item['produto_id'] <= 0) {
                throw new RuntimeException(
                    'Produto inválido na compra.'
                );
            }

            if ($item['quantidade'] <= 0) {
                throw new RuntimeException(
                    'A quantidade deve ser superior a zero.'
                );
            }

            if ($item['preco_unitario'] < 0) {
                throw new RuntimeException(
                    'Preço unitário inválido.'
                );
            }

            if ($item['subtotal'] < 0) {
                throw new RuntimeException(
                    'Subtotal inválido.'
                );
            }
        }

        if ($dados['total'] <= 0) {
            throw new RuntimeException(
                'O total da compra deve ser superior a zero.'
            );
        }
    }

    // ==================================================
    // VALIDAR RELACIONAMENTOS
    // ==================================================

    private function validarRelacionamentos(
        array $dados
    ): void {

        $this->validarFornecedor(
            $dados['fornecedor_id'],
            $dados['empresa_id']
        );

        $this->validarArmazem(
            $dados['armazem_id'],
            $dados['empresa_id']
        );

        $this->validarFilial(
            $dados['filial_id'],
            $dados['empresa_id']
        );

        $this->validarCentroCusto(
            $dados['centro_custo_id'],
            $dados['empresa_id'],
            $dados['filial_id']
        );

        $this->validarFormaPagamento(
            $dados['forma_pagamento_id']
        );

        $this->validarStatusCompra(
            $dados['status_id']
        );
    }

    private function validarFornecedor(
        int $id,
        int $empresaId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM fornecedores

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Fornecedor não encontrado.'
            );
        }
    }

    private function validarArmazem(
        int $id,
        int $empresaId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM armazens

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Armazém não encontrado.'
            );
        }
    }

    private function validarFilial(
        ?int $id,
        int $empresaId
    ): void {

        if ($id === null) {
            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM filiais

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'A filial selecionada não pertence à empresa.'
            );
        }
    }

    private function validarCentroCusto(
        ?int $id,
        int $empresaId,
        ?int $filialId
    ): void {

        if ($id === null) {
            throw new RuntimeException(
                'Selecione o centro de custo.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                centro.id,
                centro.filial_id,
                centro.tipo,
                status.nome AS status_nome

            FROM centros_custo centro

            INNER JOIN status
                ON status.id = centro.status_id
                AND status.tipo = 'centro_custo'

            WHERE centro.id = :id
              AND centro.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $centro = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$centro) {
            throw new RuntimeException(
                'Centro de custo não encontrado.'
            );
        }

        if (
            !in_array(
                strtolower(
                    (string)$centro['status_nome']
                ),
                ['activo', 'ativo'],
                true
            )
        ) {
            throw new RuntimeException(
                'O centro de custo não está activo.'
            );
        }

        if (
            !in_array(
                strtoupper(
                    (string)$centro['tipo']
                ),
                ['CUSTO', 'MISTO'],
                true
            )
        ) {
            throw new RuntimeException(
                'Para compras, use um centro do tipo Custo ou Misto.'
            );
        }

        if (
            !empty($centro['filial_id'])
            && (
                $filialId === null
                || (int)$centro['filial_id']
                !== $filialId
            )
        ) {
            throw new RuntimeException(
                'O centro de custo pertence a outra filial.'
            );
        }
    }

    private function validarFormaPagamento(
        ?int $id
    ): void {

        if ($id === null || $id <= 0) {
            throw new RuntimeException(
                'Forma de pagamento inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM formas_pagamento

            WHERE id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Forma de pagamento não encontrada.'
            );
        }
    }

    private function validarStatusCompra(
        int $id
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE id = :id
              AND tipo = 'compra'

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Status inválido para compra.'
            );
        }
    }

    // ==================================================
    // STATUS
    // ==================================================

    private function statusCompra(
        string $nome
    ): int {

        return $this->statusPorTipoNome(
            'compra',
            $nome
        );
    }

    private function statusPorTipoNome(
        string $tipo,
        string $nome
    ): int {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo = :tipo
              AND LOWER(nome) = LOWER(:nome)

            LIMIT 1
        ");

        $stmt->execute([
            ':tipo' => $tipo,
            ':nome' => $nome
        ]);

        $id = (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                "Status '{$nome}' do tipo '{$tipo}' não encontrado."
            );
        }

        return $id;
    }

    // ==================================================
    // UTILITÁRIOS
    // ==================================================

    private function validarData(
        string $data,
        string $campo
    ): void {

        $objeto = DateTime::createFromFormat(
            'Y-m-d',
            $data
        );

        if (
            !$objeto
            || $objeto->format('Y-m-d')
            !== $data
        ) {
            throw new RuntimeException(
                $campo . ' inválida.'
            );
        }
    }

    private function textoOuNull(
        mixed $valor
    ): ?string {

        $texto = trim(
            (string)($valor ?? '')
        );

        return $texto !== ''
            ? $texto
            : null;
    }

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para a compra.'
            );
        }
    }
}
