<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/ContabilidadeService.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class ContaPagar
{
    // ==================================================
    // LISTAR CONTAS
    // ==================================================

    public static function listar(
        int $empresaId,
        ?int $filialId = null
    ): array {

        self::validarEmpresa(
            $empresaId
        );

        $pdo = Database::conn();

        $sql = "
            SELECT
                cp.id,
                cp.empresa_id,
                cp.filial_id,
                cp.centro_custo_id,
                cp.fornecedor_id,
                cp.forma_pagamento_id,
                cp.status_id,

                cp.numero_documento,
                cp.referencia,
                cp.descricao,

                cp.valor,
                cp.desconto,
                cp.juros,
                cp.multa,
                cp.valor_pago,
                cp.saldo,

                cp.data_emissao,
                cp.data_vencimento,
                cp.data_pagamento,

                cp.observacoes,
                cp.criado_em,
                cp.atualizado_em,

                fornecedor.nome AS fornecedor,

                forma.nome AS forma_pagamento,

                status.nome AS status,

                COALESCE(
                    filial.nome,
                    'Sede Principal / Sem filial'
                ) AS filial,

                COALESCE(
                    centro.nome,
                    'Sem centro de custo'
                ) AS centro_custo,

                centro.codigo AS centro_custo_codigo,

                (
                    cp.valor
                    + COALESCE(cp.juros, 0)
                    + COALESCE(cp.multa, 0)
                    - COALESCE(cp.desconto, 0)
                    - COALESCE(cp.valor_pago, 0)
                ) AS saldo_calculado

            FROM contas_pagar cp

            INNER JOIN fornecedores fornecedor
                ON fornecedor.id = cp.fornecedor_id

            LEFT JOIN formas_pagamento forma
                ON forma.id = cp.forma_pagamento_id

            INNER JOIN status
                ON status.id = cp.status_id

            LEFT JOIN filiais filial
                ON filial.id = cp.filial_id
                AND filial.empresa_id = cp.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id = cp.centro_custo_id
                AND centro.empresa_id = cp.empresa_id

            WHERE cp.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND cp.filial_id = :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $sql .= "
            ORDER BY
                cp.data_vencimento ASC,
                cp.id DESC
        ";

        $stmt = $pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        $dados = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

        foreach ($dados as &$conta) {
            $saldo = max(
                0,
                (float)$conta['saldo_calculado']
            );

            $conta['saldo'] =
                round($saldo, 2);

            /*
             * O status visual é calculado para mostrar
             * vencimento mesmo que o banco ainda não tenha
             * actualizado o status_id.
             */

            $statusBanco =
                mb_strtolower(
                    trim(
                        (string)$conta['status']
                    )
                );


            if (
                $statusBanco === 'cancelado'
            ) {

                $conta['status'] =
                    'Cancelado';
            } elseif (
                $saldo <= 0
            ) {

                $conta['status'] =
                    'Pago';
            } elseif (
                (float)$conta['valor_pago'] > 0
            ) {

                $conta['status'] =
                    'Parcial';
            } elseif (
                $conta['data_vencimento']
                < date('Y-m-d')
            ) {

                $conta['status'] =
                    'Vencido';
            } else {

                $conta['status'] =
                    'Pendente';
            }
        }

        unset($conta);

        return $dados;
    }

    // ==================================================
    // BUSCAR POR ID
    // ==================================================

    public static function buscarPorId(
        int $id,
        int $empresaId
    ): ?array {

        self::validarEmpresa(
            $empresaId
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Conta a pagar inválida.'
            );
        }

        $pdo = Database::conn();

        $stmt = $pdo->prepare("
            SELECT
                cp.*,

                fornecedor.nome AS fornecedor,

                forma.nome AS forma_pagamento,

                status.nome AS status,

                COALESCE(
                    filial.nome,
                    'Sede Principal / Sem filial'
                ) AS filial,

                COALESCE(
                    centro.nome,
                    'Sem centro de custo'
                ) AS centro_custo,

                centro.codigo AS centro_custo_codigo

            FROM contas_pagar cp

            INNER JOIN fornecedores fornecedor
                ON fornecedor.id = cp.fornecedor_id

            LEFT JOIN formas_pagamento forma
                ON forma.id = cp.forma_pagamento_id

            INNER JOIN status
                ON status.id = cp.status_id

            LEFT JOIN filiais filial
                ON filial.id = cp.filial_id
                AND filial.empresa_id = cp.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id = cp.centro_custo_id
                AND centro.empresa_id = cp.empresa_id

            WHERE cp.id = :id
              AND cp.empresa_id = :empresa_id

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
    // CRIAR
    // ==================================================

    public static function criar(
        array $dados
    ): int {

        $dados =
            self::normalizarDados(
                $dados
            );


        self::validarDadosConta(
            $dados
        );


        $empresaId =
            (int)$dados['empresa_id'];

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        $pdo = Database::conn();


        self::validarFornecedor(
            $pdo,
            $dados['fornecedor_id'],
            $empresaId
        );


        self::validarFilial(
            $pdo,
            $dados['filial_id'],
            $empresaId
        );


        self::validarCentroCusto(
            $pdo,
            $dados['centro_custo_id'],
            $empresaId,
            $dados['filial_id']
        );


        self::validarFormaPagamento(
            $pdo,
            $dados['forma_pagamento_id']
        );


        $saldo =
            self::calcularTotalConta(
                $dados['valor'],
                $dados['desconto'],
                $dados['juros'],
                $dados['multa']
            );


        $statusId =
            self::statusContaPagar(
                $pdo,
                'Pendente'
            );


        $auditoria =
            new AuditoriaService(
                $pdo
            );


        $log =
            new LogService(
                $pdo
            );


        $iniciouTransacao =
            !$pdo->inTransaction();

        if ($iniciouTransacao) {
            $pdo->beginTransaction();
        }

        try {

            $stmt =
                $pdo->prepare("
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
                    :desconto,
                    :juros,
                    :multa,

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
                $empresaId,

                ':filial_id' =>
                $dados['filial_id'],

                ':centro_custo_id' =>
                $dados['centro_custo_id'],

                ':fornecedor_id' =>
                $dados['fornecedor_id'],

                ':forma_pagamento_id' =>
                $dados['forma_pagamento_id'],

                ':status_id' =>
                $statusId,

                ':numero_documento' =>
                $dados['numero_documento'],

                ':referencia' =>
                $dados['referencia'],

                ':descricao' =>
                $dados['descricao'],

                ':valor' =>
                $dados['valor'],

                ':desconto' =>
                $dados['desconto'],

                ':juros' =>
                $dados['juros'],

                ':multa' =>
                $dados['multa'],

                ':saldo' =>
                $saldo,

                ':data_emissao' =>
                $dados['data_emissao'],

                ':data_vencimento' =>
                $dados['data_vencimento'],

                ':observacoes' =>
                $dados['observacoes'],

                ':criado_por' =>
                $usuarioId
            ]);


            $id =
                (int)$pdo
                    ->lastInsertId();


            if ($id <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar a conta a pagar criada.'
                );
            }


            $novaConta =
                self::buscarContaAuditoria(
                    $pdo,
                    $id,
                    $empresaId
                );


            if (!$novaConta) {
                throw new RuntimeException(
                    'Não foi possível carregar a conta criada para auditoria.'
                );
            }


            $auditoria->insercao(
                $empresaId,
                $usuarioId,
                'contas_pagar',
                $id,
                self::dadosAuditoriaContaPagar(
                    $novaConta
                )
            );


            $log->registrar(
                $empresaId,
                $usuarioId,
                'CONTAS_PAGAR',
                'CRIAR',
                'Conta a pagar criada'
                    . (
                        !empty($novaConta['numero_documento'])
                        ? ': '
                        . $novaConta['numero_documento']
                        : ' #' . $id
                    ),
                $id,
                !empty($novaConta['filial_id'])
                    ? (int)$novaConta['filial_id']
                    : null
            );


            if (
                $iniciouTransacao
                && $pdo->inTransaction()
            ) {
                $pdo->commit();
            }

            return $id;
        } catch (Throwable $e) {

            if (
                $iniciouTransacao
                && $pdo->inTransaction()
            ) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // ATUALIZAR
    // ==================================================

    public static function atualizar(
        array $dados
    ): bool {

        $dados =
            self::normalizarDados(
                $dados
            );


        $id =
            (int)($dados['id'] ?? 0);


        if ($id <= 0) {
            throw new RuntimeException(
                'Conta a pagar inválida.'
            );
        }


        $empresaId =
            (int)$dados['empresa_id'];

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        self::validarDadosConta(
            $dados
        );


        $pdo =
            Database::conn();


        $auditoria =
            new AuditoriaService(
                $pdo
            );


        $log =
            new LogService(
                $pdo
            );


        $pdo->beginTransaction();

        try {

            /*
         * Bloquear conta.
         */
            $stmt =
                $pdo->prepare("
                SELECT *

                FROM contas_pagar

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


            $contaAtual =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$contaAtual) {
                throw new RuntimeException(
                    'Conta a pagar não encontrada.'
                );
            }


            $dadosAnteriores =
                self::dadosAuditoriaContaPagar(
                    $contaAtual
                );


            self::validarFornecedor(
                $pdo,
                $dados['fornecedor_id'],
                $empresaId
            );


            self::validarFilial(
                $pdo,
                $dados['filial_id'],
                $empresaId
            );


            self::validarCentroCusto(
                $pdo,
                $dados['centro_custo_id'],
                $empresaId,
                $dados['filial_id']
            );


            self::validarFormaPagamento(
                $pdo,
                $dados['forma_pagamento_id']
            );


            $totalConta =
                self::calcularTotalConta(
                    $dados['valor'],
                    $dados['desconto'],
                    $dados['juros'],
                    $dados['multa']
                );


            $valorPago =
                (float)$contaAtual['valor_pago'];


            if ($totalConta < $valorPago) {
                throw new RuntimeException(
                    'O novo total da conta não pode ser inferior ao valor já pago.'
                );
            }


            $saldo =
                round(
                    $totalConta
                        - $valorPago,
                    2
                );


            if ($saldo <= 0) {

                $statusId =
                    self::statusContaPagar(
                        $pdo,
                        'Pago'
                    );
            } elseif ($valorPago > 0) {

                $statusId =
                    self::statusContaPagar(
                        $pdo,
                        'Parcial'
                    );
            } elseif (
                $dados['data_vencimento']
                < date('Y-m-d')
            ) {

                $statusId =
                    self::statusContaPagar(
                        $pdo,
                        'Vencido'
                    );
            } else {

                $statusId =
                    self::statusContaPagar(
                        $pdo,
                        'Pendente'
                    );
            }


            $stmt =
                $pdo->prepare("
                UPDATE contas_pagar

                SET
                    filial_id =
                        :filial_id,

                    centro_custo_id =
                        :centro_custo_id,

                    fornecedor_id =
                        :fornecedor_id,

                    forma_pagamento_id =
                        :forma_pagamento_id,

                    status_id =
                        :status_id,

                    numero_documento =
                        :numero_documento,

                    referencia =
                        :referencia,

                    descricao =
                        :descricao,

                    valor =
                        :valor,

                    desconto =
                        :desconto,

                    juros =
                        :juros,

                    multa =
                        :multa,

                    saldo =
                        :saldo,

                    data_emissao =
                        :data_emissao,

                    data_vencimento =
                        :data_vencimento,

                    observacoes =
                        :observacoes,

                    atualizado_por =
                        :atualizado_por

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

                ':status_id' =>
                $statusId,

                ':numero_documento' =>
                $dados['numero_documento'],

                ':referencia' =>
                $dados['referencia'],

                ':descricao' =>
                $dados['descricao'],

                ':valor' =>
                $dados['valor'],

                ':desconto' =>
                $dados['desconto'],

                ':juros' =>
                $dados['juros'],

                ':multa' =>
                $dados['multa'],

                ':saldo' =>
                $saldo,

                ':data_emissao' =>
                $dados['data_emissao'],

                ':data_vencimento' =>
                $dados['data_vencimento'],

                ':observacoes' =>
                $dados['observacoes'],

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            $dadosNovos =
                self::buscarContaAuditoria(
                    $pdo,
                    $id,
                    $empresaId
                );


            if (!$dadosNovos) {
                throw new RuntimeException(
                    'Não foi possível carregar os novos dados da conta.'
                );
            }


            $auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'contas_pagar',
                $id,
                $dadosAnteriores,
                self::dadosAuditoriaContaPagar(
                    $dadosNovos
                )
            );


            $log->registrar(
                $empresaId,
                $usuarioId,
                'CONTAS_PAGAR',
                'EDITAR',
                'Conta a pagar atualizada'
                    . (
                        !empty($dadosNovos['numero_documento'])
                        ? ': '
                        . $dadosNovos['numero_documento']
                        : ' #' . $id
                    ),
                $id,
                !empty($dadosNovos['filial_id'])
                    ? (int)$dadosNovos['filial_id']
                    : null
            );


            $pdo->commit();

            return true;
        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public static function eliminar(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        self::validarEmpresa(
            $empresaId
        );


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        if ($id <= 0) {
            throw new RuntimeException(
                'Conta a pagar inválida.'
            );
        }


        $pdo =
            Database::conn();


        $auditoria =
            new AuditoriaService(
                $pdo
            );


        $log =
            new LogService(
                $pdo
            );


        $pdo->beginTransaction();

        try {

            $stmt =
                $pdo->prepare("
                SELECT *

                FROM contas_pagar

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


            $conta =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$conta) {
                throw new RuntimeException(
                    'Conta a pagar não encontrada.'
                );
            }


            if (
                (float)$conta['valor_pago']
                > 0
            ) {
                throw new RuntimeException(
                    'Não é possível eliminar uma conta que já possui pagamentos.'
                );
            }


            /*
         * Mesmo que valor_pago esteja zero,
         * também verificamos histórico.
         */
            $stmt =
                $pdo->prepare("
                SELECT COUNT(*)

                FROM pagamentos_contas_pagar

                WHERE conta_pagar_id =
                    :conta_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':conta_id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                (int)$stmt->fetchColumn()
                > 0
            ) {
                throw new RuntimeException(
                    'Esta conta possui histórico de pagamentos e não pode ser eliminada.'
                );
            }


            $dadosAnteriores =
                self::dadosAuditoriaContaPagar(
                    $conta
                );


            $stmt =
                $pdo->prepare("
                DELETE FROM contas_pagar

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");


            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível eliminar a conta a pagar.'
                );
            }


            $auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'contas_pagar',
                $id,
                $dadosAnteriores
            );


            $log->registrar(
                $empresaId,
                $usuarioId,
                'CONTAS_PAGAR',
                'ELIMINAR',
                'Conta a pagar eliminada'
                    . (
                        !empty($conta['numero_documento'])
                        ? ': '
                        . $conta['numero_documento']
                        : ' #' . $id
                    ),
                $id,
                !empty($conta['filial_id'])
                    ? (int)$conta['filial_id']
                    : null
            );


            $pdo->commit();

            return true;
        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
    // ==================================================
    // REGISTAR PAGAMENTO
    // ==================================================

    public static function registrarPagamento(
        array $dados
    ): bool {

        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $contaId =
            (int)($dados['id'] ?? 0);

        $valor =
            (float)($dados['valor'] ?? 0);

        $origemPagamento =
            strtoupper(
                trim(
                    (string)(
                        $dados['origem_pagamento']
                        ?? ''
                    )
                )
            );

        $caixaId =
            !empty($dados['caixa_id'])
            ? (int)$dados['caixa_id']
            : null;

        $bancoId =
            !empty($dados['banco_id'])
            ? (int)$dados['banco_id']
            : null;

        $formaPagamentoId =
            (int)(
                $dados['forma_pagamento_id']
                ?? 0
            );

        $dataPagamento =
            trim(
                (string)(
                    $dados['data_pagamento']
                    ?? date('Y-m-d')
                )
            );

        self::validarEmpresa(
            $empresaId
        );

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        if ($contaId <= 0) {
            throw new RuntimeException(
                'Conta a pagar inválida.'
            );
        }

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor do pagamento deve ser superior a zero.'
            );
        }

        if (
            !in_array(
                $origemPagamento,
                ['CAIXA', 'BANCO'],
                true
            )
        ) {
            throw new RuntimeException(
                'Selecione se o pagamento sairá do Caixa ou do Banco.'
            );
        }

        if (
            $origemPagamento === 'CAIXA'
            && $caixaId === null
        ) {
            throw new RuntimeException(
                'Selecione o caixa utilizado no pagamento.'
            );
        }

        if (
            $origemPagamento === 'BANCO'
            && $bancoId === null
        ) {
            throw new RuntimeException(
                'Selecione o banco utilizado no pagamento.'
            );
        }

        if ($formaPagamentoId <= 0) {
            throw new RuntimeException(
                'Selecione a forma de pagamento.'
            );
        }

        self::validarData(
            $dataPagamento,
            'Data de pagamento'
        );

        $pdo = Database::conn();

        $auditoria =
            new AuditoriaService(
                $pdo
            );

        $log =
            new LogService(
                $pdo
            );

        $pdo->beginTransaction();

        try {
            /*
             * 1. Bloqueia a conta.
             */

            $stmt = $pdo->prepare("
                SELECT
                    cp.*

                FROM contas_pagar cp

                WHERE cp.id = :id
                  AND cp.empresa_id = :empresa_id

                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $contaId,
                ':empresa_id' => $empresaId
            ]);

            $conta =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$conta) {
                throw new RuntimeException(
                    'Conta a pagar não encontrada.'
                );
            }

            $dadosContaAntes =
                self::dadosAuditoriaContaPagar(
                    $conta
                );

            $saldoAtual =
                (float)$conta['saldo'];

            if ($saldoAtual <= 0) {
                throw new RuntimeException(
                    'Esta conta já está totalmente paga.'
                );
            }

            if ($valor > $saldoAtual) {
                throw new RuntimeException(
                    'O valor do pagamento não pode ser superior ao saldo pendente de '
                        . number_format(
                            $saldoAtual,
                            2,
                            ',',
                            '.'
                        )
                        . ' MZN.'
                );
            }

            self::validarFormaPagamento(
                $pdo,
                $formaPagamentoId
            );

            /*
             * 2. Centro e filial são herdados da conta.
             */

            $filialId =
                !empty($conta['filial_id'])
                ? (int)$conta['filial_id']
                : null;

            $centroCustoId =
                !empty($conta['centro_custo_id'])
                ? (int)$conta['centro_custo_id']
                : null;

            /*
             * 3. Retira dinheiro da origem selecionada.
             */

            if ($origemPagamento === 'CAIXA') {
                self::registarSaidaCaixa(
                    $pdo,
                    [
                        'empresa_id' =>
                        $empresaId,

                        'filial_id' =>
                        $filialId,

                        'centro_custo_id' =>
                        $centroCustoId,

                        'caixa_id' =>
                        $caixaId,

                        'usuario_id' =>
                        $usuarioId,

                        'conta_id' =>
                        $contaId,

                        'valor' =>
                        $valor,

                        'descricao' =>
                        'Pagamento da conta a pagar #'
                            . $contaId,

                        'data_pagamento' =>
                        $dataPagamento
                    ]
                );
            } else {
                self::registarSaidaBanco(
                    $pdo,
                    [
                        'empresa_id' =>
                        $empresaId,

                        'filial_id' =>
                        $filialId,

                        'centro_custo_id' =>
                        $centroCustoId,

                        'banco_id' =>
                        $bancoId,

                        'usuario_id' =>
                        $usuarioId,

                        'conta_id' =>
                        $contaId,

                        'valor' =>
                        $valor,

                        'numero_documento' =>
                        self::textoOuNull(
                            $dados['numero_documento'] ?? null
                        ),

                        'referencia' =>
                        self::textoOuNull(
                            $dados['referencia']
                                ?? null
                        ),

                        'descricao' =>
                        'Pagamento da conta a pagar #'
                            . $contaId,

                        'data_pagamento' =>
                        $dataPagamento
                    ]
                );
            }

            /*
             * 4. Cria o histórico de pagamento.
             */

            $stmt = $pdo->prepare("
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
        comprovativo,

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
        :comprovativo,

        :criado_por
    )
");

            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':filial_id' => $filialId,
                ':centro_custo_id' => $centroCustoId,
                ':conta_pagar_id' => $contaId,
                ':forma_pagamento_id' => $formaPagamentoId,
                ':valor' => $valor,
                ':data_pagamento' => $dataPagamento,
                ':numero_documento' => self::textoOuNull($dados['numero_documento'] ?? null),
                ':observacoes' => self::textoOuNull($dados['observacoes'] ?? null),
                ':comprovativo' => self::textoOuNull($dados['comprovativo'] ?? null),
                ':criado_por' => $usuarioId
            ]);

            $pagamentoId =
                (int)$pdo->lastInsertId();

            if ($pagamentoId <= 0) {

                $stmtId =
                    $pdo->query("
            SELECT LAST_INSERT_ID()
        ");

                $pagamentoId =
                    (int)$stmtId->fetchColumn();
            }

            if ($pagamentoId <= 0) {
                throw new RuntimeException(
                    'O pagamento foi inserido, mas o seu identificador não foi retornado.'
                );
            }

            /*
             * 5. Atualiza a conta.
             */

            $novoValorPago =
                round(
                    (float)$conta['valor_pago']
                        + $valor,
                    2
                );

            $novoSaldo =
                round(
                    $saldoAtual - $valor,
                    2
                );

            if ($novoSaldo <= 0) {
                $novoSaldo = 0.0;

                $statusId =
                    self::statusContaPagar(
                        $pdo,
                        'Pago'
                    );

                $dataLiquidacao =
                    $dataPagamento;
            } else {
                $statusId =
                    self::statusContaPagar(
                        $pdo,
                        'Parcial'
                    );

                $dataLiquidacao = null;
            }

            $stmt = $pdo->prepare("
                UPDATE contas_pagar
                SET
                    valor_pago =
                        :valor_pago,

                    saldo =
                        :saldo,

                    status_id =
                        :status_id,

                    data_pagamento =
                        :data_pagamento,

                    atualizado_por =
                        :atualizado_por

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':valor_pago' =>
                $novoValorPago,

                ':saldo' =>
                $novoSaldo,

                ':status_id' =>
                $statusId,

                ':data_pagamento' =>
                $dataLiquidacao,

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $contaId,

                ':empresa_id' =>
                $empresaId
            ]);


            // ==========================================
            // CONTABILIZAÇÃO DO PAGAMENTO
            // ==========================================

            self::contabilizarPagamento(
                $pdo,
                $conta,
                $pagamentoId,
                $valor,
                $origemPagamento,
                $usuarioId,
                $dataPagamento,
                self::textoOuNull(
                    $dados['numero_documento']
                        ?? null
                )
            );

            /*
 * ==========================================
 * AUDITORIA DA CONTA
 * ==========================================
 */

            $contaDepois =
                self::buscarContaAuditoria(
                    $pdo,
                    $contaId,
                    $empresaId
                );


            if (!$contaDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar a conta após o pagamento.'
                );
            }


            $auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'contas_pagar',
                $contaId,
                $dadosContaAntes,
                self::dadosAuditoriaContaPagar(
                    $contaDepois
                )
            );


            /*
 * ==========================================
 * AUDITORIA DO PAGAMENTO
 * ==========================================
 */

            $pagamentoAuditoria =
                self::buscarPagamentoAuditoria(
                    $pdo,
                    $pagamentoId,
                    $empresaId
                );


            if (!$pagamentoAuditoria) {
                throw new RuntimeException(
                    'Não foi possível carregar o pagamento para auditoria.'
                );
            }


            $auditoria->insercao(
                $empresaId,
                $usuarioId,
                'pagamentos_contas_pagar',
                $pagamentoId,
                $pagamentoAuditoria
            );


            /*
 * ==========================================
 * LOG
 * ==========================================
 */

            $log->registrar(
                $empresaId,
                $usuarioId,
                'CONTAS_PAGAR',
                'PAGAR',
                'Pagamento registado na conta #'
                    . $contaId
                    . ' no valor de '
                    . number_format(
                        $valor,
                        2,
                        ',',
                        '.'
                    )
                    . ' MZN via '
                    . $origemPagamento,
                $contaId,
                $filialId
            );


            $pdo->commit();

            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Busca uma conta por ID (alias estático com validação de empresa)
     */
    public static function getById(int $id, int $empresaId): ?array
    {
        return self::buscarPorId($id, $empresaId);
    }

    /**
     * Busca o histórico de pagamentos/baixas de uma conta
     */
    public static function getHistoricoPagamentosByContaId(int $contaId, int $empresaId): array
    {
        $pdo = Database::conn();

        $sql = "SELECT 
                    p.id,
                    p.data_pagamento,
                    p.valor,
                    p.numero_documento,
                    p.observacoes,
                    p.comprovativo,
                    f.nome AS forma_pagamento,
                    p.criado_por AS usuario_id
                FROM pagamentos_contas_pagar p
                LEFT JOIN formas_pagamento f ON f.id = p.forma_pagamento_id
                WHERE p.conta_pagar_id = :conta_id
                  AND p.empresa_id = :empresa_id
                ORDER BY p.data_pagamento DESC, p.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':conta_id' => $contaId,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    private static function buscarContaAuditoria(
        PDO $pdo,
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $pdo->prepare("
            SELECT *

            FROM contas_pagar

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


    private static function buscarPagamentoAuditoria(
        PDO $pdo,
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $pdo->prepare("
            SELECT *

            FROM pagamentos_contas_pagar

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


    private static function dadosAuditoriaContaPagar(
        array $conta
    ): array {

        return [
            'id' =>
            isset($conta['id'])
                ? (int)$conta['id']
                : null,

            'empresa_id' =>
            isset($conta['empresa_id'])
                ? (int)$conta['empresa_id']
                : null,

            'filial_id' =>
            !empty($conta['filial_id'])
                ? (int)$conta['filial_id']
                : null,

            'centro_custo_id' =>
            !empty($conta['centro_custo_id'])
                ? (int)$conta['centro_custo_id']
                : null,

            'fornecedor_id' =>
            isset($conta['fornecedor_id'])
                ? (int)$conta['fornecedor_id']
                : null,

            'forma_pagamento_id' =>
            !empty($conta['forma_pagamento_id'])
                ? (int)$conta['forma_pagamento_id']
                : null,

            'status_id' =>
            isset($conta['status_id'])
                ? (int)$conta['status_id']
                : null,

            'numero_documento' =>
            $conta['numero_documento']
                ?? null,

            'referencia' =>
            $conta['referencia']
                ?? null,

            'descricao' =>
            $conta['descricao']
                ?? null,

            'valor' =>
            isset($conta['valor'])
                ? (float)$conta['valor']
                : 0.0,

            'desconto' =>
            isset($conta['desconto'])
                ? (float)$conta['desconto']
                : 0.0,

            'juros' =>
            isset($conta['juros'])
                ? (float)$conta['juros']
                : 0.0,

            'multa' =>
            isset($conta['multa'])
                ? (float)$conta['multa']
                : 0.0,

            'valor_pago' =>
            isset($conta['valor_pago'])
                ? (float)$conta['valor_pago']
                : 0.0,

            'saldo' =>
            isset($conta['saldo'])
                ? (float)$conta['saldo']
                : 0.0,

            'data_emissao' =>
            $conta['data_emissao']
                ?? null,

            'data_vencimento' =>
            $conta['data_vencimento']
                ?? null,

            'data_pagamento' =>
            $conta['data_pagamento']
                ?? null,

            'observacoes' =>
            $conta['observacoes']
                ?? null
        ];
    }

    // ==================================================
    // REGISTAR SAÍDA NO CAIXA
    // ==================================================

    private static function registarSaidaCaixa(
        PDO $pdo,
        array $dados
    ): void {

        $stmt = $pdo->prepare("
            SELECT
                c.id,
                c.saldo_atual,
                c.status_id,

                (
                    SELECT ac.id

                    FROM abertura_caixa ac

                    WHERE ac.caixa_id = c.id
                      AND ac.empresa_id = c.empresa_id

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
            $dados['caixa_id'],

            ':empresa_id' =>
            $dados['empresa_id']
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
            self::statusPorTipoNome(
                $pdo,
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
                'O caixa selecionado não possui uma abertura válida.'
            );
        }

        if (
            (float)$caixa['saldo_atual']
            < (float)$dados['valor']
        ) {
            throw new RuntimeException(
                'Saldo insuficiente no caixa selecionado.'
            );
        }

        $stmt = $pdo->prepare("
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
            $dados['empresa_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':centro_custo_id' =>
            $dados['centro_custo_id'],

            ':caixa_id' =>
            $dados['caixa_id'],

            ':abertura_id' =>
            (int)$caixa['abertura_id'],

            ':usuario_id' =>
            $dados['usuario_id'],

            ':documento_id' =>
            $dados['conta_id'],

            ':descricao' =>
            $dados['descricao'],

            ':valor' =>
            $dados['valor'],

            ':data_movimento' =>
            $dados['data_pagamento']
                . ' '
                . date('H:i:s')
        ]);

        $stmt = $pdo->prepare("
            UPDATE caixas
            SET saldo_atual =
                saldo_atual - :valor

            WHERE id = :id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':valor' =>
            $dados['valor'],

            ':id' =>
            $dados['caixa_id'],

            ':empresa_id' =>
            $dados['empresa_id']
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo do caixa.'
            );
        }
    }

    // ==================================================
    // REGISTAR SAÍDA NO BANCO
    // ==================================================

    private static function registarSaidaBanco(
        PDO $pdo,
        array $dados
    ): void {

        $empresaId = (int)($dados['empresa_id'] ?? 0);
        $filialId = !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;

        $centroCustoId = !empty($dados['centro_custo_id'])
            ? (int)$dados['centro_custo_id']
            : null;

        $bancoId = (int)($dados['banco_id'] ?? 0);
        $usuarioId = (int)($dados['usuario_id'] ?? 0);
        $contaId = (int)($dados['conta_id'] ?? 0);
        $valor = round((float)($dados['valor'] ?? 0), 2);

        if ($empresaId <= 0) {
            throw new RuntimeException('Empresa inválida.');
        }

        if ($bancoId <= 0) {
            throw new RuntimeException(
                'Selecione o banco utilizado no pagamento.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        if ($contaId <= 0) {
            throw new RuntimeException(
                'Conta a pagar inválida.'
            );
        }

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor do pagamento deve ser superior a zero.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | BLOQUEAR CONTA BANCÁRIA
    |--------------------------------------------------------------------------
    */

        $stmt = $pdo->prepare("
        SELECT
            b.id,
            b.empresa_id,
            b.filial_id,
            b.status_id,
            b.nome_banco,
            b.saldo_atual,
            s.nome AS status_nome

        FROM bancos b

        INNER JOIN status s
            ON s.id = b.status_id
           AND s.tipo = 'banco'

        WHERE b.id = :banco_id
          AND b.empresa_id = :empresa_id

        LIMIT 1

        FOR UPDATE
    ");

        $stmt->execute([
            ':banco_id' => $bancoId,
            ':empresa_id' => $empresaId
        ]);

        $banco = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$banco) {
            throw new RuntimeException(
                'Conta bancária não encontrada.'
            );
        }

        if (
            mb_strtolower(
                trim((string)$banco['status_nome'])
            ) !== 'activo'
        ) {
            throw new RuntimeException(
                'A conta bancária selecionada não está activa.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | CALCULAR SALDOS
    |--------------------------------------------------------------------------
    */

        $saldoAnterior = round(
            (float)$banco['saldo_atual'],
            2
        );

        if ($saldoAnterior < $valor) {
            throw new RuntimeException(
                'Saldo insuficiente na conta bancária. '
                    . 'Saldo disponível: '
                    . number_format(
                        $saldoAnterior,
                        2,
                        ',',
                        '.'
                    )
                    . ' MZN.'
            );
        }

        $saldoPosterior = round(
            $saldoAnterior - $valor,
            2
        );

        /*
    |--------------------------------------------------------------------------
    | REGISTAR MOVIMENTO BANCÁRIO
    |--------------------------------------------------------------------------
    */

        $stmt = $pdo->prepare("
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

            documento,
            referencia,
            descricao,

            valor,
            saldo_anterior,
            saldo_posterior,

            data_movimento,
            conciliado
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
            'CONTA_PAGAR',
            :origem_id,

            :documento,
            :referencia,
            :descricao,

            :valor,
            :saldo_anterior,
            :saldo_posterior,

            :data_movimento,
            0
        )
    ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            $filialId,

            ':centro_custo_id' =>
            $centroCustoId,

            ':banco_id' =>
            $bancoId,

            ':usuario_id' =>
            $usuarioId,

            ':origem_id' =>
            $contaId,

            ':documento' =>
            self::textoOuNull(
                $dados['numero_documento'] ?? null
            ),

            ':referencia' =>
            self::textoOuNull(
                $dados['referencia'] ?? null
            ),

            ':descricao' =>
            trim(
                (string)(
                    $dados['descricao']
                    ?? (
                        'Pagamento da conta a pagar #'
                        . $contaId
                    )
                )
            ),

            ':valor' =>
            $valor,

            ':saldo_anterior' =>
            $saldoAnterior,

            ':saldo_posterior' =>
            $saldoPosterior,

            ':data_movimento' =>
            $dados['data_pagamento']
                . ' '
                . date('H:i:s')
        ]);

        /*
    |--------------------------------------------------------------------------
    | ATUALIZAR SALDO DO BANCO
    |--------------------------------------------------------------------------
    */

        $stmt = $pdo->prepare("
        UPDATE bancos

        SET saldo_atual = :saldo_atual

        WHERE id = :banco_id
          AND empresa_id = :empresa_id
    ");

        $stmt->execute([
            ':saldo_atual' =>
            $saldoPosterior,

            ':banco_id' =>
            $bancoId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o saldo bancário.'
            );
        }
    }

    // ==================================================
    // CONTABILIZAR PAGAMENTO
    // ==================================================

    private static function contabilizarPagamento(
        PDO $pdo,
        array $conta,
        int $pagamentoId,
        float $valor,
        string $origemPagamento,
        int $usuarioId,
        string $dataPagamento,
        ?string $numeroDocumento = null
    ): ?int {

        $empresaId =
            (int)(
                $conta['empresa_id']
                ?? 0
            );

        $filialId =
            !empty($conta['filial_id'])
            ? (int)$conta['filial_id']
            : null;

        $centroCustoId =
            !empty($conta['centro_custo_id'])
            ? (int)$conta['centro_custo_id']
            : null;

        $fornecedorId =
            (int)(
                $conta['fornecedor_id']
                ?? 0
            );

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para contabilização do pagamento.'
            );
        }

        if ($pagamentoId <= 0) {
            throw new RuntimeException(
                'Pagamento inválido para contabilização.'
            );
        }

        if ($fornecedorId <= 0) {
            throw new RuntimeException(
                'Fornecedor inválido para contabilização do pagamento.'
            );
        }

        $valor =
            round(
                $valor,
                2
            );

        if ($valor <= 0) {
            throw new RuntimeException(
                'Valor inválido para contabilização do pagamento.'
            );
        }

        $origemPagamento =
            strtoupper(
                trim(
                    $origemPagamento
                )
            );

        if (
            !in_array(
                $origemPagamento,
                [
                    'CAIXA',
                    'BANCO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Origem do pagamento inválida para contabilização.'
            );
        }

        $contabilidade =
            new ContabilidadeService(
                $pdo
            );

        /*
     * Cada histórico de pagamento terá apenas
     * um lançamento contabilístico.
     */
        if (
            $contabilidade
            ->existeLancamentoOrigem(
                $empresaId,
                $filialId,
                'PAGAMENTO',
                $pagamentoId
            )
        ) {
            return null;
        }

        $contexto =
            $contabilidade
            ->contextoData(
                $empresaId,
                $dataPagamento
            );

        /*
     * Usamos o diário correspondente ao local
     * de saída do dinheiro.
     */
        $tipoDiario =
            $origemPagamento === 'CAIXA'
            ? 'CAIXA'
            : 'BANCOS';

        $diario =
            $contabilidade
            ->diarioPorTipo(
                $empresaId,
                $tipoDiario
            );

        $contaFornecedores =
            $contabilidade
            ->contaIntegracao(
                $empresaId,
                'FORNECEDORES'
            );

        $codigoContaDestino =
            $origemPagamento === 'CAIXA'
            ? 'CAIXA'
            : 'BANCOS';

        $contaDestino =
            $contabilidade
            ->contaIntegracao(
                $empresaId,
                $codigoContaDestino
            );

        $numeroConta =
            $conta['numero_documento']
            ?? null;

        $documento =
            $numeroDocumento
            ?? $numeroConta
            ?? 'PAG-' . $pagamentoId;

        return $contabilidade
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
                    'PAGAMENTO-'
                        . $pagamentoId,

                    'data_lancamento' =>
                    $dataPagamento,

                    'data_documento' =>
                    $dataPagamento,

                    'descricao' =>
                    'Pagamento da conta a pagar #'
                        . $conta['id'],

                    'origem' =>
                    'PAGAMENTO',

                    'origem_id' =>
                    $pagamentoId,

                    'usuario_id' =>
                    $usuarioId
                ],
                [
                    /*
                 * Débito:
                 * reduz a obrigação perante o fornecedor.
                 */
                    [
                        'conta_contabil_id' =>
                        (int)$contaFornecedores['id'],

                        'centro_custo_id' =>
                        $centroCustoId,

                        'cliente_id' =>
                        null,

                        'fornecedor_id' =>
                        $fornecedorId,

                        'descricao' =>
                        'Liquidação da conta do fornecedor #'
                            . $conta['id'],

                        'debito' =>
                        $valor,

                        'credito' =>
                        0
                    ],

                    /*
                 * Crédito:
                 * reduz o saldo do caixa ou banco.
                 */
                    [
                        'conta_contabil_id' =>
                        (int)$contaDestino['id'],

                        'centro_custo_id' =>
                        $centroCustoId,

                        'cliente_id' =>
                        null,

                        'fornecedor_id' =>
                        null,

                        'descricao' =>
                        'Saída por pagamento da conta #'
                            . $conta['id'],

                        'debito' =>
                        0,

                        'credito' =>
                        $valor
                    ]
                ]
            );
    }

    // ==================================================
    // STATUS DA CONTA A PAGAR
    // ==================================================

    private static function statusContaPagar(
        PDO $pdo,
        string $nome
    ): int {

        return self::statusPorTipoNome(
            $pdo,
            'conta_pagar',
            $nome
        );
    }

    // ==================================================
    // STATUS POR TIPO E NOME
    // ==================================================

    private static function statusPorTipoNome(
        PDO $pdo,
        string $tipo,
        string $nome
    ): int {

        $stmt = $pdo->prepare("
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
    // VALIDAR FORNECEDOR
    // ==================================================

    private static function validarFornecedor(
        PDO $pdo,
        int $fornecedorId,
        int $empresaId
    ): void {

        if ($fornecedorId <= 0) {
            throw new RuntimeException(
                'Selecione o fornecedor.'
            );
        }

        /*
         * Se fornecedores possuir empresa_id, mantenha
         * a verificação abaixo.
         */

        $stmt = $pdo->prepare("
            SELECT id

            FROM fornecedores

            WHERE id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $fornecedorId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Fornecedor não encontrado.'
            );
        }
    }

    // ==================================================
    // VALIDAR FILIAL
    // ==================================================

    private static function validarFilial(
        PDO $pdo,
        ?int $filialId,
        int $empresaId
    ): void {

        if ($filialId === null) {
            return;
        }

        $stmt = $pdo->prepare("
            SELECT id

            FROM filiais

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $filialId,
            ':empresa_id' => $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'A filial selecionada não pertence à empresa.'
            );
        }
    }

    // ==================================================
    // VALIDAR CENTRO DE CUSTO
    // ==================================================

    private static function validarCentroCusto(
        PDO $pdo,
        ?int $centroCustoId,
        int $empresaId,
        ?int $filialId
    ): void {

        if ($centroCustoId === null) {
            throw new RuntimeException(
                'Selecione o centro de custo.'
            );
        }

        $stmt = $pdo->prepare("
            SELECT
                cc.id,
                cc.filial_id,
                cc.tipo,

                s.nome AS status

            FROM centros_custo cc

            INNER JOIN status s
                ON s.id = cc.status_id
                AND s.tipo = 'centro_custo'

            WHERE cc.id = :id
              AND cc.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $centroCustoId,
            ':empresa_id' => $empresaId
        ]);

        $centro =
            $stmt->fetch(
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
                    (string)$centro['status']
                ),
                ['activo', 'ativo'],
                true
            )
        ) {
            throw new RuntimeException(
                'O centro de custo selecionado não está activo.'
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
                'Para contas a pagar, selecione um centro do tipo Custo ou Misto.'
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
                'O centro de custo selecionado pertence a outra filial.'
            );
        }
    }

    // ==================================================
    // VALIDAR FORMA DE PAGAMENTO
    // ==================================================

    private static function validarFormaPagamento(
        PDO $pdo,
        ?int $formaPagamentoId
    ): void {

        if ($formaPagamentoId === null) {
            return;
        }

        if ($formaPagamentoId <= 0) {
            throw new RuntimeException(
                'Forma de pagamento inválida.'
            );
        }

        $stmt = $pdo->prepare("
            SELECT id

            FROM formas_pagamento

            WHERE id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $formaPagamentoId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Forma de pagamento não encontrada.'
            );
        }
    }

    // ==================================================
    // NORMALIZAR DADOS
    // ==================================================

    private static function normalizarDados(
        array $dados
    ): array {

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

            'fornecedor_id' =>
            (int)($dados['fornecedor_id'] ?? 0),

            'forma_pagamento_id' =>
            !empty($dados['forma_pagamento_id'])
                ? (int)$dados['forma_pagamento_id']
                : null,

            'numero_documento' =>
            self::textoOuNull(
                $dados['numero_documento']
                    ?? null
            ),

            'referencia' =>
            self::textoOuNull(
                $dados['referencia']
                    ?? null
            ),

            'descricao' =>
            trim(
                (string)(
                    $dados['descricao'] ?? ''
                )
            ),

            'valor' =>
            (float)($dados['valor'] ?? 0),

            'desconto' =>
            (float)($dados['desconto'] ?? 0),

            'juros' =>
            (float)($dados['juros'] ?? 0),

            'multa' =>
            (float)($dados['multa'] ?? 0),

            'data_emissao' =>
            trim(
                (string)(
                    $dados['data_emissao'] ?? ''
                )
            ),

            'data_vencimento' =>
            trim(
                (string)(
                    $dados['data_vencimento'] ?? ''
                )
            ),

            'observacoes' =>
            self::textoOuNull(
                $dados['observacoes']
                    ?? null
            ),

            'usuario_id' =>
            !empty($dados['usuario_id'])
                ? (int)$dados['usuario_id']
                : null
        ];
    }

    // ==================================================
    // VALIDAR DADOS DA CONTA
    // ==================================================

    private static function validarDadosConta(
        array $dados
    ): void {

        self::validarEmpresa(
            $dados['empresa_id']
        );

        if ($dados['fornecedor_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o fornecedor.'
            );
        }

        if ($dados['centro_custo_id'] === null) {
            throw new RuntimeException(
                'Selecione o centro de custo.'
            );
        }

        if ($dados['descricao'] === '') {
            throw new RuntimeException(
                'Informe a descrição da conta.'
            );
        }

        if ($dados['valor'] <= 0) {
            throw new RuntimeException(
                'O valor da conta deve ser superior a zero.'
            );
        }

        foreach (
            ['desconto', 'juros', 'multa']
            as $campo
        ) {
            if ($dados[$campo] < 0) {
                throw new RuntimeException(
                    ucfirst($campo)
                        . ' não pode ser negativo.'
                );
            }
        }

        self::validarData(
            $dados['data_emissao'],
            'Data de emissão'
        );

        self::validarData(
            $dados['data_vencimento'],
            'Data de vencimento'
        );

        if (
            $dados['data_vencimento']
            < $dados['data_emissao']
        ) {
            throw new RuntimeException(
                'A data de vencimento não pode ser anterior à data de emissão.'
            );
        }

        $total =
            self::calcularTotalConta(
                $dados['valor'],
                $dados['desconto'],
                $dados['juros'],
                $dados['multa']
            );

        if ($total <= 0) {
            throw new RuntimeException(
                'O total da conta deve ser superior a zero.'
            );
        }
    }

    // ==================================================
    // CALCULAR TOTAL DA CONTA
    // ==================================================

    private static function calcularTotalConta(
        float $valor,
        float $desconto,
        float $juros,
        float $multa
    ): float {

        return round(
            $valor
                + $juros
                + $multa
                - $desconto,
            2
        );
    }

    // ==================================================
    // VALIDAR DATA
    // ==================================================

    private static function validarData(
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

    // ==================================================
    // TEXTO OU NULL
    // ==================================================

    private static function textoOuNull(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)($valor ?? '')
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    // ==================================================
    // VALIDAR EMPRESA
    // ==================================================

    private static function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para a conta a pagar.'
            );
        }
    }
}
