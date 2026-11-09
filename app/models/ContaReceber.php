<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/ContabilidadeService.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class ContaReceber
{
    private PDO $pdo;

    private ContabilidadeService $contabilidade;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

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
                cr.id,
                cr.empresa_id,
                cr.filial_id,
                cr.centro_custo_id,
                cr.cliente_id,
                cr.venda_id,
                cr.forma_pagamento_id,
                cr.status_id,

                cr.numero_documento,
                cr.data_emissao,
                cr.data_vencimento,
                cr.data_pagamento,

                cr.valor,
                cr.desconto,
                cr.juros,
                cr.multa,
                cr.valor_recebido,
                cr.saldo,

                cr.observacoes,
                cr.criado_em,
                cr.atualizado_em,

                cliente.nome AS cliente_nome,

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

                centro.codigo AS centro_custo_codigo,

                (
                    cr.valor
                    + COALESCE(cr.juros, 0)
                    + COALESCE(cr.multa, 0)
                    - COALESCE(cr.desconto, 0)
                    - COALESCE(cr.valor_recebido, 0)
                ) AS saldo_calculado

            FROM contas_receber cr

            INNER JOIN clientes cliente
                ON cliente.id = cr.cliente_id

            LEFT JOIN formas_pagamento forma
                ON forma.id = cr.forma_pagamento_id

            INNER JOIN status
                ON status.id = cr.status_id

            LEFT JOIN filiais filial
                ON filial.id = cr.filial_id
                AND filial.empresa_id = cr.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id = cr.centro_custo_id
                AND centro.empresa_id = cr.empresa_id

            WHERE cr.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND cr.filial_id = :filial_id
            ";

            $params[':filial_id'] = $filialId;
        }

        $sql .= "
            ORDER BY
                cr.data_vencimento ASC,
                cr.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        $dados = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

        foreach ($dados as &$conta) {
            $saldo = max(
                0,
                (float)$conta['saldo_calculado']
            );

            $conta['saldo'] = round(
                $saldo,
                2
            );

            if ($saldo <= 0) {
                $conta['status_nome'] = 'Recebido';
            } elseif (
                (float)$conta['valor_recebido'] > 0
            ) {
                $conta['status_nome'] = 'Parcial';
            } elseif (
                $conta['data_vencimento'] < date('Y-m-d')
            ) {
                $conta['status_nome'] = 'Vencido';
            } else {
                $conta['status_nome'] = 'Pendente';
            }
        }

        unset($conta);

        return $dados;
    }

    public function parcelas(
        int $contaId,
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );


        if ($contaId <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida.'
            );
        }


        $stmt =
            $this->pdo->prepare("
            SELECT
                p.id,
                p.conta_receber_id,

                p.numero_parcela,
                p.total_parcelas,

                p.data_vencimento,

                p.valor,
                p.valor_recebido,
                p.saldo,

                p.status,
                p.data_pagamento

            FROM contas_receber_parcelas p

            INNER JOIN contas_receber cr
                ON cr.id =
                    p.conta_receber_id

            WHERE p.conta_receber_id =
                :conta_id

              AND cr.empresa_id =
                :empresa_id

            ORDER BY
                p.numero_parcela ASC,
                p.id ASC
        ");


        $stmt->execute([
            ':conta_id' =>
            $contaId,

            ':empresa_id' =>
            $empresaId
        ]);


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
                'Conta a receber inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                cr.*,

                cliente.nome AS cliente_nome,

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

            FROM contas_receber cr

            INNER JOIN clientes cliente
                ON cliente.id = cr.cliente_id

            LEFT JOIN formas_pagamento forma
                ON forma.id = cr.forma_pagamento_id

            INNER JOIN status
                ON status.id = cr.status_id

            LEFT JOIN filiais filial
                ON filial.id = cr.filial_id
                AND filial.empresa_id = cr.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id = cr.centro_custo_id
                AND centro.empresa_id = cr.empresa_id

            WHERE cr.id = :id
              AND cr.empresa_id = :empresa_id

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
    // GUARDAR
    // ==================================================

    public function salvar(array $dados): int|bool
    {
        if (!empty($dados['id'])) {
            return $this->atualizar($dados);
        }

        return $this->criar($dados);
    }

    // ==================================================
    // CRIAR
    // ==================================================

    private function criar(array $dados): int
    {
        $dados =
            $this->normalizarDados(
                $dados
            );

        $this->validarDadosConta(
            $dados
        );

        $empresaId =
            (int)$dados['empresa_id'];

        $usuarioId =
            (int)$dados['usuario_id'];


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        $this->validarCliente(
            $dados['cliente_id'],
            $empresaId
        );

        $this->validarFilial(
            $dados['filial_id'],
            $empresaId
        );

        $this->validarCentroCusto(
            $dados['centro_custo_id'],
            $empresaId,
            $dados['filial_id']
        );

        $this->validarFormaPagamento(
            $dados['forma_pagamento_id']
        );


        $total =
            $this->calcularTotal(
                $dados['valor'],
                $dados['desconto'],
                $dados['juros'],
                $dados['multa']
            );


        $statusId =
            $this->statusContaReceber(
                'Pendente'
            );


        $this->pdo->beginTransaction();

        try {

            $stmt =
                $this->pdo->prepare("
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
                    :desconto,
                    :juros,
                    :multa,

                    0,
                    :saldo,

                    :observacoes
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $dados['filial_id'],

                ':centro_custo_id' =>
                $dados['centro_custo_id'],

                ':cliente_id' =>
                $dados['cliente_id'],

                ':venda_id' =>
                $dados['venda_id'],

                ':forma_pagamento_id' =>
                $dados['forma_pagamento_id'],

                ':status_id' =>
                $statusId,

                ':numero_documento' =>
                $dados['numero_documento'],

                ':data_emissao' =>
                $dados['data_emissao'],

                ':data_vencimento' =>
                $dados['data_vencimento'],

                ':valor' =>
                $dados['valor'],

                ':desconto' =>
                $dados['desconto'],

                ':juros' =>
                $dados['juros'],

                ':multa' =>
                $dados['multa'],

                ':saldo' =>
                $total,

                ':observacoes' =>
                $dados['observacoes']
            ]);


            $id =
                (int)$this->pdo
                    ->lastInsertId();


            if ($id <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar a conta a receber criada.'
                );
            }


            $novaConta =
                $this->buscarContaAuditoria(
                    $id,
                    $empresaId
                );


            if (!$novaConta) {
                throw new RuntimeException(
                    'Não foi possível carregar a conta criada para auditoria.'
                );
            }


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'contas_receber',
                $id,
                $this->dadosAuditoriaContaReceber(
                    $novaConta
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CONTAS_RECEBER',
                'CRIAR',
                'Conta a receber criada'
                    . (
                        !empty($novaConta['numero_documento'])
                        ? ': ' . $novaConta['numero_documento']
                        : ' #' . $id
                    ),
                $id,
                !empty($novaConta['filial_id'])
                    ? (int)$novaConta['filial_id']
                    : null
            );


            $this->pdo->commit();

            return $id;
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // ATUALIZAR
    // ==================================================

    private function atualizar(
        array $dados
    ): bool {

        $dados = $this->normalizarDados(
            $dados
        );

        $id = (int)($dados['id'] ?? 0);

        if ($id <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida.'
            );
        }

        $empresaId =
            (int)$dados['empresa_id'];

        $usuarioId =
            (int)$dados['usuario_id'];

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        $this->validarDadosConta($dados);

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    valor_recebido

                FROM contas_receber

                WHERE id = :id
                  AND empresa_id = :empresa_id

                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $id,
                ':empresa_id' =>
                $dados['empresa_id']
            ]);

            $contaAtual = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$contaAtual) {
                throw new RuntimeException(
                    'Conta a receber não encontrada.'
                );
            }

            $dadosAnteriores =
                $this->buscarContaAuditoria(
                    $id,
                    $empresaId
                );

            if (!$dadosAnteriores) {
                throw new RuntimeException(
                    'Não foi possível carregar os dados anteriores da conta.'
                );
            }

            $this->validarCliente(
                $dados['cliente_id'],
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

            $total = $this->calcularTotal(
                $dados['valor'],
                $dados['desconto'],
                $dados['juros'],
                $dados['multa']
            );

            $valorRecebido =
                (float)$contaAtual['valor_recebido'];

            if ($total < $valorRecebido) {
                throw new RuntimeException(
                    'O novo total não pode ser inferior ao valor já recebido.'
                );
            }

            $saldo = round(
                $total - $valorRecebido,
                2
            );

            if ($saldo <= 0) {
                $statusId =
                    $this->statusContaReceber(
                        'Recebido'
                    );
            } elseif ($valorRecebido > 0) {
                $statusId =
                    $this->statusContaReceber(
                        'Parcial'
                    );
            } elseif (
                $dados['data_vencimento']
                < date('Y-m-d')
            ) {
                $statusId =
                    $this->statusContaReceber(
                        'Vencido'
                    );
            } else {
                $statusId =
                    $this->statusContaReceber(
                        'Pendente'
                    );
            }

            $stmt = $this->pdo->prepare("
                UPDATE contas_receber
                SET
                    filial_id =
                        :filial_id,

                    centro_custo_id =
                        :centro_custo_id,

                    cliente_id =
                        :cliente_id,

                    venda_id =
                        :venda_id,

                    forma_pagamento_id =
                        :forma_pagamento_id,

                    status_id =
                        :status_id,

                    numero_documento =
                        :numero_documento,

                    data_emissao =
                        :data_emissao,

                    data_vencimento =
                        :data_vencimento,

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

                ':venda_id' =>
                $dados['venda_id'],

                ':forma_pagamento_id' =>
                $dados['forma_pagamento_id'],

                ':status_id' =>
                $statusId,

                ':numero_documento' =>
                $dados['numero_documento'],

                ':data_emissao' =>
                $dados['data_emissao'],

                ':data_vencimento' =>
                $dados['data_vencimento'],

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

                ':observacoes' =>
                $dados['observacoes'],

                ':id' =>
                $id,

                ':empresa_id' =>
                $dados['empresa_id']
            ]);

            $dadosNovos =
                $this->buscarContaAuditoria(
                    $id,
                    $empresaId
                );

            if (!$dadosNovos) {
                throw new RuntimeException(
                    'Não foi possível carregar os novos dados da conta.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'contas_receber',
                $id,
                $this->dadosAuditoriaContaReceber(
                    $dadosAnteriores
                ),
                $this->dadosAuditoriaContaReceber(
                    $dadosNovos
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CONTAS_RECEBER',
                'EDITAR',
                'Conta a receber atualizada'
                    . (
                        !empty($dadosNovos['numero_documento'])
                        ? ': ' . $dadosNovos['numero_documento']
                        : ' #' . $id
                    ),
                $id,
                !empty($dadosNovos['filial_id'])
                    ? (int)$dadosNovos['filial_id']
                    : null
            );

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
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


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        if ($id <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            $stmt =
                $this->pdo->prepare("
                SELECT *

                FROM contas_receber

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
                    'Conta a receber não encontrada.'
                );
            }


            if (
                (float)$conta['valor_recebido']
                > 0
            ) {
                throw new RuntimeException(
                    'Não é possível eliminar uma conta que já possui recebimentos.'
                );
            }


            $stmt =
                $this->pdo->prepare("
                SELECT COUNT(*)

                FROM recebimentos

                WHERE conta_receber_id =
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
                    'Esta conta possui histórico de recebimentos.'
                );
            }


            $dadosAnteriores =
                $this->dadosAuditoriaContaReceber(
                    $conta
                );


            /*
         * Se existirem parcelas sem recebimento,
         * eliminamos primeiro as parcelas.
         */
            $stmt =
                $this->pdo->prepare("
                DELETE FROM contas_receber_parcelas

                WHERE conta_receber_id =
                    :conta_id
            ");


            $stmt->execute([
                ':conta_id' =>
                $id
            ]);


            $stmt =
                $this->pdo->prepare("
                DELETE FROM contas_receber

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
                    'Não foi possível eliminar a conta a receber.'
                );
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'contas_receber',
                $id,
                $dadosAnteriores
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CONTAS_RECEBER',
                'ELIMINAR',
                'Conta a receber eliminada'
                    . (
                        !empty($conta['numero_documento'])
                        ? ': ' . $conta['numero_documento']
                        : ' #' . $id
                    ),
                $id,
                !empty($conta['filial_id'])
                    ? (int)$conta['filial_id']
                    : null
            );


            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
    // ==================================================
    // REGISTAR RECEBIMENTO
    // ==================================================

    public function receber(
        array $dados
    ): bool {

        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $contaId =
            (int)($dados['id'] ?? 0);

        $parcelaId =
            !empty($dados['parcela_id'])
            ? (int)$dados['parcela_id']
            : null;

        $valor =
            (float)($dados['valor'] ?? 0);

        $formaPagamentoId =
            (int)(
                $dados['forma_pagamento_id']
                ?? 0
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

        $caixaId =
            !empty($dados['caixa_id'])
            ? (int)$dados['caixa_id']
            : null;

        $bancoId =
            !empty($dados['banco_id'])
            ? (int)$dados['banco_id']
            : null;

        $dataRecebimento =
            trim(
                (string)(
                    $dados['data_recebimento']
                    ?? date('Y-m-d')
                )
            );

        $this->validarEmpresa($empresaId);

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        if ($contaId <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida.'
            );
        }

        if ($valor <= 0) {
            throw new RuntimeException(
                'O valor recebido deve ser superior a zero.'
            );
        }

        if ($formaPagamentoId <= 0) {
            throw new RuntimeException(
                'Selecione a forma de pagamento.'
            );
        }

        if (
            !in_array(
                $destino,
                ['CAIXA', 'BANCO'],
                true
            )
        ) {
            throw new RuntimeException(
                'Selecione o destino do recebimento.'
            );
        }

        if (
            $destino === 'CAIXA'
            && $caixaId === null
        ) {
            throw new RuntimeException(
                'Selecione o caixa de destino.'
            );
        }

        if (
            $destino === 'BANCO'
            && $bancoId === null
        ) {
            throw new RuntimeException(
                'Selecione o banco de destino.'
            );
        }

        $this->validarData(
            $dataRecebimento,
            'Data do recebimento'
        );

        $caminhoComprovativo = null;

        // Processamento do upload se o destino for BANCO
        if ($destino === 'BANCO' && isset($_FILES['comprovativo']) && $_FILES['comprovativo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['comprovativo'];
            $extensoesPermitidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
            $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extensao, $extensoesPermitidas, true)) {
                throw new RuntimeException('Formato de ficheiro não suportado. Envie apenas PDF ou Imagens (JPG, PNG, WEBP).');
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5 MB
                throw new RuntimeException('O ficheiro do comprovativo não pode exceder 5 MB.');
            }

            $diretorioUpload = dirname(__DIR__, 2)
                . '/public/uploads/comprovativos/';
            if (!is_dir($diretorioUpload)) {
                mkdir($diretorioUpload, 0775, true);
            }

            $nomeFicheiro = 'comprovativo_' . $contaId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;
            $destinoFicheiro = $diretorioUpload . $nomeFicheiro;

            if (!move_uploaded_file($file['tmp_name'], $destinoFicheiro)) {
                throw new RuntimeException('Falha ao guardar o comprovativo no servidor.');
            }

            $caminhoComprovativo = 'public/uploads/comprovativos/' . $nomeFicheiro;
        }

        $this->pdo->beginTransaction();

        try {
            /*
             * 1. Bloquear conta.
             */

            $stmt = $this->pdo->prepare("
                SELECT
                    cr.*,

                    cliente.nome AS cliente_nome

                FROM contas_receber cr

                INNER JOIN clientes cliente
                    ON cliente.id = cr.cliente_id

                WHERE cr.id = :id
                  AND cr.empresa_id = :empresa_id

                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $contaId,
                ':empresa_id' => $empresaId
            ]);

            $conta = $stmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$conta) {
                throw new RuntimeException(
                    'Conta a receber não encontrada.'
                );
            }

            $dadosContaAntes =
                $this->dadosAuditoriaContaReceber(
                    $conta
                );

            $saldoAtual =
                (float)$conta['saldo'];

            if ($saldoAtual <= 0) {
                throw new RuntimeException(
                    'Esta conta já foi totalmente recebida.'
                );
            }

            if ($valor > $saldoAtual) {
                throw new RuntimeException(
                    'O valor recebido não pode ser superior ao saldo pendente de '
                        . number_format(
                            $saldoAtual,
                            2,
                            ',',
                            '.'
                        )
                        . ' MZN.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | PARCELAMENTO
            |--------------------------------------------------------------------------
            */

            $stmt =
                $this->pdo->prepare("
                SELECT COUNT(*)

                FROM contas_receber_parcelas

                WHERE conta_receber_id =
                    :conta_id
            ");


            $stmt->execute([
                ':conta_id' =>
                $contaId
            ]);


            $possuiParcelas =
                (int)$stmt->fetchColumn()
                > 0;


            $parcela = null;

            if ($possuiParcelas) {

                if (
                    $parcelaId === null
                    || $parcelaId <= 0
                ) {
                    throw new RuntimeException(
                        'Selecione a parcela que deseja receber.'
                    );
                }


                $stmt =
                    $this->pdo->prepare("
                    SELECT *

                    FROM contas_receber_parcelas

                    WHERE id = :parcela_id
                    AND conta_receber_id = :conta_id

                    FOR UPDATE
                ");


                $stmt->execute([
                    ':parcela_id' =>
                    $parcelaId,

                    ':conta_id' =>
                    $contaId
                ]);


                $parcela =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                if (!$parcela) {
                    throw new RuntimeException(
                        'Parcela não encontrada para esta conta.'
                    );
                }


                $saldoParcela =
                    round(
                        (float)$parcela['saldo'],
                        2
                    );


                if ($saldoParcela <= 0) {
                    throw new RuntimeException(
                        'Esta parcela já está totalmente paga.'
                    );
                }


                if ($valor > $saldoParcela) {
                    throw new RuntimeException(
                        'O valor recebido não pode ser superior ao saldo da parcela de '
                            . number_format(
                                $saldoParcela,
                                2,
                                ',',
                                '.'
                            )
                            . ' MZN.'
                    );
                }
            }


            /*
            * Snapshot da parcela antes da alteração.
            */
            $dadosParcelaAntes =
                $parcela !== null
                ? $this->dadosAuditoriaParcelaReceber(
                    $parcela
                )
                : null;


            $this->validarFormaPagamento(
                $formaPagamentoId
            );

            $filialId =
                !empty($conta['filial_id'])
                ? (int)$conta['filial_id']
                : null;

            $centroCustoId =
                !empty($conta['centro_custo_id'])
                ? (int)$conta['centro_custo_id']
                : null;

            /*
             * 2. Entrada no Caixa ou Banco.
             */

            if ($destino === 'CAIXA') {
                $this->registarEntradaCaixa([
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
                    'Recebimento da conta #'
                        . $contaId
                        . ' - '
                        . $conta['cliente_nome'],

                    'data_recebimento' =>
                    $dataRecebimento
                ]);
            } else {
                $this->registarEntradaBanco([
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

                    'documento' =>
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
                    'Recebimento da conta #'
                        . $contaId
                        . ' - '
                        . $conta['cliente_nome'],

                    'data_recebimento' =>
                    $dataRecebimento
                ]);
            }

            /*
             * 3. Histórico em recebimentos.
             */

            $stmt = $this->pdo->prepare("
            INSERT INTO recebimentos
            (
                empresa_id,
                filial_id,
                centro_custo_id,

                conta_receber_id,
                conta_receber_parcela_id,
                forma_pagamento_id,
                usuario_id,

                valor,
                data_recebimento,

                referencia,
                observacoes,
                comprovativo
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :centro_custo_id,

                :conta_receber_id,
                :conta_receber_parcela_id,
                :forma_pagamento_id,
                :usuario_id,

                :valor,
                :data_recebimento,

                :referencia,
                :observacoes,
                :comprovativo
            )
        ");

            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':filial_id' => $filialId,
                ':centro_custo_id' => $centroCustoId,
                ':conta_receber_id' => $contaId,
                ':conta_receber_parcela_id' => $parcelaId,
                ':forma_pagamento_id' => $formaPagamentoId,
                ':usuario_id' => $usuarioId,
                ':valor' => $valor,
                ':data_recebimento' => $dataRecebimento . ' ' . date('H:i:s'),
                ':referencia' => $this->textoOuNull($dados['referencia'] ?? null),
                ':observacoes' => $this->textoOuNull($dados['observacoes'] ?? null),
                ':comprovativo' => $caminhoComprovativo
            ]);

            $recebimentoId =
                (int)$this->pdo->lastInsertId();

            if ($recebimentoId <= 0) {
                $recebimentoId =
                    (int)$this->pdo
                        ->query(
                            "SELECT LAST_INSERT_ID()"
                        )
                        ->fetchColumn();
            }

            if ($recebimentoId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o recebimento registado.'
                );
            }

            /*
|--------------------------------------------------------------------------
| ATUALIZAR PARCELA
|--------------------------------------------------------------------------
*/

            if ($parcela !== null) {

                $novoRecebidoParcela =
                    round(
                        (float)$parcela['valor_recebido']
                            + $valor,
                        2
                    );


                $novoSaldoParcela =
                    round(
                        (float)$parcela['saldo']
                            - $valor,
                        2
                    );


                if ($novoSaldoParcela <= 0) {

                    $novoSaldoParcela =
                        0.00;

                    $statusParcela =
                        'PAGA';

                    $dataPagamentoParcela =
                        $dataRecebimento;
                } else {

                    $statusParcela =
                        'PARCIAL';

                    $dataPagamentoParcela =
                        null;
                }


                $stmt =
                    $this->pdo->prepare("
            UPDATE contas_receber_parcelas

            SET
                valor_recebido =
                    :valor_recebido,

                saldo =
                    :saldo,

                status =
                    :status,

                data_pagamento =
                    :data_pagamento

            WHERE id =
                :id

              AND conta_receber_id =
                :conta_id
        ");


                $stmt->execute([
                    ':valor_recebido' =>
                    $novoRecebidoParcela,

                    ':saldo' =>
                    $novoSaldoParcela,

                    ':status' =>
                    $statusParcela,

                    ':data_pagamento' =>
                    $dataPagamentoParcela,

                    ':id' =>
                    $parcelaId,

                    ':conta_id' =>
                    $contaId
                ]);
            }

            /*
             * 4. Atualizar conta.
             */

            $novoRecebido = round(
                (float)$conta['valor_recebido']
                    + $valor,
                2
            );

            $novoSaldo = round(
                $saldoAtual - $valor,
                2
            );

            if ($novoSaldo <= 0) {
                $novoSaldo = 0.0;

                $statusId =
                    $this->statusContaReceber(
                        'Recebido'
                    );

                $dataLiquidacao =
                    $dataRecebimento;
            } else {
                $statusId =
                    $this->statusContaReceber(
                        'Parcial'
                    );

                $dataLiquidacao = null;
            }

            $stmt = $this->pdo->prepare("
                UPDATE contas_receber
                SET
                    valor_recebido =
                        :valor_recebido,

                    saldo =
                        :saldo,

                    status_id =
                        :status_id,

                    data_pagamento =
                        :data_pagamento

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");

            $stmt->execute([
                ':valor_recebido' =>
                $novoRecebido,

                ':saldo' =>
                $novoSaldo,

                ':status_id' =>
                $statusId,

                ':data_pagamento' =>
                $dataLiquidacao,

                ':id' =>
                $contaId,

                ':empresa_id' =>
                $empresaId
            ]);

            // ==========================================
            // CONTABILIZAÇÃO DO RECEBIMENTO
            // ==========================================

            $this->contabilidade
                ->contabilizarRecebimento(
                    [
                        'empresa_id' =>
                        $empresaId,

                        'filial_id' =>
                        $filialId,

                        'centro_custo_id' =>
                        $centroCustoId,

                        'recebimento_id' =>
                        $recebimentoId,

                        'conta_receber_id' =>
                        $contaId,

                        'cliente_id' =>
                        (int)$conta['cliente_id'],

                        'usuario_id' =>
                        $usuarioId,

                        'destino' =>
                        $destino,

                        'valor' =>
                        $valor,

                        'data' =>
                        $dataRecebimento,

                        'numero_documento' =>
                        $this->textoOuNull(
                            $dados['numero_documento'] ?? null
                        ),

                        'descricao' =>
                        'Recebimento da conta #'
                            . $contaId
                            . ' - '
                            . $conta['cliente_nome']
                    ]
                );

            $contaDepois =
                $this->buscarContaAuditoria(
                    $contaId,
                    $empresaId
                );


            if (!$contaDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar a conta após o recebimento.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'contas_receber',
                $contaId,
                $dadosContaAntes,
                $this->dadosAuditoriaContaReceber(
                    $contaDepois
                )
            );


            /*
 * Auditar o recebimento criado.
 */
            $recebimentoAuditoria =
                $this->buscarRecebimentoAuditoria(
                    $recebimentoId,
                    $empresaId
                );


            if (!$recebimentoAuditoria) {
                throw new RuntimeException(
                    'Não foi possível carregar o recebimento para auditoria.'
                );
            }


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'recebimentos',
                $recebimentoId,
                $recebimentoAuditoria
            );


            /*
            * Auditar parcela, quando aplicável.
            */
            if (
                $parcelaId !== null
                && $dadosParcelaAntes !== null
            ) {

                $parcelaDepois =
                    $this->buscarParcelaAuditoria(
                        $parcelaId,
                        $contaId
                    );


                if ($parcelaDepois) {

                    $this->auditoria->atualizacao(
                        $empresaId,
                        $usuarioId,
                        'contas_receber_parcelas',
                        $parcelaId,
                        $dadosParcelaAntes,
                        $this->dadosAuditoriaParcelaReceber(
                            $parcelaDepois
                        )
                    );
                }
            }


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CONTAS_RECEBER',
                'RECEBER',
                'Recebimento registado na conta #'
                    . $contaId
                    . ' no valor de '
                    . number_format(
                        $valor,
                        2,
                        ',',
                        '.'
                    )
                    . ' MZN via '
                    . $destino,
                $contaId,
                $filialId
            );

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Se houver erro e o ficheiro tiver sido enviado, remove o ficheiro criado
            if ($caminhoComprovativo && file_exists(__DIR__ . '/../../' . $caminhoComprovativo)) {
                @unlink(__DIR__ . '/../../' . $caminhoComprovativo);
            }

            throw $e;
        }
    }

    // ==================================================
    // HISTÓRICO DE RECEBIMENTOS
    // ==================================================

    public function obterHistoricoRecebimentos(int $contaId, int $empresaId): array
    {
        $this->validarEmpresa($empresaId);

        if ($contaId <= 0) {
            throw new RuntimeException('Conta a receber inválida.');
        }

        $stmt = $this->pdo->prepare("
        SELECT 
            r.id,
            r.conta_receber_id,
            r.valor,
            r.data_recebimento,
            fp.nome AS forma_pagamento,
            r.observacoes AS observacao,
            r.comprovativo,
            u.nome AS usuario_nome
        FROM recebimentos r
        LEFT JOIN formas_pagamento fp ON fp.id = r.forma_pagamento_id
        LEFT JOIN usuarios u ON u.id = r.usuario_id
        WHERE r.conta_receber_id = :conta_id 
          AND r.empresa_id = :empresa_id
        ORDER BY r.data_recebimento DESC, r.id DESC
    ");
        $stmt->execute([
            ':conta_id' => $contaId,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ==================================================
    // ENTRADA NO CAIXA
    // ==================================================

    private function registarEntradaCaixa(
        array $dados
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT
                c.id,
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

        $caixa = $stmt->fetch(
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
                'O caixa selecionado não possui uma abertura válida.'
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
            $dados['data_recebimento']
                . ' '
                . date('H:i:s')
        ]);

        $stmt = $this->pdo->prepare("
            UPDATE caixas
            SET saldo_atual =
                saldo_atual + :valor

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
    // ENTRADA NO BANCO
    // ==================================================

    private function registarEntradaBanco(
        array $dados
    ): void {

        $empresaId =
            (int)($dados['empresa_id'] ?? 0);

        $bancoId =
            (int)($dados['banco_id'] ?? 0);

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);

        $contaId =
            (int)($dados['conta_id'] ?? 0);

        $valor =
            round(
                (float)($dados['valor'] ?? 0),
                2
            );


        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }


        if ($bancoId <= 0) {
            throw new RuntimeException(
                'Banco inválido.'
            );
        }


        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }


        if ($contaId <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida.'
            );
        }


        if ($valor <= 0) {
            throw new RuntimeException(
                'Valor do recebimento inválido.'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | BLOQUEAR BANCO
    |--------------------------------------------------------------------------
    */

        $stmt =
            $this->pdo->prepare("
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
            ':banco_id' =>
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
                'Banco não encontrado.'
            );
        }


        if (
            mb_strtolower(
                trim(
                    (string)$banco['status_nome']
                )
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

        $saldoAnterior =
            round(
                (float)$banco['saldo_atual'],
                2
            );


        $saldoPosterior =
            round(
                $saldoAnterior + $valor,
                2
            );


        /*
    |--------------------------------------------------------------------------
    | REGISTAR MOVIMENTO
    |--------------------------------------------------------------------------
    */

        $stmt =
            $this->pdo->prepare("
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

                'ENTRADA',
                'RECEBIMENTO',
                'CONTA_RECEBER',
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
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : (
                    !empty($banco['filial_id'])
                    ? (int)$banco['filial_id']
                    : null
                ),

            ':centro_custo_id' =>
            !empty($dados['centro_custo_id'])
                ? (int)$dados['centro_custo_id']
                : null,

            ':banco_id' =>
            $bancoId,

            ':usuario_id' =>
            $usuarioId,

            ':origem_id' =>
            $contaId,

            ':documento' =>
            $this->textoOuNull(
                $dados['documento']
                    ?? null
            ),

            ':referencia' =>
            $this->textoOuNull(
                $dados['referencia']
                    ?? null
            ),

            ':descricao' =>
            trim(
                (string)(
                    $dados['descricao']
                    ?? (
                        'Recebimento da conta #'
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
            $dados['data_recebimento']
                . ' '
                . date('H:i:s')
        ]);


        /*
    |--------------------------------------------------------------------------
    | ATUALIZAR SALDO DO BANCO
    |--------------------------------------------------------------------------
    */

        $stmt =
            $this->pdo->prepare("
            UPDATE bancos

            SET saldo_atual =
                :saldo_atual

            WHERE id =
                :banco_id

              AND empresa_id =
                :empresa_id
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
    // CLIENTES
    // ==================================================

    public function clientes(
        int $empresaId
    ): array {

        $this->validarEmpresa($empresaId);

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
    // FORMAS DE PAGAMENTO
    // ==================================================

    public function formasPagamento(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                id,
                nome

            FROM formas_pagamento

            ORDER BY nome ASC
        ");

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

        return [
            'id' =>
            !empty($dados['id'])
                ? (int)$dados['id']
                : null,

            'empresa_id' =>
            (int)($dados['empresa_id'] ?? 0),

            'usuario_id' =>
            (int)($dados['usuario_id'] ?? 0),

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

            'venda_id' =>
            !empty($dados['venda_id'])
                ? (int)$dados['venda_id']
                : null,

            'forma_pagamento_id' =>
            !empty($dados['forma_pagamento_id'])
                ? (int)$dados['forma_pagamento_id']
                : null,

            'numero_documento' =>
            $this->textoOuNull(
                $dados['numero_documento']
                    ?? null
            ),

            'data_emissao' =>
            trim(
                (string)(
                    $dados['data_emissao']
                    ?? ''
                )
            ),

            'data_vencimento' =>
            trim(
                (string)(
                    $dados['data_vencimento']
                    ?? ''
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

            'observacoes' =>
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            )
        ];
    }

    // ==================================================
    // VALIDAÇÕES DA CONTA
    // ==================================================

    private function validarDadosConta(
        array $dados
    ): void {

        $this->validarEmpresa(
            $dados['empresa_id']
        );

        if ($dados['cliente_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o cliente.'
            );
        }

        if ($dados['centro_custo_id'] === null) {
            throw new RuntimeException(
                'Selecione o centro de custo.'
            );
        }

        if ($dados['valor'] <= 0) {
            throw new RuntimeException(
                'O valor deve ser superior a zero.'
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

        $this->validarData(
            $dados['data_emissao'],
            'Data de emissão'
        );

        $this->validarData(
            $dados['data_vencimento'],
            'Data de vencimento'
        );

        if (
            $dados['data_vencimento']
            < $dados['data_emissao']
        ) {
            throw new RuntimeException(
                'A data de vencimento não pode ser anterior à emissão.'
            );
        }

        $total = $this->calcularTotal(
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
    // VALIDAR CLIENTE
    // ==================================================

    private function validarCliente(
        int $clienteId,
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
            ':id' => $clienteId,
            ':empresa_id' => $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Cliente não encontrado.'
            );
        }
    }

    // ==================================================
    // VALIDAR FILIAL
    // ==================================================

    private function validarFilial(
        ?int $filialId,
        int $empresaId
    ): void {

        if ($filialId === null) {
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

    private function validarCentroCusto(
        ?int $centroCustoId,
        int $empresaId,
        ?int $filialId
    ): void {

        if ($centroCustoId === null) {
            throw new RuntimeException(
                'Selecione o centro de custo.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                cc.id,
                cc.filial_id,
                cc.tipo,
                status.nome AS status

            FROM centros_custo cc

            INNER JOIN status
                ON status.id = cc.status_id
                AND status.tipo = 'centro_custo'

            WHERE cc.id = :id
              AND cc.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $centroCustoId,
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
                    (string)$centro['status']
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
                ['RECEITA', 'MISTO'],
                true
            )
        ) {
            throw new RuntimeException(
                'Para contas a receber, use um centro do tipo Receita ou Misto.'
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

    // ==================================================
    // FORMA DE PAGAMENTO
    // ==================================================

    private function validarFormaPagamento(
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

        $stmt = $this->pdo->prepare("
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
    // STATUS
    // ==================================================

    private function statusContaReceber(
        string $nome
    ): int {

        return $this->statusPorTipoNome(
            'conta_receber',
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

    private function calcularTotal(
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

    private function buscarContaAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT *

            FROM contas_receber

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


    private function buscarRecebimentoAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT *

            FROM recebimentos

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


    private function buscarParcelaAuditoria(
        int $id,
        int $contaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT *

            FROM contas_receber_parcelas

            WHERE id = :id
              AND conta_receber_id = :conta_id

            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $id,

            ':conta_id' =>
            $contaId
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: null;
    }


    private function dadosAuditoriaContaReceber(
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

            'cliente_id' =>
            isset($conta['cliente_id'])
                ? (int)$conta['cliente_id']
                : null,

            'venda_id' =>
            !empty($conta['venda_id'])
                ? (int)$conta['venda_id']
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

            'data_emissao' =>
            $conta['data_emissao']
                ?? null,

            'data_vencimento' =>
            $conta['data_vencimento']
                ?? null,

            'data_pagamento' =>
            $conta['data_pagamento']
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

            'valor_recebido' =>
            isset($conta['valor_recebido'])
                ? (float)$conta['valor_recebido']
                : 0.0,

            'saldo' =>
            isset($conta['saldo'])
                ? (float)$conta['saldo']
                : 0.0,

            'observacoes' =>
            $conta['observacoes']
                ?? null
        ];
    }


    private function dadosAuditoriaParcelaReceber(
        array $parcela
    ): array {

        return [
            'id' =>
            isset($parcela['id'])
                ? (int)$parcela['id']
                : null,

            'conta_receber_id' =>
            isset($parcela['conta_receber_id'])
                ? (int)$parcela['conta_receber_id']
                : null,

            'numero_parcela' =>
            isset($parcela['numero_parcela'])
                ? (int)$parcela['numero_parcela']
                : null,

            'total_parcelas' =>
            isset($parcela['total_parcelas'])
                ? (int)$parcela['total_parcelas']
                : null,

            'data_vencimento' =>
            $parcela['data_vencimento']
                ?? null,

            'valor' =>
            isset($parcela['valor'])
                ? (float)$parcela['valor']
                : 0.0,

            'valor_recebido' =>
            isset($parcela['valor_recebido'])
                ? (float)$parcela['valor_recebido']
                : 0.0,

            'saldo' =>
            isset($parcela['saldo'])
                ? (float)$parcela['saldo']
                : 0.0,

            'status' =>
            $parcela['status']
                ?? null,

            'data_pagamento' =>
            $parcela['data_pagamento']
                ?? null
        ];
    }

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
                'Empresa inválida para a conta a receber.'
            );
        }
    }
}
