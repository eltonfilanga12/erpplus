<?php

declare(strict_types=1);

final class RubricaSalarial
{
    private PDO $db;

    private const TIPO_STATUS = 'rubrica_salarial';

    private const TIPOS = [
        'PROVENTO',
        'DESCONTO',
        'ENCARGO',
        'INFORMATIVO',
    ];

    private const CATEGORIAS = [
        'SALARIO_BASE',
        'SUBSIDIO_ALIMENTACAO',
        'SUBSIDIO_TRANSPORTE',
        'SUBSIDIO_COMUNICACAO',
        'SUBSIDIO_TURNO',
        'SUBSIDIO_RISCO',
        'SUBSIDIO_OUTRO',
        'HORA_EXTRA',
        'BONUS',
        'PREMIO',
        'COMISSAO',
        'FALTA',
        'ATRASO',
        'INSS',
        'INSS_PATRONAL',
        'IRPS',
        'ADIANTAMENTO',
        'EMPRESTIMO',
        'PENSAO',
        'OUTRO',
    ];

    private const MODOS_CALCULO = [
        'VALOR_FIXO',
        'PERCENTAGEM',
        'QUANTIDADE_VALOR',
        'FORMULA',
        'MANUAL',
    ];

    private const BASES_CALCULO = [
        'NENHUMA',
        'SALARIO_BASE',
        'TOTAL_PROVENTOS',
        'SALARIO_BRUTO',
        'VALOR_HORA',
        'VALOR_DIA',
    ];

    public function __construct()
    {
        $this->db = Database::conn();
    }

    /**
     * Lista rubricas da empresa com filtros opcionais.
     */
    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {
        $sql = "
            SELECT
                rs.id,
                rs.uuid,
                rs.empresa_id,
                rs.codigo,
                rs.nome,
                rs.descricao,
                rs.tipo,
                rs.categoria,
                rs.modo_calculo,
                rs.base_calculo,
                rs.valor_padrao,
                rs.percentagem_padrao,
                rs.formula_calculo,
                rs.tributavel_inss,
                rs.tributavel_irps,
                rs.obrigatoria,
                rs.recorrente,
                rs.permite_edicao,
                rs.ordem,
                rs.status_id,
                rs.criado_por,
                rs.atualizado_por,
                rs.criado_em,
                rs.atualizado_em,

                s.tipo AS status_tipo,
                s.nome AS status_nome,
                s.descricao AS status_descricao,

                uc.nome AS criado_por_nome,
                ua.nome AS atualizado_por_nome

            FROM rubricas_salariais rs

            INNER JOIN status s
                ON s.id = rs.status_id

            LEFT JOIN usuarios uc
                ON uc.id = rs.criado_por

            LEFT JOIN usuarios ua
                ON ua.id = rs.atualizado_por

            WHERE rs.empresa_id = :empresa_id
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
        ];

        if (!empty($filtros['codigo'])) {
            $sql .= " AND rs.codigo LIKE :codigo";
            $parametros[':codigo'] = '%' . trim((string) $filtros['codigo']) . '%';
        }

        if (!empty($filtros['nome'])) {
            $sql .= " AND rs.nome LIKE :nome";
            $parametros[':nome'] = '%' . trim((string) $filtros['nome']) . '%';
        }

        if (!empty($filtros['tipo'])) {
            $tipo = strtoupper(trim((string) $filtros['tipo']));

            if (in_array($tipo, self::TIPOS, true)) {
                $sql .= " AND rs.tipo = :tipo";
                $parametros[':tipo'] = $tipo;
            }
        }

        if (!empty($filtros['categoria'])) {
            $categoria = strtoupper(trim((string) $filtros['categoria']));

            if (in_array($categoria, self::CATEGORIAS, true)) {
                $sql .= " AND rs.categoria = :categoria";
                $parametros[':categoria'] = $categoria;
            }
        }

        if (!empty($filtros['modo_calculo'])) {
            $modoCalculo = strtoupper(
                trim((string) $filtros['modo_calculo'])
            );

            if (in_array($modoCalculo, self::MODOS_CALCULO, true)) {
                $sql .= " AND rs.modo_calculo = :modo_calculo";
                $parametros[':modo_calculo'] = $modoCalculo;
            }
        }

        if (!empty($filtros['status_id'])) {
            $sql .= " AND rs.status_id = :status_id";
            $parametros[':status_id'] = (int) $filtros['status_id'];
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND UPPER(s.nome) = :estado";
            $parametros[':estado'] = strtoupper(
                trim((string) $filtros['estado'])
            );
        }

        $sql .= "
            ORDER BY
                rs.ordem ASC,
                rs.nome ASC,
                rs.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista apenas as rubricas ativas.
     */
    public function listarAtivas(
        int $empresaId,
        ?string $tipo = null
    ): array {
        $statusId = $this->obterStatusId('Activo');

        $sql = "
            SELECT
                rs.id,
                rs.uuid,
                rs.codigo,
                rs.nome,
                rs.descricao,
                rs.tipo,
                rs.categoria,
                rs.modo_calculo,
                rs.base_calculo,
                rs.valor_padrao,
                rs.percentagem_padrao,
                rs.formula_calculo,
                rs.tributavel_inss,
                rs.tributavel_irps,
                rs.obrigatoria,
                rs.recorrente,
                rs.permite_edicao,
                rs.ordem,
                rs.status_id

            FROM rubricas_salariais rs

            WHERE rs.empresa_id = :empresa_id
              AND rs.status_id = :status_id
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':status_id'  => $statusId,
        ];

        if ($tipo !== null && $tipo !== '') {
            $tipo = strtoupper(trim($tipo));

            if (!in_array($tipo, self::TIPOS, true)) {
                throw new InvalidArgumentException(
                    'O tipo de rubrica informado é inválido.'
                );
            }

            $sql .= " AND rs.tipo = :tipo";
            $parametros[':tipo'] = $tipo;
        }

        $sql .= "
            ORDER BY
                rs.ordem ASC,
                rs.nome ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Procura uma rubrica pelo ID dentro da empresa.
     */
    public function buscarPorId(
        int $id,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                rs.*,
                s.tipo AS status_tipo,
                s.nome AS status_nome,
                s.descricao AS status_descricao,

                uc.nome AS criado_por_nome,
                ua.nome AS atualizado_por_nome

            FROM rubricas_salariais rs

            INNER JOIN status s
                ON s.id = rs.status_id

            LEFT JOIN usuarios uc
                ON uc.id = rs.criado_por

            LEFT JOIN usuarios ua
                ON ua.id = rs.atualizado_por

            WHERE rs.id = :id
              AND rs.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id'         => $id,
            ':empresa_id' => $empresaId,
        ]);

        $rubrica = $stmt->fetch(PDO::FETCH_ASSOC);

        return $rubrica ?: null;
    }

    /**
     * Procura uma rubrica pelo UUID dentro da empresa.
     */
    public function buscarPorUuid(
        string $uuid,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                rs.*,
                s.tipo AS status_tipo,
                s.nome AS status_nome,
                s.descricao AS status_descricao,

                uc.nome AS criado_por_nome,
                ua.nome AS atualizado_por_nome

            FROM rubricas_salariais rs

            INNER JOIN status s
                ON s.id = rs.status_id

            LEFT JOIN usuarios uc
                ON uc.id = rs.criado_por

            LEFT JOIN usuarios ua
                ON ua.id = rs.atualizado_por

            WHERE rs.uuid = :uuid
              AND rs.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':uuid'       => trim($uuid),
            ':empresa_id' => $empresaId,
        ]);

        $rubrica = $stmt->fetch(PDO::FETCH_ASSOC);

        return $rubrica ?: null;
    }

    /**
     * Cria uma nova rubrica salarial.
     */
    public function criar(
        array $dados,
        int $empresaId,
        ?int $usuarioId
    ): array {
        $dados = $this->normalizarDados($dados);

        $this->validarDados($dados, $empresaId);

        if ($this->codigoExiste(
            $dados['codigo'],
            $empresaId
        )) {
            throw new RuntimeException(
                'Já existe uma rubrica com este código.'
            );
        }

        $statusId = !empty($dados['status_id'])
            ? (int) $dados['status_id']
            : $this->obterStatusId('Activo');

        $this->validarStatus($statusId);

        $uuid = $this->gerarUuid();

        $sql = "
            INSERT INTO rubricas_salariais (
                uuid,
                empresa_id,
                codigo,
                nome,
                descricao,
                tipo,
                categoria,
                modo_calculo,
                base_calculo,
                valor_padrao,
                percentagem_padrao,
                formula_calculo,
                tributavel_inss,
                tributavel_irps,
                obrigatoria,
                recorrente,
                permite_edicao,
                ordem,
                status_id,
                criado_por,
                atualizado_por
            ) VALUES (
                :uuid,
                :empresa_id,
                :codigo,
                :nome,
                :descricao,
                :tipo,
                :categoria,
                :modo_calculo,
                :base_calculo,
                :valor_padrao,
                :percentagem_padrao,
                :formula_calculo,
                :tributavel_inss,
                :tributavel_irps,
                :obrigatoria,
                :recorrente,
                :permite_edicao,
                :ordem,
                :status_id,
                :criado_por,
                :atualizado_por
            )
        ";

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':uuid'                 => $uuid,
                ':empresa_id'           => $empresaId,
                ':codigo'               => $dados['codigo'],
                ':nome'                 => $dados['nome'],
                ':descricao'            => $dados['descricao'],
                ':tipo'                 => $dados['tipo'],
                ':categoria'            => $dados['categoria'],
                ':modo_calculo'         => $dados['modo_calculo'],
                ':base_calculo'         => $dados['base_calculo'],
                ':valor_padrao'         => $dados['valor_padrao'],
                ':percentagem_padrao'   => $dados['percentagem_padrao'],
                ':formula_calculo'      => $dados['formula_calculo'],
                ':tributavel_inss'      => $dados['tributavel_inss'],
                ':tributavel_irps'      => $dados['tributavel_irps'],
                ':obrigatoria'          => $dados['obrigatoria'],
                ':recorrente'           => $dados['recorrente'],
                ':permite_edicao'       => $dados['permite_edicao'],
                ':ordem'                => $dados['ordem'],
                ':status_id'            => $statusId,
                ':criado_por'           => $usuarioId,
                ':atualizado_por'       => $usuarioId,
            ]);

            $id = (int) $this->db->lastInsertId();

            $this->db->commit();

            $rubrica = $this->buscarPorId($id, $empresaId);

            if ($rubrica === null) {
                throw new RuntimeException(
                    'A rubrica foi criada, mas não pôde ser consultada.'
                );
            }

            return $rubrica;
        } catch (Throwable $erro) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if (
                $erro instanceof PDOException
                && (string) $erro->getCode() === '23000'
            ) {
                throw new RuntimeException(
                    'Já existe uma rubrica com este código.'
                );
            }

            throw $erro;
        }
    }

    /**
     * Atualiza uma rubrica existente.
     */
    public function atualizar(
        int $id,
        array $dados,
        int $empresaId,
        ?int $usuarioId
    ): array {
        $rubricaAtual = $this->buscarPorId($id, $empresaId);

        if ($rubricaAtual === null) {
            throw new RuntimeException(
                'Rubrica salarial não encontrada.'
            );
        }

        $dados = $this->normalizarDados($dados);

        $this->validarDados($dados, $empresaId, $id);

        if ($this->codigoExiste(
            $dados['codigo'],
            $empresaId,
            $id
        )) {
            throw new RuntimeException(
                'Já existe outra rubrica com este código.'
            );
        }

        $statusId = !empty($dados['status_id'])
            ? (int) $dados['status_id']
            : (int) $rubricaAtual['status_id'];

        $this->validarStatus($statusId);

        $sql = "
            UPDATE rubricas_salariais
            SET
                codigo = :codigo,
                nome = :nome,
                descricao = :descricao,
                tipo = :tipo,
                categoria = :categoria,
                modo_calculo = :modo_calculo,
                base_calculo = :base_calculo,
                valor_padrao = :valor_padrao,
                percentagem_padrao = :percentagem_padrao,
                formula_calculo = :formula_calculo,
                tributavel_inss = :tributavel_inss,
                tributavel_irps = :tributavel_irps,
                obrigatoria = :obrigatoria,
                recorrente = :recorrente,
                permite_edicao = :permite_edicao,
                ordem = :ordem,
                status_id = :status_id,
                atualizado_por = :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':codigo'               => $dados['codigo'],
                ':nome'                 => $dados['nome'],
                ':descricao'            => $dados['descricao'],
                ':tipo'                 => $dados['tipo'],
                ':categoria'            => $dados['categoria'],
                ':modo_calculo'         => $dados['modo_calculo'],
                ':base_calculo'         => $dados['base_calculo'],
                ':valor_padrao'         => $dados['valor_padrao'],
                ':percentagem_padrao'   => $dados['percentagem_padrao'],
                ':formula_calculo'      => $dados['formula_calculo'],
                ':tributavel_inss'      => $dados['tributavel_inss'],
                ':tributavel_irps'      => $dados['tributavel_irps'],
                ':obrigatoria'          => $dados['obrigatoria'],
                ':recorrente'           => $dados['recorrente'],
                ':permite_edicao'       => $dados['permite_edicao'],
                ':ordem'                => $dados['ordem'],
                ':status_id'            => $statusId,
                ':atualizado_por'       => $usuarioId,
                ':id'                   => $id,
                ':empresa_id'           => $empresaId,
            ]);

            $this->db->commit();

            $rubrica = $this->buscarPorId($id, $empresaId);

            if ($rubrica === null) {
                throw new RuntimeException(
                    'A rubrica foi atualizada, mas não pôde ser consultada.'
                );
            }

            return $rubrica;
        } catch (Throwable $erro) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if (
                $erro instanceof PDOException
                && (string) $erro->getCode() === '23000'
            ) {
                throw new RuntimeException(
                    'Já existe uma rubrica com este código.'
                );
            }

            throw $erro;
        }
    }

    /**
     * Compatibilidade com escrita "actualizar".
     */
    public function actualizar(
        int $id,
        array $dados,
        int $empresaId,
        ?int $usuarioId
    ): array {
        return $this->atualizar(
            $id,
            $dados,
            $empresaId,
            $usuarioId
        );
    }

    /**
     * Ativa uma rubrica.
     */
    public function ativar(
        int $id,
        int $empresaId,
        ?int $usuarioId
    ): array {
        return $this->alterarEstado(
            $id,
            $empresaId,
            'ACTIVA',
            $usuarioId
        );
    }

    /**
     * Compatibilidade com escrita "activar".
     */
    public function activar(
        int $id,
        int $empresaId,
        ?int $usuarioId
    ): array {
        return $this->ativar(
            $id,
            $empresaId,
            $usuarioId
        );
    }

    /**
     * Inativa uma rubrica.
     */
    public function inativar(
        int $id,
        int $empresaId,
        ?int $usuarioId
    ): array {
        return $this->alterarEstado(
            $id,
            $empresaId,
            'Inactivo',
            $usuarioId
        );
    }

    /**
     * Compatibilidade com escrita "inactivar".
     */
    public function inactivar(
        int $id,
        int $empresaId,
        ?int $usuarioId
    ): array {
        return $this->inativar(
            $id,
            $empresaId,
            $usuarioId
        );
    }

    /**
     * Retorna os estados disponíveis para rubricas salariais.
     */
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

            ORDER BY
                CASE UPPER(nome)
                    WHEN 'ACTIVA' THEN 1
                    WHEN 'ACTIVO' THEN 1
                    WHEN 'INACTIVA' THEN 2
                    WHEN 'INACTIVO' THEN 2
                    ELSE 3
                END,
                nome ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':tipo' => self::TIPO_STATUS,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumo para os cartões do dashboard.
     */
    public function resumo(int $empresaId): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN rs.tipo = 'PROVENTO'
                        THEN 1
                        ELSE 0
                    END
                ) AS proventos,

                SUM(
                    CASE
                        WHEN rs.tipo = 'DESCONTO'
                        THEN 1
                        ELSE 0
                    END
                ) AS descontos,

                SUM(
                    CASE
                        WHEN rs.tipo = 'INFORMATIVO'
                        THEN 1
                        ELSE 0
                    END
                ) AS informativos,

                SUM(
                    CASE
                        WHEN UPPER(s.nome) IN ('ACTIVA', 'ACTIVO')
                        THEN 1
                        ELSE 0
                    END
                ) AS ativas,

                SUM(
                    CASE
                        WHEN UPPER(s.nome) IN ('INACTIVA', 'INACTIVO')
                        THEN 1
                        ELSE 0
                    END
                ) AS inativas

            FROM rubricas_salariais rs

            INNER JOIN status s
                ON s.id = rs.status_id

            WHERE rs.empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
        ]);

        $resumo = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total'       => (int) ($resumo['total'] ?? 0),
            'proventos'   => (int) ($resumo['proventos'] ?? 0),
            'descontos'   => (int) ($resumo['descontos'] ?? 0),
            'informativos' => (int) ($resumo['informativos'] ?? 0),
            'ativas'      => (int) ($resumo['ativas'] ?? 0),
            'inativas'    => (int) ($resumo['inativas'] ?? 0),
        ];
    }

    /**
     * Verifica se existe um código dentro da empresa.
     */
    public function codigoExiste(
        string $codigo,
        int $empresaId,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT id
            FROM rubricas_salariais
            WHERE empresa_id = :empresa_id
              AND UPPER(codigo) = UPPER(:codigo)
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':codigo'     => trim($codigo),
        ];

        if ($ignorarId !== null) {
            $sql .= " AND id <> :ignorar_id";
            $parametros[':ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Calcula uma rubrica com base na sua configuração.
     *
     * Para FORMULA, o cálculo completo será tratado posteriormente
     * pelo motor seguro de fórmulas do processamento salarial.
     */
    public function calcularValorRubrica(
        array $rubrica,
        array $contexto = []
    ): float {
        $modoCalculo = strtoupper(
            trim((string) ($rubrica['modo_calculo'] ?? ''))
        );

        $valorPadrao = $this->converterDecimal(
            $rubrica['valor_padrao'] ?? 0
        );

        $percentagem = $this->converterDecimal(
            $rubrica['percentagem_padrao'] ?? 0
        );

        return match ($modoCalculo) {
            'VALOR_FIXO' => round($valorPadrao, 2),

            'PERCENTAGEM' => round(
                $this->obterValorBase(
                    (string) ($rubrica['base_calculo'] ?? 'NENHUMA'),
                    $contexto
                ) * ($percentagem / 100),
                2
            ),

            'QUANTIDADE_VALOR' => round(
                $valorPadrao
                    * $this->converterDecimal(
                        $contexto['quantidade'] ?? 0
                    ),
                2
            ),

            'MANUAL' => round(
                $this->converterDecimal(
                    $contexto['valor_manual'] ?? 0
                ),
                2
            ),

            'FORMULA' => throw new RuntimeException(
                'O cálculo por fórmula será executado pelo motor de fórmulas do processamento salarial.'
            ),

            default => throw new RuntimeException(
                'Modo de cálculo da rubrica inválido.'
            ),
        };
    }

    private function alterarEstado(
        int $id,
        int $empresaId,
        string $estado,
        ?int $usuarioId
    ): array {
        $rubrica = $this->buscarPorId($id, $empresaId);

        if ($rubrica === null) {
            throw new RuntimeException(
                'Rubrica salarial não encontrada.'
            );
        }

        $statusId = $this->obterStatusId($estado);

        $sql = "
            UPDATE rubricas_salariais
            SET
                status_id = :status_id,
                atualizado_por = :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status_id'      => $statusId,
            ':atualizado_por' => $usuarioId,
            ':id'             => $id,
            ':empresa_id'     => $empresaId,
        ]);

        $rubricaAtualizada = $this->buscarPorId(
            $id,
            $empresaId
        );

        if ($rubricaAtualizada === null) {
            throw new RuntimeException(
                'Não foi possível consultar a rubrica atualizada.'
            );
        }

        return $rubricaAtualizada;
    }

    /**
     * Valida todos os dados da rubrica.
     */
    private function validarDados(
        array $dados,
        int $empresaId,
        ?int $ignorarId = null
    ): void {
        if ($empresaId <= 0) {
            throw new InvalidArgumentException(
                'Empresa inválida.'
            );
        }

        if ($dados['codigo'] === '') {
            throw new InvalidArgumentException(
                'O código da rubrica é obrigatório.'
            );
        }

        if (mb_strlen($dados['codigo']) > 30) {
            throw new InvalidArgumentException(
                'O código deve possuir no máximo 30 caracteres.'
            );
        }

        if (!preg_match(
            '/^[A-Z0-9._-]+$/',
            $dados['codigo']
        )) {
            throw new InvalidArgumentException(
                'O código pode conter apenas letras, números, ponto, hífen e sublinhado.'
            );
        }

        if ($dados['nome'] === '') {
            throw new InvalidArgumentException(
                'O nome da rubrica é obrigatório.'
            );
        }

        if (mb_strlen($dados['nome']) > 120) {
            throw new InvalidArgumentException(
                'O nome deve possuir no máximo 120 caracteres.'
            );
        }

        if (
            $dados['descricao'] !== null
            && mb_strlen($dados['descricao']) > 500
        ) {
            throw new InvalidArgumentException(
                'A descrição deve possuir no máximo 500 caracteres.'
            );
        }

        if (!in_array(
            $dados['tipo'],
            self::TIPOS,
            true
        )) {
            throw new InvalidArgumentException(
                'O tipo da rubrica é inválido.'
            );
        }

        if (!in_array(
            $dados['categoria'],
            self::CATEGORIAS,
            true
        )) {
            throw new InvalidArgumentException(
                'A categoria da rubrica é inválida.'
            );
        }

        if (!in_array(
            $dados['modo_calculo'],
            self::MODOS_CALCULO,
            true
        )) {
            throw new InvalidArgumentException(
                'O modo de cálculo é inválido.'
            );
        }

        if (!in_array(
            $dados['base_calculo'],
            self::BASES_CALCULO,
            true
        )) {
            throw new InvalidArgumentException(
                'A base de cálculo é inválida.'
            );
        }

        if ($dados['valor_padrao'] < 0) {
            throw new InvalidArgumentException(
                'O valor padrão não pode ser negativo.'
            );
        }

        if ($dados['percentagem_padrao'] < 0) {
            throw new InvalidArgumentException(
                'A percentagem não pode ser negativa.'
            );
        }

        if ($dados['ordem'] < 0 || $dados['ordem'] > 65535) {
            throw new InvalidArgumentException(
                'A ordem deve estar entre 0 e 65535.'
            );
        }

        $this->validarConfiguracaoCalculo($dados);

        $this->validarRegraCategoria($dados);

        if (
            !empty($dados['status_id'])
            && (int) $dados['status_id'] > 0
        ) {
            $this->validarStatus(
                (int) $dados['status_id']
            );
        }

        if ($this->codigoExiste(
            $dados['codigo'],
            $empresaId,
            $ignorarId
        )) {
            throw new RuntimeException(
                'Já existe uma rubrica com este código.'
            );
        }
    }

    private function validarConfiguracaoCalculo(
        array $dados
    ): void {
        switch ($dados['modo_calculo']) {
            case 'VALOR_FIXO':
                if ($dados['valor_padrao'] < 0) {
                    throw new InvalidArgumentException(
                        'Informe um valor padrão válido.'
                    );
                }
                break;

            case 'PERCENTAGEM':
                if ($dados['base_calculo'] === 'NENHUMA') {
                    throw new InvalidArgumentException(
                        'Seleccione uma base para o cálculo percentual.'
                    );
                }

                if ($dados['percentagem_padrao'] <= 0) {
                    throw new InvalidArgumentException(
                        'A percentagem deve ser maior que zero.'
                    );
                }
                break;

            case 'QUANTIDADE_VALOR':
                if ($dados['valor_padrao'] <= 0) {
                    throw new InvalidArgumentException(
                        'Informe o valor unitário da rubrica.'
                    );
                }
                break;

            case 'FORMULA':
                if (
                    $dados['formula_calculo'] === null
                    || $dados['formula_calculo'] === ''
                ) {
                    throw new InvalidArgumentException(
                        'Informe a fórmula de cálculo.'
                    );
                }

                $this->validarFormula(
                    $dados['formula_calculo']
                );
                break;

            case 'MANUAL':
                break;
        }
    }

    /**
     * Valida regras básicas entre categoria e tipo.
     */
    private function validarRegraCategoria(
        array $dados
    ): void {
        $categoriasDesconto = [
            'FALTA',
            'ATRASO',
            'INSS',
            'IRPS',
            'ADIANTAMENTO',
            'EMPRESTIMO',
            'PENSAO',
        ];

        $categoriasProvento = [
            'SALARIO_BASE',
            'SUBSIDIO_ALIMENTACAO',
            'SUBSIDIO_TRANSPORTE',
            'SUBSIDIO_COMUNICACAO',
            'SUBSIDIO_TURNO',
            'SUBSIDIO_RISCO',
            'SUBSIDIO_OUTRO',
            'HORA_EXTRA',
            'BONUS',
            'PREMIO',
            'COMISSAO',
        ];

        if (
            in_array(
                $dados['categoria'],
                $categoriasDesconto,
                true
            )
            && $dados['tipo'] !== 'DESCONTO'
        ) {
            throw new InvalidArgumentException(
                'A categoria seleccionada deve ser do tipo DESCONTO.'
            );
        }

        if (
            in_array(
                $dados['categoria'],
                $categoriasProvento,
                true
            )
            && $dados['tipo'] !== 'PROVENTO'
        ) {
            throw new InvalidArgumentException(
                'A categoria seleccionada deve ser do tipo PROVENTO.'
            );
        }

        if (
            $dados['categoria'] === 'SALARIO_BASE'
            && $dados['modo_calculo'] !== 'VALOR_FIXO'
        ) {
            throw new InvalidArgumentException(
                'O salário base deve utilizar o modo VALOR_FIXO.'
            );
        }

        if (
            $dados['categoria'] === 'INSS'
            && !in_array(
                $dados['modo_calculo'],
                ['PERCENTAGEM', 'FORMULA'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'A rubrica de INSS deve utilizar PERCENTAGEM ou FORMULA.'
            );
        }

        if (
            $dados['categoria'] === 'IRPS'
            && !in_array(
                $dados['modo_calculo'],
                ['FORMULA', 'MANUAL'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'A rubrica de IRPS deve utilizar FORMULA ou MANUAL.'
            );
        }
    }

    /**
     * Normaliza dados enviados pelo Controller/AJAX.
     */
    private function normalizarDados(array $dados): array
    {
        $modoCalculo = strtoupper(
            trim((string) ($dados['modo_calculo'] ?? 'VALOR_FIXO'))
        );

        $baseCalculo = strtoupper(
            trim((string) ($dados['base_calculo'] ?? 'NENHUMA'))
        );

        $formula = trim(
            (string) ($dados['formula_calculo'] ?? '')
        );

        if ($modoCalculo !== 'PERCENTAGEM') {
            $percentagem = 0.0;
        } else {
            $percentagem = $this->converterDecimal(
                $dados['percentagem_padrao'] ?? 0
            );
        }

        if (
            !in_array(
                $modoCalculo,
                ['VALOR_FIXO', 'QUANTIDADE_VALOR'],
                true
            )
        ) {
            $valorPadrao = 0.0;
        } else {
            $valorPadrao = $this->converterDecimal(
                $dados['valor_padrao'] ?? 0
            );
        }

        if ($modoCalculo !== 'FORMULA') {
            $formula = '';
        }

        if ($modoCalculo !== 'PERCENTAGEM') {
            $baseCalculo = 'NENHUMA';
        }

        return [
            'codigo' => strtoupper(
                trim((string) ($dados['codigo'] ?? ''))
            ),

            'nome' => trim(
                (string) ($dados['nome'] ?? '')
            ),

            'descricao' => $this->textoOuNull(
                $dados['descricao'] ?? null
            ),

            'tipo' => strtoupper(
                trim((string) ($dados['tipo'] ?? ''))
            ),

            'categoria' => strtoupper(
                trim((string) ($dados['categoria'] ?? 'OUTRO'))
            ),

            'modo_calculo' => $modoCalculo,

            'base_calculo' => $baseCalculo,

            'valor_padrao' => $valorPadrao,

            'percentagem_padrao' => $percentagem,

            'formula_calculo' => $formula !== ''
                ? $formula
                : null,

            'tributavel_inss' => $this->converterBooleano(
                $dados['tributavel_inss'] ?? 0
            ),

            'tributavel_irps' => $this->converterBooleano(
                $dados['tributavel_irps'] ?? 0
            ),

            'obrigatoria' => $this->converterBooleano(
                $dados['obrigatoria'] ?? 0
            ),

            'recorrente' => $this->converterBooleano(
                $dados['recorrente'] ?? 0
            ),

            'permite_edicao' => $this->converterBooleano(
                $dados['permite_edicao'] ?? 0
            ),

            'ordem' => max(
                0,
                (int) ($dados['ordem'] ?? 0)
            ),

            'status_id' => !empty($dados['status_id'])
                ? (int) $dados['status_id']
                : null,
        ];
    }

    /**
     * Retorna o ID de um estado sem utilizar IDs fixos.
     */
    private function obterStatusId(string $nome): int
    {
        $nomesPossiveis = match (strtoupper(trim($nome))) {
            'Activo', 'Activa' => ['ACTIVA', 'ACTIVO'],
            'Inactivo', 'Inactiva' => ['INACTIVA', 'INACTIVO'],
            default => [strtoupper(trim($nome))],
        };

        $placeholders = [];

        $parametros = [
            ':tipo' => self::TIPO_STATUS,
        ];

        foreach ($nomesPossiveis as $indice => $statusNome) {
            $placeholder = ':nome_' . $indice;
            $placeholders[] = $placeholder;
            $parametros[$placeholder] = $statusNome;
        }

        $sql = "
            SELECT id
            FROM status
            WHERE tipo = :tipo
              AND UPPER(nome) IN (
                  " . implode(', ', $placeholders) . "
              )
            ORDER BY
                CASE
                    WHEN UPPER(nome) = :nome_preferencial
                    THEN 0
                    ELSE 1
                END
            LIMIT 1
        ";

        $parametros[':nome_preferencial'] = strtoupper(
            trim($nome)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        $statusId = $stmt->fetchColumn();

        if ($statusId === false) {
            throw new RuntimeException(
                "O estado {$nome} não está configurado para rubricas salariais."
            );
        }

        return (int) $statusId;
    }

    private function validarStatus(int $statusId): void
    {
        if ($statusId <= 0) {
            throw new InvalidArgumentException(
                'Estado inválido.'
            );
        }

        $sql = "
            SELECT id
            FROM status
            WHERE id = :id
              AND tipo = :tipo
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id'   => $statusId,
            ':tipo' => self::TIPO_STATUS,
        ]);

        if (!$stmt->fetchColumn()) {
            throw new InvalidArgumentException(
                'O estado selecionado não pertence às rubricas salariais.'
            );
        }
    }

    /**
     * Validação básica. Não utiliza eval().
     */
    private function validarFormula(string $formula): void
    {
        $formula = trim($formula);

        if ($formula === '') {
            throw new InvalidArgumentException(
                'A fórmula de cálculo é obrigatória.'
            );
        }

        if (mb_strlen($formula) > 500) {
            throw new InvalidArgumentException(
                'A fórmula deve possuir no máximo 500 caracteres.'
            );
        }

        if (preg_match(
            '/[;{}$`\'"\\\\]/',
            $formula
        )) {
            throw new InvalidArgumentException(
                'A fórmula contém caracteres não permitidos.'
            );
        }

        if (!preg_match(
            '/^[A-Z0-9_+\-*\/().%\s]+$/i',
            $formula
        )) {
            throw new InvalidArgumentException(
                'A fórmula contém elementos não autorizados.'
            );
        }

        $parenteses = 0;

        foreach (str_split($formula) as $caractere) {
            if ($caractere === '(') {
                $parenteses++;
            }

            if ($caractere === ')') {
                $parenteses--;

                if ($parenteses < 0) {
                    throw new InvalidArgumentException(
                        'A fórmula possui parênteses inválidos.'
                    );
                }
            }
        }

        if ($parenteses !== 0) {
            throw new InvalidArgumentException(
                'A fórmula possui parênteses não fechados.'
            );
        }
    }

    private function obterValorBase(
        string $baseCalculo,
        array $contexto
    ): float {
        $chave = match (strtoupper(trim($baseCalculo))) {
            'SALARIO_BASE'    => 'salario_base',
            'TOTAL_PROVENTOS' => 'total_proventos',
            'SALARIO_BRUTO'   => 'salario_bruto',
            'VALOR_HORA'      => 'valor_hora',
            'VALOR_DIA'       => 'valor_dia',
            default           => null,
        };

        if ($chave === null) {
            return 0.0;
        }

        return $this->converterDecimal(
            $contexto[$chave] ?? 0
        );
    }

    private function converterBooleano(mixed $valor): int
    {
        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }

        if (is_int($valor) || is_float($valor)) {
            return ((int) $valor) === 1 ? 1 : 0;
        }

        $valorNormalizado = strtolower(
            trim((string) $valor)
        );

        return in_array(
            $valorNormalizado,
            ['1', 'true', 'sim', 'on', 'yes'],
            true
        ) ? 1 : 0;
    }

    private function converterDecimal(mixed $valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        if (is_string($valor)) {
            $valor = trim($valor);

            if (
                str_contains($valor, ',')
                && str_contains($valor, '.')
            ) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            } elseif (str_contains($valor, ',')) {
                $valor = str_replace(',', '.', $valor);
            }
        }

        if (!is_numeric($valor)) {
            throw new InvalidArgumentException(
                'Foi informado um valor numérico inválido.'
            );
        }

        return round((float) $valor, 4);
    }

    private function textoOuNull(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function gerarUuid(): string
    {
        $dados = random_bytes(16);

        $dados[6] = chr(
            (ord($dados[6]) & 0x0f) | 0x40
        );

        $dados[8] = chr(
            (ord($dados[8]) & 0x3f) | 0x80
        );

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($dados), 4)
        );
    }
}
