<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class Cargo
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
                cargo.id,
                cargo.uuid,
                cargo.empresa_id,
                cargo.departamento_id,
                cargo.centro_custo_id,
                cargo.codigo,
                cargo.nome,
                cargo.descricao,
                cargo.responsabilidades,
                cargo.requisitos,
                cargo.nivel_hierarquico,
                cargo.salario_minimo,
                cargo.salario_maximo,
                cargo.carga_horaria_semanal,
                cargo.permite_horas_extras,
                cargo.cargo_chefia,
                cargo.status_id,
                cargo.criado_em,
                cargo.atualizado_em,

                departamento.nome AS departamento_nome,
                departamento.codigo AS departamento_codigo,

                centro.nome AS centro_custo_nome,
                centro.codigo AS centro_custo_codigo,

                status.nome AS status_nome

            FROM cargos cargo

            LEFT JOIN departamentos departamento
                ON departamento.id =
                    cargo.departamento_id

               AND departamento.empresa_id =
                    cargo.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id =
                    cargo.centro_custo_id

               AND centro.empresa_id =
                    cargo.empresa_id

            INNER JOIN status
                ON status.id =
                    cargo.status_id

               AND status.tipo =
                    'cargo'

            WHERE cargo.empresa_id =
                :empresa_id

            ORDER BY
                cargo.nivel_hierarquico ASC,
                cargo.nome ASC
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
        int $cargoId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $cargoId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                cargo.*,

                departamento.nome AS departamento_nome,
                departamento.codigo AS departamento_codigo,

                centro.nome AS centro_custo_nome,
                centro.codigo AS centro_custo_codigo,

                status.nome AS status_nome

            FROM cargos cargo

            LEFT JOIN departamentos departamento
                ON departamento.id =
                    cargo.departamento_id

               AND departamento.empresa_id =
                    cargo.empresa_id

            LEFT JOIN centros_custo centro
                ON centro.id =
                    cargo.centro_custo_id

               AND centro.empresa_id =
                    cargo.empresa_id

            INNER JOIN status
                ON status.id =
                    cargo.status_id

               AND status.tipo =
                    'cargo'

            WHERE cargo.id =
                :cargo_id

              AND cargo.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':cargo_id' =>
            $cargoId,

            ':empresa_id' =>
            $empresaId
        ]);

        $cargo =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$cargo) {
            throw new RuntimeException(
                'Cargo não encontrado.'
            );
        }

        return $cargo;
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
                INSERT INTO cargos
                (
                    uuid,
                    empresa_id,
                    departamento_id,
                    centro_custo_id,
                    codigo,
                    nome,
                    descricao,
                    responsabilidades,
                    requisitos,
                    nivel_hierarquico,
                    salario_minimo,
                    salario_maximo,
                    carga_horaria_semanal,
                    permite_horas_extras,
                    cargo_chefia,
                    status_id,
                    criado_por,
                    atualizado_por
                )
                VALUES
                (
                    UUID(),
                    :empresa_id,
                    :departamento_id,
                    :centro_custo_id,
                    :codigo,
                    :nome,
                    :descricao,
                    :responsabilidades,
                    :requisitos,
                    :nivel_hierarquico,
                    :salario_minimo,
                    :salario_maximo,
                    :carga_horaria_semanal,
                    :permite_horas_extras,
                    :cargo_chefia,
                    :status_id,
                    :criado_por,
                    :atualizado_por
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':departamento_id' =>
                $dados['departamento_id'],

                ':centro_custo_id' =>
                $dados['centro_custo_id'],

                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':descricao' =>
                $dados['descricao'],

                ':responsabilidades' =>
                $dados['responsabilidades'],

                ':requisitos' =>
                $dados['requisitos'],

                ':nivel_hierarquico' =>
                $dados['nivel_hierarquico'],

                ':salario_minimo' =>
                $dados['salario_minimo'],

                ':salario_maximo' =>
                $dados['salario_maximo'],

                ':carga_horaria_semanal' =>
                $dados['carga_horaria_semanal'],

                ':permite_horas_extras' =>
                $dados['permite_horas_extras'],

                ':cargo_chefia' =>
                $dados['cargo_chefia'],

                ':status_id' =>
                $dados['status_id'],

                ':criado_por' =>
                $usuarioId,

                ':atualizado_por' =>
                $usuarioId
            ]);


            $cargoId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($cargoId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o cargo criado.'
                );
            }


            $cargoDepois =
                $this->buscar(
                    $empresaId,
                    $cargoId
                );


            $this->auditoria->insercao(
                $empresaId,
                $usuarioId,
                'cargos',
                $cargoId,
                $cargoDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CARGOS',
                'CRIAR',
                sprintf(
                    'Criou o cargo %s - %s.',
                    $cargoDepois['codigo'],
                    $cargoDepois['nome']
                ),
                $cargoId
            );


            $this->pdo->commit();


            return $cargoId;
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
        int $cargoId,
        array $dados
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $cargoId
        );


        $atual =
            $this->buscar(
                $empresaId,
                $cargoId
            );


        $dados =
            $this->normalizarDados(
                $dados
            );


        $this->validarCodigoDuplicado(
            $empresaId,
            $dados['codigo'],
            $cargoId
        );


        $this->validarNomeDuplicado(
            $empresaId,
            $dados['nome'],
            $cargoId
        );


        $this->validarRelacionamentos(
            $empresaId,
            $dados
        );


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                UPDATE cargos

                SET
                    departamento_id =
                        :departamento_id,

                    centro_custo_id =
                        :centro_custo_id,

                    codigo =
                        :codigo,

                    nome =
                        :nome,

                    descricao =
                        :descricao,

                    responsabilidades =
                        :responsabilidades,

                    requisitos =
                        :requisitos,

                    nivel_hierarquico =
                        :nivel_hierarquico,

                    salario_minimo =
                        :salario_minimo,

                    salario_maximo =
                        :salario_maximo,

                    carga_horaria_semanal =
                        :carga_horaria_semanal,

                    permite_horas_extras =
                        :permite_horas_extras,

                    cargo_chefia =
                        :cargo_chefia,

                    status_id =
                        :status_id,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :cargo_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':departamento_id' =>
                $dados['departamento_id'],

                ':centro_custo_id' =>
                $dados['centro_custo_id'],

                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':descricao' =>
                $dados['descricao'],

                ':responsabilidades' =>
                $dados['responsabilidades'],

                ':requisitos' =>
                $dados['requisitos'],

                ':nivel_hierarquico' =>
                $dados['nivel_hierarquico'],

                ':salario_minimo' =>
                $dados['salario_minimo'],

                ':salario_maximo' =>
                $dados['salario_maximo'],

                ':carga_horaria_semanal' =>
                $dados['carga_horaria_semanal'],

                ':permite_horas_extras' =>
                $dados['permite_horas_extras'],

                ':cargo_chefia' =>
                $dados['cargo_chefia'],

                ':status_id' =>
                $dados['status_id'],

                ':atualizado_por' =>
                $usuarioId,

                ':cargo_id' =>
                $cargoId,

                ':empresa_id' =>
                $empresaId
            ]);


            $novo =
                $this->buscar(
                    $empresaId,
                    $cargoId
                );


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


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'cargos',
                $cargoId,
                $atual,
                $novo
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CARGOS',
                'EDITAR',
                sprintf(
                    'Atualizou o cargo %s - %s.',
                    $novo['codigo'],
                    $novo['nome']
                ),
                $cargoId
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
        int $cargoId
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $cargoId
        );


        $cargo =
            $this->buscar(
                $empresaId,
                $cargoId
            );


        /*
     * Mantém-se aqui a validação de funcionários
     * quando essa regra estiver ativa no módulo.
     */


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                DELETE FROM cargos

                WHERE id =
                    :cargo_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':cargo_id' =>
                $cargoId,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount()
                !== 1
            ) {
                throw new RuntimeException(
                    'Não foi possível eliminar o cargo.'
                );
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'cargos',
                $cargoId,
                $cargo
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'CARGOS',
                'ELIMINAR',
                sprintf(
                    'Eliminou o cargo %s - %s.',
                    $cargo['codigo'],
                    $cargo['nome']
                ),
                $cargoId
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

        $this->validarEmpresa(
            $empresaId
        );

        return [
            'cargos' =>
            $this->listar(
                $empresaId
            ),

            'departamentos' =>
            $this->listarDepartamentos(
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
    // DEPARTAMENTOS
    // ==================================================

    private function listarDepartamentos(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
        SELECT
            departamento.id,
            departamento.codigo,
            departamento.nome,
            departamento.centro_custo_id

        FROM departamentos departamento

        INNER JOIN status
            ON status.id =
                departamento.status_id

           AND status.tipo =
                'departamento'

        WHERE departamento.empresa_id =
            :empresa_id

          AND LOWER(status.nome) IN
          (
              'activo',
              'ativo',
              'activa',
              'ativa'
          )

        ORDER BY
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

            ORDER BY
                centro.nome ASC
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
                'cargo'

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
            $this->textoOpcional(
                $dados['descricao']
                    ?? null
            );

        $responsabilidades =
            $this->textoOpcional(
                $dados['responsabilidades']
                    ?? null
            );

        $requisitos =
            $this->textoOpcional(
                $dados['requisitos']
                    ?? null
            );

        $departamentoId =
            $this->inteiroOpcional(
                $dados['departamento_id']
                    ?? null
            );

        $centroCustoId =
            $this->inteiroOpcional(
                $dados['centro_custo_id']
                    ?? null
            );

        $nivelHierarquico =
            (int)(
                $dados['nivel_hierarquico']
                ?? 1
            );

        $salarioMinimo =
            $this->decimalOpcional(
                $dados['salario_minimo']
                    ?? null
            );

        $salarioMaximo =
            $this->decimalOpcional(
                $dados['salario_maximo']
                    ?? null
            );

        $cargaHorariaSemanal =
            $this->decimalOpcional(
                $dados['carga_horaria_semanal']
                    ?? 40
            );

        $permiteHorasExtras =
            $this->booleano(
                $dados['permite_horas_extras']
                    ?? 0
            );

        $cargoChefia =
            $this->booleano(
                $dados['cargo_chefia']
                    ?? 0
            );

        $statusId =
            (int)(
                $dados['status_id']
                ?? 0
            );

        if ($codigo === '') {
            throw new RuntimeException(
                'Informe o código do cargo.'
            );
        }

        if (mb_strlen($codigo) > 20) {
            throw new RuntimeException(
                'O código do cargo não pode exceder 20 caracteres.'
            );
        }

        if ($nome === '') {
            throw new RuntimeException(
                'Informe o nome do cargo.'
            );
        }

        if (mb_strlen($nome) > 150) {
            throw new RuntimeException(
                'O nome do cargo não pode exceder 150 caracteres.'
            );
        }

        if (
            $nivelHierarquico <= 0
            || $nivelHierarquico > 255
        ) {
            throw new RuntimeException(
                'O nível hierárquico do cargo é inválido.'
            );
        }

        if (
            $salarioMinimo !== null
            && $salarioMinimo < 0
        ) {
            throw new RuntimeException(
                'O salário mínimo não pode ser negativo.'
            );
        }

        if (
            $salarioMaximo !== null
            && $salarioMaximo < 0
        ) {
            throw new RuntimeException(
                'O salário máximo não pode ser negativo.'
            );
        }

        if (
            $salarioMinimo !== null
            && $salarioMaximo !== null
            && $salarioMaximo < $salarioMinimo
        ) {
            throw new RuntimeException(
                'O salário máximo não pode ser inferior ao salário mínimo.'
            );
        }

        if (
            $cargaHorariaSemanal !== null
            && (
                $cargaHorariaSemanal <= 0
                || $cargaHorariaSemanal > 168
            )
        ) {
            throw new RuntimeException(
                'A carga horária semanal é inválida.'
            );
        }

        if ($statusId <= 0) {
            $statusId =
                $this->statusAtivoId();
        }

        return [
            'departamento_id' =>
            $departamentoId,

            'centro_custo_id' =>
            $centroCustoId,

            'codigo' =>
            $codigo,

            'nome' =>
            $nome,

            'descricao' =>
            $descricao,

            'responsabilidades' =>
            $responsabilidades,

            'requisitos' =>
            $requisitos,

            'nivel_hierarquico' =>
            $nivelHierarquico,

            'salario_minimo' =>
            $salarioMinimo,

            'salario_maximo' =>
            $salarioMaximo,

            'carga_horaria_semanal' =>
            $cargaHorariaSemanal,

            'permite_horas_extras' =>
            $permiteHorasExtras,

            'cargo_chefia' =>
            $cargoChefia,

            'status_id' =>
            $statusId
        ];
    }

    // ==================================================
    // RELACIONAMENTOS
    // ==================================================

    private function validarRelacionamentos(
        int $empresaId,
        array $dados
    ): void {

        if (
            $dados['departamento_id']
            !== null
        ) {
            $this->validarDepartamento(
                $empresaId,
                $dados['departamento_id']
            );
        }

        if (
            $dados['centro_custo_id']
            !== null
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

            FROM cargos

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
                'Já existe um cargo com este código.'
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

            FROM cargos

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
                'Já existe um cargo com este nome.'
            );
        }
    }

    // ==================================================
    // VALIDAÇÕES AUXILIARES
    // ==================================================

    private function validarDepartamento(
        int $empresaId,
        int $departamentoId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM departamentos

            WHERE id =
                :departamento_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':departamento_id' =>
            $departamentoId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'O departamento selecionado não pertence à empresa.'
            );
        }
    }

    private function validarCentroCusto(
        int $empresaId,
        int $centroCustoId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

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

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'O centro de custo selecionado não pertence à empresa.'
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
                'cargo'

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Estado do cargo inválido.'
            );
        }
    }

    private function statusAtivoId(): int
    {
        $stmt = $this->pdo->query("
            SELECT id

            FROM status

            WHERE tipo =
                'cargo'

              AND LOWER(nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            LIMIT 1
        ");

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                'O estado ativo do cargo não está configurado.'
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
                'Empresa inválida para a gestão de cargos.'
            );
        }
    }

    private function validarId(
        int $id
    ): void {

        if ($id <= 0) {
            throw new RuntimeException(
                'Cargo inválido.'
            );
        }
    }

    // ==================================================
    // HELPERS
    // ==================================================

    private function dadosIguais(
        array $anterior,
        array $novo
    ): bool {

        $campos = [
            'departamento_id',
            'centro_custo_id',
            'codigo',
            'nome',
            'descricao',
            'responsabilidades',
            'requisitos',
            'nivel_hierarquico',
            'salario_minimo',
            'salario_maximo',
            'carga_horaria_semanal',
            'permite_horas_extras',
            'cargo_chefia',
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

    private function decimalOpcional(
        mixed $valor
    ): ?float {

        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        $texto =
            str_replace(
                ',',
                '.',
                trim((string)$valor)
            );

        if (!is_numeric($texto)) {
            throw new RuntimeException(
                'Foi informado um valor numérico inválido.'
            );
        }

        return round(
            (float)$texto,
            2
        );
    }

    private function textoOpcional(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    private function booleano(
        mixed $valor
    ): int {

        if (is_bool($valor)) {
            return $valor
                ? 1
                : 0;
        }

        $texto =
            strtolower(
                trim(
                    (string)$valor
                )
            );

        return in_array(
            $texto,
            [
                '1',
                'true',
                'sim',
                'yes',
                'on'
            ],
            true
        )
            ? 1
            : 0;
    }
}
