<?php

declare(strict_types=1);

final class AtivoEmpresa
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        int $empresaId
    ): array {

        return [
            'categorias_tangiveis' =>
            $this->listarCategorias(
                $empresaId,
                'TANGIVEL'
            ),

            'categorias_intangiveis' =>
            $this->listarCategorias(
                $empresaId,
                'INTANGIVEL'
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'departamentos' =>
            $this->listarDepartamentos(
                $empresaId
            ),

            'contas_contabeis' =>
            $this->listarContasContabeis(
                $empresaId
            ),

            'fornecedores' =>
            $this->listarFornecedores(
                $empresaId
            )
        ];
    }

    private function listarContasContabeis(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                conta.id,
                conta.codigo,
                conta.nome,
                conta.grupo,
                conta.classe,
                conta.tipo,
                conta.aceita_lancamento,

                conta.conta_pai_id,

                pai.codigo
                    AS conta_pai_codigo,

                pai.nome
                    AS conta_pai_nome,

                classe.codigo
                    AS classe_codigo,

                classe.nome
                    AS classe_nome

            FROM contas_contabilisticas conta

            LEFT JOIN contas_contabilisticas pai
                ON pai.id =
                    conta.conta_pai_id

               AND pai.empresa_id =
                    conta.empresa_id

            LEFT JOIN contas_contabilisticas classe
                ON classe.empresa_id =
                    conta.empresa_id

               AND classe.plano_contas_id =
                    conta.plano_contas_id

               AND classe.codigo =
                    conta.classe

            WHERE conta.empresa_id =
                :empresa_id

              AND conta.tipo =
                'ANALITICA'

              AND conta.aceita_lancamento =
                1

            ORDER BY
                conta.classe,
                conta.codigo
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
    // CONFIGURAÇÃO CONTABILÍSTICA DO ATIVO
    // ==================================================

    private function configuracaoContabilAtivo(
        int $empresaId,
        int $contaAtivoId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                config.id,
                config.conta_ativo_id,
                config.conta_acumulada_id,
                config.conta_gasto_id

            FROM ativos_configuracoes_contabeis config

            WHERE config.empresa_id =
                :empresa_id

              AND config.conta_ativo_id =
                :conta_ativo_id

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':conta_ativo_id' =>
            $contaAtivoId
        ]);


        $config =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$config) {

            throw new RuntimeException(
                'A categoria deste ativo ainda não possui configuração contabilística.'
            );
        }


        if (
            empty($config['conta_acumulada_id'])
            || empty($config['conta_gasto_id'])
        ) {

            throw new RuntimeException(
                'A configuração contabilística do ativo está incompleta.'
            );
        }


        return $config;
    }

    // ==================================================
    // LISTAR MOVIMENTAÇÕES
    // ==================================================

    public function listarMovimentacoes(
        int $empresaId,
        array $filtros = []
    ): array {

        $sql = "
        SELECT
            mov.id,
            mov.ativo_id,
            mov.tipo_movimentacao,
            mov.data_movimentacao,
            mov.localizacao_origem,
            mov.localizacao_destino,
            mov.responsavel_origem,
            mov.responsavel_destino,
            mov.motivo,
            mov.observacoes,
            mov.criado_em,

            ativo.codigo AS ativo_codigo,
            ativo.descricao AS ativo_descricao,

            filial_origem.nome AS filial_origem_nome,
            filial_destino.nome AS filial_destino_nome,

            departamento_origem.nome AS departamento_origem_nome,
            departamento_destino.nome AS departamento_destino_nome,

            usuario.nome AS usuario_nome

        FROM ativos_movimentacoes mov

        INNER JOIN ativos_empresa ativo
            ON ativo.id = mov.ativo_id
           AND ativo.empresa_id = mov.empresa_id

        LEFT JOIN filiais filial_origem
            ON filial_origem.id = mov.filial_origem_id

        LEFT JOIN filiais filial_destino
            ON filial_destino.id = mov.filial_destino_id

        LEFT JOIN departamentos departamento_origem
            ON departamento_origem.id = mov.departamento_origem_id

        LEFT JOIN departamentos departamento_destino
            ON departamento_destino.id = mov.departamento_destino_id

        LEFT JOIN usuarios usuario
            ON usuario.id = mov.usuario_id

        WHERE mov.empresa_id = :empresa_id
    ";

        $params = [
            ':empresa_id' => $empresaId
        ];


        if (!empty($filtros['ativo_id'])) {

            $sql .= "
            AND mov.ativo_id = :ativo_id
        ";

            $params[':ativo_id'] =
                (int)$filtros['ativo_id'];
        }


        if (!empty($filtros['tipo_movimentacao'])) {

            $sql .= "
            AND mov.tipo_movimentacao =
                :tipo_movimentacao
        ";

            $params[':tipo_movimentacao'] =
                strtoupper(
                    trim(
                        (string)$filtros['tipo_movimentacao']
                    )
                );
        }


        if (!empty($filtros['filial_id'])) {

            $sql .= "
            AND (
                mov.filial_origem_id = :filial_id_origem
                OR mov.filial_destino_id = :filial_id_destino
            )
        ";

            $params[':filial_id_origem'] =
                (int)$filtros['filial_id'];

            $params[':filial_id_destino'] =
                (int)$filtros['filial_id'];
        }


        if (!empty($filtros['departamento_id'])) {

            $sql .= "
            AND (
                mov.departamento_origem_id = :dep_origem
                OR mov.departamento_destino_id = :dep_destino
            )
        ";

            $params[':dep_origem'] =
                (int)$filtros['departamento_id'];

            $params[':dep_destino'] =
                (int)$filtros['departamento_id'];
        }


        if (!empty($filtros['data_inicial'])) {

            $sql .= "
            AND mov.data_movimentacao >= :data_inicial
        ";

            $params[':data_inicial'] =
                $filtros['data_inicial'];
        }


        if (!empty($filtros['data_final'])) {

            $sql .= "
            AND mov.data_movimentacao <= :data_final
        ";

            $params[':data_final'] =
                $filtros['data_final'];
        }


        if (!empty($filtros['pesquisa'])) {

            $sql .= "
            AND (
                ativo.codigo LIKE :pesquisa
                OR ativo.descricao LIKE :pesquisa
                OR mov.motivo LIKE :pesquisa
                OR mov.localizacao_origem LIKE :pesquisa
                OR mov.localizacao_destino LIKE :pesquisa
                OR mov.responsavel_origem LIKE :pesquisa
                OR mov.responsavel_destino LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . trim(
                    (string)$filtros['pesquisa']
                )
                . '%';
        }


        $sql .= "
        ORDER BY
            mov.data_movimentacao DESC,
            mov.id DESC
    ";


        $stmt =
            $this->pdo->prepare(
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
    // ATIVOS DISPONÍVEIS PARA MOVIMENTAÇÃO
    // ==================================================

    public function ativosParaMovimentacao(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                ativo.id,
                ativo.codigo,
                ativo.descricao,
                ativo.filial_id,
                ativo.departamento_id,
                ativo.localizacao,
                ativo.responsavel,
                ativo.situacao,

                filial.nome AS filial_nome,
                departamento.nome AS departamento_nome

            FROM ativos_empresa ativo

            LEFT JOIN filiais filial
                ON filial.id = ativo.filial_id

            LEFT JOIN departamentos departamento
                ON departamento.id = ativo.departamento_id

            WHERE ativo.empresa_id =
                :empresa_id

              AND ativo.situacao NOT IN (
                    'VENDIDO',
                    'ABATIDO'
              )

            ORDER BY
                ativo.codigo
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
    // REGISTAR MOVIMENTAÇÃO
    // ==================================================

    public function criarMovimentacao(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {

        $ativoId =
            (int)(
                $dados['ativo_id']
                ?? 0
            );

        $tipo =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_movimentacao']
                        ?? ''
                    )
                )
            );

        $dataMovimentacao =
            trim(
                (string)(
                    $dados['data_movimentacao']
                    ?? ''
                )
            );

        $filialDestinoId =
            $this->inteiroOuNull(
                $dados['filial_destino_id']
                    ?? null
            );

        $departamentoDestinoId =
            $this->inteiroOuNull(
                $dados['departamento_destino_id']
                    ?? null
            );

        $localizacaoDestino =
            $this->textoOuNull(
                $dados['localizacao_destino']
                    ?? null
            );

        $responsavelDestino =
            $this->textoOuNull(
                $dados['responsavel_destino']
                    ?? null
            );

        $motivo =
            $this->textoOuNull(
                $dados['motivo']
                    ?? null
            );

        $observacoes =
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            );


        if ($ativoId <= 0) {
            throw new RuntimeException(
                'Selecione o ativo.'
            );
        }


        $tiposPermitidos = [
            'TRANSFERENCIA',
            'ALTERACAO_RESPONSAVEL',
            'ALTERACAO_LOCALIZACAO',
            'CEDENCIA',
            'DEVOLUCAO'
        ];


        if (
            !in_array(
                $tipo,
                $tiposPermitidos,
                true
            )
        ) {

            throw new RuntimeException(
                'Tipo de movimentação inválido.'
            );
        }


        if ($dataMovimentacao === '') {
            throw new RuntimeException(
                'Informe a data da movimentação.'
            );
        }


        $ativo =
            $this->buscar(
                $empresaId,
                $ativoId
            );


        if ($ativo === null) {

            throw new RuntimeException(
                'Ativo não encontrado.'
            );
        }


        if (
            in_array(
                $ativo['situacao'],
                [
                    'VENDIDO',
                    'ABATIDO'
                ],
                true
            )
        ) {

            throw new RuntimeException(
                'Este ativo não pode ser movimentado.'
            );
        }


        /*
     * Valores finais.
     *
     * Se um campo não vier preenchido,
     * mantém-se o valor atual.
     */

        $filialFinalId =
            $filialDestinoId
            ?? $this->inteiroOuNull(
                $ativo['filial_id']
                    ?? null
            );


        $departamentoFinalId =
            $departamentoDestinoId
            ?? $this->inteiroOuNull(
                $ativo['departamento_id']
                    ?? null
            );


        $localizacaoFinal =
            $localizacaoDestino
            ?? $this->textoOuNull(
                $ativo['localizacao']
                    ?? null
            );


        $responsavelFinal =
            $responsavelDestino
            ?? $this->textoOuNull(
                $ativo['responsavel']
                    ?? null
            );


        /*
     * Evita guardar uma movimentação
     * sem qualquer alteração.
     */

        $mudou =
            (int)($ativo['filial_id'] ?? 0)
            !== (int)($filialFinalId ?? 0)

            || (int)($ativo['departamento_id'] ?? 0)
            !== (int)($departamentoFinalId ?? 0)

            || trim(
                (string)(
                    $ativo['localizacao']
                    ?? ''
                )
            )
            !== trim(
                (string)(
                    $localizacaoFinal
                    ?? ''
                )
            )

            || trim(
                (string)(
                    $ativo['responsavel']
                    ?? ''
                )
            )
            !== trim(
                (string)(
                    $responsavelFinal
                    ?? ''
                )
            );


        if (!$mudou) {

            throw new RuntimeException(
                'Não foi identificada nenhuma alteração na movimentação.'
            );
        }


        $this->pdo->beginTransaction();


        try {

            // ==========================================
            // HISTÓRICO
            // ==========================================

            $stmt =
                $this->pdo->prepare("
                INSERT INTO ativos_movimentacoes
                (
                    empresa_id,
                    ativo_id,
                    tipo_movimentacao,

                    filial_origem_id,
                    filial_destino_id,

                    departamento_origem_id,
                    departamento_destino_id,

                    localizacao_origem,
                    localizacao_destino,

                    responsavel_origem,
                    responsavel_destino,

                    data_movimentacao,
                    motivo,
                    observacoes,
                    usuario_id
                )
                VALUES
                (
                    :empresa_id,
                    :ativo_id,
                    :tipo_movimentacao,

                    :filial_origem_id,
                    :filial_destino_id,

                    :departamento_origem_id,
                    :departamento_destino_id,

                    :localizacao_origem,
                    :localizacao_destino,

                    :responsavel_origem,
                    :responsavel_destino,

                    :data_movimentacao,
                    :motivo,
                    :observacoes,
                    :usuario_id
                )
            ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':ativo_id' =>
                $ativoId,

                ':tipo_movimentacao' =>
                $tipo,

                ':filial_origem_id' =>
                $ativo['filial_id']
                    ?? null,

                ':filial_destino_id' =>
                $filialFinalId,

                ':departamento_origem_id' =>
                $ativo['departamento_id']
                    ?? null,

                ':departamento_destino_id' =>
                $departamentoFinalId,

                ':localizacao_origem' =>
                $ativo['localizacao']
                    ?? null,

                ':localizacao_destino' =>
                $localizacaoFinal,

                ':responsavel_origem' =>
                $ativo['responsavel']
                    ?? null,

                ':responsavel_destino' =>
                $responsavelFinal,

                ':data_movimentacao' =>
                $dataMovimentacao,

                ':motivo' =>
                $motivo,

                ':observacoes' =>
                $observacoes,

                ':usuario_id' =>
                $usuarioId
            ]);


            $movimentacaoId =
                (int)$this->pdo
                    ->lastInsertId();


            // ==========================================
            // POSIÇÃO ATUAL DO ATIVO
            // ==========================================

            $stmt =
                $this->pdo->prepare("
                UPDATE ativos_empresa

                SET
                    filial_id =
                        :filial_id,

                    departamento_id =
                        :departamento_id,

                    localizacao =
                        :localizacao,

                    responsavel =
                        :responsavel,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':filial_id' =>
                $filialFinalId,

                ':departamento_id' =>
                $departamentoFinalId,

                ':localizacao' =>
                $localizacaoFinal,

                ':responsavel' =>
                $responsavelFinal,

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $ativoId,

                ':empresa_id' =>
                $empresaId
            ]);


            $this->pdo->commit();


            return $movimentacaoId;
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
    // LISTAR MANUTENÇÕES
    // ==================================================

    public function listarManutencoes(
        int $empresaId,
        array $filtros = []
    ): array {

        $sql = "
        SELECT
            manut.id,
            manut.ativo_id,
            manut.tipo AS tipo_manutencao,
            manut.data_prevista,
            manut.data_inicio,
            manut.data_realizada,
            manut.proxima_manutencao,
            manut.fornecedor_id,
            manut.tecnico_responsavel,
            manut.descricao,
            manut.custo,
            manut.estado,
            manut.observacoes,
            manut.criado_em,

            ativo.codigo AS ativo_codigo,
            ativo.descricao AS ativo_descricao,

            fornecedor.nome AS fornecedor_nome,

            usuario.nome AS usuario_nome

        FROM ativos_manutencoes manut

        INNER JOIN ativos_empresa ativo
            ON ativo.id = manut.ativo_id
           AND ativo.empresa_id = manut.empresa_id

        LEFT JOIN fornecedores fornecedor
            ON fornecedor.id = manut.fornecedor_id

        LEFT JOIN usuarios usuario
            ON usuario.id = manut.usuario_id

        WHERE manut.empresa_id =
            :empresa_id
    ";

        $params = [
            ':empresa_id' => $empresaId
        ];


        if (!empty($filtros['ativo_id'])) {

            $sql .= "
            AND manut.ativo_id =
                :ativo_id
        ";

            $params[':ativo_id'] =
                (int)$filtros['ativo_id'];
        }


        if (!empty($filtros['tipo_manutencao'])) {

            $sql .= "
            AND manut.tipo =
                :tipo_manutencao
        ";

            $params[':tipo_manutencao'] =
                strtoupper(
                    trim(
                        (string)$filtros['tipo_manutencao']
                    )
                );
        }


        if (!empty($filtros['estado'])) {

            $sql .= "
            AND manut.estado =
                :estado
        ";

            $params[':estado'] =
                strtoupper(
                    trim(
                        (string)$filtros['estado']
                    )
                );
        }


        if (!empty($filtros['data_inicial'])) {

            $sql .= "
            AND COALESCE(
                manut.data_realizada,
                manut.data_prevista
            ) >= :data_inicial
        ";

            $params[':data_inicial'] =
                $filtros['data_inicial'];
        }


        if (!empty($filtros['data_final'])) {

            $sql .= "
            AND COALESCE(
                manut.data_realizada,
                manut.data_prevista
            ) <= :data_final
        ";

            $params[':data_final'] =
                $filtros['data_final'];
        }


        if (!empty($filtros['pesquisa'])) {

            $sql .= "
            AND (
                ativo.codigo
                    LIKE :pesquisa

                OR ativo.descricao
                    LIKE :pesquisa

                OR manut.tecnico_responsavel
                    LIKE :pesquisa

                OR manut.descricao
                    LIKE :pesquisa

                OR fornecedor.nome
                    LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . trim(
                    (string)$filtros['pesquisa']
                )
                . '%';
        }


        $sql .= "
        ORDER BY
            manut.data_prevista DESC,
            manut.id DESC
    ";


        $stmt =
            $this->pdo->prepare(
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
    // BUSCAR MANUTENÇÃO
    // ==================================================

    public function buscarManutencao(
        int $empresaId,
        int $id
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                manut.*,

                ativo.codigo AS ativo_codigo,
                ativo.descricao AS ativo_descricao,

                fornecedor.nome AS fornecedor_nome

            FROM ativos_manutencoes manut

            INNER JOIN ativos_empresa ativo
                ON ativo.id = manut.ativo_id
               AND ativo.empresa_id = manut.empresa_id

            LEFT JOIN fornecedores fornecedor
                ON fornecedor.id = manut.fornecedor_id

            WHERE manut.id =
                :id

              AND manut.empresa_id =
                :empresa_id

            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }

    // ==================================================
    // GUARDAR MANUTENÇÃO
    // ==================================================

    public function guardarManutencao(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {

        $id =
            (int)(
                $dados['id']
                ?? 0
            );


        $ativoId =
            (int)(
                $dados['ativo_id']
                ?? 0
            );


        $tipo =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_manutencao']
                        ?? ''
                    )
                )
            );


        $estado =
            strtoupper(
                trim(
                    (string)(
                        $dados['estado']
                        ?? 'AGENDADA'
                    )
                )
            );


        $tiposPermitidos = [
            'PREVENTIVA',
            'CORRETIVA',
            'INSPECAO',
            'OUTRA'
        ];


        $estadosPermitidos = [
            'AGENDADA',
            'EM_ANDAMENTO',
            'CONCLUIDA',
            'CANCELADA'
        ];


        if ($ativoId <= 0) {

            throw new RuntimeException(
                'Selecione o ativo.'
            );
        }


        if (
            !in_array(
                $tipo,
                $tiposPermitidos,
                true
            )
        ) {

            throw new RuntimeException(
                'Tipo de manutenção inválido.'
            );
        }


        if (
            !in_array(
                $estado,
                $estadosPermitidos,
                true
            )
        ) {

            throw new RuntimeException(
                'Estado da manutenção inválido.'
            );
        }


        $dataPrevista =
            trim(
                (string)(
                    $dados['data_prevista']
                    ?? ''
                )
            );


        if ($dataPrevista === '') {

            throw new RuntimeException(
                'Informe a data prevista.'
            );
        }


        $ativo =
            $this->buscar(
                $empresaId,
                $ativoId
            );


        if ($ativo === null) {

            throw new RuntimeException(
                'Ativo não encontrado.'
            );
        }


        if (
            in_array(
                $ativo['situacao'],
                [
                    'VENDIDO',
                    'ABATIDO'
                ],
                true
            )
        ) {

            throw new RuntimeException(
                'Este ativo não pode receber manutenção.'
            );
        }


        $fornecedorId =
            $this->inteiroOuNull(
                $dados['fornecedor_id']
                    ?? null
            );


        $dataInicio =
            $this->textoOuNull(
                $dados['data_inicio']
                    ?? null
            );


        $dataRealizada =
            $this->textoOuNull(
                $dados['data_realizada']
                    ?? null
            );


        $proximaManutencao =
            $this->textoOuNull(
                $dados['proxima_manutencao']
                    ?? null
            );


        $tecnico =
            $this->textoOuNull(
                $dados['tecnico_responsavel']
                    ?? null
            );


        $descricao =
            $this->textoOuNull(
                $dados['descricao']
                    ?? null
            );


        $observacoes =
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            );


        $custo =
            round(
                max(
                    0,
                    (float)(
                        $dados['custo']
                        ?? 0
                    )
                ),
                2
            );


        $this->pdo
            ->beginTransaction();


        try {

            if ($id > 0) {

                $stmt =
                    $this->pdo->prepare("
        UPDATE ativos_manutencoes

        SET
            ativo_id =
                :ativo_id,

            tipo =
                :tipo_manutencao,

            data_prevista =
                :data_prevista,

            data_inicio =
                :data_inicio,

            data_realizada =
                :data_realizada,

            descricao =
                :descricao,

            fornecedor_id =
                :fornecedor_id,

            tecnico_responsavel =
                :tecnico_responsavel,

            custo =
                :custo,

            proxima_manutencao =
                :proxima_manutencao,

            estado =
                :estado,

            observacoes =
                :observacoes,

            usuario_id =
                :usuario_id

        WHERE id =
            :id

          AND empresa_id =
            :empresa_id
    ");

                $stmt->execute([
                    ':ativo_id' =>
                    $ativoId,

                    ':tipo_manutencao' =>
                    $tipo,

                    ':data_prevista' =>
                    $dataPrevista,

                    ':data_inicio' =>
                    $dataInicio,

                    ':data_realizada' =>
                    $dataRealizada,

                    ':descricao' =>
                    $descricao,

                    ':fornecedor_id' =>
                    $fornecedorId,

                    ':tecnico_responsavel' =>
                    $tecnico,

                    ':custo' =>
                    $custo,

                    ':proxima_manutencao' =>
                    $proximaManutencao,

                    ':estado' =>
                    $estado,

                    ':observacoes' =>
                    $observacoes,

                    ':usuario_id' =>
                    $usuarioId,

                    ':id' =>
                    $id,

                    ':empresa_id' =>
                    $empresaId
                ]);
            } else {

                $stmt =
                    $this->pdo->prepare("
        INSERT INTO ativos_manutencoes
        (
            empresa_id,
            ativo_id,
            tipo,
            data_prevista,
            data_inicio,
            data_realizada,
            descricao,
            fornecedor_id,
            tecnico_responsavel,
            custo,
            proxima_manutencao,
            estado,
            observacoes,
            usuario_id
        )
        VALUES
        (
            :empresa_id,
            :ativo_id,
            :tipo_manutencao,
            :data_prevista,
            :data_inicio,
            :data_realizada,
            :descricao,
            :fornecedor_id,
            :tecnico_responsavel,
            :custo,
            :proxima_manutencao,
            :estado,
            :observacoes,
            :usuario_id
        )
    ");

                $stmt->execute([
                    ':empresa_id' =>
                    $empresaId,

                    ':ativo_id' =>
                    $ativoId,

                    ':tipo_manutencao' =>
                    $tipo,

                    ':data_prevista' =>
                    $dataPrevista,

                    ':data_inicio' =>
                    $dataInicio,

                    ':data_realizada' =>
                    $dataRealizada,

                    ':descricao' =>
                    $descricao,

                    ':fornecedor_id' =>
                    $fornecedorId,

                    ':tecnico_responsavel' =>
                    $tecnico,

                    ':custo' =>
                    $custo,

                    ':proxima_manutencao' =>
                    $proximaManutencao,

                    ':estado' =>
                    $estado,

                    ':observacoes' =>
                    $observacoes,

                    ':usuario_id' =>
                    $usuarioId
                ]);


                $id =
                    (int)$this->pdo
                        ->lastInsertId();
            }


            // ==========================================
            // ATUALIZAR SITUAÇÃO DO ATIVO
            // ==========================================

            if (
                $estado === 'EM_ANDAMENTO'
            ) {

                $stmt =
                    $this->pdo->prepare("
                    UPDATE ativos_empresa

                    SET
                        situacao =
                            'EM_MANUTENCAO',

                        atualizado_por =
                            :usuario_id

                    WHERE id =
                        :ativo_id

                      AND empresa_id =
                        :empresa_id
                ");


                $stmt->execute([
                    ':usuario_id' =>
                    $usuarioId,

                    ':ativo_id' =>
                    $ativoId,

                    ':empresa_id' =>
                    $empresaId
                ]);
            } elseif (
                in_array(
                    $estado,
                    [
                        'CONCLUIDA',
                        'CANCELADA'
                    ],
                    true
                )
            ) {

                /*
             * Só volta para EM_USO se não existir
             * outra manutenção em andamento.
             */

                $stmt =
                    $this->pdo->prepare("
                    SELECT COUNT(*)

                    FROM ativos_manutencoes

                    WHERE empresa_id =
                        :empresa_id

                      AND ativo_id =
                        :ativo_id

                      AND estado =
                        'EM_ANDAMENTO'

                      AND id <> :id
                ");


                $stmt->execute([
                    ':empresa_id' =>
                    $empresaId,

                    ':ativo_id' =>
                    $ativoId,

                    ':id' =>
                    $id
                ]);


                $outrasEmAndamento =
                    (int)$stmt
                        ->fetchColumn();


                if (
                    $outrasEmAndamento === 0
                ) {

                    $stmt =
                        $this->pdo->prepare("
                        UPDATE ativos_empresa

                        SET
                            situacao =
                                'EM_USO',

                            atualizado_por =
                                :usuario_id

                        WHERE id =
                            :ativo_id

                          AND empresa_id =
                            :empresa_id

                          AND situacao =
                            'EM_MANUTENCAO'
                    ");


                    $stmt->execute([
                        ':usuario_id' =>
                        $usuarioId,

                        ':ativo_id' =>
                        $ativoId,

                        ':empresa_id' =>
                        $empresaId
                    ]);
                }
            }


            $this->pdo
                ->commit();


            return $id;
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {

                $this->pdo
                    ->rollBack();
            }


            throw $e;
        }
    }

    // ==================================================
    // LISTAR BAIXAS / ALIENAÇÕES
    // ==================================================

    public function listarBaixas(
        int $empresaId,
        array $filtros = []
    ): array {

        $sql = "
    SELECT
        baixa.id,
        baixa.ativo_id,
        baixa.tipo,
        baixa.data_baixa,

        baixa.valor_aquisicao,
        baixa.depreciacao_acumulada,
        baixa.valor_liquido,
        baixa.valor_alienacao,
        baixa.ganho_perda,

        baixa.motivo,
        baixa.observacoes,
        baixa.documento,

        baixa.lancamento_contabil_id,
        baixa.usuario_id,
        baixa.criado_em,

        ativo.codigo AS ativo_codigo,
        ativo.descricao AS ativo_descricao,

        usuario.nome AS usuario_nome

    FROM ativos_baixas baixa

    INNER JOIN ativos_empresa ativo
        ON ativo.id = baixa.ativo_id
       AND ativo.empresa_id = baixa.empresa_id

    LEFT JOIN usuarios usuario
        ON usuario.id = baixa.usuario_id

    WHERE baixa.empresa_id =
        :empresa_id
";

        $params = [
            ':empresa_id' => $empresaId
        ];


        if (!empty($filtros['ativo_id'])) {

            $sql .= "
            AND baixa.ativo_id =
                :ativo_id
        ";

            $params[':ativo_id'] =
                (int)$filtros['ativo_id'];
        }


        if (!empty($filtros['tipo'])) {

            $sql .= "
            AND baixa.tipo =
                :tipo
        ";

            $params[':tipo'] =
                strtoupper(
                    trim(
                        (string)$filtros['tipo']
                    )
                );
        }


        if (!empty($filtros['data_inicial'])) {

            $sql .= "
            AND baixa.data_baixa >=
                :data_inicial
        ";

            $params[':data_inicial'] =
                $filtros['data_inicial'];
        }


        if (!empty($filtros['data_final'])) {

            $sql .= "
            AND baixa.data_baixa <=
                :data_final
        ";

            $params[':data_final'] =
                $filtros['data_final'];
        }


        if (!empty($filtros['pesquisa'])) {

            $sql .= "
    AND (
        ativo.codigo
            LIKE :pesquisa

        OR ativo.descricao
            LIKE :pesquisa

        OR baixa.documento
            LIKE :pesquisa

        OR baixa.motivo
            LIKE :pesquisa
    )
";

            $params[':pesquisa'] =
                '%'
                . trim(
                    (string)$filtros['pesquisa']
                )
                . '%';
        }


        $sql .= "
        ORDER BY
            baixa.data_baixa DESC,
            baixa.id DESC
    ";


        $stmt =
            $this->pdo->prepare(
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
    // BUSCAR BAIXA
    // ==================================================

    public function buscarBaixa(
        int $empresaId,
        int $id
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                baixa.*,

                ativo.codigo AS ativo_codigo,
                ativo.descricao AS ativo_descricao

            FROM ativos_baixas baixa

            INNER JOIN ativos_empresa ativo
                ON ativo.id = baixa.ativo_id
               AND ativo.empresa_id = baixa.empresa_id

            WHERE baixa.id =
                :id

              AND baixa.empresa_id =
                :empresa_id

            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }

    // ==================================================
    // DADOS DO ATIVO PARA BAIXA
    // ==================================================

    public function dadosAtivoParaBaixa(
        int $empresaId,
        int $ativoId
    ): array {

        if ($ativoId <= 0) {

            throw new RuntimeException(
                'Ativo inválido.'
            );
        }


        $ativo =
            $this->buscar(
                $empresaId,
                $ativoId
            );


        if ($ativo === null) {

            throw new RuntimeException(
                'Ativo não encontrado.'
            );
        }


        if (
            in_array(
                $ativo['situacao'],
                [
                    'VENDIDO',
                    'ABATIDO'
                ],
                true
            )
        ) {

            throw new RuntimeException(
                'Este ativo já possui uma baixa definitiva.'
            );
        }


        $valorAquisicao =
            round(
                (float)(
                    $ativo['valor_aquisicao']
                    ?? 0
                ),
                2
            );


        $depreciacaoAcumulada =
            $this->depreciacaoAcumulada(
                $empresaId,
                $ativoId
            );


        $valorLiquido =
            round(
                max(
                    0,
                    $valorAquisicao
                        - $depreciacaoAcumulada
                ),
                2
            );


        return [
            'id' =>
            (int)$ativo['id'],

            'codigo' =>
            $ativo['codigo'],

            'descricao' =>
            $ativo['descricao'],

            'tipo' =>
            $ativo['tipo'],

            'situacao' =>
            $ativo['situacao'],

            'valor_aquisicao' =>
            $valorAquisicao,

            'depreciacao_acumulada' =>
            round(
                $depreciacaoAcumulada,
                2
            ),

            'valor_liquido' =>
            $valorLiquido
        ];
    }


    // ==================================================
    // CRIAR BAIXA / ALIENAÇÃO
    // ==================================================

    public function criarBaixa(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): array {

        $ativoId =
            (int)(
                $dados['ativo_id']
                ?? 0
            );


        $tipo =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo']
                        ?? ''
                    )
                )
            );


        $tiposPermitidos = [
            'VENDA',
            'ABATE',
            'PERDA',
            'ROUBO',
            'DOACAO',
            'FIM_VIDA_UTIL',
            'OUTRO'
        ];


        if ($ativoId <= 0) {

            throw new RuntimeException(
                'Selecione o ativo.'
            );
        }


        if (
            !in_array(
                $tipo,
                $tiposPermitidos,
                true
            )
        ) {

            throw new RuntimeException(
                'Tipo de baixa/alienação inválido.'
            );
        }


        $dataBaixa =
            trim(
                (string)(
                    $dados['data_baixa']
                    ?? ''
                )
            );


        if ($dataBaixa === '') {

            throw new RuntimeException(
                'Informe a data da baixa.'
            );
        }


        $ativo =
            $this->buscar(
                $empresaId,
                $ativoId
            );


        if ($ativo === null) {

            throw new RuntimeException(
                'Ativo não encontrado.'
            );
        }


        if (
            in_array(
                $ativo['situacao'],
                [
                    'VENDIDO',
                    'ABATIDO'
                ],
                true
            )
        ) {

            throw new RuntimeException(
                'Este ativo já foi baixado ou vendido.'
            );
        }


        // ==========================================
        // VALOR LÍQUIDO ATUAL
        // ==========================================

        $acumulado =
            $this->depreciacaoAcumulada(
                $empresaId,
                $ativoId
            );


        $valorAquisicao =
            (float)$ativo['valor_aquisicao'];


        $valorLiquido =
            max(
                0,
                $valorAquisicao
                    - $acumulado
            );


        $valorAlienacao = null;

        if (
            isset($dados['valor_alienacao'])
            && $dados['valor_alienacao'] !== ''
        ) {

            $valorAlienacao =
                round(
                    max(
                        0,
                        (float)$dados['valor_alienacao']
                    ),
                    2
                );
        }


        $valorRecebido =
            $valorAlienacao
            ?? 0;


        $ganhoPerda =
            round(
                $valorRecebido
                    - $valorLiquido,
                2
            );

        $formaRecebimento =
            strtoupper(
                trim(
                    (string)(
                        $dados['forma_recebimento']
                        ?? ''
                    )
                )
            );


        $contaRecebimentoId =
            $this->inteiroOuNull(
                $dados['conta_recebimento_id']
                    ?? null
            );

        if ($tipo === 'VENDA') {

            $formasPermitidas = [
                'CAIXA',
                'BANCO',
                'CONTA_RECEBER'
            ];


            if (
                !in_array(
                    $formaRecebimento,
                    $formasPermitidas,
                    true
                )
            ) {

                throw new RuntimeException(
                    'Informe a forma de recebimento da venda.'
                );
            }


            if (
                $contaRecebimentoId === null
            ) {

                throw new RuntimeException(
                    'Selecione a conta de recebimento.'
                );
            }


            $contaRecebimento =
                $this->validarContaContabilBaixa(
                    $empresaId,
                    $contaRecebimentoId
                );


            /*
     * Validação adicional conforme
     * a forma escolhida.
     */

            $codigoConta =
                trim(
                    (string)$contaRecebimento['codigo']
                );


            if (
                $formaRecebimento === 'CAIXA'
                && !str_starts_with(
                    $codigoConta,
                    '1.1'
                )
            ) {

                throw new RuntimeException(
                    'A conta selecionada não pertence ao grupo Caixa.'
                );
            }


            if (
                $formaRecebimento === 'BANCO'
                && !str_starts_with(
                    $codigoConta,
                    '1.2'
                )
            ) {

                throw new RuntimeException(
                    'A conta selecionada não pertence ao grupo Bancos.'
                );
            }


            if (
                $formaRecebimento === 'CONTA_RECEBER'
                && !(
                    str_starts_with(
                        $codigoConta,
                        '4.1'
                    )
                    || str_starts_with(
                        $codigoConta,
                        '4.5'
                    )
                )
            ) {

                throw new RuntimeException(
                    'A conta selecionada não corresponde a uma conta a receber.'
                );
            }
        } else {

            $formaRecebimento =
                '';

            $contaRecebimentoId =
                null;
        }


        $documento =
            $this->textoOuNull(
                $dados['documento']
                    ?? null
            );


        $motivo =
            $this->textoOuNull(
                $dados['motivo']
                    ?? null
            );


        $observacoes =
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            );


        $this->pdo->beginTransaction();


        try {

            // ==========================================
            // HISTÓRICO DA BAIXA
            // ==========================================

            $stmt =
                $this->pdo->prepare("
                    INSERT INTO ativos_baixas
                    (
                        empresa_id,
                        ativo_id,
                        tipo,
                        data_baixa,
                        valor_aquisicao,
                        depreciacao_acumulada,
                        valor_liquido,
                        valor_alienacao,
                        forma_recebimento,
                        conta_recebimento_id,
                        ganho_perda,
                        motivo,
                        observacoes,
                        documento,
                        usuario_id
                    )
                    VALUES
                    (
                        :empresa_id,
                        :ativo_id,
                        :tipo,
                        :data_baixa,
                        :valor_aquisicao,
                        :depreciacao_acumulada,
                        :valor_liquido,
                        :valor_alienacao,
                        :forma_recebimento,
                        :conta_recebimento_id,
                        :ganho_perda,
                        :motivo,
                        :observacoes,
                        :documento,
                        :usuario_id
                    )
                ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId,

                ':ativo_id' =>
                $ativoId,

                ':tipo' =>
                $tipo,

                ':data_baixa' =>
                $dataBaixa,

                ':valor_aquisicao' =>
                round(
                    $valorAquisicao,
                    2
                ),

                ':depreciacao_acumulada' =>
                round(
                    $acumulado,
                    2
                ),

                ':valor_liquido' =>
                round(
                    $valorLiquido,
                    2
                ),

                ':valor_alienacao' =>
                $valorAlienacao,

                ':forma_recebimento' =>
                $formaRecebimento !== ''
                    ? $formaRecebimento
                    : null,

                ':conta_recebimento_id' =>
                $contaRecebimentoId,

                ':ganho_perda' =>
                $ganhoPerda,

                ':motivo' =>
                $motivo,

                ':observacoes' =>
                $observacoes,

                ':documento' =>
                $documento,

                ':usuario_id' =>
                $usuarioId
            ]);


            $baixaId =
                (int)$this->pdo
                    ->lastInsertId();

            // ==========================================
            // CONTABILIZAÇÃO AUTOMÁTICA
            // ==========================================

            $lancamentoId =
                $this->criarLancamentoBaixa(
                    $empresaId,
                    $usuarioId,
                    $baixaId,
                    $ativo,
                    $tipo,
                    $dataBaixa,
                    round($valorAquisicao, 2),
                    round($acumulado, 2),
                    round($valorLiquido, 2),
                    $valorAlienacao,
                    $ganhoPerda,
                    $contaRecebimentoId
                );

            // ==========================================
            // LIGAR LANÇAMENTO À BAIXA
            // ==========================================

            $stmt =
                $this->pdo->prepare("
        UPDATE ativos_baixas

        SET lancamento_contabil_id =
            :lancamento_id

        WHERE id =
            :baixa_id

          AND empresa_id =
            :empresa_id
    ");

            $stmt->execute([
                ':lancamento_id' =>
                $lancamentoId,

                ':baixa_id' =>
                $baixaId,

                ':empresa_id' =>
                $empresaId
            ]);


            // ==========================================
            // ATUALIZAR SITUAÇÃO DO ATIVO
            // ==========================================

            $novaSituacao =
                $tipo === 'VENDA'
                ? 'VENDIDO'
                : 'ABATIDO';


            $stmt =
                $this->pdo->prepare("
        UPDATE ativos_empresa

        SET
            situacao =
                :situacao,

            atualizado_por =
                :usuario_id

        WHERE id =
            :ativo_id

          AND empresa_id =
            :empresa_id
    ");

            $stmt->execute([
                ':situacao' =>
                $novaSituacao,

                ':usuario_id' =>
                $usuarioId,

                ':ativo_id' =>
                $ativoId,

                ':empresa_id' =>
                $empresaId
            ]);


            $this->pdo->commit();


            return [
                'id' =>
                $baixaId,

                'ativo_id' =>
                $ativoId,

                'tipo' =>
                $tipo,

                'valor_liquido' =>
                round(
                    $valorLiquido,
                    2
                ),

                'valor_alienacao' =>
                $valorAlienacao,

                'ganho_perda' =>
                $ganhoPerda,

                'situacao' =>
                $novaSituacao,

                'lancamento_contabil_id' =>
                $lancamentoId
            ];
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {

                $this->pdo
                    ->rollBack();
            }


            throw $e;
        }
    }


    private function obterConfiguracaoContabil(
        int $empresaId,
        int $contaAtivoId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                conta_ativo_id,
                conta_acumulada_id,
                conta_gasto_id

            FROM ativos_configuracoes_contabeis

            WHERE empresa_id = :empresa_id
              AND conta_ativo_id = :conta_ativo_id

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':conta_ativo_id' => $contaAtivoId
        ]);

        $dados =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            throw new RuntimeException(
                'Este ativo não possui configuração contabilística.'
            );
        }

        return $dados;
    }

    private function obterContextoContabil(
        int $empresaId,
        string $data
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT id
            FROM exercicios_contabilisticos
            WHERE empresa_id = :empresa_id
              AND :data BETWEEN data_inicio AND data_fim
            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':data' => $data
        ]);

        $exercicioId =
            (int)($stmt->fetchColumn() ?: 0);

        if ($exercicioId <= 0) {
            throw new RuntimeException(
                'Nenhum exercício contabilístico encontrado.'
            );
        }

        $stmt =
            $this->pdo->prepare("
            SELECT id
            FROM periodos_contabilisticos
            WHERE empresa_id = :empresa_id
              AND exercicio_id = :exercicio_id
              AND :data BETWEEN data_inicio AND data_fim
            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':exercicio_id' => $exercicioId,
            ':data' => $data
        ]);

        $periodoId =
            (int)($stmt->fetchColumn() ?: 0);

        if ($periodoId <= 0) {
            throw new RuntimeException(
                'Nenhum período contabilístico encontrado.'
            );
        }

        return [
            'exercicio_id' => $exercicioId,
            'periodo_id' => $periodoId
        ];
    }

    private function obterDiarioAtivos(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                id,
                ultimo_numero
            FROM diarios_contabilisticos
            WHERE empresa_id = :empresa_id
              AND tipo = 'OUTROS'
            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $diario =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$diario) {
            throw new RuntimeException(
                'Diário OUTROS não encontrado.'
            );
        }

        return $diario;
    }


    private function criarLancamentoDepreciacao(
        int $empresaId,
        int $usuarioId,
        int $depreciacaoId,
        array $ativo,
        array $config,
        float $valor,
        int $ano,
        int $mes,
        string $tipo
    ): int {

        $ultimoDia =
            cal_days_in_month(
                CAL_GREGORIAN,
                $mes,
                $ano
            );

        $data =
            sprintf(
                '%04d-%02d-%02d',
                $ano,
                $mes,
                $ultimoDia
            );

        $contexto =
            $this->obterContextoContabil(
                $empresaId,
                $data
            );

        $diario =
            $this->obterDiarioAtivos(
                $empresaId
            );

        $numero =
            $this->proximoNumeroDiario(
                (int)$diario['id']
            );

        $descricao =
            (
                $tipo === 'AMORTIZACAO'
                ? 'Amortização'
                : 'Depreciação'
            )
            . ' do ativo '
            . $ativo['codigo']
            . ' referente a '
            . sprintf('%02d/%04d', $mes, $ano);

        $statusId = 50;

        $stmt =
            $this->pdo->prepare("
            INSERT INTO lancamentos_contabilisticos
            (
                empresa_id,
                filial_id,
                exercicio_id,
                periodo_id,
                diario_id,
                numero,
                referencia,
                data_lancamento,
                data_documento,
                descricao,
                origem,
                origem_id,
                status_id,
                total_debito,
                total_credito,
                contabilizado_em,
                contabilizado_por,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :exercicio_id,
                :periodo_id,
                :diario_id,
                :numero,
                :referencia,
                :data_lancamento,
                :data_documento,
                :descricao,
                'ATIVO',
                :origem_id,
                :status_id,
                :total_debito,
                :total_credito,
                NOW(),
                :contabilizado_por,
                :criado_por,
                :atualizado_por
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            $ativo['filial_id']
                ?: null,

            ':exercicio_id' =>
            $contexto['exercicio_id'],

            ':periodo_id' =>
            $contexto['periodo_id'],

            ':diario_id' =>
            $diario['id'],

            ':numero' =>
            $numero,

            ':referencia' =>
            'ATIVO-' . $depreciacaoId,

            ':data_lancamento' =>
            $data,

            ':data_documento' =>
            $data,

            ':descricao' =>
            $descricao,

            ':origem_id' =>
            $depreciacaoId,

            ':status_id' =>
            $statusId,

            ':total_debito' =>
            $valor,

            ':total_credito' =>
            $valor,

            ':contabilizado_por' =>
            $usuarioId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);

        $lancamentoId =
            (int)$this->pdo->lastInsertId();

        $stmt =
            $this->pdo->prepare("
            INSERT INTO lancamento_contabilistico_itens
            (
                lancamento_id,
                conta_contabil_id,
                centro_custo_id,
                descricao,
                debito,
                credito,
                ordem
            )
            VALUES
            (
                :lancamento,
                :conta,
                :centro,
                :descricao,
                :debito,
                0,
                1
            )
        ");

        $stmt->execute([
            ':lancamento' => $lancamentoId,
            ':conta' => $config['conta_gasto_id'],
            ':centro' =>
            $ativo['centro_custo_id']
                ?: null,
            ':descricao' => $descricao,
            ':debito' => $valor
        ]);

        $stmt =
            $this->pdo->prepare("
            INSERT INTO lancamento_contabilistico_itens
            (
                lancamento_id,
                conta_contabil_id,
                centro_custo_id,
                descricao,
                debito,
                credito,
                ordem
            )
            VALUES
            (
                :lancamento,
                :conta,
                :centro,
                :descricao,
                0,
                :credito,
                2
            )
        ");

        $stmt->execute([
            ':lancamento' => $lancamentoId,
            ':conta' => $config['conta_acumulada_id'],
            ':centro' =>
            $ativo['centro_custo_id']
                ?: null,
            ':descricao' => $descricao,
            ':credito' => $valor
        ]);

        return $lancamentoId;
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
                ativo.id,
                ativo.codigo,
                ativo.tipo,
                ativo.descricao,
                ativo.marca,
                ativo.modelo,
                ativo.numero_serie,
                ativo.data_aquisicao,
                ativo.data_entrada_uso,
                ativo.valor_aquisicao,
                ativo.valor_residual,
                ativo.vida_util_meses,
                ativo.metodo_depreciacao,
                ativo.taxa_anual,
                ativo.localizacao,
                ativo.responsavel,
                ativo.estado_fisico,
                ativo.situacao,

                conta.id AS conta_contabil_id,
                conta.codigo AS conta_codigo,
                conta.nome AS categoria_nome,

                filial.id AS filial_id,
                filial.nome AS filial_nome,

                departamento.id AS departamento_id,
                departamento.nome AS departamento_nome,

                fornecedor.id AS fornecedor_id,
                fornecedor.nome AS fornecedor_nome,

                COALESCE(
                    (
                        SELECT
                            SUM(dep.valor_periodo)

                        FROM ativos_depreciacoes dep

                        WHERE dep.ativo_id =
                            ativo.id

                        AND dep.empresa_id =
                            ativo.empresa_id

                        AND dep.lancamento_estorno_id
                            IS NULL
                    ),
                    0
                ) AS depreciacao_acumulada

            FROM ativos_empresa ativo

            INNER JOIN contas_contabilisticas conta
                ON conta.id =
                    ativo.conta_contabil_id

               AND conta.empresa_id =
                    ativo.empresa_id

            LEFT JOIN filiais filial
                ON filial.id =
                    ativo.filial_id

            LEFT JOIN departamentos departamento
                ON departamento.id =
                    ativo.departamento_id

            LEFT JOIN fornecedores fornecedor
                ON fornecedor.id =
                    ativo.fornecedor_id

            WHERE ativo.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            !empty($filtros['tipo'])
        ) {

            $sql .= "
                AND ativo.tipo =
                    :tipo
            ";

            $params[':tipo'] =
                strtoupper(
                    trim(
                        (string)$filtros['tipo']
                    )
                );
        }


        if (
            !empty($filtros['conta_contabil_id'])
        ) {

            $sql .= "
                AND ativo.conta_contabil_id =
                    :conta_contabil_id
            ";

            $params[':conta_contabil_id'] =
                (int)$filtros['conta_contabil_id'];
        }


        if (
            !empty($filtros['filial_id'])
        ) {

            $sql .= "
                AND ativo.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        if (
            !empty($filtros['departamento_id'])
        ) {

            $sql .= "
                AND ativo.departamento_id =
                    :departamento_id
            ";

            $params[':departamento_id'] =
                (int)$filtros['departamento_id'];
        }


        if (
            !empty($filtros['situacao'])
        ) {

            $sql .= "
                AND ativo.situacao =
                    :situacao
            ";

            $params[':situacao'] =
                strtoupper(
                    trim(
                        (string)$filtros['situacao']
                    )
                );
        }


        if (
            !empty($filtros['pesquisa'])
        ) {

            $sql .= "
                AND (
                    ativo.codigo
                        LIKE :pesquisa

                    OR ativo.descricao
                        LIKE :pesquisa

                    OR ativo.numero_serie
                        LIKE :pesquisa

                    OR ativo.marca
                        LIKE :pesquisa

                    OR ativo.modelo
                        LIKE :pesquisa
                )
            ";

            $params[':pesquisa'] =
                '%'
                . trim(
                    (string)$filtros['pesquisa']
                )
                . '%';
        }


        $sql .= "
            ORDER BY
                ativo.id DESC
        ";


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $dados =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach ($dados as &$ativo) {

            $valorAquisicao =
                (float)$ativo['valor_aquisicao'];


            $acumulado =
                (float)$ativo['depreciacao_acumulada'];


            $ativo['valor_liquido'] =
                round(
                    max(
                        0,
                        $valorAquisicao
                            - $acumulado
                    ),
                    2
                );


            $ativo['vida_util_anos'] =
                !empty($ativo['vida_util_meses'])
                ? round(
                    (
                        (int)$ativo['vida_util_meses']
                    ) / 12,
                    2
                )
                : null;
        }


        unset($ativo);


        return $dados;
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $empresaId,
        int $ativoId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    ativo.*,

                    conta.codigo
                        AS conta_codigo,

                    conta.nome
                        AS categoria_nome,

                    filial.nome
                        AS filial_nome,

                    departamento.nome
                        AS departamento_nome,

                    fornecedor.nome
                        AS fornecedor_nome

                FROM ativos_empresa ativo

                INNER JOIN contas_contabilisticas conta
                    ON conta.id =
                        ativo.conta_contabil_id

                   AND conta.empresa_id =
                        ativo.empresa_id

                LEFT JOIN filiais filial
                    ON filial.id =
                        ativo.filial_id

                LEFT JOIN departamentos departamento
                    ON departamento.id =
                        ativo.departamento_id

                LEFT JOIN fornecedores fornecedor
                    ON fornecedor.id =
                        ativo.fornecedor_id

                WHERE ativo.id =
                    :id

                  AND ativo.empresa_id =
                    :empresa_id

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
            $ativoId,

            ':empresa_id' =>
            $empresaId
        ]);


        $ativo =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$ativo) {
            return null;
        }


        $ativo['depreciacao_acumulada'] =
            $this
            ->depreciacaoAcumulada(
                $empresaId,
                $ativoId
            );


        $ativo['valor_liquido'] =
            round(
                max(
                    (float)$ativo['valor_residual'],

                    (float)$ativo['valor_aquisicao']
                        -
                        (float)$ativo['depreciacao_acumulada']
                ),
                2
            );


        return $ativo;
    }

    // ==================================================
    // CONFIGURAÇÃO CONTABILÍSTICA DAS BAIXAS
    // ==================================================

    public function configuracaoBaixas(
        int $empresaId
    ): ?array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                config.*,

                ganho.codigo
                    AS ganho_codigo,

                ganho.nome
                    AS ganho_nome,

                perda.codigo
                    AS perda_codigo,

                perda.nome
                    AS perda_nome,

                abate.codigo
                    AS abate_codigo,

                abate.nome
                    AS abate_nome

            FROM ativos_baixas_configuracoes config

            LEFT JOIN contas_contabilisticas ganho
                ON ganho.id =
                    config.conta_ganho_alienacao_id

            LEFT JOIN contas_contabilisticas perda
                ON perda.id =
                    config.conta_perda_alienacao_id

            LEFT JOIN contas_contabilisticas abate
                ON abate.id =
                    config.conta_abate_id

            WHERE config.empresa_id =
                :empresa_id

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }

    public function guardarConfiguracaoBaixas(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): void {

        $contaGanho =
            (int)(
                $dados['conta_ganho_alienacao_id']
                ?? 0
            );

        $contaPerda =
            (int)(
                $dados['conta_perda_alienacao_id']
                ?? 0
            );

        $contaAbate =
            (int)(
                $dados['conta_abate_id']
                ?? 0
            );


        if (
            $contaGanho <= 0
            || $contaPerda <= 0
            || $contaAbate <= 0
        ) {

            throw new RuntimeException(
                'Preencha todas as contas da configuração das baixas.'
            );
        }


        $stmt =
            $this->pdo->prepare("
            INSERT INTO ativos_baixas_configuracoes
            (
                empresa_id,
                conta_ganho_alienacao_id,
                conta_perda_alienacao_id,
                conta_abate_id,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                :empresa_id,
                :conta_ganho,
                :conta_perda,
                :conta_abate,
                :criado_por,
                :atualizado_por
            )

            ON DUPLICATE KEY UPDATE

                conta_ganho_alienacao_id =
                    VALUES(
                        conta_ganho_alienacao_id
                    ),

                conta_perda_alienacao_id =
                    VALUES(
                        conta_perda_alienacao_id
                    ),

                conta_abate_id =
                    VALUES(
                        conta_abate_id
                    ),

                atualizado_por =
                    VALUES(
                        atualizado_por
                    )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':conta_ganho' =>
            $contaGanho,

            ':conta_perda' =>
            $contaPerda,

            ':conta_abate' =>
            $contaAbate,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);
    }

    // ==================================================
    // VALIDAR CONTA CONTABILÍSTICA
    // ==================================================

    private function validarContaContabilBaixa(
        int $empresaId,
        int $contaId
    ): array {

        if ($contaId <= 0) {

            throw new RuntimeException(
                'Conta contabilística inválida.'
            );
        }


        $stmt =
            $this->pdo->prepare("
            SELECT
                id,
                codigo,
                nome,
                grupo,
                tipo,
                aceita_lancamento

            FROM contas_contabilisticas

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id

              AND tipo =
                'ANALITICA'

              AND aceita_lancamento =
                1

            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $contaId,

            ':empresa_id' =>
            $empresaId
        ]);


        $conta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$conta) {

            throw new RuntimeException(
                'A conta contabilística selecionada não é válida para lançamentos.'
            );
        }


        return $conta;
    }

    private function obterConfiguracaoBaixa(
        int $empresaId
    ): array {

        $config =
            $this->configuracaoBaixas(
                $empresaId
            );


        if (!$config) {

            throw new RuntimeException(
                'A configuração contabilística das baixas ainda não foi definida.'
            );
        }


        $campos = [
            'conta_ganho_alienacao_id',
            'conta_perda_alienacao_id',
            'conta_abate_id'
        ];


        foreach ($campos as $campo) {

            if (
                empty($config[$campo])
            ) {

                throw new RuntimeException(
                    'A configuração contabilística das baixas está incompleta.'
                );
            }


            $this->validarContaContabilBaixa(
                $empresaId,
                (int)$config[$campo]
            );
        }


        return $config;
    }

    private function inserirItemLancamentoBaixa(
        int $lancamentoId,
        int $contaId,
        ?int $centroCustoId,
        string $descricao,
        float $debito,
        float $credito,
        int $ordem
    ): void {

        $stmt =
            $this->pdo->prepare("
            INSERT INTO lancamento_contabilistico_itens
            (
                lancamento_id,
                conta_contabil_id,
                centro_custo_id,
                descricao,
                debito,
                credito,
                ordem
            )
            VALUES
            (
                :lancamento_id,
                :conta_id,
                :centro_custo_id,
                :descricao,
                :debito,
                :credito,
                :ordem
            )
        ");


        $stmt->execute([
            ':lancamento_id' =>
            $lancamentoId,

            ':conta_id' =>
            $contaId,

            ':centro_custo_id' =>
            $centroCustoId,

            ':descricao' =>
            $descricao,

            ':debito' =>
            round(
                $debito,
                2
            ),

            ':credito' =>
            round(
                $credito,
                2
            ),

            ':ordem' =>
            $ordem
        ]);
    }

    // ==================================================
    // LANÇAMENTO CONTABILÍSTICO DA BAIXA
    // ==================================================

    private function criarLancamentoBaixa(
        int $empresaId,
        int $usuarioId,
        int $baixaId,
        array $ativo,
        string $tipo,
        string $dataBaixa,
        float $valorAquisicao,
        float $depreciacaoAcumulada,
        float $valorLiquido,
        ?float $valorAlienacao,
        float $ganhoPerda,
        ?int $contaRecebimentoId
    ): int {

        // ==============================================
        // CONFIGURAÇÃO
        // ==============================================

        $configAtivo =
            $this->configuracaoContabilAtivo(
                $empresaId,
                (int)$ativo['conta_contabil_id']
            );


        $configBaixa =
            $this->obterConfiguracaoBaixa(
                $empresaId
            );


        // ==============================================
        // CONTEXTO CONTABILÍSTICO
        // ==============================================

        $contexto =
            $this->obterContextoContabil(
                $empresaId,
                $dataBaixa
            );


        $diario =
            $this->obterDiarioAtivos(
                $empresaId
            );


        $numero =
            $this->proximoNumeroDiario(
                (int)$diario['id']
            );


        $statusId =
            $this->statusLancamentoPorNome(
                'Contabilizado'
            );


        if ($statusId === null) {

            throw new RuntimeException(
                'Status Contabilizado não encontrado.'
            );
        }


        // ==============================================
        // DESCRIÇÃO
        // ==============================================

        $nomesTipos = [

            'VENDA' =>
            'Venda',

            'ABATE' =>
            'Abate',

            'PERDA' =>
            'Perda',

            'ROUBO' =>
            'Roubo',

            'DOACAO' =>
            'Doação',

            'FIM_VIDA_UTIL' =>
            'Fim de vida útil',

            'OUTRO' =>
            'Baixa'
        ];


        $descricao =
            (
                $nomesTipos[$tipo]
                ?? 'Baixa'
            )
            . ' do ativo '
            . $ativo['codigo']
            . ' - '
            . $ativo['descricao'];


        // ==============================================
        // MONTAR PARTIDAS
        // ==============================================

        $itens = [];


        /*
     * 1. Retirar depreciação/amortização acumulada.
     *
     * Conta acumulada possui natureza credora.
     * Para eliminá-la, debitamos.
     */

        if (
            $depreciacaoAcumulada > 0
        ) {

            $itens[] = [
                'conta_id' =>
                (int)$configAtivo['conta_acumulada_id'],

                'debito' =>
                $depreciacaoAcumulada,

                'credito' =>
                0
            ];
        }


        /*
     * 2. VENDA
     */

        if ($tipo === 'VENDA') {

            if (
                $contaRecebimentoId === null
                || $contaRecebimentoId <= 0
            ) {

                throw new RuntimeException(
                    'Informe a conta de recebimento da venda.'
                );
            }


            $this->validarContaContabilBaixa(
                $empresaId,
                $contaRecebimentoId
            );


            $valorRecebido =
                round(
                    (float)(
                        $valorAlienacao
                        ?? 0
                    ),
                    2
                );


            if ($valorRecebido <= 0) {

                throw new RuntimeException(
                    'Informe o valor da alienação.'
                );
            }


            // Valor recebido

            $itens[] = [
                'conta_id' =>
                $contaRecebimentoId,

                'debito' =>
                $valorRecebido,

                'credito' =>
                0
            ];


            /*
         * Se ganho_perda > 0:
         * crédito em rendimento.
         */

            if ($ganhoPerda > 0) {

                $itens[] = [
                    'conta_id' =>
                    (int)$configBaixa['conta_ganho_alienacao_id'],

                    'debito' =>
                    0,

                    'credito' =>
                    $ganhoPerda
                ];
            }


            /*
         * Se ganho_perda < 0:
         * débito em perda.
         */

            if ($ganhoPerda < 0) {

                $itens[] = [
                    'conta_id' =>
                    (int)$configBaixa['conta_perda_alienacao_id'],

                    'debito' =>
                    abs(
                        $ganhoPerda
                    ),

                    'credito' =>
                    0
                ];
            }
        } else {

            /*
         * BAIXAS SEM RECEBIMENTO.
         *
         * O valor líquido precisa ser reconhecido
         * como gasto/perda.
         */




            if ($valorLiquido > 0) {

                /*
             * ABATE e FIM DE VIDA:
             * usamos a conta específica de abate.
             *
             * PERDA, ROUBO, DOAÇÃO e OUTRO:
             * usamos a conta de perda configurada.
             */

                $contaPerdaId =
                    in_array(
                        $tipo,
                        [
                            'ABATE',
                            'FIM_VIDA_UTIL'
                        ],
                        true
                    )
                    ? (int)$configBaixa['conta_abate_id']
                    : (int)$configBaixa['conta_perda_alienacao_id'];


                $itens[] = [
                    'conta_id' =>
                    $contaPerdaId,

                    'debito' =>
                    $valorLiquido,

                    'credito' =>
                    0
                ];
            }
        }


        /*
     * 3. Retirar o próprio ativo do balanço.
     */

        $itens[] = [
            'conta_id' =>
            (int)$ativo['conta_contabil_id'],

            'debito' =>
            0,

            'credito' =>
            $valorAquisicao
        ];


        // ==============================================
        // TOTALIZAR
        // ==============================================

        $totalDebito =
            0.0;

        $totalCredito =
            0.0;


        foreach ($itens as $item) {

            $totalDebito +=
                (float)$item['debito'];

            $totalCredito +=
                (float)$item['credito'];
        }


        $totalDebito =
            round(
                $totalDebito,
                2
            );

        $totalCredito =
            round(
                $totalCredito,
                2
            );


        if (
            abs(
                $totalDebito
                    - $totalCredito
            ) > 0.01
        ) {

            throw new RuntimeException(
                'O lançamento da baixa não está balanceado. '
                    . 'Débito: '
                    . number_format(
                        $totalDebito,
                        2,
                        ',',
                        '.'
                    )
                    . ' | Crédito: '
                    . number_format(
                        $totalCredito,
                        2,
                        ',',
                        '.'
                    )
            );
        }


        // ==============================================
        // CABEÇALHO DO LANÇAMENTO
        // ==============================================

        $stmt =
            $this->pdo->prepare("
            INSERT INTO lancamentos_contabilisticos
            (
                empresa_id,
                filial_id,
                exercicio_id,
                periodo_id,
                diario_id,
                numero,
                referencia,
                data_lancamento,
                data_documento,
                descricao,
                origem,
                origem_id,
                status_id,
                total_debito,
                total_credito,
                contabilizado_em,
                contabilizado_por,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :exercicio_id,
                :periodo_id,
                :diario_id,
                :numero,
                :referencia,
                :data_lancamento,
                :data_documento,
                :descricao,
                'ATIVO',
                :origem_id,
                :status_id,
                :total_debito,
                :total_credito,
                NOW(),
                :contabilizado_por,
                :criado_por,
                :atualizado_por
            )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            !empty($ativo['filial_id'])
                ? (int)$ativo['filial_id']
                : null,

            ':exercicio_id' =>
            $contexto['exercicio_id'],

            ':periodo_id' =>
            $contexto['periodo_id'],

            ':diario_id' =>
            (int)$diario['id'],

            ':numero' =>
            $numero,

            ':referencia' =>
            'BAIXA-ATIVO-'
                . $baixaId,

            ':data_lancamento' =>
            $dataBaixa,

            ':data_documento' =>
            $dataBaixa,

            ':descricao' =>
            $descricao,

            ':origem_id' =>
            $baixaId,

            ':status_id' =>
            $statusId,

            ':total_debito' =>
            $totalDebito,

            ':total_credito' =>
            $totalCredito,

            ':contabilizado_por' =>
            $usuarioId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);


        $lancamentoId =
            (int)$this->pdo
                ->lastInsertId();


        // ==============================================
        // PARTIDAS
        // ==============================================

        $ordem =
            1;


        foreach ($itens as $item) {

            $this->inserirItemLancamentoBaixa(
                $lancamentoId,
                (int)$item['conta_id'],
                !empty($ativo['centro_custo_id'])
                    ? (int)$ativo['centro_custo_id']
                    : null,
                $descricao,
                (float)$item['debito'],
                (float)$item['credito'],
                $ordem
            );


            $ordem++;
        }


        return $lancamentoId;
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {

        $dados =
            $this->normalizarDados(
                $dados
            );


        $this->validarDados(
            $empresaId,
            $dados
        );


        $codigo =
            trim(
                (string)(
                    $dados['codigo']
                    ?? ''
                )
            );


        if ($codigo === '') {

            $codigo =
                $this->proximoCodigo(
                    $empresaId
                );
        }


        $stmt =
            $this->pdo->prepare("
                INSERT INTO ativos_empresa
                (
                    empresa_id,
                    filial_id,
                    conta_contabil_id,
                    codigo,
                    tipo,
                    descricao,
                    marca,
                    modelo,
                    numero_serie,
                    data_aquisicao,
                    data_entrada_uso,
                    valor_aquisicao,
                    valor_residual,
                    vida_util_meses,
                    metodo_depreciacao,
                    taxa_anual,
                    departamento_id,
                    localizacao,
                    responsavel,
                    fornecedor_id,
                    numero_documento,
                    estado_fisico,
                    situacao,
                    observacoes,
                    criado_por,
                    atualizado_por
                )
                VALUES
                (
                    :empresa_id,
                    :filial_id,
                    :conta_contabil_id,
                    :codigo,
                    :tipo,
                    :descricao,
                    :marca,
                    :modelo,
                    :numero_serie,
                    :data_aquisicao,
                    :data_entrada_uso,
                    :valor_aquisicao,
                    :valor_residual,
                    :vida_util_meses,
                    :metodo_depreciacao,
                    :taxa_anual,
                    :departamento_id,
                    :localizacao,
                    :responsavel,
                    :fornecedor_id,
                    :numero_documento,
                    :estado_fisico,
                    :situacao,
                    :observacoes,
                    :criado_por,
                    :atualizado_por
                )
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            $dados['filial_id'],

            ':conta_contabil_id' =>
            $dados['conta_contabil_id'],

            ':codigo' =>
            $codigo,

            ':tipo' =>
            $dados['tipo'],

            ':descricao' =>
            $dados['descricao'],

            ':marca' =>
            $dados['marca'],

            ':modelo' =>
            $dados['modelo'],

            ':numero_serie' =>
            $dados['numero_serie'],

            ':data_aquisicao' =>
            $dados['data_aquisicao'],

            ':data_entrada_uso' =>
            $dados['data_entrada_uso'],

            ':valor_aquisicao' =>
            $dados['valor_aquisicao'],

            ':valor_residual' =>
            $dados['valor_residual'],

            ':vida_util_meses' =>
            $dados['vida_util_meses'],

            ':metodo_depreciacao' =>
            $dados['metodo_depreciacao'],

            ':taxa_anual' =>
            $dados['taxa_anual'],

            ':departamento_id' =>
            $dados['departamento_id'],

            ':localizacao' =>
            $dados['localizacao'],

            ':responsavel' =>
            $dados['responsavel'],

            ':fornecedor_id' =>
            $dados['fornecedor_id'],

            ':numero_documento' =>
            $dados['numero_documento'],

            ':estado_fisico' =>
            $dados['estado_fisico'],

            ':situacao' =>
            $dados['situacao'],

            ':observacoes' =>
            $dados['observacoes'],

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);


        $ativoId =
            (int)$this->pdo
                ->lastInsertId();


        /*
         * Regista automaticamente
         * a primeira movimentação.
         */

        $this->registarEntradaInicial(
            $empresaId,
            $usuarioId,
            $ativoId,
            $dados
        );


        return $ativoId;
    }

    // ==================================================
    // ATIVOS DISPONÍVEIS PARA DEPRECIAÇÃO / AMORTIZAÇÃO
    // ==================================================

    public function listarParaDepreciacao(
        int $empresaId,
        array $filtros = []
    ): array {

        $ano =
            (int)(
                $filtros['ano']
                ?? date('Y')
            );

        $mes =
            (int)(
                $filtros['mes']
                ?? date('n')
            );


        if ($ano <= 0) {
            $ano = (int)date('Y');
        }


        if (
            $mes < 1
            || $mes > 12
        ) {
            $mes = (int)date('n');
        }


        $sql = "
            SELECT

                ativo.id,
                ativo.codigo,
                ativo.tipo,
                ativo.descricao,
                ativo.valor_aquisicao,
                ativo.valor_residual,
                ativo.vida_util_meses,
                ativo.taxa_anual,
                ativo.data_entrada_uso,
                ativo.situacao,

                conta.codigo AS conta_codigo,
                conta.nome AS categoria_nome,

                COALESCE(
                    (
                        SELECT
                            SUM(dep.valor_periodo)

                        FROM ativos_depreciacoes dep

                        WHERE dep.empresa_id =
                            ativo.empresa_id

                        AND dep.ativo_id =
                            ativo.id

                        AND dep.lancamento_estorno_id
                            IS NULL
                    ),
                    0
                ) AS valor_acumulado,

                EXISTS(
                    SELECT 1

                    FROM ativos_depreciacoes dep_processado

                    WHERE dep_processado.empresa_id =
                        ativo.empresa_id

                    AND dep_processado.ativo_id =
                        ativo.id

                    AND dep_processado.ano =
                        :ano_processado

                    AND dep_processado.mes =
                        :mes_processado

                    AND dep_processado.lancamento_estorno_id
                        IS NULL
                ) AS periodo_processado,

                (
                    SELECT
                        dep_periodo.id

                    FROM ativos_depreciacoes dep_periodo

                    WHERE dep_periodo.empresa_id =
                        ativo.empresa_id

                    AND dep_periodo.ativo_id =
                        ativo.id

                    AND dep_periodo.ano =
                        :ano_periodo

                    AND dep_periodo.mes =
                        :mes_periodo

                    AND dep_periodo.lancamento_estorno_id
                        IS NULL

                    ORDER BY
                        dep_periodo.id DESC

                    LIMIT 1
                ) AS depreciacao_periodo_id

            FROM ativos_empresa ativo

            INNER JOIN contas_contabilisticas conta
                ON conta.id =
                    ativo.conta_contabil_id

            AND conta.empresa_id =
                    ativo.empresa_id

            WHERE ativo.empresa_id =
                :empresa_id

            AND ativo.situacao NOT IN (
                    'VENDIDO',
                    'ABATIDO'
            )

            AND ativo.vida_util_meses IS NOT NULL

            AND ativo.vida_util_meses > 0

            AND ativo.valor_aquisicao >
                    ativo.valor_residual
        ";


        $params = [

            ':empresa_id' =>
            $empresaId,

            ':ano_processado' =>
            $ano,

            ':mes_processado' =>
            $mes,

            ':ano_periodo' =>
            $ano,

            ':mes_periodo' =>
            $mes
        ];


        if (
            !empty($filtros['tipo'])
        ) {

            $sql .= "
            AND ativo.tipo =
                :tipo
        ";

            $params[':tipo'] =
                strtoupper(
                    trim(
                        (string)$filtros['tipo']
                    )
                );
        }


        if (
            !empty($filtros['pesquisa'])
        ) {

            $sql .= "
            AND (
                ativo.codigo
                    LIKE :pesquisa

                OR ativo.descricao
                    LIKE :pesquisa

                OR conta.nome
                    LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . trim(
                    (string)$filtros['pesquisa']
                )
                . '%';
        }


        $sql .= "
        ORDER BY
            ativo.codigo
    ";


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $dados =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach ($dados as &$ativo) {

            $valorAquisicao =
                (float)$ativo['valor_aquisicao'];


            $valorResidual =
                (float)$ativo['valor_residual'];


            $acumulado =
                (float)$ativo['valor_acumulado'];


            $vidaUtilMeses =
                (int)$ativo['vida_util_meses'];


            $baseDepreciavel =
                max(
                    0,
                    $valorAquisicao
                        - $valorResidual
                );


            $valorMensal =
                $vidaUtilMeses > 0
                ? (
                    $baseDepreciavel
                    / $vidaUtilMeses
                )
                : 0;


            $valorLiquido =
                max(
                    $valorResidual,
                    $valorAquisicao
                        - $acumulado
                );


            $saldoDepreciavel =
                max(
                    0,
                    $valorLiquido
                        - $valorResidual
                );


            $valorPeriodo =
                min(
                    $valorMensal,
                    $saldoDepreciavel
                );


            $ativo['valor_mensal'] =
                round(
                    $valorMensal,
                    2
                );


            $ativo['valor_anual'] =
                round(
                    $valorMensal * 12,
                    2
                );


            $ativo['valor_liquido'] =
                round(
                    $valorLiquido,
                    2
                );


            $ativo['saldo_depreciavel'] =
                round(
                    $saldoDepreciavel,
                    2
                );


            $ativo['valor_periodo'] =
                round(
                    $valorPeriodo,
                    2
                );


            $ativo['periodo_processado'] =
                (bool)$ativo['periodo_processado'];


            $ativo['tipo_processamento'] =
                $ativo['tipo']
                === 'INTANGIVEL'
                ? 'AMORTIZACAO'
                : 'DEPRECIACAO';
        }


        unset($ativo);


        return $dados;
    }

    // ==================================================
    // PROCESSAR DEPRECIAÇÃO / AMORTIZAÇÃO
    // ==================================================

    public function processarDepreciacao(
        int $empresaId,
        int $usuarioId,
        int $ativoId,
        int $ano,
        int $mes
    ): array {

        if (
            $ano <= 0
            || $mes < 1
            || $mes > 12
        ) {

            throw new RuntimeException(
                'Período inválido.'
            );
        }


        $ativo =
            $this->buscar(
                $empresaId,
                $ativoId
            );


        if ($ativo === null) {

            throw new RuntimeException(
                'Ativo não encontrado.'
            );
        }


        if (
            in_array(
                $ativo['situacao'],
                [
                    'VENDIDO',
                    'ABATIDO'
                ],
                true
            )
        ) {

            throw new RuntimeException(
                'Este ativo já não pode ser depreciado/amortizado.'
            );
        }


        if (
            empty($ativo['vida_util_meses'])
        ) {

            throw new RuntimeException(
                'O ativo não possui vida útil definida.'
            );
        }


        /*
     * A depreciação/amortização só começa
     * quando o ativo entra em utilização.
     */

        $dataInicio =
            $ativo['data_entrada_uso']
            ?: $ativo['data_aquisicao'];


        $ultimoDiaPeriodo =
            sprintf(
                '%04d-%02d-%02d',
                $ano,
                $mes,
                cal_days_in_month(
                    CAL_GREGORIAN,
                    $mes,
                    $ano
                )
            );


        if (
            $dataInicio
            && $dataInicio
            > $ultimoDiaPeriodo
        ) {

            throw new RuntimeException(
                'O ativo ainda não estava em utilização neste período.'
            );
        }


        /*
     * Evita duplicidade.
     */

        $stmt =
            $this->pdo->prepare("
        SELECT id

        FROM ativos_depreciacoes

        WHERE empresa_id =
            :empresa_id

          AND ativo_id =
            :ativo_id

          AND ano =
            :ano

          AND mes =
            :mes

          AND lancamento_estorno_id
              IS NULL

        LIMIT 1
    ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':ativo_id' =>
            $ativoId,

            ':ano' =>
            $ano,

            ':mes' =>
            $mes
        ]);


        if ($stmt->fetchColumn()) {

            throw new RuntimeException(
                'Este ativo já foi processado para o período selecionado.'
            );
        }


        $valorAquisicao =
            (float)$ativo['valor_aquisicao'];


        $valorResidual =
            (float)$ativo['valor_residual'];


        $vidaUtilMeses =
            (int)$ativo['vida_util_meses'];


        $acumuladoAnterior =
            $this->depreciacaoAcumulada(
                $empresaId,
                $ativoId
            );


        $base =
            max(
                0,
                $valorAquisicao
                    - $valorResidual
            );


        $valorMensal =
            $vidaUtilMeses > 0
            ? $base / $vidaUtilMeses
            : 0;


        $valorLiquidoAnterior =
            max(
                $valorResidual,
                $valorAquisicao
                    - $acumuladoAnterior
            );


        $saldoDepreciavel =
            max(
                0,
                $valorLiquidoAnterior
                    - $valorResidual
            );


        if ($saldoDepreciavel <= 0) {

            throw new RuntimeException(
                'O ativo já atingiu o seu valor residual.'
            );
        }


        $valorPeriodo =
            round(
                min(
                    $valorMensal,
                    $saldoDepreciavel
                ),
                2
            );


        if ($valorPeriodo <= 0) {

            throw new RuntimeException(
                'Não existe valor a depreciar/amortizar neste período.'
            );
        }


        $novoAcumulado =
            round(
                $acumuladoAnterior
                    + $valorPeriodo,
                2
            );


        $novoValorLiquido =
            round(
                max(
                    $valorResidual,
                    $valorAquisicao
                        - $novoAcumulado
                ),
                2
            );


        $tipo =
            $ativo['tipo']
            === 'INTANGIVEL'
            ? 'AMORTIZACAO'
            : 'DEPRECIACAO';



        $this->pdo->beginTransaction();

        try {

            /*
     * 1. Calcula normalmente
     */

            $valorPeriodo =
                round(
                    $valorPeriodo,
                    2
                );

            /*
     * 2. Inserir depreciação
     */

            $stmt =
                $this->pdo->prepare("
            INSERT INTO ativos_depreciacoes
            (
                empresa_id,
                ativo_id,
                ano,
                mes,
                tipo,
                valor_base,
                valor_periodo,
                valor_acumulado,
                valor_liquido,
                processado_por
            )
            VALUES
            (
                :empresa_id,
                :ativo_id,
                :ano,
                :mes,
                :tipo,
                :valor_base,
                :valor_periodo,
                :valor_acumulado,
                :valor_liquido,
                :usuario
            )
        ");

            $stmt->execute([
                ':empresa_id' => $empresaId,
                ':ativo_id' => $ativoId,
                ':ano' => $ano,
                ':mes' => $mes,
                ':tipo' => $tipo,
                ':valor_base' => $base,
                ':valor_periodo' => $valorPeriodo,
                ':valor_acumulado' => $novoAcumulado,
                ':valor_liquido' => $novoValorLiquido,
                ':usuario' => $usuarioId
            ]);

            $depreciacaoId =
                (int)$this->pdo->lastInsertId();

            /*
     * 3. Buscar configuração
     */

            $config =
                $this->obterConfiguracaoContabil(
                    $empresaId,
                    (int)$ativo['conta_contabil_id']
                );

            /*
     * 4. Criar lançamento
     */

            $lancamentoId =
                $this->criarLancamentoDepreciacao(
                    $empresaId,
                    $usuarioId,
                    $depreciacaoId,
                    $ativo,
                    $config,
                    $valorPeriodo,
                    $ano,
                    $mes,
                    $tipo
                );

            /*
     * 5. Atualizar ligação
     */

            $stmt =
                $this->pdo->prepare("
            UPDATE ativos_depreciacoes
            SET lancamento_contabil_id =
                :lancamento
            WHERE id =
                :id
        ");

            $stmt->execute([
                ':lancamento' => $lancamentoId,
                ':id' => $depreciacaoId
            ]);

            $this->pdo->commit();

            return [
                'sucesso' => true,
                'depreciacao_id' => $depreciacaoId,
                'lancamento_contabil_id' => $lancamentoId
            ];
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // EXERCÍCIO E PERÍODO CONTABILÍSTICO
    // ==================================================

    private function obterExercicioPeriodo(
        int $empresaId,
        string $dataLancamento
    ): array {

        /*
     * EXERCÍCIO
     */

        $stmt =
            $this->pdo->prepare("
            SELECT
                e.id

            FROM exercicios_contabilisticos e

            INNER JOIN status s
                ON s.id = e.status_id

            WHERE e.empresa_id =
                :empresa_id

              AND :data_lancamento
                  BETWEEN e.data_inicio
                  AND e.data_fim

              AND s.tipo =
                  'exercicio_contabilistico'

              AND LOWER(s.nome)
                  IN (
                      'aberto',
                      'em fecho'
                  )

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':data_lancamento' =>
            $dataLancamento
        ]);


        $exercicioId =
            (int)(
                $stmt->fetchColumn()
                ?: 0
            );


        if ($exercicioId <= 0) {

            throw new RuntimeException(
                'Não existe exercício contabilístico aberto para '
                    . $dataLancamento
                    . '.'
            );
        }


        /*
     * PERÍODO
     */

        $stmt =
            $this->pdo->prepare("
            SELECT
                p.id

            FROM periodos_contabilisticos p

            INNER JOIN status s
                ON s.id = p.status_id

            WHERE p.empresa_id =
                :empresa_id

              AND p.exercicio_id =
                :exercicio_id

              AND :data_lancamento
                  BETWEEN p.data_inicio
                  AND p.data_fim

              AND s.tipo =
                  'periodo_contabilistico'

              AND LOWER(s.nome) =
                  'aberto'

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioId,

            ':data_lancamento' =>
            $dataLancamento
        ]);


        $periodoId =
            (int)(
                $stmt->fetchColumn()
                ?: 0
            );


        if ($periodoId <= 0) {

            throw new RuntimeException(
                'Não existe período contabilístico aberto para '
                    . $dataLancamento
                    . '.'
            );
        }


        return [
            'exercicio_id' =>
            $exercicioId,

            'periodo_id' =>
            $periodoId
        ];
    }

    // ==================================================
    // PROCESSAR EM LOTE
    // ==================================================

    public function processarDepreciacoesLote(
        int $empresaId,
        int $usuarioId,
        array $ativosIds,
        int $ano,
        int $mes
    ): array {

        $processados =
            [];

        $ignorados =
            [];

        $erros =
            [];


        foreach ($ativosIds as $ativoId) {

            $ativoId =
                (int)$ativoId;


            if ($ativoId <= 0) {
                continue;
            }


            try {

                $processados[] =
                    $this
                    ->processarDepreciacao(
                        $empresaId,
                        $usuarioId,
                        $ativoId,
                        $ano,
                        $mes
                    );
            } catch (
                RuntimeException $e
            ) {

                $ignorados[] = [
                    'ativo_id' =>
                    $ativoId,

                    'mensagem' =>
                    $e->getMessage()
                ];
            } catch (
                Throwable $e
            ) {

                $erros[] = [
                    'ativo_id' =>
                    $ativoId,

                    'mensagem' =>
                    $e->getMessage()
                ];
            }
        }


        return [

            'total_processados' =>
            count(
                $processados
            ),

            'total_ignorados' =>
            count(
                $ignorados
            ),

            'total_erros' =>
            count(
                $erros
            ),

            'processados' =>
            $processados,

            'ignorados' =>
            $ignorados,

            'erros' =>
            $erros
        ];
    }

    // ==================================================
    // LISTAR CONFIGURAÇÕES CONTABILÍSTICAS
    // ==================================================

    public function listarConfiguracoesContabeis(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                config.id,
                config.conta_ativo_id,
                config.conta_acumulada_id,
                config.conta_gasto_id,

                ativo.codigo AS conta_ativo_codigo,
                ativo.nome AS conta_ativo_nome,

                acumulada.codigo AS conta_acumulada_codigo,
                acumulada.nome AS conta_acumulada_nome,

                gasto.codigo AS conta_gasto_codigo,
                gasto.nome AS conta_gasto_nome

            FROM ativos_configuracoes_contabeis config

            INNER JOIN contas_contabilisticas ativo
                ON ativo.id =
                    config.conta_ativo_id

            LEFT JOIN contas_contabilisticas acumulada
                ON acumulada.id =
                    config.conta_acumulada_id

            LEFT JOIN contas_contabilisticas gasto
                ON gasto.id =
                    config.conta_gasto_id

            WHERE config.empresa_id =
                :empresa_id

            ORDER BY
                ativo.codigo
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
    // GUARDAR CONFIGURAÇÃO CONTABILÍSTICA
    // ==================================================

    public function guardarConfiguracaoContabil(
        int $empresaId,
        array $dados
    ): int {

        $contaAtivoId =
            (int)(
                $dados['conta_ativo_id']
                ?? 0
            );

        $contaAcumuladaId =
            (int)(
                $dados['conta_acumulada_id']
                ?? 0
            );

        $contaGastoId =
            (int)(
                $dados['conta_gasto_id']
                ?? 0
            );


        if ($contaAtivoId <= 0) {

            throw new RuntimeException(
                'Informe a conta do ativo.'
            );
        }


        if ($contaAcumuladaId <= 0) {

            throw new RuntimeException(
                'Informe a conta de depreciação/amortização acumulada.'
            );
        }


        if ($contaGastoId <= 0) {

            throw new RuntimeException(
                'Informe a conta de gasto.'
            );
        }


        /*
     * Confirma que todas pertencem
     * à empresa.
     */

        foreach (
            [
                $contaAtivoId,
                $contaAcumuladaId,
                $contaGastoId
            ] as $contaId
        ) {

            $stmt =
                $this->pdo->prepare("
                SELECT id

                FROM contas_contabilisticas

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");


            $stmt->execute([
                ':id' =>
                $contaId,

                ':empresa_id' =>
                $empresaId
            ]);


            if (!$stmt->fetchColumn()) {

                throw new RuntimeException(
                    'Uma das contas contabilísticas selecionadas é inválida.'
                );
            }
        }


        /*
     * Se a categoria já estiver configurada,
     * atualizamos.
     */

        $stmt =
            $this->pdo->prepare("
            SELECT id

            FROM ativos_configuracoes_contabeis

            WHERE empresa_id =
                :empresa_id

              AND conta_ativo_id =
                :conta_ativo_id

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':conta_ativo_id' =>
            $contaAtivoId
        ]);


        $configId =
            (int)(
                $stmt->fetchColumn()
                ?: 0
            );


        if ($configId > 0) {

            $stmt =
                $this->pdo->prepare("
                UPDATE ativos_configuracoes_contabeis

                SET
                    conta_acumulada_id =
                        :conta_acumulada_id,

                    conta_gasto_id =
                        :conta_gasto_id

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':conta_acumulada_id' =>
                $contaAcumuladaId,

                ':conta_gasto_id' =>
                $contaGastoId,

                ':id' =>
                $configId,

                ':empresa_id' =>
                $empresaId
            ]);


            return $configId;
        }


        $stmt =
            $this->pdo->prepare("
            INSERT INTO ativos_configuracoes_contabeis
            (
                empresa_id,
                conta_ativo_id,
                conta_acumulada_id,
                conta_gasto_id
            )
            VALUES
            (
                :empresa_id,
                :conta_ativo_id,
                :conta_acumulada_id,
                :conta_gasto_id
            )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':conta_ativo_id' =>
            $contaAtivoId,

            ':conta_acumulada_id' =>
            $contaAcumuladaId,

            ':conta_gasto_id' =>
            $contaGastoId
        ]);


        return (int)$this->pdo
            ->lastInsertId();
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $empresaId,
        int $usuarioId,
        int $ativoId,
        array $dados
    ): void {

        $atual =
            $this->buscar(
                $empresaId,
                $ativoId
            );


        if ($atual === null) {

            throw new RuntimeException(
                'Ativo não encontrado.'
            );
        }


        $dados =
            $this->normalizarDados(
                $dados
            );


        $this->validarDados(
            $empresaId,
            $dados
        );


        $stmt =
            $this->pdo->prepare("
                UPDATE ativos_empresa

                SET
                    filial_id =
                        :filial_id,

                    conta_contabil_id =
                        :conta_contabil_id,

                    tipo =
                        :tipo,

                    descricao =
                        :descricao,

                    marca =
                        :marca,

                    modelo =
                        :modelo,

                    numero_serie =
                        :numero_serie,

                    data_aquisicao =
                        :data_aquisicao,

                    data_entrada_uso =
                        :data_entrada_uso,

                    valor_aquisicao =
                        :valor_aquisicao,

                    valor_residual =
                        :valor_residual,

                    vida_util_meses =
                        :vida_util_meses,

                    metodo_depreciacao =
                        :metodo_depreciacao,

                    taxa_anual =
                        :taxa_anual,

                    departamento_id =
                        :departamento_id,

                    localizacao =
                        :localizacao,

                    responsavel =
                        :responsavel,

                    fornecedor_id =
                        :fornecedor_id,

                    numero_documento =
                        :numero_documento,

                    estado_fisico =
                        :estado_fisico,

                    situacao =
                        :situacao,

                    observacoes =
                        :observacoes,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


        $stmt->execute([
            ':filial_id' =>
            $dados['filial_id'],

            ':conta_contabil_id' =>
            $dados['conta_contabil_id'],

            ':tipo' =>
            $dados['tipo'],

            ':descricao' =>
            $dados['descricao'],

            ':marca' =>
            $dados['marca'],

            ':modelo' =>
            $dados['modelo'],

            ':numero_serie' =>
            $dados['numero_serie'],

            ':data_aquisicao' =>
            $dados['data_aquisicao'],

            ':data_entrada_uso' =>
            $dados['data_entrada_uso'],

            ':valor_aquisicao' =>
            $dados['valor_aquisicao'],

            ':valor_residual' =>
            $dados['valor_residual'],

            ':vida_util_meses' =>
            $dados['vida_util_meses'],

            ':metodo_depreciacao' =>
            $dados['metodo_depreciacao'],

            ':taxa_anual' =>
            $dados['taxa_anual'],

            ':departamento_id' =>
            $dados['departamento_id'],

            ':localizacao' =>
            $dados['localizacao'],

            ':responsavel' =>
            $dados['responsavel'],

            ':fornecedor_id' =>
            $dados['fornecedor_id'],

            ':numero_documento' =>
            $dados['numero_documento'],

            ':estado_fisico' =>
            $dados['estado_fisico'],

            ':situacao' =>
            $dados['situacao'],

            ':observacoes' =>
            $dados['observacoes'],

            ':atualizado_por' =>
            $usuarioId,

            ':id' =>
            $ativoId,

            ':empresa_id' =>
            $empresaId
        ]);


        /*
         * Detecta mudanças de localização,
         * filial, departamento ou responsável.
         */

        $this->registarMudancaSeNecessario(
            $empresaId,
            $usuarioId,
            $ativoId,
            $atual,
            $dados
        );
    }

    // ==================================================
    // PRÓXIMO CÓDIGO
    // ==================================================

    public function proximoCodigo(
        int $empresaId
    ): string {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    COALESCE(
                        MAX(id),
                        0
                    )

                FROM ativos_empresa

                WHERE empresa_id =
                    :empresa_id
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        $sequencia =
            (int)$stmt->fetchColumn()
            + 1;


        return sprintf(
            'ATV-%05d',
            $sequencia
        );
    }

    // ==================================================
    // DEPRECIAÇÃO ACUMULADA
    // ==================================================

    public function depreciacaoAcumulada(
        int $empresaId,
        int $ativoId
    ): float {

        $stmt =
            $this->pdo->prepare("
            SELECT
                COALESCE(
                    SUM(valor_periodo),
                    0
                )

            FROM ativos_depreciacoes

            WHERE empresa_id =
                :empresa_id

              AND ativo_id =
                :ativo_id

              AND lancamento_estorno_id
                  IS NULL
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':ativo_id' =>
            $ativoId
        ]);


        return round(
            (float)$stmt->fetchColumn(),
            2
        );
    }

    private function buscarDepreciacao(
        int $empresaId,
        int $depreciacaoId,
        bool $bloquear = false
    ): ?array {

        $sql = "
        SELECT
            dep.*,

            ativo.codigo AS ativo_codigo,
            ativo.descricao AS ativo_descricao,
            ativo.filial_id,

            lancamento.diario_id,
            lancamento.numero AS lancamento_numero,
            lancamento.descricao AS lancamento_descricao

        FROM ativos_depreciacoes dep

        INNER JOIN ativos_empresa ativo
            ON ativo.id =
                dep.ativo_id

           AND ativo.empresa_id =
                dep.empresa_id

        LEFT JOIN lancamentos_contabilisticos lancamento
            ON lancamento.id =
                dep.lancamento_contabil_id

        WHERE dep.id =
            :id

          AND dep.empresa_id =
            :empresa_id

        LIMIT 1
    ";


        if ($bloquear) {

            $sql .= "
            FOR UPDATE
        ";
        }


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute([
            ':id' =>
            $depreciacaoId,

            ':empresa_id' =>
            $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }

    private function statusLancamentoPorNome(
        string $nome
    ): ?int {

        $stmt =
            $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                'lancamento_contabilistico'

              AND LOWER(nome) =
                LOWER(:nome)

            LIMIT 1
        ");


        $stmt->execute([
            ':nome' =>
            trim($nome)
        ]);


        $id =
            $stmt->fetchColumn();


        return $id !== false
            ? (int)$id
            : null;
    }

    private function criarLancamentoEstornoDepreciacao(
        int $empresaId,
        int $usuarioId,
        array $depreciacao,
        string $dataEstorno,
        string $motivo
    ): int {

        $lancamentoOriginalId =
            (int)(
                $depreciacao['lancamento_contabil_id']
                ?? 0
            );


        if ($lancamentoOriginalId <= 0) {

            throw new RuntimeException(
                'A depreciação/amortização não possui lançamento contabilístico.'
            );
        }


        // ==========================================
        // LANÇAMENTO ORIGINAL
        // ==========================================

        $stmt =
            $this->pdo->prepare("
            SELECT *

            FROM lancamentos_contabilisticos

            WHERE id =
                :id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");


        $stmt->execute([
            ':id' =>
            $lancamentoOriginalId,

            ':empresa_id' =>
            $empresaId
        ]);


        $original =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$original) {

            throw new RuntimeException(
                'Lançamento contabilístico original não encontrado.'
            );
        }


        // ==========================================
        // ITENS ORIGINAIS
        // ==========================================

        $stmt =
            $this->pdo->prepare("
            SELECT
                conta_contabil_id,
                centro_custo_id,
                descricao,
                debito,
                credito,
                ordem

            FROM lancamento_contabilistico_itens

            WHERE lancamento_id =
                :lancamento_id

            ORDER BY ordem, id
        ");


        $stmt->execute([
            ':lancamento_id' =>
            $lancamentoOriginalId
        ]);


        $itens =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        if (!$itens) {

            throw new RuntimeException(
                'O lançamento original não possui partidas contabilísticas.'
            );
        }


        // ==========================================
        // CONTEXTO CONTABILÍSTICO
        // ==========================================

        $contexto =
            $this->obterContextoContabil(
                $empresaId,
                $dataEstorno
            );


        /*
     * Mantemos o mesmo diário do lançamento original.
     */

        $diarioId =
            (int)$original['diario_id'];


        $numero =
            $this->proximoNumeroDiario(
                $diarioId
            );


        $descricao =
            'Estorno da '
            . (
                $depreciacao['tipo']
                === 'AMORTIZACAO'
                ? 'amortização'
                : 'depreciação'
            )
            . ' do ativo '
            . $depreciacao['ativo_codigo']
            . ' - '
            . $depreciacao['ativo_descricao'];


        if ($motivo !== '') {

            $descricao .=
                '. Motivo: '
                . $motivo;
        }


        $totalDebito =
            0.0;

        $totalCredito =
            0.0;


        foreach ($itens as $item) {

            /*
         * Inversão:
         *
         * débito do estorno = crédito original
         * crédito do estorno = débito original
         */

            $totalDebito +=
                (float)$item['credito'];

            $totalCredito +=
                (float)$item['debito'];
        }


        $stmt =
            $this->pdo->prepare("
            INSERT INTO lancamentos_contabilisticos
            (
                empresa_id,
                filial_id,
                exercicio_id,
                periodo_id,
                diario_id,
                numero,
                referencia,
                data_lancamento,
                data_documento,
                descricao,
                origem,
                origem_id,
                status_id,
                total_debito,
                total_credito,
                contabilizado_em,
                contabilizado_por,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                :empresa_id,
                :filial_id,
                :exercicio_id,
                :periodo_id,
                :diario_id,
                :numero,
                :referencia,
                :data_lancamento,
                :data_documento,
                :descricao,
                'ESTORNO',
                :origem_id,
                :status_id,
                :total_debito,
                :total_credito,
                NOW(),
                :contabilizado_por,
                :criado_por,
                :atualizado_por
            )
        ");


        /*
     * O novo lançamento deve ficar contabilizado.
     */

        $statusContabilizado =
            $this->statusLancamentoPorNome(
                'Contabilizado'
            );


        if ($statusContabilizado === null) {

            throw new RuntimeException(
                'Status Contabilizado não encontrado.'
            );
        }


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            $depreciacao['filial_id']
                ?: null,

            ':exercicio_id' =>
            $contexto['exercicio_id'],

            ':periodo_id' =>
            $contexto['periodo_id'],

            ':diario_id' =>
            $diarioId,

            ':numero' =>
            $numero,

            ':referencia' =>
            'ESTORNO-ATIVO-'
                . $depreciacao['id'],

            ':data_lancamento' =>
            $dataEstorno,

            ':data_documento' =>
            $dataEstorno,

            ':descricao' =>
            $descricao,

            /*
         * origem_id aponta para o lançamento
         * que estamos a estornar.
         */
            ':origem_id' =>
            $lancamentoOriginalId,

            ':status_id' =>
            $statusContabilizado,

            ':total_debito' =>
            round(
                $totalDebito,
                2
            ),

            ':total_credito' =>
            round(
                $totalCredito,
                2
            ),

            ':contabilizado_por' =>
            $usuarioId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);


        $lancamentoEstornoId =
            (int)$this->pdo
                ->lastInsertId();


        // ==========================================
        // PARTIDAS INVERSAS
        // ==========================================

        $stmtItem =
            $this->pdo->prepare("
            INSERT INTO lancamento_contabilistico_itens
            (
                lancamento_id,
                conta_contabil_id,
                centro_custo_id,
                descricao,
                debito,
                credito,
                ordem
            )
            VALUES
            (
                :lancamento_id,
                :conta_contabil_id,
                :centro_custo_id,
                :descricao,
                :debito,
                :credito,
                :ordem
            )
        ");


        $ordem =
            1;


        foreach ($itens as $item) {

            $stmtItem->execute([
                ':lancamento_id' =>
                $lancamentoEstornoId,

                ':conta_contabil_id' =>
                (int)$item['conta_contabil_id'],

                ':centro_custo_id' =>
                !empty($item['centro_custo_id'])
                    ? (int)$item['centro_custo_id']
                    : null,

                ':descricao' =>
                'Estorno: '
                    . (
                        $item['descricao']
                        ?: $descricao
                    ),

                /*
             * INVERTE AS PARTIDAS
             */

                ':debito' =>
                round(
                    (float)$item['credito'],
                    2
                ),

                ':credito' =>
                round(
                    (float)$item['debito'],
                    2
                ),

                ':ordem' =>
                $ordem++
            ]);
        }


        return $lancamentoEstornoId;
    }

    public function estornarDepreciacao(
        int $empresaId,
        int $usuarioId,
        int $depreciacaoId,
        string $dataEstorno,
        string $motivo
    ): array {

        if ($depreciacaoId <= 0) {

            throw new RuntimeException(
                'Processamento inválido.'
            );
        }


        $dataEstorno =
            trim(
                $dataEstorno
            );


        $motivo =
            trim(
                $motivo
            );


        if ($dataEstorno === '') {

            throw new RuntimeException(
                'Informe a data do estorno.'
            );
        }


        if ($motivo === '') {

            throw new RuntimeException(
                'Informe o motivo do estorno.'
            );
        }


        $this->pdo
            ->beginTransaction();


        try {

            /*
         * Bloqueia o processamento.
         */

            $depreciacao =
                $this->buscarDepreciacao(
                    $empresaId,
                    $depreciacaoId,
                    true
                );


            if ($depreciacao === null) {

                throw new RuntimeException(
                    'Depreciação/Amortização não encontrada.'
                );
            }


            if (
                !empty($depreciacao['lancamento_estorno_id'])
            ) {

                throw new RuntimeException(
                    'Este processamento já foi estornado.'
                );
            }


            /*
         * Cria lançamento inverso.
         */

            $lancamentoEstornoId =
                $this
                ->criarLancamentoEstornoDepreciacao(
                    $empresaId,
                    $usuarioId,
                    $depreciacao,
                    $dataEstorno,
                    $motivo
                );


            /*
         * Marca processamento como estornado.
         */

            $stmt =
                $this->pdo->prepare("
                UPDATE ativos_depreciacoes

                SET
                    lancamento_estorno_id =
                        :lancamento_estorno_id,

                    motivo_estorno =
                        :motivo_estorno,

                    estornado_em =
                        NOW(),

                    estornado_por =
                        :estornado_por

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmt->execute([
                ':lancamento_estorno_id' =>
                $lancamentoEstornoId,

                ':motivo_estorno' =>
                $motivo,

                ':estornado_por' =>
                $usuarioId,

                ':id' =>
                $depreciacaoId,

                ':empresa_id' =>
                $empresaId
            ]);


            /*
         * Se existir status ESTORNADO,
         * atualizamos o lançamento original.
         */

            $statusEstornado =
                $this
                ->statusLancamentoPorNome(
                    'Estornado'
                );


            if ($statusEstornado !== null) {

                $stmt =
                    $this->pdo->prepare("
                    UPDATE lancamentos_contabilisticos

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
                    $statusEstornado,

                    ':usuario_id' =>
                    $usuarioId,

                    ':id' =>
                    (int)$depreciacao['lancamento_contabil_id'],

                    ':empresa_id' =>
                    $empresaId
                ]);
            }


            $this->pdo
                ->commit();


            return [
                'depreciacao_id' =>
                $depreciacaoId,

                'lancamento_original_id' =>
                (int)$depreciacao['lancamento_contabil_id'],

                'lancamento_estorno_id' =>
                $lancamentoEstornoId,

                'data_estorno' =>
                $dataEstorno,

                'motivo' =>
                $motivo
            ];
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {

                $this->pdo
                    ->rollBack();
            }


            throw $e;
        }
    }

    // ==================================================
    // CATEGORIAS CONTABILÍSTICAS
    // ==================================================

    private function listarCategorias(
        int $empresaId,
        string $tipo
    ): array {

        $prefixo =
            $tipo === 'TANGIVEL'
            ? '3.2'
            : '3.3';


        $stmt =
            $this->pdo->prepare("
            SELECT
                conta.id,
                conta.codigo,
                conta.nome,
                conta.grupo,
                conta.classe,

                conta.conta_pai_id,

                pai.codigo
                    AS conta_pai_codigo,

                pai.nome
                    AS conta_pai_nome,

                classe.codigo
                    AS classe_codigo,

                classe.nome
                    AS classe_nome

            FROM contas_contabilisticas conta

            LEFT JOIN contas_contabilisticas pai
                ON pai.id =
                    conta.conta_pai_id

               AND pai.empresa_id =
                    conta.empresa_id

            LEFT JOIN contas_contabilisticas classe
                ON classe.empresa_id =
                    conta.empresa_id

               AND classe.plano_contas_id =
                    conta.plano_contas_id

               AND classe.codigo =
                    conta.classe

            WHERE conta.empresa_id =
                :empresa_id

              AND conta.grupo =
                'ATIVO'

              AND conta.codigo LIKE
                :codigo

              AND conta.tipo =
                'ANALITICA'

              AND conta.aceita_lancamento =
                1

            ORDER BY
                conta.codigo
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':codigo' =>
            $prefixo . '%'
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
    // ==================================================
    // FILIAIS
    // ==================================================

    private function listarFiliais(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    codigo,
                    nome

                FROM filiais

                WHERE empresa_id =
                    :empresa_id

                ORDER BY nome
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
    // DEPARTAMENTOS
    // ==================================================

    private function listarDepartamentos(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    nome

                FROM departamentos

                WHERE empresa_id =
                    :empresa_id

                ORDER BY nome
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
    // FORNECEDORES
    // ==================================================

    private function listarFornecedores(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    nome

                FROM fornecedores

                WHERE empresa_id =
                    :empresa_id

                ORDER BY nome
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
    // NORMALIZAR
    // ==================================================

    private function normalizarDados(
        array $dados
    ): array {

        $tipo =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo']
                        ?? ''
                    )
                )
            );


        $vidaUtilMeses =
            isset(
                $dados['vida_util_meses']
            )
            ? (int)$dados['vida_util_meses']
            : 0;


        if (
            $vidaUtilMeses <= 0
            && !empty($dados['vida_util_anos'])
        ) {

            $vidaUtilMeses =
                (int)round(
                    (float)$dados['vida_util_anos'] * 12
                );
        }


        return [
            'codigo' =>
            trim(
                (string)(
                    $dados['codigo']
                    ?? ''
                )
            ),

            'tipo' =>
            $tipo,

            'conta_contabil_id' =>
            (int)(
                $dados['conta_contabil_id']
                ?? 0
            ),

            'descricao' =>
            trim(
                (string)(
                    $dados['descricao']
                    ?? ''
                )
            ),

            'marca' =>
            $this->textoOuNull(
                $dados['marca']
                    ?? null
            ),

            'modelo' =>
            $this->textoOuNull(
                $dados['modelo']
                    ?? null
            ),

            'numero_serie' =>
            $this->textoOuNull(
                $dados['numero_serie']
                    ?? null
            ),

            'data_aquisicao' =>
            trim(
                (string)(
                    $dados['data_aquisicao']
                    ?? ''
                )
            ),

            'data_entrada_uso' =>
            $this->textoOuNull(
                $dados['data_entrada_uso']
                    ?? null
            ),

            'valor_aquisicao' =>
            round(
                (float)(
                    $dados['valor_aquisicao']
                    ?? 0
                ),
                2
            ),

            'valor_residual' =>
            round(
                (float)(
                    $dados['valor_residual']
                    ?? 0
                ),
                2
            ),

            'vida_util_meses' =>
            $vidaUtilMeses > 0
                ? $vidaUtilMeses
                : null,

            'metodo_depreciacao' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['metodo_depreciacao']
                        ?? 'LINEAR'
                    )
                )
            ),

            'taxa_anual' =>
            $this->decimalOuNull(
                $dados['taxa_anual']
                    ?? null
            ),

            'filial_id' =>
            $this->inteiroOuNull(
                $dados['filial_id']
                    ?? null
            ),

            'departamento_id' =>
            $this->inteiroOuNull(
                $dados['departamento_id']
                    ?? null
            ),

            'localizacao' =>
            $this->textoOuNull(
                $dados['localizacao']
                    ?? null
            ),

            'responsavel' =>
            $this->textoOuNull(
                $dados['responsavel']
                    ?? null
            ),

            'fornecedor_id' =>
            $this->inteiroOuNull(
                $dados['fornecedor_id']
                    ?? null
            ),

            'numero_documento' =>
            $this->textoOuNull(
                $dados['numero_documento']
                    ?? null
            ),

            'estado_fisico' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['estado_fisico']
                        ?? 'NOVO'
                    )
                )
            ),

            'situacao' =>
            strtoupper(
                trim(
                    (string)(
                        $dados['situacao']
                        ?? 'EM_USO'
                    )
                )
            ),

            'observacoes' =>
            $this->textoOuNull(
                $dados['observacoes']
                    ?? null
            )
        ];
    }

    // ==================================================
    // VALIDAR
    // ==================================================

    private function validarDados(
        int $empresaId,
        array $dados
    ): void {

        if (
            !in_array(
                $dados['tipo'],
                [
                    'TANGIVEL',
                    'INTANGIVEL'
                ],
                true
            )
        ) {

            throw new RuntimeException(
                'Tipo de ativo inválido.'
            );
        }


        if (
            $dados['conta_contabil_id']
            <= 0
        ) {

            throw new RuntimeException(
                'Informe a categoria contabilística do ativo.'
            );
        }


        if (
            $dados['descricao'] === ''
        ) {

            throw new RuntimeException(
                'Informe a descrição do ativo.'
            );
        }


        if (
            $dados['data_aquisicao']
            === ''
        ) {

            throw new RuntimeException(
                'Informe a data de aquisição.'
            );
        }


        if (
            $dados['valor_aquisicao']
            <= 0
        ) {

            throw new RuntimeException(
                'O valor de aquisição deve ser superior a zero.'
            );
        }


        if (
            $dados['valor_residual']
            < 0
        ) {

            throw new RuntimeException(
                'O valor residual não pode ser negativo.'
            );
        }


        if (
            $dados['valor_residual']
            > $dados['valor_aquisicao']
        ) {

            throw new RuntimeException(
                'O valor residual não pode ser superior ao valor de aquisição.'
            );
        }


        /*
         * Confirma que a conta realmente pertence
         * à empresa e corresponde ao tipo escolhido.
         */

        $prefixo =
            $dados['tipo']
            === 'TANGIVEL'
            ? '3.2'
            : '3.3';


        $stmt =
            $this->pdo->prepare("
                SELECT id

                FROM contas_contabilisticas

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                  AND grupo =
                    'ATIVO'

                  AND codigo LIKE
                    :codigo

                  AND tipo =
                    'ANALITICA'

                  AND aceita_lancamento =
                    1

                LIMIT 1
            ");


        $stmt->execute([
            ':id' =>
            $dados['conta_contabil_id'],

            ':empresa_id' =>
            $empresaId,

            ':codigo' =>
            $prefixo . '%'
        ]);


        if (!$stmt->fetchColumn()) {

            throw new RuntimeException(
                'A categoria selecionada não corresponde ao tipo de ativo.'
            );
        }
    }

    // ==================================================
    // ENTRADA INICIAL
    // ==================================================

    private function registarEntradaInicial(
        int $empresaId,
        int $usuarioId,
        int $ativoId,
        array $dados
    ): void {

        $stmt =
            $this->pdo->prepare("
                INSERT INTO ativos_movimentacoes
                (
                    empresa_id,
                    ativo_id,
                    tipo_movimentacao,
                    filial_destino_id,
                    departamento_destino_id,
                    localizacao_destino,
                    responsavel_destino,
                    data_movimentacao,
                    motivo,
                    usuario_id
                )
                VALUES
                (
                    :empresa_id,
                    :ativo_id,
                    'ENTRADA',
                    :filial_destino_id,
                    :departamento_destino_id,
                    :localizacao_destino,
                    :responsavel_destino,
                    :data_movimentacao,
                    'Entrada inicial do ativo',
                    :usuario_id
                )
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':ativo_id' =>
            $ativoId,

            ':filial_destino_id' =>
            $dados['filial_id'],

            ':departamento_destino_id' =>
            $dados['departamento_id'],

            ':localizacao_destino' =>
            $dados['localizacao'],

            ':responsavel_destino' =>
            $dados['responsavel'],

            ':data_movimentacao' =>
            $dados['data_entrada_uso']
                ?? $dados['data_aquisicao'],

            ':usuario_id' =>
            $usuarioId
        ]);
    }

    // ==================================================
    // REGISTAR ALTERAÇÃO
    // ==================================================

    private function registarMudancaSeNecessario(
        int $empresaId,
        int $usuarioId,
        int $ativoId,
        array $atual,
        array $novo
    ): void {

        $mudou =
            (int)($atual['filial_id'] ?? 0)
            !== (int)($novo['filial_id'] ?? 0)

            || (int)(
                $atual['departamento_id']
                ?? 0
            ) !== (int)(
                $novo['departamento_id']
                ?? 0
            )

            || trim(
                (string)(
                    $atual['localizacao']
                    ?? ''
                )
            ) !== trim(
                (string)(
                    $novo['localizacao']
                    ?? ''
                )
            )

            || trim(
                (string)(
                    $atual['responsavel']
                    ?? ''
                )
            ) !== trim(
                (string)(
                    $novo['responsavel']
                    ?? ''
                )
            );


        if (!$mudou) {
            return;
        }


        $stmt =
            $this->pdo->prepare("
                INSERT INTO ativos_movimentacoes
                (
                    empresa_id,
                    ativo_id,
                    tipo_movimentacao,

                    filial_origem_id,
                    filial_destino_id,

                    departamento_origem_id,
                    departamento_destino_id,

                    localizacao_origem,
                    localizacao_destino,

                    responsavel_origem,
                    responsavel_destino,

                    data_movimentacao,
                    motivo,
                    usuario_id
                )
                VALUES
                (
                    :empresa_id,
                    :ativo_id,
                    'TRANSFERENCIA',

                    :filial_origem_id,
                    :filial_destino_id,

                    :departamento_origem_id,
                    :departamento_destino_id,

                    :localizacao_origem,
                    :localizacao_destino,

                    :responsavel_origem,
                    :responsavel_destino,

                    CURDATE(),
                    'Alteração dos dados de afetação do ativo',
                    :usuario_id
                )
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':ativo_id' =>
            $ativoId,

            ':filial_origem_id' =>
            $atual['filial_id']
                ?? null,

            ':filial_destino_id' =>
            $novo['filial_id'],

            ':departamento_origem_id' =>
            $atual['departamento_id']
                ?? null,

            ':departamento_destino_id' =>
            $novo['departamento_id'],

            ':localizacao_origem' =>
            $atual['localizacao']
                ?? null,

            ':localizacao_destino' =>
            $novo['localizacao'],

            ':responsavel_origem' =>
            $atual['responsavel']
                ?? null,

            ':responsavel_destino' =>
            $novo['responsavel'],

            ':usuario_id' =>
            $usuarioId
        ]);
    }

    // ==================================================
    // PRÓXIMO NÚMERO DO DIÁRIO
    // ==================================================

    private function proximoNumeroDiario(
        int $diarioId
    ): int {

        /*
     * Bloqueia o diário dentro da transação.
     * Isso evita duas operações receberem
     * o mesmo número simultaneamente.
     */

        $stmt =
            $this->pdo->prepare("
            SELECT ultimo_numero

            FROM diarios_contabilisticos

            WHERE id =
                :id

            FOR UPDATE
        ");


        $stmt->execute([
            ':id' =>
            $diarioId
        ]);


        $ultimoNumero =
            $stmt->fetchColumn();


        if ($ultimoNumero === false) {

            throw new RuntimeException(
                'Diário contabilístico não encontrado.'
            );
        }


        $numero =
            (int)$ultimoNumero
            + 1;


        $stmt =
            $this->pdo->prepare("
            UPDATE diarios_contabilisticos

            SET ultimo_numero =
                :numero

            WHERE id =
                :id
        ");


        $stmt->execute([
            ':numero' =>
            $numero,

            ':id' =>
            $diarioId
        ]);


        return $numero;
    }

    // ==================================================
    // HELPERS
    // ==================================================

    // ==================================================
    // STATUS DO LANÇAMENTO
    // ==================================================

    private function obterStatusLancamento(): int
    {
        $stmt =
            $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                'lancamento_contabilistico'

              AND LOWER(nome) =
                'contabilizado'

            LIMIT 1
        ");


        $stmt->execute();


        $statusId =
            (int)(
                $stmt->fetchColumn()
                ?: 0
            );


        if ($statusId <= 0) {

            throw new RuntimeException(
                'Status Contabilizado não encontrado.'
            );
        }


        return $statusId;
    }

    private function inteiroOuNull(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        $valor =
            (int)$valor;

        return $valor > 0
            ? $valor
            : null;
    }


    private function decimalOuNull(
        mixed $valor
    ): ?float {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return round(
            (float)$valor,
            4
        );
    }


    private function textoOuNull(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)(
                    $valor
                    ?? ''
                )
            );

        return $texto !== ''
            ? $texto
            : null;
    }
}
