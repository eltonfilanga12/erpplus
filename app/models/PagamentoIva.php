<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/ContabilidadeService.php';

final class PagamentoIva
{
    private PDO $db;
    private ContabilidadeService $contabilidade;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->contabilidade = new ContabilidadeService($db);
    }

    public function dadosIniciais(int $empresaId, ?int $filialId): array
    {
        return [
            'periodos' => $this->listarPeriodos($empresaId),
            'caixas' => $this->listarCaixas($empresaId, $filialId),
            'bancos' => $this->listarBancos($empresaId, $filialId),
            'centros' => $this->listarCentrosCusto($empresaId, $filialId)
        ];
    }

    public function listar(int $empresaId, ?int $filialId): array
    {
        $where = ['a.empresa_id = :empresa_id'];
        $params = [':empresa_id' => $empresaId];

        if ($filialId !== null) {
            $where[] = 'a.filial_id = :filial_id';
            $params[':filial_id'] = $filialId;
        }

        $sql = "
            SELECT
                a.*,
                p.nome AS periodo_nome,
                f.nome AS filial_nome,
                s.nome AS status_nome,
                CASE
                    WHEN a.saldo_pagar > 0 AND CURDATE() > a.data_limite THEN 'VENCIDO'
                    ELSE UPPER(s.nome)
                END AS situacao,
                (SELECT COUNT(*) FROM pagamentos_iva pg WHERE pg.apuramento_iva_id = a.id) AS quantidade_pagamentos
            FROM apuramentos_iva a
            LEFT JOIN periodos_contabilisticos p ON p.id = a.periodo_id
            LEFT JOIN filiais f ON f.id = a.filial_id
            INNER JOIN status s ON s.id = a.status_id AND s.tipo = 'apuramento_iva'
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.data_fim DESC, a.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pagamentos(int $empresaId, int $apuramentoId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                COALESCE(c.nome, CONCAT(b.nome_banco, ' - ', b.conta)) AS conta_nome
            FROM pagamentos_iva p
            LEFT JOIN caixas c ON c.id = p.caixa_id
            LEFT JOIN bancos b ON b.id = p.banco_id
            WHERE p.empresa_id = :empresa_id
              AND p.apuramento_iva_id = :apuramento_id
            ORDER BY p.data_pagamento DESC, p.id DESC
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':apuramento_id' => $apuramentoId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function calcular(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
        ?int $filialId
    ): array {
        $this->validarDatas($dataInicio, $dataFim);


        $filialId = $this->validarFilialEmpresa(
            $empresaId,
            $filialId
        );

        $filialId = $this->resolverFilialDoPeriodo(
            $empresaId,
            $dataInicio,
            $dataFim,
            $filialId
        );

        $registos = $this->saldosIvaPorConta(
            $empresaId,
            $dataInicio,
            $dataFim,
            $filialId
        );

        $liquidado = 0.0;
        $dedutivel = 0.0;

        foreach ($registos as $registo) {
            if ($registo['tipo_iva'] === 'IVA_LIQUIDADO') {
                $liquidado += (float)$registo['saldo'];
            } elseif ($registo['tipo_iva'] === 'IVA_DEDUTIVEL') {
                $dedutivel += (float)$registo['saldo'];
            }
        }

        $liquidado = round($liquidado, 2);
        $dedutivel = round($dedutivel, 2);
        $saldoPeriodo = round($liquidado - $dedutivel, 2);

        $creditoAnterior = $this->creditoFiscalAnterior(
            $empresaId,
            $dataInicio,
            $filialId
        );

        $creditoUtilizado = min(
            $creditoAnterior,
            max($saldoPeriodo, 0)
        );

        $creditoGerado = max(-$saldoPeriodo, 0);

        $ivaPagar = max(
            round($saldoPeriodo - $creditoAnterior, 2),
            0
        );

        $creditoSeguinte = round(
            max(
                $creditoAnterior - max($saldoPeriodo, 0),
                0
            ) + $creditoGerado,
            2
        );

        return [
            'filial_id' => $filialId,
            'iva_liquidado' => $liquidado,
            'iva_dedutivel' => $dedutivel,
            'saldo_periodo' => $saldoPeriodo,
            'credito_fiscal_anterior' => $creditoAnterior,
            'credito_fiscal_utilizado' => round($creditoUtilizado, 2),
            'credito_fiscal_gerado' => round($creditoGerado, 2),
            'credito_fiscal_seguinte' => $creditoSeguinte,
            'iva_pagar' => $ivaPagar,
            'iva_recuperar' => $creditoSeguinte
        ];
    }

    private function creditoFiscalAnterior(
        int $empresaId,
        string $dataInicio,
        ?int $filialId
    ): float {
        $stmt = $this->db->prepare("
            SELECT COALESCE(a.credito_fiscal_seguinte, a.iva_recuperar, 0)
            FROM apuramentos_iva a
            INNER JOIN status s
                ON s.id = a.status_id
               AND s.tipo = 'apuramento_iva'
            WHERE a.empresa_id = :empresa_id
              AND a.data_fim < :data_inicio
              AND (
                    (:filial_id_nula IS NULL AND a.filial_id IS NULL)
                    OR a.filial_id = :filial_id_igual
              )
              AND UPPER(s.nome) NOT IN ('ANULADO', 'CANCELADO')
            ORDER BY a.data_fim DESC, a.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':data_inicio' => $dataInicio,
            ':filial_id_nula' => $filialId,
            ':filial_id_igual' => $filialId
        ]);

        return round(max((float)($stmt->fetchColumn() ?: 0), 0), 2);
    }

    private function saldosIvaPorConta(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
        ?int $filialId
    ): array {
        $statusContabilizado = $this->contabilidade->statusLancamentoId('Contabilizado');
        $where = [
            'l.empresa_id = :empresa_id',
            'l.status_id = :status_id',
            'l.data_lancamento BETWEEN :data_inicio AND :data_fim',
            "l.origem NOT IN ('APURAMENTO_IVA', 'PAGAMENTO_IVA')"
        ];
        $params = [
            ':empresa_id' => $empresaId,
            ':status_id' => $statusContabilizado,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim
        ];

        if ($filialId !== null) {
            $where[] = 'l.filial_id = :filial_id';
            $params[':filial_id'] = $filialId;
        }

        $stmt = $this->db->prepare("
            SELECT
                c.id AS conta_id,
                c.codigo AS conta_codigo,
                c.nome AS conta_nome,
                CASE
                    WHEN COALESCE(c.integracao_codigo, pai.integracao_codigo, '') LIKE 'IVA_LIQUIDADO%'
                         OR LOWER(c.nome) LIKE '%iva liquid%'
                    THEN 'IVA_LIQUIDADO'
                    WHEN COALESCE(c.integracao_codigo, pai.integracao_codigo, '') LIKE 'IVA_DEDUTIVEL%'
                         OR LOWER(c.nome) LIKE '%iva dedut%'
                    THEN 'IVA_DEDUTIVEL'
                    ELSE 'OUTRO'
                END AS tipo_iva,
                CASE
                    WHEN COALESCE(c.integracao_codigo, pai.integracao_codigo, '') LIKE 'IVA_LIQUIDADO%'
                         OR LOWER(c.nome) LIKE '%iva liquid%'
                    THEN SUM(i.credito - i.debito)
                    WHEN COALESCE(c.integracao_codigo, pai.integracao_codigo, '') LIKE 'IVA_DEDUTIVEL%'
                         OR LOWER(c.nome) LIKE '%iva dedut%'
                    THEN SUM(i.debito - i.credito)
                    ELSE 0
                END AS saldo
            FROM lancamento_contabilistico_itens i
            INNER JOIN lancamentos_contabilisticos l ON l.id = i.lancamento_id
            INNER JOIN contas_contabilisticas c
                ON c.id = i.conta_contabil_id AND c.empresa_id = l.empresa_id
            LEFT JOIN contas_contabilisticas pai
                ON pai.id = c.conta_pai_id AND pai.empresa_id = c.empresa_id
            WHERE " . implode(' AND ', $where) . "
              AND (
                    COALESCE(c.integracao_codigo, pai.integracao_codigo, '') LIKE 'IVA_LIQUIDADO%'
                 OR COALESCE(c.integracao_codigo, pai.integracao_codigo, '') LIKE 'IVA_DEDUTIVEL%'
                 OR LOWER(c.nome) LIKE '%iva liquid%'
                 OR LOWER(c.nome) LIKE '%iva dedut%'
              )
            GROUP BY c.id, c.codigo, c.nome, c.integracao_codigo, pai.integracao_codigo
            HAVING ABS(saldo) >= 0.005
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    /**
     * Identifica a filial dos movimentos de IVA do período.
     *
     * Se a sessão já tiver filial, respeita essa filial.
     * Se não tiver, identifica automaticamente a única filial
     * presente nos movimentos de IVA elegíveis.
     *
     * Se existirem várias filiais ou movimentos de IVA sem filial,
     * exige que a situação seja resolvida antes de calcular.
     */
    private function resolverFilialDoPeriodo(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
        ?int $filialId
    ): ?int {
        if ($filialId !== null) {
            return $filialId;
        }

        $statusId = $this->contabilidade
            ->statusLancamentoId('Contabilizado');

        $stmt = $this->db->prepare("
        SELECT
            COUNT(DISTINCT l.filial_id) AS total_filiais,
            MAX(l.filial_id) AS filial_id,
            COUNT(
                DISTINCT CASE
                    WHEN l.filial_id IS NULL THEN l.id
                    ELSE NULL
                END
            ) AS movimentos_sem_filial
        FROM lancamentos_contabilisticos l
        INNER JOIN lancamento_contabilistico_itens i
            ON i.lancamento_id = l.id
        INNER JOIN contas_contabilisticas c
            ON c.id = i.conta_contabil_id
           AND c.empresa_id = l.empresa_id
        LEFT JOIN contas_contabilisticas pai
            ON pai.id = c.conta_pai_id
           AND pai.empresa_id = c.empresa_id
        WHERE l.empresa_id = :empresa_id
          AND l.status_id = :status_id
          AND l.data_lancamento BETWEEN :data_inicio AND :data_fim

          AND COALESCE(l.origem, '') NOT IN (
              'APURAMENTO_IVA',
              'PAGAMENTO_IVA'
          )

          AND LOWER(COALESCE(l.descricao, ''))
              NOT LIKE '%apuramento do iva%'

          AND LOWER(COALESCE(l.descricao, ''))
              NOT LIKE '%pagamento do iva%'

          AND (
                COALESCE(
                    c.integracao_codigo,
                    pai.integracao_codigo,
                    ''
                ) LIKE 'IVA_LIQUIDADO%'

                OR COALESCE(
                    c.integracao_codigo,
                    pai.integracao_codigo,
                    ''
                ) LIKE 'IVA_DEDUTIVEL%'

                OR LOWER(c.nome) LIKE '%iva liquid%'

                OR LOWER(c.nome) LIKE '%iva dedut%'
          )
    ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':status_id' => $statusId,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalFiliais = (int)($resultado['total_filiais'] ?? 0);
        $movimentosSemFilial = (int)(
            $resultado['movimentos_sem_filial'] ?? 0
        );

        if ($totalFiliais > 1) {
            throw new RuntimeException(
                'Existem movimentos de IVA de várias filiais neste período. '
                    . 'Selecione a filial antes de calcular.'
            );
        }

        if ($movimentosSemFilial > 0) {
            throw new RuntimeException(
                'Existem movimentos de IVA sem filial neste período. '
                    . 'Verifique esses lançamentos antes de continuar.'
            );
        }

        if ($totalFiliais === 1) {
            return (int)$resultado['filial_id'];
        }

        // Não existem movimentos de IVA elegíveis no período.
        return null;
    }

    public function apurar(int $empresaId, ?int $filialId, int $usuarioId, array $dados): int
    {
        $periodicidade = strtoupper(trim((string)($dados['periodicidade'] ?? 'MENSAL')));
        if (!in_array($periodicidade, ['MENSAL', 'TRIMESTRAL'], true)) {
            throw new InvalidArgumentException('Periodicidade do IVA inválida.');
        }

        $dataInicio = trim((string)($dados['data_inicio'] ?? ''));
        $dataFim = trim((string)($dados['data_fim'] ?? ''));
        $this->validarDatas($dataInicio, $dataFim);
        $periodoId = !empty($dados['periodo_id']) ? (int)$dados['periodo_id'] : null;

        $filialId = $this->validarFilialEmpresa(
            $empresaId,
            $filialId
        );

        $filialId = $this->resolverFilialDoPeriodo(
            $empresaId,
            $dataInicio,
            $dataFim,
            $filialId
        );

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM apuramentos_iva
                WHERE empresa_id = :empresa_id
                  AND data_inicio <= :data_fim
                  AND data_fim >= :data_inicio
                  AND (
                    (:filial_id_nula IS NULL AND filial_id IS NULL)
                    OR filial_id = :filial_id_igual
                )
                LIMIT 1 FOR UPDATE
            ");
            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':filial_id_nula'  => $filialId,
                ':filial_id_igual' => $filialId,
                ':data_inicio' => $dataInicio,
                ':data_fim' => $dataFim
            ]);
            if ($stmt->fetchColumn()) {
                throw new RuntimeException('Este período de IVA já foi apurado.');
            }

            $valores = $this->calcular($empresaId, $dataInicio, $dataFim, $filialId);
            $statusNome = $valores['iva_pagar'] > 0
                ? 'Pendente'
                : ($valores['iva_recuperar'] > 0 ? 'A recuperar' : 'Sem movimento');
            $statusId = $this->statusId('apuramento_iva', $statusNome);
            $dataLimite = (new DateTimeImmutable($dataFim))
                ->modify('last day of next month')->format('Y-m-d');

            $stmt = $this->db->prepare("
                INSERT INTO apuramentos_iva
                (empresa_id, filial_id, periodo_id, periodicidade, data_inicio, data_fim,
                 data_limite, iva_liquidado, iva_dedutivel, saldo_periodo,
                 credito_fiscal_anterior, credito_fiscal_utilizado,
                 credito_fiscal_gerado, credito_fiscal_seguinte,
                 iva_pagar, iva_recuperar,
                 valor_pago, saldo_pagar, status_id, criado_por)
                VALUES
                (:empresa_id, :filial_id, :periodo_id, :periodicidade, :data_inicio, :data_fim,
                 :data_limite, :iva_liquidado, :iva_dedutivel, :saldo_periodo,
                 :credito_fiscal_anterior, :credito_fiscal_utilizado,
                 :credito_fiscal_gerado, :credito_fiscal_seguinte,
                 :iva_pagar, :iva_recuperar,
                 0, :saldo_pagar, :status_id, :criado_por)
            ");
            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':filial_id' => $filialId,
                ':periodo_id' => $periodoId,
                ':periodicidade' => $periodicidade,
                ':data_inicio' => $dataInicio,
                ':data_fim' => $dataFim,
                ':data_limite' => $dataLimite,
                ':iva_liquidado' => $valores['iva_liquidado'],
                ':iva_dedutivel' => $valores['iva_dedutivel'],
                ':saldo_periodo' => $valores['saldo_periodo'],
                ':credito_fiscal_anterior' => $valores['credito_fiscal_anterior'],
                ':credito_fiscal_utilizado' => $valores['credito_fiscal_utilizado'],
                ':credito_fiscal_gerado' => $valores['credito_fiscal_gerado'],
                ':credito_fiscal_seguinte' => $valores['credito_fiscal_seguinte'],
                ':iva_pagar' => $valores['iva_pagar'],
                ':iva_recuperar' => $valores['iva_recuperar'],
                ':saldo_pagar' => $valores['iva_pagar'],
                ':status_id' => $statusId,
                ':criado_por' => $usuarioId
            ]);
            $apuramentoId = (int)$this->db->lastInsertId();

            if (
                $valores['iva_liquidado'] > 0
                || $valores['iva_dedutivel'] > 0
                || $valores['credito_fiscal_utilizado'] > 0
                || $valores['credito_fiscal_gerado'] > 0
            ) {
                $this->assegurarContasIntegracaoIva($empresaId);
                $lancamentoId = $this->contabilizarApuramento(
                    $apuramentoId,
                    $empresaId,
                    $filialId,
                    $usuarioId,
                    $dataInicio,
                    $dataFim,
                    $valores
                );
                $stmt = $this->db->prepare("
                    UPDATE apuramentos_iva SET lancamento_apuramento_id = :lancamento_id
                    WHERE id = :id AND empresa_id = :empresa_id
                ");
                $stmt->execute([
                    ':lancamento_id' => $lancamentoId,
                    ':id' => $apuramentoId,
                    ':empresa_id' => $empresaId
                ]);
            }

            $this->db->commit();
            return $apuramentoId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }


    private function validarFilialEmpresa(
        int $empresaId,
        ?int $filialId
    ): ?int {
        if ($filialId === null) {
            return null;
        }

        $stmt = $this->db->prepare("
        SELECT id
        FROM filiais
        WHERE id = :filial_id
          AND empresa_id = :empresa_id
        LIMIT 1
    ");

        $stmt->execute([
            ':filial_id' => $filialId,
            ':empresa_id' => $empresaId
        ]);

        if ($stmt->fetchColumn() === false) {
            throw new RuntimeException(
                'A filial selecionada não pertence à empresa.'
            );
        }

        return $filialId;
    }

    public function pagar(int $empresaId, ?int $filialId, int $usuarioId, array $dados): int
    {
        $apuramentoId = (int)($dados['apuramento_id'] ?? 0);
        $fonte = strtoupper(trim((string)($dados['fonte'] ?? '')));
        $valor = round((float)($dados['valor'] ?? 0), 2);
        $dataPagamento = trim((string)($dados['data_pagamento'] ?? ''));
        $referencia = trim((string)($dados['referencia'] ?? ''));


        if ($apuramentoId <= 0 || !in_array($fonte, ['CAIXA', 'BANCO'], true)) {
            throw new InvalidArgumentException('Dados do pagamento do IVA inválidos.');
        }
        if ($valor <= 0 || $referencia === '') {
            throw new InvalidArgumentException('Informe um valor e uma referência válidos.');
        }
        if (!$this->dataHoraValida($dataPagamento)) {
            throw new InvalidArgumentException('Data do pagamento inválida.');
        }

        $dataPagamento =
            str_replace('T', ' ', $dataPagamento);

        if (strlen($dataPagamento) === 16) {
            $dataPagamento .= ':00';
        }

        $caminhoComprovativo = null;
        if (isset($dados['FILES']['comprovativo']) && $dados['FILES']['comprovativo']['error'] === UPLOAD_ERR_OK) {
            $file = $dados['FILES']['comprovativo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $extsPermitidas = ['pdf', 'png', 'jpg', 'jpeg'];

            if (!in_array($ext, $extsPermitidas, true)) {
                throw new InvalidArgumentException('Formato de comprovativo inválido. Use PDF, PNG ou JPG.');
            }

            $diretorio = dirname(__DIR__, 2)
                . '/public/uploads/comprovativos/';
            if (!is_dir($diretorio)) {
                mkdir($diretorio, 0755, true);
            }

            $nomeFicheiro = 'comprovativo_iva_' . time() . '_' . uniqid() . '.' . $ext;
            $caminhoCompleto = $diretorio . $nomeFicheiro;

            if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
                $caminhoComprovativo = 'public/uploads/comprovativos/' . $nomeFicheiro;
            }
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM apuramentos_iva
                WHERE id = :id
                AND empresa_id = :empresa_id
                FOR UPDATE
            ");

            $stmt->execute([
                ':id' => $apuramentoId,
                ':empresa_id' => $empresaId
            ]);

            $apuramento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$apuramento) {
                throw new RuntimeException(
                    'Apuramento do IVA não encontrado.'
                );
            }

            $filialPagamento = $apuramento['filial_id'] !== null
                ? (int)$apuramento['filial_id']
                : null;

            /*
            * Se o utilizador estiver associado a uma filial específica,
            * não permitir o pagamento de apuramentos de outra filial.
            */
            if (
                $filialId !== null
                && $filialPagamento !== $filialId
            ) {
                throw new RuntimeException(
                    'Não tem autorização para pagar o IVA desta filial.'
                );
            }

            /*
            * Resolver o centro de custo com base na filial do apuramento.
            */
            $centroCustoId = $this->resolverCentroCustoId(
                $empresaId,
                $filialPagamento,
                !empty($dados['centro_custo_id'])
                    ? (int)$dados['centro_custo_id']
                    : null
            );

            $dados['centro_custo_id'] = $centroCustoId;

            $stmt = $this->db->prepare("
            INSERT INTO pagamentos_iva
            (apuramento_iva_id, empresa_id, filial_id, usuario_id, fonte, caixa_id, banco_id,
             valor, data_pagamento, referencia, documento, observacoes, comprovativo)
            VALUES
            (:apuramento_id, :empresa_id, :filial_id, :usuario_id, :fonte, :caixa_id, :banco_id,
             :valor, :data_pagamento, :referencia, :documento, :observacoes, :comprovativo)
        ");
            $stmt->execute([
                ':apuramento_id' => $apuramentoId,
                ':empresa_id'    => $empresaId,
                ':filial_id'     => $filialPagamento,
                ':usuario_id'    => $usuarioId,
                ':fonte'         => $fonte,
                ':caixa_id'      => $fonte === 'CAIXA' ? (int)($dados['caixa_id'] ?? 0) : null,
                ':banco_id'      => $fonte === 'BANCO' ? (int)($dados['banco_id'] ?? 0) : null,
                ':valor'         => $valor,
                ':data_pagamento' => $dataPagamento,
                ':referencia'    => $referencia,
                ':documento'     => trim((string)($dados['documento'] ?? '')) ?: null,
                ':observacoes'   => trim((string)($dados['observacoes'] ?? '')) ?: null,
                ':comprovativo'  => $caminhoComprovativo
            ]);
            $pagamentoId = (int)$this->db->lastInsertId();

            $movimentoId = $fonte === 'CAIXA'
                ? $this->registarSaidaCaixa($pagamentoId, $empresaId, $filialPagamento, $usuarioId, $dados, $valor, $dataPagamento)
                : $this->registarSaidaBanco($pagamentoId, $empresaId, $filialPagamento, $usuarioId, $dados, $valor, $dataPagamento, $referencia);

            $lancamentoId = $this->contabilizarPagamento(
                $pagamentoId,
                $empresaId,
                $filialPagamento,
                $usuarioId,
                substr($dataPagamento, 0, 10),
                $fonte,
                $valor
            );

            $campoMovimento = $fonte === 'CAIXA' ? 'movimento_caixa_id' : 'movimento_bancario_id';
            $stmt = $this->db->prepare("
                UPDATE pagamentos_iva
                SET {$campoMovimento} = :movimento_id,
                    lancamento_contabilistico_id = :lancamento_id
                WHERE id = :id AND empresa_id = :empresa_id
            ");
            $stmt->execute([
                ':movimento_id' => $movimentoId,
                ':lancamento_id' => $lancamentoId,
                ':id' => $pagamentoId,
                ':empresa_id' => $empresaId
            ]);

            $novoPago = round((float)$apuramento['valor_pago'] + $valor, 2);
            $novoSaldo = max(round((float)$apuramento['iva_pagar'] - $novoPago, 2), 0);
            $novoStatus = $this->statusId('apuramento_iva', $novoSaldo <= 0.009 ? 'Pago' : 'Parcial');
            $stmt = $this->db->prepare("
                UPDATE apuramentos_iva
                SET valor_pago = :valor_pago, saldo_pagar = :saldo, status_id = :status_id
                WHERE id = :id AND empresa_id = :empresa_id
            ");
            $stmt->execute([
                ':valor_pago' => $novoPago,
                ':saldo' => $novoSaldo,
                ':status_id' => $novoStatus,
                ':id' => $apuramentoId,
                ':empresa_id' => $empresaId
            ]);

            $this->db->commit();
            return $pagamentoId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function anular(
        int $empresaId,
        ?int $filialId,
        int $apuramentoId
    ): void {
        if ($apuramentoId <= 0) {
            throw new InvalidArgumentException(
                'Apuramento do IVA inválido.'
            );
        }

        $this->db->beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | Procurar e bloquear o apuramento
        |--------------------------------------------------------------------------
        */

            $stmt = $this->db->prepare("
                SELECT
                    id,
                    filial_id,
                    lancamento_apuramento_id,
                    valor_pago
                FROM apuramentos_iva
                WHERE id = :apuramento_id
                AND empresa_id = :empresa_id
                AND (
                        (:filial_id_nula IS NULL AND filial_id IS NULL)
                        OR filial_id = :filial_id_igual
                )
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->execute([
                ':apuramento_id'   => $apuramentoId,
                ':empresa_id'      => $empresaId,
                ':filial_id_nula'  => $filialId,
                ':filial_id_igual' => $filialId
            ]);

            $apuramento =
                $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$apuramento) {
                throw new RuntimeException(
                    'Apuramento do IVA não encontrado.'
                );
            }

            $filialApuramento = $apuramento['filial_id'] !== null
                ? (int)$apuramento['filial_id']
                : null;

            if (
                $filialId !== null
                && $filialApuramento !== $filialId
            ) {
                throw new RuntimeException(
                    'Não tem autorização para anular o apuramento desta filial.'
                );
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM apuramentos_iva
                WHERE empresa_id = :empresa_id
                  AND data_inicio > (
                      SELECT data_fim FROM apuramentos_iva WHERE id = :apuramento_id
                  )
                  AND (
                        (:filial_id_nula IS NULL AND filial_id IS NULL)
                        OR filial_id = :filial_id_igual
                  )
            ");
            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':apuramento_id' => $apuramentoId,
                ':filial_id_nula' => $filialId,
                ':filial_id_igual' => $filialId
            ]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException(
                    'Não pode anular este apuramento porque existe um período posterior que depende do crédito fiscal transportado.'
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Verificar pagamentos
        |--------------------------------------------------------------------------
        */

            $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM pagamentos_iva
            WHERE apuramento_iva_id = :apuramento_id
              AND empresa_id = :empresa_id
        ");

            $stmt->execute([
                ':apuramento_id' => $apuramentoId,
                ':empresa_id'    => $empresaId
            ]);

            $quantidadePagamentos =
                (int)$stmt->fetchColumn();

            if (
                $quantidadePagamentos > 0
                || (float)$apuramento['valor_pago'] > 0
            ) {
                throw new RuntimeException(
                    'Este apuramento possui pagamentos. Primeiro deve estornar os pagamentos do IVA.'
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Eliminar o lançamento contabilístico do apuramento
        |--------------------------------------------------------------------------
        */

            $lancamentoId =
                (int)(
                    $apuramento['lancamento_apuramento_id']
                    ?? 0
                );

            if ($lancamentoId > 0) {

                $stmt = $this->db->prepare("
                DELETE FROM lancamento_contabilistico_itens
                WHERE lancamento_id = :lancamento_id
            ");

                $stmt->execute([
                    ':lancamento_id' => $lancamentoId
                ]);

                $stmt = $this->db->prepare("
                DELETE FROM lancamentos_contabilisticos
                WHERE id = :lancamento_id
                  AND empresa_id = :empresa_id
            ");

                $stmt->execute([
                    ':lancamento_id' => $lancamentoId,
                    ':empresa_id'    => $empresaId
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Eliminar o apuramento
        |--------------------------------------------------------------------------
        */

            $stmt = $this->db->prepare("
            DELETE FROM apuramentos_iva
            WHERE id = :apuramento_id
              AND empresa_id = :empresa_id
        ");

            $stmt->execute([
                ':apuramento_id' => $apuramentoId,
                ':empresa_id'    => $empresaId
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível anular o apuramento do IVA.'
                );
            }

            $this->db->commit();
        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    private function contabilizarApuramento(
        int $apuramentoId,
        int $empresaId,
        ?int $filialId,
        int $usuarioId,
        string $dataInicio,
        string $dataFim,
        array $valores
    ): int {
        $contexto = $this->contabilidade->contextoData($empresaId, $dataFim);
        $diario = $this->contabilidade->diarioPorTipo($empresaId, 'IMPOSTOS');
        $itens = [];

        foreach ($this->saldosIvaPorConta($empresaId, $dataInicio, $dataFim, $filialId) as $saldo) {
            $valor = round((float)$saldo['saldo'], 2);
            if ($valor <= 0) {
                continue;
            }
            $itens[] = $saldo['tipo_iva'] === 'IVA_LIQUIDADO'
                ? $this->item((int)$saldo['conta_id'], 'Apuramento do IVA liquidado', $valor, 0)
                : $this->item((int)$saldo['conta_id'], 'Apuramento do IVA dedutível', 0, $valor);
        }
        if ($valores['credito_fiscal_utilizado'] > 0) {
            $conta = $this->contabilidade->contaIntegracao($empresaId, 'IVA_RECUPERAR');
            $itens[] = $this->item(
                (int)$conta['id'],
                'Crédito fiscal utilizado no período',
                0,
                $valores['credito_fiscal_utilizado']
            );
        }
        if ($valores['credito_fiscal_gerado'] > 0) {
            $conta = $this->contabilidade->contaIntegracao($empresaId, 'IVA_RECUPERAR');
            $itens[] = $this->item(
                (int)$conta['id'],
                'Crédito fiscal gerado no período',
                $valores['credito_fiscal_gerado'],
                0
            );
        }
        if ($valores['iva_pagar'] > 0) {
            $conta = $this->contabilidade->contaIntegracao($empresaId, 'IVA_APURADO');
            $itens[] = $this->item((int)$conta['id'], 'IVA apurado a pagar', 0, $valores['iva_pagar']);
        }

        return $this->contabilidade->criarLancamento([
            'empresa_id' => $empresaId,
            'filial_id' => $filialId,
            'exercicio_id' => (int)$contexto['exercicio_id'],
            'periodo_id' => (int)$contexto['periodo_id'],
            'diario_id' => (int)$diario['id'],
            'numero_documento' => 'IVA-AP-' . $apuramentoId,
            'referencia' => 'APURAMENTO-IVA-' . $apuramentoId,
            'data_lancamento' => $dataFim,
            'data_documento' => $dataFim,
            'descricao' => 'Apuramento do IVA #' . $apuramentoId,
            'origem' => 'APURAMENTO_IVA',
            'origem_id' => $apuramentoId,
            'usuario_id' => $usuarioId
        ], $itens);
    }

    private function assegurarContasIntegracaoIva(int $empresaId): void
    {
        $mapeamentos = [
            'IVA_APURADO' => '4.4.3.7.1',
            'IVA_RECUPERAR' => '4.4.3.8'
        ];

        foreach ($mapeamentos as $integracao => $codigo) {
            $stmt = $this->db->prepare("
                SELECT id
                FROM contas_contabilisticas
                WHERE empresa_id = :empresa_id
                  AND integracao_codigo = :integracao
                LIMIT 1
            ");
            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':integracao' => $integracao
            ]);
            if ($stmt->fetchColumn()) {
                continue;
            }

            $stmt = $this->db->prepare("
                UPDATE contas_contabilisticas
                SET integracao_codigo = :integracao
                WHERE empresa_id = :empresa_id
                  AND codigo = :codigo
                  AND tipo = 'ANALITICA'
                  AND aceita_lancamento = 1
                LIMIT 1
            ");
            $stmt->execute([
                ':integracao' => $integracao,
                ':empresa_id' => $empresaId,
                ':codigo' => $codigo
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    "A conta padrão {$codigo} para {$integracao} não existe ou não aceita lançamentos. Corrija o plano de contas na interface administrativa."
                );
            }
        }
    }

    private function contabilizarPagamento(
        int $pagamentoId,
        int $empresaId,
        ?int $filialId,
        int $usuarioId,
        string $data,
        string $fonte,
        float $valor
    ): int {
        $contexto = $this->contabilidade->contextoData($empresaId, $data);
        $diario = $this->contabilidade->diarioPorTipo($empresaId, $fonte === 'CAIXA' ? 'CAIXA' : 'BANCOS');
        $iva = $this->contabilidade->contaIntegracao($empresaId, 'IVA_APURADO');
        $financeira = $this->contabilidade->contaIntegracao($empresaId, $fonte === 'CAIXA' ? 'CAIXA' : 'BANCOS');

        return $this->contabilidade->criarLancamento([
            'empresa_id' => $empresaId,
            'filial_id' => $filialId,
            'exercicio_id' => (int)$contexto['exercicio_id'],
            'periodo_id' => (int)$contexto['periodo_id'],
            'diario_id' => (int)$diario['id'],
            'numero_documento' => 'IVA-PG-' . $pagamentoId,
            'referencia' => 'PAGAMENTO-IVA-' . $pagamentoId,
            'data_lancamento' => $data,
            'data_documento' => $data,
            'descricao' => 'Pagamento do IVA #' . $pagamentoId,
            'origem' => 'PAGAMENTO_IVA',
            'origem_id' => $pagamentoId,
            'usuario_id' => $usuarioId
        ], [
            $this->item((int)$iva['id'], 'Liquidação do IVA a pagar', $valor, 0),
            $this->item((int)$financeira['id'], 'Saída para pagamento do IVA', 0, $valor)
        ]);
    }

    private function registarSaidaCaixa(
        int $pagamentoId,
        int $empresaId,
        ?int $filialId,
        int $usuarioId,
        array $dados,
        float $valor,
        string $data
    ): int {
        $caixaId = (int)($dados['caixa_id'] ?? 0);
        $stmt = $this->db->prepare("
            SELECT c.id, c.saldo_atual,
                (SELECT ac.id FROM abertura_caixa ac
                 WHERE ac.caixa_id = c.id AND ac.empresa_id = c.empresa_id
                   AND ac.data_fechamento IS NULL ORDER BY ac.id DESC LIMIT 1) AS abertura_id
            FROM caixas c
            WHERE c.id = :id AND c.empresa_id = :empresa_id
              AND (
                :filial_id_nula IS NULL
                OR c.filial_id IS NULL
                OR c.filial_id = :filial_id_igual
            )
            FOR UPDATE
        ");
        $stmt->execute([
            ':id'                => $caixaId,
            ':empresa_id'        => $empresaId,
            ':filial_id_nula'    => $filialId,
            ':filial_id_igual'   => $filialId
        ]);
        $caixa = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$caixa || empty($caixa['abertura_id'])) {
            throw new RuntimeException('Selecione um caixa aberto e válido.');
        }
        if ((float)$caixa['saldo_atual'] < $valor) {
            throw new RuntimeException('Saldo insuficiente no caixa.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO movimentos_caixa
            (empresa_id, filial_id, centro_custo_id, caixa_id, abertura_id, usuario_id,
             tipo, origem, documento_id, descricao, valor, data_movimento)
            VALUES (:empresa_id, :filial_id, :centro_custo_id, :caixa_id, :abertura_id, :usuario_id,
                    'SAIDA', 'PAGAMENTO', :documento_id, :descricao, :valor, :data_movimento)
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':filial_id' => $filialId,
            ':centro_custo_id' => $this->resolverCentroCustoId(
                $empresaId,
                $filialId,
                !empty($dados['centro_custo_id']) ? (int)$dados['centro_custo_id'] : null
            ),
            ':caixa_id' => $caixaId,
            ':abertura_id' => (int)$caixa['abertura_id'],
            ':usuario_id' => $usuarioId,
            ':documento_id' => $pagamentoId,
            ':descricao' => 'Pagamento do IVA #' . $pagamentoId,
            ':valor' => $valor,
            ':data_movimento' => $data
        ]);
        $movimentoId = (int)$this->db->lastInsertId();
        $stmt = $this->db->prepare("UPDATE caixas SET saldo_atual = saldo_atual - :valor WHERE id = :id AND empresa_id = :empresa_id");
        $stmt->execute([':valor' => $valor, ':id' => $caixaId, ':empresa_id' => $empresaId]);
        return $movimentoId;
    }

    private function registarSaidaBanco(
        int $pagamentoId,
        int $empresaId,
        ?int $filialId,
        int $usuarioId,
        array $dados,
        float $valor,
        string $data,
        string $referencia
    ): int {
        $bancoId = (int)($dados['banco_id'] ?? 0);
        $stmt = $this->db->prepare("
            SELECT id, saldo_atual FROM bancos
            WHERE id = :id AND empresa_id = :empresa_id
              AND (
                :filial_id_nula IS NULL
                OR filial_id IS NULL
                OR filial_id = :filial_id_igual
            )
            FOR UPDATE
        ");
        $stmt->execute([
            ':id'                => $bancoId,
            ':empresa_id'        => $empresaId,
            ':filial_id_nula'    => $filialId,
            ':filial_id_igual'   => $filialId
        ]);
        $banco = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$banco) {
            throw new RuntimeException('Conta bancária inválida.');
        }
        $saldoAnterior = (float)$banco['saldo_atual'];
        if ($saldoAnterior < $valor) {
            throw new RuntimeException('Saldo insuficiente na conta bancária.');
        }
        $saldoPosterior = round($saldoAnterior - $valor, 2);

        $stmt = $this->db->prepare("
            INSERT INTO movimentos_bancarios
            (empresa_id, filial_id, centro_custo_id, banco_id, usuario_id, tipo, categoria,
             origem, documento_id, origem_id, descricao, documento, referencia, valor,
             saldo_anterior, saldo_posterior, data_movimento, observacoes)
            VALUES (:empresa_id, :filial_id, :centro_custo_id, :banco_id, :usuario_id, 'SAIDA', 'PAGAMENTO',
                    'IVA', :documento_id, :origem_id, :descricao, :documento, :referencia, :valor,
                    :saldo_anterior, :saldo_posterior, :data_movimento, :observacoes)
        ");
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':filial_id' => $filialId,
            ':centro_custo_id' => $this->resolverCentroCustoId(
                $empresaId,
                $filialId,
                !empty($dados['centro_custo_id']) ? (int)$dados['centro_custo_id'] : null
            ),
            ':banco_id' => $bancoId,
            ':usuario_id' => $usuarioId,
            ':documento_id' => $pagamentoId,
            ':origem_id' => $pagamentoId,
            ':descricao' => 'Pagamento do IVA #' . $pagamentoId,
            ':documento' => 'IVA-PG-' . $pagamentoId,
            ':referencia' => $referencia,
            ':valor' => $valor,
            ':saldo_anterior' => $saldoAnterior,
            ':saldo_posterior' => $saldoPosterior,
            ':data_movimento' => $data,
            ':observacoes' => trim((string)($dados['observacoes'] ?? '')) ?: null
        ]);
        $movimentoId = (int)$this->db->lastInsertId();
        $stmt = $this->db->prepare("UPDATE bancos SET saldo_atual = :saldo WHERE id = :id AND empresa_id = :empresa_id");
        $stmt->execute([':saldo' => $saldoPosterior, ':id' => $bancoId, ':empresa_id' => $empresaId]);
        return $movimentoId;
    }

    private function listarPeriodos(int $empresaId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, nome, numero, data_inicio, data_fim
            FROM periodos_contabilisticos
            WHERE empresa_id = :empresa_id
            ORDER BY data_inicio DESC
        ");
        $stmt->execute([':empresa_id' => $empresaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listarCentrosCusto(int $empresaId, ?int $filialId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, codigo, nome
            FROM centros_custo
            WHERE empresa_id = :empresa_id
              AND (
                    :filial_id_nula IS NULL
                    OR filial_id IS NULL
                    OR filial_id = :filial_id_igual
              )
            ORDER BY codigo, nome
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':filial_id_nula' => $filialId,
            ':filial_id_igual' => $filialId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function resolverCentroCustoId(
        int $empresaId,
        ?int $filialId,
        ?int $centroCustoId
    ): int {
        if ($centroCustoId !== null && $centroCustoId > 0) {
            $stmt = $this->db->prepare("
                SELECT id
                FROM centros_custo
                WHERE id = :centro_custo_id
                  AND empresa_id = :empresa_id
                  AND (
                        :filial_id_nula IS NULL
                        OR filial_id IS NULL
                        OR filial_id = :filial_id_igual
                  )
                LIMIT 1
            ");

            $stmt->execute([
                ':centro_custo_id' => $centroCustoId,
                ':empresa_id' => $empresaId,
                ':filial_id_nula' => $filialId,
                ':filial_id_igual' => $filialId
            ]);

            $id = $stmt->fetchColumn();

            if ($id === false) {
                throw new RuntimeException('O centro de custo informado não pertence à empresa ou à filial.');
            }

            return (int)$id;
        }

        $centros = $this->listarCentrosCusto($empresaId, $filialId);

        if (count($centros) === 0) {
            throw new RuntimeException('Não existe centro de custo disponível para a empresa ou filial.');
        }

        if (count($centros) > 1) {
            throw new RuntimeException('Selecione o centro de custo do pagamento do IVA.');
        }

        return (int)$centros[0]['id'];
    }

    private function listarCaixas(
        int $empresaId,
        ?int $filialId
    ): array {
        $sql = "
        SELECT
            c.id,
            c.nome,
            c.saldo_atual
        FROM caixas c
        WHERE c.empresa_id = :empresa_id

          AND (
                :filial_id_nula IS NULL
                OR c.filial_id IS NULL
                OR c.filial_id = :filial_id_igual
          )

          AND EXISTS (
                SELECT 1
                FROM abertura_caixa ac
                WHERE ac.caixa_id = c.id
                  AND ac.empresa_id = c.empresa_id
                  AND ac.data_fechamento IS NULL
          )

        ORDER BY c.nome
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id'       => $empresaId,
            ':filial_id_nula'   => $filialId,
            ':filial_id_igual'  => $filialId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listarBancos(
        int $empresaId,
        ?int $filialId
    ): array {
        $sql = "
        SELECT
            b.id,
            b.nome_banco,
            b.conta,
            b.saldo_atual
        FROM bancos b

        INNER JOIN status s
            ON s.id = b.status_id
           AND s.tipo = 'banco'
           AND LOWER(s.nome) = 'activo'

        WHERE b.empresa_id = :empresa_id

          AND (
                :filial_id_nula IS NULL
                OR b.filial_id IS NULL
                OR b.filial_id = :filial_id_igual
          )

        ORDER BY
            b.nome_banco,
            b.conta
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id'       => $empresaId,
            ':filial_id_nula'   => $filialId,
            ':filial_id_igual'  => $filialId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function statusId(string $tipo, string $nome): int
    {
        $stmt = $this->db->prepare("SELECT id FROM status WHERE tipo = :tipo AND LOWER(nome) = LOWER(:nome) LIMIT 1");
        $stmt->execute([':tipo' => $tipo, ':nome' => $nome]);
        $id = (int)$stmt->fetchColumn();
        if ($id <= 0) {
            throw new RuntimeException("Status '{$nome}' não configurado para '{$tipo}'.");
        }
        return $id;
    }

    private function item(int $contaId, string $descricao, float $debito, float $credito): array
    {
        return [
            'conta_contabil_id' => $contaId,
            'centro_custo_id' => null,
            'cliente_id' => null,
            'fornecedor_id' => null,
            'descricao' => $descricao,
            'debito' => $debito,
            'credito' => $credito
        ];
    }

    private function validarDatas(string $inicio, string $fim): void
    {
        foreach ([$inicio, $fim] as $data) {
            $obj = DateTime::createFromFormat('Y-m-d', $data);
            if (!$obj || $obj->format('Y-m-d') !== $data) {
                throw new InvalidArgumentException('Período do IVA inválido.');
            }
        }
        if ($inicio > $fim) {
            throw new InvalidArgumentException('A data inicial não pode ser superior à data final.');
        }
    }

    private function dataHoraValida(string $data): bool
    {
        $obj = DateTime::createFromFormat('Y-m-d\TH:i', $data)
            ?: DateTime::createFromFormat('Y-m-d H:i:s', $data);
        return $obj !== false;
    }
}
