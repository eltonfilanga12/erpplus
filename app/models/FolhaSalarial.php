<?php

declare(strict_types=1);

class FolhaSalarial
{
    private PDO $db;

    private const TIPO_STATUS =
    'folha_salarial';

    private const STATUS_GERADA =
    'Gerada';

    private const STATUS_FECHADA =
    'Fechada';

    private const STATUS_CANCELADA =
    'Cancelada';

    private const TIPO_STATUS_PERIODO =
    'periodo_salarial';

    private const STATUS_PERIODO_PROCESSADO =
    'Processado';

    private const STATUS_PERIODO_FECHADO =
    'Fechado';

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Listar folhas
    |--------------------------------------------------------------------------
    */

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {
        if ($empresaId <= 0) {
            throw new Exception(
                'Empresa não informada.'
            );
        }

        $sql = "
            SELECT
                fs.id,
                fs.uuid,
                fs.empresa_id,
                fs.periodo_id,
                fs.codigo,
                fs.descricao,
                fs.moeda,
                fs.numero_funcionarios,

                fs.total_salario_base,
                fs.total_subsidios,
                fs.total_horas_extras,
                fs.total_outros_proventos,
                fs.total_proventos,

                fs.total_faltas,
                fs.total_atrasos,
                fs.total_inss,
                fs.total_irps,
                fs.total_outros_descontos,
                fs.total_descontos,

                fs.total_bruto,
                fs.total_liquido,
                fs.total_pago,
                fs.saldo_pendente,

                fs.versao,
                fs.observacoes,

                fs.gerada_em,
                fs.fechada_em,
                fs.cancelada_em,

                fs.criado_em,
                fs.atualizado_em,

                p.codigo AS periodo_codigo,
                p.descricao AS periodo_descricao,
                p.ano,
                p.mes,
                p.data_inicio,
                p.data_fim,

                st.nome AS estado

            FROM folhas_salariais fs

            INNER JOIN periodos_salariais p
                ON p.id = fs.periodo_id

            INNER JOIN status st
                ON st.id = fs.status_id
               AND st.tipo = :tipo_status

            WHERE fs.empresa_id = :empresa_id
        ";

        $params = [
            'tipo_status' =>
            self::TIPO_STATUS,

            'empresa_id' =>
            $empresaId
        ];

        if (!empty($filtros['periodo_id'])) {
            $sql .= "
                AND fs.periodo_id = :periodo_id
            ";

            $params['periodo_id'] =
                (int)$filtros['periodo_id'];
        }

        if (!empty($filtros['estado'])) {
            $sql .= "
                AND st.nome = :estado
            ";

            $params['estado'] =
                trim(
                    (string)$filtros['estado']
                );
        }

        if (!empty($filtros['ano'])) {
            $sql .= "
                AND p.ano = :ano
            ";

            $params['ano'] =
                (int)$filtros['ano'];
        }

        if (!empty($filtros['pesquisa'])) {
            $pesquisa =
                '%' .
                trim(
                    (string)$filtros['pesquisa']
                ) .
                '%';

            $sql .= "
                AND (
                    fs.codigo LIKE :pesquisa_codigo
                    OR fs.descricao
                        LIKE :pesquisa_descricao
                    OR p.codigo
                        LIKE :pesquisa_periodo
                )
            ";

            $params['pesquisa_codigo'] =
                $pesquisa;

            $params['pesquisa_descricao'] =
                $pesquisa;

            $params['pesquisa_periodo'] =
                $pesquisa;
        }

        $sql .= "
            ORDER BY
                p.ano DESC,
                p.mes DESC,
                fs.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarPeriodosDisponiveis(
        int $empresaId
    ): array {
        if ($empresaId <= 0) {
            throw new Exception(
                'Empresa não informada.'
            );
        }

        $stmt = $this->db->prepare("
        SELECT
            p.id,
            p.uuid,
            p.codigo,
            p.descricao,
            p.ano,
            p.mes,
            p.data_inicio,
            p.data_fim,
            st.nome AS estado

        FROM periodos_salariais p

        INNER JOIN status st
            ON st.id = p.status_id
           AND st.tipo = :tipo_status

        WHERE p.empresa_id = :empresa_id
          AND st.nome IN (
              'Processado',
              'Fechado'
          )

        ORDER BY
            p.ano DESC,
            p.mes DESC,
            p.id DESC
    ");

        $stmt->execute([
            'tipo_status' =>
            self::TIPO_STATUS_PERIODO,

            'empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Buscar folha
    |--------------------------------------------------------------------------
    */

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {
        if ($id <= 0 || $empresaId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT
                fs.*,

                p.codigo AS periodo_codigo,
                p.descricao AS periodo_descricao,
                p.ano,
                p.mes,
                p.data_inicio,
                p.data_fim,

                st.nome AS estado

            FROM folhas_salariais fs

            INNER JOIN periodos_salariais p
                ON p.id = fs.periodo_id

            INNER JOIN status st
                ON st.id = fs.status_id
               AND st.tipo = :tipo_status

            WHERE fs.id = :id
              AND fs.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            'tipo_status' =>
            self::TIPO_STATUS,

            'id' =>
            $id,

            'empresa_id' =>
            $empresaId
        ]);

        $folha = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$folha) {
            return null;
        }

        $folha['funcionarios'] =
            $this->listarFuncionarios(
                (int)$folha['periodo_id'],
                $empresaId
            );

        $folha['rubricas'] =
            $this->resumoRubricas(
                (int)$folha['periodo_id'],
                $empresaId
            );

        return $folha;
    }

    /*
    |--------------------------------------------------------------------------
    | Gerar folha
    |--------------------------------------------------------------------------
    */

    public function gerar(
        int $empresaId,
        int $periodoId,
        int $utilizadorId,
        ?string $observacoes = null
    ): array {
        if ($empresaId <= 0) {
            throw new Exception(
                'Empresa não informada.'
            );
        }

        if ($periodoId <= 0) {
            throw new Exception(
                'Período salarial não informado.'
            );
        }

        $periodo =
            $this->buscarPeriodo(
                $periodoId,
                $empresaId
            );

        if (!$periodo) {
            throw new Exception(
                'Período salarial não encontrado.'
            );
        }

        $estadoPeriodo =
            trim(
                (string)(
                    $periodo['estado'] ?? ''
                )
            );

        if (
            !in_array(
                $estadoPeriodo,
                [
                    self::STATUS_PERIODO_PROCESSADO,
                    self::STATUS_PERIODO_FECHADO
                ],
                true
            )
        ) {
            throw new Exception(
                'A folha somente pode ser gerada '
                    . 'para períodos Processados ou Fechados. '
                    . 'Estado actual: '
                    . (
                        $estadoPeriodo !== ''
                        ? $estadoPeriodo
                        : 'não identificado'
                    )
                    . '.'
            );
        }

        if (
            $this->existeFolhaPeriodo(
                $periodoId,
                $empresaId
            )
        ) {
            throw new Exception(
                'Já existe uma folha salarial '
                    . 'para este período.'
            );
        }

        $totais =
            $this->calcularTotaisPeriodo(
                $periodoId,
                $empresaId
            );

        if (
            (int)$totais['numero_funcionarios']
            <= 0
        ) {
            throw new Exception(
                'O período não possui processamentos '
                    . 'salariais para gerar a folha.'
            );
        }

        $statusGerada =
            $this->obterStatusId(
                self::TIPO_STATUS,
                self::STATUS_GERADA
            );

        $codigo =
            $this->gerarCodigo(
                $periodo,
                $empresaId
            );

        $descricao =
            'Folha de salário - '
            . (
                $periodo['descricao']
                ?: $periodo['codigo']
            );

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO folhas_salariais
                (
                    uuid,
                    empresa_id,
                    periodo_id,
                    codigo,
                    descricao,
                    moeda,
                    numero_funcionarios,

                    total_salario_base,
                    total_subsidios,
                    total_horas_extras,
                    total_outros_proventos,
                    total_proventos,

                    total_faltas,
                    total_atrasos,
                    total_inss,
                    total_inss_patronal,
                    total_irps,
                    total_outros_descontos,
                    total_descontos,

                    total_bruto,
                    total_liquido,
                    total_pago,
                    saldo_pendente,

                    status_id,
                    observacoes,

                    gerada_por,
                    gerada_em,
                    criado_por
                )
                VALUES
                (
                    :uuid,
                    :empresa_id,
                    :periodo_id,
                    :codigo,
                    :descricao,
                    :moeda,
                    :numero_funcionarios,

                    :total_salario_base,
                    :total_subsidios,
                    :total_horas_extras,
                    :total_outros_proventos,
                    :total_proventos,

                    :total_faltas,
                    :total_atrasos,
                    :total_inss,
                    :total_inss_patronal,
                    :total_irps,
                    :total_outros_descontos,
                    :total_descontos,

                    :total_bruto,
                    :total_liquido,
                    :total_pago,
                    :saldo_pendente,

                    :status_id,
                    :observacoes,

                    :gerada_por,
                    NOW(),
                    :criado_por
                )
            ");

            $stmt->execute([
                'uuid' =>
                $this->gerarUUID(),

                'empresa_id' =>
                $empresaId,

                'periodo_id' =>
                $periodoId,

                'codigo' =>
                $codigo,

                'descricao' =>
                $descricao,

                'moeda' =>
                strtoupper(
                    (string)(
                        $totais['moeda']
                        ?: 'MZN'
                    )
                ),

                'numero_funcionarios' =>
                $totais['numero_funcionarios'],

                'total_salario_base' =>
                $totais['total_salario_base'],

                'total_subsidios' =>
                $totais['total_subsidios'],

                'total_horas_extras' =>
                $totais['total_horas_extras'],

                'total_outros_proventos' =>
                $totais['total_outros_proventos'],

                'total_proventos' =>
                $totais['total_proventos'],

                'total_faltas' =>
                $totais['total_faltas'],

                'total_atrasos' =>
                $totais['total_atrasos'],

                'total_inss' =>
                $totais['total_inss'],

                'total_inss_patronal' =>
                $totais['total_inss_patronal'],

                'total_irps' =>
                $totais['total_irps'],

                'total_outros_descontos' =>
                $totais['total_outros_descontos'],

                'total_descontos' =>
                $totais['total_descontos'],

                'total_bruto' =>
                $totais['total_bruto'],

                'total_liquido' =>
                $totais['total_liquido'],

                'total_pago' =>
                $totais['total_pago'],

                'saldo_pendente' =>
                $totais['saldo_pendente'],

                'status_id' =>
                $statusGerada,

                'observacoes' =>
                $this->normalizarTexto(
                    $observacoes
                ),

                'gerada_por' =>
                $utilizadorId > 0
                    ? $utilizadorId
                    : null,

                'criado_por' =>
                $utilizadorId > 0
                    ? $utilizadorId
                    : null
            ]);

            $id =
                (int)$this->db
                    ->lastInsertId();

            $this->db->commit();

            return [
                'success' => true,
                'message' =>
                'Folha salarial gerada com sucesso.',
                'id' =>
                $id,
                'codigo' =>
                $codigo
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Recalcular folha
    |--------------------------------------------------------------------------
    */

    public function recalcular(
        int $id,
        int $empresaId,
        int $utilizadorId
    ): array {
        $folha =
            $this->buscarFolhaSimples(
                $id,
                $empresaId
            );

        if (!$folha) {
            throw new Exception(
                'Folha salarial não encontrada.'
            );
        }

        if (
            $folha['estado'] !==
            self::STATUS_GERADA
        ) {
            throw new Exception(
                'Apenas folhas com estado Gerada '
                    . 'podem ser recalculadas.'
            );
        }

        $totais =
            $this->calcularTotaisPeriodo(
                (int)$folha['periodo_id'],
                $empresaId
            );

        $stmt = $this->db->prepare("
            UPDATE folhas_salariais

            SET
                moeda = :moeda,
                numero_funcionarios =
                    :numero_funcionarios,

                total_salario_base =
                    :total_salario_base,

                total_subsidios =
                    :total_subsidios,

                total_horas_extras =
                    :total_horas_extras,

                total_outros_proventos =
                    :total_outros_proventos,

                total_proventos =
                    :total_proventos,

                total_faltas =
                    :total_faltas,

                total_atrasos =
                    :total_atrasos,

                total_inss =
                    :total_inss,

                total_inss_patronal =
                    :total_inss_patronal,

                total_irps =
                    :total_irps,

                total_outros_descontos =
                    :total_outros_descontos,

                total_descontos =
                    :total_descontos,

                total_bruto =
                    :total_bruto,

                total_liquido =
                    :total_liquido,

                total_pago =
                    :total_pago,

                saldo_pendente =
                    :saldo_pendente,

                versao = versao + 1,
                atualizado_por =
                    :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            'moeda' =>
            strtoupper(
                (string)(
                    $totais['moeda']
                    ?: 'MZN'
                )
            ),

            'numero_funcionarios' =>
            $totais['numero_funcionarios'],

            'total_salario_base' =>
            $totais['total_salario_base'],

            'total_subsidios' =>
            $totais['total_subsidios'],

            'total_horas_extras' =>
            $totais['total_horas_extras'],

            'total_outros_proventos' =>
            $totais['total_outros_proventos'],

            'total_proventos' =>
            $totais['total_proventos'],

            'total_faltas' =>
            $totais['total_faltas'],

            'total_atrasos' =>
            $totais['total_atrasos'],

            'total_inss' =>
            $totais['total_inss'],

            'total_inss_patronal' =>
            $totais['total_inss_patronal'],

            'total_irps' =>
            $totais['total_irps'],

            'total_outros_descontos' =>
            $totais['total_outros_descontos'],

            'total_descontos' =>
            $totais['total_descontos'],

            'total_bruto' =>
            $totais['total_bruto'],

            'total_liquido' =>
            $totais['total_liquido'],

            'total_pago' =>
            $totais['total_pago'],

            'saldo_pendente' =>
            $totais['saldo_pendente'],

            'atualizado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,

            'id' =>
            $id,

            'empresa_id' =>
            $empresaId
        ]);

        return [
            'success' => true,
            'message' =>
            'Folha salarial recalculada com sucesso.',
            'id' =>
            $id
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Fechar folha
    |--------------------------------------------------------------------------
    */

    public function fechar(
        int $id,
        int $empresaId,
        int $utilizadorId
    ): array {
        $folha =
            $this->buscarFolhaSimples(
                $id,
                $empresaId
            );

        if (!$folha) {
            throw new Exception(
                'Folha salarial não encontrada.'
            );
        }

        if (
            $folha['estado'] !==
            self::STATUS_GERADA
        ) {
            throw new Exception(
                'Apenas folhas com estado Gerada '
                    . 'podem ser fechadas.'
            );
        }

        $statusFechada =
            $this->obterStatusId(
                self::TIPO_STATUS,
                self::STATUS_FECHADA
            );

        $stmt = $this->db->prepare("
            UPDATE folhas_salariais

            SET
                status_id = :status_id,
                fechada_por = :fechada_por,
                fechada_em = NOW(),
                atualizado_por =
                    :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            'status_id' =>
            $statusFechada,

            'fechada_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,

            'atualizado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,

            'id' =>
            $id,

            'empresa_id' =>
            $empresaId
        ]);

        return [
            'success' => true,
            'message' =>
            'Folha salarial fechada com sucesso.',
            'id' =>
            $id
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelar folha
    |--------------------------------------------------------------------------
    */

    public function cancelar(
        int $id,
        int $empresaId,
        int $utilizadorId,
        string $motivo
    ): array {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new Exception(
                'Informe o motivo do cancelamento.'
            );
        }

        $folha =
            $this->buscarFolhaSimples(
                $id,
                $empresaId
            );

        if (!$folha) {
            throw new Exception(
                'Folha salarial não encontrada.'
            );
        }

        if (
            $folha['estado'] ===
            self::STATUS_CANCELADA
        ) {
            throw new Exception(
                'Esta folha já se encontra cancelada.'
            );
        }

        if ((float)$folha['total_pago'] > 0) {
            throw new Exception(
                'Não é possível cancelar uma folha '
                    . 'que já possui pagamentos.'
            );
        }

        $statusCancelada =
            $this->obterStatusId(
                self::TIPO_STATUS,
                self::STATUS_CANCELADA
            );

        $stmt = $this->db->prepare("
            UPDATE folhas_salariais

            SET
                status_id = :status_id,
                cancelada_por = :cancelada_por,
                cancelada_em = NOW(),
                motivo_cancelamento = :motivo,
                atualizado_por =
                    :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            'status_id' =>
            $statusCancelada,

            'cancelada_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,

            'motivo' =>
            $motivo,

            'atualizado_por' =>
            $utilizadorId > 0
                ? $utilizadorId
                : null,

            'id' =>
            $id,

            'empresa_id' =>
            $empresaId
        ]);

        return [
            'success' => true,
            'message' =>
            'Folha salarial cancelada com sucesso.',
            'id' =>
            $id
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Funcionários da folha
    |--------------------------------------------------------------------------
    */

    public function listarFuncionarios(
        int $periodoId,
        int $empresaId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                ps.id AS processamento_id,
                ps.uuid,
                ps.funcionario_id,
                ps.contrato_id,
                ps.salario_funcionario_id,

                ps.numero_funcionario,
                ps.funcionario_nome,
                ps.cargo_nome,
                ps.departamento_nome,
                ps.filial_nome,

                ps.moeda,
                ps.salario_base,
                ps.total_salario_base,
                ps.total_subsidios,
                ps.total_horas_extras,
                ps.total_outros_proventos,
                ps.total_proventos,

                ps.total_faltas,
                ps.total_atrasos,
                ps.total_inss,
                ps.total_inss_patronal,
                ps.total_irps,
                ps.total_outros_descontos,
                ps.total_descontos,

                ps.salario_bruto,
                ps.salario_liquido,
                ps.valor_pago,
                ps.saldo_pendente,

                st.nome AS estado

            FROM processamentos_salariais ps

            INNER JOIN status st
                ON st.id = ps.status_id
               AND st.tipo =
                    'processamento_salarial'

            WHERE ps.periodo_id = :periodo_id
              AND ps.empresa_id = :empresa_id

            ORDER BY
                ps.funcionario_nome ASC
        ");

        $stmt->execute([
            'periodo_id' =>
            $periodoId,

            'empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resumo das rubricas
    |--------------------------------------------------------------------------
    */

    public function resumoRubricas(
        int $periodoId,
        int $empresaId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                psi.codigo_rubrica,
                psi.nome_rubrica,
                psi.tipo,
                psi.categoria,

                COUNT(DISTINCT psi.processamento_id)
                    AS numero_funcionarios,

                SUM(psi.valor_total)
                    AS valor_total

            FROM processamento_salario_itens psi

            INNER JOIN processamentos_salariais ps
                ON ps.id = psi.processamento_id

            WHERE ps.periodo_id = :periodo_id
              AND ps.empresa_id = :empresa_id

            GROUP BY
                psi.codigo_rubrica,
                psi.nome_rubrica,
                psi.tipo,
                psi.categoria

            ORDER BY
                psi.tipo ASC,
                psi.nome_rubrica ASC
        ");

        $stmt->execute([
            'periodo_id' =>
            $periodoId,

            'empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resumo geral
    |--------------------------------------------------------------------------
    */

    public function resumo(
        int $empresaId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS quantidade_folhas,

                COALESCE(
                    SUM(numero_funcionarios),
                    0
                ) AS numero_funcionarios,

                COALESCE(
                    SUM(total_bruto),
                    0
                ) AS total_bruto,

                COALESCE(
                    SUM(total_descontos),
                    0
                ) AS total_descontos,

                COALESCE(
                    SUM(total_inss),
                    0
                ) AS total_inss,

                COALESCE(
                    SUM(total_inss_patronal),
                    0
                ) AS total_inss_patronal,

                COALESCE(
                    SUM(total_irps),
                    0
                ) AS total_irps,

                COALESCE(
                    SUM(total_liquido),
                    0
                ) AS total_liquido,

                COALESCE(
                    SUM(total_pago),
                    0
                ) AS total_pago,

                COALESCE(
                    SUM(saldo_pendente),
                    0
                ) AS saldo_pendente

            FROM folhas_salariais

            WHERE empresa_id = :empresa_id
        ");

        $stmt->execute([
            'empresa_id' =>
            $empresaId
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC)
            ?: [];

        return [
            'quantidade_folhas' =>
            (int)(
                $resultado['quantidade_folhas'] ?? 0
            ),

            'numero_funcionarios' =>
            (int)(
                $resultado['numero_funcionarios'] ?? 0
            ),

            'total_bruto' =>
            (float)(
                $resultado['total_bruto']
                ?? 0
            ),

            'total_descontos' =>
            (float)(
                $resultado['total_descontos'] ?? 0
            ),

            'total_inss' =>
            (float)(
                $resultado['total_inss']
                ?? 0
            ),

            'total_inss_patronal' =>
            (float)(
                $resultado['total_inss_patronal']
                ?? 0
            ),

            'total_irps' =>
            (float)(
                $resultado['total_irps']
                ?? 0
            ),

            'total_liquido' =>
            (float)(
                $resultado['total_liquido']
                ?? 0
            ),

            'total_pago' =>
            (float)(
                $resultado['total_pago']
                ?? 0
            ),

            'saldo_pendente' =>
            (float)(
                $resultado['saldo_pendente']
                ?? 0
            )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas auxiliares
    |--------------------------------------------------------------------------
    */

    private function buscarPeriodo(
        int $periodoId,
        int $empresaId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                st.nome AS estado

            FROM periodos_salariais p

            INNER JOIN status st
                ON st.id = p.status_id
               AND st.tipo =
                    :tipo_status_periodo

            WHERE p.id = :periodo_id
              AND p.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            'tipo_status_periodo' =>
            self::TIPO_STATUS_PERIODO,

            'periodo_id' =>
            $periodoId,

            'empresa_id' =>
            $empresaId
        ]);

        $periodo =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $periodo ?: null;
    }

    private function buscarFolhaSimples(
        int $id,
        int $empresaId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT
                fs.*,
                st.nome AS estado

            FROM folhas_salariais fs

            INNER JOIN status st
                ON st.id = fs.status_id
               AND st.tipo = :tipo_status

            WHERE fs.id = :id
              AND fs.empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            'tipo_status' =>
            self::TIPO_STATUS,

            'id' =>
            $id,

            'empresa_id' =>
            $empresaId
        ]);

        $folha =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $folha ?: null;
    }

    private function existeFolhaPeriodo(
        int $periodoId,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM folhas_salariais

            WHERE periodo_id = :periodo_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            'periodo_id' =>
            $periodoId,

            'empresa_id' =>
            $empresaId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function calcularTotaisPeriodo(
        int $periodoId,
        int $empresaId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS numero_funcionarios,

                COALESCE(
                    MAX(moeda),
                    'MZN'
                ) AS moeda,

                COALESCE(
                    SUM(total_salario_base),
                    0
                ) AS total_salario_base,

                COALESCE(
                    SUM(total_subsidios),
                    0
                ) AS total_subsidios,

                COALESCE(
                    SUM(total_horas_extras),
                    0
                ) AS total_horas_extras,

                COALESCE(
                    SUM(total_outros_proventos),
                    0
                ) AS total_outros_proventos,

                COALESCE(
                    SUM(total_proventos),
                    0
                ) AS total_proventos,

                COALESCE(
                    SUM(total_faltas),
                    0
                ) AS total_faltas,

                COALESCE(
                    SUM(total_atrasos),
                    0
                ) AS total_atrasos,

                COALESCE(
                    SUM(total_inss),
                    0
                ) AS total_inss,

                COALESCE(
                    SUM(total_inss_patronal),
                    0
                ) AS total_inss_patronal,

                COALESCE(
                    SUM(total_irps),
                    0
                ) AS total_irps,

                COALESCE(
                    SUM(total_outros_descontos),
                    0
                ) AS total_outros_descontos,

                COALESCE(
                    SUM(total_descontos),
                    0
                ) AS total_descontos,

                COALESCE(
                    SUM(salario_bruto),
                    0
                ) AS total_bruto,

                COALESCE(
                    SUM(salario_liquido),
                    0
                ) AS total_liquido,

                COALESCE(
                    SUM(valor_pago),
                    0
                ) AS total_pago,

                COALESCE(
                    SUM(saldo_pendente),
                    0
                ) AS saldo_pendente

            FROM processamentos_salariais

            WHERE periodo_id = :periodo_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            'periodo_id' =>
            $periodoId,

            'empresa_id' =>
            $empresaId
        ]);

        $totais =
            $stmt->fetch(PDO::FETCH_ASSOC)
            ?: [];

        return [
            'numero_funcionarios' =>
            (int)(
                $totais['numero_funcionarios'] ?? 0
            ),

            'moeda' =>
            (string)(
                $totais['moeda']
                ?? 'MZN'
            ),

            'total_salario_base' =>
            (float)(
                $totais['total_salario_base'] ?? 0
            ),

            'total_subsidios' =>
            (float)(
                $totais['total_subsidios'] ?? 0
            ),

            'total_horas_extras' =>
            (float)(
                $totais['total_horas_extras'] ?? 0
            ),

            'total_outros_proventos' =>
            (float)(
                $totais['total_outros_proventos'] ?? 0
            ),

            'total_proventos' =>
            (float)(
                $totais['total_proventos'] ?? 0
            ),

            'total_faltas' =>
            (float)(
                $totais['total_faltas']
                ?? 0
            ),

            'total_atrasos' =>
            (float)(
                $totais['total_atrasos']
                ?? 0
            ),

            'total_inss' =>
            (float)(
                $totais['total_inss']
                ?? 0
            ),

            'total_inss_patronal' =>
            (float)(
                $totais['total_inss_patronal']
                ?? 0
            ),

            'total_irps' =>
            (float)(
                $totais['total_irps']
                ?? 0
            ),

            'total_outros_descontos' =>
            (float)(
                $totais['total_outros_descontos'] ?? 0
            ),

            'total_descontos' =>
            (float)(
                $totais['total_descontos'] ?? 0
            ),

            'total_bruto' =>
            (float)(
                $totais['total_bruto']
                ?? 0
            ),

            'total_liquido' =>
            (float)(
                $totais['total_liquido']
                ?? 0
            ),

            'total_pago' =>
            (float)(
                $totais['total_pago']
                ?? 0
            ),

            'saldo_pendente' =>
            (float)(
                $totais['saldo_pendente']
                ?? 0
            )
        ];
    }

    private function gerarCodigo(
        array $periodo,
        int $empresaId
    ): string {
        $ano =
            (int)($periodo['ano'] ?? date('Y'));

        $mes =
            str_pad(
                (string)(
                    $periodo['mes']
                    ?? date('m')
                ),
                2,
                '0',
                STR_PAD_LEFT
            );

        $base =
            'FS-' . $ano . '-' . $mes;

        $codigo = $base;
        $sequencia = 1;

        while (
            $this->existeCodigo(
                $codigo,
                $empresaId
            )
        ) {
            $sequencia++;

            $codigo =
                $base
                . '-'
                . str_pad(
                    (string)$sequencia,
                    2,
                    '0',
                    STR_PAD_LEFT
                );
        }

        return $codigo;
    }

    private function existeCodigo(
        string $codigo,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM folhas_salariais

            WHERE codigo = :codigo
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            'codigo' =>
            $codigo,

            'empresa_id' =>
            $empresaId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function obterStatusId(
        string $tipo,
        string $nome
    ): int {
        $stmt = $this->db->prepare("
            SELECT id

            FROM status

            WHERE tipo = :tipo
              AND nome = :nome

            LIMIT 1
        ");

        $stmt->execute([
            'tipo' =>
            $tipo,

            'nome' =>
            $nome
        ]);

        $id = $stmt->fetchColumn();

        if (!$id) {
            throw new RuntimeException(
                sprintf(
                    'Estado não encontrado: '
                        . 'tipo=%s, nome=%s.',
                    $tipo,
                    $nome
                )
            );
        }

        return (int)$id;
    }

    private function normalizarTexto(
        ?string $texto
    ): ?string {
        if ($texto === null) {
            return null;
        }

        $texto = trim($texto);

        return $texto !== ''
            ? $texto
            : null;
    }

    private function gerarUUID(): string
    {
        $dados = random_bytes(16);

        $dados[6] =
            chr(
                (
                    ord($dados[6])
                    & 0x0f
                )
                    | 0x40
            );

        $dados[8] =
            chr(
                (
                    ord($dados[8])
                    & 0x3f
                )
                    | 0x80
            );

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(
                bin2hex($dados),
                4
            )
        );
    }
}
