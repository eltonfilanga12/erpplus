<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

final class PlanoConta
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
            new AuditoriaService($pdo);

        $this->log =
            new LogService($pdo);
    }

    // ==================================================
    // LISTAR PLANOS
    // ==================================================

    public function listarPlanos(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt = $this->pdo->prepare("
        SELECT
            plano.id,
            plano.empresa_id,
            plano.codigo,
            plano.nome,
            plano.descricao,
            plano.versao,
            plano.data_inicio_vigencia,
            plano.data_fim_vigencia,
            plano.padrao,
            plano.status_id,
            plano.criado_em,
            plano.atualizado_em,

            st.nome AS status_nome,
            st.descricao AS status_descricao,

            COUNT(conta.id)
                AS quantidade_contas

        FROM planos_contas plano

        INNER JOIN status st
            ON st.id = plano.status_id
           AND st.tipo = 'plano_contas'

        LEFT JOIN contas_contabilisticas conta
            ON conta.plano_contas_id =
                plano.id

        WHERE plano.empresa_id =
            :empresa_id

        GROUP BY
            plano.id,
            plano.empresa_id,
            plano.codigo,
            plano.nome,
            plano.descricao,
            plano.versao,
            plano.data_inicio_vigencia,
            plano.data_fim_vigencia,
            plano.padrao,
            plano.status_id,
            plano.criado_em,
            plano.atualizado_em,
            st.nome,
            st.descricao

        ORDER BY
            plano.padrao DESC,
            plano.codigo ASC
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

    // ==================================================
    // BUSCAR PLANO
    // ==================================================

    public function buscarPlano(
        int $id,
        int $empresaId
    ): ?array {

        $this->validarEmpresa(
            $empresaId
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Plano de contas inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
        SELECT
            plano.*,

            st.nome AS status_nome,
            st.descricao AS status_descricao

        FROM planos_contas plano

        INNER JOIN status st
            ON st.id = plano.status_id
           AND st.tipo = 'plano_contas'

        WHERE plano.id = :id
          AND plano.empresa_id = :empresa_id

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
    // GUARDAR PLANO
    // ==================================================

    public function guardarPlano(
        array $dados
    ): int {

        $dados =
            $this->normalizarPlano(
                $dados
            );

        $this->validarDadosPlano(
            $dados
        );

        $planoAntes = null;

        $novoPlano =
            $dados['id'] === null;


        if (!$novoPlano) {

            $planoAntes =
                $this->buscarPlano(
                    (int)$dados['id'],
                    (int)$dados['empresa_id']
                );


            if (!$planoAntes) {
                throw new RuntimeException(
                    'Plano de contas não encontrado.'
                );
            }
        }


        $this->pdo->beginTransaction();


        try {
            if (
                $dados['padrao'] === 1
            ) {
                $this->removerPlanoPadrao(
                    $dados['empresa_id'],
                    $dados['id']
                );
            }

            if ($dados['id'] === null) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO planos_contas
                    (
                        empresa_id,
                        codigo,
                        nome,
                        descricao,
                        versao,
                        data_inicio_vigencia,
                        data_fim_vigencia,
                        padrao,
                        status_id,
                        criado_por,
                        atualizado_por
                    )
                    VALUES
                    (
                        :empresa_id,
                        :codigo,
                        :nome,
                        :descricao,
                        :versao,
                        :data_inicio_vigencia,
                        :data_fim_vigencia,
                        :padrao,
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

                    ':descricao' =>
                    $dados['descricao'],

                    ':versao' =>
                    $dados['versao'],

                    ':data_inicio_vigencia' =>
                    $dados['data_inicio_vigencia'],

                    ':data_fim_vigencia' =>
                    $dados['data_fim_vigencia'],

                    ':padrao' =>
                    $dados['padrao'],

                    ':status_id' =>
                    $dados['status_id'],

                    ':criado_por' =>
                    $dados['usuario_id'],

                    ':atualizado_por' =>
                    $dados['usuario_id']
                ]);

                $planoId =
                    (int)$this->pdo
                        ->lastInsertId();
            } else {
                $planoAtual =
                    $this->buscarPlano(
                        $dados['id'],
                        $dados['empresa_id']
                    );

                if (!$planoAtual) {
                    throw new RuntimeException(
                        'Plano de contas não encontrado.'
                    );
                }

                $stmt = $this->pdo->prepare("
                    UPDATE planos_contas
                    SET
                        codigo =
                            :codigo,

                        nome =
                            :nome,

                        descricao =
                            :descricao,

                        versao =
                            :versao,

                        data_inicio_vigencia =
                            :data_inicio_vigencia,

                        data_fim_vigencia =
                            :data_fim_vigencia,

                        padrao =
                            :padrao,

                        status_id = :status_id,

                        atualizado_por =
                            :atualizado_por

                    WHERE id = :id
                      AND empresa_id =
                          :empresa_id
                ");

                $stmt->execute([
                    ':codigo' =>
                    $dados['codigo'],

                    ':nome' =>
                    $dados['nome'],

                    ':descricao' =>
                    $dados['descricao'],

                    ':versao' =>
                    $dados['versao'],

                    ':data_inicio_vigencia' =>
                    $dados['data_inicio_vigencia'],

                    ':data_fim_vigencia' =>
                    $dados['data_fim_vigencia'],

                    ':padrao' =>
                    $dados['padrao'],

                    ':status_id' =>
                    $dados['status_id'],

                    ':atualizado_por' =>
                    $dados['usuario_id'],

                    ':id' =>
                    $dados['id'],

                    ':empresa_id' =>
                    $dados['empresa_id']
                ]);

                $planoId =
                    $dados['id'];
            }

            $planoDepois =
                $this->buscarPlano(
                    $planoId,
                    (int)$dados['empresa_id']
                );

            if (!$planoDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o plano de contas após a gravação.'
                );
            }

            if ($novoPlano) {

                $this->auditoria->insercao(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'planos_contas',
                    $planoId,
                    $planoDepois
                );

                $this->log->registrar(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'PLANO_CONTAS',
                    'CRIAR',
                    sprintf(
                        'Criou o plano de contas %s - %s.',
                        $planoDepois['codigo'],
                        $planoDepois['nome']
                    ),
                    $planoId
                );
            } else {

                $this->auditoria->atualizacao(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'planos_contas',
                    $planoId,
                    $planoAntes,
                    $planoDepois
                );

                $this->log->registrar(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'PLANO_CONTAS',
                    'EDITAR',
                    sprintf(
                        'Atualizou o plano de contas %s - %s.',
                        $planoDepois['codigo'],
                        $planoDepois['nome']
                    ),
                    $planoId
                );
            }

            $this->pdo->commit();

            return $planoId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // ALTERAR STATUS DO PLANO
    // ==================================================

    public function alterarStatusPlano(
        int $id,
        int $empresaId,
        int $statusId,
        int $usuarioId
    ): bool {

        $plano =
            $this->buscarPlano(
                $id,
                $empresaId
            );

        if (!$plano) {
            throw new RuntimeException(
                'Plano de contas não encontrado.'
            );
        }

        $this->validarStatus(
            $statusId,
            'plano_contas'
        );

        if (
            (int)$plano['padrao'] === 1
            && $this->statusEh(
                $statusId,
                'plano_contas',
                'Inactivo'
            )
        ) {
            throw new RuntimeException(
                'O plano padrão não pode ser inativado.'
            );
        }

        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
            UPDATE planos_contas

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


            $alterado =
                $stmt->rowCount() === 1;


            if (!$alterado) {

                $this->pdo->rollBack();

                return false;
            }


            $planoDepois =
                $this->buscarPlano(
                    $id,
                    $empresaId
                );


            if (!$planoDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar o plano após alterar o status.'
                );
            }


            $this->auditoria->atualizacao(
                $empresaId,
                $usuarioId,
                'planos_contas',
                $id,
                $plano,
                $planoDepois
            );


            $this->log->registrar(
                $empresaId,
                $usuarioId,
                'PLANO_CONTAS',
                'ALTERAR_STATUS',
                sprintf(
                    'Alterou o status do plano de contas %s - %s para %s.',
                    $planoDepois['codigo'],
                    $planoDepois['nome'],
                    $planoDepois['status_nome']
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
    // ELIMINAR PLANO
    // ==================================================

    public function eliminarPlano(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        $plano =
            $this->buscarPlano(
                $id,
                $empresaId
            );


        if (!$plano) {
            throw new RuntimeException(
                'Plano de contas não encontrado.'
            );
        }


        if (
            (int)$plano['padrao'] === 1
        ) {
            throw new RuntimeException(
                'O plano padrão não pode ser eliminado.'
            );
        }


        $stmt =
            $this->pdo->prepare("
            SELECT COUNT(*)

            FROM contas_contabilisticas

            WHERE plano_contas_id =
                :id
        ");


        $stmt->execute([
            ':id' =>
            $id
        ]);


        if (
            (int)$stmt->fetchColumn()
            > 0
        ) {
            throw new RuntimeException(
                'Não é possível eliminar um plano que já possui contas.'
            );
        }


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                DELETE FROM planos_contas

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


            $this->auditoria
                ->exclusao(
                    $empresaId,
                    $usuarioId,
                    'planos_contas',
                    $id,
                    $plano
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'PLANO_CONTAS',
                    'ELIMINAR',
                    'Eliminou o plano de contas '
                        . $plano['codigo']
                        . ' - '
                        . $plano['nome']
                        . '.',
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
    // LISTAR CONTAS
    // ==================================================

    public function listarContas(
        int $planoId,
        int $empresaId
    ): array {

        $this->validarPlanoEmpresa(
            $planoId,
            $empresaId
        );

        $stmt = $this->pdo->prepare("
        SELECT
            conta.id,
            conta.empresa_id,
            conta.plano_contas_id,
            conta.conta_pai_id,
            conta.codigo,
            conta.nome,
            conta.descricao,
            conta.nivel,
            conta.classe,
            conta.tipo,
            conta.natureza,
            conta.grupo,
            conta.aceita_lancamento,
            conta.exige_centro_custo,
            conta.exige_entidade,
            conta.conta_caixa,
            conta.conta_banco,
            conta.conta_cliente,
            conta.conta_fornecedor,
            conta.conta_imposto,
            conta.integracao_codigo,
            conta.fluxo_caixa_categoria,
            conta.fluxo_caixa_descricao,
            conta.status_id,

            st.nome AS status_nome,
            st.descricao AS status_descricao,

            pai.codigo AS conta_pai_codigo,
            pai.nome AS conta_pai_nome,

            (
                SELECT COUNT(*)

                FROM contas_contabilisticas filha

                WHERE filha.conta_pai_id =
                    conta.id
            ) AS quantidade_filhas

        FROM contas_contabilisticas conta

        INNER JOIN status st
            ON st.id = conta.status_id
           AND st.tipo = 'conta_contabilistica'

        LEFT JOIN contas_contabilisticas pai
            ON pai.id =
                conta.conta_pai_id

        WHERE conta.plano_contas_id =
            :plano_id

          AND conta.empresa_id =
            :empresa_id

        ORDER BY conta.codigo ASC
    ");

        $stmt->execute([
            ':plano_id' =>
            $planoId,

            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
    // ==================================================
    // LISTAR CONTAS EM ÁRVORE
    // ==================================================

    public function listarArvore(
        int $planoId,
        int $empresaId
    ): array {

        $contas =
            $this->listarContas(
                $planoId,
                $empresaId
            );

        $mapa = [];

        foreach ($contas as $conta) {
            $conta['filhas'] = [];

            $mapa[(int)$conta['id']] = $conta;
        }

        $arvore = [];

        foreach ($mapa as $id => &$conta) {
            $paiId =
                !empty($conta['conta_pai_id'])
                ? (int)$conta['conta_pai_id']
                : null;

            if (
                $paiId !== null
                && isset($mapa[$paiId])
            ) {
                $mapa[$paiId]['filhas'][] =
                    &$conta;
            } else {
                $arvore[] =
                    &$conta;
            }
        }

        unset($conta);

        return $arvore;
    }

    // ==================================================
    // BUSCAR CONTA
    // ==================================================

    public function buscarConta(
        int $id,
        int $empresaId
    ): ?array {

        $this->validarEmpresa(
            $empresaId
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Conta contabilística inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
        SELECT
            conta.*,

            st.nome AS status_nome,
            st.descricao AS status_descricao,

            pai.codigo AS conta_pai_codigo,
            pai.nome AS conta_pai_nome

        FROM contas_contabilisticas conta

        INNER JOIN status st
            ON st.id = conta.status_id
           AND st.tipo = 'conta_contabilistica'

        LEFT JOIN contas_contabilisticas pai
            ON pai.id =
                conta.conta_pai_id

        WHERE conta.id = :id
          AND conta.empresa_id =
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
    // GUARDAR CONTA
    // ==================================================

    public function guardarConta(
        array $dados
    ): int {

        $dados =
            $this->normalizarConta(
                $dados
            );

        $this->validarDadosConta(
            $dados
        );

        $this->validarIntegracaoUnica(
            $dados['empresa_id'],
            $dados['id'],
            $dados['integracao_codigo']
        );

        $this->pdo->beginTransaction();

        try {
            $this->validarPlanoEmpresa(
                $dados['plano_contas_id'],
                $dados['empresa_id']
            );

            $contaPai = null;

            if (
                $dados['conta_pai_id']
                !== null
            ) {

                $contaPai =
                    $this->buscarConta(
                        $dados['conta_pai_id'],
                        $dados['empresa_id']
                    );


                if (
                    !$contaPai
                    || (int)$contaPai['plano_contas_id']
                    !== $dados['plano_contas_id']
                ) {
                    throw new RuntimeException(
                        'A conta superior não pertence ao plano selecionado.'
                    );
                }


                if (
                    $contaPai['tipo']
                    !== 'SINTETICA'
                ) {
                    throw new RuntimeException(
                        'Uma conta analítica não pode receber contas filhas.'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Validar integração contabilística
            |--------------------------------------------------------------------------
            */

            if (
                $dados['integracao_codigo'] !== null
                && $dados['tipo'] !== 'ANALITICA'
            ) {
                throw new RuntimeException(
                    'A integração automática só pode ser atribuída a uma conta analítica.'
                );
            }


            if (
                $dados['integracao_codigo'] !== null
                && $dados['aceita_lancamento'] !== 1
            ) {
                throw new RuntimeException(
                    'A conta de integração deve aceitar lançamentos.'
                );
            }

            $dados['nivel'] =
                $contaPai
                ? (int)$contaPai['nivel'] + 1
                : 1;

            $dados['classe'] =
                $this->obterClasse(
                    $dados['codigo']
                );

            if (
                $dados['tipo']
                === 'SINTETICA'
            ) {
                $dados['aceita_lancamento'] =
                    0;
            }

            $contaAntes = null;
            $novaConta = $dados['id'] === null;

            if (!$novaConta) {
                $contaAntes =
                    $this->buscarConta(
                        (int)$dados['id'],
                        (int)$dados['empresa_id']
                    );

                if (!$contaAntes) {
                    throw new RuntimeException(
                        'Conta contabilística não encontrada.'
                    );
                }
            }

            if ($dados['id'] === null) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO contas_contabilisticas
                    (
                        empresa_id,
                        plano_contas_id,
                        conta_pai_id,
                        codigo,
                        nome,
                        descricao,
                        nivel,
                        classe,
                        tipo,
                        natureza,
                        grupo,
                        aceita_lancamento,
                        exige_centro_custo,
                        exige_entidade,
                        conta_caixa,
                        conta_banco,
                        conta_cliente,
                        conta_fornecedor,
                        conta_imposto,
                        integracao_codigo,
                        fluxo_caixa_categoria,
                        fluxo_caixa_descricao,
                        status_id,
                        criado_por,
                        atualizado_por
                    )
                    VALUES
                    (
                        :empresa_id,
                        :plano_contas_id,
                        :conta_pai_id,
                        :codigo,
                        :nome,
                        :descricao,
                        :nivel,
                        :classe,
                        :tipo,
                        :natureza,
                        :grupo,
                        :aceita_lancamento,
                        :exige_centro_custo,
                        :exige_entidade,
                        :conta_caixa,
                        :conta_banco,
                        :conta_cliente,
                        :conta_fornecedor,
                        :conta_imposto,
                        :integracao_codigo,
                        :fluxo_caixa_categoria,
                        :fluxo_caixa_descricao,
                        :status_id,
                        :criado_por,
                        :atualizado_por
                    )
                ");

                $stmt->execute(
                    $this->parametrosConta(
                        $dados
                    )
                );

                $contaId =
                    (int)$this->pdo
                        ->lastInsertId();
            } else {
                $contaAtual =
                    $this->buscarConta(
                        $dados['id'],
                        $dados['empresa_id']
                    );

                if (!$contaAtual) {
                    throw new RuntimeException(
                        'Conta contabilística não encontrada.'
                    );
                }

                if (
                    $dados['conta_pai_id']
                    === $dados['id']
                ) {
                    throw new RuntimeException(
                        'A conta não pode ser superior de si própria.'
                    );
                }

                $this->validarCicloHierarquia(
                    $dados['id'],
                    $dados['conta_pai_id']
                );

                $this->validarAlteracaoTipo(
                    $dados['id'],
                    $dados['tipo']
                );

                $stmt = $this->pdo->prepare("
                    UPDATE contas_contabilisticas
                    SET
                        plano_contas_id =
                            :plano_contas_id,

                        conta_pai_id =
                            :conta_pai_id,

                        codigo =
                            :codigo,

                        nome =
                            :nome,

                        descricao =
                            :descricao,

                        nivel =
                            :nivel,

                        classe =
                            :classe,

                        tipo =
                            :tipo,

                        natureza =
                            :natureza,

                        grupo =
                            :grupo,

                        aceita_lancamento =
                            :aceita_lancamento,

                        exige_centro_custo =
                            :exige_centro_custo,

                        exige_entidade =
                            :exige_entidade,

                        conta_caixa =
                            :conta_caixa,

                        conta_banco =
                            :conta_banco,

                        conta_cliente =
                            :conta_cliente,

                        conta_fornecedor =
                            :conta_fornecedor,

                        conta_imposto =
                            :conta_imposto,

                        integracao_codigo =
                            :integracao_codigo,

                        fluxo_caixa_categoria =
                            :fluxo_caixa_categoria,

                        fluxo_caixa_descricao =
                            :fluxo_caixa_descricao,

                        status_id = :status_id,

                        atualizado_por =
                            :atualizado_por

                    WHERE id = :id
                      AND empresa_id =
                          :empresa_id
                ");

                $params =
                    $this->parametrosConta(
                        $dados
                    );

                $params[':id'] =
                    $dados['id'];

                unset(
                    $params[':criado_por']
                );

                $stmt->execute(
                    $params
                );

                $contaId =
                    $dados['id'];
            }

            $contaDepois =
                $this->buscarConta(
                    $contaId,
                    (int)$dados['empresa_id']
                );

            if (!$contaDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar a conta contabilística após a gravação.'
                );
            }

            if ($novaConta) {

                $this->auditoria->insercao(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'contas_contabilisticas',
                    $contaId,
                    $contaDepois
                );

                $this->log->registrar(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'PLANO_CONTAS',
                    'CRIAR_CONTA',
                    sprintf(
                        'Criou a conta contabilística %s - %s.',
                        $contaDepois['codigo'],
                        $contaDepois['nome']
                    ),
                    $contaId
                );
            } else {

                $this->auditoria->atualizacao(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'contas_contabilisticas',
                    $contaId,
                    $contaAntes,
                    $contaDepois
                );

                $this->log->registrar(
                    (int)$dados['empresa_id'],
                    (int)$dados['usuario_id'],
                    'PLANO_CONTAS',
                    'EDITAR_CONTA',
                    sprintf(
                        'Atualizou a conta contabilística %s - %s.',
                        $contaDepois['codigo'],
                        $contaDepois['nome']
                    ),
                    $contaId
                );
            }

            $this->pdo->commit();

            return $contaId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function validarIntegracaoUnica(
        int $empresaId,
        ?int $contaId,
        ?string $integracaoCodigo
    ): void {

        if ($integracaoCodigo === null) {
            return;
        }

        $sql = "
        SELECT id

        FROM contas_contabilisticas

        WHERE empresa_id =
            :empresa_id

          AND integracao_codigo =
            :integracao_codigo
    ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':integracao_codigo' =>
            $integracaoCodigo
        ];

        if ($contaId !== null) {
            $sql .= "
            AND id <> :id
        ";

            $params[':id'] =
                $contaId;
        }

        $sql .= "
        LIMIT 1
    ";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        if ($stmt->fetchColumn()) {
            throw new RuntimeException(
                "Já existe uma conta configurada para a integração '{$integracaoCodigo}'."
            );
        }
    }

    // ==================================================
    // ALTERAR STATUS DA CONTA
    // ==================================================

    public function alterarStatusConta(
        int $id,
        int $empresaId,
        int $statusId,
        int $usuarioId
    ): bool {

        $contaAntes =
            $this->buscarConta(
                $id,
                $empresaId
            );


        if (!$contaAntes) {
            throw new RuntimeException(
                'Conta contabilística não encontrada.'
            );
        }


        $this->validarStatus(
            $statusId,
            'conta_contabilistica'
        );


        $statusInativo =
            $this->statusEh(
                $statusId,
                'conta_contabilistica',
                'Inactiva'
            );


        $statusBloqueado =
            $this->statusEh(
                $statusId,
                'conta_contabilistica',
                'Bloqueada'
            );


        if (
            (
                $statusInativo
                || $statusBloqueado
            )
            && $this->contaPossuiMovimentos(
                $id
            )
        ) {
            throw new RuntimeException(
                'A conta possui movimentos. Utilize o bloqueio apenas depois de confirmar que não existem operações pendentes.'
            );
        }


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                UPDATE contas_contabilisticas

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
                $stmt->rowCount()
                !== 1
            ) {
                $this->pdo->rollBack();

                return false;
            }


            $contaDepois =
                $this->buscarConta(
                    $id,
                    $empresaId
                );


            if (!$contaDepois) {
                throw new RuntimeException(
                    'Não foi possível carregar a conta após alterar o status.'
                );
            }


            $this->auditoria
                ->atualizacao(
                    $empresaId,
                    $usuarioId,
                    'contas_contabilisticas',
                    $id,
                    $contaAntes,
                    $contaDepois
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'PLANO_CONTAS',
                    'ALTERAR_STATUS_CONTA',
                    'Alterou o estado da conta contabilística '
                        . $contaDepois['codigo']
                        . ' - '
                        . $contaDepois['nome']
                        . ' para '
                        . $contaDepois['status_nome']
                        . '.',
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
    // ELIMINAR CONTA
    // ==================================================

    public function eliminarConta(
        int $id,
        int $empresaId,
        int $usuarioId
    ): bool {

        $conta =
            $this->buscarConta(
                $id,
                $empresaId
            );


        if (!$conta) {
            throw new RuntimeException(
                'Conta contabilística não encontrada.'
            );
        }


        if (
            $this->contaPossuiFilhas(
                $id
            )
        ) {
            throw new RuntimeException(
                'A conta possui contas filhas e não pode ser eliminada.'
            );
        }


        if (
            $this->contaPossuiMovimentos(
                $id
            )
        ) {
            throw new RuntimeException(
                'A conta possui movimentos contabilísticos e não pode ser eliminada.'
            );
        }


        $this->pdo->beginTransaction();


        try {

            $stmt =
                $this->pdo->prepare("
                DELETE FROM contas_contabilisticas

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
                $stmt->rowCount()
                !== 1
            ) {
                $this->pdo->rollBack();

                return false;
            }


            $this->auditoria
                ->exclusao(
                    $empresaId,
                    $usuarioId,
                    'contas_contabilisticas',
                    $id,
                    $conta
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'PLANO_CONTAS',
                    'ELIMINAR_CONTA',
                    'Eliminou a conta contabilística '
                        . $conta['codigo']
                        . ' - '
                        . $conta['nome']
                        . '.',
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
    // CONTAS PARA SELECT
    // ==================================================

    public function contasParaSelect(
        int $planoId,
        int $empresaId,
        bool $somenteAnaliticas = false
    ): array {

        $this->validarPlanoEmpresa(
            $planoId,
            $empresaId
        );

        $sql = "
        SELECT
            conta.id,
            conta.codigo,
            conta.nome,
            conta.nivel,
            conta.tipo,
            conta.grupo,
            conta.natureza,
            conta.status_id,
            st.nome AS status_nome

        FROM contas_contabilisticas conta

        INNER JOIN status st
            ON st.id = conta.status_id
           AND st.tipo =
               'conta_contabilistica'

        WHERE conta.plano_contas_id =
            :plano_id

          AND conta.empresa_id =
            :empresa_id

          AND LOWER(st.nome) =
              LOWER('Activa')
    ";

        if ($somenteAnaliticas) {
            $sql .= "
            AND conta.tipo =
                'ANALITICA'

            AND conta.aceita_lancamento = 1
        ";
        }

        $sql .= "
        ORDER BY conta.codigo ASC
    ";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute([
            ':plano_id' =>
            $planoId,

            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // NORMALIZAR PLANO
    // ==================================================

    private function normalizarPlano(
        array $dados
    ): array {

        return [
            'id' =>
            !empty($dados['id'])
                ? (int)$dados['id']
                : null,

            'empresa_id' =>
            (int)($dados['empresa_id'] ?? 0),

            'usuario_id' =>
            (int)($dados['usuario_id'] ?? 0),

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

            'descricao' =>
            $this->textoOuNull(
                $dados['descricao']
                    ?? null
            ),

            'versao' =>
            $this->textoOuNull(
                $dados['versao']
                    ?? null
            ),

            'data_inicio_vigencia' =>
            trim(
                (string)(
                    $dados['data_inicio_vigencia']
                    ?? ''
                )
            ),

            'data_fim_vigencia' =>
            $this->textoOuNull(
                $dados['data_fim_vigencia']
                    ?? null
            ),

            'padrao' =>
            !empty($dados['padrao'])
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
    // NORMALIZAR CONTA
    // ==================================================

    private function normalizarConta(
        array $dados
    ): array {

        return [
            'id' =>
            !empty($dados['id'])
                ? (int)$dados['id']
                : null,

            'empresa_id' =>
            (int)($dados['empresa_id'] ?? 0),

            'usuario_id' =>
            (int)($dados['usuario_id'] ?? 0),

            'plano_contas_id' =>
            (int)(
                $dados['plano_contas_id']
                ?? 0
            ),

            'conta_pai_id' =>
            !empty($dados['conta_pai_id'])
                ? (int)$dados['conta_pai_id']
                : null,

            'codigo' =>
            trim(
                (string)(
                    $dados['codigo']
                    ?? ''
                )
            ),

            'nome' =>
            trim(
                (string)(
                    $dados['nome']
                    ?? ''
                )
            ),

            'descricao' =>
            $this->textoOuNull(
                $dados['descricao']
                    ?? null
            ),

            'nivel' =>
            1,

            'classe' =>
            null,

            'tipo' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo']
                        ?? 'ANALITICA'
                    )
                )
            ),

            'natureza' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['natureza']
                        ?? ''
                    )
                )
            ),

            'grupo' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['grupo']
                        ?? ''
                    )
                )
            ),

            'aceita_lancamento' =>
            !empty($dados['aceita_lancamento'])
                ? 1
                : 0,

            'exige_centro_custo' =>
            !empty($dados['exige_centro_custo'])
                ? 1
                : 0,

            'exige_entidade' =>
            !empty($dados['exige_entidade'])
                ? 1
                : 0,

            'conta_caixa' =>
            !empty($dados['conta_caixa'])
                ? 1
                : 0,

            'conta_banco' =>
            !empty($dados['conta_banco'])
                ? 1
                : 0,

            'conta_cliente' =>
            !empty($dados['conta_cliente'])
                ? 1
                : 0,

            'conta_fornecedor' =>
            !empty($dados['conta_fornecedor'])
                ? 1
                : 0,

            'conta_imposto' =>
            !empty($dados['conta_imposto'])
                ? 1
                : 0,

            'integracao_codigo' =>
            $this->normalizarIntegracaoCodigo(
                $dados['integracao_codigo']
                    ?? null
            ),

            'fluxo_caixa_categoria' =>
            $this->normalizarFluxoCaixaCategoria(
                $dados['fluxo_caixa_categoria']
                    ?? 'NENHUM'
            ),

            'fluxo_caixa_descricao' =>
            $this->textoOuNull(
                $dados['fluxo_caixa_descricao']
                    ?? null
            ),

            'status_id' =>
            (int)(
                $dados['status_id']
                ?? 0
            )
        ];
    }

    private function normalizarFluxoCaixaCategoria(
        mixed $valor
    ): string {

        $categoria =
            strtoupper(
                trim(
                    (string)(
                        $valor
                        ?? 'NENHUM'
                    )
                )
            );

        if ($categoria === '') {
            $categoria = 'NENHUM';
        }

        $permitidas = [
            'NENHUM',
            'OPERACIONAL',
            'INVESTIMENTO',
            'FINANCIAMENTO'
        ];

        if (
            !in_array(
                $categoria,
                $permitidas,
                true
            )
        ) {
            throw new RuntimeException(
                'Categoria da Demonstração de Fluxos de Caixa inválida.'
            );
        }

        return $categoria;
    }


    private function normalizarIntegracaoCodigo(
        mixed $valor
    ): ?string {

        $codigo =
            strtoupper(
                trim(
                    (string)($valor ?? '')
                )
            );

        if ($codigo === '') {
            return null;
        }

        $permitidos = [
            'CAIXA',
            'BANCOS',
            'CLIENTES',
            'FORNECEDORES',
            'VENDAS',
            'COMPRAS',
            'STOCK',
            'CMV',
            'IVA_LIQUIDADO',
            'IVA_DEDUTIVEL'
        ];

        if (
            !in_array(
                $codigo,
                $permitidos,
                true
            )
        ) {
            throw new RuntimeException(
                'Código de integração contabilística inválido.'
            );
        }

        return $codigo;
    }

    // ==================================================
    // VALIDAR PLANO
    // ==================================================

    private function validarDadosPlano(
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
                'Informe o código do plano.'
            );
        }

        if ($dados['nome'] === '') {
            throw new RuntimeException(
                'Informe o nome do plano.'
            );
        }

        $this->validarStatus(
            $dados['status_id'],
            'plano_contas'
        );

        $this->validarData(
            $dados['data_inicio_vigencia'],
            'Data de início da vigência'
        );

        if (
            $dados['data_fim_vigencia']
            !== null
        ) {
            $this->validarData(
                $dados['data_fim_vigencia'],
                'Data de fim da vigência'
            );

            if (
                $dados['data_fim_vigencia']
                < $dados['data_inicio_vigencia']
            ) {
                throw new RuntimeException(
                    'A data final da vigência não pode ser anterior à data inicial.'
                );
            }
        }
    }

    // ==================================================
    // VALIDAR CONTA
    // ==================================================

    private function validarDadosConta(
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

        if (
            $dados['plano_contas_id']
            <= 0
        ) {
            throw new RuntimeException(
                'Selecione o plano de contas.'
            );
        }

        if ($dados['codigo'] === '') {
            throw new RuntimeException(
                'Informe o código da conta.'
            );
        }

        if ($dados['nome'] === '') {
            throw new RuntimeException(
                'Informe o nome da conta.'
            );
        }

        if (
            !preg_match(
                '/^[0-9A-Za-z.\-_\/]+$/',
                $dados['codigo']
            )
        ) {
            throw new RuntimeException(
                'O código da conta contém caracteres inválidos.'
            );
        }

        if (
            !in_array(
                $dados['tipo'],
                [
                    'SINTETICA',
                    'ANALITICA'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Tipo de conta inválido.'
            );
        }

        if (
            !in_array(
                $dados['natureza'],
                [
                    'DEVEDORA',
                    'CREDORA'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Natureza da conta inválida.'
            );
        }

        if (
            !in_array(
                $dados['grupo'],
                [
                    'ATIVO',
                    'PASSIVO',
                    'CAPITAL_PROPRIO',
                    'RENDIMENTO',
                    'GASTO',
                    'RESULTADO',
                    'EXTRAPATRIMONIAL'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Grupo contabilístico inválido.'
            );
        }

        if (
            !in_array(
                $dados['fluxo_caixa_categoria'],
                [
                    'NENHUM',
                    'OPERACIONAL',
                    'INVESTIMENTO',
                    'FINANCIAMENTO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Classificação da DFC inválida.'
            );
        }

        if (
            $dados['fluxo_caixa_descricao'] !== null
            && mb_strlen(
                $dados['fluxo_caixa_descricao']
            ) > 180
        ) {
            throw new RuntimeException(
                'A descrição da DFC não pode exceder 180 caracteres.'
            );
        }

        $this->validarStatus(
            $dados['status_id'],
            'conta_contabilistica'
        );
    }

    // ==================================================
    // VALIDAÇÕES AUXILIARES
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }
    }

    private function validarPlanoEmpresa(
        int $planoId,
        int $empresaId
    ): void {

        $plano =
            $this->buscarPlano(
                $planoId,
                $empresaId
            );

        if (!$plano) {
            throw new RuntimeException(
                'Plano de contas não encontrado.'
            );
        }
    }

    private function removerPlanoPadrao(
        int $empresaId,
        ?int $ignorarId
    ): void {

        $sql = "
            UPDATE planos_contas

            SET padrao = 0

            WHERE empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :id
            ";

            $params[':id'] =
                $ignorarId;
        }

        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );
    }

    private function validarCicloHierarquia(
        int $contaId,
        ?int $contaPaiId
    ): void {

        if ($contaPaiId === null) {
            return;
        }

        $atual = $contaPaiId;

        while ($atual !== null) {
            if ($atual === $contaId) {
                throw new RuntimeException(
                    'A hierarquia criaria um ciclo entre as contas.'
                );
            }

            $stmt = $this->pdo->prepare("
                SELECT conta_pai_id

                FROM contas_contabilisticas

                WHERE id = :id

                LIMIT 1
            ");

            $stmt->execute([
                ':id' => $atual
            ]);

            $resultado =
                $stmt->fetchColumn();

            $atual =
                $resultado !== false
                && $resultado !== null
                ? (int)$resultado
                : null;
        }
    }

    private function validarAlteracaoTipo(
        int $contaId,
        string $novoTipo
    ): void {

        if (
            $novoTipo === 'ANALITICA'
            && $this->contaPossuiFilhas(
                $contaId
            )
        ) {
            throw new RuntimeException(
                'Uma conta com contas filhas deve permanecer sintética.'
            );
        }

        if (
            $novoTipo === 'SINTETICA'
            && $this->contaPossuiMovimentos(
                $contaId
            )
        ) {
            throw new RuntimeException(
                'Uma conta com movimentos deve permanecer analítica.'
            );
        }
    }

    private function contaPossuiFilhas(
        int $contaId
    ): bool {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM contas_contabilisticas

            WHERE conta_pai_id = :id
        ");

        $stmt->execute([
            ':id' => $contaId
        ]);

        return (
            (int)$stmt->fetchColumn()
            > 0
        );
    }

    private function contaPossuiMovimentos(
        int $contaId
    ): bool {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM lancamento_contabilistico_itens

            WHERE conta_contabil_id =
                :conta_id
        ");

        $stmt->execute([
            ':conta_id' =>
            $contaId
        ]);

        return (
            (int)$stmt->fetchColumn()
            > 0
        );
    }

    private function obterClasse(
        string $codigo
    ): ?string {

        $codigo =
            trim($codigo);

        if ($codigo === '') {
            return null;
        }

        if (
            preg_match(
                '/^[0-9]+/',
                $codigo,
                $resultado
            )
        ) {
            return $resultado[0][0]
                ?? null;
        }

        return strtoupper(
            substr(
                $codigo,
                0,
                1
            )
        );
    }

    private function parametrosConta(
        array $dados
    ): array {

        return [
            ':empresa_id' =>
            $dados['empresa_id'],

            ':plano_contas_id' =>
            $dados['plano_contas_id'],

            ':conta_pai_id' =>
            $dados['conta_pai_id'],

            ':codigo' =>
            $dados['codigo'],

            ':nome' =>
            $dados['nome'],

            ':descricao' =>
            $dados['descricao'],

            ':nivel' =>
            $dados['nivel'],

            ':classe' =>
            $dados['classe'],

            ':tipo' =>
            $dados['tipo'],

            ':natureza' =>
            $dados['natureza'],

            ':grupo' =>
            $dados['grupo'],

            ':aceita_lancamento' =>
            $dados['aceita_lancamento'],

            ':exige_centro_custo' =>
            $dados['exige_centro_custo'],

            ':exige_entidade' =>
            $dados['exige_entidade'],

            ':conta_caixa' =>
            $dados['conta_caixa'],

            ':conta_banco' =>
            $dados['conta_banco'],

            ':conta_cliente' =>
            $dados['conta_cliente'],

            ':conta_fornecedor' =>
            $dados['conta_fornecedor'],

            ':conta_imposto' =>
            $dados['conta_imposto'],

            ':integracao_codigo' =>
            $dados['integracao_codigo'],

            ':fluxo_caixa_categoria' =>
            $dados['fluxo_caixa_categoria'],

            ':fluxo_caixa_descricao' =>
            $dados['fluxo_caixa_descricao'],

            ':status_id' =>
            $dados['status_id'],

            ':criado_por' =>
            $dados['usuario_id'],

            ':atualizado_por' =>
            $dados['usuario_id']
        ];
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

    // ==================================================
    // VALIDAR STATUS
    // ==================================================

    private function validarStatus(
        int $statusId,
        string $tipo
    ): void {

        if ($statusId <= 0) {
            throw new RuntimeException(
                'Status inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
        SELECT COUNT(*)

        FROM status

        WHERE id = :id
          AND tipo = :tipo
    ");

        $stmt->execute([
            ':id' =>
            $statusId,

            ':tipo' =>
            $tipo
        ]);

        if (
            (int)$stmt->fetchColumn()
            !== 1
        ) {
            throw new RuntimeException(
                "Status inválido para '{$tipo}'."
            );
        }
    }


    // ==================================================
    // VERIFICAR STATUS PELO NOME
    // ==================================================

    private function statusEh(
        int $statusId,
        string $tipo,
        string $nome
    ): bool {

        $stmt = $this->pdo->prepare("
        SELECT COUNT(*)

        FROM status

        WHERE id = :id
          AND tipo = :tipo
          AND LOWER(nome) = LOWER(:nome)
    ");

        $stmt->execute([
            ':id' =>
            $statusId,

            ':tipo' =>
            $tipo,

            ':nome' =>
            $nome
        ]);

        return (
            (int)$stmt->fetchColumn()
            === 1
        );
    }
}
