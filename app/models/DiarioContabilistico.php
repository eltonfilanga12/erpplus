<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class DiarioContabilistico
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
                diario.id,
                diario.empresa_id,
                diario.codigo,
                diario.nome,
                diario.tipo,
                diario.descricao,
                diario.permite_manual,
                diario.numeracao_automatica,
                diario.ultimo_numero,
                diario.status_id,
                diario.criado_por,
                diario.atualizado_por,
                diario.criado_em,
                diario.atualizado_em,

                st.nome AS status_nome,
                st.descricao AS status_descricao,

                (
                    SELECT COUNT(*)

                    FROM lancamentos_contabilisticos lancamento

                    WHERE lancamento.diario_id =
                        diario.id
                ) AS quantidade_lancamentos

            FROM diarios_contabilisticos diario

            INNER JOIN status st
                ON st.id = diario.status_id
               AND st.tipo =
                   'diario_contabilistico'

            WHERE diario.empresa_id =
                :empresa_id

            ORDER BY
                diario.codigo ASC,
                diario.nome ASC
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
        int $id,
        int $empresaId
    ): ?array {

        $this->validarEmpresa(
            $empresaId
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Diário contabilístico inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                diario.*,

                st.nome AS status_nome,
                st.descricao AS status_descricao

            FROM diarios_contabilisticos diario

            INNER JOIN status st
                ON st.id = diario.status_id
               AND st.tipo =
                   'diario_contabilistico'

            WHERE diario.id = :id
              AND diario.empresa_id =
                  :empresa_id

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
    // GUARDAR
    // ==================================================

    public function guardar(
        array $dados
    ): int {

        $dados =
            $this->normalizarDados(
                $dados
            );

        $this->validarDados(
            $dados
        );

        $this->validarCodigoDuplicado(
            $dados['empresa_id'],
            $dados['codigo'],
            $dados['id']
        );

        if ($dados['id'] === null) {
            return $this->criar(
                $dados
            );
        }

        return $this->atualizar(
            $dados
        );
    }

    // ==================================================
    // CRIAR
    // ==================================================

    private function criar(
        array $dados
    ): int {

        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                INSERT INTO diarios_contabilisticos
                (
                    empresa_id,
                    codigo,
                    nome,
                    tipo,
                    descricao,
                    permite_manual,
                    numeracao_automatica,
                    ultimo_numero,
                    status_id,
                    criado_por,
                    atualizado_por
                )
                VALUES
                (
                    :empresa_id,
                    :codigo,
                    :nome,
                    :tipo,
                    :descricao,
                    :permite_manual,
                    :numeracao_automatica,
                    0,
                    :status_id,
                    :criado_por,
                    :atualizado_por
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $dados['empresa_id'],

                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':tipo' =>
                $dados['tipo'],

                ':descricao' =>
                $dados['descricao'],

                ':permite_manual' =>
                $dados['permite_manual'],

                ':numeracao_automatica' =>
                $dados['numeracao_automatica'],

                ':status_id' =>
                $dados['status_id'],

                ':criado_por' =>
                $dados['usuario_id'],

                ':atualizado_por' =>
                $dados['usuario_id']
            ]);


            $diarioId =
                (int)$this->pdo
                    ->lastInsertId();


            if ($diarioId <= 0) {
                throw new RuntimeException(
                    'Não foi possível identificar o diário contabilístico criado.'
                );
            }


            $diarioDepois =
                $this->buscar(
                    $diarioId,
                    (int)$dados['empresa_id']
                );


            if (!$diarioDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o diário contabilístico após a criação.'
                );
            }


            $this->auditoria->insercao(
                (int)$dados['empresa_id'],
                (int)$dados['usuario_id'],
                'diarios_contabilisticos',
                $diarioId,
                $diarioDepois
            );


            $this->log->registrar(
                (int)$dados['empresa_id'],
                (int)$dados['usuario_id'],
                'DIARIO_CONTABILISTICO',
                'CRIAR',
                sprintf(
                    'Criou o diário contabilístico %s - %s.',
                    $diarioDepois['codigo'],
                    $diarioDepois['nome']
                ),
                $diarioId
            );


            $this->pdo->commit();


            return $diarioId;
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
    // ATUALIZAR
    // ==================================================

    private function atualizar(
        array $dados
    ): int {

        $diarioAntes =
            $this->buscar(
                $dados['id'],
                $dados['empresa_id']
            );


        if (!$diarioAntes) {
            throw new RuntimeException(
                'Diário contabilístico não encontrado.'
            );
        }


        if (
            $this->statusEh(
                (int)$diarioAntes['status_id'],
                'Bloqueado'
            )
        ) {
            throw new RuntimeException(
                'Um diário bloqueado não pode ser editado.'
            );
        }


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                UPDATE diarios_contabilisticos

                SET
                    codigo =
                        :codigo,

                    nome =
                        :nome,

                    tipo =
                        :tipo,

                    descricao =
                        :descricao,

                    permite_manual =
                        :permite_manual,

                    numeracao_automatica =
                        :numeracao_automatica,

                    status_id =
                        :status_id,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':codigo' =>
                $dados['codigo'],

                ':nome' =>
                $dados['nome'],

                ':tipo' =>
                $dados['tipo'],

                ':descricao' =>
                $dados['descricao'],

                ':permite_manual' =>
                $dados['permite_manual'],

                ':numeracao_automatica' =>
                $dados['numeracao_automatica'],

                ':status_id' =>
                $dados['status_id'],

                ':atualizado_por' =>
                $dados['usuario_id'],

                ':id' =>
                $dados['id'],

                ':empresa_id' =>
                $dados['empresa_id']
            ]);


            $diarioDepois =
                $this->buscar(
                    (int)$dados['id'],
                    (int)$dados['empresa_id']
                );


            if (!$diarioDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o diário após a atualização.'
                );
            }


            $this->auditoria->atualizacao(
                (int)$dados['empresa_id'],
                (int)$dados['usuario_id'],
                'diarios_contabilisticos',
                (int)$dados['id'],
                $diarioAntes,
                $diarioDepois
            );


            $this->log->registrar(
                (int)$dados['empresa_id'],
                (int)$dados['usuario_id'],
                'DIARIO_CONTABILISTICO',
                'EDITAR',
                sprintf(
                    'Atualizou o diário contabilístico %s - %s.',
                    $diarioDepois['codigo'],
                    $diarioDepois['nome']
                ),
                (int)$dados['id']
            );


            $this->pdo->commit();


            return (int)$dados['id'];
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
    // ALTERAR STATUS
    // ==================================================

    public function alterarStatus(
        int $id,
        int $empresaId,
        int $statusId,
        int $usuarioId
    ): bool {

        $diarioAntes =
            $this->buscar(
                $id,
                $empresaId
            );


        if (!$diarioAntes) {
            throw new RuntimeException(
                'Diário contabilístico não encontrado.'
            );
        }


        $this->validarStatus(
            $statusId
        );


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                UPDATE diarios_contabilisticos

                SET
                    status_id =
                        :status_id,

                    atualizado_por =
                        :usuario_id

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':status_id' =>
                $statusId,

                ':usuario_id' =>
                $usuarioId,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount() !== 1
            ) {
                $this->pdo->rollBack();

                return false;
            }


            $diarioDepois =
                $this->buscar(
                    $id,
                    $empresaId
                );


            if (!$diarioDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o diário após alterar o status.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'diarios_contabilisticos',
                $id,
                $diarioAntes,
                $diarioDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'DIARIO_CONTABILISTICO',
                'ALTERAR_STATUS',
                sprintf(
                    'Alterou o estado do diário contabilístico %s - %s de %s para %s.',
                    $diarioDepois['codigo'],
                    $diarioDepois['nome'],
                    $diarioAntes['status_nome'],
                    $diarioDepois['status_nome']
                ),
                $id
            );


            $this->pdo->commit();


            return true;
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
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        $diario =
            $this->buscar(
                $id,
                $empresaId
            );


        if (!$diario) {
            throw new RuntimeException(
                'Diário contabilístico não encontrado.'
            );
        }


        if (
            $this->possuiLancamentos(
                $id
            )
        ) {
            throw new RuntimeException(
                'O diário possui lançamentos contabilísticos e não pode ser eliminado.'
            );
        }


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                DELETE FROM diarios_contabilisticos

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);


            if (
                $stmt->rowCount() !== 1
            ) {
                $this->pdo->rollBack();

                return false;
            }


            $this->auditoria->exclusao(
                $empresaId,
                $usuarioId,
                'diarios_contabilisticos',
                $id,
                $diario
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'DIARIO_CONTABILISTICO',
                'ELIMINAR',
                sprintf(
                    'Eliminou o diário contabilístico %s - %s.',
                    $diario['codigo'],
                    $diario['nome']
                ),
                $id
            );


            $this->pdo->commit();


            return true;
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
    // LISTAR STATUS
    // ==================================================

    public function listarStatus(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                tipo,
                nome,
                descricao

            FROM status

            WHERE tipo =
                'diario_contabilistico'

            ORDER BY id ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // PRÓXIMO NÚMERO
    // ==================================================

    public function proximoNumero(
        int $id,
        int $empresaId
    ): int {

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    ultimo_numero,
                    numeracao_automatica

                FROM diarios_contabilisticos

                WHERE id = :id
                  AND empresa_id =
                      :empresa_id

                FOR UPDATE
            ");

            $stmt->execute([
                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

            $diario =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$diario) {
                throw new RuntimeException(
                    'Diário contabilístico não encontrado.'
                );
            }

            if (
                (int)$diario['numeracao_automatica'] !== 1
            ) {
                throw new RuntimeException(
                    'Este diário não utiliza numeração automática.'
                );
            }

            $proximo =
                (int)$diario['ultimo_numero']
                + 1;

            $stmt = $this->pdo->prepare("
                UPDATE diarios_contabilisticos

                SET ultimo_numero =
                    :ultimo_numero

                WHERE id = :id
                  AND empresa_id =
                      :empresa_id
            ");

            $stmt->execute([
                ':ultimo_numero' =>
                $proximo,

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

            $this->pdo->commit();

            return $proximo;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
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
            (int)(
                $dados['empresa_id']
                ?? 0
            ),

            'usuario_id' =>
            (int)(
                $dados['usuario_id']
                ?? 0
            ),

            'codigo' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['codigo']
                        ?? ''
                    )
                )
            ),

            'nome' =>
            trim(
                (string)(
                    $dados['nome']
                    ?? ''
                )
            ),

            'tipo' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo']
                        ?? ''
                    )
                )
            ),

            'descricao' =>
            $this->textoOuNull(
                $dados['descricao']
                    ?? null
            ),

            'permite_manual' =>
            !empty($dados['permite_manual'])
                ? 1
                : 0,

            'numeracao_automatica' =>
            !empty($dados['numeracao_automatica'])
                ? 1
                : 0,

            'status_id' =>
            (int)(
                $dados['status_id']
                ?? 0
            )
        ];
    }

    // ==================================================
    // VALIDAR
    // ==================================================

    private function validarDados(
        array $dados
    ): void {

        $this->validarEmpresa(
            $dados['empresa_id']
        );

        if ($dados['usuario_id'] <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        if ($dados['codigo'] === '') {
            throw new RuntimeException(
                'Informe o código do diário.'
            );
        }

        if ($dados['nome'] === '') {
            throw new RuntimeException(
                'Informe o nome do diário.'
            );
        }

        if (
            !in_array(
                $dados['tipo'],
                [
                    'GERAL',
                    'VENDAS',
                    'COMPRAS',
                    'CAIXA',
                    'BANCOS',
                    'STOCK',
                    'SALARIOS',
                    'IMPOSTOS',
                    'ABERTURA',
                    'ENCERRAMENTO',
                    'OUTROS'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Tipo de diário contabilístico inválido.'
            );
        }

        $this->validarStatus(
            $dados['status_id']
        );
    }

    // ==================================================
    // VALIDAR STATUS
    // ==================================================

    private function validarStatus(
        int $statusId
    ): void {

        if ($statusId <= 0) {
            throw new RuntimeException(
                'Status do diário inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :id
              AND tipo =
                  'diario_contabilistico'
        ");

        $stmt->execute([
            ':id' =>
            $statusId
        ]);

        if (
            (int)$stmt->fetchColumn()
            !== 1
        ) {
            throw new RuntimeException(
                "Status inválido para 'diario_contabilistico'."
            );
        }
    }

    // ==================================================
    // STATUS PELO NOME
    // ==================================================

    private function statusEh(
        int $statusId,
        string $nome
    ): bool {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :id
              AND tipo =
                  'diario_contabilistico'

              AND LOWER(nome) =
                  LOWER(:nome)
        ");

        $stmt->execute([
            ':id' =>
            $statusId,

            ':nome' =>
            $nome
        ]);

        return (
            (int)$stmt->fetchColumn()
            === 1
        );
    }

    // ==================================================
    // VALIDAR CÓDIGO
    // ==================================================

    private function validarCodigoDuplicado(
        int $empresaId,
        string $codigo,
        ?int $ignorarId
    ): void {

        $sql = "
            SELECT COUNT(*)

            FROM diarios_contabilisticos

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
                AND id <> :id
            ";

            $params[':id'] =
                $ignorarId;
        }

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'Já existe um diário contabilístico com este código.'
            );
        }
    }

    // ==================================================
    // POSSUI LANÇAMENTOS
    // ==================================================

    private function possuiLancamentos(
        int $diarioId
    ): bool {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM lancamentos_contabilisticos

            WHERE diario_id =
                :diario_id
        ");

        $stmt->execute([
            ':diario_id' =>
            $diarioId
        ]);

        return (
            (int)$stmt->fetchColumn()
            > 0
        );
    }

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }
    }

    private function textoOuNull(
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
}
