<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class ExercicioContabilistico
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
    // LISTAR EXERCÍCIOS
    // ==================================================

    public function listar(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.id,
                exercicio.empresa_id,
                exercicio.codigo,
                exercicio.descricao,
                exercicio.data_inicio,
                exercicio.data_fim,
                exercicio.status_id,
                exercicio.encerrado_em,
                exercicio.encerrado_por,
                exercicio.criado_em,
                exercicio.atualizado_em,

                status.nome
                    AS status_nome,

                status.descricao
                    AS status_descricao,

                (
                    SELECT COUNT(*)

                    FROM periodos_contabilisticos periodo

                    WHERE periodo.exercicio_id =
                        exercicio.id
                ) AS quantidade_periodos

            FROM exercicios_contabilisticos exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

            WHERE exercicio.empresa_id =
                :empresa_id

            ORDER BY
                exercicio.data_inicio DESC,
                exercicio.id DESC
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
    // BUSCAR EXERCÍCIO
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
                'Exercício contabilístico inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.*,

                status.nome
                    AS status_nome,

                status.tipo
                    AS status_tipo

            FROM exercicios_contabilisticos exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

            WHERE exercicio.id =
                :id

              AND exercicio.empresa_id =
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
    // GUARDAR EXERCÍCIO
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

        $this->validarSobreposicao(
            $dados['empresa_id'],
            $dados['data_inicio'],
            $dados['data_fim'],
            $dados['id']
        );

        $novoExercicio =
            $dados['id'] === null;


        $exercicioAntes =
            null;


        if (!$novoExercicio) {

            $exercicioAntes =
                $this->buscar(
                    (int)$dados['id'],
                    (int)$dados['empresa_id']
                );


            if (!$exercicioAntes) {
                throw new RuntimeException(
                    'Exercício contabilístico não encontrado.'
                );
            }
        }

        $this->pdo->beginTransaction();

        try {
            if ($dados['id'] === null) {
                $statusAberto =
                    $this->statusId(
                        'exercicio_contabilistico',
                        'Aberto'
                    );

                $stmt = $this->pdo->prepare("
                    INSERT INTO exercicios_contabilisticos
                    (
                        empresa_id,
                        codigo,
                        descricao,
                        data_inicio,
                        data_fim,
                        status_id,
                        criado_por,
                        atualizado_por
                    )
                    VALUES
                    (
                        :empresa_id,
                        :codigo,
                        :descricao,
                        :data_inicio,
                        :data_fim,
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

                    ':descricao' =>
                    $dados['descricao'],

                    ':data_inicio' =>
                    $dados['data_inicio'],

                    ':data_fim' =>
                    $dados['data_fim'],

                    ':status_id' =>
                    $statusAberto,

                    ':criado_por' =>
                    $dados['usuario_id'],

                    ':atualizado_por' =>
                    $dados['usuario_id']
                ]);

                $exercicioId =
                    (int)$this->pdo
                        ->lastInsertId();
            } else {

                if (
                    $this->statusNome(
                        (int)$exercicioAntes['status_id']
                    ) === 'Encerrado'
                ) {
                    throw new RuntimeException(
                        'Um exercício encerrado não pode ser editado.'
                    );
                }

                $stmt = $this->pdo->prepare("
                    UPDATE exercicios_contabilisticos

                    SET
                        codigo =
                            :codigo,

                        descricao =
                            :descricao,

                        data_inicio =
                            :data_inicio,

                        data_fim =
                            :data_fim,

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

                    ':descricao' =>
                    $dados['descricao'],

                    ':data_inicio' =>
                    $dados['data_inicio'],

                    ':data_fim' =>
                    $dados['data_fim'],

                    ':atualizado_por' =>
                    $dados['usuario_id'],

                    ':id' =>
                    $dados['id'],

                    ':empresa_id' =>
                    $dados['empresa_id']
                ]);

                $exercicioId =
                    $dados['id'];
            }

            $exercicioDepois =
                $this->buscar(
                    $exercicioId,
                    (int)$dados['empresa_id']
                );


            if (!$exercicioDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o exercício contabilístico após a gravação.'
                );
            }


            if ($novoExercicio) {

                $this->auditoria->insercao(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'exercicios_contabilisticos',
                    $exercicioId,
                    $exercicioDepois
                );


                $this->log->registrar(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'EXERCICIO_CONTABILISTICO',
                    'CRIAR',
                    sprintf(
                        'Criou o exercício contabilístico %s - %s.',
                        $exercicioDepois['codigo'],
                        $exercicioDepois['descricao']
                    ),
                    $exercicioId
                );
            } else {

                $this->auditoria->atualizacao(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'exercicios_contabilisticos',
                    $exercicioId,
                    $exercicioAntes,
                    $exercicioDepois
                );


                $this->log->registrar(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'EXERCICIO_CONTABILISTICO',
                    'EDITAR',
                    sprintf(
                        'Atualizou o exercício contabilístico %s - %s.',
                        $exercicioDepois['codigo'],
                        $exercicioDepois['descricao']
                    ),
                    $exercicioId
                );
            }

            $this->pdo->commit();

            return $exercicioId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // GERAR 12 PERÍODOS
    // ==================================================

    public function gerarPeriodos(
        int $exercicioId,
        int $empresaId,
        int $usuarioId
    ): int {

        $exercicio =
            $this->buscar(
                $exercicioId,
                $empresaId
            );

        if (!$exercicio) {
            throw new RuntimeException(
                'Exercício contabilístico não encontrado.'
            );
        }

        $statusNome =
            $this->statusNome(
                (int)$exercicio['status_id']
            );

        if ($statusNome === 'Encerrado') {
            throw new RuntimeException(
                'Não é possível gerar períodos num exercício encerrado.'
            );
        }

        $statusAberto =
            $this->statusId(
                'periodo_contabilistico',
                'Aberto'
            );

        $inicio =
            new DateTime(
                $exercicio['data_inicio']
            );

        $fim =
            new DateTime(
                $exercicio['data_fim']
            );

        $this->pdo->beginTransaction();

        try {
            $quantidade = 0;

            $periodosCriados = [];

            $periodoAtual =
                clone $inicio;

            $numero = 1;

            while (
                $periodoAtual <= $fim
            ) {
                $inicioMes =
                    clone $periodoAtual;

                $fimMes =
                    clone $periodoAtual;

                $fimMes->modify(
                    'last day of this month'
                );

                if ($fimMes > $fim) {
                    $fimMes =
                        clone $fim;
                }

                $nomeMes =
                    $this->nomeMes(
                        (int)$inicioMes
                            ->format('n')
                    );

                $stmt = $this->pdo->prepare("
                    INSERT INTO periodos_contabilisticos
                    (
                        empresa_id,
                        exercicio_id,
                        numero,
                        nome,
                        data_inicio,
                        data_fim,
                        status_id
                    )
                    SELECT
                        :empresa_id,
                        :exercicio_id,
                        :numero,
                        :nome,
                        :data_inicio,
                        :data_fim,
                        :status_id

                    WHERE NOT EXISTS
                    (
                        SELECT 1

                        FROM periodos_contabilisticos

                        WHERE exercicio_id =
                            :exercicio_existente

                          AND numero =
                            :numero_existente
                    )
                ");

                $stmt->execute([
                    ':empresa_id' =>
                    $empresaId,

                    ':exercicio_id' =>
                    $exercicioId,

                    ':numero' =>
                    $numero,

                    ':nome' =>
                    $nomeMes,

                    ':data_inicio' =>
                    $inicioMes->format(
                        'Y-m-d'
                    ),

                    ':data_fim' =>
                    $fimMes->format(
                        'Y-m-d'
                    ),

                    ':status_id' =>
                    $statusAberto,

                    ':exercicio_existente' =>
                    $exercicioId,

                    ':numero_existente' =>
                    $numero
                ]);

                $inserido =
                    $stmt->rowCount() === 1;


                if ($inserido) {

                    $quantidade++;


                    $periodoId =
                        (int)$this->pdo
                            ->lastInsertId();


                    if ($periodoId > 0) {

                        $periodoCriado =
                            $this->buscarPeriodo(
                                $periodoId,
                                $empresaId
                            );


                        if ($periodoCriado) {

                            $this->auditoria->insercao(
                                $empresaId,
                                $usuarioId,
                                'periodos_contabilisticos',
                                $periodoId,
                                $periodoCriado
                            );


                            $periodosCriados[] =
                                $periodoId;
                        }
                    }
                }

                $periodoAtual->modify(
                    'first day of next month'
                );

                $numero++;
            }

            if ($quantidade > 0) {

                $this->log->registrar(
                    $empresaId,
                    $usuarioId,
                    'EXERCICIO_CONTABILISTICO',
                    'GERAR_PERIODOS',
                    sprintf(
                        'Gerou %d período(s) contabilístico(s) para o exercício %s - %s.',
                        $quantidade,
                        $exercicio['codigo'],
                        $exercicio['descricao']
                    ),
                    $exercicioId
                );
            }


            $this->pdo->commit();

            return $quantidade;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // LISTAR PERÍODOS
    // ==================================================

    public function listarPeriodos(
        int $exercicioId,
        int $empresaId
    ): array {

        $exercicio =
            $this->buscar(
                $exercicioId,
                $empresaId
            );

        if (!$exercicio) {
            throw new RuntimeException(
                'Exercício contabilístico não encontrado.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                periodo.*,

                status.nome
                    AS status_nome,

                status.descricao
                    AS status_descricao

            FROM periodos_contabilisticos periodo

            INNER JOIN status
                ON status.id =
                    periodo.status_id

            WHERE periodo.exercicio_id =
                :exercicio_id

              AND periodo.empresa_id =
                :empresa_id

            ORDER BY
                periodo.numero ASC
        ");

        $stmt->execute([
            ':exercicio_id' =>
            $exercicioId,

            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // ALTERAR STATUS DO EXERCÍCIO
    // ==================================================

    public function alterarStatus(
        int $id,
        int $empresaId,
        string $nomeStatus,
        int $usuarioId
    ): bool {

        $exercicio =
            $this->buscar(
                $id,
                $empresaId
            );

        if (!$exercicio) {
            throw new RuntimeException(
                'Exercício contabilístico não encontrado.'
            );
        }

        $statusAtual =
            $this->statusNome(
                (int)$exercicio['status_id']
            );

        $nomeStatus =
            ucfirst(
                strtolower(
                    trim($nomeStatus)
                )
            );

        if (
            !in_array(
                $nomeStatus,
                [
                    'Aberto',
                    'Bloqueado',
                    'Encerrado'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Status de exercício inválido.'
            );
        }

        if (
            $statusAtual === 'Encerrado'
            && $nomeStatus !== 'Encerrado'
        ) {
            throw new RuntimeException(
                'Um exercício encerrado não pode ser reaberto diretamente.'
            );
        }

        if ($nomeStatus === 'Encerrado') {
            $this->validarEncerramento(
                $id
            );
        }

        $statusId =
            $this->statusId(
                'exercicio_contabilistico',
                $nomeStatus
            );

        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
            UPDATE exercicios_contabilisticos

            SET
                status_id =
                    :status_id,

                encerrado_em =
                    CASE
                        WHEN :encerrar = 1
                        THEN NOW()
                        ELSE encerrado_em
                    END,

                encerrado_por =
                    CASE
                        WHEN :encerrar_usuario = 1
                        THEN :usuario_id
                        ELSE encerrado_por
                    END,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id
        ");


            $encerrar =
                $nomeStatus === 'Encerrado'
                ? 1
                : 0;


            $stmt->execute([
                ':status_id' =>
                $statusId,

                ':encerrar' =>
                $encerrar,

                ':encerrar_usuario' =>
                $encerrar,

                ':usuario_id' =>
                $usuarioId,

                ':atualizado_por' =>
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


            $exercicioDepois =
                $this->buscar(
                    $id,
                    $empresaId
                );


            if (!$exercicioDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o exercício após alterar o status.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'exercicios_contabilisticos',
                $id,
                $exercicio,
                $exercicioDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'EXERCICIO_CONTABILISTICO',
                'ALTERAR_STATUS',
                sprintf(
                    'Alterou o estado do exercício contabilístico %s - %s de %s para %s.',
                    $exercicioDepois['codigo'],
                    $exercicioDepois['descricao'],
                    $statusAtual,
                    $nomeStatus
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
    // ALTERAR STATUS DO PERÍODO
    // ==================================================

    public function alterarStatusPeriodo(
        int $id,
        int $empresaId,
        string $nomeStatus,
        int $usuarioId
    ): bool {

        $periodo =
            $this->buscarPeriodo(
                $id,
                $empresaId
            );

        if (!$periodo) {
            throw new RuntimeException(
                'Período contabilístico não encontrado.'
            );
        }

        $nomeStatus =
            ucfirst(
                strtolower(
                    trim($nomeStatus)
                )
            );

        if (
            !in_array(
                $nomeStatus,
                [
                    'Aberto',
                    'Bloqueado',
                    'Fechado'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Status de período inválido.'
            );
        }

        if (
            $this->statusNome(
                (int)$periodo['status_id']
            ) === 'Fechado'
            && $nomeStatus !== 'Fechado'
        ) {
            throw new RuntimeException(
                'Um período fechado não pode ser reaberto diretamente.'
            );
        }

        if ($nomeStatus === 'Fechado') {
            $this->validarPeriodoBalanceado(
                $id
            );
        }

        $statusId =
            $this->statusId(
                'periodo_contabilistico',
                $nomeStatus
            );

        $statusAnterior =
            $this->statusNome(
                (int)$periodo['status_id']
            );


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
            UPDATE periodos_contabilisticos

            SET
                status_id =
                    :status_id,

                fechado_em =
                    CASE
                        WHEN :fechar = 1
                        THEN NOW()
                        ELSE fechado_em
                    END,

                fechado_por =
                    CASE
                        WHEN :fechar_usuario = 1
                        THEN :usuario_id
                        ELSE fechado_por
                    END

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id
        ");


            $fechar =
                $nomeStatus === 'Fechado'
                ? 1
                : 0;


            $stmt->execute([
                ':status_id' =>
                $statusId,

                ':fechar' =>
                $fechar,

                ':fechar_usuario' =>
                $fechar,

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


            $periodoDepois =
                $this->buscarPeriodo(
                    $id,
                    $empresaId
                );


            if (!$periodoDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o período após alterar o status.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'periodos_contabilisticos',
                $id,
                $periodo,
                $periodoDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'EXERCICIO_CONTABILISTICO',
                'ALTERAR_STATUS_PERIODO',
                sprintf(
                    'Alterou o estado do período contabilístico %s de %s para %s.',
                    $periodoDepois['nome'],
                    $statusAnterior,
                    $nomeStatus
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
    // BUSCAR PERÍODO
    // ==================================================

    public function buscarPeriodo(
        int $id,
        int $empresaId
    ): ?array {

        $stmt = $this->pdo->prepare("
            SELECT
                periodo.*,

                status.nome
                    AS status_nome

            FROM periodos_contabilisticos periodo

            INNER JOIN status
                ON status.id =
                    periodo.status_id

            WHERE periodo.id =
                :id

              AND periodo.empresa_id =
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
    // ELIMINAR EXERCÍCIO
    // ==================================================

    public function eliminar(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        $exercicio =
            $this->buscar(
                $id,
                $empresaId
            );

        if (!$exercicio) {
            throw new RuntimeException(
                'Exercício contabilístico não encontrado.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM lancamentos_contabilisticos

            WHERE exercicio_id =
                :exercicio_id
        ");

        $stmt->execute([
            ':exercicio_id' =>
            $id
        ]);

        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'O exercício possui lançamentos contabilísticos e não pode ser eliminado.'
            );
        }

        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
            DELETE FROM exercicios_contabilisticos

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
                'exercicios_contabilisticos',
                $id,
                $exercicio
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'EXERCICIO_CONTABILISTICO',
                'ELIMINAR',
                sprintf(
                    'Eliminou o exercício contabilístico %s - %s.',
                    $exercicio['codigo'],
                    $exercicio['descricao']
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
    // VALIDAÇÕES
    // ==================================================

    private function validarEncerramento(
        int $exercicioId
    ): void {

        $statusFechado =
            $this->statusId(
                'periodo_contabilistico',
                'Fechado'
            );

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM periodos_contabilisticos

            WHERE exercicio_id =
                :exercicio_id

              AND status_id <>
                :status_fechado
        ");

        $stmt->execute([
            ':exercicio_id' =>
            $exercicioId,

            ':status_fechado' =>
            $statusFechado
        ]);

        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'Todos os períodos devem estar fechados antes de encerrar o exercício.'
            );
        }
    }

    private function validarPeriodoBalanceado(
        int $periodoId
    ): void {

        $statusRascunho =
            $this->statusId(
                'lancamento_contabilistico',
                'Rascunho'
            );

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM lancamentos_contabilisticos

            WHERE periodo_id =
                :periodo_id

              AND status_id =
                :status_rascunho
        ");

        $stmt->execute([
            ':periodo_id' =>
            $periodoId,

            ':status_rascunho' =>
            $statusRascunho
        ]);

        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'Existem lançamentos em rascunho neste período.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM lancamentos_contabilisticos

            WHERE periodo_id =
                :periodo_id

              AND ABS(
                    total_debito
                    - total_credito
                  ) > 0.009
        ");

        $stmt->execute([
            ':periodo_id' =>
            $periodoId
        ]);

        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'Existem lançamentos contabilísticos não balanceados neste período.'
            );
        }
    }

    private function validarSobreposicao(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
        ?int $ignorarId
    ): void {

        $sql = "
            SELECT COUNT(*)

            FROM exercicios_contabilisticos

            WHERE empresa_id =
                :empresa_id

              AND data_inicio <=
                :data_fim

              AND data_fim >=
                :data_inicio
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_fim' =>
            $dataFim,

            ':data_inicio' =>
            $dataInicio
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
                'Já existe um exercício contabilístico que ocupa parte deste período.'
            );
        }
    }

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
            trim(
                (string)(
                    $dados['codigo']
                    ?? ''
                )
            ),

            'descricao' =>
            trim(
                (string)(
                    $dados['descricao']
                    ?? ''
                )
            ),

            'data_inicio' =>
            trim(
                (string)(
                    $dados['data_inicio']
                    ?? ''
                )
            ),

            'data_fim' =>
            trim(
                (string)(
                    $dados['data_fim']
                    ?? ''
                )
            )
        ];
    }

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
                'Informe o código do exercício.'
            );
        }

        if ($dados['descricao'] === '') {
            throw new RuntimeException(
                'Informe a descrição do exercício.'
            );
        }

        $this->validarData(
            $dados['data_inicio'],
            'Data inicial'
        );

        $this->validarData(
            $dados['data_fim'],
            'Data final'
        );

        if (
            $dados['data_fim']
            < $dados['data_inicio']
        ) {
            throw new RuntimeException(
                'A data final não pode ser anterior à data inicial.'
            );
        }
    }

    private function statusId(
        string $tipo,
        string $nome
    ): int {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                :tipo

              AND LOWER(nome) =
                LOWER(:nome)

            LIMIT 1
        ");

        $stmt->execute([
            ':tipo' =>
            $tipo,

            ':nome' =>
            $nome
        ]);

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                "Status '{$nome}' do tipo '{$tipo}' não encontrado."
            );
        }

        return $id;
    }

    private function statusNome(
        int $statusId
    ): string {

        $stmt = $this->pdo->prepare("
            SELECT nome

            FROM status

            WHERE id =
                :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' =>
            $statusId
        ]);

        return (string)$stmt
            ->fetchColumn();
    }

    private function nomeMes(
        int $mes
    ): string {

        $meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];

        return $meses[$mes]
            ?? 'Período';
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

    private function validarData(
        string $data,
        string $campo
    ): void {

        $objeto =
            DateTime::createFromFormat(
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

    // ==================================================
    // LISTAR STATUS
    // ==================================================

    public function listarStatus(
        string $tipo
    ): array {

        $tipo = trim($tipo);

        if ($tipo === '') {
            throw new RuntimeException(
                'Tipo de status inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
        SELECT
            id,
            tipo,
            nome,
            descricao

        FROM status

        WHERE tipo = :tipo

        ORDER BY id ASC
    ");

        $stmt->execute([
            ':tipo' => $tipo
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}
