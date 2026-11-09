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


final class Venda
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
    // LISTAR VENDAS
    // ==================================================

    public function listar(
        int $empresaId,
        ?int $filialId = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $sql = "
            SELECT
                venda.id,
                venda.empresa_id,
                venda.filial_id,
                venda.centro_custo_id,

                venda.cliente_id,
                venda.forma_pagamento_id,
                venda.condicao_pagamento,

                venda.armazem_id,
                venda.usuario_id,
                venda.status_id,

                venda.numero_fatura,
                venda.numero_documento,
                venda.tipo_documento,

                venda.data_venda,
                venda.data_vencimento,

                venda.subtotal,
                venda.desconto,
                venda.imposto,
                venda.total,

                venda.origem,
                venda.observacoes,

                venda.conta_receber_id,
                venda.movimento_estoque_id,

                venda.criado_em,

                cliente.nome AS cliente_nome,

                armazem.nome AS armazem_nome,

                forma.nome AS forma_pagamento_nome,

                status.nome AS status_nome,

                COALESCE(
                    filial.nome,
                    'Sede Principal / Sem filial'
                ) AS filial_nome,

                COALESCE(
                    centro.nome,
                    'Sem centro de receita'
                ) AS centro_custo_nome,

                centro.codigo AS centro_custo_codigo

            FROM vendas venda

            INNER JOIN clientes cliente
                ON cliente.id = venda.cliente_id

            INNER JOIN armazens armazem
                ON armazem.id = venda.armazem_id

            LEFT JOIN formas_pagamento forma
                ON forma.id = venda.forma_pagamento_id

            INNER JOIN status
                ON status.id = venda.status_id
                AND status.tipo = 'venda'

            LEFT JOIN filiais filial
                ON filial.id = venda.filial_id
                AND filial.empresa_id = venda.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id = venda.centro_custo_id
                AND centro.empresa_id = venda.empresa_id

            WHERE venda.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND venda.filial_id = :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $sql .= "
            ORDER BY venda.id DESC
        ";

        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR VENDA
    // ==================================================

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $this->validarEmpresa(
            $empresaId
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Venda inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                venda.*,

                cliente.nome AS cliente_nome,

                armazem.nome AS armazem_nome,

                forma.nome AS forma_pagamento_nome,

                status.nome AS status_nome,

                COALESCE(
                    filial.nome,
                    'Sede Principal / Sem filial'
                ) AS filial_nome,

                COALESCE(
                    centro.nome,
                    'Sem centro de receita'
                ) AS centro_custo_nome,

                centro.codigo AS centro_custo_codigo

            FROM vendas venda

            INNER JOIN clientes cliente
                ON cliente.id = venda.cliente_id

            INNER JOIN armazens armazem
                ON armazem.id = venda.armazem_id

            LEFT JOIN formas_pagamento forma
                ON forma.id = venda.forma_pagamento_id

            INNER JOIN status
                ON status.id = venda.status_id
                AND status.tipo = 'venda'

            LEFT JOIN filiais filial
                ON filial.id = venda.filial_id
                AND filial.empresa_id = venda.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id = venda.centro_custo_id
                AND centro.empresa_id = venda.empresa_id

            WHERE venda.id = :id
              AND venda.empresa_id = :empresa_id

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
    // BUSCAR ITENS
    // ==================================================

    public function buscarItens(
        int $vendaId,
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        if ($vendaId <= 0) {
            throw new RuntimeException(
                'Venda inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                item.*,

                produto.nome AS produto_nome

            FROM venda_itens item

            INNER JOIN vendas venda
                ON venda.id = item.venda_id

            INNER JOIN produtos produto
                ON produto.id = item.produto_id

            WHERE item.venda_id = :venda_id
              AND venda.empresa_id = :empresa_id

            ORDER BY item.id ASC
        ");

        $stmt->execute([
            ':venda_id' => $vendaId,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // RESERVAR NÚMERO DO DOCUMENTO
    // ==================================================

    private function resolverFilialVenda(
        array $dados
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                filial_id
            FROM armazens
            WHERE id = :armazem_id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");

        $stmt->execute([
            ':armazem_id' =>
            $dados['armazem_id'],

            ':empresa_id' =>
            $dados['empresa_id']
        ]);

        $filialArmazem =
            $stmt->fetchColumn();

        if ($filialArmazem === false) {
            throw new RuntimeException(
                'Armazém não encontrado.'
            );
        }

        $filialArmazem =
            !empty($filialArmazem)
            ? (int)$filialArmazem
            : null;

        /*
     * Quando não foi informada uma filial,
     * usar automaticamente a filial do armazém.
     */
        if ($dados['filial_id'] === null) {

            $dados['filial_id'] =
                $filialArmazem;

            return $dados;
        }

        /*
     * Quando a filial veio da sessão,
     * o armazém deve pertencer a ela.
     */
        if (
            $filialArmazem !== null
            && $filialArmazem
            !== (int)$dados['filial_id']
        ) {
            throw new RuntimeException(
                'O armazém selecionado pertence a outra filial.'
            );
        }

        return $dados;
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

        $dados =
            $this->resolverFilialVenda(
                $dados
            );

        $this->validarDadosVenda(
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
                        'Venda não encontrada.'
                    );
                }


                $dadosAnteriores['itens'] =
                    $this->buscarItens(
                        (int)$dados['id'],
                        (int)$dados['empresa_id']
                    );
            }


            if ($novo) {

                $vendaId =
                    $this->criarVenda(
                        $dados
                    );
            } else {

                $vendaId =
                    $this->atualizarVenda(
                        $dados
                    );
            }


            $venda =
                $this->bloquearVenda(
                    $vendaId,
                    $dados['empresa_id']
                );


            $statusConfirmada =
                $this->statusVenda(
                    'Confirmada'
                );


            if (
                (int)$venda['status_id']
                === $statusConfirmada
            ) {

                $this->confirmarVenda(
                    $venda,
                    $dados
                );
            }


            $dadosNovos =
                $this->buscar(
                    $vendaId,
                    (int)$dados['empresa_id']
                );


            if (!$dadosNovos) {

                throw new RuntimeException(
                    'Não foi possível recarregar a venda.'
                );
            }


            $dadosNovos['itens'] =
                $this->buscarItens(
                    $vendaId,
                    (int)$dados['empresa_id']
                );


            if ($novo) {

                $this->auditoria
                    ->insercao(
                        (int)$dados['empresa_id'],
                        (int)$dados['usuario_id'],
                        'vendas',
                        $vendaId,
                        $this->dadosAuditoriaVenda(
                            $dadosNovos
                        )
                    );
            } else {

                $this->auditoria
                    ->atualizacao(
                        (int)$dados['empresa_id'],
                        (int)$dados['usuario_id'],
                        'vendas',
                        $vendaId,
                        $this->dadosAuditoriaVenda(
                            $dadosAnteriores
                        ),
                        $this->dadosAuditoriaVenda(
                            $dadosNovos
                        )
                    );
            }


            $this->log
                ->registrar(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'VENDAS',
                    $novo
                        ? 'CRIAR'
                        : 'EDITAR',

                    $novo
                        ? 'Registou a venda '
                        . (
                            $dadosNovos['numero_fatura']
                            ?? '#' . $vendaId
                        )
                        . '.'
                        : 'Atualizou a venda '
                        . (
                            $dadosNovos['numero_fatura']
                            ?? '#' . $vendaId
                        )
                        . '.',

                    $vendaId,

                    !empty($dadosNovos['filial_id'])
                        ? (int)$dadosNovos['filial_id']
                        : null
                );


            $this->pdo
                ->commit();


            return $vendaId;
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

    private function dadosAuditoriaVenda(
        array $venda
    ): array {

        $itensAuditoria =
            [];


        foreach (
            $venda['itens']
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
            isset($venda['empresa_id'])
                ? (int)$venda['empresa_id']
                : null,

            'filial_id' =>
            !empty($venda['filial_id'])
                ? (int)$venda['filial_id']
                : null,

            'centro_custo_id' =>
            !empty($venda['centro_custo_id'])
                ? (int)$venda['centro_custo_id']
                : null,

            'cliente_id' =>
            isset($venda['cliente_id'])
                ? (int)$venda['cliente_id']
                : null,

            'forma_pagamento_id' =>
            !empty($venda['forma_pagamento_id'])
                ? (int)$venda['forma_pagamento_id']
                : null,

            'condicao_pagamento' =>
            $venda['condicao_pagamento']
                ?? null,

            'numero_parcelas' =>
            isset($venda['numero_parcelas'])
                ? (int)$venda['numero_parcelas']
                : null,

            'tipo_parcelamento' =>
            $venda['tipo_parcelamento']
                ?? null,

            'armazem_id' =>
            isset($venda['armazem_id'])
                ? (int)$venda['armazem_id']
                : null,

            'status_id' =>
            isset($venda['status_id'])
                ? (int)$venda['status_id']
                : null,

            'status' =>
            $venda['status_nome']
                ?? null,

            'numero_fatura' =>
            $venda['numero_fatura']
                ?? null,

            'numero_documento' =>
            $venda['numero_documento']
                ?? null,

            'tipo_documento' =>
            $venda['tipo_documento']
                ?? null,

            'data_venda' =>
            $venda['data_venda']
                ?? null,

            'data_vencimento' =>
            $venda['data_vencimento']
                ?? null,

            'subtotal' =>
            isset($venda['subtotal'])
                ? (float)$venda['subtotal']
                : 0,

            'desconto' =>
            isset($venda['desconto'])
                ? (float)$venda['desconto']
                : 0,

            'imposto' =>
            isset($venda['imposto'])
                ? (float)$venda['imposto']
                : 0,

            'total' =>
            isset($venda['total'])
                ? (float)$venda['total']
                : 0,

            'origem' =>
            $venda['origem']
                ?? null,

            'conta_receber_id' =>
            !empty($venda['conta_receber_id'])
                ? (int)$venda['conta_receber_id']
                : null,

            'movimento_estoque_id' =>
            !empty($venda['movimento_estoque_id'])
                ? (int)$venda['movimento_estoque_id']
                : null,

            'observacoes' =>
            $venda['observacoes']
                ?? null,

            'itens' =>
            $itensAuditoria
        ];
    }

    // ==================================================
    // CRIAR VENDA
    // ==================================================

    private function criarVenda(
        array $dados
    ): int {

        $this->validarRelacionamentos(
            $dados
        );

        $numeroFatura =
            $this->gerarNumeroDocumentoDefinitivo(
                $dados['empresa_id'],
                $dados['tipo_documento'],
                $dados['data_venda']
            );

        $stmt = $this->pdo->prepare("
            INSERT INTO vendas
            (
                empresa_id,
                filial_id,
                centro_custo_id,

                cliente_id,
                forma_pagamento_id,
                condicao_pagamento,
                numero_parcelas,
                tipo_parcelamento,

                armazem_id,
                usuario_id,
                status_id,

                numero_fatura,
                numero_documento,
                tipo_documento,

                data_venda,
                data_vencimento,

                subtotal,
                desconto,
                imposto_habilitado,
                taxa_imposto,
                imposto,
                total,

                origem,
                observacoes
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :centro_custo_id,

                :cliente_id,
                :forma_pagamento_id,
                :condicao_pagamento,
                :numero_parcelas,
                :tipo_parcelamento,

                :armazem_id,
                :usuario_id,
                :status_id,

                :numero_fatura,
                :numero_documento,
                :tipo_documento,

                :data_venda,
                :data_vencimento,

                :subtotal,
                :desconto,
                :imposto_habilitado,
                :taxa_imposto,
                :imposto,
                :total,

                :origem,
                :observacoes
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $dados['empresa_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':centro_custo_id' =>
            $dados['centro_custo_id'],

            ':cliente_id' =>
            $dados['cliente_id'],

            ':forma_pagamento_id' =>
            $dados['forma_pagamento_id'],

            ':condicao_pagamento' =>
            $dados['condicao_pagamento'],

            ':numero_parcelas' =>
            $dados['numero_parcelas'],

            ':tipo_parcelamento' =>
            $dados['tipo_parcelamento'],

            ':armazem_id' =>
            $dados['armazem_id'],

            ':usuario_id' =>
            $dados['usuario_id'],

            ':status_id' =>
            $dados['status_id'],

            ':numero_fatura' =>
            $numeroFatura,

            ':numero_documento' =>
            $dados['numero_documento'],

            ':tipo_documento' =>
            $dados['tipo_documento'],

            ':data_venda' =>
            $dados['data_venda'],

            ':data_vencimento' =>
            $dados['data_vencimento'],

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

            ':origem' =>
            $dados['origem'],

            ':observacoes' =>
            $dados['observacoes']
        ]);

        $vendaId = (int)$this->pdo
            ->lastInsertId();

        $this->gravarItens(
            $vendaId,
            $dados['itens']
        );

        return $vendaId;
    }

    // ==================================================
    // ATUALIZAR VENDA
    // ==================================================

    private function atualizarVenda(
        array $dados
    ): int {

        $vendaAtual =
            $this->bloquearVenda(
                (int)$dados['id'],
                $dados['empresa_id']
            );

        $statusConfirmada =
            $this->statusVenda(
                'Confirmada'
            );

        $statusCancelada =
            $this->statusVenda(
                'Cancelada'
            );

        if (
            (int)$vendaAtual['status_id']
            === $statusCancelada
        ) {
            throw new RuntimeException(
                'Uma venda cancelada não pode ser editada.'
            );
        }

        if (
            (int)$vendaAtual['status_id']
            === $statusConfirmada
        ) {
            throw new RuntimeException(
                'A venda já está confirmada. Para alterar produtos ou valores será necessário cancelar e estornar a venda.'
            );
        }

        /*
         * O número reservado originalmente não pode
         * ser alterado pelo formulário.
         */

        if (
            $dados['numero_fatura']
            !== $vendaAtual['numero_fatura']
        ) {
            throw new RuntimeException(
                'O número da factura não pode ser alterado.'
            );
        }

        $this->validarRelacionamentos(
            $dados
        );

        $stmt = $this->pdo->prepare("
    UPDATE vendas
    SET
        filial_id =
            :filial_id,

        centro_custo_id =
            :centro_custo_id,

        cliente_id =
            :cliente_id,

        forma_pagamento_id =
            :forma_pagamento_id,

        condicao_pagamento =
            :condicao_pagamento,

        numero_parcelas =
            :numero_parcelas,

        tipo_parcelamento =
        :tipo_parcelamento,

        armazem_id =
            :armazem_id,

        status_id =
            :status_id,

        numero_documento =
            :numero_documento,

        tipo_documento =
            :tipo_documento,

        data_venda =
            :data_venda,

        data_vencimento =
            :data_vencimento,

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
            :total,

        origem =
            :origem,

        observacoes =
            :observacoes

    WHERE id = :id
      AND empresa_id = :empresa_id
");

        $stmt->execute([

            ':filial_id' =>
            $dados['filial_id'],

            ':centro_custo_id' =>
            $dados['centro_custo_id'],

            ':cliente_id' =>
            $dados['cliente_id'],

            ':forma_pagamento_id' =>
            $dados['forma_pagamento_id'],

            ':condicao_pagamento' =>
            $dados['condicao_pagamento'],

            ':numero_parcelas' =>
            $dados['numero_parcelas'],

            ':tipo_parcelamento' =>
            $dados['tipo_parcelamento'],

            ':armazem_id' =>
            $dados['armazem_id'],

            ':status_id' =>
            $dados['status_id'],

            ':numero_documento' =>
            $dados['numero_documento'],

            ':tipo_documento' =>
            $dados['tipo_documento'],

            ':data_venda' =>
            $dados['data_venda'],

            ':data_vencimento' =>
            $dados['data_vencimento'],

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

            ':origem' =>
            $dados['origem'],

            ':observacoes' =>
            $dados['observacoes'],

            ':id' =>
            $dados['id'],

            ':empresa_id' =>
            $dados['empresa_id']
        ]);

        $stmt = $this->pdo->prepare("
            DELETE FROM venda_itens

            WHERE venda_id = :venda_id
        ");

        $stmt->execute([
            ':venda_id' => $dados['id']
        ]);

        $this->gravarItens(
            (int)$dados['id'],
            $dados['itens']
        );

        return (int)$dados['id'];
    }

    // ==================================================
    // CONFIRMAR VENDA
    // ==================================================

    private function confirmarVenda(
        array $venda,
        array $dados
    ): void {

        // ==========================================
        // MOVIMENTO DE STOCK
        // ==========================================

        $movimentoEstoqueId =
            !empty($venda['movimento_estoque_id'])
            ? (int)$venda['movimento_estoque_id']
            : null;

        if ($movimentoEstoqueId === null) {

            $movimentoEstoqueId =
                $this->criarMovimentoEstoque(
                    $venda,
                    $dados['itens']
                );

            $stmt = $this->pdo->prepare("
            UPDATE vendas

            SET movimento_estoque_id =
                :movimento_id

            WHERE id = :id
              AND empresa_id = :empresa_id
        ");

            $stmt->execute([
                ':movimento_id' =>
                $movimentoEstoqueId,

                ':id' =>
                $venda['id'],

                ':empresa_id' =>
                $venda['empresa_id']
            ]);
        }


        // ==========================================
        // CONTA A RECEBER
        // ==========================================
        $contaReceberId =
            !empty($venda['conta_receber_id'])
            ? (int)$venda['conta_receber_id']
            : null;


        if ($contaReceberId === null) {

            /*
    |--------------------------------------------------------------------------
    | CRIAR UMA ÚNICA CONTA A RECEBER
    |--------------------------------------------------------------------------
    */

            $contaReceberId =
                $this->criarContaReceber(
                    $venda
                );


            if ($contaReceberId <= 0) {
                throw new RuntimeException(
                    'Não foi possível gerar a conta a receber da venda.'
                );
            }


            /*
    |--------------------------------------------------------------------------
    | GUARDAR A CONTA PRINCIPAL NA VENDA
    |--------------------------------------------------------------------------
    */

            $stmt =
                $this->pdo->prepare("
            UPDATE vendas

            SET conta_receber_id =
                :conta_receber_id

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id
        ");


            $stmt->execute([
                ':conta_receber_id' =>
                $contaReceberId,

                ':id' =>
                $venda['id'],

                ':empresa_id' =>
                $venda['empresa_id']
            ]);


            /*
    |--------------------------------------------------------------------------
    | CRIAR PLANO DE PARCELAS
    |--------------------------------------------------------------------------
    */

            $this->criarParcelasContaReceber(
                $contaReceberId,
                $venda,
                $dados
            );
        }


        // ==========================================
        // CONTABILIZAÇÃO DA RECEITA
        // ==========================================

        $this->contabilizarVenda(
            $venda
        );


        // ==========================================
        // CONTABILIZAÇÃO DO CMV E STOCK
        // ==========================================

        $this->contabilizarCustoVenda(
            $venda,
            $movimentoEstoqueId
        );


        // ==========================================
        // RECEBIMENTO AUTOMÁTICO À VISTA
        // ==========================================

        if (
            strtoupper(
                (string)$venda['condicao_pagamento']
            ) === 'AVISTA'
        ) {
            $this->registarRecebimentoAutomatico(
                $venda,
                $contaReceberId,
                $dados
            );
        }
    }

    // ==================================================
    // CONTABILIZAR CUSTO DA VENDA
    // ==================================================

    private function contabilizarCustoVenda(
        array $venda,
        int $movimentoEstoqueId
    ): ?int {

        $empresaId =
            (int)$venda['empresa_id'];

        $filialId =
            !empty($venda['filial_id'])
            ? (int)$venda['filial_id']
            : null;

        $vendaId =
            (int)$venda['id'];

        if ($movimentoEstoqueId <= 0) {
            throw new RuntimeException(
                'Movimento de stock inválido para contabilização do custo.'
            );
        }

        /*
     * Para não colidir com o lançamento principal da venda,
     * o CMV será registado com origem STOCK e origem_id igual
     * ao movimento de stock.
     */
        if (
            $this->contabilidade
            ->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                'STOCK',
                $movimentoEstoqueId
            )
        ) {
            return null;
        }

        $stmt =
            $this->pdo->prepare("
            SELECT
                COALESCE(
                    SUM(
                        item.quantidade
                        * item.custo_unitario
                    ),
                    0
                ) AS custo_total

            FROM movimento_estoque_itens item

            INNER JOIN movimentos_estoque movimento
                ON movimento.id =
                    item.movimento_id

            WHERE item.movimento_id =
                :movimento_id

              AND movimento.empresa_id =
                :empresa_id

              AND movimento.tipo =
                'SAIDA'
        ");

        $stmt->execute([
            ':movimento_id' =>
            $movimentoEstoqueId,

            ':empresa_id' =>
            $empresaId
        ]);

        $custoTotal =
            round(
                (float)$stmt->fetchColumn(),
                2
            );

        if ($custoTotal <= 0) {
            throw new RuntimeException(
                'Não foi possível calcular o custo dos produtos vendidos. Verifique o preço de custo dos produtos.'
            );
        }

        $dataVenda =
            substr(
                (string)$venda['data_venda'],
                0,
                10
            );

        $contexto =
            $this->contabilidade
            ->contextoData(
                $empresaId,
                $dataVenda
            );

        /*
     * Pode usar o diário de STOCK.
     * Esse diário deve estar activo e possuir
     * numeração automática.
     */
        $diario =
            $this->contabilidade
            ->diarioPorTipo(
                $empresaId,
                'STOCK'
            );

        $contaCmv =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'CMV'
            );

        $contaStock =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'STOCK'
            );

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
                    $venda['numero_fatura'],

                    'referencia' =>
                    'CMV-VENDA-'
                        . $vendaId,

                    'data_lancamento' =>
                    $dataVenda,

                    'data_documento' =>
                    $dataVenda,

                    'descricao' =>
                    'Custo dos produtos vendidos na factura '
                        . $venda['numero_fatura'],

                    'origem' =>
                    'STOCK',

                    'origem_id' =>
                    $movimentoEstoqueId,

                    'usuario_id' =>
                    (int)$venda['usuario_id']
                ],
                [
                    [
                        /*
                     * Débito: reconhece o custo da mercadoria vendida.
                     */
                        'conta_contabil_id' =>
                        (int)$contaCmv['id'],

                        'centro_custo_id' =>
                        !empty($venda['centro_custo_id'])
                            ? (int)$venda['centro_custo_id']
                            : null,

                        'cliente_id' =>
                        null,

                        'fornecedor_id' =>
                        null,

                        'descricao' =>
                        'Custo da venda '
                            . $venda['numero_fatura'],

                        'debito' =>
                        $custoTotal,

                        'credito' =>
                        0
                    ],

                    [
                        /*
                     * Crédito: reduz contabilisticamente o valor do stock.
                     */
                        'conta_contabil_id' =>
                        (int)$contaStock['id'],

                        'centro_custo_id' =>
                        null,

                        'cliente_id' =>
                        null,

                        'fornecedor_id' =>
                        null,

                        'descricao' =>
                        'Baixa do stock da venda '
                            . $venda['numero_fatura'],

                        'debito' =>
                        0,

                        'credito' =>
                        $custoTotal
                    ]
                ]
            );
    }


    // ==================================================
    // CONTABILIZAR VENDA
    // ==================================================

    private function contabilizarVenda(
        array $venda
    ): ?int {

        $empresaId =
            (int)$venda['empresa_id'];

        $filialId =
            !empty($venda['filial_id'])
            ? (int)$venda['filial_id']
            : null;

        $vendaId =
            (int)$venda['id'];

        /*
     * Impede lançamento duplicado quando confirmarVenda()
     * for executado novamente.
     */
        if (
            $this->contabilidade
            ->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                'VENDA',
                $vendaId
            )
        ) {
            return null;
        }

        $dataVenda =
            substr(
                (string)$venda['data_venda'],
                0,
                10
            );

        $contexto =
            $this->contabilidade
            ->contextoData(
                $empresaId,
                $dataVenda
            );

        $diario =
            $this->contabilidade
            ->diarioPorTipo(
                $empresaId,
                'VENDAS'
            );

        $contaClientes =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'CLIENTES'
            );

        $contaVendas =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'VENDAS'
            );

        $total =
            round(
                (float)$venda['total'],
                2
            );

        $imposto =
            round(
                (float)$venda['imposto'],
                2
            );

        /*
     * Receita líquida do imposto:
     *
     * total = receita líquida + imposto
     */
        $valorVendas =
            round(
                $total - $imposto,
                2
            );

        if ($total <= 0) {
            throw new RuntimeException(
                'O valor total da venda é inválido para contabilização.'
            );
        }

        if ($valorVendas <= 0) {
            throw new RuntimeException(
                'O valor da receita da venda é inválido.'
            );
        }

        $itens = [

            // Débito: cliente deve o total da factura
            [
                'conta_contabil_id' =>
                (int)$contaClientes['id'],

                'centro_custo_id' =>
                null,

                'cliente_id' =>
                (int)$venda['cliente_id'],

                'fornecedor_id' =>
                null,

                'descricao' =>
                'Valor a receber da venda '
                    . $venda['numero_fatura'],

                'debito' =>
                $total,

                'credito' =>
                0
            ],

            // Crédito: rendimento da venda sem imposto
            [
                'conta_contabil_id' =>
                (int)$contaVendas['id'],

                'centro_custo_id' =>
                !empty($venda['centro_custo_id'])
                    ? (int)$venda['centro_custo_id']
                    : null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                null,

                'descricao' =>
                'Receita da venda '
                    . $venda['numero_fatura'],

                'debito' =>
                0,

                'credito' =>
                $valorVendas
            ]
        ];

        /*
     * O IVA só é adicionado quando a venda
     * possui imposto superior a zero.
     */
        if ($imposto > 0) {

            $contaIvaLiquidado =
                $this->contabilidade
                ->contaIntegracao(
                    $empresaId,
                    'IVA_LIQUIDADO'
                );

            $itens[] = [
                'conta_contabil_id' =>
                (int)$contaIvaLiquidado['id'],

                'centro_custo_id' =>
                null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                null,

                'descricao' =>
                'IVA liquidado da venda '
                    . $venda['numero_fatura'],

                'debito' =>
                0,

                'credito' =>
                $imposto
            ];
        }

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
                    $venda['numero_fatura'],

                    'referencia' =>
                    'VENDA-' . $vendaId,

                    'data_lancamento' =>
                    $dataVenda,

                    'data_documento' =>
                    $dataVenda,

                    'descricao' =>
                    'Venda '
                        . $venda['numero_fatura'],

                    'origem' =>
                    'VENDA',

                    'origem_id' =>
                    $vendaId,

                    'usuario_id' =>
                    (int)$venda['usuario_id']
                ],
                $itens
            );
    }

    // ==================================================
    // CONTABILIZAR RECEBIMENTO AUTOMÁTICO
    // ==================================================

    private function contabilizarRecebimentoVenda(
        array $venda,
        array $dados,
        int $contaReceberId
    ): ?int {

        $empresaId =
            (int)$venda['empresa_id'];

        $filialId =
            !empty($venda['filial_id'])
            ? (int)$venda['filial_id']
            : null;

        $vendaId =
            (int)$venda['id'];

        /*
     * Impede duplicação caso o processo seja chamado novamente.
     */
        if (
            $this->contabilidade
            ->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                'RECEBIMENTO',
                $contaReceberId
            )
        ) {
            return null;
        }

        $dataVenda =
            substr(
                (string)$venda['data_venda'],
                0,
                10
            );

        $contexto =
            $this->contabilidade
            ->contextoData(
                $empresaId,
                $dataVenda
            );

        $destino =
            strtoupper(
                trim(
                    (string)(
                        $dados['destino_recebimento']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $destino,
                [
                    'CAIXA',
                    'BANCO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Destino do recebimento inválido.'
            );
        }

        $contaDestino =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                $destino === 'CAIXA'
                    ? 'CAIXA'
                    : 'BANCOS'
            );

        $contaClientes =
            $this->contabilidade
            ->contaIntegracao(
                $empresaId,
                'CLIENTES'
            );

        $tipoDiario =
            $destino === 'CAIXA'
            ? 'CAIXA'
            : 'BANCOS';

        $diario =
            $this->contabilidade
            ->diarioPorTipo(
                $empresaId,
                $tipoDiario
            );

        $valor =
            round(
                (float)$venda['total'],
                2
            );

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor do recebimento é inválido para contabilização.'
            );
        }

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
                    $venda['numero_fatura'],

                    'referencia' =>
                    'RECEBIMENTO-VENDA-'
                        . $vendaId,

                    'data_lancamento' =>
                    $dataVenda,

                    'data_documento' =>
                    $dataVenda,

                    'descricao' =>
                    'Recebimento da venda '
                        . $venda['numero_fatura'],

                    'origem' =>
                    'RECEBIMENTO',

                    'origem_id' =>
                    $contaReceberId,

                    'usuario_id' =>
                    (int)$venda['usuario_id']
                ],
                [
                    [
                        'conta_contabil_id' =>
                        (int)$contaDestino['id'],

                        'centro_custo_id' =>
                        !empty($venda['centro_custo_id'])
                            ? (int)$venda['centro_custo_id']
                            : null,

                        'cliente_id' =>
                        null,

                        'fornecedor_id' =>
                        null,

                        'descricao' =>
                        'Entrada por recebimento da venda '
                            . $venda['numero_fatura'],

                        'debito' =>
                        $valor,

                        'credito' =>
                        0
                    ],

                    [
                        'conta_contabil_id' =>
                        (int)$contaClientes['id'],

                        'centro_custo_id' =>
                        null,

                        'cliente_id' =>
                        (int)$venda['cliente_id'],

                        'fornecedor_id' =>
                        null,

                        'descricao' =>
                        'Liquidação do cliente na venda '
                            . $venda['numero_fatura'],

                        'debito' =>
                        0,

                        'credito' =>
                        $valor
                    ]
                ]
            );
    }

    // ==================================================
    // CRIAR MOVIMENTO DE STOCK
    // ==================================================

    private function criarMovimentoEstoque(
        array $venda,
        array $itens
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
                tipo,
                armazem_origem_id,
                usuario_id,
                status_id,
                data_movimento,
                observacoes
            )
            VALUES
            (
                :empresa_id,
                'SAIDA',
                :armazem_origem_id,
                :usuario_id,
                :status_id,
                NOW(),
                :observacoes
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $venda['empresa_id'],

            ':armazem_origem_id' =>
            $venda['armazem_id'],

            ':usuario_id' =>
            $venda['usuario_id'],

            ':status_id' =>
            $statusMovimento,

            ':observacoes' =>
            'Saída automática referente à venda '
                . $venda['numero_fatura']
        ]);

        $movimentoId =
            (int)$this->pdo
                ->lastInsertId();

        if ($movimentoId <= 0) {
            throw new RuntimeException(
                'Não foi possível criar o movimento de stock da venda.'
            );
        }

        foreach ($itens as $item) {

            $produtoId =
                (int)(
                    $item['produto_id']
                    ?? 0
                );

            $quantidade =
                round(
                    (float)(
                        $item['quantidade']
                        ?? 0
                    ),
                    3
                );

            if ($produtoId <= 0) {
                throw new RuntimeException(
                    'Produto inválido no movimento de stock.'
                );
            }

            if ($quantidade <= 0) {
                throw new RuntimeException(
                    'Quantidade inválida no movimento de stock.'
                );
            }

            /*
     * Obtém o custo do produto no momento da venda.
     * O valor fica gravado no movimento, preservando
     * o histórico mesmo que o preço de custo mude depois.
     */
            $stmtCusto =
                $this->pdo->prepare("
            SELECT
                preco_custo

            FROM produtos

            WHERE id =
                :produto_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

            $stmtCusto->execute([
                ':produto_id' =>
                $produtoId,

                ':empresa_id' =>
                $venda['empresa_id']
            ]);

            $custoUnitario =
                $stmtCusto->fetchColumn();

            if ($custoUnitario === false) {
                throw new RuntimeException(
                    'Produto não encontrado para calcular o custo da saída.'
                );
            }

            $custoUnitario =
                round(
                    (float)$custoUnitario,
                    2
                );

            if ($custoUnitario < 0) {
                throw new RuntimeException(
                    'O custo unitário do produto é inválido.'
                );
            }

            $stmt =
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

            $stmt->execute([
                ':movimento_id' =>
                $movimentoId,

                ':produto_id' =>
                $produtoId,

                ':quantidade' =>
                $quantidade,

                ':custo_unitario' =>
                $custoUnitario
            ]);
        }

        EstoqueService::aplicarMovimento(
            $movimentoId,
            $this->pdo
        );

        return $movimentoId;
    }

    // ==================================================
    // CRIAR CONTA A RECEBER
    // ==================================================

    private function criarContaReceber(
        array $venda
    ): int {

        $statusPendente =
            $this->statusPorTipoNome(
                'conta_receber',
                'Pendente'
            );

        $dataEmissao =
            substr(
                (string)$venda['data_venda'],
                0,
                10
            );

        $dataVencimento =
            !empty($venda['data_vencimento'])
            ? $venda['data_vencimento']
            : $dataEmissao;

        $stmt = $this->pdo->prepare("
            INSERT INTO contas_receber
            (
                empresa_id,
                filial_id,
                centro_custo_id,

                cliente_id,
                venda_id,
                forma_pagamento_id,
                status_id,

                numero_documento,

                data_emissao,
                data_vencimento,
                data_pagamento,

                valor,
                desconto,
                juros,
                multa,

                valor_recebido,
                saldo,

                observacoes
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :centro_custo_id,

                :cliente_id,
                :venda_id,
                :forma_pagamento_id,
                :status_id,

                :numero_documento,

                :data_emissao,
                :data_vencimento,
                NULL,

                :valor,
                0,
                0,
                0,

                0,
                :saldo,

                :observacoes
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $venda['empresa_id'],

            ':filial_id' =>
            $venda['filial_id'],

            ':centro_custo_id' =>
            $venda['centro_custo_id'],

            ':cliente_id' =>
            $venda['cliente_id'],

            ':venda_id' =>
            $venda['id'],

            ':forma_pagamento_id' =>
            $venda['forma_pagamento_id'],

            ':status_id' =>
            $statusPendente,

            ':numero_documento' =>
            $venda['numero_fatura'],

            ':data_emissao' =>
            $dataEmissao,

            ':data_vencimento' =>
            $dataVencimento,

            ':valor' =>
            $venda['total'],

            ':saldo' =>
            $venda['total'],

            ':observacoes' =>
            'Conta gerada automaticamente pela venda '
                . $venda['numero_fatura']
        ]);

        return (int)$this->pdo
            ->lastInsertId();
    }

    public function buscarParcelas(
        int $vendaId,
        int $empresaId
    ): array {

        if ($vendaId <= 0) {
            return [];
        }


        if ($empresaId <= 0) {
            return [];
        }


        $stmt =
            $this->pdo->prepare("
            SELECT
                parcela.id,

                parcela.conta_receber_id,

                parcela.numero_parcela,
                parcela.total_parcelas,

                parcela.data_vencimento,

                parcela.valor,
                parcela.valor_recebido,
                parcela.saldo,

                parcela.status,
                parcela.data_pagamento

            FROM contas_receber_parcelas parcela

            INNER JOIN contas_receber conta
                ON conta.id =
                    parcela.conta_receber_id

            WHERE conta.venda_id =
                :venda_id

              AND conta.empresa_id =
                :empresa_id

            ORDER BY
                parcela.numero_parcela ASC,
                parcela.id ASC
        ");


        $stmt->execute([
            ':venda_id' =>
            $vendaId,

            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    private function registarRecebimentoAutomatico(
        array $venda,
        int $contaReceberId,
        array $dados
    ): void {

        $valor =
            (float)$venda['total'];

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor do recebimento é inválido.'
            );
        }

        $destino =
            strtoupper(
                trim(
                    (string)(
                        $dados['destino_recebimento']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $destino,
                ['CAIXA', 'BANCO'],
                true
            )
        ) {
            throw new RuntimeException(
                'Destino do recebimento inválido.'
            );
        }


        // ==========================================
        // EVITAR RECEBIMENTO DUPLICADO
        // ==========================================

        $stmt = $this->pdo->prepare("
        SELECT id

        FROM recebimentos

        WHERE conta_receber_id =
            :conta_receber_id

          AND empresa_id =
            :empresa_id

          AND referencia =
            :referencia

        LIMIT 1
    ");

        $referencia =
            'VENDA-' . $venda['id'];

        $stmt->execute([
            ':conta_receber_id' =>
            $contaReceberId,

            ':empresa_id' =>
            $venda['empresa_id'],

            ':referencia' =>
            $referencia
        ]);

        if ($stmt->fetchColumn()) {
            return;
        }


        // ==========================================
        // REGISTAR RECEBIMENTO
        // ==========================================

        $stmt = $this->pdo->prepare("
        INSERT INTO recebimentos
        (
            empresa_id,
            conta_receber_id,
            forma_pagamento_id,
            usuario_id,
            valor,
            data_recebimento,
            referencia,
            observacoes
        )
        VALUES
        (
            :empresa_id,
            :conta_receber_id,
            :forma_pagamento_id,
            :usuario_id,
            :valor,
            :data_recebimento,
            :referencia,
            :observacoes
        )
    ");

        $stmt->execute([
            ':empresa_id' =>
            $venda['empresa_id'],

            ':conta_receber_id' =>
            $contaReceberId,

            ':forma_pagamento_id' =>
            $venda['forma_pagamento_id'],

            ':usuario_id' =>
            $venda['usuario_id'],

            ':valor' =>
            $valor,

            ':data_recebimento' =>
            $venda['data_venda'],

            ':referencia' =>
            $referencia,

            ':observacoes' =>
            'Recebimento automático da venda '
                . $venda['numero_fatura']
        ]);


        // ==========================================
        // ACTUALIZAR CONTA A RECEBER
        // ==========================================

        $statusRecebida =
            $this->statusContaReceberLiquidada();

        $stmt = $this->pdo->prepare("
        UPDATE contas_receber

        SET
            valor_recebido = :valor_recebido,
            saldo = 0,
            data_pagamento = :data_pagamento,
            status_id = :status_id

        WHERE id = :id
          AND empresa_id = :empresa_id
    ");

        $stmt->execute([
            ':valor_recebido' =>
            $valor,

            ':data_pagamento' =>
            substr(
                (string)$venda['data_venda'],
                0,
                10
            ),

            ':status_id' =>
            $statusRecebida,

            ':id' =>
            $contaReceberId,

            ':empresa_id' =>
            $venda['empresa_id']
        ]);

        $stmt =
            $this->pdo->prepare("
        UPDATE contas_receber_parcelas

        SET
            valor_recebido = valor,
            saldo = 0,
            status = 'PAGA',
            data_pagamento = :data_pagamento

        WHERE conta_receber_id =
            :conta_receber_id
    ");

        $stmt->execute([
            ':data_pagamento' =>
            substr(
                (string)$venda['data_venda'],
                0,
                10
            ),

            ':conta_receber_id' =>
            $contaReceberId
        ]);

        // ==========================================
        // REGISTAR ENTRADA NO DESTINO
        // ==========================================

        if ($destino === 'CAIXA') {

            $this->registarEntradaCaixaVenda(
                $venda,
                $dados,
                $valor,
                $contaReceberId
            );
        } else {

            $this->registarEntradaBancoVenda(
                $venda,
                $dados,
                $valor,
                $contaReceberId
            );
        }


        // ==========================================
        // CONTABILIZAR RECEBIMENTO
        // ==========================================

        $this->contabilizarRecebimentoVenda(
            $venda,
            $dados,
            $contaReceberId
        );
    }

    private function statusContaReceberLiquidada(): int
    {
        $nomesPossiveis = [
            'Recebido',
            'Recebida',
            'Pago',
            'Paga'
        ];

        foreach ($nomesPossiveis as $nome) {
            $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo = 'conta_receber'
              AND LOWER(nome) = LOWER(:nome)

            LIMIT 1
        ");

            $stmt->execute([
                ':nome' => $nome
            ]);

            $id =
                (int)$stmt->fetchColumn();

            if ($id > 0) {
                return $id;
            }
        }

        throw new RuntimeException(
            'Não foi encontrado um status de Conta a Receber liquidada.'
        );
    }

    private function registarEntradaCaixaVenda(
        array $venda,
        array $dados,
        float $valor,
        int $contaReceberId
    ): void {

        $caixaId =
            (int)($dados['caixa_id'] ?? 0);

        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido para o recebimento.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                abertura.id

            FROM abertura_caixa abertura

            WHERE abertura.caixa_id = :caixa_id
            AND abertura.usuario_id = :usuario_id
            AND abertura.empresa_id = :empresa_id
            AND abertura.data_fechamento IS NULL

            ORDER BY abertura.id DESC

            LIMIT 1

            FOR UPDATE
        ");

        $stmt->execute([
            ':caixa_id' =>
            $caixaId,

            ':usuario_id' =>
            $venda['usuario_id'],

            ':empresa_id' =>
            $venda['empresa_id']
        ]);
        $aberturaId =
            (int)$stmt->fetchColumn();

        if ($aberturaId <= 0) {
            throw new RuntimeException(
                'O caixa selecionado não possui uma abertura activa para este utilizador.'
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
            'ENTRADA',
            'RECEBIMENTO',
            :documento_id,
            :descricao,
            :valor,
            :data_movimento
        )
    ");

        $stmt->execute([
            ':empresa_id' =>
            $venda['empresa_id'],

            ':filial_id' =>
            $venda['filial_id'],

            ':centro_custo_id' =>
            !empty($venda['centro_custo_id'])
                ? (int)$venda['centro_custo_id']
                : null,

            ':caixa_id' =>
            $caixaId,

            ':abertura_id' =>
            $aberturaId,

            ':usuario_id' =>
            $venda['usuario_id'],

            ':documento_id' =>
            $contaReceberId,

            ':descricao' =>
            'Recebimento automático da venda '
                . $venda['numero_fatura'],

            ':valor' =>
            $valor,

            ':data_movimento' =>
            $venda['data_venda']
        ]);

        /*
        |--------------------------------------------------------------------------
        | ATUALIZAR SALDO DO CAIXA
        |--------------------------------------------------------------------------
        */

        $stmt = $this->pdo->prepare("
            UPDATE caixas

            SET saldo_atual =
                saldo_atual + :valor

            WHERE id = :caixa_id
            AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':valor' =>
            $valor,

            ':caixa_id' =>
            $caixaId,

            ':empresa_id' =>
            $venda['empresa_id']
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo do caixa.'
            );
        }
    }

    private function registarEntradaBancoVenda(
        array $venda,
        array $dados,
        float $valor,
        int $contaReceberId
    ): void {

        $bancoId =
            (int)($dados['banco_id'] ?? 0);

        if ($bancoId <= 0) {
            throw new RuntimeException(
                'Conta bancária inválida para o recebimento.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | BLOQUEAR A CONTA E CALCULAR OS SALDOS
        |--------------------------------------------------------------------------
        */

        $stmt = $this->pdo->prepare("
            SELECT saldo_atual

            FROM bancos

            WHERE id = :banco_id
              AND empresa_id = :empresa_id

            LIMIT 1

            FOR UPDATE
        ");

        $stmt->execute([
            ':banco_id' => $bancoId,
            ':empresa_id' => (int)$venda['empresa_id']
        ]);

        $saldoAnterior =
            $stmt->fetchColumn();

        if ($saldoAnterior === false) {
            throw new RuntimeException(
                'Conta bancária não encontrada.'
            );
        }

        $saldoAnterior =
            round((float)$saldoAnterior, 2);

        $saldoPosterior =
            round($saldoAnterior + $valor, 2);

        $stmt = $this->pdo->prepare("
            INSERT INTO movimentos_bancarios
            (
                empresa_id,
                filial_id,
                centro_custo_id,
                banco_id,
                usuario_id,
                tipo,
                categoria,
                origem,
                documento_id,
                descricao,
                valor,
                saldo_anterior,
                saldo_posterior,
                data_movimento
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :centro_custo_id,
                :banco_id,
                :usuario_id,
                'ENTRADA',
                'RECEBIMENTO',
                'RECEBIMENTO',
                :documento_id,
                :descricao,
                :valor,
                :saldo_anterior,
                :saldo_posterior,
                :data_movimento
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            (int)$venda['empresa_id'],

            ':filial_id' =>
            !empty($venda['filial_id'])
                ? (int)$venda['filial_id']
                : null,

            ':centro_custo_id' =>
            !empty($venda['centro_custo_id'])
                ? (int)$venda['centro_custo_id']
                : null,

            ':banco_id' =>
            $bancoId,

            ':usuario_id' =>
            $venda['usuario_id'],

            ':documento_id' =>
            $contaReceberId,

            ':descricao' =>
            'Recebimento automático da venda '
                . $venda['numero_fatura'],

            ':valor' =>
            $valor,

            ':saldo_anterior' =>
            $saldoAnterior,

            ':saldo_posterior' =>
            $saldoPosterior,

            ':data_movimento' =>
            $venda['data_venda']
        ]);

        /*
        |--------------------------------------------------------------------------
        | ATUALIZAR SALDO DO BANCO
        |--------------------------------------------------------------------------
        */

        $stmt = $this->pdo->prepare("
            UPDATE bancos

            SET saldo_atual = :saldo_posterior

            WHERE id = :banco_id
            AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':saldo_posterior' =>
            $saldoPosterior,

            ':banco_id' =>
            $bancoId,

            ':empresa_id' =>
            $venda['empresa_id']
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo da conta bancária.'
            );
        }
    }

    // ==================================================
    // GRAVAR ITENS
    // ==================================================

    private function gravarItens(
        int $vendaId,
        array $itens
    ): void {

        foreach ($itens as $item) {
            $stmt = $this->pdo->prepare("
                INSERT INTO venda_itens
                (
                    venda_id,
                    produto_id,
                    quantidade,
                    preco_unitario,
                    desconto,
                    imposto,
                    subtotal
                )
                VALUES
                (
                    :venda_id,
                    :produto_id,
                    :quantidade,
                    :preco_unitario,
                    :desconto,
                    :imposto,
                    :subtotal
                )
            ");

            $stmt->execute([
                ':venda_id' =>
                $vendaId,

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
    // BLOQUEAR VENDA
    // ==================================================

    private function bloquearVenda(
        int $id,
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT *

            FROM vendas

            WHERE id = :id
              AND empresa_id = :empresa_id

            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $venda = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$venda) {
            throw new RuntimeException(
                'Venda não encontrada.'
            );
        }

        return $venda;
    }

    public function cancelar(
        int $vendaId,
        int $empresaId,
        int $usuarioId,
        string $motivo
    ): array {

        $this->validarEmpresa(
            $empresaId
        );


        if ($vendaId <= 0) {

            throw new RuntimeException(
                'Venda inválida.'
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


        $this->pdo
            ->beginTransaction();


        try {

            // ==================================================
            // 1. BLOQUEAR VENDA
            // ==================================================

            $venda =
                $this->bloquearVenda(
                    $vendaId,
                    $empresaId
                );


            $statusConfirmada =
                $this->statusVenda(
                    'Confirmada'
                );


            $statusCancelada =
                $this->statusVenda(
                    'Cancelada'
                );


            if (
                (int)$venda['status_id']
                === $statusCancelada
            ) {

                throw new RuntimeException(
                    'Esta venda já está cancelada.'
                );
            }


            if (
                (int)$venda['status_id']
                !== $statusConfirmada
            ) {

                throw new RuntimeException(
                    'Apenas vendas confirmadas podem ser canceladas por estorno.'
                );
            }


            if (
                empty($venda['movimento_estoque_id'])
            ) {

                throw new RuntimeException(
                    'A venda não possui movimento de saída de stock.'
                );
            }


            if (
                empty($venda['conta_receber_id'])
            ) {

                throw new RuntimeException(
                    'A venda não possui conta a receber associada.'
                );
            }


            // ==================================================
            // 2. ITENS
            // ==================================================

            $itens =
                $this->buscarItens(
                    $vendaId,
                    $empresaId
                );


            if (!$itens) {

                throw new RuntimeException(
                    'A venda não possui itens para estorno.'
                );
            }


            // ==================================================
            // 3. CONTA A RECEBER / RECEBIMENTO
            // ==================================================

            $contaReceber =
                $this->buscarContaReceberParaCancelamento(
                    (int)$venda['conta_receber_id'],
                    $empresaId
                );


            $recebimento =
                $this->buscarRecebimentoAutomaticoVenda(
                    $venda,
                    $contaReceber
                );


            /*
         * Se a venda for a prazo e já houver
         * recebimentos manuais, bloqueamos.
         */
            if (
                strtoupper(
                    (string)$venda['condicao_pagamento']
                ) === 'APRAZO'
            ) {

                $this->validarContaReceberParaCancelamento(
                    (int)$venda['conta_receber_id'],
                    $empresaId
                );
            }


            // ==================================================
            // 4. ESTORNAR RECEBIMENTO À VISTA
            // ==================================================

            $movimentoFinanceiroEstornoId =
                null;


            $lancamentoRecebimentoEstornoId =
                null;


            if (
                strtoupper(
                    (string)$venda['condicao_pagamento']
                ) === 'AVISTA'
            ) {

                if (!$recebimento) {

                    throw new RuntimeException(
                        'O recebimento automático da venda não foi encontrado.'
                    );
                }


                $movimentoFinanceiroEstornoId =
                    $this->estornarRecebimentoAutomaticoVenda(
                        $venda,
                        $contaReceber,
                        $recebimento,
                        $usuarioId,
                        $motivo
                    );


                $tipoDiarioRecebimento =
                    $this->tipoDiarioRecebimentoVenda(
                        $venda,
                        $contaReceber
                    );


                $lancamentoRecebimentoEstornoId =
                    $this->estornarLancamentoOrigem(
                        $venda,
                        'RECEBIMENTO',
                        (int)$contaReceber['id'],
                        $usuarioId,
                        $motivo,
                        $tipoDiarioRecebimento
                    );
            }


            // ==================================================
            // 5. DEVOLVER STOCK
            // ==================================================

            $movimentoEstornoId =
                $this->criarMovimentoEstornoStockVenda(
                    $venda,
                    $itens,
                    $usuarioId
                );


            // ==================================================
            // 6. CANCELAR CONTA A RECEBER
            // ==================================================

            $this->cancelarContaReceberVenda(
                (int)$contaReceber['id'],
                $empresaId,
                $usuarioId,
                $motivo
            );


            // ==================================================
            // 7. ESTORNAR VENDA CONTABILÍSTICA
            // ==================================================

            $lancamentoVendaEstornoId =
                $this->estornarLancamentoOrigem(
                    $venda,
                    'VENDA',
                    $vendaId,
                    $usuarioId,
                    $motivo,
                    'VENDAS'
                );


            // ==================================================
            // 8. ESTORNAR CMV
            // ==================================================

            $lancamentoCmvEstornoId =
                $this->estornarLancamentoOrigem(
                    $venda,
                    'STOCK',
                    (int)$venda['movimento_estoque_id'],
                    $usuarioId,
                    $motivo,
                    'STOCK'
                );


            // ==================================================
            // 9. ATUALIZAR VENDA
            // ==================================================

            $stmt =
                $this->pdo
                ->prepare("
                    UPDATE vendas

                    SET status_id =
                        :status_id

                    WHERE id =
                        :id

                      AND empresa_id =
                        :empresa_id
                ");


            $stmt->execute([
                ':status_id' =>
                $statusCancelada,

                ':id' =>
                $vendaId,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount()
                !== 1
            ) {

                throw new RuntimeException(
                    'Não foi possível cancelar a venda.'
                );
            }


            // ==================================================
            // 10. AUDITORIA
            // ==================================================

            $vendaAntes =
                array_merge(
                    $venda,
                    [
                        'itens' =>
                        $itens
                    ]
                );


            $vendaDepois =
                $this->buscar(
                    $vendaId,
                    $empresaId
                );


            if (!$vendaDepois) {

                throw new RuntimeException(
                    'Não foi possível recarregar a venda cancelada.'
                );
            }


            $vendaDepois['itens'] =
                $itens;


            $dadosDepois =
                $this->dadosAuditoriaVenda(
                    $vendaDepois
                );


            $dadosDepois['motivo_cancelamento'] =
                $motivo;


            $dadosDepois['movimento_estorno_id'] =
                $movimentoEstornoId;


            $dadosDepois['lancamento_venda_estorno_id'] =
                $lancamentoVendaEstornoId;


            $dadosDepois['lancamento_cmv_estorno_id'] =
                $lancamentoCmvEstornoId;


            $dadosDepois['lancamento_recebimento_estorno_id'] =
                $lancamentoRecebimentoEstornoId;


            $this->auditoria
                ->atualizacao(
                    $empresaId,
                    $usuarioId,
                    'vendas',
                    $vendaId,
                    $this->dadosAuditoriaVenda(
                        $vendaAntes
                    ),
                    $dadosDepois
                );


            /*
         * Movimento de stock que devolveu
         * a mercadoria ao armazém.
         */
            $this->auditoria
                ->insercao(
                    $empresaId,
                    $usuarioId,
                    'movimentos_estoque',
                    $movimentoEstornoId,

                    [
                        'tipo' =>
                        'ENTRADA',

                        'origem' =>
                        'ESTORNO_VENDA',

                        'venda_id' =>
                        $vendaId,

                        'movimento_original_id' =>
                        (int)$venda['movimento_estoque_id'],

                        'armazem_id' =>
                        (int)$venda['armazem_id'],

                        'motivo' =>
                        $motivo
                    ]
                );


            // ==================================================
            // 11. LOG
            // ==================================================

            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'VENDAS',
                    'CANCELAR',

                    'Cancelou a venda '
                        . (
                            $venda['numero_fatura']
                            ?? '#' . $vendaId
                        )
                        . '. Motivo: '
                        . $motivo,

                    $vendaId,

                    !empty($venda['filial_id'])
                        ? (int)$venda['filial_id']
                        : null
                );


            $this->pdo
                ->commit();


            return [

                'movimento_estorno_id' =>
                $movimentoEstornoId,

                'movimento_financeiro_estorno_id' =>
                $movimentoFinanceiroEstornoId,

                'lancamento_venda_estorno_id' =>
                $lancamentoVendaEstornoId,

                'lancamento_cmv_estorno_id' =>
                $lancamentoCmvEstornoId,

                'lancamento_recebimento_estorno_id' =>
                $lancamentoRecebimentoEstornoId
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

    private function buscarContaReceberParaCancelamento(
        int $contaReceberId,
        int $empresaId
    ): array {

        $stmt =
            $this->pdo
            ->prepare("
                SELECT *

                FROM contas_receber

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                FOR UPDATE
            ");


        $stmt->execute([
            ':id' =>
            $contaReceberId,

            ':empresa_id' =>
            $empresaId
        ]);


        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$conta) {

            throw new RuntimeException(
                'Conta a receber da venda não encontrada.'
            );
        }


        return $conta;
    }

    private function validarContaReceberParaCancelamento(
        int $contaReceberId,
        int $empresaId
    ): void {

        $stmt =
            $this->pdo
            ->prepare("
                SELECT
                    COUNT(*)

                FROM recebimentos

                WHERE conta_receber_id =
                    :conta_receber_id

                  AND empresa_id =
                    :empresa_id
            ");


        $stmt->execute([
            ':conta_receber_id' =>
            $contaReceberId,

            ':empresa_id' =>
            $empresaId
        ]);


        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {

            throw new RuntimeException(
                'A conta a receber possui recebimentos. Primeiro será necessário estornar os recebimentos antes de cancelar a venda.'
            );
        }
    }

    private function buscarRecebimentoAutomaticoVenda(
        array $venda,
        array $contaReceber
    ): ?array {

        $stmt =
            $this->pdo
            ->prepare("
                SELECT *

                FROM recebimentos

                WHERE empresa_id =
                    :empresa_id

                  AND conta_receber_id =
                    :conta_receber_id

                  AND referencia =
                    :referencia

                LIMIT 1

                FOR UPDATE
            ");


        $stmt->execute([
            ':empresa_id' =>
            $venda['empresa_id'],

            ':conta_receber_id' =>
            $contaReceber['id'],

            ':referencia' =>
            'VENDA-'
                . $venda['id']
        ]);


        $recebimento =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $recebimento
            ?: null;
    }

    private function tipoDiarioRecebimentoVenda(
        array $venda,
        array $contaReceber
    ): string {

        $empresaId =
            (int)$venda['empresa_id'];


        $contaReceberId =
            (int)$contaReceber['id'];


        /*
     * Caixa?
     */
        $stmt =
            $this->pdo
            ->prepare("
                SELECT COUNT(*)

                FROM movimentos_caixa

                WHERE empresa_id =
                    :empresa_id

                  AND origem =
                    'RECEBIMENTO'

                  AND documento_id =
                    :documento_id

                  AND tipo =
                    'ENTRADA'
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':documento_id' =>
            $contaReceberId
        ]);


        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {

            return 'CAIXA';
        }


        /*
     * Banco?
     */
        $stmt =
            $this->pdo
            ->prepare("
                SELECT COUNT(*)

                FROM movimentos_bancarios

                WHERE empresa_id =
                    :empresa_id

                  AND origem =
                    'RECEBIMENTO'

                  AND documento_id =
                    :documento_id

                  AND tipo =
                    'ENTRADA'
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':documento_id' =>
            $contaReceberId
        ]);


        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {

            return 'BANCOS';
        }


        throw new RuntimeException(
            'Não foi possível identificar se o recebimento da venda ocorreu em Caixa ou Banco.'
        );
    }

    private function estornarRecebimentoAutomaticoVenda(
        array $venda,
        array $contaReceber,
        array $recebimento,
        int $usuarioId,
        string $motivo
    ): int {

        $empresaId =
            (int)$venda['empresa_id'];


        $filialId =
            !empty($venda['filial_id'])
            ? (int)$venda['filial_id']
            : null;


        $contaReceberId =
            (int)$contaReceber['id'];


        $valor =
            round(
                (float)$recebimento['valor'],
                2
            );


        if ($valor <= 0) {

            throw new RuntimeException(
                'O recebimento possui valor inválido para estorno.'
            );
        }


        /*
     * ==================================================
     * IDENTIFICAR O MOVIMENTO FINANCEIRO ORIGINAL
     * ==================================================
     *
     * Primeiro Caixa.
     */

        $stmt =
            $this->pdo
            ->prepare("
                SELECT
                    id,
                    caixa_id,
                    abertura_id,
                    valor

                FROM movimentos_caixa

                WHERE empresa_id =
                    :empresa_id

                  AND origem =
                    'RECEBIMENTO'

                  AND documento_id =
                    :documento_id

                  AND tipo =
                    'ENTRADA'

                ORDER BY id DESC

                LIMIT 1

                FOR UPDATE
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':documento_id' =>
            $contaReceberId
        ]);


        $movimentoCaixa =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($movimentoCaixa) {

            $caixaId =
                (int)$movimentoCaixa['caixa_id'];


            /*
         * Verificar saldo para não produzir
         * saldo negativo por causa do estorno.
         */
            $stmt =
                $this->pdo
                ->prepare("
                    SELECT saldo_atual

                    FROM caixas

                    WHERE id =
                        :id

                      AND empresa_id =
                        :empresa_id

                    FOR UPDATE
                ");


            $stmt->execute([
                ':id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            $saldoAtual =
                $stmt->fetchColumn();


            if ($saldoAtual === false) {

                throw new RuntimeException(
                    'Caixa do recebimento não encontrado.'
                );
            }


            if (
                (float)$saldoAtual
                + 0.000001
                < $valor
            ) {

                throw new RuntimeException(
                    'O caixa não possui saldo suficiente para estornar este recebimento.'
                );
            }


            /*
         * Criar movimento inverso.
         */
            $stmt =
                $this->pdo
                ->prepare("
                    INSERT INTO movimentos_caixa
                    (
                        empresa_id,
                        filial_id,
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
                        :caixa_id,
                        :abertura_id,
                        :usuario_id,
                        'SAIDA',
                        'ESTORNO_RECEBIMENTO',
                        :documento_id,
                        :descricao,
                        :valor,
                        NOW()
                    )
                ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $filialId,

                ':caixa_id' =>
                $caixaId,

                ':abertura_id' =>
                !empty($movimentoCaixa['abertura_id'])
                    ? (int)$movimentoCaixa['abertura_id']
                    : null,

                ':usuario_id' =>
                $usuarioId,

                ':documento_id' =>
                $contaReceberId,

                ':descricao' =>
                'Estorno do recebimento da venda '
                    . $venda['numero_fatura']
                    . '. Motivo: '
                    . $motivo,

                ':valor' =>
                $valor
            ]);


            $movimentoEstornoId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($movimentoEstornoId <= 0) {

                throw new RuntimeException(
                    'Não foi possível criar o estorno no caixa.'
                );
            }


            $stmt =
                $this->pdo
                ->prepare("
                    UPDATE caixas

                    SET saldo_atual =
                        saldo_atual - :valor

                    WHERE id =
                        :id

                      AND empresa_id =
                        :empresa_id
                ");


            $stmt->execute([
                ':valor' =>
                $valor,

                ':id' =>
                $caixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount()
                !== 1
            ) {

                throw new RuntimeException(
                    'Não foi possível atualizar o saldo do caixa durante o estorno.'
                );
            }


            return $movimentoEstornoId;
        }


        /*
     * ==================================================
     * PROCURAR NO BANCO
     * ==================================================
     */

        $stmt =
            $this->pdo
            ->prepare("
                SELECT
                    id,
                    banco_id,
                    valor

                FROM movimentos_bancarios

                WHERE empresa_id =
                    :empresa_id

                  AND origem =
                    'RECEBIMENTO'

                  AND documento_id =
                    :documento_id

                  AND tipo =
                    'ENTRADA'

                ORDER BY id DESC

                LIMIT 1

                FOR UPDATE
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':documento_id' =>
            $contaReceberId
        ]);


        $movimentoBanco =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$movimentoBanco) {

            throw new RuntimeException(
                'Não foi encontrado o movimento financeiro original do recebimento.'
            );
        }


        $bancoId =
            (int)$movimentoBanco['banco_id'];


        $stmt =
            $this->pdo
            ->prepare("
                SELECT saldo_atual

                FROM bancos

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                FOR UPDATE
            ");


        $stmt->execute([
            ':id' =>
            $bancoId,

            ':empresa_id' =>
            $empresaId
        ]);


        $saldoAtual =
            $stmt->fetchColumn();


        if ($saldoAtual === false) {

            throw new RuntimeException(
                'Conta bancária do recebimento não encontrada.'
            );
        }


        if (
            (float)$saldoAtual
            + 0.000001
            < $valor
        ) {

            throw new RuntimeException(
                'A conta bancária não possui saldo suficiente para estornar este recebimento.'
            );
        }


        $stmt =
            $this->pdo
            ->prepare("
                INSERT INTO movimentos_bancarios
                (
                    empresa_id,
                    filial_id,
                    banco_id,
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
                    :banco_id,
                    :usuario_id,
                    'SAIDA',
                    'ESTORNO_RECEBIMENTO',
                    :documento_id,
                    :descricao,
                    :valor,
                    NOW()
                )
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            $filialId,

            ':banco_id' =>
            $bancoId,

            ':usuario_id' =>
            $usuarioId,

            ':documento_id' =>
            $contaReceberId,

            ':descricao' =>
            'Estorno do recebimento da venda '
                . $venda['numero_fatura']
                . '. Motivo: '
                . $motivo,

            ':valor' =>
            $valor
        ]);


        $movimentoEstornoId =
            (int)$this->pdo
                ->lastInsertId();


        if ($movimentoEstornoId <= 0) {

            throw new RuntimeException(
                'Não foi possível criar o estorno bancário.'
            );
        }


        $stmt =
            $this->pdo
            ->prepare("
                UPDATE bancos

                SET saldo_atual =
                    saldo_atual - :valor

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


        $stmt->execute([
            ':valor' =>
            $valor,

            ':id' =>
            $bancoId,

            ':empresa_id' =>
            $empresaId
        ]);


        if (
            $stmt->rowCount()
            !== 1
        ) {

            throw new RuntimeException(
                'Não foi possível atualizar o saldo bancário durante o estorno.'
            );
        }


        return $movimentoEstornoId;
    }

    private function estornarLancamentoOrigem(
        array $venda,
        string $origem,
        int $origemId,
        int $usuarioId,
        string $motivo,
        string $tipoDiario
    ): int {

        $empresaId =
            (int)$venda['empresa_id'];


        $filialId =
            !empty($venda['filial_id'])
            ? (int)$venda['filial_id']
            : null;


        if ($origemId <= 0) {

            throw new RuntimeException(
                'Origem contabilística inválida para estorno.'
            );
        }


        /*
     * ==================================================
     * LOCALIZAR LANÇAMENTO ORIGINAL
     * ==================================================
     */

        $sql = "
        SELECT
            id,
            numero_documento,
            referencia,
            descricao

        FROM lancamentos_contabilisticos

        WHERE empresa_id =
            :empresa_id

          AND origem =
            :origem

          AND origem_id =
            :origem_id
    ";


        $params = [

            ':empresa_id' =>
            $empresaId,

            ':origem' =>
            $origem,

            ':origem_id' =>
            $origemId
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
        ORDER BY id DESC

        LIMIT 1

        FOR UPDATE
    ";


        $stmt =
            $this->pdo
            ->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $original =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$original) {

            throw new RuntimeException(
                'Lançamento contabilístico original não encontrado para '
                    . $origem
                    . '.'
            );
        }


        $lancamentoOriginalId =
            (int)$original['id'];


        /*
     * ==================================================
     * IMPEDIR DUPLICIDADE DE ESTORNO
     * ==================================================
     */

        $stmt =
            $this->pdo
            ->prepare("
                SELECT id

                FROM lancamentos_contabilisticos

                WHERE empresa_id =
                    :empresa_id

                  AND origem =
                    'ESTORNO'

                  AND referencia =
                    :referencia

                LIMIT 1
            ");


        $referenciaEstorno =
            'ESTORNO-'
            . $origem
            . '-'
            . $origemId;


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':referencia' =>
            $referenciaEstorno
        ]);


        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {

            throw new RuntimeException(
                'Este lançamento já possui estorno.'
            );
        }


        /*
     * ==================================================
     * ITENS ORIGINAIS
     * ==================================================
     */

        $stmt =
            $this->pdo
            ->prepare("
                SELECT
                    conta_contabil_id,
                    centro_custo_id,
                    cliente_id,
                    fornecedor_id,
                    descricao,
                    debito,
                    credito

                FROM lancamento_contabilistico_itens

                WHERE lancamento_id =
                    :lancamento_id

                ORDER BY id ASC
            ");


        $stmt->execute([
            ':lancamento_id' =>
            $lancamentoOriginalId
        ]);


        $itensOriginais =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        if (!$itensOriginais) {

            throw new RuntimeException(
                'O lançamento contabilístico original não possui itens.'
            );
        }


        $itensEstorno =
            [];


        foreach (
            $itensOriginais as $item
        ) {

            $itensEstorno[] = [

                'conta_contabil_id' =>
                (int)$item['conta_contabil_id'],

                'centro_custo_id' =>
                !empty($item['centro_custo_id'])
                    ? (int)$item['centro_custo_id']
                    : null,

                'cliente_id' =>
                !empty($item['cliente_id'])
                    ? (int)$item['cliente_id']
                    : null,

                'fornecedor_id' =>
                !empty($item['fornecedor_id'])
                    ? (int)$item['fornecedor_id']
                    : null,

                'descricao' =>
                'Estorno: '
                    . (
                        $item['descricao']
                        ?? $original['descricao']
                    ),

                /*
             * INVERSÃO:
             *
             * débito original vira crédito
             * crédito original vira débito
             */
                'debito' =>
                (float)$item['credito'],

                'credito' =>
                (float)$item['debito']
            ];
        }


        /*
     * ==================================================
     * DATA DO ESTORNO
     * ==================================================
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
                $tipoDiario
            );


        /*
     * ==================================================
     * CRIAR ESTORNO
     * ==================================================
     */

        $lancamentoEstornoId =
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
                    $venda['numero_fatura'],

                    'referencia' =>
                    $referenciaEstorno,

                    'data_lancamento' =>
                    $dataEstorno,

                    'data_documento' =>
                    $dataEstorno,

                    'descricao' =>
                    'Estorno de '
                        . $origem
                        . ' da venda '
                        . $venda['numero_fatura']
                        . '. Motivo: '
                        . $motivo,

                    'origem' =>
                    'ESTORNO',

                    /*
                     * Aqui usamos o lançamento original,
                     * e não vendaId, porque VENDA,
                     * CMV e RECEBIMENTO podem ter
                     * origens diferentes.
                     */
                    'origem_id' =>
                    $lancamentoOriginalId,

                    'usuario_id' =>
                    $usuarioId
                ],

                $itensEstorno
            );


        if ($lancamentoEstornoId <= 0) {

            throw new RuntimeException(
                'Não foi possível criar o lançamento de estorno.'
            );
        }


        /*
     * ==================================================
     * RELACIONAR ORIGINAL ↔ ESTORNO
     * ==================================================
     */

        $stmt =
            $this->pdo
            ->prepare("
            UPDATE lancamentos_contabilisticos

            SET lancamento_estornado_id =
                :estorno_id

            WHERE id =
                :original_id

              AND empresa_id =
                :empresa_id
        ");


        $stmt->execute([
            ':estorno_id' =>
            $lancamentoEstornoId,

            ':original_id' =>
            $lancamentoOriginalId,

            ':empresa_id' =>
            $empresaId
        ]);


        if (
            $stmt->rowCount()
            !== 1
        ) {

            throw new RuntimeException(
                'Não foi possível relacionar o lançamento original ao estorno.'
            );
        }


        return $lancamentoEstornoId;
    }

    private function criarMovimentoEstornoStockVenda(
        array $venda,
        array $itens,
        int $usuarioId
    ): int {

        $statusMovimento =
            $this->statusPorTipoNome(
                'movimento_estoque',
                'Confirmado'
            );


        $stmt =
            $this->pdo
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
                    NULL,
                    :armazem_destino_id,
                    :usuario_id,
                    :status_id,
                    'ENTRADA',
                    :observacoes,
                    NOW()
                )
            ");


        $stmt->execute([
            ':empresa_id' =>
            $venda['empresa_id'],

            ':armazem_destino_id' =>
            $venda['armazem_id'],

            ':usuario_id' =>
            $usuarioId,

            ':status_id' =>
            $statusMovimento,

            ':observacoes' =>
            'Estorno da saída de stock da venda '
                . $venda['numero_fatura']
        ]);


        $movimentoId =
            (int)$this->pdo
                ->lastInsertId();


        if ($movimentoId <= 0) {

            throw new RuntimeException(
                'Não foi possível criar o movimento de estorno da venda.'
            );
        }


        /*
     * O custo deve vir do movimento original,
     * não do preço de venda nem do preço_custo atual.
     */
        $stmtCusto =
            $this->pdo
            ->prepare("
                SELECT
                    produto_id,
                    custo_unitario

                FROM movimento_estoque_itens

                WHERE movimento_id =
                    :movimento_id
            ");


        $stmtCusto->execute([
            ':movimento_id' =>
            $venda['movimento_estoque_id']
        ]);


        $custos =
            [];


        foreach (
            $stmtCusto->fetchAll(
                PDO::FETCH_ASSOC
            ) as $custo
        ) {

            $custos[(int)$custo['produto_id']] =
                (float)$custo['custo_unitario'];
        }


        $stmtItem =
            $this->pdo
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
                (int)$item['produto_id'];


            if (
                !array_key_exists(
                    $produtoId,
                    $custos
                )
            ) {

                throw new RuntimeException(
                    'Não foi encontrado o custo histórico do produto '
                        . (
                            $item['produto_nome']
                            ?? '#' . $produtoId
                        )
                        . '.'
                );
            }


            $stmtItem->execute([
                ':movimento_id' =>
                $movimentoId,

                ':produto_id' =>
                $produtoId,

                ':quantidade' =>
                (float)$item['quantidade'],

                ':custo_unitario' =>
                $custos[$produtoId]
            ]);
        }


        EstoqueService::aplicarMovimento(
            $movimentoId,
            $this->pdo
        );


        return $movimentoId;
    }

    private function cancelarContaReceberVenda(
        int $contaReceberId,
        int $empresaId,
        int $usuarioId,
        string $motivo
    ): void {

        $statusCancelado =
            $this->statusPorTipoNome(
                'conta_receber',
                'Cancelado'
            );


        $stmt =
            $this->pdo
            ->prepare("
                UPDATE contas_receber

                SET
                    status_id =
                        :status_id,

                    saldo =
                        0,

                    observacoes =
                        CONCAT(
                            COALESCE(
                                observacoes,
                                ''
                            ),
                            CASE
                                WHEN COALESCE(
                                    observacoes,
                                    ''
                                ) = ''
                                THEN ''
                                ELSE '\n'
                            END,
                            'Cancelada por estorno da venda. Motivo: ',
                            :motivo
                        )

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

            ':id' =>
            $contaReceberId,

            ':empresa_id' =>
            $empresaId
        ]);


        /*
     * As parcelas também deixam de estar
     * pendentes.
     */
        $stmt =
            $this->pdo
            ->prepare("
                UPDATE contas_receber_parcelas

                SET
                    saldo = 0,
                    status = 'CANCELADA'

                WHERE conta_receber_id =
                    :conta_receber_id
            ");


        $stmt->execute([
            ':conta_receber_id' =>
            $contaReceberId
        ]);
    }

    // ==================================================
    // ELIMINAR VENDA
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
                'Venda inválida.'
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

            $venda =
                $this->bloquearVenda(
                    $id,
                    $empresaId
                );


            /*
         * Venda que já provocou integração
         * não pode ser eliminada fisicamente.
         */
            if (
                !empty($venda['movimento_estoque_id'])
                || !empty($venda['conta_receber_id'])
            ) {

                throw new RuntimeException(
                    'Não é possível eliminar uma venda confirmada. Será necessário cancelar e estornar a venda.'
                );
            }


            /*
         * Fotografia antes da exclusão.
         */
            $itens =
                $this->buscarItens(
                    $id,
                    $empresaId
                );


            $dadosAuditoria =
                $this->dadosAuditoriaVenda(
                    array_merge(
                        $venda,
                        [
                            'itens' =>
                            $itens
                        ]
                    )
                );


            /*
         * Apagar itens.
         */
            $stmt =
                $this->pdo
                ->prepare("
                    DELETE FROM venda_itens

                    WHERE venda_id =
                        :venda_id
                ");


            $stmt->execute([
                ':venda_id' =>
                $id
            ]);


            /*
         * Apagar venda.
         */
            $stmt =
                $this->pdo
                ->prepare("
                    DELETE FROM vendas

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
                    'Não foi possível eliminar a venda.'
                );
            }


            /*
         * Auditoria.
         */
            $this->auditoria
                ->exclusao(
                    $empresaId,
                    $usuarioId,
                    'vendas',
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
                    'VENDAS',
                    'ELIMINAR',

                    'Eliminou a venda '
                        . (
                            $venda['numero_fatura']
                            ?? '#' . $id
                        )
                        . '.',

                    $id,

                    !empty($venda['filial_id'])
                        ? (int)$venda['filial_id']
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
    // CLIENTES
    // ==================================================

    public function clientes(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome

            FROM clientes

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
    // ARMAZÉNS
    // ==================================================

    public function armazens(
        int $empresaId,
        ?int $filialId = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $sql = "
        SELECT
            id,
            filial_id,
            nome,
            codigo
        FROM armazens
        WHERE empresa_id = :empresa_id
    ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        /*
     * Utilizador associado a uma filial
     * vê apenas armazéns dessa filial.
     */
        if ($filialId !== null) {

            $sql .= "
            AND filial_id = :filial_id
        ";

            $params[':filial_id'] =
                $filialId;
        }

        $sql .= "
        ORDER BY nome ASC
    ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

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
                preco_venda

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
    // STATUS DE VENDA
    // ==================================================

    public function listarStatus(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo = 'venda'

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

    public function visualizarProximoNumeroDocumento(
        int $empresaId,
        string $tipoDocumento,
        ?string $dataVenda = null
    ): string {

        $this->validarEmpresa(
            $empresaId
        );

        $tipoDocumento =
            $this->normalizarTipoDocumento(
                $tipoDocumento
            );

        $dataVenda =
            $dataVenda ?: date('Y-m-d');

        $dataBase =
            substr(
                $dataVenda,
                0,
                10
            );

        $this->validarData(
            $dataBase,
            'Data da venda'
        );

        $ano =
            (int)substr(
                $dataBase,
                0,
                4
            );

        $serie =
            $this->serieDocumento(
                $tipoDocumento
            );

        $stmt = $this->pdo->prepare("
        SELECT ultimo_numero

        FROM sequencias_documentos

        WHERE empresa_id = :empresa_id
          AND tipo_documento = :tipo_documento
          AND serie = :serie
          AND ano = :ano

        LIMIT 1
    ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':tipo_documento' =>
            $tipoDocumento,

            ':serie' =>
            $serie,

            ':ano' =>
            $ano
        ]);

        $ultimoNumero =
            (int)(
                $stmt->fetchColumn()
                ?: 0
            );

        $proximoNumero =
            $ultimoNumero + 1;

        return sprintf(
            '%s%d-%06d',
            $serie,
            $ano,
            $proximoNumero
        );
    }

    private function gerarNumeroDocumentoDefinitivo(
        int $empresaId,
        string $tipoDocumento,
        string $dataVenda
    ): string {

        $tipoDocumento =
            $this->normalizarTipoDocumento(
                $tipoDocumento
            );

        $dataBase =
            substr(
                $dataVenda,
                0,
                10
            );

        $this->validarData(
            $dataBase,
            'Data da venda'
        );

        $ano =
            (int)substr(
                $dataBase,
                0,
                4
            );

        $serie =
            $this->serieDocumento(
                $tipoDocumento
            );

        /*
     * O método é chamado dentro da transação
     * já iniciada pelo salvar().
     */

        $stmt = $this->pdo->prepare("
        INSERT INTO sequencias_documentos
        (
            empresa_id,
            tipo_documento,
            serie,
            ano,
            ultimo_numero
        )
        VALUES
        (
            :empresa_id,
            :tipo_documento,
            :serie,
            :ano,
            0
        )
        ON DUPLICATE KEY UPDATE
            id = id
    ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':tipo_documento' =>
            $tipoDocumento,

            ':serie' =>
            $serie,

            ':ano' =>
            $ano
        ]);

        $stmt = $this->pdo->prepare("
        SELECT
            id,
            ultimo_numero

        FROM sequencias_documentos

        WHERE empresa_id = :empresa_id
          AND tipo_documento = :tipo_documento
          AND serie = :serie
          AND ano = :ano

        FOR UPDATE
    ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':tipo_documento' =>
            $tipoDocumento,

            ':serie' =>
            $serie,

            ':ano' =>
            $ano
        ]);

        $sequencia =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$sequencia) {
            throw new RuntimeException(
                'Não foi possível obter a sequência documental.'
            );
        }

        $proximoNumero =
            (int)$sequencia['ultimo_numero']
            + 1;

        $stmt = $this->pdo->prepare("
        UPDATE sequencias_documentos

        SET ultimo_numero =
            :ultimo_numero

        WHERE id = :id
    ");

        $stmt->execute([
            ':ultimo_numero' =>
            $proximoNumero,

            ':id' =>
            $sequencia['id']
        ]);

        return sprintf(
            '%s%d-%06d',
            $serie,
            $ano,
            $proximoNumero
        );
    }

    // ==================================================
    // NORMALIZAR DADOS
    // ==================================================

    private function normalizarDados(
        array $dados
    ): array {



        $itens =
            $dados['itens'] ?? [];

        if (!is_array($itens)) {
            $itens = [];
        }

        $condicaoPagamento =
            strtoupper(
                trim(
                    (string)(
                        $dados['condicao_pagamento']
                        ?? 'APRAZO'
                    )
                )
            );



        $itensNormalizados = [];

        $dataVencimento =
            $this->textoOuNull(
                $dados['data_vencimento']
                    ?? null
            );

        if (
            $condicaoPagamento === 'APRAZO'
        ) {
            $dataVencimento =
                $this->textoOuNull(
                    $dados['parcela_1_data']
                        ?? $dataVencimento
                );
        }

        $tipoParcelamento =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_parcelamento']
                        ?? 'AUTOMATICO'
                    )
                )
            );


        if (
            !in_array(
                $tipoParcelamento,
                [
                    'AUTOMATICO',
                    'PERSONALIZADO'
                ],
                true
            )
        ) {
            $tipoParcelamento =
                'AUTOMATICO';
        }


        if (
            $condicaoPagamento
            !== 'APRAZO'
        ) {
            $tipoParcelamento =
                'AUTOMATICO';
        }

        foreach ($itens as $item) {

            $itensNormalizados[] = [
                'produto_id' =>
                (int)($item['produto_id'] ?? 0),

                'quantidade' =>
                (float)($item['quantidade'] ?? 0),

                'preco_unitario' =>
                (float)($item['preco_unitario'] ?? 0),

                'desconto' =>
                (float)($item['desconto'] ?? 0),

                'imposto' =>
                (float)($item['imposto'] ?? 0),

                'subtotal' =>
                (float)($item['subtotal'] ?? 0)
            ];
        }

        $impostoHabilitado =
            !empty($dados['imposto_habilitado']);

        $taxaImposto =
            16.00;


        $numeroParcelas =
            isset($dados['numero_parcelas'])
            ? (int)$dados['numero_parcelas']
            : 1;

        $numeroParcelas =
            max(
                1,
                min(
                    3,
                    $numeroParcelas
                )
            );

        $subtotal =
            round(
                array_reduce(
                    $itensNormalizados,
                    static function (
                        float $total,
                        array $item
                    ): float {

                        return $total
                            + (float)(
                                $item['subtotal']
                                ?? 0
                            );
                    },
                    0.0
                ),
                2
            );


        $desconto =
            max(
                0,
                round(
                    (float)(
                        $dados['desconto']
                        ?? 0
                    ),
                    2
                )
            );


        $baseTributavel =
            max(
                0,
                $subtotal
                    - $desconto
            );


        $imposto =
            $impostoHabilitado
            ? round(
                $baseTributavel
                    * 0.16,
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
            (int)($dados['empresa_id'] ?? 0),

            'filial_id' =>
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : null,

            'centro_custo_id' =>
            !empty($dados['centro_custo_id'])
                ? (int)$dados['centro_custo_id']
                : null,

            'cliente_id' =>
            (int)($dados['cliente_id'] ?? 0),

            'forma_pagamento_id' =>
            !empty($dados['forma_pagamento_id'])
                ? (int)$dados['forma_pagamento_id']
                : null,

            'condicao_pagamento' =>
            $condicaoPagamento,

            'armazem_id' =>
            (int)($dados['armazem_id'] ?? 0),

            'usuario_id' =>
            (int)($dados['usuario_id'] ?? 0),

            'status_id' =>
            (int)($dados['status_id'] ?? 0),

            'numero_fatura' =>
            trim(
                (string)(
                    $dados['numero_fatura']
                    ?? ''
                )
            ),

            'numero_documento' =>
            $this->textoOuNull(
                $dados['numero_documento']
                    ?? null
            ),

            'tipo_documento' =>
            $this->normalizarTipoDocumento(
                (string)(
                    $dados['tipo_documento']
                    ?? 'FACTURA'
                )
            ),

            'data_venda' =>
            trim(
                (string)(
                    $dados['data_venda']
                    ?? ''
                )
            ),

            'data_vencimento' =>
            $dataVencimento,

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

            'numero_parcelas' =>
            $numeroParcelas,

            'tipo_parcelamento' =>
            $tipoParcelamento,

            'parcela_1_data' =>
            $this->textoOuNull(
                $dados['parcela_1_data']
                    ?? null
            ),

            'parcela_2_data' =>
            $this->textoOuNull(
                $dados['parcela_2_data']
                    ?? null
            ),

            'parcela_3_data' =>
            $this->textoOuNull(
                $dados['parcela_3_data']
                    ?? null
            ),

            'parcela_1_valor' =>
            round(
                (float)(
                    $dados['parcela_1_valor']
                    ?? 0
                ),
                2
            ),

            'parcela_2_valor' =>
            round(
                (float)(
                    $dados['parcela_2_valor']
                    ?? 0
                ),
                2
            ),

            'parcela_3_valor' =>
            round(
                (float)(
                    $dados['parcela_3_valor']
                    ?? 0
                ),
                2
            ),

            'origem' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['origem']
                        ?? 'BALCAO'
                    )
                )
            ),
            'destino_recebimento' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['destino_recebimento']
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

            'observacoes' =>
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            ),

            'itens' =>
            $itensNormalizados
        ];
    }

    // ==================================================
    // VALIDAR DADOS DA VENDA
    // ==================================================

    private function validarDadosVenda(
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

        if (
            $dados['condicao_pagamento']
            === 'AVISTA'
        ) {
            if (
                !in_array(
                    $dados['destino_recebimento'],
                    ['CAIXA', 'BANCO'],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Seleccione o destino do recebimento da venda.'
                );
            }

            if (
                $dados['destino_recebimento']
                === 'CAIXA'
                && (
                    $dados['caixa_id'] === null
                    || $dados['caixa_id'] <= 0
                )
            ) {
                throw new RuntimeException(
                    'Seleccione o caixa que recebeu o valor.'
                );
            }

            if (
                $dados['destino_recebimento']
                === 'BANCO'
                && (
                    $dados['banco_id'] === null
                    || $dados['banco_id'] <= 0
                )
            ) {
                throw new RuntimeException(
                    'Seleccione a conta bancária que recebeu o valor.'
                );
            }
        }

        if (
            $dados['condicao_pagamento']
            === 'APRAZO'
        ) {

            if (
                $dados['numero_parcelas'] < 1
                || $dados['numero_parcelas'] > 3
            ) {
                throw new RuntimeException(
                    'A venda pode ser parcelada entre 1 e 3 parcelas.'
                );
            }


            for (
                $parcela = 1;
                $parcela <=
                    $dados['numero_parcelas'];
                $parcela++
            ) {

                $campo =
                    'parcela_'
                    . $parcela
                    . '_data';


                if (
                    empty($dados[$campo])
                ) {
                    throw new RuntimeException(
                        'Informe o vencimento da '
                            . $parcela
                            . 'ª parcela.'
                    );
                }
            }
        }

        if ($dados['cliente_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o cliente.'
            );
        }

        if ($dados['armazem_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o armazém.'
            );
        }

        if (
            $dados['centro_custo_id']
            === null
        ) {
            throw new RuntimeException(
                'Selecione o centro de receita.'
            );
        }

        if (
            $dados['forma_pagamento_id']
            === null
        ) {
            throw new RuntimeException(
                'Selecione a forma de pagamento.'
            );
        }

        if ($dados['status_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o status da venda.'
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

        if (
            !in_array(
                $dados['origem'],
                ['BALCAO', 'ONLINE', 'APP'],
                true
            )
        ) {
            throw new RuntimeException(
                'Origem da venda inválida.'
            );
        }

        if (
            $dados['numero_fatura'] === ''
        ) {
            throw new RuntimeException(
                'O número da factura não foi gerado.'
            );
        }

        $dataVenda =
            substr(
                $dados['data_venda'],
                0,
                10
            );

        $this->validarData(
            $dataVenda,
            'Data da venda'
        );

        if (
            $dados['condicao_pagamento']
            === 'APRAZO'
        ) {
            if (
                $dados['data_vencimento']
                === null
            ) {
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
                < $dataVenda
            ) {
                throw new RuntimeException(
                    'A data de vencimento não pode ser anterior à venda.'
                );
            }
        }

        if (empty($dados['itens'])) {
            throw new RuntimeException(
                'Adicione pelo menos um produto à venda.'
            );
        }

        foreach ($dados['itens'] as $item) {
            if ($item['produto_id'] <= 0) {
                throw new RuntimeException(
                    'Produto inválido na venda.'
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
                    'Subtotal do produto inválido.'
                );
            }
        }

        if ($dados['total'] <= 0) {
            throw new RuntimeException(
                'O total da venda deve ser superior a zero.'
            );
        }
    }

    private function criarParcelasContaReceber(
        int $contaReceberId,
        array $venda,
        array $dados
    ): void {

        if ($contaReceberId <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida.'
            );
        }


        $condicao =
            strtoupper(
                trim(
                    (string)(
                        $venda['condicao_pagamento']
                        ?? ''
                    )
                )
            );


        $total =
            round(
                (float)(
                    $venda['total']
                    ?? 0
                ),
                2
            );


        if ($total <= 0) {
            throw new RuntimeException(
                'O total da venda é inválido para parcelamento.'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | NÚMERO DE PARCELAS
    |--------------------------------------------------------------------------
    */

        $numeroParcelas =
            $condicao === 'APRAZO'
            ? max(
                1,
                min(
                    3,
                    (int)(
                        $dados['numero_parcelas']
                        ?? 1
                    )
                )
            )
            : 1;

        $tipoParcelamento =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_parcelamento']
                        ?? 'AUTOMATICO'
                    )
                )
            );


        /*
    |--------------------------------------------------------------------------
    | VALORES DAS PARCELAS
    |--------------------------------------------------------------------------
    |
    | Se o formulário enviou valores válidos cuja soma é igual ao total,
    | usamos esses valores.
    |
    | Caso contrário fazemos divisão automática.
    |--------------------------------------------------------------------------
    */

        $valores = [];


        /*
|--------------------------------------------------------------------------
| PERSONALIZADO
|--------------------------------------------------------------------------
*/

        if (
            $condicao === 'APRAZO'
            && $tipoParcelamento
            === 'PERSONALIZADO'
        ) {

            $somaCentavos = 0;


            for (
                $numero = 1;
                $numero <= $numeroParcelas;
                $numero++
            ) {

                $campoValor =
                    'parcela_'
                    . $numero
                    . '_valor';


                $valor =
                    round(
                        (float)(
                            $dados[$campoValor]
                            ?? 0
                        ),
                        2
                    );


                if ($valor <= 0) {

                    throw new RuntimeException(
                        'O valor da '
                            . $numero
                            . 'ª parcela deve ser superior a zero.'
                    );
                }


                $centavos =
                    (int)round(
                        $valor * 100
                    );


                $somaCentavos +=
                    $centavos;


                $valores[$numero] =
                    $centavos / 100;
            }


            $totalCentavos =
                (int)round(
                    $total * 100
                );


            if (
                $somaCentavos
                !== $totalCentavos
            ) {

                throw new RuntimeException(
                    'A soma das parcelas personalizadas deve ser igual ao total da venda.'
                );
            }
        }


        /*
|--------------------------------------------------------------------------
| AUTOMÁTICO
|--------------------------------------------------------------------------
*/ else {

            $totalCentavos =
                (int)round(
                    $total * 100
                );


            $baseCentavos =
                intdiv(
                    $totalCentavos,
                    $numeroParcelas
                );


            $resto =
                $totalCentavos
                - (
                    $baseCentavos
                    * $numeroParcelas
                );


            for (
                $numero = 1;
                $numero <= $numeroParcelas;
                $numero++
            ) {

                $centavos =
                    $baseCentavos;


                if (
                    $numero ===
                    $numeroParcelas
                ) {
                    $centavos +=
                        $resto;
                }


                $valores[$numero] =
                    $centavos / 100;
            }
        }


        /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO FINAL
    |--------------------------------------------------------------------------
    */

        $somaFinal =
            round(
                array_sum(
                    $valores
                ),
                2
            );


        if (
            abs(
                $somaFinal
                    - $total
            ) > 0.01
        ) {
            throw new RuntimeException(
                'A soma das parcelas deve ser igual ao total da venda.'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

        $stmt =
            $this->pdo->prepare("
            INSERT INTO contas_receber_parcelas
            (
                conta_receber_id,
                numero_parcela,
                total_parcelas,

                data_vencimento,

                valor,
                valor_recebido,
                saldo,

                status,
                data_pagamento
            )
            VALUES
            (
                :conta_receber_id,
                :numero_parcela,
                :total_parcelas,

                :data_vencimento,

                :valor,
                0,
                :saldo,

                'PENDENTE',
                NULL
            )
        ");


        for (
            $numero = 1;
            $numero <= $numeroParcelas;
            $numero++
        ) {

            /*
        |--------------------------------------------------------------------------
        | VENCIMENTO
        |--------------------------------------------------------------------------
        */

            if ($condicao === 'APRAZO') {

                $campoData =
                    'parcela_'
                    . $numero
                    . '_data';


                $dataVencimento =
                    trim(
                        (string)(
                            $dados[$campoData]
                            ?? ''
                        )
                    );


                if ($dataVencimento === '') {
                    throw new RuntimeException(
                        'Informe o vencimento da '
                            . $numero
                            . 'ª parcela.'
                    );
                }
            } else {

                /*
             * À vista:
             * parcela única vence no dia da venda.
             */

                $dataVencimento =
                    substr(
                        (string)$venda['data_venda'],
                        0,
                        10
                    );
            }


            $valor =
                round(
                    (float)$valores[$numero],
                    2
                );


            if ($valor <= 0) {
                throw new RuntimeException(
                    'O valor da '
                        . $numero
                        . 'ª parcela é inválido.'
                );
            }


            $stmt->execute([
                ':conta_receber_id' =>
                $contaReceberId,

                ':numero_parcela' =>
                $numero,

                ':total_parcelas' =>
                $numeroParcelas,

                ':data_vencimento' =>
                $dataVencimento,

                ':valor' =>
                $valor,

                ':saldo' =>
                $valor
            ]);
        }
    }



    // ==================================================
    // VALIDAR RELACIONAMENTOS
    // ==================================================

    private function validarRelacionamentos(
        array $dados
    ): void {

        $this->validarCliente(
            $dados['cliente_id'],
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

        $this->validarCentroReceita(
            $dados['centro_custo_id'],
            $dados['empresa_id'],
            $dados['filial_id']
        );

        $this->validarFormaPagamento(
            $dados['forma_pagamento_id']
        );

        $this->validarStatusVenda(
            $dados['status_id']
        );
    }

    private function validarCliente(
        int $id,
        int $empresaId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM clientes

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
                'Cliente não encontrado.'
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

    private function validarCentroReceita(
        ?int $id,
        int $empresaId,
        ?int $filialId
    ): void {

        if ($id === null) {
            throw new RuntimeException(
                'Selecione o centro de receita.'
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
                'Centro de receita não encontrado.'
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
                'O centro de receita não está activo.'
            );
        }

        if (
            !in_array(
                strtoupper(
                    (string)$centro['tipo']
                ),
                ['RECEITA', 'MISTO'],
                true
            )
        ) {
            throw new RuntimeException(
                'Para vendas, selecione um centro do tipo Receita ou Misto.'
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
                'O centro de receita pertence a outra filial.'
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

    private function validarStatusVenda(
        int $id
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE id = :id
              AND tipo = 'venda'

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Status inválido para venda.'
            );
        }
    }


    // ==================================================
    // STATUS
    // ==================================================

    private function statusVenda(
        string $nome
    ): int {

        return $this->statusPorTipoNome(
            'venda',
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

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                "Status '{$nome}' do tipo '{$tipo}' não encontrado."
            );
        }

        return $id;
    }

    // ==================================================
    // DOCUMENTOS
    // ==================================================

    private function normalizarTipoDocumento(
        string $tipo
    ): string {

        $tipo = strtoupper(
            trim($tipo)
        );

        $permitidos = [
            'FACTURA',
            'FACTURA_RECIBO',
            'RECIBO',
            'PROFORMA',
            'COTACAO'
        ];

        if (
            !in_array(
                $tipo,
                $permitidos,
                true
            )
        ) {
            throw new RuntimeException(
                'Tipo de documento inválido.'
            );
        }

        return $tipo;
    }

    private function serieDocumento(
        string $tipoDocumento
    ): string {

        return match ($tipoDocumento) {
            'FACTURA' =>
            'FT',

            'FACTURA_RECIBO' =>
            'FR',

            'RECIBO' =>
            'RC',

            'PROFORMA' =>
            'PF',

            'COTACAO' =>
            'CT',

            default =>
            'VD'
        };
    }

    // ==================================================
    // UTILITÁRIOS
    // ==================================================

    private function validarData(
        string $data,
        string $campo
    ): void {

        $objeto =
            DateTime::createFromFormat(
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
                'Empresa inválida para a venda.'
            );
        }
    }
}
