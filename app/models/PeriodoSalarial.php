<?php

declare(strict_types=1);

final class PeriodoSalarial
{
    private PDO $db;

    private const TIPO_STATUS = 'periodo_salarial';
    private const TIPO_PROCESSAMENTO = 'processamento_salarial';
    private const TIPO_PAGAMENTO = 'pagamento_salarial';

    private const STATUS_ABERTO = 'Aberto';
    private const STATUS_EM_PROCESSAMENTO = 'Em Processamento';
    private const STATUS_PROCESSADO = 'Processado';
    private const STATUS_FECHADO = 'Fechado';
    private const STATUS_CANCELADO = 'Cancelado';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {
        $sql = "
        SELECT
            ps.id,
            ps.uuid,
            ps.empresa_id,
            ps.codigo,
            ps.descricao,
            ps.ano,
            ps.mes,
            ps.data_inicio,
            ps.data_fim,
            ps.data_limite_assiduidade,
            ps.data_prevista_pagamento,
            ps.dias_uteis,
            ps.observacoes,
            ps.status_id,
            ps.aberto_por,
            ps.aberto_em,
            ps.fechado_por,
            ps.fechado_em,
            ps.cancelado_por,
            ps.cancelado_em,
            ps.motivo_cancelamento,
            ps.criado_por,
            ps.atualizado_por,
            ps.criado_em,
            ps.atualizado_em,

            s.tipo AS status_tipo,
            s.nome AS status_nome,
            s.descricao AS status_descricao

        FROM periodos_salariais ps

        INNER JOIN status s
            ON s.id = ps.status_id
            AND s.tipo = 'periodo_salarial'

        WHERE ps.empresa_id = :empresa_id
    ";

        $parametros = [
            ':empresa_id' => $empresaId
        ];

        if (
            isset($filtros['ano']) &&
            $filtros['ano'] !== ''
        ) {
            $sql .= " AND ps.ano = :ano";
            $parametros[':ano'] = (int) $filtros['ano'];
        }

        if (
            isset($filtros['mes']) &&
            $filtros['mes'] !== ''
        ) {
            $sql .= " AND ps.mes = :mes";
            $parametros[':mes'] = (int) $filtros['mes'];
        }

        if (
            isset($filtros['status_id']) &&
            $filtros['status_id'] !== ''
        ) {
            $sql .= " AND ps.status_id = :status_id";

            $parametros[':status_id'] =
                (int) $filtros['status_id'];
        }

        $sql .= "
        ORDER BY
            ps.ano DESC,
            ps.mes DESC,
            ps.id DESC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscarPorId(
        int $id,
        int $empresaId
    ): ?array {
        $sql = "
        SELECT
            ps.id,
            ps.uuid,
            ps.empresa_id,
            ps.codigo,
            ps.descricao,
            ps.ano,
            ps.mes,
            ps.data_inicio,
            ps.data_fim,
            ps.data_limite_assiduidade,
            ps.data_prevista_pagamento,
            ps.dias_calendario,
            ps.dias_uteis,
            ps.observacoes,
            ps.status_id,

            ps.aberto_por,
            ps.aberto_em,

            ps.fechado_por,
            ps.fechado_em,

            ps.cancelado_por,
            ps.cancelado_em,
            ps.motivo_cancelamento,

            ps.criado_por,
            ps.atualizado_por,
            ps.criado_em,
            ps.atualizado_em,

            s.tipo AS status_tipo,
            s.nome AS status_nome,
            s.descricao AS status_descricao

        FROM periodos_salariais ps

        INNER JOIN status s
            ON s.id = ps.status_id
            AND s.tipo = :tipo_status

        WHERE ps.id = :id
          AND ps.empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':tipo_status' => self::TIPO_STATUS,
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $periodo !== false
            ? $periodo
            : null;
    }

    public function buscarPorUuid(string $uuid, int $empresaId): ?array
    {
        $sql = "
            SELECT
                ps.*,
                s.nome AS status_nome,
                s.nome AS status_codigo
            FROM periodos_salariais ps
            INNER JOIN status s
                ON s.id = ps.status_id
            WHERE ps.uuid = :uuid
              AND ps.empresa_id = :empresa_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uuid' => trim($uuid),
            ':empresa_id' => $empresaId,
        ]);

        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $periodo !== false ? $periodo : null;
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(array $dados, int $empresaId, int $usuarioId): array
    {
        $dados = $this->normalizarDados($dados);
        $erros = $this->validarDados($dados);

        if ($erros !== []) {
            return $this->respostaErro(
                'Existem campos inválidos.',
                $erros
            );
        }

        if ($this->existePeriodo(
            $empresaId,
            (int)$dados['ano'],
            (int)$dados['mes']
        )) {
            return $this->respostaErro(
                'Já existe um período salarial para o ano e mês seleccionados.',
                [
                    'ano' => 'Já existe um período para este ano e mês.',
                    'mes' => 'Já existe um período para este ano e mês.',
                ]
            );
        }

        if ($this->existeCodigo($empresaId, (string)$dados['codigo'])) {
            return $this->respostaErro(
                'O código informado já está a ser utilizado.',
                ['codigo' => 'Informe um código diferente.']
            );
        }

        $statusAbertoId = $this->obterStatusId(self::STATUS_ABERTO);

        if ($statusAbertoId === null) {
            return $this->respostaErro(
                'O estado "Aberto" não está configurado para períodos salariais.'
            );
        }

        try {
            $this->db->beginTransaction();

            $sql = "
                INSERT INTO periodos_salariais (
                    uuid,
                    empresa_id,
                    codigo,
                    descricao,
                    ano,
                    mes,
                    data_inicio,
                    data_fim,
                    data_limite_assiduidade,
                    data_prevista_pagamento,
                    dias_calendario,
                    dias_uteis,
                    observacoes,
                    status_id,
                    aberto_por,
                    aberto_em,
                    criado_por,
                    atualizado_por,
                    criado_em,
                    atualizado_em
                ) VALUES (
                    :uuid,
                    :empresa_id,
                    :codigo,
                    :descricao,
                    :ano,
                    :mes,
                    :data_inicio,
                    :data_fim,
                    :data_limite_assiduidade,
                    :data_prevista_pagamento,
                    :dias_calendario,
                    :dias_uteis,
                    :observacoes,
                    :status_id,
                    :aberto_por,
                    CURRENT_TIMESTAMP,
                    :criado_por,
                    :atualizado_por,
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                )
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':uuid' => $this->gerarUuidV4(),
                ':empresa_id' => $empresaId,
                ':codigo' => $dados['codigo'],
                ':descricao' => $dados['descricao'],
                ':ano' => $dados['ano'],
                ':mes' => $dados['mes'],
                ':data_inicio' => $dados['data_inicio'],
                ':data_fim' => $dados['data_fim'],
                ':data_limite_assiduidade' =>
                $dados['data_limite_assiduidade'],
                ':data_prevista_pagamento' =>
                $dados['data_prevista_pagamento'],
                ':dias_calendario' =>
                $dados['dias_calendario'],
                ':dias_uteis' =>
                $dados['dias_uteis'],
                ':observacoes' =>
                $dados['observacoes'],
                ':status_id' =>
                $statusAbertoId,
                ':aberto_por' =>
                $usuarioId,
                ':criado_por' =>
                $usuarioId,
                ':atualizado_por' =>
                $usuarioId
            ]);

            $id = (int)$this->db->lastInsertId();

            $this->db->commit();

            return $this->respostaSucesso(
                'Período salarial criado com sucesso.',
                $this->buscarPorId($id, $empresaId)
            );
        } catch (Throwable $e) {
            $this->reverterTransacao();
            $this->registarErro($e);

            return $this->respostaErro(
                'Não foi possível criar o período salarial.'
            );
        }
    }

    // ==================================================
    // ATUALIZAR
    // ==================================================

    public function atualizar(
        int $id,
        array $dados,
        int $empresaId,
        int $usuarioId
    ): array {
        $periodo = $this->buscarPorId($id, $empresaId);

        if ($periodo === null) {
            return $this->respostaErro('Período salarial não encontrado.');
        }

        if (!$this->estadoIgual($periodo, self::STATUS_ABERTO)) {
            return $this->respostaErro(
                'Apenas períodos abertos podem ser editados.'
            );
        }

        $dados = $this->normalizarDados($dados);
        $erros = $this->validarDados($dados);

        if ($erros !== []) {
            return $this->respostaErro(
                'Existem campos inválidos.',
                $erros
            );
        }

        if ($this->existePeriodo(
            $empresaId,
            (int)$dados['ano'],
            (int)$dados['mes'],
            $id
        )) {
            return $this->respostaErro(
                'Já existe outro período para o ano e mês seleccionados.',
                [
                    'ano' => 'Já existe outro período para este ano e mês.',
                    'mes' => 'Já existe outro período para este ano e mês.',
                ]
            );
        }

        if ($this->existeCodigo(
            $empresaId,
            (string)$dados['codigo'],
            $id
        )) {
            return $this->respostaErro(
                'O código informado já está a ser utilizado por outro período.',
                ['codigo' => 'Informe um código diferente.']
            );
        }

        try {
            $this->db->beginTransaction();

            $sql = "
                UPDATE periodos_salariais
                SET
                    codigo = :codigo,
                    descricao = :descricao,
                    ano = :ano,
                    mes = :mes,
                    data_inicio = :data_inicio,
                    data_fim = :data_fim,
                    data_limite_assiduidade = :data_limite_assiduidade,
                    data_prevista_pagamento = :data_prevista_pagamento,
                    dias_calendario = :dias_calendario,
                    dias_uteis = :dias_uteis,
                    observacoes = :observacoes,
                    atualizado_por = :atualizado_por,
                    atualizado_em = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND empresa_id = :empresa_id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':codigo' => $dados['codigo'],
                ':descricao' => $dados['descricao'],
                ':ano' => $dados['ano'],
                ':mes' => $dados['mes'],
                ':data_inicio' => $dados['data_inicio'],
                ':data_fim' => $dados['data_fim'],
                ':data_limite_assiduidade' => $dados['data_limite_assiduidade'],
                ':data_prevista_pagamento' => $dados['data_prevista_pagamento'],
                ':dias_calendario' => $dados['dias_calendario'],
                ':dias_uteis' => $dados['dias_uteis'],
                ':observacoes' => $dados['observacoes'],
                ':atualizado_por' => $usuarioId,
                ':id' => $id,
                ':empresa_id' => $empresaId,
            ]);

            $this->db->commit();

            return $this->respostaSucesso(
                'Período salarial atualizado com sucesso.',
                $this->buscarPorId($id, $empresaId)
            );
        } catch (Throwable $e) {
            $this->reverterTransacao();
            $this->registarErro($e);

            return $this->respostaErro(
                'Não foi possível atualizar o período salarial.'
            );
        }
    }

    // ==================================================
    // TRANSIÇÕES DE ESTADO
    // ==================================================

    public function fechar(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {
        $statusAtual =
            $this->buscarStatusIdPorNome(
                self::STATUS_PROCESSADO
            );

        $novoStatus =
            $this->buscarStatusIdPorNome(
                self::STATUS_FECHADO
            );

        if (
            $statusAtual === null
            || $novoStatus === null
        ) {
            throw new RuntimeException(
                'Os estados do período salarial não estão configurados.'
            );
        }

        $sql = "
        UPDATE periodos_salariais

        SET
            status_id = :novo_status,
            fechado_por = :fechado_por,
            fechado_em = CURRENT_TIMESTAMP,
            atualizado_por = :atualizado_por,
            atualizado_em = CURRENT_TIMESTAMP

        WHERE id = :id
          AND empresa_id = :empresa_id
          AND status_id = :status_atual
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':novo_status' => $novoStatus,
            ':fechado_por' => $usuarioId,
            ':atualizado_por' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId,
            ':status_atual' => $statusAtual
        ]);

        return $stmt->rowCount() === 1;
    }

    public function reabrir(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {
        $statusAtual =
            $this->buscarStatusIdPorNome(
                self::STATUS_FECHADO
            );

        $novoStatus =
            $this->buscarStatusIdPorNome(
                self::STATUS_ABERTO
            );

        if (
            $statusAtual === null
            || $novoStatus === null
        ) {
            throw new RuntimeException(
                'Os estados do período salarial não estão configurados.'
            );
        }

        $sql = "
        UPDATE periodos_salariais

        SET
            status_id = :novo_status,
            aberto_por = :aberto_por,
            aberto_em = CURRENT_TIMESTAMP,
            fechado_por = NULL,
            fechado_em = NULL,
            atualizado_por = :atualizado_por,
            atualizado_em = CURRENT_TIMESTAMP

        WHERE id = :id
          AND empresa_id = :empresa_id
          AND status_id = :status_atual
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':novo_status' => $novoStatus,
            ':aberto_por' => $usuarioId,
            ':atualizado_por' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId,
            ':status_atual' => $statusAtual
        ]);

        return $stmt->rowCount() === 1;
    }

    public function iniciarProcessamento(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {
        $statusAtual =
            $this->buscarStatusIdPorNome(
                self::STATUS_ABERTO
            );

        $novoStatus =
            $this->buscarStatusIdPorNome(
                self::STATUS_EM_PROCESSAMENTO
            );

        if (
            $statusAtual === null
            || $novoStatus === null
        ) {
            throw new RuntimeException(
                'Os estados do período salarial não estão configurados.'
            );
        }

        $sql = "
        UPDATE periodos_salariais

        SET
            status_id = :novo_status,
            atualizado_por = :atualizado_por,
            atualizado_em = CURRENT_TIMESTAMP

        WHERE id = :id
          AND empresa_id = :empresa_id
          AND status_id = :status_atual
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':novo_status' => $novoStatus,
            ':atualizado_por' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId,
            ':status_atual' => $statusAtual
        ]);

        return $stmt->rowCount() === 1;
    }

    public function concluirProcessamento(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {
        $statusAtual =
            $this->buscarStatusIdPorNome(
                self::STATUS_EM_PROCESSAMENTO
            );

        $novoStatus =
            $this->buscarStatusIdPorNome(
                self::STATUS_PROCESSADO
            );

        if (
            $statusAtual === null
            || $novoStatus === null
        ) {
            throw new RuntimeException(
                'Os estados do período salarial não estão configurados.'
            );
        }

        $sql = "
        UPDATE periodos_salariais

        SET
            status_id = :novo_status,
            atualizado_por = :atualizado_por,
            atualizado_em = CURRENT_TIMESTAMP

        WHERE id = :id
          AND empresa_id = :empresa_id
          AND status_id = :status_atual
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':novo_status' => $novoStatus,
            ':atualizado_por' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId,
            ':status_atual' => $statusAtual
        ]);

        return $stmt->rowCount() === 1;
    }

    public function cancelar(
        int $id,
        int $empresaId,
        string $motivo,
        int $usuarioId
    ): bool {
        $statusCancelado =
            $this->buscarStatusIdPorNome(
                self::STATUS_CANCELADO
            );

        if ($statusCancelado === null) {
            throw new RuntimeException(
                'O estado Cancelado não está configurado.'
            );
        }

        $sql = "
        UPDATE periodos_salariais

        SET
            status_id = :status_id,
            motivo_cancelamento = :motivo,
            cancelado_por = :cancelado_por,
            cancelado_em = CURRENT_TIMESTAMP,
            atualizado_por = :atualizado_por,
            atualizado_em = CURRENT_TIMESTAMP

        WHERE id = :id
          AND empresa_id = :empresa_id
          AND status_id <> :status_cancelado
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status_id' => $statusCancelado,
            ':motivo' => $motivo,
            ':cancelado_por' => $usuarioId,
            ':atualizado_por' => $usuarioId,
            ':id' => $id,
            ':empresa_id' => $empresaId,
            ':status_cancelado' => $statusCancelado
        ]);

        return $stmt->rowCount() === 1;
    }
    // ==================================================
    // ESTADOS, ANOS E RESUMO
    // ==================================================

    public function listarEstados(): array
    {
        $sql = "
        SELECT
            id,
            tipo,
            nome,
            descricao

        FROM status

        WHERE tipo = :tipo

        ORDER BY id ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':tipo' => 'periodo_salarial'
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarStatusIdPorNome(
        string $nome
    ): ?int {
        $sql = "
        SELECT id

        FROM status

        WHERE tipo = :tipo
          AND LOWER(nome) = LOWER(:nome)

        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':tipo' => 'periodo_salarial',
            ':nome' => $nome
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false
            ? (int) $id
            : null;
    }

    public function listarAnos(int $empresaId): array
    {
        $sql = "
            SELECT DISTINCT ano
            FROM periodos_salariais
            WHERE empresa_id = :empresa_id
            ORDER BY ano DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
        ]);

        return array_map(
            static fn(array $item): int => (int)$item['ano'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function resumo(
        int $empresaId
    ): array {
        $sql = "
        SELECT
            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN LOWER(s.nome) = 'aberto'
                    THEN 1
                    ELSE 0
                END
            ) AS abertos,

            SUM(
                CASE
                    WHEN LOWER(s.nome) = 'em processamento'
                    THEN 1
                    ELSE 0
                END
            ) AS em_processamento,

            SUM(
                CASE
                    WHEN LOWER(s.nome) = 'processado'
                    THEN 1
                    ELSE 0
                END
            ) AS processados,

            SUM(
                CASE
                    WHEN LOWER(s.nome) = 'fechado'
                    THEN 1
                    ELSE 0
                END
            ) AS fechados,

            SUM(
                CASE
                    WHEN LOWER(s.nome) = 'cancelado'
                    THEN 1
                    ELSE 0
                END
            ) AS cancelados

        FROM periodos_salariais ps

        INNER JOIN status s
            ON s.id = ps.status_id
            AND s.tipo = 'periodo_salarial'

        WHERE ps.empresa_id = :empresa_id
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $resumo = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($resumo['total'] ?? 0),

            'abertos' =>
            (int) ($resumo['abertos'] ?? 0),

            'em_processamento' =>
            (int) ($resumo['em_processamento'] ?? 0),

            'processados' =>
            (int) ($resumo['processados'] ?? 0),

            'fechados' =>
            (int) ($resumo['fechados'] ?? 0),

            'cancelados' =>
            (int) ($resumo['cancelados'] ?? 0)
        ];
    }

    // ==================================================
    // AUXILIARES DE ESTADO
    // ==================================================

    private function alterarEstado(
        int $id,
        int $empresaId,
        int $usuarioId,
        string $novoStatus,
        string $mensagem,
        bool $limparMotivoCancelamento = false
    ): array {
        $statusId = $this->obterStatusId($novoStatus);

        if ($statusId === null) {
            return $this->respostaErro(sprintf(
                'O estado "%s" não está configurado.',
                $novoStatus
            ));
        }

        try {
            $this->db->beginTransaction();

            $sql = "
                UPDATE periodos_salariais
                SET
                    status_id = :status_id,
                    motivo_cancelamento = CASE
                        WHEN :limpar_motivo = 1 THEN NULL
                        ELSE motivo_cancelamento
                    END,
                    atualizado_por = :atualizado_por,
                    atualizado_em = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND empresa_id = :empresa_id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':status_id' => $statusId,
                ':limpar_motivo' => $limparMotivoCancelamento ? 1 : 0,
                ':atualizado_por' => $usuarioId,
                ':id' => $id,
                ':empresa_id' => $empresaId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'O estado do período não foi actualizado.'
                );
            }

            $this->db->commit();

            return $this->respostaSucesso(
                $mensagem,
                $this->buscarPorId($id, $empresaId)
            );
        } catch (Throwable $e) {
            $this->reverterTransacao();
            $this->registarErro($e);

            return $this->respostaErro(
                'Não foi possível alterar o estado do período.'
            );
        }
    }

    private function obterStatusId(string $nome): ?int
    {
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
            ':nome' => $nome,
        ]);

        $statusId = $stmt->fetchColumn();

        return $statusId !== false ? (int)$statusId : null;
    }

    private function estadoIgual(array $periodo, string $estado): bool
    {
        return isset($periodo['status_nome'])
            && (string)$periodo['status_nome'] === $estado;
    }

    // ==================================================
    // VERIFICAÇÕES DE DUPLICIDADE E DEPENDÊNCIAS
    // ==================================================

    private function existePeriodo(
        int $empresaId,
        int $ano,
        int $mes,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT 1
            FROM periodos_salariais
            WHERE empresa_id = :empresa_id
              AND ano = :ano
              AND mes = :mes
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':ano' => $ano,
            ':mes' => $mes,
        ];

        if ($ignorarId !== null) {
            $sql .= ' AND id <> :ignorar_id';
            $parametros[':ignorar_id'] = $ignorarId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchColumn() !== false;
    }

    private function existeCodigo(
        int $empresaId,
        string $codigo,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT 1
            FROM periodos_salariais
            WHERE empresa_id = :empresa_id
              AND codigo = :codigo
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':codigo' => $codigo,
        ];

        if ($ignorarId !== null) {
            $sql .= ' AND id <> :ignorar_id';
            $parametros[':ignorar_id'] = $ignorarId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchColumn() !== false;
    }

    private function periodoPossuiProcessamentoActivo(
        int $periodoId,
        int $empresaId
    ): bool {
        if (!$this->tabelaExiste('processamentos_salariais')) {
            return false;
        }

        $sql = "
            SELECT 1
            FROM processamentos_salariais p
            INNER JOIN status s
                ON s.id = p.status_id
            WHERE p.periodo_salarial_id = :periodo_id
              AND p.empresa_id = :empresa_id
              AND s.tipo = :tipo_status
              AND s.nome <> :status_cancelado
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':periodo_id' => $periodoId,
            ':empresa_id' => $empresaId,
            ':tipo_status' => self::TIPO_PROCESSAMENTO,
            ':status_cancelado' => self::STATUS_CANCELADO,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function periodoPossuiPagamentos(
        int $periodoId,
        int $empresaId
    ): bool {
        if (
            !$this->tabelaExiste('pagamentos_salariais')
            || !$this->tabelaExiste('processamentos_salariais')
        ) {
            return false;
        }

        $sql = "
            SELECT 1
            FROM pagamentos_salariais pg
            INNER JOIN processamentos_salariais pr
                ON pr.id = pg.processamento_salarial_id
            INNER JOIN status s
                ON s.id = pg.status_id
            WHERE pr.periodo_salarial_id = :periodo_id
              AND pg.empresa_id = :empresa_id
              AND s.tipo = :tipo_status
              AND s.nome <> :status_cancelado
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':periodo_id' => $periodoId,
            ':empresa_id' => $empresaId,
            ':tipo_status' => self::TIPO_PAGAMENTO,
            ':status_cancelado' => self::STATUS_CANCELADO,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function tabelaExiste(string $nomeTabela): bool
    {
        $sql = "
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :tabela
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':tabela' => $nomeTabela,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    // ==================================================
    // NORMALIZAÇÃO E VALIDAÇÃO
    // ==================================================

    private function normalizarDados(array $dados): array
    {
        $dataInicio = trim((string)($dados['data_inicio'] ?? ''));
        $dataFim = trim((string)($dados['data_fim'] ?? ''));

        $diasCalendario = 0;

        if (
            $this->dataValida($dataInicio)
            && $this->dataValida($dataFim)
            && $dataFim >= $dataInicio
        ) {
            $diasCalendario = $this->calcularDiasCalendario(
                $dataInicio,
                $dataFim
            );
        }

        return [
            'codigo' => mb_strtoupper(
                trim((string)($dados['codigo'] ?? '')),
                'UTF-8'
            ),
            'descricao' => trim((string)($dados['descricao'] ?? '')),
            'ano' => (int)($dados['ano'] ?? 0),
            'mes' => (int)($dados['mes'] ?? 0),
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'data_limite_assiduidade' => $this->normalizarDataOpcional(
                $dados['data_limite_assiduidade'] ?? null
            ),
            'data_prevista_pagamento' => $this->normalizarDataOpcional(
                $dados['data_prevista_pagamento'] ?? null
            ),
            'dias_calendario' => $diasCalendario,
            'dias_uteis' => $this->normalizarDecimal(
                $dados['dias_uteis'] ?? 0
            ),
            'observacoes' => $this->normalizarTextoOpcional(
                $dados['observacoes'] ?? null
            ),
        ];
    }

    private function validarDados(array $dados): array
    {
        $erros = [];

        if ($dados['codigo'] === '') {
            $erros['codigo'] = 'Informe o código do período.';
        } elseif (mb_strlen((string)$dados['codigo']) > 30) {
            $erros['codigo'] = 'O código não pode ultrapassar 30 caracteres.';
        } elseif (!preg_match('/^[\p{L}0-9_-]+$/u', (string)$dados['codigo'])) {
            $erros['codigo'] =
                'Use apenas letras, números, hífen e sublinhado no código.';
        }

        if ($dados['descricao'] === '') {
            $erros['descricao'] = 'Informe a descrição do período.';
        } elseif (mb_strlen((string)$dados['descricao']) > 120) {
            $erros['descricao'] =
                'A descrição não pode ultrapassar 120 caracteres.';
        }

        if ($dados['ano'] < 2000 || $dados['ano'] > 2100) {
            $erros['ano'] = 'Informe um ano entre 2000 e 2100.';
        }

        if ($dados['mes'] < 1 || $dados['mes'] > 12) {
            $erros['mes'] = 'Seleccione um mês válido.';
        }

        if (!$this->dataValida((string)$dados['data_inicio'])) {
            $erros['data_inicio'] = 'Informe uma data inicial válida.';
        }

        if (!$this->dataValida((string)$dados['data_fim'])) {
            $erros['data_fim'] = 'Informe uma data final válida.';
        }

        if (
            $this->dataValida((string)$dados['data_inicio'])
            && $this->dataValida((string)$dados['data_fim'])
        ) {
            if ($dados['data_fim'] < $dados['data_inicio']) {
                $erros['data_fim'] =
                    'A data final não pode ser anterior à data inicial.';
            }

            $inicio = new DateTimeImmutable((string)$dados['data_inicio']);

            if (
                (int)$inicio->format('Y') !== (int)$dados['ano']
                || (int)$inicio->format('n') !== (int)$dados['mes']
            ) {
                $erros['data_inicio'] =
                    'A data inicial deve pertencer ao ano e mês seleccionados.';
            }
        }

        $this->validarDataOpcional(
            $dados['data_limite_assiduidade'],
            'data_limite_assiduidade',
            'A data limite da assiduidade é inválida.',
            $erros
        );

        $this->validarDataOpcional(
            $dados['data_prevista_pagamento'],
            'data_prevista_pagamento',
            'A data prevista de pagamento é inválida.',
            $erros
        );

        if ((float)$dados['dias_uteis'] < 0) {
            $erros['dias_uteis'] =
                'O número de dias úteis não pode ser negativo.';
        } elseif ((float)$dados['dias_uteis'] > (int)$dados['dias_calendario']) {
            $erros['dias_uteis'] =
                'Os dias úteis não podem ser superiores aos dias de calendário.';
        }

        if (
            $dados['observacoes'] !== null
            && mb_strlen((string)$dados['observacoes']) > 1000
        ) {
            $erros['observacoes'] =
                'As observações não podem ultrapassar 1000 caracteres.';
        }

        return $erros;
    }

    private function validarDataOpcional(
        mixed $valor,
        string $campo,
        string $mensagem,
        array &$erros
    ): void {
        if ($valor !== null && !$this->dataValida((string)$valor)) {
            $erros[$campo] = $mensagem;
        }
    }

    private function calcularDiasCalendario(
        string $dataInicio,
        string $dataFim
    ): int {
        $inicio = new DateTimeImmutable($dataInicio);
        $fim = new DateTimeImmutable($dataFim);

        return (int)$inicio->diff($fim)->days + 1;
    }

    private function dataValida(string $data): bool
    {
        if ($data === '') {
            return false;
        }

        $objetoData = DateTimeImmutable::createFromFormat('Y-m-d', $data);

        return $objetoData !== false
            && $objetoData->format('Y-m-d') === $data;
    }

    private function normalizarDataOpcional(mixed $valor): ?string
    {
        $valor = trim((string)$valor);

        return $valor !== '' ? $valor : null;
    }

    private function normalizarTextoOpcional(mixed $valor): ?string
    {
        $valor = trim((string)$valor);

        return $valor !== '' ? $valor : null;
    }

    private function normalizarDecimal(mixed $valor): string
    {
        $valor = str_replace(',', '.', trim((string)$valor));

        if ($valor === '' || !is_numeric($valor)) {
            return '0.00';
        }

        return number_format((float)$valor, 2, '.', '');
    }

    // ==================================================
    // RESPOSTAS, LOG E TRANSAÇÃO
    // ==================================================

    private function respostaSucesso(string $mensagem, mixed $dados = null): array
    {
        $resposta = [
            'sucesso' => true,
            'mensagem' => $mensagem,
        ];

        if ($dados !== null) {
            $resposta['dados'] = $dados;
        }

        return $resposta;
    }

    private function respostaErro(string $mensagem, array $erros = []): array
    {
        $resposta = [
            'sucesso' => false,
            'mensagem' => $mensagem,
        ];

        if ($erros !== []) {
            $resposta['erros'] = $erros;
        }

        return $resposta;
    }

    private function reverterTransacao(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    private function registarErro(Throwable $e): void
    {
        error_log(sprintf(
            '[PeriodoSalarial] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    private function gerarUuidV4(): string
    {
        $dados = random_bytes(16);
        $dados[6] = chr((ord($dados[6]) & 0x0f) | 0x40);
        $dados[8] = chr((ord($dados[8]) & 0x3f) | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($dados), 4)
        );
    }
}
