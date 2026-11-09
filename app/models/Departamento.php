<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class Departamento
{
    private PDO $pdo;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
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

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                departamento.id,
                departamento.uuid,
                departamento.empresa_id,
                departamento.filial_id,
                departamento.departamento_pai_id,
                departamento.codigo,
                departamento.nome,
                departamento.descricao,
                departamento.gestor_id,
                departamento.centro_custo_id,
                departamento.status_id,
                departamento.criado_em,
                departamento.atualizado_em,

                filial.nome AS filial_nome,

                pai.nome AS departamento_pai_nome,

                centro.nome AS centro_custo_nome,

                status.nome AS status_nome

            FROM departamentos departamento

            LEFT JOIN filiais filial
                ON filial.id =
                    departamento.filial_id

            LEFT JOIN departamentos pai
                ON pai.id =
                    departamento.departamento_pai_id

            LEFT JOIN centros_custo centro
                ON centro.id =
                    departamento.centro_custo_id

            INNER JOIN status
                ON status.id =
                    departamento.status_id

               AND status.tipo =
                    'departamento'

            WHERE departamento.empresa_id =
                :empresa_id

            ORDER BY
                COALESCE(
                    departamento.departamento_pai_id,
                    departamento.id
                ),
                departamento.nome ASC
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
    // BUSCAR
    // ==================================================

    public function buscar(
        int $empresaId,
        int $departamentoId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $departamentoId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                departamento.*,

                filial.nome AS filial_nome,

                pai.nome AS departamento_pai_nome,

                centro.nome AS centro_custo_nome,

                status.nome AS status_nome

            FROM departamentos departamento

            LEFT JOIN filiais filial
                ON filial.id =
                    departamento.filial_id

            LEFT JOIN departamentos pai
                ON pai.id =
                    departamento.departamento_pai_id

            LEFT JOIN centros_custo centro
                ON centro.id =
                    departamento.centro_custo_id

            INNER JOIN status
                ON status.id =
                    departamento.status_id

               AND status.tipo =
                    'departamento'

            WHERE departamento.id =
                :departamento_id

              AND departamento.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':departamento_id' =>
            $departamentoId,

            ':empresa_id' =>
            $empresaId
        ]);

        $departamento =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$departamento) {
            throw new RuntimeException(
                'Departamento não encontrado.'
            );
        }

        return $departamento;
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $dados =
            $this->normalizarDados(
                $empresaId,
                $dados
            );

        $this->validarCodigoDuplicado(
            $empresaId,
            $dados['codigo']
        );

        $this->validarNomeDuplicado(
            $empresaId,
            $dados['nome']
        );

        $this->validarRelacionamentos(
            $empresaId,
            $dados
        );


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                INSERT INTO departamentos
                (
                    uuid,
                    empresa_id,
                    filial_id,
                    departamento_pai_id,
                    codigo,
                    nome,
                    descricao,
                    gestor_id,
                    centro_custo_id,
                    status_id,
                    criado_por,
                    atualizado_por
                )
                VALUES
                (
                    UUID(),
                    :empresa_id,
                    :filial_id,
                    :departamento_pai_id,
                    :codigo,
                    :nome,
                    :descricao,
                    :gestor_id,
                    :centro_custo_id,
                    :status_id,
                    :criado_por,
                    :atualizado_por
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':filial_id' =>
                $dados['filial_id'],

                ':departamento_pai_id' =>
                $dados['departamento_pai_id'],

                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':descricao' =>
                $dados['descricao'],

                ':gestor_id' =>
                null,

                ':centro_custo_id' =>
                $dados['centro_custo_id'],

                ':status_id' =>
                $dados['status_id'],

                ':criado_por' =>
                $usuarioId,

                ':atualizado_por' =>
                $usuarioId
            ]);


            $departamentoId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($departamentoId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o departamento criado.'
                );
            }


            $departamentoDepois =
                $this->buscar(
                    $empresaId,
                    $departamentoId
                );


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'departamentos',
                $departamentoId,
                $departamentoDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'DEPARTAMENTOS',
                'CRIAR',
                sprintf(
                    'Criou o departamento %s - %s.',
                    $departamentoDepois['codigo'],
                    $departamentoDepois['nome']
                ),
                $departamentoId,
                $departamentoDepois['filial_id']
                    !== null
                    ? (int)$departamentoDepois['filial_id']
                    : null
            );


            $this->pdo->commit();


            return $departamentoId;
        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }


            throw $e;
        }
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $empresaId,
        int $usuarioId,
        int $departamentoId,
        array $dados
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $departamentoId
        );


        $atual =
            $this->buscar(
                $empresaId,
                $departamentoId
            );


        $dados =
            $this->normalizarDados(
                $empresaId,
                $dados
            );


        $this->validarCodigoDuplicado(
            $empresaId,
            $dados['codigo'],
            $departamentoId
        );


        $this->validarNomeDuplicado(
            $empresaId,
            $dados['nome'],
            $departamentoId
        );


        $this->validarRelacionamentos(
            $empresaId,
            $dados,
            $departamentoId
        );


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                UPDATE departamentos

                SET
                    filial_id =
                        :filial_id,

                    departamento_pai_id =
                        :departamento_pai_id,

                    codigo =
                        :codigo,

                    nome =
                        :nome,

                    descricao =
                        :descricao,

                    centro_custo_id =
                        :centro_custo_id,

                    status_id =
                        :status_id,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :departamento_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':filial_id' =>
                $dados['filial_id'],

                ':departamento_pai_id' =>
                $dados['departamento_pai_id'],

                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':descricao' =>
                $dados['descricao'],

                ':centro_custo_id' =>
                $dados['centro_custo_id'],

                ':status_id' =>
                $dados['status_id'],

                ':atualizado_por' =>
                $usuarioId,

                ':departamento_id' =>
                $departamentoId,

                ':empresa_id' =>
                $empresaId
            ]);


            $novo =
                $this->buscar(
                    $empresaId,
                    $departamentoId
                );


            /*
        |--------------------------------------------------------------------------
        | Nenhuma alteração real
        |--------------------------------------------------------------------------
        */

            if (
                $stmt->rowCount() === 0
                && $this->dadosIguais(
                    $atual,
                    $novo
                )
            ) {
                $this->pdo->commit();

                return;
            }


            /*
        |--------------------------------------------------------------------------
        | Auditoria
        |--------------------------------------------------------------------------
        */

            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'departamentos',
                $departamentoId,
                $atual,
                $novo
            );


            /*
        |--------------------------------------------------------------------------
        | Log
        |--------------------------------------------------------------------------
        */

            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'DEPARTAMENTOS',
                'EDITAR',
                sprintf(
                    'Atualizou o departamento %s - %s.',
                    $novo['codigo'],
                    $novo['nome']
                ),
                $departamentoId,
                $novo['filial_id']
                    !== null
                    ? (int)$novo['filial_id']
                    : null
            );


            $this->pdo->commit();
        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }


            throw $e;
        }
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $empresaId,
        int $usuarioId,
        int $departamentoId
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $departamentoId
        );


        $departamento =
            $this->buscar(
                $empresaId,
                $departamentoId
            );


        $this->validarSemSubdepartamentos(
            $empresaId,
            $departamentoId
        );


        /*
     * A validação de funcionários
     * permanece conforme a regra atual.
     */


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                DELETE FROM departamentos

                WHERE id =
                    :departamento_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':departamento_id' =>
                $departamentoId,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount()
                !== 1
            ) {
                throw new RuntimeException(
                    'Não foi possível eliminar o departamento.'
                );
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'departamentos',
                $departamentoId,
                $departamento
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'DEPARTAMENTOS',
                'ELIMINAR',
                sprintf(
                    'Eliminou o departamento %s - %s.',
                    $departamento['codigo'],
                    $departamento['nome']
                ),
                $departamentoId,
                $departamento['filial_id']
                    !== null
                    ? (int)$departamento['filial_id']
                    : null
            );


            $this->pdo->commit();
        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }


            throw $e;
        }
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        int $empresaId
    ): array {

        return [
            'departamentos' =>
            $this->listar(
                $empresaId
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'centros_custo' =>
            $this->listarCentrosCusto(
                $empresaId
            ),

            'status' =>
            $this->listarStatus()
        ];
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    private function listarFiliais(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                codigo,
                nome

            FROM filiais

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
    // CENTROS DE CUSTO
    // ==================================================

    private function listarCentrosCusto(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                centro.id,
                centro.codigo,
                centro.nome

            FROM centros_custo centro

            INNER JOIN status
                ON status.id =
                    centro.status_id

               AND status.tipo =
                    'centro_custo'

            WHERE centro.empresa_id =
                :empresa_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            ORDER BY centro.nome ASC
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
        $stmt = $this->pdo->query("
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo =
                'departamento'

            ORDER BY id ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // NORMALIZAÇÃO
    // ==================================================

    private function normalizarDados(
        int $empresaId,
        array $dados
    ): array {

        $codigo =
            strtoupper(
                trim(
                    (string)(
                        $dados['codigo']
                        ?? ''
                    )
                )
            );

        $nome =
            trim(
                (string)(
                    $dados['nome']
                    ?? ''
                )
            );

        $descricao =
            trim(
                (string)(
                    $dados['descricao']
                    ?? ''
                )
            );

        $filialId =
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            );

        $departamentoPaiId =
            $this->inteiroOpcional(
                $dados['departamento_pai_id']
                    ?? null
            );

        $centroCustoId =
            $this->inteiroOpcional(
                $dados['centro_custo_id']
                    ?? null
            );

        $statusId =
            (int)(
                $dados['status_id']
                ?? 0
            );

        if ($codigo === '') {
            throw new RuntimeException(
                'Informe o código do departamento.'
            );
        }

        if (mb_strlen($codigo) > 20) {
            throw new RuntimeException(
                'O código do departamento não pode exceder 20 caracteres.'
            );
        }

        if ($nome === '') {
            throw new RuntimeException(
                'Informe o nome do departamento.'
            );
        }

        if (mb_strlen($nome) > 150) {
            throw new RuntimeException(
                'O nome do departamento não pode exceder 150 caracteres.'
            );
        }

        if ($statusId <= 0) {
            $statusId =
                $this->statusAtivoId();
        }

        return [
            'empresa_id' =>
            $empresaId,

            'codigo' =>
            $codigo,

            'nome' =>
            $nome,

            'descricao' =>
            $descricao !== ''
                ? $descricao
                : null,

            'filial_id' =>
            $filialId,

            'departamento_pai_id' =>
            $departamentoPaiId,

            'centro_custo_id' =>
            $centroCustoId,

            'status_id' =>
            $statusId
        ];
    }

    // ==================================================
    // RELACIONAMENTOS
    // ==================================================

    private function validarRelacionamentos(
        int $empresaId,
        array $dados,
        ?int $departamentoId = null
    ): void {

        if ($dados['filial_id'] !== null) {
            $this->validarFilial(
                $empresaId,
                $dados['filial_id']
            );
        }

        if (
            $dados['departamento_pai_id'] !== null
        ) {

            if (
                $departamentoId !== null

                && $dados['departamento_pai_id'] === $departamentoId
            ) {
                throw new RuntimeException(
                    'Um departamento não pode ser o seu próprio departamento superior.'
                );
            }

            $this->buscar(
                $empresaId,
                $dados['departamento_pai_id']
            );

            if ($departamentoId !== null) {
                $this->validarSemCiclo(
                    $empresaId,
                    $departamentoId,
                    $dados['departamento_pai_id']
                );
            }
        }

        if (
            $dados['centro_custo_id'] !== null
        ) {
            $this->validarCentroCusto(
                $empresaId,
                $dados['centro_custo_id']
            );
        }

        $this->validarStatus(
            $dados['status_id']
        );
    }

    // ==================================================
    // DUPLICADOS
    // ==================================================

    private function validarCodigoDuplicado(
        int $empresaId,
        string $codigo,
        ?int $ignorarId = null
    ): void {

        $sql = "
            SELECT id

            FROM departamentos

            WHERE empresa_id =
                :empresa_id

              AND codigo =
                :codigo
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':codigo' =>
            $codigo
        ];

        if ($ignorarId !== null) {

            $sql .= "
                AND id <> :ignorar_id
            ";

            $params[':ignorar_id'] =
                $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        if ($stmt->fetchColumn()) {
            throw new RuntimeException(
                'Já existe um departamento com este código.'
            );
        }
    }

    private function validarNomeDuplicado(
        int $empresaId,
        string $nome,
        ?int $ignorarId = null
    ): void {

        $sql = "
            SELECT id

            FROM departamentos

            WHERE empresa_id =
                :empresa_id

              AND LOWER(nome) =
                LOWER(:nome)
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':nome' =>
            $nome
        ];

        if ($ignorarId !== null) {

            $sql .= "
                AND id <> :ignorar_id
            ";

            $params[':ignorar_id'] =
                $ignorarId;
        }

        $sql .= " LIMIT 1";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        if ($stmt->fetchColumn()) {
            throw new RuntimeException(
                'Já existe um departamento com este nome.'
            );
        }
    }

    // ==================================================
    // HIERARQUIA
    // ==================================================

    private function validarSemSubdepartamentos(
        int $empresaId,
        int $departamentoId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM departamentos

            WHERE empresa_id =
                :empresa_id

              AND departamento_pai_id =
                :departamento_id
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':departamento_id' =>
            $departamentoId
        ]);

        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'Não é possível eliminar um departamento que possui subdepartamentos.'
            );
        }
    }

    private function validarSemCiclo(
        int $empresaId,
        int $departamentoId,
        int $novoPaiId
    ): void {

        $atualId =
            $novoPaiId;

        $limite =
            100;

        while (
            $atualId > 0
            && $limite > 0
        ) {

            if (
                $atualId ===
                $departamentoId
            ) {
                throw new RuntimeException(
                    'A hierarquia selecionada criaria um ciclo entre departamentos.'
                );
            }

            $stmt = $this->pdo->prepare("
                SELECT departamento_pai_id

                FROM departamentos

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");

            $stmt->execute([
                ':id' =>
                $atualId,

                ':empresa_id' =>
                $empresaId
            ]);

            $pai =
                $stmt->fetchColumn();

            if (
                $pai === false
                || $pai === null
            ) {
                break;
            }

            $atualId =
                (int)$pai;

            $limite--;
        }

        if ($limite <= 0) {
            throw new RuntimeException(
                'A hierarquia dos departamentos excedeu o limite permitido.'
            );
        }
    }

    // ==================================================
    // VALIDAÇÕES AUXILIARES
    // ==================================================

    private function validarFilial(
        int $empresaId,
        int $filialId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM filiais

            WHERE id =
                :filial_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':filial_id' =>
            $filialId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'A filial selecionada não pertence à empresa.'
            );
        }
    }

    private function validarCentroCusto(
        int $empresaId,
        int $centroCustoId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT centro.id

            FROM centros_custo centro

            INNER JOIN status
                ON status.id =
                    centro.status_id

               AND status.tipo =
                    'centro_custo'

            WHERE centro.id =
                :centro_custo_id

              AND centro.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':centro_custo_id' =>
            $centroCustoId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Centro de custo não encontrado para a empresa.'
            );
        }
    }

    private function validarStatus(
        int $statusId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE id =
                :status_id

              AND tipo =
                'departamento'

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Estado do departamento inválido.'
            );
        }
    }

    private function statusAtivoId(): int
    {
        $stmt = $this->pdo->query("
            SELECT id

            FROM status

            WHERE tipo =
                'departamento'

              AND LOWER(nome) IN
              (
                  'activo',
                  'ativo'
              )

            LIMIT 1
        ");

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                'O estado ativo do departamento não está configurado.'
            );
        }

        return $id;
    }

    private function validarUsuario(
        int $usuarioId
    ): void {

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM usuarios

            WHERE id =
                :usuario_id

            LIMIT 1
        ");

        $stmt->execute([
            ':usuario_id' =>
            $usuarioId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Utilizador não encontrado.'
            );
        }
    }

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para a gestão de departamentos.'
            );
        }
    }

    private function validarId(
        int $id
    ): void {

        if ($id <= 0) {
            throw new RuntimeException(
                'Departamento inválido.'
            );
        }
    }

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        $inteiro =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $inteiro === false
            || $inteiro <= 0
        ) {
            return null;
        }

        return (int)$inteiro;
    }

    private function dadosIguais(
        array $anterior,
        array $novo
    ): bool {

        $campos = [
            'filial_id',
            'departamento_pai_id',
            'codigo',
            'nome',
            'descricao',
            'centro_custo_id',
            'status_id'
        ];

        foreach ($campos as $campo) {

            $valorAnterior =
                $anterior[$campo]
                ?? null;

            $valorNovo =
                $novo[$campo]
                ?? null;

            if (
                (string)$valorAnterior
                !== (string)$valorNovo
            ) {
                return false;
            }
        }

        return true;
    }
}
