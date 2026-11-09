<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/ContabilidadeService.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

require_once __DIR__
    . '/../models/ContaPagar.php';


final class Despesa
{
    private PDO $db;
    private AuditoriaService $auditoria;
    private LogService $log;

    public function __construct(
        PDO $db
    ) {
        $this->db =
            $db;

        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->auditoria =
            new AuditoriaService(
                $db
            );

        $this->log =
            new LogService(
                $db
            );
    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        int $empresaId
    ): array {

        return [
            'contas_gasto' =>
            $this->listarContasGasto(
                $empresaId
            ),

            'fornecedores' =>
            $this->listarFornecedores(
                $empresaId
            ),

            'centros_custo' =>
            $this->listarCentrosCusto(
                $empresaId
            ),

            'caixas' =>
            $this->listarCaixas(
                $empresaId
            ),

            'bancos' =>
            $this->listarBancos(
                $empresaId
            ),

            'status' =>
            $this->listarStatus()
        ];
    }


    // ==================================================
    // LISTAR DESPESAS
    // ==================================================

    public function listar(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                d.id,
                d.empresa_id,
                d.filial_id,
                d.centro_custo_id,
                d.fornecedor_id,
                d.conta_gasto_id,
                d.caixa_id,
                d.banco_id,
                d.conta_pagar_id,
                d.status_id,

                d.numero_documento,
                d.referencia,
                d.descricao,

                d.valor_base,
                d.imposto_valor,
                d.valor_total,

                d.data_despesa,
                d.data_vencimento,
                d.data_pagamento,

                d.tipo_liquidacao,
                d.observacoes,

                d.criado_em,
                d.atualizado_em,

                f.nome AS fornecedor_nome,

                cc.codigo AS conta_codigo,
                cc.nome AS conta_nome,

                centro.codigo AS centro_custo_codigo,
                centro.nome AS centro_custo_nome,

                cx.nome AS caixa_nome,

                b.nome_banco AS banco_nome,

                s.nome AS status_nome,

                cp.valor AS conta_pagar_valor,
                cp.valor_pago AS conta_pagar_valor_pago,
                cp.saldo AS conta_pagar_saldo,

                scp.nome AS conta_pagar_status

            FROM despesas d

            INNER JOIN contas_contabilisticas cc
                ON cc.id =
                    d.conta_gasto_id
               AND cc.empresa_id =
                    d.empresa_id

            INNER JOIN status s
                ON s.id =
                    d.status_id
               AND s.tipo =
                    'despesa'

            LEFT JOIN fornecedores f
                ON f.id =
                    d.fornecedor_id
               AND f.empresa_id =
                    d.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id =
                    d.centro_custo_id
               AND centro.empresa_id =
                    d.empresa_id

            LEFT JOIN caixas cx
                ON cx.id =
                    d.caixa_id
               AND cx.empresa_id =
                    d.empresa_id

            LEFT JOIN bancos b
                ON b.id =
                    d.banco_id
               AND b.empresa_id =
                    d.empresa_id

            LEFT JOIN contas_pagar cp
               ON cp.id = d.conta_pagar_id
               AND cp.empresa_id = d.empresa_id

            LEFT JOIN status scp
                ON scp.id = cp.status_id
            AND scp.tipo = 'conta_pagar'

            WHERE d.empresa_id =
                :empresa_id

            ORDER BY
                d.data_despesa DESC,
                d.id DESC
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



    // ==================================================
    // BUSCAR DESPESA
    // ==================================================

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT
                    d.*

                FROM despesas d

                WHERE d.id =
                    :id

                  AND d.empresa_id =
                    :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        $despesa =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $despesa
            ?: null;
    }


    // ==================================================
    // GUARDAR DESPESA
    // ==================================================

    public function guardar(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        $dados =
            $this->normalizarDados(
                $dados
            );

        /*
        * Quando o utilizador é geral e nenhuma filial
        * foi informada, utiliza a filial do centro de custo.
        */
        if (
            $dados['filial_id'] === null
            && $dados['centro_custo_id'] !== null
        ) {

            $dados['filial_id'] =
                $this->filialCentroCusto(
                    $empresaId,
                    $dados['centro_custo_id']
                );
        }




        $this->validarDados(
            $empresaId,
            $dados
        );


        $id =
            (int)(
                $dados['id']
                ?? 0
            );


        /*
     * Edição possui a própria transação.
     */
        if ($id > 0) {

            $this->atualizar(
                $id,
                $empresaId,
                $usuarioId,
                $dados
            );

            return $id;
        }


        $this->db->beginTransaction();

        try {

            $stmt =
                $this->db->prepare("
                INSERT INTO despesas
                (
                    empresa_id,
                    filial_id,
                    centro_custo_id,
                    fornecedor_id,
                    conta_gasto_id,
                    forma_pagamento_id,
                    caixa_id,
                    banco_id,
                    conta_pagar_id,
                    status_id,

                    numero_documento,
                    referencia,
                    descricao,

                    valor_base,
                    imposto_valor,
                    valor_total,

                    data_despesa,
                    data_vencimento,
                    data_pagamento,

                    tipo_liquidacao,
                    observacoes,

                    criado_por,
                    atualizado_por
                )
                VALUES
                (
                    :empresa_id,
                    :filial_id,
                    :centro_custo_id,
                    :fornecedor_id,
                    :conta_gasto_id,
                    NULL,
                    :caixa_id,
                    :banco_id,
                    NULL,
                    :status_id,

                    :numero_documento,
                    :referencia,
                    :descricao,

                    :valor_base,
                    :imposto_valor,
                    :valor_total,

                    :data_despesa,
                    :data_vencimento,
                    NULL,

                    :tipo_liquidacao,
                    :observacoes,

                    :criado_por,
                    :atualizado_por
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $dados['filial_id'],

                ':centro_custo_id' =>
                $dados['centro_custo_id'],

                ':fornecedor_id' =>
                $dados['fornecedor_id'],

                ':conta_gasto_id' =>
                $dados['conta_gasto_id'],

                ':caixa_id' =>
                $dados['caixa_id'],

                ':banco_id' =>
                $dados['banco_id'],

                ':status_id' =>
                $this->statusId(
                    'Rascunho'
                ),

                ':numero_documento' =>
                $dados['numero_documento'],

                ':referencia' =>
                $dados['referencia'],

                ':descricao' =>
                $dados['descricao'],

                ':valor_base' =>
                $dados['valor_base'],

                ':imposto_valor' =>
                $dados['imposto_valor'],

                ':valor_total' =>
                $dados['valor_total'],

                ':data_despesa' =>
                $dados['data_despesa'],

                ':data_vencimento' =>
                $dados['data_vencimento'],

                ':tipo_liquidacao' =>
                $dados['tipo_liquidacao'],

                ':observacoes' =>
                $dados['observacoes'],

                ':criado_por' =>
                $usuarioId,

                ':atualizado_por' =>
                $usuarioId
            ]);


            $id =
                (int)$this->db
                    ->lastInsertId();


            if ($id <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar a despesa criada.'
                );
            }


            $despesaDepois =
                $this->buscarDespesaAuditoria(
                    $id,
                    $empresaId
                );


            if (!$despesaDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar a despesa criada.'
                );
            }


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'despesas',
                $id,
                $this->dadosAuditoriaDespesa(
                    $despesaDepois
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'DESPESAS',
                'CRIAR',
                'Despesa criada: #'
                    . $id
                    . ' - '
                    . $despesaDepois['descricao'],
                $id,
                !empty($despesaDepois['filial_id'])
                    ? (int)$despesaDepois['filial_id']
                    : null
            );


            $this->db->commit();

            return $id;
        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    // ==================================================
    // ATUALIZAR
    // ==================================================

    private function atualizar(
        int $id,
        int $empresaId,
        int $usuarioId,
        array $dados
    ): void {

        if ($id <= 0) {
            throw new RuntimeException(
                'Despesa inválida.'
            );
        }


        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        $this->db->beginTransaction();

        try {

            /*
         * Bloquear a despesa e guardar
         * o estado anterior.
         */
            $stmt =
                $this->db->prepare("
                SELECT *
                FROM despesas
                WHERE id = :id
                  AND empresa_id = :empresa_id
                FOR UPDATE
            ");


            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            $despesaAntes =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$despesaAntes) {
                throw new RuntimeException(
                    'Despesa não encontrada.'
                );
            }


            $statusRascunho =
                $this->statusId(
                    'Rascunho'
                );


            if (
                (int)$despesaAntes['status_id']
                !== $statusRascunho
            ) {
                throw new RuntimeException(
                    'Apenas despesas em rascunho podem ser alteradas.'
                );
            }


            $stmt =
                $this->db->prepare("
                UPDATE despesas

                SET
                    filial_id =
                        :filial_id,

                    centro_custo_id =
                        :centro_custo_id,

                    fornecedor_id =
                        :fornecedor_id,

                    conta_gasto_id =
                        :conta_gasto_id,

                    forma_pagamento_id = NULL,

                    caixa_id =
                        :caixa_id,

                    banco_id =
                        :banco_id,

                    numero_documento =
                        :numero_documento,

                    referencia =
                        :referencia,

                    descricao =
                        :descricao,

                    valor_base =
                        :valor_base,

                    imposto_valor =
                        :imposto_valor,

                    valor_total =
                        :valor_total,

                    data_despesa =
                        :data_despesa,

                    data_vencimento =
                        :data_vencimento,

                    tipo_liquidacao =
                        :tipo_liquidacao,

                    observacoes =
                        :observacoes,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");


            $stmt->execute([
                ':filial_id' =>
                $dados['filial_id'],

                ':centro_custo_id' =>
                $dados['centro_custo_id'],

                ':fornecedor_id' =>
                $dados['fornecedor_id'],

                ':conta_gasto_id' =>
                $dados['conta_gasto_id'],

                ':caixa_id' =>
                $dados['caixa_id'],

                ':banco_id' =>
                $dados['banco_id'],

                ':numero_documento' =>
                $dados['numero_documento'],

                ':referencia' =>
                $dados['referencia'],

                ':descricao' =>
                $dados['descricao'],

                ':valor_base' =>
                $dados['valor_base'],

                ':imposto_valor' =>
                $dados['imposto_valor'],

                ':valor_total' =>
                $dados['valor_total'],

                ':data_despesa' =>
                $dados['data_despesa'],

                ':data_vencimento' =>
                $dados['data_vencimento'],

                ':tipo_liquidacao' =>
                $dados['tipo_liquidacao'],

                ':observacoes' =>
                $dados['observacoes'],

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            $despesaDepois =
                $this->buscarDespesaAuditoria(
                    $id,
                    $empresaId
                );


            if (!$despesaDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar a despesa atualizada.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'despesas',
                $id,
                $this->dadosAuditoriaDespesa(
                    $despesaAntes
                ),
                $this->dadosAuditoriaDespesa(
                    $despesaDepois
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'DESPESAS',
                'EDITAR',
                'Despesa atualizada: #'
                    . $id
                    . ' - '
                    . $despesaDepois['descricao'],
                $id,
                !empty($despesaDepois['filial_id'])
                    ? (int)$despesaDepois['filial_id']
                    : null
            );


            $this->db->commit();
        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // CONTABILIZAR DESPESA
    // ==================================================

    public function contabilizar(
        int $id,
        int $empresaId,
        int $usuarioId
    ): array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Despesa inválida.'
            );
        }

        $iniciouTransacao =
            !$this->db->inTransaction();

        if ($iniciouTransacao) {
            $this->db->beginTransaction();
        }

        try {

            // ==========================================
            // BLOQUEAR DESPESA
            // ==========================================

            $stmt =
                $this->db->prepare("
                SELECT d.*

                FROM despesas d

                WHERE d.id = :id
                  AND d.empresa_id = :empresa_id

                FOR UPDATE
            ");

            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

            $despesa =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$despesa) {
                throw new RuntimeException(
                    'Despesa não encontrada.'
                );
            }

            $despesaAntes =
                $despesa;


            if (
                empty($despesa['filial_id'])
                && !empty($despesa['centro_custo_id'])
            ) {

                $filialCentroId =
                    $this->filialCentroCusto(
                        $empresaId,
                        (int)$despesa['centro_custo_id']
                    );

                if ($filialCentroId !== null) {

                    $stmtFilial =
                        $this->db->prepare("
                UPDATE despesas

                SET filial_id =
                    :filial_id,

                    atualizado_por =
                    :atualizado_por,

                    atualizado_em =
                    NOW()

                WHERE id =
                    :despesa_id

                  AND empresa_id =
                    :empresa_id
            ");

                    $stmtFilial->execute([
                        ':filial_id' =>
                        $filialCentroId,

                        ':atualizado_por' =>
                        $usuarioId,

                        ':despesa_id' =>
                        $id,

                        ':empresa_id' =>
                        $empresaId
                    ]);

                    $despesa['filial_id'] =
                        $filialCentroId;
                }
            }



            /*
            * Variáveis utilizadas para auditoria
            * dos efeitos financeiros.
            */
            $movimentoCaixaId =
                null;

            $movimentoBancoId =
                null;

            $caixaAntes =
                null;

            $bancoAntes =
                null;

            // ==========================================
            // VALIDAR STATUS
            // ==========================================

            if (
                (int)$despesa['status_id']
                !== $this->statusId('Rascunho')
            ) {
                throw new RuntimeException(
                    'Apenas despesas em rascunho podem ser contabilizadas.'
                );
            }


            // ==========================================
            // VALIDAR CONTA
            // ==========================================

            $this->validarContaGasto(
                $empresaId,
                (int)$despesa['conta_gasto_id']
            );


            // ==========================================
            // VALOR
            // ==========================================

            $valor =
                round(
                    (float)$despesa['valor_total'],
                    2
                );

            if ($valor <= 0) {
                throw new RuntimeException(
                    'O valor da despesa é inválido.'
                );
            }


            $valorBase =
                round(
                    (float)$despesa['valor_base'],
                    2
                );


            $impostoValor =
                round(
                    (float)$despesa['imposto_valor'],
                    2
                );


            $valor =
                round(
                    (float)$despesa['valor_total'],
                    2
                );


            if ($valorBase <= 0) {

                throw new RuntimeException(
                    'O valor base da despesa é inválido.'
                );
            }


            if ($impostoValor < 0) {

                throw new RuntimeException(
                    'O valor do imposto não pode ser negativo.'
                );
            }


            $valorCalculado =
                round(
                    $valorBase
                        + $impostoValor,
                    2
                );


            if (
                abs(
                    $valorCalculado
                        - $valor
                ) > 0.01
            ) {

                throw new RuntimeException(
                    'O total da despesa não corresponde ao valor base mais o imposto.'
                );
            }


            // ==========================================
            // CONTABILIDADE
            // ==========================================

            $contabilidade =
                new ContabilidadeService(
                    $this->db
                );


            if (
                $contabilidade
                ->existeLancamentoOrigem(
                    $empresaId,
                    !empty($despesa['filial_id'])
                        ? (int)$despesa['filial_id']
                        : null,
                    'DESPESA',
                    $id
                )
            ) {
                throw new RuntimeException(
                    'Esta despesa já foi contabilizada.'
                );
            }


            $tipoLiquidacao =
                strtoupper(
                    trim(
                        (string)$despesa['tipo_liquidacao']
                    )
                );

            // ==========================================
            // TIPO DE LIQUIDAÇÃO
            // ==========================================

            if (
                $tipoLiquidacao
                === 'CAIXA'
            ) {

                // ======================================
                // CAIXA
                // ======================================

                $caixa =
                    $this->obterCaixaParaDespesa(
                        (int)$despesa['caixa_id'],
                        $empresaId,
                        $valor
                    );

                $caixaAntes =
                    $this->buscarCaixaAuditoria(
                        (int)$caixa['id'],
                        $empresaId
                    );


                $contexto =
                    $contabilidade
                    ->contextoData(
                        $empresaId,
                        (string)$despesa['data_despesa']
                    );


                $diario =
                    $contabilidade
                    ->diarioPorTipo(
                        $empresaId,
                        'CAIXA'
                    );


                $contaFinanceira =
                    $contabilidade
                    ->contaIntegracao(
                        $empresaId,
                        'CAIXA'
                    );


                $lancamentoId =
                    $this->criarLancamentoDespesa(
                        $contabilidade,
                        $despesa,
                        $contexto,
                        $diario,
                        (int)$contaFinanceira['id'],
                        $usuarioId
                    );


                $movimentoCaixaId =
                    $this->registarSaidaCaixaDespesa(
                        $despesa,
                        $caixa,
                        $valor,
                        $usuarioId
                    );
            } elseif (
                $tipoLiquidacao
                === 'BANCO'
            ) {

                // ======================================
                // BANCO
                // ======================================

                $banco =
                    $this->obterBancoParaDespesa(
                        (int)$despesa['banco_id'],
                        $empresaId,
                        $valor
                    );

                $bancoAntes =
                    $this->buscarBancoAuditoria(
                        (int)$banco['id'],
                        $empresaId
                    );

                $contexto =
                    $contabilidade
                    ->contextoData(
                        $empresaId,
                        (string)$despesa['data_despesa']
                    );


                $diario =
                    $contabilidade
                    ->diarioPorTipo(
                        $empresaId,
                        'BANCOS'
                    );


                $contaFinanceira =
                    $contabilidade
                    ->contaIntegracao(
                        $empresaId,
                        'BANCOS'
                    );


                $lancamentoId =
                    $this->criarLancamentoDespesa(
                        $contabilidade,
                        $despesa,
                        $contexto,
                        $diario,
                        (int)$contaFinanceira['id'],
                        $usuarioId
                    );


                $movimentoBancoId =
                    $this->registarSaidaBancoDespesa(
                        $despesa,
                        $banco,
                        $valor,
                        $usuarioId
                    );
            } elseif (
                $tipoLiquidacao
                === 'CONTA_PAGAR'
            ) {

                // ======================================
                // CONTA A PAGAR
                // ======================================

                if (
                    empty($despesa['fornecedor_id'])
                ) {

                    throw new RuntimeException(
                        'Selecione o fornecedor da despesa a prazo.'
                    );
                }


                if (
                    empty($despesa['centro_custo_id'])
                ) {

                    throw new RuntimeException(
                        'Selecione o centro de custo da despesa a prazo.'
                    );
                }


                if (
                    empty($despesa['data_vencimento'])
                ) {

                    throw new RuntimeException(
                        'Informe a data de vencimento da despesa.'
                    );
                }


                // ======================================
                // CONTEXTO CONTABILÍSTICO
                // ======================================

                $contexto =
                    $contabilidade
                    ->contextoData(
                        $empresaId,
                        (string)$despesa['data_despesa']
                    );


                // ======================================
                // DIÁRIO
                // ======================================

                $diario =
                    $contabilidade
                    ->diarioPorTipo(
                        $empresaId,
                        'COMPRAS'
                    );


                // ======================================
                // CONTA FORNECEDORES
                // ======================================

                $contaFornecedores =
                    $contabilidade
                    ->contaIntegracao(
                        $empresaId,
                        'FORNECEDORES'
                    );


                // ======================================
                // LANÇAMENTO CONTABILÍSTICO
                // ======================================

                $lancamentoId =
                    $this->criarLancamentoDespesa(
                        $contabilidade,
                        $despesa,
                        $contexto,
                        $diario,
                        (int)$contaFornecedores['id'],
                        $usuarioId,
                        (int)$despesa['fornecedor_id']
                    );


                // ======================================
                // CRIAR CONTA A PAGAR
                // ======================================

                $contaPagarId =
                    $this->criarContaPagarDespesa(
                        $despesa,
                        $usuarioId
                    );


                if ($contaPagarId <= 0) {

                    throw new RuntimeException(
                        'Não foi possível criar a conta a pagar da despesa.'
                    );
                }


                // ======================================
                // LIGAR CONTA A PAGAR À DESPESA
                // ======================================

                $stmt =
                    $this->db->prepare("
            UPDATE despesas

            SET conta_pagar_id =
                :conta_pagar_id

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");


                $stmt->execute([
                    ':conta_pagar_id' =>
                    $contaPagarId,

                    ':id' =>
                    (int)$despesa['id'],

                    ':empresa_id' =>
                    $empresaId
                ]);


                if (
                    $stmt->rowCount()
                    !== 1
                ) {

                    throw new RuntimeException(
                        'Não foi possível associar a conta a pagar à despesa.'
                    );
                }
            } else {

                throw new RuntimeException(
                    'Tipo de liquidação inválido.'
                );
            }

            // ==========================================
            // ALTERAR STATUS
            // ==========================================

            $stmt =
                $this->db->prepare("
                UPDATE despesas

                SET
                    status_id =
                        :status_id,

                    data_pagamento =
                        :data_pagamento,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");


            $stmt->execute([
                ':status_id' =>
                $this->statusId(
                    'Contabilizada'
                ),

                ':data_pagamento' =>
                in_array(
                    $tipoLiquidacao,
                    [
                        'CAIXA',
                        'BANCO'
                    ],
                    true
                )
                    ? $despesa['data_despesa']
                    : null,

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

            // ==========================================
            // ESTADO FINAL DA DESPESA
            // ==========================================

            $despesaDepois =
                $this->buscarDespesaAuditoria(
                    $id,
                    $empresaId
                );


            if (!$despesaDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar a despesa contabilizada.'
                );
            }


            // ==========================================
            // AUDITORIA DA DESPESA
            // ==========================================

            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'despesas',
                $id,
                $this->dadosAuditoriaDespesa(
                    $despesaAntes
                ),
                $this->dadosAuditoriaDespesa(
                    $despesaDepois
                )
            );


            // ==========================================
            // AUDITORIA - CAIXA
            // ==========================================

            if (
                $tipoLiquidacao === 'CAIXA'
                && $movimentoCaixaId !== null
            ) {

                $movimentoDepois =
                    $this->buscarMovimentoCaixaAuditoria(
                        $movimentoCaixaId,
                        $empresaId
                    );


                if (!$movimentoDepois) {
                    throw new RuntimeException(
                        'Não foi possível carregar o movimento de caixa criado.'
                    );
                }


                $this->auditoria->insercao(
                    $empresaId,
                    $usuarioId,
                    'movimentos_caixa',
                    $movimentoCaixaId,
                    $movimentoDepois
                );


                $caixaDepois =
                    $this->buscarCaixaAuditoria(
                        (int)$despesa['caixa_id'],
                        $empresaId
                    );


                if (
                    $caixaAntes
                    && $caixaDepois
                ) {

                    $this->auditoria->atualizacao(
                        $empresaId,
                        $usuarioId,
                        'caixas',
                        (int)$despesa['caixa_id'],
                        $this->dadosAuditoriaCaixa(
                            $caixaAntes
                        ),
                        $this->dadosAuditoriaCaixa(
                            $caixaDepois
                        )
                    );
                }
            }


            // ==========================================
            // AUDITORIA - BANCO
            // ==========================================

            if (
                $tipoLiquidacao === 'BANCO'
                && $movimentoBancoId !== null
            ) {

                $movimentoDepois =
                    $this->buscarMovimentoBancoAuditoria(
                        $movimentoBancoId,
                        $empresaId
                    );


                if (!$movimentoDepois) {
                    throw new RuntimeException(
                        'Não foi possível carregar o movimento bancário criado.'
                    );
                }


                $this->auditoria->insercao(
                    $empresaId,
                    $usuarioId,
                    'movimentos_bancarios',
                    $movimentoBancoId,
                    $movimentoDepois
                );


                $bancoDepois =
                    $this->buscarBancoAuditoria(
                        (int)$despesa['banco_id'],
                        $empresaId
                    );


                if (
                    $bancoAntes
                    && $bancoDepois
                ) {

                    $this->auditoria->atualizacao(
                        $empresaId,
                        $usuarioId,
                        'bancos',
                        (int)$despesa['banco_id'],
                        $this->dadosAuditoriaBanco(
                            $bancoAntes
                        ),
                        $this->dadosAuditoriaBanco(
                            $bancoDepois
                        )
                    );
                }
            }


            // ==========================================
            // LOG DE NEGÓCIO
            // ==========================================

            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'DESPESAS',
                'CONTABILIZAR',
                'Despesa contabilizada: #'
                    . $id
                    . ' - '
                    . $despesaDepois['descricao']
                    . ' | Liquidação: '
                    . $tipoLiquidacao
                    . ' | Valor: '
                    . number_format(
                        (float)$despesaDepois['valor_total'],
                        2,
                        '.',
                        ''
                    ),
                $id,
                !empty($despesaDepois['filial_id'])
                    ? (int)$despesaDepois['filial_id']
                    : null
            );

            if ($iniciouTransacao) {
                $this->db->commit();
            }


            return [
                'success' =>
                true,

                'mensagem' =>
                'Despesa contabilizada com sucesso.',

                'lancamento_id' =>
                $lancamentoId
            ];
        } catch (Throwable $e) {

            if (
                $iniciouTransacao
                && $this->db->inTransaction()
            ) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // OBTER BANCO PARA DESPESA
    // ==================================================

    private function obterBancoParaDespesa(
        int $bancoId,
        int $empresaId,
        float $valor
    ): array {

        if ($bancoId <= 0) {

            throw new RuntimeException(
                'Conta bancária inválida.'
            );
        }


        $stmt =
            $this->db->prepare("
            SELECT
                b.id,
                b.empresa_id,
                b.filial_id,
                b.status_id,

                b.nome_banco,
                b.conta,
                b.moeda,

                b.saldo_atual

            FROM bancos b

            WHERE b.id =
                :id

              AND b.empresa_id =
                :empresa_id

            FOR UPDATE
        ");


        $stmt->execute([
            ':id' =>
            $bancoId,

            ':empresa_id' =>
            $empresaId
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


        /*
     * Aceitamos as duas grafias
     * utilizadas no sistema.
     */

        $stmt =
            $this->db->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                'banco'

              AND LOWER(nome) IN (
                    'activo',
                    'ativo',
                    'activa',
                    'ativa'
              )

            ORDER BY id

            LIMIT 1
        ");


        $stmt->execute();


        $statusActivo =
            (int)$stmt
                ->fetchColumn();


        if ($statusActivo <= 0) {

            throw new RuntimeException(
                'Não foi encontrado o status activo para contas bancárias.'
            );
        }


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


        return $banco;
    }

    // ==================================================
    // REGISTAR SAÍDA DO BANCO
    // ==================================================

    private function registarSaidaBancoDespesa(
        array $despesa,
        array $banco,
        float $valor,
        int $usuarioId
    ): int {

        $saldoAnterior =
            round(
                (float)$banco['saldo_atual'],
                2
            );


        $saldoPosterior =
            round(
                $saldoAnterior
                    - $valor,
                2
            );


        if ($saldoPosterior < 0) {

            throw new RuntimeException(
                'Saldo bancário insuficiente.'
            );
        }


        $numeroDocumento =
            !empty($despesa['numero_documento'])
            ? (string)$despesa['numero_documento']
            : 'DESP-'
            . $despesa['id'];


        $referencia =
            'DESPESA-'
            . $despesa['id'];


        $descricao =
            'Pagamento da despesa #'
            . $despesa['id']
            . ' - '
            . $despesa['descricao'];


        // ==============================================
        // MOVIMENTO BANCÁRIO
        // ==============================================

        $stmt =
            $this->db->prepare("
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
                origem_id,

                descricao,
                documento,
                referencia,

                valor,

                saldo_anterior,
                saldo_posterior,

                data_movimento,

                conciliado,
                data_conciliacao,
                conciliado_por,

                observacoes
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

                'DESPESA',
                :origem_id,

                :descricao,
                :documento,
                :referencia,

                :valor,

                :saldo_anterior,
                :saldo_posterior,

                :data_movimento,

                0,
                NULL,
                NULL,

                :observacoes
            )
        ");


        $stmt->execute([
            ':empresa_id' =>
            (int)$despesa['empresa_id'],

            ':filial_id' =>
            !empty($despesa['filial_id'])
                ? (int)$despesa['filial_id']
                : null,

            ':centro_custo_id' =>
            !empty($despesa['centro_custo_id'])
                ? (int)$despesa['centro_custo_id']
                : null,

            ':banco_id' =>
            (int)$banco['id'],

            ':usuario_id' =>
            $usuarioId,

            ':origem_id' =>
            (int)$despesa['id'],

            ':descricao' =>
            $descricao,

            ':documento' =>
            $numeroDocumento,

            ':referencia' =>
            $referencia,

            ':valor' =>
            $valor,

            ':saldo_anterior' =>
            $saldoAnterior,

            ':saldo_posterior' =>
            $saldoPosterior,

            ':data_movimento' =>
            $despesa['data_despesa']
                . ' '
                . date(
                    'H:i:s'
                ),

            ':observacoes' =>
            'Movimento criado automaticamente pelo módulo de Despesas.'
        ]);

        $movimentoId =
            (int)$this->db
                ->lastInsertId();


        if ($movimentoId <= 0) {
            throw new RuntimeException(
                'Não foi possível identificar o movimento bancário da despesa.'
            );
        }

        // ==============================================
        // ATUALIZAR BANCO
        // ==============================================

        $stmt =
            $this->db->prepare("
            UPDATE bancos

            SET saldo_atual =
                :saldo_posterior

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");


        $stmt->execute([
            ':saldo_posterior' =>
            $saldoPosterior,

            ':id' =>
            (int)$banco['id'],

            ':empresa_id' =>
            (int)$despesa['empresa_id']
        ]);


        if (
            $stmt->rowCount()
            !== 1
        ) {

            throw new RuntimeException(
                'Não foi possível atualizar o saldo da conta bancária.'
            );
        }

        return $movimentoId;
    }

    // ==================================================
    // OBTER CAIXA PARA DESPESA
    // ==================================================

    private function obterCaixaParaDespesa(
        int $caixaId,
        int $empresaId,
        float $valor
    ): array {

        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }


        $stmt =
            $this->db->prepare("
            SELECT
                c.id,
                c.empresa_id,
                c.filial_id,
                c.status_id,
                c.nome,
                c.codigo,
                c.saldo_atual,

                (
                    SELECT ac.id

                    FROM abertura_caixa ac

                    WHERE ac.caixa_id =
                        c.id

                      AND ac.empresa_id =
                        c.empresa_id

                      AND ac.data_fechamento
                        IS NULL

                    ORDER BY
                        ac.id DESC

                    LIMIT 1

                ) AS abertura_id

            FROM caixas c

            WHERE c.id =
                :id

              AND c.empresa_id =
                :empresa_id

            FOR UPDATE
        ");


        $stmt->execute([
            ':id' =>
            $caixaId,

            ':empresa_id' =>
            $empresaId
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


        if (
            empty($caixa['abertura_id'])
        ) {
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


        return $caixa;
    }

    // ==================================================
    // REGISTAR SAÍDA DO CAIXA
    // ==================================================

    private function registarSaidaCaixaDespesa(
        array $despesa,
        array $caixa,
        float $valor,
        int $usuarioId
    ): int {

        $descricao =
            'Pagamento da despesa #'
            . $despesa['id']
            . ' - '
            . $despesa['descricao'];


        $stmt =
            $this->db->prepare("
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
                'DESPESA',
                :documento_id,

                :descricao,
                :valor,
                :data_movimento
            )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $despesa['empresa_id'],

            ':filial_id' =>
            !empty($despesa['filial_id'])
                ? (int)$despesa['filial_id']
                : null,

            ':centro_custo_id' =>
            !empty($despesa['centro_custo_id'])
                ? (int)$despesa['centro_custo_id']
                : null,

            ':caixa_id' =>
            (int)$caixa['id'],

            ':abertura_id' =>
            (int)$caixa['abertura_id'],

            ':usuario_id' =>
            $usuarioId,

            /*
         * Agora documento_id aponta
         * diretamente para despesas.id.
         */
            ':documento_id' =>
            (int)$despesa['id'],

            ':descricao' =>
            $descricao,

            ':valor' =>
            $valor,

            ':data_movimento' =>
            $despesa['data_despesa']
                . ' '
                . date('H:i:s')
        ]);

        $movimentoId =
            (int)$this->db
                ->lastInsertId();


        if ($movimentoId <= 0) {
            throw new RuntimeException(
                'Não foi possível identificar o movimento de caixa da despesa.'
            );
        }

        $stmt =
            $this->db->prepare("
            UPDATE caixas

            SET saldo_atual =
                saldo_atual - :valor

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");


        $stmt->execute([
            ':valor' =>
            $valor,

            ':id' =>
            (int)$caixa['id'],

            ':empresa_id' =>
            (int)$despesa['empresa_id']
        ]);


        if (
            $stmt->rowCount()
            !== 1
        ) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo do caixa.'
            );
        }

        return $movimentoId;
    }

    // ==================================================
    // CRIAR LANÇAMENTO DA DESPESA
    // ==================================================

    private function criarLancamentoDespesa(
        ContabilidadeService $contabilidade,
        array $despesa,
        array $contexto,
        array $diario,
        int $contaContrapartidaId,
        int $usuarioId,
        ?int $fornecedorId = null
    ): int {

        // ==============================================
        // VALORES
        // ==============================================

        $valorBase =
            round(
                (float)$despesa['valor_base'],
                2
            );


        $impostoValor =
            round(
                (float)$despesa['imposto_valor'],
                2
            );


        $valorTotal =
            round(
                (float)$despesa['valor_total'],
                2
            );


        if ($valorBase <= 0) {

            throw new RuntimeException(
                'Valor base inválido para contabilização.'
            );
        }


        if ($impostoValor < 0) {

            throw new RuntimeException(
                'Valor do imposto inválido.'
            );
        }


        if (
            abs(
                (
                    $valorBase
                    + $impostoValor
                )
                    - $valorTotal
            ) > 0.01
        ) {

            throw new RuntimeException(
                'O total da despesa não confere com o valor base e imposto.'
            );
        }


        // ==============================================
        // DOCUMENTO
        // ==============================================

        $documento =
            !empty($despesa['numero_documento'])
            ? (string)$despesa['numero_documento']
            : 'DESP-'
            . $despesa['id'];


        // ==============================================
        // ITENS DO LANÇAMENTO
        // ==============================================

        $itens = [];


        // ----------------------------------------------
        // DÉBITO - GASTO
        // ----------------------------------------------

        $itens[] = [

            'conta_contabil_id' =>
            (int)$despesa['conta_gasto_id'],

            'centro_custo_id' =>
            !empty($despesa['centro_custo_id'])
                ? (int)$despesa['centro_custo_id']
                : null,

            'cliente_id' =>
            null,

            'fornecedor_id' =>
            $fornecedorId,

            'descricao' =>
            (string)$despesa['descricao'],

            'debito' =>
            $valorBase,

            'credito' =>
            0
        ];


        // ----------------------------------------------
        // DÉBITO - IVA DEDUTÍVEL
        // ----------------------------------------------

        if ($impostoValor > 0) {

            $contaIva =
                $contabilidade
                ->contaIntegracao(
                    (int)$despesa['empresa_id'],
                    'IVA_DEDUTIVEL_DESPESAS'
                );


            if (
                empty($contaIva['id'])
            ) {

                throw new RuntimeException(
                    'A conta IVA dedutível para despesas não está configurada.'
                );
            }


            $itens[] = [

                'conta_contabil_id' =>
                (int)$contaIva['id'],

                /*
             * IVA não é gasto do centro de custo.
             */
                'centro_custo_id' =>
                null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                $fornecedorId,

                'descricao' =>
                'IVA dedutível - '
                    . $despesa['descricao'],

                'debito' =>
                $impostoValor,

                'credito' =>
                0
            ];
        }


        // ----------------------------------------------
        // CRÉDITO - CAIXA / BANCO / FORNECEDORES
        // ----------------------------------------------

        $itens[] = [

            'conta_contabil_id' =>
            $contaContrapartidaId,

            'centro_custo_id' =>
            null,

            'cliente_id' =>
            null,

            'fornecedor_id' =>
            $fornecedorId,

            'descricao' =>
            'Contrapartida da despesa #'
                . $despesa['id'],

            'debito' =>
            0,

            'credito' =>
            $valorTotal
        ];


        // ==============================================
        // CRIAR LANÇAMENTO
        // ==============================================

        return $contabilidade
            ->criarLancamento(
                [
                    'empresa_id' =>
                    (int)$despesa['empresa_id'],

                    'filial_id' =>
                    !empty($despesa['filial_id'])
                        ? (int)$despesa['filial_id']
                        : null,

                    'exercicio_id' =>
                    (int)$contexto['exercicio_id'],

                    'periodo_id' =>
                    (int)$contexto['periodo_id'],

                    'diario_id' =>
                    (int)$diario['id'],

                    'numero_documento' =>
                    $documento,

                    'referencia' =>
                    'DESPESA-'
                        . $despesa['id'],

                    'data_lancamento' =>
                    $despesa['data_despesa'],

                    'data_documento' =>
                    $despesa['data_despesa'],

                    'descricao' =>
                    $despesa['descricao'],

                    'origem' =>
                    'DESPESA',

                    'origem_id' =>
                    (int)$despesa['id'],

                    'usuario_id' =>
                    $usuarioId
                ],

                $itens
            );
    }

    // ==================================================
    // CRIAR CONTA A PAGAR DA DESPESA
    // ==================================================

    private function criarContaPagarDespesa(
        array $despesa,
        int $usuarioId
    ): int {

        if (
            empty($despesa['fornecedor_id'])
        ) {

            throw new RuntimeException(
                'Fornecedor inválido para criação da conta a pagar.'
            );
        }


        if (
            empty($despesa['centro_custo_id'])
        ) {

            throw new RuntimeException(
                'Centro de custo obrigatório para criação da conta a pagar.'
            );
        }


        if (
            empty($despesa['data_vencimento'])
        ) {

            throw new RuntimeException(
                'Data de vencimento obrigatória.'
            );
        }


        $valor =
            round(
                (float)$despesa['valor_total'],
                2
            );


        $numeroDocumento =
            !empty($despesa['numero_documento'])
            ? (string)$despesa['numero_documento']
            : 'DESP-'
            . $despesa['id'];


        return ContaPagar::criar([
            'empresa_id' =>
            (int)$despesa['empresa_id'],

            'filial_id' =>
            !empty($despesa['filial_id'])
                ? (int)$despesa['filial_id']
                : null,

            'centro_custo_id' =>
            (int)$despesa['centro_custo_id'],

            'fornecedor_id' =>
            (int)$despesa['fornecedor_id'],

            'numero_documento' =>
            $numeroDocumento,

            'referencia' =>
            'DESPESA-'
                . $despesa['id'],

            'descricao' =>
            'Despesa a pagar - '
                . $despesa['descricao'],

            'valor' =>
            $valor,

            'desconto' =>
            0,

            'juros' =>
            0,

            'multa' =>
            0,

            'data_emissao' =>
            $despesa['data_despesa'],

            'data_vencimento' =>
            $despesa['data_vencimento'],

            'observacoes' =>
            'Conta criada automaticamente pela despesa #'
                . $despesa['id'],

            'usuario_id' =>
            $usuarioId
        ]);
    }

    private function statusPorTipoNome(
        string $tipo,
        string $nome
    ): int {

        $stmt =
            $this->db->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                :tipo

              AND LOWER(nome) =
                LOWER(:nome)

            LIMIT 1
        ");


        $stmt->execute([
            ':tipo' =>
            $tipo,

            ':nome' =>
            $nome
        ]);


        $id =
            (int)$stmt
                ->fetchColumn();


        if ($id <= 0) {
            throw new RuntimeException(
                "Status '{$nome}' do tipo '{$tipo}' não encontrado."
            );
        }


        return $id;
    }


    // ==================================================
    // NORMALIZAR
    // ==================================================

    private function normalizarDados(
        array $dados
    ): array {

        $valorBase =
            round(
                (float)(
                    $dados['valor_base']
                    ?? 0
                ),
                2
            );


        $impostoPercentual =
            round(
                (float)(
                    $dados['imposto_percentual']
                    ?? 0
                ),
                4
            );


        if (
            $impostoPercentual < 0
            || $impostoPercentual > 100
        ) {

            throw new InvalidArgumentException(
                'A percentagem do imposto deve estar entre 0% e 100%.'
            );
        }


        $imposto =
            round(
                $valorBase
                    * $impostoPercentual
                    / 100,
                2
            );


        return [
            'id' =>
            (int)(
                $dados['id']
                ?? 0
            ),

            'filial_id' =>
            $this->inteiroOuNull(
                $dados['filial_id']
                    ?? null
            ),

            'centro_custo_id' =>
            $this->inteiroOuNull(
                $dados['centro_custo_id']
                    ?? null
            ),

            'fornecedor_id' =>
            $this->inteiroOuNull(
                $dados['fornecedor_id']
                    ?? null
            ),

            'conta_gasto_id' =>
            (int)(
                $dados['conta_gasto_id']
                ?? 0
            ),

            'forma_pagamento_id' => null,

            'caixa_id' =>
            $this->inteiroOuNull(
                $dados['caixa_id']
                    ?? null
            ),

            'banco_id' =>
            $this->inteiroOuNull(
                $dados['banco_id']
                    ?? null
            ),

            'numero_documento' =>
            $this->textoOuNull(
                $dados['numero_documento']
                    ?? null
            ),

            'referencia' =>
            $this->textoOuNull(
                $dados['referencia']
                    ?? null
            ),

            'descricao' =>
            trim(
                (string)(
                    $dados['descricao']
                    ?? ''
                )
            ),

            'valor_base' =>
            $valorBase,

            'imposto_valor' =>
            $imposto,

            'valor_total' =>
            round(
                $valorBase
                    + $imposto,
                2
            ),

            'data_despesa' =>
            trim(
                (string)(
                    $dados['data_despesa']
                    ?? ''
                )
            ),

            'data_vencimento' =>
            $this->textoOuNull(
                $dados['data_vencimento']
                    ?? null
            ),

            'tipo_liquidacao' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_liquidacao']
                        ?? ''
                    )
                )
            ),

            'observacoes' =>
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            )
        ];
    }

    // ==================================================
    // FILIAL DO CENTRO DE CUSTO
    // ==================================================

    private function filialCentroCusto(
        int $empresaId,
        ?int $centroCustoId
    ): ?int {

        if (
            $centroCustoId === null
            || $centroCustoId <= 0
        ) {
            return null;
        }

        $stmt =
            $this->db->prepare("
            SELECT filial_id

            FROM centros_custo

            WHERE id =
                :centro_custo_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':centro_custo_id' =>
            $centroCustoId,

            ':empresa_id' =>
            $empresaId
        ]);

        $filialId =
            $stmt->fetchColumn();

        return (
            $filialId !== false
            && $filialId !== null
            && (int)$filialId > 0
        )
            ? (int)$filialId
            : null;
    }

    // ==================================================
    // VALIDAR
    // ==================================================

    private function validarDados(
        int $empresaId,
        array $dados
    ): void {

        if (
            $dados['descricao']
            === ''
        ) {

            throw new RuntimeException(
                'Informe a descrição da despesa.'
            );
        }


        if (
            $dados['valor_base']
            <= 0
        ) {

            throw new RuntimeException(
                'O valor da despesa deve ser superior a zero.'
            );
        }


        if (
            $dados['imposto_valor']
            < 0
        ) {

            throw new RuntimeException(
                'O valor do imposto não pode ser negativo.'
            );
        }


        $this->validarData(
            $dados['data_despesa'],
            'Data da despesa'
        );


        if (
            $dados['data_vencimento']
            !== null
        ) {

            $this->validarData(
                $dados['data_vencimento'],
                'Data de vencimento'
            );
        }


        if (
            !in_array(
                $dados['tipo_liquidacao'],
                [
                    'CAIXA',
                    'BANCO',
                    'CONTA_PAGAR'
                ],
                true
            )
        ) {

            throw new RuntimeException(
                'Tipo de liquidação inválido.'
            );
        }


        $this->validarContaGasto(
            $empresaId,
            $dados['conta_gasto_id']
        );


        if (
            $dados['tipo_liquidacao']
            === 'CAIXA'
            && $dados['caixa_id']
            === null
        ) {

            throw new RuntimeException(
                'Selecione o caixa da despesa.'
            );
        }


        if (
            $dados['tipo_liquidacao']
            === 'BANCO'
            && $dados['banco_id']
            === null
        ) {

            throw new RuntimeException(
                'Selecione a conta bancária da despesa.'
            );
        }


        if (
            $dados['tipo_liquidacao']
            === 'CONTA_PAGAR'
            && $dados['fornecedor_id']
            === null
        ) {

            throw new RuntimeException(
                'Selecione o fornecedor para a despesa a prazo.'
            );
        }
    }


    // ==================================================
    // VALIDAR CONTA DE GASTO
    // ==================================================

    private function validarContaGasto(
        int $empresaId,
        int $contaId
    ): void {

        if ($contaId <= 0) {

            throw new RuntimeException(
                'Selecione a conta contabilística da despesa.'
            );
        }


        $stmt =
            $this->db->prepare("
                SELECT COUNT(*)

                FROM contas_contabilisticas

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                  AND grupo =
                    'GASTO'

                  AND tipo =
                    'ANALITICA'

                  AND aceita_lancamento =
                    1
            ");


        $stmt->execute([
            ':id' =>
            $contaId,

            ':empresa_id' =>
            $empresaId
        ]);


        if (
            (int)$stmt->fetchColumn()
            <= 0
        ) {

            throw new RuntimeException(
                'A conta selecionada não é uma conta analítica válida de gasto.'
            );
        }
    }


    // ==================================================
    // CONTAS DE GASTO
    // ==================================================

    private function listarContasGasto(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                id,
                plano_contas_id,
                codigo,
                nome,
                exige_centro_custo,

                CASE

                    WHEN codigo LIKE '6.1%'
                        THEN '6.1 - Custo dos Inventários'

                    WHEN codigo LIKE '6.2%'
                        THEN '6.2 - Gastos com o Pessoal'

                    WHEN codigo LIKE '6.3%'
                        THEN '6.3 - Fornecimentos e Serviços'

                    WHEN codigo LIKE '6.4%'
                        THEN '6.4 - Perdas por Imparidade'

                    WHEN codigo LIKE '6.5%'
                        THEN '6.5 - Amortizações do Período'

                    WHEN codigo LIKE '6.6%'
                        THEN '6.6 - Provisões do Período'

                    WHEN codigo LIKE '6.7%'
                        THEN '6.7 - Perdas por Redução do Justo Valor'

                    WHEN codigo LIKE '6.8%'
                        THEN '6.8 - Outros Gastos e Perdas Operacionais'

                    WHEN codigo LIKE '6.9%'
                        THEN '6.9 - Gastos e Perdas Financeiros'

                    ELSE 'Outros Gastos'

                END AS classe_gasto

                FROM contas_contabilisticas

                WHERE empresa_id =
                    :empresa_id

                  AND grupo =
                    'GASTO'

                  AND tipo =
                    'ANALITICA'

                  AND aceita_lancamento =
                    1

                ORDER BY
                    codigo ASC
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
    // FORNECEDORES
    // ==================================================

    private function listarFornecedores(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    nome,
                    nuit

                FROM fornecedores

                WHERE empresa_id =
                    :empresa_id

                ORDER BY
                    nome ASC
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
    // CENTROS DE CUSTO
    // ==================================================

    private function listarCentrosCusto(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    filial_id,
                    codigo,
                    nome,
                    tipo

                FROM centros_custo

                WHERE empresa_id =
                    :empresa_id

                ORDER BY
                    codigo ASC
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
    // CAIXAS
    // ==================================================

    private function listarCaixas(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    filial_id,
                    codigo,
                    nome,
                    saldo_atual

                FROM caixas

                WHERE empresa_id =
                    :empresa_id

                ORDER BY
                    nome ASC
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
    // BANCOS
    // ==================================================

    private function listarBancos(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    filial_id,
                    nome_banco,
                    conta,
                    moeda,
                    saldo_atual

                FROM bancos

                WHERE empresa_id =
                    :empresa_id

                ORDER BY
                    nome_banco ASC,
                    conta ASC
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
    // STATUS
    // ==================================================

    private function listarStatus(): array
    {
        $stmt =
            $this->db->prepare("
                SELECT
                    id,
                    nome,
                    descricao

                FROM status

                WHERE tipo =
                    'despesa'

                ORDER BY
                    id ASC
            ");


        $stmt->execute();


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    private function statusId(
        string $nome
    ): int {

        $stmt =
            $this->db->prepare("
                SELECT id

                FROM status

                WHERE tipo =
                    'despesa'

                  AND LOWER(nome) =
                    LOWER(:nome)

                LIMIT 1
            ");


        $stmt->execute([
            ':nome' =>
            $nome
        ]);


        $id =
            (int)$stmt
                ->fetchColumn();


        if ($id <= 0) {

            throw new RuntimeException(
                "Status de despesa '{$nome}' não encontrado."
            );
        }


        return $id;
    }


    // ==================================================
    // HELPERS
    // ==================================================

    // ==================================================
    // AUDITORIA - DESPESA
    // ==================================================

    private function buscarDespesaAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT *
            FROM despesas
            WHERE id = :id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }


    private function dadosAuditoriaDespesa(
        array $despesa
    ): array {

        return [
            'id' =>
            isset($despesa['id'])
                ? (int)$despesa['id']
                : null,

            'empresa_id' =>
            isset($despesa['empresa_id'])
                ? (int)$despesa['empresa_id']
                : null,

            'filial_id' =>
            !empty($despesa['filial_id'])
                ? (int)$despesa['filial_id']
                : null,

            'centro_custo_id' =>
            !empty($despesa['centro_custo_id'])
                ? (int)$despesa['centro_custo_id']
                : null,

            'fornecedor_id' =>
            !empty($despesa['fornecedor_id'])
                ? (int)$despesa['fornecedor_id']
                : null,

            'conta_gasto_id' =>
            !empty($despesa['conta_gasto_id'])
                ? (int)$despesa['conta_gasto_id']
                : null,

            'caixa_id' =>
            !empty($despesa['caixa_id'])
                ? (int)$despesa['caixa_id']
                : null,

            'banco_id' =>
            !empty($despesa['banco_id'])
                ? (int)$despesa['banco_id']
                : null,

            'conta_pagar_id' =>
            !empty($despesa['conta_pagar_id'])
                ? (int)$despesa['conta_pagar_id']
                : null,

            'status_id' =>
            isset($despesa['status_id'])
                ? (int)$despesa['status_id']
                : null,

            'numero_documento' =>
            $despesa['numero_documento']
                ?? null,

            'referencia' =>
            $despesa['referencia']
                ?? null,

            'descricao' =>
            $despesa['descricao']
                ?? null,

            'valor_base' =>
            isset($despesa['valor_base'])
                ? (float)$despesa['valor_base']
                : 0.0,

            'imposto_valor' =>
            isset($despesa['imposto_valor'])
                ? (float)$despesa['imposto_valor']
                : 0.0,

            'valor_total' =>
            isset($despesa['valor_total'])
                ? (float)$despesa['valor_total']
                : 0.0,

            'data_despesa' =>
            $despesa['data_despesa']
                ?? null,

            'data_vencimento' =>
            $despesa['data_vencimento']
                ?? null,

            'data_pagamento' =>
            $despesa['data_pagamento']
                ?? null,

            'tipo_liquidacao' =>
            $despesa['tipo_liquidacao']
                ?? null,

            'observacoes' =>
            $despesa['observacoes']
                ?? null
        ];
    }


    // ==================================================
    // AUDITORIA - CAIXA
    // ==================================================

    private function buscarCaixaAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT *
            FROM caixas
            WHERE id = :id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }


    private function dadosAuditoriaCaixa(
        array $caixa
    ): array {

        return [
            'id' =>
            isset($caixa['id'])
                ? (int)$caixa['id']
                : null,

            'empresa_id' =>
            isset($caixa['empresa_id'])
                ? (int)$caixa['empresa_id']
                : null,

            'filial_id' =>
            !empty($caixa['filial_id'])
                ? (int)$caixa['filial_id']
                : null,

            'status_id' =>
            isset($caixa['status_id'])
                ? (int)$caixa['status_id']
                : null,

            'codigo' =>
            $caixa['codigo']
                ?? null,

            'nome' =>
            $caixa['nome']
                ?? null,

            'saldo_inicial' =>
            isset($caixa['saldo_inicial'])
                ? (float)$caixa['saldo_inicial']
                : null,

            'saldo_atual' =>
            isset($caixa['saldo_atual'])
                ? (float)$caixa['saldo_atual']
                : 0.0
        ];
    }


    private function buscarMovimentoCaixaAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT *
            FROM movimentos_caixa
            WHERE id = :id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }


    // ==================================================
    // AUDITORIA - BANCO
    // ==================================================

    private function buscarBancoAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT *
            FROM bancos
            WHERE id = :id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }


    private function dadosAuditoriaBanco(
        array $banco
    ): array {

        return [
            'id' =>
            isset($banco['id'])
                ? (int)$banco['id']
                : null,

            'empresa_id' =>
            isset($banco['empresa_id'])
                ? (int)$banco['empresa_id']
                : null,

            'filial_id' =>
            !empty($banco['filial_id'])
                ? (int)$banco['filial_id']
                : null,

            'status_id' =>
            isset($banco['status_id'])
                ? (int)$banco['status_id']
                : null,

            'nome_banco' =>
            $banco['nome_banco']
                ?? null,

            'conta' =>
            $banco['conta']
                ?? null,

            'moeda' =>
            $banco['moeda']
                ?? null,

            'saldo_inicial' =>
            isset($banco['saldo_inicial'])
                ? (float)$banco['saldo_inicial']
                : null,

            'saldo_atual' =>
            isset($banco['saldo_atual'])
                ? (float)$banco['saldo_atual']
                : 0.0
        ];
    }


    private function buscarMovimentoBancoAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT *
            FROM movimentos_bancarios
            WHERE id = :id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }

    private function inteiroOuNull(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || $valor === ''
        ) {

            return null;
        }


        $id =
            (int)$valor;


        return
            $id > 0
            ? $id
            : null;
    }


    private function textoOuNull(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)(
                    $valor
                    ?? ''
                )
            );


        return
            $texto !== ''
            ? $texto
            : null;
    }


    private function validarData(
        string $data,
        string $campo
    ): void {

        $objeto =
            DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data
            );


        if (
            !$objeto
            || $objeto->format(
                'Y-m-d'
            ) !== $data
        ) {

            throw new RuntimeException(
                "{$campo} inválida."
            );
        }
    }
}
