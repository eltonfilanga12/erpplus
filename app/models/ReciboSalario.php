<?php

declare(strict_types=1);

final class ReciboSalario
{
    private const TIPO_STATUS =
        'recibo_salarial';

    private PDO $db;

    public function __construct()
    {
        $this->db =
            Database::conn();
    }

    /*
    |--------------------------------------------------------------------------
    | Listar recibos
    |--------------------------------------------------------------------------
    */
    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {
        $sql = "
            SELECT
                rs.id,
                rs.uuid,
                rs.codigo,
                rs.empresa_id,
                rs.folha_salarial_id,
                rs.processamento_id,
                rs.periodo_id,
                rs.funcionario_id,
                rs.contrato_id,

                rs.numero_funcionario,
                rs.funcionario_nome,
                rs.cargo_nome,
                rs.departamento_nome,
                rs.filial_nome,

                rs.moeda,
                rs.salario_base,
                rs.total_subsidios,
                rs.total_horas_extras,
                rs.total_outros_proventos,
                rs.total_proventos,

                rs.total_faltas,
                rs.total_atrasos,
                rs.total_inss,
                rs.total_irps,
                rs.total_outros_descontos,
                rs.total_descontos,

                rs.salario_bruto,
                rs.salario_liquido,
                rs.valor_pago,
                rs.saldo_pendente,

                rs.emitido_em,
                rs.enviado_em,
                rs.entregue_em,
                rs.observacoes,
                rs.criado_em,

                st.nome AS estado,

                fs.codigo AS folha_codigo,
                fs.descricao AS folha_descricao,

                ps.codigo AS periodo_codigo,
                ps.descricao AS periodo_descricao,
                ps.ano AS periodo_ano,
                ps.mes AS periodo_mes

            FROM recibos_salarios rs

            INNER JOIN status st
                ON st.id = rs.status_id
               AND st.tipo = :tipo_status

            INNER JOIN folhas_salariais fs
                ON fs.id = rs.folha_salarial_id
               AND fs.empresa_id = rs.empresa_id

            INNER JOIN periodos_salariais ps
                ON ps.id = rs.periodo_id
               AND ps.empresa_id = rs.empresa_id

            WHERE rs.empresa_id = :empresa_id
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':tipo_status' => self::TIPO_STATUS
        ];

        if (!empty($filtros['periodo_id'])) {
            $sql .= "
                AND rs.periodo_id = :periodo_id
            ";

            $parametros[':periodo_id'] =
                (int) $filtros['periodo_id'];
        }

        if (!empty($filtros['folha_salarial_id'])) {
            $sql .= "
                AND rs.folha_salarial_id =
                    :folha_salarial_id
            ";

            $parametros[':folha_salarial_id'] =
                (int) $filtros['folha_salarial_id'];
        }

        if (!empty($filtros['funcionario_id'])) {
            $sql .= "
                AND rs.funcionario_id =
                    :funcionario_id
            ";

            $parametros[':funcionario_id'] =
                (int) $filtros['funcionario_id'];
        }

        if (!empty($filtros['estado'])) {
            $sql .= "
                AND st.nome = :estado
            ";

            $parametros[':estado'] =
                trim((string) $filtros['estado']);
        }

        if (!empty($filtros['pesquisa'])) {
            $sql .= "
                AND (
                    rs.codigo LIKE :pesquisa
                    OR rs.numero_funcionario
                        LIKE :pesquisa
                    OR rs.funcionario_nome
                        LIKE :pesquisa
                    OR rs.cargo_nome
                        LIKE :pesquisa
                    OR rs.departamento_nome
                        LIKE :pesquisa
                    OR fs.codigo
                        LIKE :pesquisa
                    OR ps.codigo
                        LIKE :pesquisa
                )
            ";

            $parametros[':pesquisa'] =
                '%' . trim(
                    (string) $filtros['pesquisa']
                ) . '%';
        }

        $sql .= "
            ORDER BY
                rs.criado_em DESC,
                rs.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar recibo
    |--------------------------------------------------------------------------
    */
    public function buscar(
        int $id,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                rs.*,

                st.nome AS estado,

                fs.codigo AS folha_codigo,
                fs.descricao AS folha_descricao,

                ps.codigo AS periodo_codigo,
                ps.descricao AS periodo_descricao,
                ps.ano AS periodo_ano,
                ps.mes AS periodo_mes,
                ps.data_inicio,
                ps.data_fim,

                emp.nome AS empresa_nome,
                emp.nuit AS empresa_nuit,
                emp.email AS empresa_email,
                emp.telefone AS empresa_telefone

            FROM recibos_salarios rs

            INNER JOIN status st
                ON st.id = rs.status_id
               AND st.tipo = :tipo_status

            INNER JOIN folhas_salariais fs
                ON fs.id = rs.folha_salarial_id
               AND fs.empresa_id = rs.empresa_id

            INNER JOIN periodos_salariais ps
                ON ps.id = rs.periodo_id
               AND ps.empresa_id = rs.empresa_id

            INNER JOIN empresas emp
                ON emp.id = rs.empresa_id

            WHERE rs.id = :id
              AND rs.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId,
            ':tipo_status' => self::TIPO_STATUS
        ]);

        $recibo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $recibo ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Listar períodos disponíveis
    |--------------------------------------------------------------------------
    */
    public function listarPeriodos(
        int $empresaId
    ): array {
        $sql = "
            SELECT DISTINCT
                ps.id,
                ps.uuid,
                ps.codigo,
                ps.descricao,
                ps.ano,
                ps.mes,
                ps.data_inicio,
                ps.data_fim,

                st.nome AS estado

            FROM periodos_salariais ps

            INNER JOIN folhas_salariais fs
                ON fs.periodo_id = ps.id
               AND fs.empresa_id = ps.empresa_id

            INNER JOIN status st
                ON st.id = ps.status_id
               AND st.tipo = 'periodo_salarial'

            WHERE ps.empresa_id = :empresa_id
              AND st.nome IN (
                  'Processado',
                  'Fechado'
              )

            ORDER BY
                ps.ano DESC,
                ps.mes DESC,
                ps.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Listar folhas disponíveis
    |--------------------------------------------------------------------------
    */
    public function listarFolhas(
        int $empresaId,
        ?int $periodoId = null
    ): array {
        $sql = "
            SELECT
                fs.id,
                fs.uuid,
                fs.codigo,
                fs.descricao,
                fs.periodo_id,
                fs.moeda,

                fs.numero_funcionarios,
                fs.total_bruto,
                fs.total_descontos,
                fs.total_liquido,

                st.nome AS estado

            FROM folhas_salariais fs

            INNER JOIN status st
                ON st.id = fs.status_id
               AND st.tipo = 'folha_salarial'

            WHERE fs.empresa_id = :empresa_id
        ";

        $parametros = [
            ':empresa_id' => $empresaId
        ];

        if ($periodoId !== null && $periodoId > 0) {
            $sql .= "
                AND fs.periodo_id = :periodo_id
            ";

            $parametros[':periodo_id'] =
                $periodoId;
        }

        $sql .= "
            AND st.nome <> 'Cancelada'

            ORDER BY
                fs.criado_em DESC,
                fs.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Funcionários processados na folha
    |--------------------------------------------------------------------------
    */
    public function listarFuncionariosFolha(
    int $folhaSalarialId,
    int $empresaId
): array {
    $sql = "
        SELECT
            pr.id AS processamento_id,
            pr.funcionario_id,
            pr.contrato_id,

            pr.numero_funcionario,
            pr.funcionario_nome,
            pr.cargo_nome,
            pr.departamento_nome,
            pr.filial_nome,

            pr.moeda,
            pr.total_salario_base,
            pr.total_proventos,
            pr.total_descontos,
            pr.salario_liquido,

            CASE
                WHEN rs.id IS NULL
                    THEN 0
                ELSE 1
            END AS possui_recibo,

            rs.id AS recibo_id,
            rs.codigo AS recibo_codigo

        FROM folhas_salariais fs

        INNER JOIN processamentos_salariais pr
            ON pr.periodo_id = fs.periodo_id
           AND pr.empresa_id = fs.empresa_id

        LEFT JOIN recibos_salarios rs
            ON rs.processamento_id = pr.id
           AND rs.empresa_id = pr.empresa_id

        WHERE fs.id = :folha_salarial_id
          AND fs.empresa_id = :empresa_id

        ORDER BY
            pr.funcionario_nome ASC,
            pr.id ASC
    ";

    $stmt =
        $this->db->prepare($sql);

    $stmt->execute([
        ':folha_salarial_id' =>
            $folhaSalarialId,

        ':empresa_id' =>
            $empresaId
    ]);

    return $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );
}
    /*
    |--------------------------------------------------------------------------
    | Gerar recibo individual
    |--------------------------------------------------------------------------
    */
    public function gerarIndividual(
    int $processamentoId,
    int $folhaSalarialId,
    int $empresaId,
    int $usuarioId,
    ?string $observacoes = null
): array {
        $this->db->beginTransaction();

        try {
            $existente =
                $this->buscarPorProcessamento(
                    $processamentoId,
                    $empresaId
                );

            if ($existente !== null) {
                throw new RuntimeException(
                    'Já existe um recibo para este processamento.'
                );
            }

            $processamento =
    $this->buscarDadosProcessamento(
        $processamentoId,
        $folhaSalarialId,
        $empresaId
    );

            if ($processamento === null) {
                throw new RuntimeException(
                    'Processamento salarial não encontrado.'
                );
            }

            $statusId =
                $this->buscarStatusId('Emitido');

            if ($statusId === null) {
                throw new RuntimeException(
                    'O estado Emitido não foi configurado para recibos salariais.'
                );
            }

            $codigo =
                $this->gerarCodigo(
                    $empresaId,
                    (int) $processamento['ano'],
                    (int) $processamento['mes']
                );

            $sql = "
                INSERT INTO recibos_salarios (
                    uuid,
                    empresa_id,
                    folha_salarial_id,
                    processamento_id,
                    periodo_id,
                    funcionario_id,
                    contrato_id,

                    codigo,

                    numero_funcionario,
                    funcionario_nome,
                    cargo_nome,
                    departamento_nome,
                    filial_nome,

                    moeda,

                    salario_base,
                    total_subsidios,
                    total_horas_extras,
                    total_outros_proventos,
                    total_proventos,

                    total_faltas,
                    total_atrasos,
                    total_inss,
                    total_irps,
                    total_outros_descontos,
                    total_descontos,

                    salario_bruto,
                    salario_liquido,
                    valor_pago,
                    saldo_pendente,

                    status_id,
                    emitido_por,
                    emitido_em,
                    observacoes,
                    criado_por
                )
                VALUES (
                    :uuid,
                    :empresa_id,
                    :folha_salarial_id,
                    :processamento_id,
                    :periodo_id,
                    :funcionario_id,
                    :contrato_id,

                    :codigo,

                    :numero_funcionario,
                    :funcionario_nome,
                    :cargo_nome,
                    :departamento_nome,
                    :filial_nome,

                    :moeda,

                    :salario_base,
                    :total_subsidios,
                    :total_horas_extras,
                    :total_outros_proventos,
                    :total_proventos,

                    :total_faltas,
                    :total_atrasos,
                    :total_inss,
                    :total_irps,
                    :total_outros_descontos,
                    :total_descontos,

                    :salario_bruto,
                    :salario_liquido,
                    :valor_pago,
                    :saldo_pendente,

                    :status_id,
                    :emitido_por,
                    NOW(),
                    :observacoes,
                    :criado_por
                )
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':uuid' =>
                    $this->gerarUuid(),

                ':empresa_id' =>
                    $empresaId,

                ':folha_salarial_id' =>
                    (int) $processamento[
                        'folha_salarial_id'
                    ],

                ':processamento_id' =>
                    $processamentoId,

                ':periodo_id' =>
                    (int) $processamento[
                        'periodo_id'
                    ],

                ':funcionario_id' =>
                    (int) $processamento[
                        'funcionario_id'
                    ],

                ':contrato_id' =>
                    !empty(
                        $processamento['contrato_id']
                    )
                        ? (int) $processamento[
                            'contrato_id'
                        ]
                        : null,

                ':codigo' =>
                    $codigo,

                ':numero_funcionario' =>
                    $processamento[
                        'numero_funcionario'
                    ],

                ':funcionario_nome' =>
                    $processamento[
                        'funcionario_nome'
                    ],

                ':cargo_nome' =>
                    $processamento['cargo_nome'],

                ':departamento_nome' =>
                    $processamento[
                        'departamento_nome'
                    ],

                ':filial_nome' =>
                    $processamento['filial_nome'],

                ':moeda' =>
                    $processamento['moeda']
                    ?: 'MZN',

                ':salario_base' =>
                    $processamento[
                        'salario_base'
                    ],

                ':total_subsidios' =>
                    $processamento[
                        'total_subsidios'
                    ],

                ':total_horas_extras' =>
                    $processamento[
                        'total_horas_extras'
                    ],

                ':total_outros_proventos' =>
                    $processamento[
                        'total_outros_proventos'
                    ],

                ':total_proventos' =>
                    $processamento[
                        'total_proventos'
                    ],

                ':total_faltas' =>
                    $processamento[
                        'total_faltas'
                    ],

                ':total_atrasos' =>
                    $processamento[
                        'total_atrasos'
                    ],

                ':total_inss' =>
                    $processamento[
                        'total_inss'
                    ],

                ':total_irps' =>
                    $processamento[
                        'total_irps'
                    ],

                ':total_outros_descontos' =>
                    $processamento[
                        'total_outros_descontos'
                    ],

                ':total_descontos' =>
                    $processamento[
                        'total_descontos'
                    ],

                ':salario_bruto' =>
                    $processamento[
                        'salario_bruto'
                    ],

                ':salario_liquido' =>
                    $processamento[
                        'salario_liquido'
                    ],

                ':valor_pago' =>
                    $processamento[
                        'valor_pago'
                    ],

                ':saldo_pendente' =>
                    $processamento[
                        'saldo_pendente'
                    ],

                ':status_id' =>
                    $statusId,

                ':emitido_por' =>
                    $usuarioId,

                ':observacoes' =>
                    $observacoes,

                ':criado_por' =>
                    $usuarioId
            ]);

            $reciboId =
                (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'id' => $reciboId,
                'codigo' => $codigo
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
    | Gerar todos os recibos de uma folha
    |--------------------------------------------------------------------------
    */
    public function gerarTodos(
        int $folhaSalarialId,
        int $empresaId,
        int $usuarioId,
        ?string $observacoes = null
    ): array {
        $processamentos =
            $this->listarProcessamentosSemRecibo(
                $folhaSalarialId,
                $empresaId
            );

        if (empty($processamentos)) {
            return [
                'gerados' => 0,
                'ignorados' => 0,
                'erros' => [],
                'message' =>
                    'Não existem processamentos pendentes de recibo.'
            ];
        }

        $gerados = 0;
        $ignorados = 0;
        $erros = [];

        foreach ($processamentos as $processamento) {
            try {
                $this->gerarIndividual(
    (int) $processamento['id'],
    $folhaSalarialId,
    $empresaId,
    $usuarioId,
    $observacoes
);

                $gerados++;
            } catch (Throwable $e) {
                $ignorados++;

                $erros[] = [
                    'processamento_id' =>
                        (int) $processamento['id'],

                    'funcionario' =>
                        $processamento[
                            'funcionario_nome'
                        ],

                    'erro' =>
                        $e->getMessage()
                ];
            }
        }

        return [
            'gerados' => $gerados,
            'ignorados' => $ignorados,
            'erros' => $erros
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Marcar como enviado
    |--------------------------------------------------------------------------
    */
    public function marcarEnviado(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {
        $statusId =
            $this->buscarStatusId('Enviado');

        if ($statusId === null) {
            throw new RuntimeException(
                'O estado Enviado não foi configurado.'
            );
        }

        $sql = "
            UPDATE recibos_salarios
            SET
                status_id = :status_id,
                enviado_em = NOW(),
                atualizado_por = :usuario_id

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status_id' => $statusId,
            ':usuario_id' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Marcar como entregue
    |--------------------------------------------------------------------------
    */
    public function marcarEntregue(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {
        $statusId =
            $this->buscarStatusId('Entregue');

        if ($statusId === null) {
            throw new RuntimeException(
                'O estado Entregue não foi configurado.'
            );
        }

        $sql = "
            UPDATE recibos_salarios
            SET
                status_id = :status_id,
                entregue_em = NOW(),
                atualizado_por = :usuario_id

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status_id' => $statusId,
            ':usuario_id' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelar recibo
    |--------------------------------------------------------------------------
    */
    public function cancelar(
        int $id,
        int $empresaId,
        int $usuarioId,
        string $motivo
    ): bool {
        $recibo =
            $this->buscar($id, $empresaId);

        if ($recibo === null) {
            throw new RuntimeException(
                'Recibo salarial não encontrado.'
            );
        }

        if ($recibo['estado'] === 'Cancelado') {
            throw new RuntimeException(
                'Este recibo já está cancelado.'
            );
        }

        $statusId =
            $this->buscarStatusId('Cancelado');

        if ($statusId === null) {
            throw new RuntimeException(
                'O estado Cancelado não foi configurado.'
            );
        }

        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5) {
            throw new InvalidArgumentException(
                'Informe um motivo válido para o cancelamento.'
            );
        }

        $sql = "
            UPDATE recibos_salarios
            SET
                status_id = :status_id,

                observacoes = CONCAT(
                    COALESCE(observacoes, ''),
                    CASE
                        WHEN observacoes IS NULL
                          OR observacoes = ''
                            THEN ''
                        ELSE '\n'
                    END,
                    'Cancelado em ',
                    DATE_FORMAT(
                        NOW(),
                        '%d/%m/%Y %H:%i'
                    ),
                    ': ',
                    :motivo
                ),

                atualizado_por = :usuario_id

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status_id' => $statusId,
            ':motivo' => $motivo,
            ':usuario_id' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->rowCount() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */
    public function resumo(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                COUNT(rs.id) AS total_recibos,

                SUM(
                    CASE
                        WHEN st.nome = 'Emitido'
                            THEN 1
                        ELSE 0
                    END
                ) AS total_emitidos,

                SUM(
                    CASE
                        WHEN st.nome = 'Enviado'
                            THEN 1
                        ELSE 0
                    END
                ) AS total_enviados,

                SUM(
                    CASE
                        WHEN st.nome = 'Entregue'
                            THEN 1
                        ELSE 0
                    END
                ) AS total_entregues,

                SUM(
                    CASE
                        WHEN st.nome = 'Cancelado'
                            THEN 1
                        ELSE 0
                    END
                ) AS total_cancelados,

                COALESCE(
                    SUM(
                        CASE
                            WHEN st.nome <> 'Cancelado'
                                THEN rs.salario_liquido
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_liquido,

                COALESCE(
                    SUM(
                        CASE
                            WHEN st.nome <> 'Cancelado'
                                THEN rs.saldo_pendente
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_pendente

            FROM recibos_salarios rs

            INNER JOIN status st
                ON st.id = rs.status_id
               AND st.tipo = :tipo_status

            WHERE rs.empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':tipo_status' => self::TIPO_STATUS
        ]);

        $resumo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resumo ?: [
            'total_recibos' => 0,
            'total_emitidos' => 0,
            'total_enviados' => 0,
            'total_entregues' => 0,
            'total_cancelados' => 0,
            'total_liquido' => 0,
            'total_pendente' => 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Processamentos sem recibo
    |--------------------------------------------------------------------------
    */
    private function listarProcessamentosSemRecibo(
    int $folhaSalarialId,
    int $empresaId
): array {
    $sql = "
        SELECT
            pr.id,
            pr.funcionario_nome

        FROM folhas_salariais fs

        INNER JOIN processamentos_salariais pr
            ON pr.periodo_id = fs.periodo_id
           AND pr.empresa_id = fs.empresa_id

        LEFT JOIN recibos_salarios rs
            ON rs.processamento_id = pr.id
           AND rs.empresa_id = pr.empresa_id

        WHERE fs.id = :folha_salarial_id
          AND fs.empresa_id = :empresa_id
          AND rs.id IS NULL

        ORDER BY
            pr.funcionario_nome ASC,
            pr.id ASC
    ";

    $stmt =
        $this->db->prepare($sql);

    $stmt->execute([
        ':folha_salarial_id' =>
            $folhaSalarialId,

        ':empresa_id' =>
            $empresaId
    ]);

    return $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );
}

    /*
    |--------------------------------------------------------------------------
    | Buscar dados do processamento
    |--------------------------------------------------------------------------
    */
    private function buscarDadosProcessamento(
    int $processamentoId,
    int $folhaSalarialId,
    int $empresaId
): ?array {
    $sql = "
        SELECT
            pr.id,
            pr.empresa_id,

            fs.id AS folha_salarial_id,

            pr.periodo_id,
            pr.funcionario_id,
            pr.contrato_id,

            pr.moeda,

            COALESCE(
                pr.total_salario_base,
                0
            ) AS salario_base,

            COALESCE(
                pr.total_subsidios,
                0
            ) AS total_subsidios,

            COALESCE(
                pr.total_horas_extras,
                0
            ) AS total_horas_extras,

            COALESCE(
                pr.total_outros_proventos,
                0
            ) AS total_outros_proventos,

            COALESCE(
                pr.total_proventos,
                0
            ) AS total_proventos,

            COALESCE(
                pr.total_faltas,
                0
            ) AS total_faltas,

            COALESCE(
                pr.total_atrasos,
                0
            ) AS total_atrasos,

            COALESCE(
                pr.total_inss,
                0
            ) AS total_inss,

            COALESCE(
                pr.total_irps,
                0
            ) AS total_irps,

            COALESCE(
                pr.total_outros_descontos,
                0
            ) AS total_outros_descontos,

            COALESCE(
                pr.total_descontos,
                0
            ) AS total_descontos,

            COALESCE(
                pr.salario_bruto,
                pr.total_proventos,
                0
            ) AS salario_bruto,

            COALESCE(
                pr.salario_liquido,
                0
            ) AS salario_liquido,

            COALESCE(
                pr.valor_pago,
                0
            ) AS valor_pago,

            COALESCE(
                pr.saldo_pendente,
                pr.salario_liquido,
                0
            ) AS saldo_pendente,

            pr.numero_funcionario,
            pr.funcionario_nome,
            pr.cargo_nome,
            pr.departamento_nome,
            pr.filial_nome,

            ps.ano,
            ps.mes

        FROM processamentos_salariais pr

        INNER JOIN folhas_salariais fs
            ON fs.periodo_id = pr.periodo_id
           AND fs.empresa_id = pr.empresa_id

        INNER JOIN periodos_salariais ps
            ON ps.id = pr.periodo_id
           AND ps.empresa_id = pr.empresa_id

        WHERE pr.id = :processamento_id
          AND fs.id = :folha_salarial_id
          AND pr.empresa_id = :empresa_id

        LIMIT 1
    ";

    $stmt =
        $this->db->prepare($sql);

    $stmt->execute([
        ':processamento_id' =>
            $processamentoId,

        ':folha_salarial_id' =>
            $folhaSalarialId,

        ':empresa_id' =>
            $empresaId
    ]);

    $dados =
        $stmt->fetch(PDO::FETCH_ASSOC);

    return $dados ?: null;
}

    /*
    |--------------------------------------------------------------------------
    | Buscar recibo por processamento
    |--------------------------------------------------------------------------
    */
    private function buscarPorProcessamento(
        int $processamentoId,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                id,
                codigo

            FROM recibos_salarios

            WHERE processamento_id =
                :processamento_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':processamento_id' =>
                $processamentoId,

            ':empresa_id' =>
                $empresaId
        ]);

        $recibo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $recibo ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar ID do estado
    |--------------------------------------------------------------------------
    */
    private function buscarStatusId(
        string $nome
    ): ?int {
        $sql = "
            SELECT id

            FROM status

            WHERE tipo = :tipo
              AND nome = :nome

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':tipo' => self::TIPO_STATUS,
            ':nome' => $nome
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false
            ? (int) $id
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Gerar código do recibo
    |--------------------------------------------------------------------------
    */
    private function gerarCodigo(
        int $empresaId,
        int $ano,
        int $mes
    ): string {
        $prefixo = sprintf(
            'RS-%04d-%02d-',
            $ano,
            $mes
        );

        $sql = "
            SELECT codigo

            FROM recibos_salarios

            WHERE empresa_id = :empresa_id
              AND codigo LIKE :prefixo

            ORDER BY id DESC

            LIMIT 1

            FOR UPDATE
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':prefixo' => $prefixo . '%'
        ]);

        $ultimoCodigo =
            $stmt->fetchColumn();

        $sequencia = 1;

        if ($ultimoCodigo) {
            $partes =
                explode('-', $ultimoCodigo);

            $ultimoNumero =
                (int) end($partes);

            $sequencia =
                $ultimoNumero + 1;
        }

        return $prefixo
            . str_pad(
                (string) $sequencia,
                5,
                '0',
                STR_PAD_LEFT
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Gerar UUID
    |--------------------------------------------------------------------------
    */
    private function gerarUuid(): string
    {
        $dados = random_bytes(16);

        $dados[6] =
            chr(
                ord($dados[6])
                & 0x0f
                | 0x40
            );

        $dados[8] =
            chr(
                ord($dados[8])
                & 0x3f
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