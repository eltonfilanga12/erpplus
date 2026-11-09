<?php

declare(strict_types=1);

final class ContratoTrabalho
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa($empresaId);

        $funcionarioId =
            $this->inteiroOpcional(
                $filtros['funcionario_id'] ?? null
            );

        $tipoContratoId =
            $this->inteiroOpcional(
                $filtros['tipo_contrato_id'] ?? null
            );

        $filialId =
            $this->inteiroOpcional(
                $filtros['filial_id'] ?? null
            );

        $departamentoId =
            $this->inteiroOpcional(
                $filtros['departamento_id'] ?? null
            );

        $statusId =
            $this->inteiroOpcional(
                $filtros['status_id'] ?? null
            );

        $dataInicio =
            $this->dataOpcional(
                $filtros['data_inicio'] ?? null,
                'data inicial do filtro'
            );

        $dataFim =
            $this->dataOpcional(
                $filtros['data_fim'] ?? null,
                'data final do filtro'
            );

        $pesquisa =
            $this->textoOpcional(
                $filtros['pesquisa'] ?? null
            );

        if (
            $dataInicio !== null
            && $dataFim !== null
            && $dataFim < $dataInicio
        ) {
            throw new RuntimeException(
                'A data final do filtro não pode ser anterior à data inicial.'
            );
        }

        $sql = "
            SELECT
                contrato.id,
                contrato.uuid,
                contrato.empresa_id,
                contrato.funcionario_id,
                contrato.tipo_contrato_id,
                contrato.filial_id,
                contrato.departamento_id,
                contrato.cargo_id,
                contrato.horario_trabalho_id,
                contrato.numero_contrato,
                contrato.referencia_externa,
                contrato.data_assinatura,
                contrato.data_inicio,
                contrato.data_fim,
                contrato.periodo_experimental_inicio,
                contrato.periodo_experimental_fim,
                contrato.aviso_previo_dias,
                contrato.renovacao_automatica,
                contrato.numero_renovacoes,
                contrato.numero_maximo_renovacoes,
                contrato.data_proxima_renovacao,
                contrato.salario_base,
                contrato.moeda,
                contrato.periodicidade_salario,
                contrato.horas_semanais,
                contrato.local_trabalho,
                contrato.descricao_funcoes,
                contrato.clausulas_especiais,
                contrato.beneficios,
                contrato.observacoes,
                contrato.documento_contrato,
                contrato.data_suspensao,
                contrato.motivo_suspensao,
                contrato.data_rescisao,
                contrato.motivo_rescisao,
                contrato.iniciativa_rescisao,
                contrato.status_id,
                contrato.criado_por,
                contrato.atualizado_por,
                contrato.criado_em,
                contrato.atualizado_em,

                funcionario.numero_funcionario,
                funcionario.nome_completo AS funcionario_nome,
                funcionario.email_profissional,
                funcionario.telefone_principal,

                tipo.codigo AS tipo_contrato_codigo,
                tipo.nome AS tipo_contrato_nome,
                tipo.duracao_determinada,
                tipo.duracao_meses,
                tipo.renovacao_automatica
                    AS tipo_renovacao_automatica,
                tipo.numero_maximo_renovacoes
                    AS tipo_numero_maximo_renovacoes,
                tipo.periodo_experimental_dias,
                tipo.aviso_previo_dias
                    AS tipo_aviso_previo_dias,

                filial.nome AS filial_nome,
                filial.codigo AS filial_codigo,

                departamento.nome AS departamento_nome,
                departamento.codigo AS departamento_codigo,

                cargo.nome AS cargo_nome,
                cargo.codigo AS cargo_codigo,

                horario.nome AS horario_nome,
                horario.codigo AS horario_codigo,
                horario.hora_entrada,
                horario.hora_saida,
                horario.carga_horaria_semanal,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM contratos_trabalho contrato

            INNER JOIN funcionarios funcionario
                ON funcionario.id =
                    contrato.funcionario_id

               AND funcionario.empresa_id =
                    contrato.empresa_id

            INNER JOIN tipos_contratos tipo
                ON tipo.id =
                    contrato.tipo_contrato_id

               AND tipo.empresa_id =
                    contrato.empresa_id

            LEFT JOIN filiais filial
                ON filial.id =
                    contrato.filial_id

               AND filial.empresa_id =
                    contrato.empresa_id

            LEFT JOIN departamentos departamento
                ON departamento.id =
                    contrato.departamento_id

               AND departamento.empresa_id =
                    contrato.empresa_id

            LEFT JOIN cargos cargo
                ON cargo.id =
                    contrato.cargo_id

               AND cargo.empresa_id =
                    contrato.empresa_id

            LEFT JOIN horarios_trabalho horario
                ON horario.id =
                    contrato.horario_trabalho_id

               AND horario.empresa_id =
                    contrato.empresa_id

            INNER JOIN status
                ON status.id =
                    contrato.status_id

               AND status.tipo =
                    'contrato_trabalho'

            WHERE contrato.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($funcionarioId !== null) {
            $sql .= "
                AND contrato.funcionario_id =
                    :funcionario_id
            ";

            $params[':funcionario_id'] =
                $funcionarioId;
        }

        if ($tipoContratoId !== null) {
            $sql .= "
                AND contrato.tipo_contrato_id =
                    :tipo_contrato_id
            ";

            $params[':tipo_contrato_id'] =
                $tipoContratoId;
        }

        if ($filialId !== null) {
            $sql .= "
                AND contrato.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        if ($departamentoId !== null) {
            $sql .= "
                AND contrato.departamento_id =
                    :departamento_id
            ";

            $params[':departamento_id'] =
                $departamentoId;
        }

        if ($statusId !== null) {
            $sql .= "
                AND contrato.status_id =
                    :status_id
            ";

            $params[':status_id'] =
                $statusId;
        }

        if ($dataInicio !== null) {
            $sql .= "
                AND (
                    contrato.data_fim IS NULL
                    OR contrato.data_fim >= :data_inicio
                )
            ";

            $params[':data_inicio'] =
                $dataInicio;
        }

        if ($dataFim !== null) {
            $sql .= "
                AND contrato.data_inicio <=
                    :data_fim
            ";

            $params[':data_fim'] =
                $dataFim;
        }

        if ($pesquisa !== null) {
            $sql .= "
                AND
                (
                    contrato.numero_contrato
                        LIKE :pesquisa

                    OR contrato.referencia_externa
                        LIKE :pesquisa

                    OR funcionario.nome_completo
                        LIKE :pesquisa

                    OR funcionario.numero_funcionario
                        LIKE :pesquisa

                    OR tipo.nome
                        LIKE :pesquisa
                )
            ";

            $params[':pesquisa'] =
                '%' . $pesquisa . '%';
        }

        $sql .= "
            ORDER BY
                contrato.data_inicio DESC,
                contrato.id DESC
        ";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $empresaId,
        int $contratoId
    ): array {

        $this->validarEmpresa($empresaId);
        $this->validarId($contratoId);

        $stmt = $this->pdo->prepare("
            SELECT
                contrato.*,

                funcionario.numero_funcionario,
                funcionario.nome_completo AS funcionario_nome,
                funcionario.email_profissional,
                funcionario.telefone_principal,

                tipo.codigo AS tipo_contrato_codigo,
                tipo.nome AS tipo_contrato_nome,
                tipo.duracao_determinada,
                tipo.duracao_meses,
                tipo.exige_data_fim,
                tipo.renovacao_automatica
                    AS tipo_renovacao_automatica,
                tipo.numero_maximo_renovacoes
                    AS tipo_numero_maximo_renovacoes,
                tipo.periodo_experimental_dias,
                tipo.aviso_previo_dias
                    AS tipo_aviso_previo_dias,

                filial.nome AS filial_nome,
                filial.codigo AS filial_codigo,

                departamento.nome AS departamento_nome,
                departamento.codigo AS departamento_codigo,

                cargo.nome AS cargo_nome,
                cargo.codigo AS cargo_codigo,

                horario.nome AS horario_nome,
                horario.codigo AS horario_codigo,
                horario.hora_entrada,
                horario.hora_saida,
                horario.carga_horaria_semanal,

                status.nome AS status_nome,
                status.descricao AS status_descricao

            FROM contratos_trabalho contrato

            INNER JOIN funcionarios funcionario
                ON funcionario.id =
                    contrato.funcionario_id

               AND funcionario.empresa_id =
                    contrato.empresa_id

            INNER JOIN tipos_contratos tipo
                ON tipo.id =
                    contrato.tipo_contrato_id

               AND tipo.empresa_id =
                    contrato.empresa_id

            LEFT JOIN filiais filial
                ON filial.id =
                    contrato.filial_id

               AND filial.empresa_id =
                    contrato.empresa_id

            LEFT JOIN departamentos departamento
                ON departamento.id =
                    contrato.departamento_id

               AND departamento.empresa_id =
                    contrato.empresa_id

            LEFT JOIN cargos cargo
                ON cargo.id =
                    contrato.cargo_id

               AND cargo.empresa_id =
                    contrato.empresa_id

            LEFT JOIN horarios_trabalho horario
                ON horario.id =
                    contrato.horario_trabalho_id

               AND horario.empresa_id =
                    contrato.empresa_id

            INNER JOIN status
                ON status.id =
                    contrato.status_id

               AND status.tipo =
                    'contrato_trabalho'

            WHERE contrato.id =
                :contrato_id

              AND contrato.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':contrato_id' => $contratoId,
            ':empresa_id' => $empresaId
        ]);

        $contrato =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contrato) {
            throw new RuntimeException(
                'Contrato de trabalho não encontrado.'
            );
        }

        return $contrato;
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {

        $this->validarEmpresa($empresaId);
        $this->validarUsuario($usuarioId);

        $dados =
            $this->normalizarDados(
                $empresaId,
                $dados
            );

        $this->validarRelacionamentos(
            $empresaId,
            $dados
        );

        $this->validarNumeroDuplicado(
            $empresaId,
            $dados['numero_contrato']
        );

        $this->validarContratoAtivoDuplicado(
            $empresaId,
            $dados['funcionario_id'],
            $dados['status_id']
        );

        $stmt = $this->pdo->prepare("
            INSERT INTO contratos_trabalho
            (
                uuid,
                empresa_id,
                funcionario_id,
                tipo_contrato_id,
                filial_id,
                departamento_id,
                cargo_id,
                horario_trabalho_id,
                numero_contrato,
                referencia_externa,
                data_assinatura,
                data_inicio,
                data_fim,
                periodo_experimental_inicio,
                periodo_experimental_fim,
                aviso_previo_dias,
                renovacao_automatica,
                numero_renovacoes,
                numero_maximo_renovacoes,
                data_proxima_renovacao,
                salario_base,
                moeda,
                periodicidade_salario,
                horas_semanais,
                local_trabalho,
                descricao_funcoes,
                clausulas_especiais,
                beneficios,
                observacoes,
                documento_contrato,
                data_suspensao,
                motivo_suspensao,
                data_rescisao,
                motivo_rescisao,
                iniciativa_rescisao,
                status_id,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                UUID(),
                :empresa_id,
                :funcionario_id,
                :tipo_contrato_id,
                :filial_id,
                :departamento_id,
                :cargo_id,
                :horario_trabalho_id,
                :numero_contrato,
                :referencia_externa,
                :data_assinatura,
                :data_inicio,
                :data_fim,
                :periodo_experimental_inicio,
                :periodo_experimental_fim,
                :aviso_previo_dias,
                :renovacao_automatica,
                :numero_renovacoes,
                :numero_maximo_renovacoes,
                :data_proxima_renovacao,
                :salario_base,
                :moeda,
                :periodicidade_salario,
                :horas_semanais,
                :local_trabalho,
                :descricao_funcoes,
                :clausulas_especiais,
                :beneficios,
                :observacoes,
                :documento_contrato,
                :data_suspensao,
                :motivo_suspensao,
                :data_rescisao,
                :motivo_rescisao,
                :iniciativa_rescisao,
                :status_id,
                :criado_por,
                :atualizado_por
            )
        ");

        $stmt->execute(
            $this->parametrosContrato(
                $empresaId,
                $usuarioId,
                $dados
            )
        );

        return (int)$this->pdo
            ->lastInsertId();
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $empresaId,
        int $usuarioId,
        int $contratoId,
        array $dados
    ): void {

        $this->validarEmpresa($empresaId);
        $this->validarUsuario($usuarioId);
        $this->validarId($contratoId);

        $this->buscar(
            $empresaId,
            $contratoId
        );

        $dados =
            $this->normalizarDados(
                $empresaId,
                $dados
            );

        $this->validarRelacionamentos(
            $empresaId,
            $dados
        );

        $this->validarNumeroDuplicado(
            $empresaId,
            $dados['numero_contrato'],
            $contratoId
        );

        $this->validarContratoAtivoDuplicado(
            $empresaId,
            $dados['funcionario_id'],
            $dados['status_id'],
            $contratoId
        );

        $stmt = $this->pdo->prepare("
            UPDATE contratos_trabalho

            SET
                funcionario_id =
                    :funcionario_id,

                tipo_contrato_id =
                    :tipo_contrato_id,

                filial_id =
                    :filial_id,

                departamento_id =
                    :departamento_id,

                cargo_id =
                    :cargo_id,

                horario_trabalho_id =
                    :horario_trabalho_id,

                numero_contrato =
                    :numero_contrato,

                referencia_externa =
                    :referencia_externa,

                data_assinatura =
                    :data_assinatura,

                data_inicio =
                    :data_inicio,

                data_fim =
                    :data_fim,

                periodo_experimental_inicio =
                    :periodo_experimental_inicio,

                periodo_experimental_fim =
                    :periodo_experimental_fim,

                aviso_previo_dias =
                    :aviso_previo_dias,

                renovacao_automatica =
                    :renovacao_automatica,

                numero_renovacoes =
                    :numero_renovacoes,

                numero_maximo_renovacoes =
                    :numero_maximo_renovacoes,

                data_proxima_renovacao =
                    :data_proxima_renovacao,

                salario_base =
                    :salario_base,

                moeda =
                    :moeda,

                periodicidade_salario =
                    :periodicidade_salario,

                horas_semanais =
                    :horas_semanais,

                local_trabalho =
                    :local_trabalho,

                descricao_funcoes =
                    :descricao_funcoes,

                clausulas_especiais =
                    :clausulas_especiais,

                beneficios =
                    :beneficios,

                observacoes =
                    :observacoes,

                documento_contrato =
                    :documento_contrato,

                data_suspensao =
                    :data_suspensao,

                motivo_suspensao =
                    :motivo_suspensao,

                data_rescisao =
                    :data_rescisao,

                motivo_rescisao =
                    :motivo_rescisao,

                iniciativa_rescisao =
                    :iniciativa_rescisao,

                status_id =
                    :status_id,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :contrato_id

              AND empresa_id =
                :empresa_id
        ");

        $params =
            $this->parametrosContrato(
                $empresaId,
                $usuarioId,
                $dados
            );

        unset($params[':criado_por']);

        $params[':contrato_id'] =
            $contratoId;

        $stmt->execute($params);
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $empresaId,
        int $contratoId
    ): void {

        $this->validarEmpresa($empresaId);
        $this->validarId($contratoId);

        $contrato =
            $this->buscar(
                $empresaId,
                $contratoId
            );

        $status =
            $this->normalizarTexto(
                $contrato['status_nome'] ?? ''
            );

        if (
            !in_array(
                $status,
                [
                    'rascunho',
                    'cancelado'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Somente contratos em rascunho ou cancelados podem ser eliminados.'
            );
        }

        $stmt = $this->pdo->prepare("
            DELETE FROM contratos_trabalho

            WHERE id =
                :contrato_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':contrato_id' => $contratoId,
            ':empresa_id' => $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível eliminar o contrato de trabalho.'
            );
        }
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        int $empresaId
    ): array {

        $this->validarEmpresa($empresaId);

        return [
            'contratos' =>
            $this->listar($empresaId),

            'funcionarios' =>
            $this->listarFuncionarios(
                $empresaId
            ),

            'tipos_contratos' =>
            $this->listarTiposContratos(
                $empresaId
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'departamentos' =>
            $this->listarDepartamentos(
                $empresaId
            ),

            'cargos' =>
            $this->listarCargos(
                $empresaId
            ),

            'horarios' =>
            $this->listarHorarios(
                $empresaId
            ),

            'status' =>
            $this->listarStatus(),

            'periodicidades' =>
            $this->listarPeriodicidades(),

            'iniciativas_rescisao' =>
            $this->listarIniciativasRescisao()
        ];
    }

    // ==================================================
    // NORMALIZAR DADOS
    // ==================================================

    private function normalizarDados(
        int $empresaId,
        array $dados
    ): array {

        $funcionarioId =
            (int)($dados['funcionario_id'] ?? 0);

        $tipoContratoId =
            (int)($dados['tipo_contrato_id'] ?? 0);

        if ($funcionarioId <= 0) {
            throw new RuntimeException(
                'Selecione o funcionário.'
            );
        }

        if ($tipoContratoId <= 0) {
            throw new RuntimeException(
                'Selecione o tipo de contrato.'
            );
        }

        $funcionario =
            $this->buscarFuncionario(
                $empresaId,
                $funcionarioId
            );

        $tipoContrato =
            $this->buscarTipoContrato(
                $empresaId,
                $tipoContratoId
            );

        $numeroContrato =
            strtoupper(
                trim(
                    (string)(
                        $dados['numero_contrato']
                        ?? ''
                    )
                )
            );

        if ($numeroContrato === '') {
            throw new RuntimeException(
                'Informe o número do contrato.'
            );
        }

        if (mb_strlen($numeroContrato) > 40) {
            throw new RuntimeException(
                'O número do contrato não pode exceder 40 caracteres.'
            );
        }

        $dataInicio =
            $this->dataObrigatoria(
                $dados['data_inicio'] ?? null,
                'data de início'
            );

        $dataFim =
            $this->dataOpcional(
                $dados['data_fim'] ?? null,
                'data de fim'
            );

        $duracaoDeterminada =
            (int)(
                $tipoContrato['duracao_determinada']
                ?? 0
            ) === 1;

        $exigeDataFim =
            (int)(
                $tipoContrato['exige_data_fim']
                ?? 0
            ) === 1;

        if (
            $dataFim === null
            && $duracaoDeterminada
            && !empty($tipoContrato['duracao_meses'])
        ) {
            $dataFim =
                (
                    new DateTimeImmutable(
                        $dataInicio
                    )
                )
                ->modify(
                    '+'
                        . (int)$tipoContrato['duracao_meses']
                        . ' months'
                )
                ->format('Y-m-d');
        }

        if (
            $exigeDataFim
            && $dataFim === null
        ) {
            throw new RuntimeException(
                'Este tipo de contrato exige uma data final.'
            );
        }

        if (
            $dataFim !== null
            && $dataFim < $dataInicio
        ) {
            throw new RuntimeException(
                'A data final não pode ser anterior à data inicial.'
            );
        }

        $periodoExperimentalInicio =
            $this->dataOpcional(
                $dados['periodo_experimental_inicio']
                    ?? null,
                'início do período experimental'
            );

        $periodoExperimentalFim =
            $this->dataOpcional(
                $dados['periodo_experimental_fim']
                    ?? null,
                'fim do período experimental'
            );

        if (
            $periodoExperimentalInicio === null
            && (int)(
                $tipoContrato['periodo_experimental_dias']
                ?? 0
            ) > 0
        ) {
            $periodoExperimentalInicio =
                $dataInicio;
        }

        if (
            $periodoExperimentalFim === null
            && $periodoExperimentalInicio !== null
            && (int)(
                $tipoContrato['periodo_experimental_dias']
                ?? 0
            ) > 0
        ) {
            $periodoExperimentalFim =
                (
                    new DateTimeImmutable(
                        $periodoExperimentalInicio
                    )
                )
                ->modify(
                    '+'
                        . (int)$tipoContrato['periodo_experimental_dias']
                        . ' days'
                )
                ->format('Y-m-d');
        }

        if (
            $periodoExperimentalInicio !== null
            && $periodoExperimentalFim !== null
            && $periodoExperimentalFim
            < $periodoExperimentalInicio
        ) {
            throw new RuntimeException(
                'O fim do período experimental não pode ser anterior ao início.'
            );
        }

        if (
            $periodoExperimentalInicio !== null
            && $periodoExperimentalInicio
            < $dataInicio
        ) {
            throw new RuntimeException(
                'O período experimental não pode iniciar antes do contrato.'
            );
        }

        if (
            $dataFim !== null
            && $periodoExperimentalFim !== null
            && $periodoExperimentalFim > $dataFim
        ) {
            throw new RuntimeException(
                'O período experimental não pode terminar depois do contrato.'
            );
        }

        $renovacaoAutomatica =
            $this->booleano(
                $dados['renovacao_automatica']
                    ?? $tipoContrato['renovacao_automatica']
                    ?? 0
            );

        $numeroRenovacoes =
            $this->inteiroNaoNegativo(
                $dados['numero_renovacoes']
                    ?? 0,
                'número de renovações'
            );

        $numeroMaximoRenovacoes =
            $this->inteiroOpcionalNaoNegativo(
                $dados['numero_maximo_renovacoes']
                    ?? $tipoContrato['numero_maximo_renovacoes']
                    ?? null,
                'número máximo de renovações'
            );

        if (
            $numeroMaximoRenovacoes !== null
            && $numeroRenovacoes
            > $numeroMaximoRenovacoes
        ) {
            throw new RuntimeException(
                'O número de renovações excede o limite configurado.'
            );
        }

        $dataProximaRenovacao =
            $this->dataOpcional(
                $dados['data_proxima_renovacao']
                    ?? null,
                'data da próxima renovação'
            );

        if (
            $renovacaoAutomatica === 1
            && $dataProximaRenovacao === null
            && $dataFim !== null
        ) {
            $dataProximaRenovacao =
                $dataFim;
        }

        if ($renovacaoAutomatica !== 1) {
            $dataProximaRenovacao = null;
        }

        $avisoPrevioDias =
            $this->inteiroNaoNegativo(
                $dados['aviso_previo_dias']
                    ?? $tipoContrato['aviso_previo_dias']
                    ?? 0,
                'aviso prévio'
            );

        $salarioBase =
            $this->decimalNaoNegativo(
                $dados['salario_base']
                    ?? $funcionario['salario_base']
                    ?? 0,
                'salário base'
            );

        $moeda =
            strtoupper(
                trim(
                    (string)(
                        $dados['moeda']
                        ?? $funcionario['moeda']
                        ?? 'MZN'
                    )
                )
            );

        if (!preg_match('/^[A-Z]{3}$/', $moeda)) {
            throw new RuntimeException(
                'A moeda deve possuir três letras.'
            );
        }

        $statusId =
            (int)($dados['status_id'] ?? 0);

        if ($statusId <= 0) {
            $statusId =
                $this->statusRascunhoId();
        }

        $statusNome =
            $this->statusNome(
                $statusId
            );

        $dataSuspensao =
            $this->dataOpcional(
                $dados['data_suspensao']
                    ?? null,
                'data de suspensão'
            );

        $motivoSuspensao =
            $this->textoOpcional(
                $dados['motivo_suspensao']
                    ?? null
            );

        $dataRescisao =
            $this->dataOpcional(
                $dados['data_rescisao']
                    ?? null,
                'data de rescisão'
            );

        $motivoRescisao =
            $this->textoOpcional(
                $dados['motivo_rescisao']
                    ?? null
            );

        $iniciativaRescisao =
            $this->enumOpcional(
                $dados['iniciativa_rescisao']
                    ?? null,
                [
                    'EMPRESA',
                    'FUNCIONARIO',
                    'MUTUO_ACORDO',
                    'FIM_CONTRATO',
                    'OUTRO'
                ],
                'iniciativa da rescisão'
            );

        if ($statusNome === 'suspenso') {
            if ($dataSuspensao === null) {
                throw new RuntimeException(
                    'Informe a data de suspensão.'
                );
            }

            if ($motivoSuspensao === null) {
                throw new RuntimeException(
                    'Informe o motivo da suspensão.'
                );
            }
        } else {
            $dataSuspensao = null;
            $motivoSuspensao = null;
        }

        if (
            in_array(
                $statusNome,
                [
                    'rescindido',
                    'terminado'
                ],
                true
            )
        ) {
            if ($dataRescisao === null) {
                throw new RuntimeException(
                    'Informe a data de término ou rescisão.'
                );
            }

            if ($dataRescisao < $dataInicio) {
                throw new RuntimeException(
                    'A data de rescisão não pode ser anterior ao início do contrato.'
                );
            }

            if ($motivoRescisao === null) {
                throw new RuntimeException(
                    'Informe o motivo da rescisão ou término.'
                );
            }

            if ($iniciativaRescisao === null) {
                throw new RuntimeException(
                    'Selecione a iniciativa da rescisão.'
                );
            }
        } else {
            $dataRescisao = null;
            $motivoRescisao = null;
            $iniciativaRescisao = null;
        }

        return [
            'funcionario_id' =>
            $funcionarioId,

            'tipo_contrato_id' =>
            $tipoContratoId,

            'filial_id' =>
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? $funcionario['filial_id']
                    ?? null
            ),

            'departamento_id' =>
            $this->inteiroOpcional(
                $dados['departamento_id']
                    ?? $funcionario['departamento_id']
                    ?? null
            ),

            'cargo_id' =>
            $this->inteiroOpcional(
                $dados['cargo_id']
                    ?? $funcionario['cargo_id']
                    ?? null
            ),

            'horario_trabalho_id' =>
            $this->inteiroOpcional(
                $dados['horario_trabalho_id']
                    ?? $funcionario['horario_trabalho_id']
                    ?? null
            ),

            'numero_contrato' =>
            $numeroContrato,

            'referencia_externa' =>
            $this->textoOpcional(
                $dados['referencia_externa']
                    ?? null
            ),

            'data_assinatura' =>
            $this->dataOpcional(
                $dados['data_assinatura']
                    ?? null,
                'data de assinatura'
            ),

            'data_inicio' =>
            $dataInicio,

            'data_fim' =>
            $dataFim,

            'periodo_experimental_inicio' =>
            $periodoExperimentalInicio,

            'periodo_experimental_fim' =>
            $periodoExperimentalFim,

            'aviso_previo_dias' =>
            $avisoPrevioDias,

            'renovacao_automatica' =>
            $renovacaoAutomatica,

            'numero_renovacoes' =>
            $numeroRenovacoes,

            'numero_maximo_renovacoes' =>
            $numeroMaximoRenovacoes,

            'data_proxima_renovacao' =>
            $dataProximaRenovacao,

            'salario_base' =>
            $salarioBase,

            'moeda' =>
            $moeda,

            'periodicidade_salario' =>
            $this->enumObrigatorio(
                $dados['periodicidade_salario']
                    ?? 'MENSAL',
                [
                    'MENSAL',
                    'QUINZENAL',
                    'SEMANAL',
                    'DIARIO',
                    'HORARIO'
                ],
                'periodicidade salarial'
            ),

            'horas_semanais' =>
            $this->decimalOpcionalNaoNegativo(
                $dados['horas_semanais']
                    ?? null,
                'horas semanais'
            ),

            'local_trabalho' =>
            $this->textoOpcional(
                $dados['local_trabalho']
                    ?? null
            ),

            'descricao_funcoes' =>
            $this->textoOpcional(
                $dados['descricao_funcoes']
                    ?? null
            ),

            'clausulas_especiais' =>
            $this->textoOpcional(
                $dados['clausulas_especiais']
                    ?? null
            ),

            'beneficios' =>
            $this->textoOpcional(
                $dados['beneficios']
                    ?? null
            ),

            'observacoes' =>
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            ),

            'documento_contrato' =>
            $this->textoOpcional(
                $dados['documento_contrato']
                    ?? null
            ),

            'data_suspensao' =>
            $dataSuspensao,

            'motivo_suspensao' =>
            $motivoSuspensao,

            'data_rescisao' =>
            $dataRescisao,

            'motivo_rescisao' =>
            $motivoRescisao,

            'iniciativa_rescisao' =>
            $iniciativaRescisao,

            'status_id' =>
            $statusId
        ];
    }

    // ==================================================
    // PARÂMETROS
    // ==================================================

    private function parametrosContrato(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): array {

        return [
            ':empresa_id' => $empresaId,
            ':funcionario_id' => $dados['funcionario_id'],
            ':tipo_contrato_id' => $dados['tipo_contrato_id'],
            ':filial_id' => $dados['filial_id'],
            ':departamento_id' => $dados['departamento_id'],
            ':cargo_id' => $dados['cargo_id'],
            ':horario_trabalho_id' => $dados['horario_trabalho_id'],
            ':numero_contrato' => $dados['numero_contrato'],
            ':referencia_externa' => $dados['referencia_externa'],
            ':data_assinatura' => $dados['data_assinatura'],
            ':data_inicio' => $dados['data_inicio'],
            ':data_fim' => $dados['data_fim'],
            ':periodo_experimental_inicio' =>
            $dados['periodo_experimental_inicio'],
            ':periodo_experimental_fim' =>
            $dados['periodo_experimental_fim'],
            ':aviso_previo_dias' => $dados['aviso_previo_dias'],
            ':renovacao_automatica' => $dados['renovacao_automatica'],
            ':numero_renovacoes' => $dados['numero_renovacoes'],
            ':numero_maximo_renovacoes' =>
            $dados['numero_maximo_renovacoes'],
            ':data_proxima_renovacao' =>
            $dados['data_proxima_renovacao'],
            ':salario_base' => $dados['salario_base'],
            ':moeda' => $dados['moeda'],
            ':periodicidade_salario' =>
            $dados['periodicidade_salario'],
            ':horas_semanais' => $dados['horas_semanais'],
            ':local_trabalho' => $dados['local_trabalho'],
            ':descricao_funcoes' => $dados['descricao_funcoes'],
            ':clausulas_especiais' => $dados['clausulas_especiais'],
            ':beneficios' => $dados['beneficios'],
            ':observacoes' => $dados['observacoes'],
            ':documento_contrato' => $dados['documento_contrato'],
            ':data_suspensao' => $dados['data_suspensao'],
            ':motivo_suspensao' => $dados['motivo_suspensao'],
            ':data_rescisao' => $dados['data_rescisao'],
            ':motivo_rescisao' => $dados['motivo_rescisao'],
            ':iniciativa_rescisao' => $dados['iniciativa_rescisao'],
            ':status_id' => $dados['status_id'],
            ':criado_por' => $usuarioId,
            ':atualizado_por' => $usuarioId
        ];
    }

    // ==================================================
    // RELACIONAMENTOS
    // ==================================================

    private function validarRelacionamentos(
        int $empresaId,
        array $dados
    ): void {

        $this->buscarFuncionario(
            $empresaId,
            $dados['funcionario_id']
        );

        $this->buscarTipoContrato(
            $empresaId,
            $dados['tipo_contrato_id']
        );

        if ($dados['filial_id'] !== null) {
            $this->validarRegistroEmpresa(
                'filiais',
                $dados['filial_id'],
                $empresaId,
                'Filial inválida.'
            );
        }

        if ($dados['departamento_id'] !== null) {
            $this->validarRegistroEmpresa(
                'departamentos',
                $dados['departamento_id'],
                $empresaId,
                'Departamento inválido.'
            );
        }

        if ($dados['cargo_id'] !== null) {
            $this->validarRegistroEmpresa(
                'cargos',
                $dados['cargo_id'],
                $empresaId,
                'Cargo inválido.'
            );
        }

        if ($dados['horario_trabalho_id'] !== null) {
            $this->validarRegistroEmpresa(
                'horarios_trabalho',
                $dados['horario_trabalho_id'],
                $empresaId,
                'Horário de trabalho inválido.'
            );
        }

        $this->validarStatus(
            $dados['status_id']
        );
    }

    private function validarRegistroEmpresa(
        string $tabela,
        int $registroId,
        int $empresaId,
        string $mensagem
    ): void {

        $permitidas = [
            'filiais',
            'departamentos',
            'cargos',
            'horarios_trabalho'
        ];

        if (!in_array($tabela, $permitidas, true)) {
            throw new RuntimeException(
                'Tabela de validação inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id
            FROM {$tabela}
            WHERE id = :registro_id
              AND empresa_id = :empresa_id
            LIMIT 1
        ");

        $stmt->execute([
            ':registro_id' => $registroId,
            ':empresa_id' => $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException($mensagem);
        }
    }

    // ==================================================
    // CONTRATO ATIVO DUPLICADO
    // ==================================================

    private function validarContratoAtivoDuplicado(
        int $empresaId,
        int $funcionarioId,
        int $statusId,
        ?int $ignorarId = null
    ): void {

        if (
            $this->statusNome($statusId)
            !== 'activo'
        ) {
            return;
        }

        $sql = "
            SELECT contrato.id

            FROM contratos_trabalho contrato

            INNER JOIN status
                ON status.id =
                    contrato.status_id

               AND status.tipo =
                    'contrato_trabalho'

            WHERE contrato.empresa_id =
                :empresa_id

              AND contrato.funcionario_id =
                :funcionario_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )
        ";

        $params = [
            ':empresa_id' => $empresaId,
            ':funcionario_id' => $funcionarioId
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND contrato.id <>
                    :ignorar_id
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
                'O funcionário já possui outro contrato ativo.'
            );
        }
    }

    // ==================================================
    // DUPLICADOS
    // ==================================================

    private function validarNumeroDuplicado(
        int $empresaId,
        string $numeroContrato,
        ?int $ignorarId = null
    ): void {

        $sql = "
            SELECT id

            FROM contratos_trabalho

            WHERE empresa_id =
                :empresa_id

              AND numero_contrato =
                :numero_contrato
        ";

        $params = [
            ':empresa_id' => $empresaId,
            ':numero_contrato' => $numeroContrato
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
                'Já existe um contrato com este número.'
            );
        }
    }

    // ==================================================
    // LISTAS AUXILIARES
    // ==================================================

    private function listarFuncionarios(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                funcionario.id,
                funcionario.numero_funcionario,
                funcionario.nome_completo,
                funcionario.filial_id,
                funcionario.departamento_id,
                funcionario.cargo_id,
                funcionario.horario_trabalho_id,
                funcionario.salario_base,
                funcionario.moeda

            FROM funcionarios funcionario

            INNER JOIN status
                ON status.id =
                    funcionario.status_id

               AND status.tipo =
                    'funcionario'

            WHERE funcionario.empresa_id =
                :empresa_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            ORDER BY funcionario.nome_completo
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarTiposContratos(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                tipo.id,
                tipo.codigo,
                tipo.nome,
                tipo.duracao_determinada,
                tipo.duracao_meses,
                tipo.exige_data_fim,
                tipo.renovacao_automatica,
                tipo.numero_maximo_renovacoes,
                tipo.periodo_experimental_dias,
                tipo.aviso_previo_dias

            FROM tipos_contratos tipo

            INNER JOIN status
                ON status.id =
                    tipo.status_id

               AND status.tipo =
                    'tipo_contrato'

            WHERE tipo.empresa_id =
                :empresa_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            ORDER BY tipo.nome
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarFiliais(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                codigo,
                nome,
                endereco

            FROM filiais

            WHERE empresa_id =
                :empresa_id

            ORDER BY nome
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarDepartamentos(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                departamento.id,
                departamento.codigo,
                departamento.nome

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

            ORDER BY departamento.nome
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarCargos(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                cargo.id,
                cargo.codigo,
                cargo.nome,
                cargo.departamento_id

            FROM cargos cargo

            INNER JOIN status
                ON status.id =
                    cargo.status_id

               AND status.tipo =
                    'cargo'

            WHERE cargo.empresa_id =
                :empresa_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            ORDER BY cargo.nome
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarHorarios(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                horario.id,
                horario.codigo,
                horario.nome,
                horario.hora_entrada,
                horario.hora_saida,
                horario.carga_horaria_semanal

            FROM horarios_trabalho horario

            INNER JOIN status
                ON status.id =
                    horario.status_id

               AND status.tipo =
                    'horario_trabalho'

            WHERE horario.empresa_id =
                :empresa_id

              AND LOWER(status.nome) IN
              (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            ORDER BY horario.nome
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarStatus(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo =
                'contrato_trabalho'

            ORDER BY id
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarPeriodicidades(): array
    {
        return [
            ['codigo' => 'MENSAL', 'nome' => 'Mensal'],
            ['codigo' => 'QUINZENAL', 'nome' => 'Quinzenal'],
            ['codigo' => 'SEMANAL', 'nome' => 'Semanal'],
            ['codigo' => 'DIARIO', 'nome' => 'Diário'],
            ['codigo' => 'HORARIO', 'nome' => 'Por Hora']
        ];
    }

    private function listarIniciativasRescisao(): array
    {
        return [
            ['codigo' => 'EMPRESA', 'nome' => 'Empresa'],
            ['codigo' => 'FUNCIONARIO', 'nome' => 'Funcionário'],
            ['codigo' => 'MUTUO_ACORDO', 'nome' => 'Mútuo Acordo'],
            ['codigo' => 'FIM_CONTRATO', 'nome' => 'Fim do Contrato'],
            ['codigo' => 'OUTRO', 'nome' => 'Outro']
        ];
    }

    // ==================================================
    // BUSCAS AUXILIARES
    // ==================================================

    private function buscarFuncionario(
        int $empresaId,
        int $funcionarioId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                filial_id,
                departamento_id,
                cargo_id,
                horario_trabalho_id,
                salario_base,
                moeda

            FROM funcionarios

            WHERE id =
                :funcionario_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':funcionario_id' => $funcionarioId,
            ':empresa_id' => $empresaId
        ]);

        $funcionario =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario) {
            throw new RuntimeException(
                'Funcionário inválido.'
            );
        }

        return $funcionario;
    }

    private function buscarTipoContrato(
        int $empresaId,
        int $tipoContratoId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                duracao_determinada,
                duracao_meses,
                exige_data_fim,
                renovacao_automatica,
                numero_maximo_renovacoes,
                periodo_experimental_dias,
                aviso_previo_dias

            FROM tipos_contratos

            WHERE id =
                :tipo_contrato_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':tipo_contrato_id' => $tipoContratoId,
            ':empresa_id' => $empresaId
        ]);

        $tipo =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tipo) {
            throw new RuntimeException(
                'Tipo de contrato inválido.'
            );
        }

        return $tipo;
    }

    // ==================================================
    // STATUS
    // ==================================================

    private function validarStatus(
        int $statusId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE id =
                :status_id

              AND tipo =
                'contrato_trabalho'

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Estado do contrato inválido.'
            );
        }
    }

    private function statusNome(
        int $statusId
    ): string {

        $stmt = $this->pdo->prepare("
            SELECT nome

            FROM status

            WHERE id =
                :status_id

              AND tipo =
                'contrato_trabalho'

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        $nome =
            $stmt->fetchColumn();

        if ($nome === false) {
            throw new RuntimeException(
                'Estado do contrato inválido.'
            );
        }

        return $this->normalizarTexto(
            (string)$nome
        );
    }

    private function statusRascunhoId(): int
    {
        $stmt = $this->pdo->query("
            SELECT id

            FROM status

            WHERE tipo =
                'contrato_trabalho'

              AND LOWER(nome) =
                'rascunho'

            LIMIT 1
        ");

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                'O estado Rascunho do contrato não está configurado.'
            );
        }

        return $id;
    }

    // ==================================================
    // VALIDAÇÕES GERAIS
    // ==================================================

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
            ':usuario_id' => $usuarioId
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
                'Empresa inválida para a gestão dos contratos.'
            );
        }
    }

    private function validarId(
        int $contratoId
    ): void {

        if ($contratoId <= 0) {
            throw new RuntimeException(
                'Contrato de trabalho inválido.'
            );
        }
    }

    // ==================================================
    // HELPERS
    // ==================================================

    private function dataObrigatoria(
        mixed $valor,
        string $campo
    ): string {

        $data =
            trim((string)($valor ?? ''));

        if ($data === '') {
            throw new RuntimeException(
                'Informe a ' . $campo . '.'
            );
        }

        return $this->validarData(
            $data,
            $campo
        );
    }

    private function dataOpcional(
        mixed $valor,
        string $campo
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->validarData(
            trim((string)$valor),
            $campo
        );
    }

    private function validarData(
        string $data,
        string $campo
    ): string {

        $objeto =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $data
            );

        $erros =
            DateTimeImmutable::getLastErrors();

        if (
            $objeto === false
            || (
                is_array($erros)
                && (
                    $erros['warning_count'] > 0
                    || $erros['error_count'] > 0
                )
            )
            || $objeto->format('Y-m-d')
            !== $data
        ) {
            throw new RuntimeException(
                'A ' . $campo . ' é inválida.'
            );
        }

        return $data;
    }

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim((string)$valor) === ''
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

    private function inteiroNaoNegativo(
        mixed $valor,
        string $campo
    ): int {

        $inteiro =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $inteiro === false
            || $inteiro < 0
        ) {
            throw new RuntimeException(
                'O ' . $campo . ' é inválido.'
            );
        }

        return (int)$inteiro;
    }

    private function inteiroOpcionalNaoNegativo(
        mixed $valor,
        string $campo
    ): ?int {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->inteiroNaoNegativo(
            $valor,
            $campo
        );
    }

    private function decimalNaoNegativo(
        mixed $valor,
        string $campo
    ): string {

        $texto =
            str_replace(
                ',',
                '.',
                trim((string)($valor ?? '0'))
            );

        if (
            $texto === ''
            || !is_numeric($texto)
        ) {
            throw new RuntimeException(
                'O ' . $campo . ' é inválido.'
            );
        }

        $numero = (float)$texto;

        if ($numero < 0) {
            throw new RuntimeException(
                'O ' . $campo . ' não pode ser negativo.'
            );
        }

        return number_format(
            $numero,
            2,
            '.',
            ''
        );
    }

    private function decimalOpcionalNaoNegativo(
        mixed $valor,
        string $campo
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->decimalNaoNegativo(
            $valor,
            $campo
        );
    }

    private function enumObrigatorio(
        mixed $valor,
        array $permitidos,
        string $campo
    ): string {

        $texto =
            strtoupper(
                trim((string)($valor ?? ''))
            );

        if (
            !in_array(
                $texto,
                $permitidos,
                true
            )
        ) {
            throw new RuntimeException(
                'A opção selecionada para '
                    . $campo
                    . ' é inválida.'
            );
        }

        return $texto;
    }

    private function enumOpcional(
        mixed $valor,
        array $permitidos,
        string $campo
    ): ?string {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        return $this->enumObrigatorio(
            $valor,
            $permitidos,
            $campo
        );
    }

    private function textoOpcional(
        mixed $valor
    ): ?string {

        $texto =
            trim((string)($valor ?? ''));

        return $texto !== ''
            ? $texto
            : null;
    }

    private function booleano(
        mixed $valor
    ): int {

        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }

        $texto =
            strtolower(
                trim((string)$valor)
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

    private function normalizarTexto(
        string $valor
    ): string {

        return mb_strtolower(
            trim($valor),
            'UTF-8'
        );
    }
}
