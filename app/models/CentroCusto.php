<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/AuditoriaService.php';
require_once __DIR__ . '/../services/LogService.php';

final class CentroCusto
{
    private PDO $pdo;
    private AuditoriaService $auditoria;
    private LogService $log;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->auditoria =
            new AuditoriaService($pdo);

        $this->log =
            new LogService($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $sql = "
            SELECT
                cc.id,
                cc.empresa_id,
                cc.filial_id,
                cc.centro_pai_id,
                cc.codigo,
                cc.nome,
                cc.descricao,
                cc.tipo,
                cc.responsavel,
                cc.orcamento_mensal,
                cc.status_id,
                cc.criado_em,
                cc.atualizado_em,

                COALESCE(
                    f.nome,
                    'Todas / Sede Principal'
                ) AS filial,

                COALESCE(
                    pai.nome,
                    'Centro Principal'
                ) AS centro_pai,

                s.nome AS status,

                (
                    SELECT COUNT(*)
                    FROM centros_custo filho
                    WHERE filho.centro_pai_id = cc.id
                ) AS total_subcentros

            FROM centros_custo cc

            LEFT JOIN filiais f
                ON f.id = cc.filial_id
                AND f.empresa_id = cc.empresa_id

            LEFT JOIN centros_custo pai
                ON pai.id = cc.centro_pai_id
                AND pai.empresa_id = cc.empresa_id

            INNER JOIN status s
                ON s.id = cc.status_id
                AND s.tipo = 'centro_custo'

            WHERE cc.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if (!empty($filtros['filial_id'])) {
            $sql .= "
                AND cc.filial_id = :filial_id
            ";

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }

        if (!empty($filtros['status_id'])) {
            $sql .= "
                AND cc.status_id = :status_id
            ";

            $params[':status_id'] =
                (int)$filtros['status_id'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= "
                AND cc.tipo = :tipo
            ";

            $params[':tipo'] =
                strtoupper(
                    trim(
                        (string)$filtros['tipo']
                    )
                );
        }

        if (!empty($filtros['pesquisa'])) {
            $sql .= "
                AND (
                    cc.codigo LIKE :pesquisa
                    OR cc.nome LIKE :pesquisa
                    OR cc.responsavel LIKE :pesquisa
                    OR cc.descricao LIKE :pesquisa
                )
            ";

            $params[':pesquisa'] =
                '%' . trim(
                    (string)$filtros['pesquisa']
                ) . '%';
        }

        $sql .= "
            ORDER BY
                COALESCE(
                    cc.centro_pai_id,
                    cc.id
                ) ASC,
                cc.centro_pai_id ASC,
                cc.codigo ASC,
                cc.nome ASC
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
    // BUSCAR POR ID
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
                'Centro de custo inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                cc.*,

                COALESCE(
                    f.nome,
                    'Todas / Sede Principal'
                ) AS filial,

                COALESCE(
                    pai.nome,
                    'Centro Principal'
                ) AS centro_pai,

                s.nome AS status

            FROM centros_custo cc

            LEFT JOIN filiais f
                ON f.id = cc.filial_id
                AND f.empresa_id = cc.empresa_id

            LEFT JOIN centros_custo pai
                ON pai.id = cc.centro_pai_id
                AND pai.empresa_id = cc.empresa_id

            INNER JOIN status s
                ON s.id = cc.status_id
                AND s.tipo = 'centro_custo'

            WHERE cc.id = :id
              AND cc.empresa_id = :empresa_id

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

    public function criar(
        array $dados
    ): int {

        $dados =
            $this->normalizarDados(
                $dados
            );

        $this->validarDados(
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


        $this->validarCodigoDuplicado(
            $empresaId,
            $dados['codigo']
        );

        $this->validarFilial(
            $empresaId,
            $dados['filial_id']
        );

        $this->validarCentroPai(
            $empresaId,
            $dados['centro_pai_id'],
            null
        );

        $this->validarStatus(
            $dados['status_id']
        );


        $this->pdo->beginTransaction();

        try {

            $stmt = $this->pdo->prepare("
            INSERT INTO centros_custo
            (
                empresa_id,
                filial_id,
                centro_pai_id,
                codigo,
                nome,
                descricao,
                tipo,
                responsavel,
                orcamento_mensal,
                status_id,
                criado_por
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :centro_pai_id,
                :codigo,
                :nome,
                :descricao,
                :tipo,
                :responsavel,
                :orcamento_mensal,
                :status_id,
                :criado_por
            )
        ");

            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $dados['filial_id'],

                ':centro_pai_id' =>
                $dados['centro_pai_id'],

                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':descricao' =>
                $dados['descricao'],

                ':tipo' =>
                $dados['tipo'],

                ':responsavel' =>
                $dados['responsavel'],

                ':orcamento_mensal' =>
                $dados['orcamento_mensal'],

                ':status_id' =>
                $dados['status_id'],

                ':criado_por' =>
                $usuarioId
            ]);


            $id =
                (int)$this->pdo
                    ->lastInsertId();


            if ($id <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o centro de custo criado.'
                );
            }


            $centroDepois =
                $this->buscarCentroAuditoria(
                    $id,
                    $empresaId
                );


            if (!$centroDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o centro de custo criado.'
                );
            }


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'centros_custo',
                $id,
                $this->dadosAuditoriaCentroCusto(
                    $centroDepois
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CENTROS_CUSTO',
                'CRIAR',
                'Centro de custo criado: '
                    . $centroDepois['codigo']
                    . ' - '
                    . $centroDepois['nome'],
                $id,
                !empty($centroDepois['filial_id'])
                    ? (int)$centroDepois['filial_id']
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

    public function atualizar(
        array $dados
    ): bool {

        $dados =
            $this->normalizarDados(
                $dados
            );

        $id =
            (int)($dados['id'] ?? 0);

        $empresaId =
            (int)$dados['empresa_id'];

        $usuarioId =
            (int)($dados['usuario_id'] ?? 0);


        if ($id <= 0) {
            throw new RuntimeException(
                'Centro de custo inválido.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        $this->validarDados(
            $dados
        );


        $this->pdo->beginTransaction();

        try {

            /*
         * Bloquear e guardar estado anterior.
         */
            $stmt = $this->pdo->prepare("
            SELECT *
            FROM centros_custo
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

            $centroAntes =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$centroAntes) {
                throw new RuntimeException(
                    'Centro de custo não encontrado.'
                );
            }


            $this->validarCodigoDuplicado(
                $empresaId,
                $dados['codigo'],
                $id
            );

            $this->validarFilial(
                $empresaId,
                $dados['filial_id']
            );

            $this->validarCentroPai(
                $empresaId,
                $dados['centro_pai_id'],
                $id
            );

            $this->validarStatus(
                $dados['status_id']
            );


            $stmt = $this->pdo->prepare("
            UPDATE centros_custo
            SET
                filial_id = :filial_id,
                centro_pai_id = :centro_pai_id,
                codigo = :codigo,
                nome = :nome,
                descricao = :descricao,
                tipo = :tipo,
                responsavel = :responsavel,
                orcamento_mensal = :orcamento_mensal,
                status_id = :status_id,
                atualizado_por = :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id
        ");

            $stmt->execute([
                ':filial_id' =>
                $dados['filial_id'],

                ':centro_pai_id' =>
                $dados['centro_pai_id'],

                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':descricao' =>
                $dados['descricao'],

                ':tipo' =>
                $dados['tipo'],

                ':responsavel' =>
                $dados['responsavel'],

                ':orcamento_mensal' =>
                $dados['orcamento_mensal'],

                ':status_id' =>
                $dados['status_id'],

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            $centroDepois =
                $this->buscarCentroAuditoria(
                    $id,
                    $empresaId
                );


            if (!$centroDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o centro de custo atualizado.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'centros_custo',
                $id,
                $this->dadosAuditoriaCentroCusto(
                    $centroAntes
                ),
                $this->dadosAuditoriaCentroCusto(
                    $centroDepois
                )
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CENTROS_CUSTO',
                'EDITAR',
                'Centro de custo atualizado: '
                    . $centroDepois['codigo']
                    . ' - '
                    . $centroDepois['nome'],
                $id,
                !empty($centroDepois['filial_id'])
                    ? (int)$centroDepois['filial_id']
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


        if ($id <= 0) {
            throw new RuntimeException(
                'Centro de custo inválido.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        $this->pdo->beginTransaction();

        try {

            /*
         * Bloquear o centro.
         */
            $stmt = $this->pdo->prepare("
            SELECT *
            FROM centros_custo
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


            $centro =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$centro) {
                throw new RuntimeException(
                    'Centro de custo não encontrado.'
                );
            }


            /*
         * Preservar as regras existentes.
         */
            $this->validarSemSubcentros(
                $id,
                $empresaId
            );

            $this->validarSemMovimentos(
                $id,
                $empresaId
            );


            $dadosAnteriores =
                $this->dadosAuditoriaCentroCusto(
                    $centro
                );


            $stmt = $this->pdo->prepare("
            DELETE FROM centros_custo
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
                    'Não foi possível eliminar o centro de custo.'
                );
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'centros_custo',
                $id,
                $dadosAnteriores
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CENTROS_CUSTO',
                'ELIMINAR',
                'Centro de custo eliminado: '
                    . $centro['codigo']
                    . ' - '
                    . $centro['nome'],
                $id,
                !empty($centro['filial_id'])
                    ? (int)$centro['filial_id']
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
    // RESUMO PARA OS CARDS
    // ==================================================

    public function resumo(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $sql = "
            SELECT
                COUNT(cc.id) AS total_centros,

                SUM(
                    CASE
                        WHEN LOWER(s.nome)
                            IN ('activo', 'ativo')
                        THEN 1
                        ELSE 0
                    END
                ) AS total_activos,

                COALESCE(
                    SUM(cc.orcamento_mensal),
                    0
                ) AS orcamento_total,

                SUM(
                    CASE
                        WHEN cc.centro_pai_id IS NOT NULL
                        THEN 1
                        ELSE 0
                    END
                ) AS total_subcentros

            FROM centros_custo cc

            INNER JOIN status s
                ON s.id = cc.status_id
                AND s.tipo = 'centro_custo'

            WHERE cc.empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if (!empty($filtros['filial_id'])) {
            $sql .= "
                AND cc.filial_id = :filial_id
            ";

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }

        if (!empty($filtros['status_id'])) {
            $sql .= "
                AND cc.status_id = :status_id
            ";

            $params[':status_id'] =
                (int)$filtros['status_id'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= "
                AND cc.tipo = :tipo
            ";

            $params[':tipo'] =
                strtoupper(
                    trim(
                        (string)$filtros['tipo']
                    )
                );
        }

        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        $resultado =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];

        return [
            'total_centros' =>
            (int)(
                $resultado['total_centros']
                ?? 0
            ),

            'total_activos' =>
            (int)(
                $resultado['total_activos']
                ?? 0
            ),

            'orcamento_total' =>
            (float)(
                $resultado['orcamento_total']
                ?? 0
            ),

            'total_subcentros' =>
            (int)(
                $resultado['total_subcentros']
                ?? 0
            )
        ];
    }

    // ==================================================
    // LISTAR CENTROS PARA SELECT
    // ==================================================

    public function listarParaSelect(
        int $empresaId,
        ?int $excluirId = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $sql = "
            SELECT
                cc.id,
                cc.codigo,
                cc.nome,
                cc.centro_pai_id,

                CASE
                    WHEN cc.centro_pai_id IS NULL
                    THEN CONCAT(
                        cc.codigo,
                        ' - ',
                        cc.nome
                    )
                    ELSE CONCAT(
                        '— ',
                        cc.codigo,
                        ' - ',
                        cc.nome
                    )
                END AS texto

            FROM centros_custo cc

            INNER JOIN status s
                ON s.id = cc.status_id
                AND s.tipo = 'centro_custo'

            WHERE cc.empresa_id = :empresa_id

              AND LOWER(s.nome)
                  IN ('activo', 'ativo')
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if (
            $excluirId !== null
            && $excluirId > 0
        ) {
            $sql .= "
                AND cc.id <> :excluir_id
            ";

            $params[':excluir_id'] =
                $excluirId;
        }

        $sql .= "
            ORDER BY
                cc.centro_pai_id IS NOT NULL,
                cc.codigo ASC,
                cc.nome ASC
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
    // LISTAR STATUS
    // ==================================================

    public function listarStatus(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo = 'centro_custo'

            ORDER BY
                CASE
                    WHEN LOWER(nome)
                        IN ('activo', 'ativo')
                    THEN 1

                    WHEN LOWER(nome)
                        IN ('inactivo', 'inativo')
                    THEN 2

                    WHEN LOWER(nome) = 'encerrado'
                    THEN 3

                    ELSE 4
                END,
                nome ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // NORMALIZAR DADOS
    // ==================================================

    private function normalizarDados(
        array $dados
    ): array {

        $tipo = strtoupper(
            trim(
                (string)(
                    $dados['tipo'] ?? 'CUSTO'
                )
            )
        );

        return [
            'id' =>
            !empty($dados['id'])
                ? (int)$dados['id']
                : null,

            'empresa_id' =>
            (int)(
                $dados['empresa_id'] ?? 0
            ),

            'filial_id' =>
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : null,

            'centro_pai_id' =>
            !empty($dados['centro_pai_id'])
                ? (int)$dados['centro_pai_id']
                : null,

            'codigo' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['codigo'] ?? ''
                    )
                )
            ),

            'nome' =>
            trim(
                (string)(
                    $dados['nome'] ?? ''
                )
            ),

            'descricao' =>
            $this->textoOuNull(
                $dados['descricao'] ?? null
            ),

            'tipo' =>
            $tipo,

            'responsavel' =>
            $this->textoOuNull(
                $dados['responsavel'] ?? null
            ),

            'orcamento_mensal' =>
            isset($dados['orcamento_mensal'])
                && $dados['orcamento_mensal'] !== ''
                ? (float)$dados['orcamento_mensal']
                : 0.0,

            'status_id' =>
            (int)(
                $dados['status_id'] ?? 0
            ),

            'usuario_id' =>
            !empty($dados['usuario_id'])
                ? (int)$dados['usuario_id']
                : null
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

        if ($dados['codigo'] === '') {
            throw new RuntimeException(
                'Informe o código do centro de custo.'
            );
        }

        if (
            mb_strlen(
                $dados['codigo']
            ) > 30
        ) {
            throw new RuntimeException(
                'O código não pode ter mais de 30 caracteres.'
            );
        }

        if ($dados['nome'] === '') {
            throw new RuntimeException(
                'Informe o nome do centro de custo.'
            );
        }

        if (
            mb_strlen(
                $dados['nome']
            ) > 150
        ) {
            throw new RuntimeException(
                'O nome não pode ter mais de 150 caracteres.'
            );
        }

        if (
            !in_array(
                $dados['tipo'],
                [
                    'CUSTO',
                    'RECEITA',
                    'MISTO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Tipo de centro de custo inválido.'
            );
        }

        if ($dados['status_id'] <= 0) {
            throw new RuntimeException(
                'Selecione o status do centro de custo.'
            );
        }

        if (
            $dados['orcamento_mensal']
            < 0
        ) {
            throw new RuntimeException(
                'O orçamento mensal não pode ser negativo.'
            );
        }

        if (
            $dados['descricao'] !== null
            && mb_strlen(
                $dados['descricao']
            ) > 255
        ) {
            throw new RuntimeException(
                'A descrição não pode ter mais de 255 caracteres.'
            );
        }

        if (
            $dados['responsavel'] !== null
            && mb_strlen(
                $dados['responsavel']
            ) > 150
        ) {
            throw new RuntimeException(
                'O responsável não pode ter mais de 150 caracteres.'
            );
        }
    }

    // ==================================================
    // VALIDAR CÓDIGO DUPLICADO
    // ==================================================

    private function validarCodigoDuplicado(
        int $empresaId,
        string $codigo,
        ?int $ignorarId = null
    ): void {

        $sql = "
            SELECT id

            FROM centros_custo

            WHERE empresa_id = :empresa_id
              AND codigo = :codigo
        ";

        $params = [
            ':empresa_id' => $empresaId,
            ':codigo' => $codigo
        ];

        if (
            $ignorarId !== null
            && $ignorarId > 0
        ) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $params[':ignorar_id'] =
                $ignorarId;
        }

        $sql .= "
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        if ($stmt->fetchColumn()) {
            throw new RuntimeException(
                'Já existe um centro de custo com este código.'
            );
        }
    }

    // ==================================================
    // VALIDAR STATUS
    // ==================================================

    private function validarStatus(
        int $statusId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE id = :id
              AND tipo = 'centro_custo'

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $statusId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Status inválido para centro de custo.'
            );
        }
    }

    // ==================================================
    // VALIDAR FILIAL
    // ==================================================

    private function validarFilial(
        int $empresaId,
        ?int $filialId
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
    // VALIDAR CENTRO PAI
    // ==================================================

    private function validarCentroPai(
        int $empresaId,
        ?int $centroPaiId,
        ?int $centroAtualId
    ): void {

        if ($centroPaiId === null) {
            return;
        }

        if (
            $centroAtualId !== null
            && $centroPaiId === $centroAtualId
        ) {
            throw new RuntimeException(
                'Um centro de custo não pode ser o seu próprio centro pai.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                centro_pai_id

            FROM centros_custo

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $centroPaiId,
            ':empresa_id' => $empresaId
        ]);

        $centroPai =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$centroPai) {
            throw new RuntimeException(
                'Centro pai não encontrado.'
            );
        }

        /*
         * Evita ciclos simples:
         *
         * Administração
         * └── Tecnologia
         *
         * Tecnologia não poderá tornar-se pai
         * de Administração.
         */

        if ($centroAtualId !== null) {
            $this->validarHierarquiaSemCiclo(
                $centroPaiId,
                $centroAtualId,
                $empresaId
            );
        }
    }

    // ==================================================
    // VALIDAR CICLO HIERÁRQUICO
    // ==================================================

    private function validarHierarquiaSemCiclo(
        int $centroPaiId,
        int $centroAtualId,
        int $empresaId
    ): void {

        $idAtual =
            $centroPaiId;

        $visitados = [];

        while ($idAtual > 0) {

            if (
                $idAtual ===
                $centroAtualId
            ) {
                throw new RuntimeException(
                    'A hierarquia selecionada cria um ciclo entre os centros de custo.'
                );
            }

            if (
                in_array(
                    $idAtual,
                    $visitados,
                    true
                )
            ) {
                throw new RuntimeException(
                    'Foi encontrada uma hierarquia circular entre centros de custo.'
                );
            }

            $visitados[] =
                $idAtual;

            $stmt =
                $this->pdo->prepare("
                    SELECT centro_pai_id

                    FROM centros_custo

                    WHERE id = :id
                      AND empresa_id = :empresa_id

                    LIMIT 1
                ");

            $stmt->execute([
                ':id' => $idAtual,
                ':empresa_id' => $empresaId
            ]);

            $pai =
                $stmt->fetchColumn();

            if (
                $pai === false
                || $pai === null
            ) {
                break;
            }

            $idAtual =
                (int)$pai;
        }
    }

    // ==================================================
    // VALIDAR SUBCENTROS
    // ==================================================

    private function validarSemSubcentros(
        int $id,
        int $empresaId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM centros_custo

            WHERE centro_pai_id = :id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'Este centro possui subcentros e não pode ser eliminado.'
            );
        }
    }

    // ==================================================
    // VALIDAR MOVIMENTOS ASSOCIADOS
    // ==================================================

    private function validarSemMovimentos(
        int $id,
        int $empresaId
    ): void {

        $consultas = [
            "
                SELECT COUNT(*)
                FROM contas_pagar
                WHERE centro_custo_id = :id
                  AND empresa_id = :empresa_id
            ",

            "
                SELECT COUNT(*)
                FROM contas_receber
                WHERE centro_custo_id = :id
                  AND empresa_id = :empresa_id
            ",

            "
                SELECT COUNT(*)
                FROM despesas
                WHERE centro_custo_id = :id
                AND empresa_id = :empresa_id
            ",

            "
                SELECT COUNT(*)
                FROM pagamentos_contas_pagar
                WHERE centro_custo_id = :id
                  AND empresa_id = :empresa_id
            ",

            "
                SELECT COUNT(*)
                FROM recebimentos
                WHERE centro_custo_id = :id
                  AND empresa_id = :empresa_id
            ",

            "
                SELECT COUNT(*)
                FROM movimentos_caixa
                WHERE centro_custo_id = :id
                  AND empresa_id = :empresa_id
            ",

            "
                SELECT COUNT(*)
                FROM movimentos_bancarios
                WHERE centro_custo_id = :id
                  AND empresa_id = :empresa_id
            "
        ];

        foreach ($consultas as $sql) {
            $stmt =
                $this->pdo->prepare(
                    $sql
                );

            $stmt->execute([
                ':id' => $id,
                ':empresa_id' => $empresaId
            ]);

            if (
                (int)$stmt->fetchColumn()
                > 0
            ) {
                throw new RuntimeException(
                    'Este centro de custo possui movimentos associados e não pode ser eliminado.'
                );
            }
        }
    }

    // ==================================================
    // BUSCAR CENTRO PARA AUDITORIA
    // ==================================================

    private function buscarCentroAuditoria(
        int $id,
        int $empresaId
    ): ?array {

        $stmt = $this->pdo->prepare("
        SELECT *
        FROM centros_custo
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
    // DADOS DO CENTRO PARA AUDITORIA
    // ==================================================

    private function dadosAuditoriaCentroCusto(
        array $centro
    ): array {

        return [
            'id' =>
            isset($centro['id'])
                ? (int)$centro['id']
                : null,

            'empresa_id' =>
            isset($centro['empresa_id'])
                ? (int)$centro['empresa_id']
                : null,

            'filial_id' =>
            !empty($centro['filial_id'])
                ? (int)$centro['filial_id']
                : null,

            'centro_pai_id' =>
            !empty($centro['centro_pai_id'])
                ? (int)$centro['centro_pai_id']
                : null,

            'codigo' =>
            $centro['codigo'] ?? null,

            'nome' =>
            $centro['nome'] ?? null,

            'descricao' =>
            $centro['descricao'] ?? null,

            'tipo' =>
            $centro['tipo'] ?? null,

            'responsavel' =>
            $centro['responsavel'] ?? null,

            'orcamento_mensal' =>
            isset($centro['orcamento_mensal'])
                ? (float)$centro['orcamento_mensal']
                : 0.0,

            'status_id' =>
            isset($centro['status_id'])
                ? (int)$centro['status_id']
                : null
        ];
    }

    // ==================================================
    // TEXTO OU NULL
    // ==================================================

    private function textoOuNull(
        mixed $valor
    ): ?string {

        $texto = trim(
            (string)(
                $valor ?? ''
            )
        );

        return $texto !== ''
            ? $texto
            : null;
    }

    // ==================================================
    // VALIDAR EMPRESA
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para o centro de custo.'
            );
        }
    }
}
