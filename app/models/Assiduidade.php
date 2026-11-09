<?php

declare(strict_types=1);

final class Assiduidade
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
        int $empresaId,
        ?string $dataInicio = null,
        ?string $dataFim = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $hoje = date('Y-m-d');

        $dataInicio =
            $dataInicio !== null
            ? $this->validarData(
                $dataInicio,
                'data inicial'
            )
            : date('Y-m-01');

        $dataFim =
            $dataFim !== null
            ? $this->validarData(
                $dataFim,
                'data final'
            )
            : date('Y-m-t');

        if ($dataFim < $dataInicio) {
            throw new RuntimeException(
                'A data final não pode ser anterior à data inicial.'
            );
        }

        return [
            'resumo' =>
            $this->resumo(
                $empresaId,
                $dataInicio,
                $dataFim
            ),

            'assiduidade' =>
            $this->listarAssiduidade(
                $empresaId,
                [
                    'data_inicio' =>
                    $dataInicio,

                    'data_fim' =>
                    $dataFim
                ]
            ),

            'registos_ponto' =>
            $this->listarRegistosPonto(
                $empresaId,
                [
                    'data_inicio' =>
                    $dataInicio,

                    'data_fim' =>
                    $dataFim
                ]
            ),

            'faltas' =>
            $this->listarFaltas(
                $empresaId,
                [
                    'data_inicio' =>
                    $dataInicio,

                    'data_fim' =>
                    $dataFim
                ]
            ),

            'horas_extras' =>
            $this->listarHorasExtras(
                $empresaId,
                [
                    'data_inicio' =>
                    $dataInicio,

                    'data_fim' =>
                    $dataFim
                ]
            ),

            'funcionarios' =>
            $this->listarFuncionarios(
                $empresaId
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'tipos_registo' =>
            $this->listarTiposRegisto(),

            'origens_registo' =>
            $this->listarOrigensRegisto(),

            'ocorrencias' =>
            $this->listarOcorrencias(),

            'tipos_falta' =>
            $this->listarTiposFalta(),

            'tipos_hora_extra' =>
            $this->listarTiposHoraExtra(),

            'status_registo' =>
            $this->listarStatus(
                'registo_ponto'
            ),

            'status_assiduidade' =>
            $this->listarStatus(
                'assiduidade_diaria'
            ),

            'status_falta' =>
            $this->listarStatus(
                'falta'
            ),

            'status_hora_extra' =>
            $this->listarStatus(
                'hora_extra'
            ),

            'periodo' => [
                'data_inicio' =>
                $dataInicio,

                'data_fim' =>
                $dataFim,

                'hoje' =>
                $hoje
            ]
        ];
    }

    // ==================================================
    // RESUMO
    // ==================================================

    public function resumo(
        int $empresaId,
        string $dataInicio,
        string $dataFim
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $dataInicio =
            $this->validarData(
                $dataInicio,
                'data inicial'
            );

        $dataFim =
            $this->validarData(
                $dataFim,
                'data final'
            );

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS total_registos,

                COUNT(
                    DISTINCT
                    assiduidade.funcionario_id
                ) AS funcionarios_registados,

                SUM(
                    CASE
                        WHEN assiduidade.presente = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS presencas,

                SUM(
                    CASE
                        WHEN assiduidade.falta_dia_inteiro = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS faltas_dia_inteiro,

                SUM(
                    CASE
                        WHEN assiduidade.minutos_atraso > 0
                        THEN 1
                        ELSE 0
                    END
                ) AS atrasos,

                SUM(
                    CASE
                        WHEN assiduidade.minutos_saida_antecipada > 0
                        THEN 1
                        ELSE 0
                    END
                ) AS saidas_antecipadas,

                COALESCE(
                    SUM(
                        assiduidade.minutos_trabalhados
                    ),
                    0
                ) AS minutos_trabalhados,

                COALESCE(
                    SUM(
                        assiduidade.minutos_atraso
                    ),
                    0
                ) AS minutos_atraso,

                COALESCE(
                    SUM(
                        assiduidade.minutos_extra
                    ),
                    0
                ) AS minutos_extra,

                COALESCE(
                    SUM(
                        assiduidade.minutos_falta
                    ),
                    0
                ) AS minutos_falta

            FROM assiduidade_diaria assiduidade

            WHERE assiduidade.empresa_id =
                :empresa_id

              AND assiduidade.data_assiduidade
                  BETWEEN :data_inicio
                  AND :data_fim
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':data_inicio' =>
            $dataInicio,

            ':data_fim' =>
            $dataFim
        ]);

        $resumo =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];

        $stmtFuncionarios =
            $this->pdo->prepare("
                SELECT
                    COUNT(*) AS total_funcionarios

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
            ");

        $stmtFuncionarios->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        $totalFuncionarios =
            (int)$stmtFuncionarios
                ->fetchColumn();

        $presencas =
            (int)(
                $resumo['presencas']
                ?? 0
            );

        $faltas =
            (int)(
                $resumo['faltas_dia_inteiro']
                ?? 0
            );

        $totalDias =
            $presencas
            + $faltas;

        $taxaAssiduidade =
            $totalDias > 0
            ? round(
                (
                    $presencas
                    / $totalDias
                ) * 100,
                2
            )
            : 0.00;

        return [
            'total_funcionarios' =>
            $totalFuncionarios,

            'funcionarios_registados' =>
            (int)(
                $resumo['funcionarios_registados']
                ?? 0
            ),

            'total_registos' =>
            (int)(
                $resumo['total_registos']
                ?? 0
            ),

            'presencas' =>
            $presencas,

            'faltas' =>
            $faltas,

            'atrasos' =>
            (int)(
                $resumo['atrasos']
                ?? 0
            ),

            'saidas_antecipadas' =>
            (int)(
                $resumo['saidas_antecipadas']
                ?? 0
            ),

            'minutos_trabalhados' =>
            (int)(
                $resumo['minutos_trabalhados']
                ?? 0
            ),

            'minutos_atraso' =>
            (int)(
                $resumo['minutos_atraso']
                ?? 0
            ),

            'minutos_extra' =>
            (int)(
                $resumo['minutos_extra']
                ?? 0
            ),

            'minutos_falta' =>
            (int)(
                $resumo['minutos_falta']
                ?? 0
            ),

            'taxa_assiduidade' =>
            $taxaAssiduidade
        ];
    }

    // ==================================================
    // REGISTOS DE PONTO — LISTAR
    // ==================================================

    public function listarRegistosPonto(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $filtros =
            $this->normalizarFiltrosComuns(
                $filtros
            );

        $tipoRegisto =
            $this->enumOpcional(
                $filtros['tipo_registo']
                    ?? null,
                [
                    'ENTRADA',
                    'INICIO_INTERVALO',
                    'FIM_INTERVALO',
                    'SAIDA'
                ],
                'tipo de registo'
            );

        $origem =
            $this->enumOpcional(
                $filtros['origem']
                    ?? null,
                [
                    'MANUAL',
                    'BIOMETRICO',
                    'CARTAO',
                    'APLICACAO',
                    'IMPORTACAO',
                    'OUTRO'
                ],
                'origem do registo'
            );

        $sql = "
            SELECT
                registo.id,
                registo.uuid,
                registo.empresa_id,
                registo.funcionario_id,
                registo.filial_id,
                registo.data_registo,
                registo.hora_registo,
                registo.data_hora_registo,
                registo.tipo_registo,
                registo.origem,
                registo.dispositivo,
                registo.identificador_dispositivo,
                registo.latitude,
                registo.longitude,
                registo.endereco_localizacao,
                registo.ip,
                registo.observacoes,
                registo.justificacao,
                registo.registo_original_id,
                registo.corrigido,
                registo.status_id,
                registo.validado_por,
                registo.validado_em,
                registo.criado_por,
                registo.atualizado_por,
                registo.criado_em,
                registo.atualizado_em,

                funcionario.numero_funcionario,
                funcionario.nome_completo
                    AS funcionario_nome,

                filial.nome
                    AS filial_nome,

                filial.codigo
                    AS filial_codigo,

                status.nome
                    AS status_nome,

                status.descricao
                    AS status_descricao,

                utilizador.nome
                    AS validado_por_nome

            FROM registos_ponto registo

            INNER JOIN funcionarios funcionario
                ON funcionario.id =
                    registo.funcionario_id

               AND funcionario.empresa_id =
                    registo.empresa_id

            LEFT JOIN filiais filial
                ON filial.id =
                    registo.filial_id

               AND filial.empresa_id =
                    registo.empresa_id

            INNER JOIN status
                ON status.id =
                    registo.status_id

               AND status.tipo =
                    'registo_ponto'

            LEFT JOIN usuarios utilizador
                ON utilizador.id =
                    registo.validado_por

            WHERE registo.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        $this->aplicarFiltrosComuns(
            $sql,
            $params,
            $filtros,
            'registo',
            'data_registo'
        );

        if ($tipoRegisto !== null) {
            $sql .= "
                AND registo.tipo_registo =
                    :tipo_registo
            ";

            $params[':tipo_registo'] =
                $tipoRegisto;
        }

        if ($origem !== null) {
            $sql .= "
                AND registo.origem =
                    :origem
            ";

            $params[':origem'] =
                $origem;
        }

        $sql .= "
            ORDER BY
                registo.data_hora_registo DESC,
                registo.id DESC
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
    // REGISTOS DE PONTO — BUSCAR
    // ==================================================

    public function buscarRegistoPonto(
        int $empresaId,
        int $registoId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarId(
            $registoId,
            'Registo de ponto'
        );

        $registos =
            $this->listarRegistosPonto(
                $empresaId,
                [
                    'id' =>
                    $registoId
                ]
            );

        foreach ($registos as $registo) {
            if (
                (int)$registo['id']
                === $registoId
            ) {
                return $registo;
            }
        }

        throw new RuntimeException(
            'Registo de ponto não encontrado.'
        );
    }

    // ==================================================
    // GUARDAR ASSIDUIDADE EM LOTE
    // ==================================================

    public function guardarAssiduidadeLote(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );


        $data =
            $this->dataObrigatoria(
                $dados['data']
                    ?? null,
                'data da assiduidade'
            );


        $origem =
            $this->enumObrigatorio(
                $dados['origem']
                    ?? 'MANUAL',
                [
                    'MANUAL',
                    'BIOMETRICO',
                    'CARTAO',
                    'APLICACAO',
                    'IMPORTACAO',
                    'OUTRO'
                ],
                'origem do registo'
            );


        $funcionarios =
            $dados['funcionarios']
            ?? [];


        if (
            !is_array($funcionarios)
            || empty($funcionarios)
        ) {
            throw new RuntimeException(
                'Selecione pelo menos um funcionário.'
            );
        }


        $dispositivo =
            $this->textoOpcional(
                $dados['dispositivo']
                    ?? null
            );


        $identificador =
            $this->textoOpcional(
                $dados['identificador_dispositivo']
                    ?? null
            );


        $observacaoGeral =
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            );


        $resultado = [
            'total' =>
            count($funcionarios),

            'presentes' =>
            0,

            'faltas' =>
            0,

            'registos_criados' =>
            0
        ];


        /*
     * Uma única transação.
     *
     * Se um funcionário falhar,
     * nenhuma parte do lote ficará gravada.
     */

        $iniciouTransacao =
            !$this->pdo
                ->inTransaction();


        if ($iniciouTransacao) {
            $this->pdo
                ->beginTransaction();
        }


        try {

            foreach (
                $funcionarios
                as $indice => $item
            ) {

                if (
                    !is_array($item)
                ) {
                    throw new RuntimeException(
                        'Dados inválidos do funcionário na posição '
                            . ($indice + 1)
                            . '.'
                    );
                }


                $funcionarioId =
                    (int)(
                        $item['funcionario_id']
                        ?? 0
                    );


                if (
                    $funcionarioId <= 0
                ) {
                    throw new RuntimeException(
                        'Funcionário inválido na posição '
                            . ($indice + 1)
                            . '.'
                    );
                }


                /*
             * Garante que pertence
             * à empresa atual.
             */

                $funcionario =
                    $this->buscarFuncionario(
                        $empresaId,
                        $funcionarioId
                    );

                $this->validarHorarioFuncionario(
                    $funcionario
                );

                $horario =
                    $this->buscarHorarioFuncionario(
                        $empresaId,
                        $funcionario
                    );

                $this->validarDiaParaRegisto(
                    $empresaId,
                    $funcionario,
                    $horario,
                    $data
                );

                $situacao =
                    strtoupper(
                        trim(
                            (string)(
                                $item['situacao']
                                ?? ''
                            )
                        )
                    );


                if (
                    !in_array(
                        $situacao,
                        [
                            'PRESENTE',
                            'FALTA'
                        ],
                        true
                    )
                ) {
                    throw new RuntimeException(
                        'Situação inválida para o funcionário '
                            . (
                                $funcionario['nome_completo']
                                ?? '#' . $funcionarioId
                            )
                            . '.'
                    );
                }


                /*
             * O lote é para a marcação inicial
             * do dia.
             *
             * Se já houver ponto, utilizar
             * o Registo Individual para corrigir
             * ou complementar.
             */

                $stmtExistente =
                    $this->pdo
                    ->prepare("
                        SELECT
                            COUNT(*)

                        FROM registos_ponto registo

                        INNER JOIN status
                            ON status.id =
                                registo.status_id

                           AND status.tipo =
                                'registo_ponto'

                        WHERE registo.empresa_id =
                            :empresa_id

                          AND registo.funcionario_id =
                            :funcionario_id

                          AND registo.data_registo =
                            :data_registo

                          AND LOWER(status.nome)
                              NOT IN
                              (
                                  'anulado',
                                  'anulada'
                              )
                    ");


                $stmtExistente
                    ->execute([
                        ':empresa_id' =>
                        $empresaId,

                        ':funcionario_id' =>
                        $funcionarioId,

                        ':data_registo' =>
                        $data
                    ]);


                if (
                    (int)$stmtExistente
                        ->fetchColumn() > 0
                ) {

                    throw new RuntimeException(
                        'Já existem registos de ponto para '
                            . (
                                $funcionario['nome_completo']
                                ?? 'o funcionário #'
                                . $funcionarioId
                            )
                            . ' nesta data. '
                            . 'Utilize o Registo Individual para alterações.'
                    );
                }


                // ==========================================
                // FALTA
                // ==========================================

                if (
                    $situacao === 'FALTA'
                ) {

                    $this->registarFalta(
                        $empresaId,
                        $usuarioId,
                        $funcionarioId,
                        $data
                    );

                    $resultado['faltas']++;

                    continue;
                }


                // ==========================================
                // PRESENTE
                // ==========================================

                $entrada =
                    $this->horaObrigatoria(
                        $item['entrada']
                            ?? null,
                        'hora de entrada'
                    );


                $inicioIntervalo =
                    $this->horaObrigatoria(
                        $item['inicio_intervalo']
                            ?? null,
                        'hora de início do almoço'
                    );


                $fimIntervalo =
                    $this->horaObrigatoria(
                        $item['fim_intervalo']
                            ?? null,
                        'hora de regresso do almoço'
                    );


                $saida =
                    $this->horaObrigatoria(
                        $item['saida']
                            ?? null,
                        'hora de saída'
                    );


                /*
             * Mantemos a mesma sequência
             * que validarSequenciaPonto()
             * já exige.
             */

                if (
                    !(
                        $entrada
                        < $inicioIntervalo
                        && $inicioIntervalo
                        < $fimIntervalo
                        && $fimIntervalo
                        < $saida
                    )
                ) {

                    throw new RuntimeException(
                        'A sequência das horas é inválida para '
                            . (
                                $funcionario['nome_completo']
                                ?? 'o funcionário #'
                                . $funcionarioId
                            )
                            . '.'
                    );
                }


                $observacaoIndividual =
                    $this->textoOpcional(
                        $item['observacoes']
                            ?? null
                    );


                $observacoes = [];

                if (
                    $observacaoGeral !== null
                ) {
                    $observacoes[] =
                        $observacaoGeral;
                }

                if (
                    $observacaoIndividual
                    !== null
                ) {
                    $observacoes[] =
                        $observacaoIndividual;
                }


                $observacaoFinal =
                    !empty($observacoes)
                    ? implode(
                        ' | ',
                        $observacoes
                    )
                    : null;


                $filialId =
                    !empty($item['filial_id'])
                    ? (int)$item['filial_id']
                    : (
                        !empty($funcionario['filial_id'])
                        ? (int)$funcionario['filial_id']
                        : null
                    );


                /*
             * IMPORTANTE:
             *
             * Chamamos o método que já existe.
             * Não repetimos o código de INSERT.
             */

                $registos = [
                    [
                        'tipo' =>
                        'ENTRADA',

                        'hora' =>
                        $entrada
                    ],

                    [
                        'tipo' =>
                        'INICIO_INTERVALO',

                        'hora' =>
                        $inicioIntervalo
                    ],

                    [
                        'tipo' =>
                        'FIM_INTERVALO',

                        'hora' =>
                        $fimIntervalo
                    ],

                    [
                        'tipo' =>
                        'SAIDA',

                        'hora' =>
                        $saida
                    ]
                ];


                foreach (
                    $registos
                    as $registo
                ) {

                    $this->criarRegistoPonto(
                        $empresaId,
                        $usuarioId,
                        [
                            'funcionario_id' =>
                            $funcionarioId,

                            'filial_id' =>
                            $filialId,

                            'data_registo' =>
                            $data,

                            'hora_registo' =>
                            $registo['hora'],

                            'tipo_registo' =>
                            $registo['tipo'],

                            'origem' =>
                            $origem,

                            'dispositivo' =>
                            $dispositivo,

                            'identificador_dispositivo' =>
                            $identificador,

                            'observacoes' =>
                            $observacaoFinal,

                            'corrigido' =>
                            0
                        ]
                    );


                    $resultado['registos_criados']++;
                }


                $resultado['presentes']++;
            }


            if ($iniciouTransacao) {

                $this->pdo
                    ->commit();
            }


            return $resultado;
        } catch (Throwable $e) {

            if (
                $iniciouTransacao
                && $this->pdo
                ->inTransaction()
            ) {

                $this->pdo
                    ->rollBack();
            }


            throw $e;
        }
    }

    // ==================================================
    // REGISTOS DE PONTO — CRIAR
    // ==================================================

    public function criarRegistoPonto(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {
        $statusId = $this->buscarStatusId(
            'registo_ponto',
            'Pendente'
        );

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $dados =
            $this->normalizarRegistoPonto(
                $empresaId,
                $dados
            );

        $funcionario =
            $this->buscarFuncionario(
                $empresaId,
                $dados['funcionario_id']
            );

        $this->validarHorarioFuncionario(
            $funcionario
        );

        $horario =
            $this->buscarHorarioFuncionario(
                $empresaId,
                $funcionario
            );

        $this->validarDiaParaRegisto(
            $empresaId,
            $funcionario,
            $horario,
            $dados['data_registo']
        );

        $this->validarDisponibilidadeDia(
            $empresaId,
            $dados['funcionario_id'],
            $dados['data_registo'],
            'PRESENCA'
        );


        $this->validarSequenciaPonto(
            $empresaId,
            $dados['funcionario_id'],
            $dados['data_registo'],
            $dados['tipo_registo'],
            $dados['data_hora_registo']
        );

        $stmt = $this->pdo->prepare("
            INSERT INTO registos_ponto
            (
                uuid,
                empresa_id,
                funcionario_id,
                filial_id,
                data_registo,
                hora_registo,
                data_hora_registo,
                tipo_registo,
                origem,
                dispositivo,
                identificador_dispositivo,
                latitude,
                longitude,
                endereco_localizacao,
                ip,
                observacoes,
                justificacao,
                registo_original_id,
                corrigido,
                status_id,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                UUID(),
                :empresa_id,
                :funcionario_id,
                :filial_id,
                :data_registo,
                :hora_registo,
                :data_hora_registo,
                :tipo_registo,
                :origem,
                :dispositivo,
                :identificador_dispositivo,
                :latitude,
                :longitude,
                :endereco_localizacao,
                :ip,
                :observacoes,
                :justificacao,
                :registo_original_id,
                :corrigido,
                :status_id,
                :criado_por,
                :atualizado_por
            )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $dados['funcionario_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':data_registo' =>
            $dados['data_registo'],

            ':hora_registo' =>
            $dados['hora_registo'],

            ':data_hora_registo' =>
            $dados['data_hora_registo'],

            ':tipo_registo' =>
            $dados['tipo_registo'],

            ':origem' =>
            $dados['origem'],

            ':dispositivo' =>
            $dados['dispositivo'],

            ':identificador_dispositivo' =>
            $dados['identificador_dispositivo'],

            ':latitude' =>
            $dados['latitude'],

            ':longitude' =>
            $dados['longitude'],

            ':endereco_localizacao' =>
            $dados['endereco_localizacao'],

            ':ip' =>
            $dados['ip'],

            ':observacoes' =>
            $dados['observacoes'],

            ':justificacao' =>
            $dados['justificacao'],

            ':registo_original_id' =>
            $dados['registo_original_id'],

            ':corrigido' =>
            $dados['corrigido'],

            ':status_id' =>
            $statusId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);

        $registoId =
            (int)$this->pdo
                ->lastInsertId();

        $this->apurarDia(
            $empresaId,
            $dados['funcionario_id'],
            $dados['data_registo'],
            $usuarioId
        );

        return $registoId;
    }

    // ==================================================
    // REGISTOS DE PONTO — EDITAR
    // ==================================================

    public function editarRegistoPonto(
        int $empresaId,
        int $usuarioId,
        int $registoId,
        array $dados
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $this->validarId(
            $registoId,
            'Registo de ponto'
        );

        $registoAnterior =
            $this->buscarRegistoPonto(
                $empresaId,
                $registoId
            );

        $dados =
            $this->normalizarRegistoPonto(
                $empresaId,
                $dados
            );

        $stmt = $this->pdo->prepare("
            UPDATE registos_ponto

            SET
                funcionario_id =
                    :funcionario_id,

                filial_id =
                    :filial_id,

                data_registo =
                    :data_registo,

                hora_registo =
                    :hora_registo,

                data_hora_registo =
                    :data_hora_registo,

                tipo_registo =
                    :tipo_registo,

                origem =
                    :origem,

                dispositivo =
                    :dispositivo,

                identificador_dispositivo =
                    :identificador_dispositivo,

                latitude =
                    :latitude,

                longitude =
                    :longitude,

                endereco_localizacao =
                    :endereco_localizacao,

                ip =
                    :ip,

                observacoes =
                    :observacoes,

                justificacao =
                    :justificacao,

                registo_original_id =
                    :registo_original_id,

                corrigido =
                    :corrigido,

                status_id =
                    :status_id,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :registo_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':funcionario_id' =>
            $dados['funcionario_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':data_registo' =>
            $dados['data_registo'],

            ':hora_registo' =>
            $dados['hora_registo'],

            ':data_hora_registo' =>
            $dados['data_hora_registo'],

            ':tipo_registo' =>
            $dados['tipo_registo'],

            ':origem' =>
            $dados['origem'],

            ':dispositivo' =>
            $dados['dispositivo'],

            ':identificador_dispositivo' =>
            $dados['identificador_dispositivo'],

            ':latitude' =>
            $dados['latitude'],

            ':longitude' =>
            $dados['longitude'],

            ':endereco_localizacao' =>
            $dados['endereco_localizacao'],

            ':ip' =>
            $dados['ip'],

            ':observacoes' =>
            $dados['observacoes'],

            ':justificacao' =>
            $dados['justificacao'],

            ':registo_original_id' =>
            $dados['registo_original_id'],

            ':corrigido' =>
            $dados['corrigido'],

            ':status_id' =>
            $dados['status_id'],

            ':atualizado_por' =>
            $usuarioId,

            ':registo_id' =>
            $registoId,

            ':empresa_id' =>
            $empresaId
        ]);

        $this->apurarDia(
            $empresaId,
            (int)$registoAnterior['funcionario_id'],
            $registoAnterior['data_registo'],
            $usuarioId
        );

        $this->apurarDia(
            $empresaId,
            $dados['funcionario_id'],
            $dados['data_registo'],
            $usuarioId
        );
    }

    // ==================================================
    // REGISTOS DE PONTO — VALIDAR
    // ==================================================

    public function validarRegistoPonto(
        int $empresaId,
        int $usuarioId,
        int $registoId
    ): void {

        $this->validarUsuario(
            $usuarioId
        );

        $registo =
            $this->buscarRegistoPonto(
                $empresaId,
                $registoId
            );

        $statusValidadoId =
            $this->statusId(
                'registo_ponto',
                [
                    'validado'
                ]
            );

        $stmt = $this->pdo->prepare("
            UPDATE registos_ponto

            SET
                status_id =
                    :status_id,

                validado_por =
                    :validado_por,

                validado_em =
                    NOW(),

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :registo_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':status_id' =>
            $statusValidadoId,

            ':validado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId,

            ':registo_id' =>
            $registoId,

            ':empresa_id' =>
            $empresaId
        ]);

        $this->apurarDia(
            $empresaId,
            (int)$registo['funcionario_id'],
            $registo['data_registo'],
            $usuarioId
        );
    }

    public function validarRegistosPontoLote(
        int $empresaId,
        int $usuarioId,
        array $registoIds
    ): array {
        $this->validarEmpresa($empresaId);
        $this->validarUsuario($usuarioId);

        $registoIds = array_values(array_unique(array_map('intval', $registoIds)));
        $placeholders = implode(',', array_fill(0, count($registoIds), '?'));
        $statusValidadoId = $this->statusId('registo_ponto', ['validado']);
        $statusPendenteId = $this->statusId('registo_ponto', ['pendente']);

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, funcionario_id, data_registo
                FROM registos_ponto
                WHERE empresa_id = ?
                  AND status_id = ?
                  AND id IN ({$placeholders})
                FOR UPDATE
            ");
            $stmt->execute(array_merge([$empresaId, $statusPendenteId], $registoIds));
            $registos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($registos) !== count($registoIds)) {
                throw new RuntimeException(
                    'Um ou mais registos já não estão pendentes ou não pertencem à empresa.'
                );
            }

            $stmt = $this->pdo->prepare("
                UPDATE registos_ponto
                SET status_id = ?,
                    validado_por = ?,
                    validado_em = NOW(),
                    atualizado_por = ?
                WHERE empresa_id = ?
                  AND status_id = ?
                  AND id IN ({$placeholders})
            ");
            $stmt->execute(array_merge(
                [$statusValidadoId, $usuarioId, $usuarioId, $empresaId, $statusPendenteId],
                $registoIds
            ));

            $dias = [];
            foreach ($registos as $registo) {
                $chave = $registo['funcionario_id'] . '|' . $registo['data_registo'];
                $dias[$chave] = $registo;
            }

            foreach ($dias as $registo) {
                $this->apurarDia(
                    $empresaId,
                    (int)$registo['funcionario_id'],
                    $registo['data_registo'],
                    $usuarioId
                );
            }

            $this->pdo->commit();

            return [
                'total_validados' => count($registos),
                'ids' => $registoIds
            ];
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $erro;
        }
    }

    // ==================================================
    // REGISTOS DE PONTO — ANULAR
    // ==================================================

    public function anularRegistoPonto(
        int $empresaId,
        int $usuarioId,
        int $registoId,
        ?string $justificacao = null
    ): void {

        $this->validarUsuario(
            $usuarioId
        );

        $registo =
            $this->buscarRegistoPonto(
                $empresaId,
                $registoId
            );

        $statusAnuladoId =
            $this->statusId(
                'registo_ponto',
                [
                    'anulado'
                ]
            );

        $stmt = $this->pdo->prepare("
            UPDATE registos_ponto

            SET
                status_id =
                    :status_id,

                justificacao =
                    :justificacao,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :registo_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':status_id' =>
            $statusAnuladoId,

            ':justificacao' =>
            $this->textoOpcional(
                $justificacao
            ),

            ':atualizado_por' =>
            $usuarioId,

            ':registo_id' =>
            $registoId,

            ':empresa_id' =>
            $empresaId
        ]);

        $this->apurarDia(
            $empresaId,
            (int)$registo['funcionario_id'],
            $registo['data_registo'],
            $usuarioId
        );
    }

    // ==================================================
    // ASSIDUIDADE — LISTAR
    // ==================================================

    public function listarAssiduidade(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $filtros =
            $this->normalizarFiltrosComuns(
                $filtros
            );

        $ocorrencia =
            $this->enumOpcional(
                $filtros['ocorrencia']
                    ?? null,
                [
                    'NORMAL',
                    'ATRASO',
                    'SAIDA_ANTECIPADA',
                    'FALTA_PARCIAL',
                    'FALTA_TOTAL',
                    'FERIAS',
                    'LICENCA',
                    'FERIADO',
                    'FOLGA',
                    'INCOMPLETO'
                ],
                'ocorrência'
            );

        $sql = "
            SELECT
                assiduidade.*,

                funcionario.numero_funcionario,
                funcionario.nome_completo
                    AS funcionario_nome,

                filial.nome
                    AS filial_nome,

                filial.codigo
                    AS filial_codigo,

                horario.nome
                    AS horario_nome,

                horario.codigo
                    AS horario_codigo,

                status.nome
                    AS status_nome,

                status.descricao
                    AS status_descricao,

                aprovador.nome
                    AS aprovado_por_nome

            FROM assiduidade_diaria assiduidade

            INNER JOIN funcionarios funcionario
                ON funcionario.id =
                    assiduidade.funcionario_id

               AND funcionario.empresa_id =
                    assiduidade.empresa_id

            LEFT JOIN filiais filial
                ON filial.id =
                    assiduidade.filial_id

               AND filial.empresa_id =
                    assiduidade.empresa_id

            LEFT JOIN horarios_trabalho horario
                ON horario.id =
                    assiduidade.horario_trabalho_id

               AND horario.empresa_id =
                    assiduidade.empresa_id

            INNER JOIN status
                ON status.id =
                    assiduidade.status_id

               AND status.tipo =
                    'assiduidade_diaria'

            LEFT JOIN usuarios aprovador
                ON aprovador.id =
                    assiduidade.aprovado_por

            WHERE assiduidade.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        $this->aplicarFiltrosComuns(
            $sql,
            $params,
            $filtros,
            'assiduidade',
            'data_assiduidade'
        );

        if ($ocorrencia !== null) {
            $sql .= "
                AND assiduidade.ocorrencia =
                    :ocorrencia
            ";

            $params[':ocorrencia'] =
                $ocorrencia;
        }

        $sql .= "
            ORDER BY
                assiduidade.data_assiduidade DESC,
                funcionario.nome_completo ASC
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
    // APURAR UM DIA
    // ==================================================

    public function apurarDia(
        int $empresaId,
        int $funcionarioId,
        string $data,
        int $usuarioId
    ): int {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $data =
            $this->validarData(
                $data,
                'data da assiduidade'
            );

        $funcionario =
            $this->buscarFuncionario(
                $empresaId,
                $funcionarioId
            );

        $horario =
            $this->buscarHorarioFuncionario(
                $empresaId,
                $funcionario
            );

        $registos =
            $this->registosValidosDoDia(
                $empresaId,
                $funcionarioId,
                $data
            );

        $horaEntrada =
            $this->primeiraHoraPorTipo(
                $registos,
                'ENTRADA'
            );

        $horaSaida =
            $this->ultimaHoraPorTipo(
                $registos,
                'SAIDA'
            );

        $inicioIntervalo =
            $this->primeiraHoraPorTipo(
                $registos,
                'INICIO_INTERVALO'
            );

        $fimIntervalo =
            $this->ultimaHoraPorTipo(
                $registos,
                'FIM_INTERVALO'
            );

        $horaPrevistaEntrada =
            $horario['hora_entrada']
            ?? null;

        $horaPrevistaSaida =
            $horario['hora_saida']
            ?? null;

        $minutosIntervalo =
            $this->diferencaMinutos(
                $inicioIntervalo,
                $fimIntervalo
            );

        $minutosTrabalhados =
            $this->diferencaMinutos(
                $horaEntrada,
                $horaSaida
            );

        if ($minutosTrabalhados > 0) {
            $minutosTrabalhados =
                max(
                    0,
                    $minutosTrabalhados
                        - $minutosIntervalo
                );
        }

        $minutosPrevistos =
            $this->calcularMinutosPrevistos(
                $horario
            );

        $minutosAtraso =
            $this->calcularAtraso(
                $horaPrevistaEntrada,
                $horaEntrada
            );

        $minutosSaidaAntecipada =
            $this->calcularSaidaAntecipada(
                $horaPrevistaSaida,
                $horaSaida
            );

        $minutosExtra =
            max(
                0,
                $minutosTrabalhados
                    - $minutosPrevistos
            );

        $presente =
            $horaEntrada !== null
            || $horaSaida !== null;

        $faltaDiaInteiro =
            !$presente
            && $minutosPrevistos > 0;

        $minutosFalta =
            $faltaDiaInteiro
            ? $minutosPrevistos
            : max(
                0,
                $minutosPrevistos
                    - $minutosTrabalhados
            );

        $ocorrencia =
            $this->determinarOcorrencia(
                $horaEntrada,
                $horaSaida,
                $minutosAtraso,
                $minutosSaidaAntecipada,
                $faltaDiaInteiro,
                $minutosFalta
            );

        $statusId =
            $this->statusId(
                'assiduidade_diaria',
                [
                    'apurada'
                ]
            );

        $diaSemana =
            (int)(
                new DateTimeImmutable(
                    $data
                )
            )->format('N');

        $stmt = $this->pdo->prepare("
            INSERT INTO assiduidade_diaria
            (
                uuid,
                empresa_id,
                funcionario_id,
                filial_id,
                horario_trabalho_id,
                data_assiduidade,
                dia_semana,
                hora_prevista_entrada,
                hora_prevista_saida,
                hora_real_entrada,
                hora_real_saida,
                inicio_intervalo,
                fim_intervalo,
                minutos_previstos,
                minutos_trabalhados,
                minutos_intervalo,
                minutos_atraso,
                minutos_saida_antecipada,
                minutos_extra,
                minutos_falta,
                presente,
                falta_dia_inteiro,
                dia_util,
                feriado,
                folga,
                em_ferias,
                em_licenca,
                trabalho_nocturno,
                trabalho_feriado,
                ocorrencia,
                status_id,
                apurado_em,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                UUID(),
                :empresa_id,
                :funcionario_id,
                :filial_id,
                :horario_trabalho_id,
                :data_assiduidade,
                :dia_semana,
                :hora_prevista_entrada,
                :hora_prevista_saida,
                :hora_real_entrada,
                :hora_real_saida,
                :inicio_intervalo,
                :fim_intervalo,
                :minutos_previstos,
                :minutos_trabalhados,
                :minutos_intervalo,
                :minutos_atraso,
                :minutos_saida_antecipada,
                :minutos_extra,
                :minutos_falta,
                :presente,
                :falta_dia_inteiro,
                :dia_util,
                0,
                0,
                0,
                0,
                :trabalho_nocturno,
                0,
                :ocorrencia,
                :status_id,
                NOW(),
                :criado_por,
                :atualizado_por
            )

            ON DUPLICATE KEY UPDATE
                filial_id =
                    VALUES(filial_id),

                horario_trabalho_id =
                    VALUES(horario_trabalho_id),

                dia_semana =
                    VALUES(dia_semana),

                hora_prevista_entrada =
                    VALUES(hora_prevista_entrada),

                hora_prevista_saida =
                    VALUES(hora_prevista_saida),

                hora_real_entrada =
                    VALUES(hora_real_entrada),

                hora_real_saida =
                    VALUES(hora_real_saida),

                inicio_intervalo =
                    VALUES(inicio_intervalo),

                fim_intervalo =
                    VALUES(fim_intervalo),

                minutos_previstos =
                    VALUES(minutos_previstos),

                minutos_trabalhados =
                    VALUES(minutos_trabalhados),

                minutos_intervalo =
                    VALUES(minutos_intervalo),

                minutos_atraso =
                    VALUES(minutos_atraso),

                minutos_saida_antecipada =
                    VALUES(minutos_saida_antecipada),

                minutos_extra =
                    VALUES(minutos_extra),

                minutos_falta =
                    VALUES(minutos_falta),

                presente =
                    VALUES(presente),

                falta_dia_inteiro =
                    VALUES(falta_dia_inteiro),

                trabalho_nocturno =
                    VALUES(trabalho_nocturno),

                ocorrencia =
                    VALUES(ocorrencia),

                status_id =
                    VALUES(status_id),

                apurado_em =
                    NOW(),

                atualizado_por =
                    VALUES(atualizado_por)
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':filial_id' =>
            $funcionario['filial_id']
                ?? null,

            ':horario_trabalho_id' =>
            $funcionario['horario_trabalho_id']
                ?? null,

            ':data_assiduidade' =>
            $data,

            ':dia_semana' =>
            $diaSemana,

            ':hora_prevista_entrada' =>
            $horaPrevistaEntrada,

            ':hora_prevista_saida' =>
            $horaPrevistaSaida,

            ':hora_real_entrada' =>
            $horaEntrada,

            ':hora_real_saida' =>
            $horaSaida,

            ':inicio_intervalo' =>
            $inicioIntervalo,

            ':fim_intervalo' =>
            $fimIntervalo,

            ':minutos_previstos' =>
            $minutosPrevistos,

            ':minutos_trabalhados' =>
            $minutosTrabalhados,

            ':minutos_intervalo' =>
            $minutosIntervalo,

            ':minutos_atraso' =>
            $minutosAtraso,

            ':minutos_saida_antecipada' =>
            $minutosSaidaAntecipada,

            ':minutos_extra' =>
            $minutosExtra,

            ':minutos_falta' =>
            $minutosFalta,

            ':presente' =>
            $presente ? 1 : 0,

            ':falta_dia_inteiro' =>
            $faltaDiaInteiro ? 1 : 0,

            ':dia_util' =>
            $minutosPrevistos > 0
                ? 1
                : 0,

            ':trabalho_nocturno' =>
            $this->houveTrabalhoNocturno(
                $horaEntrada,
                $horaSaida
            ),

            ':ocorrencia' =>
            $ocorrencia,

            ':status_id' =>
            $statusId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);

        $assiduidadeId =
            $this->buscarAssiduidadeId(
                $empresaId,
                $funcionarioId,
                $data
            );

        $this->sincronizarFaltaAutomatica(
            $empresaId,
            $funcionarioId,
            $assiduidadeId,
            $data,
            $faltaDiaInteiro,
            $minutosFalta,
            $minutosPrevistos,
            $usuarioId
        );

        $this->sincronizarHoraExtraAutomatica(
            $empresaId,
            $funcionarioId,
            $assiduidadeId,
            $data,
            $minutosExtra,
            $usuarioId
        );

        return $assiduidadeId;
    }

    // ==================================================
    // APROVAR ASSIDUIDADE
    // ==================================================

    public function aprovarAssiduidade(
        int $empresaId,
        int $usuarioId,
        int $assiduidadeId
    ): void {

        $this->validarUsuario(
            $usuarioId
        );

        $statusId =
            $this->statusId(
                'assiduidade_diaria',
                [
                    'aprovada'
                ]
            );

        $stmt = $this->pdo->prepare("
            UPDATE assiduidade_diaria

            SET
                status_id =
                    :status_id,

                aprovado_por =
                    :aprovado_por,

                aprovado_em =
                    NOW(),

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :assiduidade_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId,

            ':aprovado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId,

            ':assiduidade_id' =>
            $assiduidadeId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Assiduidade diária não encontrada.'
            );
        }
    }

    // ==================================================
    // ASSIDUIDADE — APROVAR EM LOTE
    // ==================================================

    public function aprovarAssiduidadesLote(
        int $empresaId,
        int $usuarioId,
        array $assiduidadeIds
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        // ----------------------------------------------
        // NORMALIZAR IDS
        // ----------------------------------------------

        $assiduidadeIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $assiduidadeIds
                        ),
                        static function (
                            int $id
                        ): bool {

                            return $id > 0;
                        }
                    )
                )
            );

        if (empty($assiduidadeIds)) {

            throw new InvalidArgumentException(
                'Selecione pelo menos uma assiduidade.'
            );
        }

        if (count($assiduidadeIds) > 500) {

            throw new InvalidArgumentException(
                'Selecione no máximo 500 registos de cada vez.'
            );
        }

        // Um placeholder para cada ID recebido
        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count($assiduidadeIds),
                    '?'
                )
            );

        $statusAprovadoId =
            $this->statusId(
                'assiduidade_diaria',
                [
                    'aprovada'
                ]
            );

        $this->pdo->beginTransaction();

        try {

            // ------------------------------------------
            // BLOQUEAR E VALIDAR OS REGISTOS
            // ------------------------------------------

            $stmt =
                $this->pdo->prepare("
                SELECT
                    assiduidade.id

                FROM assiduidade_diaria
                    AS assiduidade

                INNER JOIN status

                    ON status.id =
                        assiduidade.status_id

                   AND status.tipo =
                        'assiduidade_diaria'

                WHERE assiduidade.empresa_id =
                    ?

                  AND LOWER(status.nome) IN (
                      'aberta',
                      'apurada'
                  )

                  AND assiduidade.id IN (
                      {$placeholders}
                  )

                FOR UPDATE
            ");

            $stmt->execute(
                array_merge(
                    [
                        $empresaId
                    ],
                    $assiduidadeIds
                )
            );

            $registos =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );

            /*
         * Se a quantidade encontrada for diferente,
         * algum registo:
         *
         * - não pertence à empresa;
         * - já foi aprovado;
         * - não existe;
         * - possui um estado que não permite aprovação.
         */
            if (
                count($registos)
                !== count($assiduidadeIds)
            ) {

                throw new RuntimeException(
                    'Uma ou mais assiduidades já não podem ser aprovadas ou não pertencem à empresa.'
                );
            }

            // ------------------------------------------
            // APROVAR OS REGISTOS
            // ------------------------------------------

            $stmt =
                $this->pdo->prepare("
                UPDATE assiduidade_diaria

                SET
                    status_id =
                        ?,

                    aprovado_por =
                        ?,

                    aprovado_em =
                        NOW(),

                    atualizado_por =
                        ?

                WHERE empresa_id =
                    ?

                  AND id IN (
                      {$placeholders}
                  )
            ");

            $stmt->execute(
                array_merge(
                    [
                        $statusAprovadoId,
                        $usuarioId,
                        $usuarioId,
                        $empresaId
                    ],
                    $assiduidadeIds
                )
            );

            if (
                $stmt->rowCount()
                !== count($assiduidadeIds)
            ) {

                throw new RuntimeException(
                    'Não foi possível aprovar todos os registos selecionados.'
                );
            }

            $this->pdo->commit();

            return [
                'total_aprovados' =>
                count($registos),

                'ids' =>
                $assiduidadeIds
            ];
        } catch (Throwable $erro) {

            if ($this->pdo->inTransaction()) {

                $this->pdo->rollBack();
            }

            throw $erro;
        }
    }

    // ==================================================
    // FALTAS — LISTAR
    // ==================================================

    public function listarFaltas(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $filtros =
            $this->normalizarFiltrosComuns(
                $filtros
            );

        $sql = "
            SELECT
                falta.*,

                funcionario.numero_funcionario,
                funcionario.nome_completo
                    AS funcionario_nome,

                status.nome
                    AS status_nome,

                status.descricao
                    AS status_descricao,

                analisador.nome
                    AS analisado_por_nome

            FROM faltas falta

            INNER JOIN funcionarios funcionario
                ON funcionario.id =
                    falta.funcionario_id

               AND funcionario.empresa_id =
                    falta.empresa_id

            INNER JOIN status
                ON status.id =
                    falta.status_id

               AND status.tipo =
                    'falta'

            LEFT JOIN usuarios analisador
                ON analisador.id =
                    falta.analisado_por

            WHERE falta.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        $this->aplicarFiltrosComuns(
            $sql,
            $params,
            $filtros,
            'falta',
            'data_inicio'
        );

        $sql .= "
            ORDER BY
                falta.data_inicio DESC,
                falta.id DESC
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
    // FALTAS — JUSTIFICAR
    // ==================================================

    public function justificarFalta(
        int $empresaId,
        int $usuarioId,
        int $faltaId,
        array $dados
    ): void {

        $this->validarUsuario(
            $usuarioId
        );

        $statusId =
            $this->statusId(
                'falta',
                [
                    'justificada'
                ]
            );

        $justificacao =
            $this->textoObrigatorio(
                $dados['justificacao']
                    ?? null,
                'justificação'
            );

        $stmt = $this->pdo->prepare("
            UPDATE faltas

            SET
                justificacao =
                    :justificacao,

                documento_justificacao =
                    :documento_justificacao,

                remunerada =
                    :remunerada,

                desconta_salario =
                    :desconta_salario,

                desconta_ferias =
                    :desconta_ferias,

                status_id =
                    :status_id,

                analisado_por =
                    :analisado_por,

                analisado_em =
                    NOW(),

                observacoes =
                    :observacoes,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :falta_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':justificacao' =>
            $justificacao,

            ':documento_justificacao' =>
            $this->textoOpcional(
                $dados['documento_justificacao']
                    ?? null
            ),

            ':remunerada' =>
            $this->booleano(
                $dados['remunerada']
                    ?? 0
            ),

            ':desconta_salario' =>
            $this->booleano(
                $dados['desconta_salario']
                    ?? 0
            ),

            ':desconta_ferias' =>
            $this->booleano(
                $dados['desconta_ferias']
                    ?? 0
            ),

            ':status_id' =>
            $statusId,

            ':analisado_por' =>
            $usuarioId,

            ':observacoes' =>
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            ),

            ':atualizado_por' =>
            $usuarioId,

            ':falta_id' =>
            $faltaId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Falta não encontrada.'
            );
        }
    }

    // ==================================================
    // FALTAS — MARCAR INJUSTIFICADA
    // ==================================================

    public function marcarFaltaInjustificada(
        int $empresaId,
        int $usuarioId,
        int $faltaId,
        ?string $observacoes = null
    ): void {

        $statusId =
            $this->statusId(
                'falta',
                [
                    'injustificada'
                ]
            );

        $stmt = $this->pdo->prepare("
            UPDATE faltas

            SET
                status_id =
                    :status_id,

                remunerada =
                    0,

                desconta_salario =
                    1,

                analisado_por =
                    :analisado_por,

                analisado_em =
                    NOW(),

                observacoes =
                    :observacoes,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :falta_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId,

            ':analisado_por' =>
            $usuarioId,

            ':observacoes' =>
            $this->textoOpcional(
                $observacoes
            ),

            ':atualizado_por' =>
            $usuarioId,

            ':falta_id' =>
            $faltaId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Falta não encontrada.'
            );
        }
    }

    // ==================================================
    // HORAS EXTRAS — LISTAR
    // ==================================================

    public function listarHorasExtras(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $filtros =
            $this->normalizarFiltrosComuns(
                $filtros
            );

        $sql = "
            SELECT
                hora_extra.*,

                funcionario.numero_funcionario,
                funcionario.nome_completo
                    AS funcionario_nome,

                status.nome
                    AS status_nome,

                status.descricao
                    AS status_descricao,

                aprovador.nome
                    AS aprovado_por_nome

            FROM horas_extras hora_extra

            INNER JOIN funcionarios funcionario
                ON funcionario.id =
                    hora_extra.funcionario_id

               AND funcionario.empresa_id =
                    hora_extra.empresa_id

            INNER JOIN status
                ON status.id =
                    hora_extra.status_id

               AND status.tipo =
                    'hora_extra'

            LEFT JOIN usuarios aprovador
                ON aprovador.id =
                    hora_extra.aprovado_por

            WHERE hora_extra.empresa_id =
                :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        $this->aplicarFiltrosComuns(
            $sql,
            $params,
            $filtros,
            'hora_extra',
            'data_hora_extra'
        );

        $sql .= "
            ORDER BY
                hora_extra.data_hora_extra DESC,
                hora_extra.id DESC
        ";

        $stmt =
            $this->pdo->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );

        $linhas =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach ($linhas as &$linha) {

            $dataHoraExtra =
                (string)(
                    $linha['data_hora_extra']
                    ?? ''
                );

            $funcionarioId =
                (int)(
                    $linha['funcionario_id']
                    ?? 0
                );

            // ==============================================
            // CONFIGURAÇÃO SALARIAL
            // ==============================================

            $salario = null;

            $salarioBase = 0.00;

            $horasMensais = 0.00;

            $salarioConfigurado = false;


            try {

                $salario =
                    $this->buscarSalarioVigenteHoraExtra(
                        $empresaId,
                        $funcionarioId,
                        $dataHoraExtra
                    );


                $salarioBase =
                    (float)(
                        $salario['salario_base']
                        ?? 0
                    );


                $horasMensais =
                    (float)(
                        $salario['horas_mensais']
                        ?? 0
                    );


                $salarioConfigurado =
                    $salarioBase > 0
                    && $horasMensais > 0;
            } catch (RuntimeException $e) {

                /*
                * Na LISTAGEM não bloqueamos a página
                * por falta de configuração salarial.
                *
                * A validação continuará obrigatória
                * no momento de aprovar/calcular
                * financeiramente a hora extra.
                */

                $salario = null;

                $salarioBase = 0.00;

                $horasMensais = 0.00;

                $salarioConfigurado = false;
            }

            /*
            * IMPORTANTE:
            * O cálculo interno utiliza toda a precisão.
            * O arredondamento para 2 casas é apenas
            * para apresentação/gravação.
            */
            $valorHoraExato =
                $horasMensais > 0
                ? $salarioBase / $horasMensais
                : 0.0;


            // ==============================================
            // PERCENTAGEM
            // ==============================================

            $percentual =
                (float)(
                    $linha['percentual_acrescimo']
                    ?? 0
                );

            if ($percentual <= 0) {

                $percentual =
                    $this->percentualHoraExtra(
                        (string)(
                            $linha['tipo_hora_extra']
                            ?? ''
                        )
                    );
            }


            // ==============================================
            // MINUTOS
            // ==============================================

            $minutos =
                (int)(
                    $linha['minutos_aprovados']
                    ?? 0
                );

            if ($minutos <= 0) {

                $minutos =
                    (int)(
                        $linha['minutos_apurados']
                        ?? 0
                    );
            }


            // ==============================================
            // VALOR TOTAL
            // ==============================================

            /*
     * Sempre calculamos com o valor/hora exato.
     *
     * Exemplo:
     * 15000 / 176 = 85.227272...
     *
     * 1h com acréscimo de 50%:
     * 85.227272... × 1.50 = 127.840909...
     *
     * Resultado:
     * 127.84 MZN
     */
            $valorTotal =
                (
                    $minutos / 60
                )
                * $valorHoraExato
                * (
                    1
                    + (
                        $percentual / 100
                    )
                );


            // ==============================================
            // DADOS PARA O FRONT-END
            // ==============================================

            $linha['salario_base'] =
                round(
                    $salarioBase,
                    2
                );

            $linha['horas_mensais'] =
                round(
                    $horasMensais,
                    2
                );

            $linha['moeda'] =
                $salario['moeda']
                ?? 'MZN';

            $linha['valor_hora'] =
                round(
                    $valorHoraExato,
                    2
                );

            $linha['percentual_acrescimo'] =
                round(
                    $percentual,
                    2
                );

            $linha['valor_total_calculado'] =
                round(
                    $valorTotal,
                    2
                );
        }

        unset($linha);

        return $linhas;
    }

    public function registarFalta(
        int $empresaId,
        int $usuarioId,
        int $funcionarioId,
        string $data
    ): int {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $data =
            $this->validarData(
                $data,
                'data da falta'
            );


        $this->validarDisponibilidadeDia(
            $empresaId,
            $funcionarioId,
            $data,
            'FALTA'
        );


        $funcionario =
            $this->buscarFuncionario(
                $empresaId,
                $funcionarioId
            );


        $horario =
            $this->buscarHorarioFuncionario(
                $empresaId,
                $funcionario
            );

        $this->validarDiaParaRegisto(
            $empresaId,
            $funcionario,
            $horario,
            $data
        );

        $minutosPrevistos =
            $this->calcularMinutosPrevistos(
                $horario
            );


        /*
     * Uma falta integral deveria ter uma
     * jornada configurada.
     */
        if ($minutosPrevistos <= 0) {

            throw new RuntimeException(
                'O funcionário não possui horário de trabalho '
                    . 'configurado para calcular a falta.'
            );
        }


        $statusId =
            $this->statusId(
                'assiduidade_diaria',
                [
                    'apurada'
                ]
            );


        $diaSemana =
            (int)(
                new DateTimeImmutable(
                    $data
                )
            )->format('N');


        $stmt =
            $this->pdo->prepare("
            INSERT INTO assiduidade_diaria
            (
                uuid,
                empresa_id,
                funcionario_id,
                filial_id,
                horario_trabalho_id,
                data_assiduidade,
                dia_semana,

                hora_prevista_entrada,
                hora_prevista_saida,

                hora_real_entrada,
                hora_real_saida,

                inicio_intervalo,
                fim_intervalo,

                minutos_previstos,
                minutos_trabalhados,
                minutos_intervalo,

                minutos_atraso,
                minutos_saida_antecipada,
                minutos_extra,
                minutos_falta,

                presente,
                falta_dia_inteiro,
                dia_util,
                trabalho_nocturno,

                ocorrencia,
                status_id,

                criado_por,
                atualizado_por
            )
            VALUES
            (
                UUID(),
                :empresa_id,
                :funcionario_id,
                :filial_id,
                :horario_trabalho_id,
                :data_assiduidade,
                :dia_semana,

                :hora_prevista_entrada,
                :hora_prevista_saida,

                NULL,
                NULL,

                NULL,
                NULL,

                :minutos_previstos,
                0,
                0,

                0,
                0,
                0,
                :minutos_falta,

                0,
                1,
                1,
                0,

                'FALTA_TOTAL',
                :status_id,

                :criado_por,
                :atualizado_por
            )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':filial_id' =>
            !empty($funcionario['filial_id'])
                ? (int)$funcionario['filial_id']
                : null,

            ':horario_trabalho_id' =>
            !empty($funcionario['horario_trabalho_id'])
                ? (int)$funcionario['horario_trabalho_id']
                : null,

            ':data_assiduidade' =>
            $data,

            ':dia_semana' =>
            $diaSemana,

            ':hora_prevista_entrada' =>
            $horario['hora_entrada']
                ?? null,

            ':hora_prevista_saida' =>
            $horario['hora_saida']
                ?? null,

            ':minutos_previstos' =>
            $minutosPrevistos,

            ':minutos_falta' =>
            $minutosPrevistos,

            ':status_id' =>
            $statusId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);


        $assiduidadeId =
            $this->buscarAssiduidadeId(
                $empresaId,
                $funcionarioId,
                $data
            );


        /*
     * Esta função já cria a falta com:
     *
     * remunerada = 0
     * desconta_salario = 1
     */
        $this->sincronizarFaltaAutomatica(
            $empresaId,
            $funcionarioId,
            $assiduidadeId,
            $data,
            true,
            $minutosPrevistos,
            $minutosPrevistos,
            $usuarioId
        );


        return $assiduidadeId;
    }

    private function validarDisponibilidadeDia(
        int $empresaId,
        int $funcionarioId,
        string $data,
        string $operacao
    ): void {

        $operacao =
            strtoupper(
                trim(
                    $operacao
                )
            );


        // ==================================================
        // VERIFICAR REGISTOS DE PONTO
        // ==================================================

        $stmt =
            $this->pdo->prepare("
            SELECT
                COUNT(*)

            FROM registos_ponto registo

            INNER JOIN status
                ON status.id =
                    registo.status_id

               AND status.tipo =
                    'registo_ponto'

            WHERE registo.empresa_id =
                :empresa_id

              AND registo.funcionario_id =
                :funcionario_id

              AND registo.data_registo =
                :data

              AND LOWER(status.nome)
                  NOT IN
                  (
                      'anulado',
                      'anulada'
                  )
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data' =>
            $data
        ]);


        $temPonto =
            (int)$stmt->fetchColumn() > 0;


        // ==================================================
        // VERIFICAR FALTA
        // ==================================================

        $stmt =
            $this->pdo->prepare("
            SELECT
                falta.id,
                falta.tipo_falta,
                falta.origem

            FROM faltas falta

            INNER JOIN status
                ON status.id =
                    falta.status_id

               AND status.tipo =
                    'falta'

            WHERE falta.empresa_id =
                :empresa_id

              AND falta.funcionario_id =
                :funcionario_id

              AND :data BETWEEN
                    falta.data_inicio
                    AND falta.data_fim

              AND LOWER(status.nome)
                  NOT IN
                  (
                      'anulada',
                      'anulado',
                      'cancelada',
                      'cancelado'
                  )

            ORDER BY
                falta.id DESC

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data' =>
            $data
        ]);


        $falta =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        $temFalta =
            $falta !== false;


        $temFaltaDiaInteiro =
            $temFalta
            && strtoupper(
                (string)(
                    $falta['tipo_falta']
                    ?? ''
                )
            ) === 'DIA_INTEIRO';


        // ==================================================
        // QUER MARCAR FALTA
        // ==================================================

        if (
            $operacao === 'FALTA'
            && $temPonto
        ) {

            throw new RuntimeException(
                'Não é possível marcar falta em '
                    . $data
                    . '. O funcionário já possui registos de ponto nesta data.'
            );
        }


        if (
            $operacao === 'FALTA'
            && $temFalta
        ) {

            throw new RuntimeException(
                'Já existe uma falta registada para este funcionário em '
                    . $data
                    . '.'
            );
        }


        // ==================================================
        // QUER MARCAR PRESENÇA
        // ==================================================

        /*
     * IMPORTANTE:
     *
     * Uma falta PARCIAL automática pode surgir durante
     * a construção dos registos:
     *
     * ENTRADA
     * -> apurarDia()
     * -> falta parcial temporária
     *
     * Portanto ela NÃO pode impedir almoço ou saída.
     *
     * Apenas uma falta de DIA_INTEIRO bloqueia presença.
     */

        if (
            $operacao === 'PRESENCA'
            && $temFaltaDiaInteiro
        ) {

            throw new RuntimeException(
                'Não é possível marcar presença em '
                    . $data
                    . '. O funcionário já possui uma falta de dia inteiro nesta data.'
            );
        }
    }


    // ==================================================
    // HORAS EXTRAS — APROVAR
    // ==================================================

    public function aprovarHoraExtra(
        int $empresaId,
        int $usuarioId,
        int $horaExtraId,
        array $dados
    ): void {

        $this->validarUsuario(
            $usuarioId
        );

        $minutosAprovados =
            $this->inteiroNaoNegativo(
                $dados['minutos_aprovados']
                    ?? 0,
                'minutos aprovados'
            );

        // ==================================================
        // BUSCAR HORA EXTRA
        // ==================================================

        $stmtHoraExtra =
            $this->pdo->prepare("
        SELECT
            id,
            funcionario_id,
            data_hora_extra,
            tipo_hora_extra,
            minutos_apurados

        FROM horas_extras

        WHERE id =
            :hora_extra_id

          AND empresa_id =
            :empresa_id

        LIMIT 1
    ");


        $stmtHoraExtra->execute([
            ':hora_extra_id' =>
            $horaExtraId,

            ':empresa_id' =>
            $empresaId
        ]);


        $horaExtra =
            $stmtHoraExtra->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$horaExtra) {

            throw new RuntimeException(
                'Registo de horas extras não encontrado.'
            );
        }


        // ==================================================
        // VALIDAR MINUTOS APROVADOS
        // ==================================================

        $minutosApurados =
            (int)(
                $horaExtra['minutos_apurados']
                ?? 0
            );


        if ($minutosAprovados <= 0) {

            throw new RuntimeException(
                'Informe os minutos aprovados.'
            );
        }


        if (
            $minutosAprovados
            > $minutosApurados
        ) {

            throw new RuntimeException(
                'Os minutos aprovados não podem exceder '
                    . 'os minutos apurados.'
            );
        }


        // ==================================================
        // BUSCAR SALÁRIO VIGENTE
        // ==================================================

        $salario =
            $this->buscarSalarioVigenteHoraExtra(
                $empresaId,
                (int)$horaExtra['funcionario_id'],
                (string)$horaExtra['data_hora_extra']
            );


        $salarioBase =
            (float)$salario['salario_base'];


        $horasMensais =
            (float)$salario['horas_mensais'];


        if ($horasMensais <= 0) {

            throw new RuntimeException(
                'As horas mensais do funcionário '
                    . 'não estão configuradas.'
            );
        }


        // ==================================================
        // VALOR HORA
        // ==================================================

        // Valor/hora exato para cálculos internos.
        // Não arredondar aqui para evitar diferenças no valor total.
        $valorHoraExato =
            $salarioBase
            / $horasMensais;

        // Valor/hora apresentado/gravadado com 2 casas decimais.
        $valorHora =
            round(
                $valorHoraExato,
                2
            );

        // ==================================================
        // PERCENTAGEM
        // ==================================================

        $percentual =
            $this->percentualHoraExtra(
                (string)$horaExtra['tipo_hora_extra']
            );


        // ==================================================
        // VALOR TOTAL
        // ==================================================

        $valorTotal =
            (
                $minutosAprovados
                / 60
            )
            * $valorHoraExato
            * (
                1
                + (
                    $percentual
                    / 100
                )
            );

        $valorTotal =
            round(
                $valorTotal,
                2
            );

        $statusId =
            $this->statusId(
                'hora_extra',
                [
                    'aprovada'
                ]
            );

        $stmt = $this->pdo->prepare("
            UPDATE horas_extras

            SET
                minutos_aprovados =
                    :minutos_aprovados,

                percentual_acrescimo =
                    :percentual_acrescimo,

                valor_hora =
                    :valor_hora,

                valor_total =
                    :valor_total,

                status_id =
                    :status_id,

                aprovado_por =
                    :aprovado_por,

                aprovado_em =
                    NOW(),

                observacoes =
                    :observacoes,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :hora_extra_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':minutos_aprovados' =>
            $minutosAprovados,

            ':percentual_acrescimo' =>
            $percentual,

            ':valor_hora' =>
            $valorHora,

            ':valor_total' =>
            number_format(
                $valorTotal,
                2,
                '.',
                ''
            ),

            ':status_id' =>
            $statusId,

            ':aprovado_por' =>
            $usuarioId,

            ':observacoes' =>
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            ),

            ':atualizado_por' =>
            $usuarioId,

            ':hora_extra_id' =>
            $horaExtraId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Registo de horas extras não encontrado.'
            );
        }
    }

    // ==================================================
    // HORAS EXTRAS — REJEITAR
    // ==================================================

    public function rejeitarHoraExtra(
        int $empresaId,
        int $usuarioId,
        int $horaExtraId,
        ?string $observacoes = null
    ): void {

        $statusId =
            $this->statusId(
                'hora_extra',
                [
                    'rejeitada'
                ]
            );

        $stmt = $this->pdo->prepare("
            UPDATE horas_extras

            SET
                minutos_aprovados =
                    0,

                valor_total =
                    0,

                status_id =
                    :status_id,

                aprovado_por =
                    :aprovado_por,

                aprovado_em =
                    NOW(),

                observacoes =
                    :observacoes,

                atualizado_por =
                    :atualizado_por

            WHERE id =
                :hora_extra_id

              AND empresa_id =
                :empresa_id
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId,

            ':aprovado_por' =>
            $usuarioId,

            ':observacoes' =>
            $this->textoOpcional(
                $observacoes
            ),

            ':atualizado_por' =>
            $usuarioId,

            ':hora_extra_id' =>
            $horaExtraId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Registo de horas extras não encontrado.'
            );
        }
    }

    // ==================================================
    // NORMALIZAR REGISTO DE PONTO
    // ==================================================

    private function normalizarRegistoPonto(
        int $empresaId,
        array $dados
    ): array {

        $funcionarioId =
            (int)(
                $dados['funcionario_id']
                ?? 0
            );

        if ($funcionarioId <= 0) {
            throw new RuntimeException(
                'Selecione o funcionário.'
            );
        }

        $funcionario =
            $this->buscarFuncionario(
                $empresaId,
                $funcionarioId
            );

        $dataRegisto =
            $this->dataObrigatoria(
                $dados['data_registo']
                    ?? null,
                'data do registo'
            );

        $horaRegisto =
            $this->horaObrigatoria(
                $dados['hora_registo']
                    ?? null,
                'hora do registo'
            );

        $tipoRegisto =
            $this->enumObrigatorio(
                $dados['tipo_registo']
                    ?? null,
                [
                    'ENTRADA',
                    'INICIO_INTERVALO',
                    'FIM_INTERVALO',
                    'SAIDA'
                ],
                'tipo de registo'
            );

        $origem =
            $this->enumObrigatorio(
                $dados['origem']
                    ?? 'MANUAL',
                [
                    'MANUAL',
                    'BIOMETRICO',
                    'CARTAO',
                    'APLICACAO',
                    'IMPORTACAO',
                    'OUTRO'
                ],
                'origem do registo'
            );

        $statusId =
            (int)(
                $dados['status_id']
                ?? 0
            );

        if ($statusId <= 0) {
            $statusId =
                $this->statusId(
                    'registo_ponto',
                    [
                        'pendente'
                    ]
                );
        }

        $this->validarStatus(
            $statusId,
            'registo_ponto'
        );

        $filialId =
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? $funcionario['filial_id']
                    ?? null
            );

        if ($filialId !== null) {
            $this->validarRegistroEmpresa(
                'filiais',
                $filialId,
                $empresaId,
                'Filial inválida.'
            );
        }

        $registoOriginalId =
            $this->inteiroOpcional(
                $dados['registo_original_id']
                    ?? null
            );

        if ($registoOriginalId !== null) {
            $this->buscarRegistoPonto(
                $empresaId,
                $registoOriginalId
            );
        }

        return [
            'funcionario_id' =>
            $funcionarioId,

            'filial_id' =>
            $filialId,

            'data_registo' =>
            $dataRegisto,

            'hora_registo' =>
            $horaRegisto,

            'data_hora_registo' =>
            $dataRegisto
                . ' '
                . $horaRegisto,

            'tipo_registo' =>
            $tipoRegisto,

            'origem' =>
            $origem,

            'dispositivo' =>
            $this->textoOpcional(
                $dados['dispositivo']
                    ?? null
            ),

            'identificador_dispositivo' =>
            $this->textoOpcional(
                $dados['identificador_dispositivo']
                    ?? null
            ),

            'latitude' =>
            $this->decimalOpcional(
                $dados['latitude']
                    ?? null,
                -90,
                90,
                'latitude'
            ),

            'longitude' =>
            $this->decimalOpcional(
                $dados['longitude']
                    ?? null,
                -180,
                180,
                'longitude'
            ),

            'endereco_localizacao' =>
            $this->textoOpcional(
                $dados['endereco_localizacao']
                    ?? null
            ),

            'ip' =>
            $this->textoOpcional(
                $dados['ip']
                    ?? $_SERVER['REMOTE_ADDR']
                    ?? null
            ),

            'observacoes' =>
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            ),

            'justificacao' =>
            $this->textoOpcional(
                $dados['justificacao']
                    ?? null
            ),

            'registo_original_id' =>
            $registoOriginalId,

            'corrigido' =>
            $this->booleano(
                $dados['corrigido']
                    ?? (
                        $registoOriginalId
                        !== null
                    )
            ),

            'status_id' =>
            $statusId
        ];
    }

    // ==================================================
    // SINCRONIZAR FALTA AUTOMÁTICA
    // ==================================================

    private function sincronizarFaltaAutomatica(
        int $empresaId,
        int $funcionarioId,
        int $assiduidadeId,
        string $data,
        bool $faltaDiaInteiro,
        int $minutosFalta,
        int $minutosPrevistos,
        int $usuarioId
    ): void {


        // ==================================================
        // LOCALIZAR FALTA AUTOMÁTICA EXISTENTE
        // ==================================================

        $stmt =
            $this->pdo->prepare("
            SELECT id

            FROM faltas

            WHERE empresa_id =
                :empresa_id

              AND funcionario_id =
                :funcionario_id

              AND assiduidade_diaria_id =
                :assiduidade_id

              AND origem =
                'AUTOMATICA'

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':assiduidade_id' =>
            $assiduidadeId
        ]);


        $faltaId =
            (int)$stmt->fetchColumn();


        // ==================================================
        // NÃO EXISTE MAIS FALTA
        // ==================================================

        if (
            !$faltaDiaInteiro
            && $minutosFalta <= 0
        ) {

            /*
         * Se existia uma falta criada automaticamente
         * por um apuramento anterior, ela deixa de ser
         * válida e deve ser removida.
         */

            if ($faltaId > 0) {

                $stmtDelete =
                    $this->pdo->prepare("
                    DELETE FROM faltas

                    WHERE id =
                        :falta_id

                      AND empresa_id =
                        :empresa_id

                      AND origem =
                        'AUTOMATICA'
                ");


                $stmtDelete->execute([
                    ':falta_id' =>
                    $faltaId,

                    ':empresa_id' =>
                    $empresaId
                ]);
            }


            return;
        }


        // ==================================================
        // STATUS
        // ==================================================

        $statusId =
            $this->statusId(
                'falta',
                [
                    'pendente'
                ]
            );


        // ==================================================
        // TIPO DE FALTA
        // ==================================================

        $tipoFalta =
            $faltaDiaInteiro
            ? 'DIA_INTEIRO'
            : 'PARCIAL';


        // ==================================================
        // QUANTIDADE PROPORCIONAL DE DIAS
        // ==================================================

        if ($faltaDiaInteiro) {

            $quantidadeDias =
                '1.0000';
        } else {

            $quantidade =
                $minutosPrevistos > 0
                ? $minutosFalta
                / $minutosPrevistos
                : 0;


            $quantidadeDias =
                number_format(
                    $quantidade,
                    4,
                    '.',
                    ''
                );
        }


        // ==================================================
        // ATUALIZAR FALTA EXISTENTE
        // ==================================================

        if ($faltaId > 0) {

            $stmtUpdate =
                $this->pdo->prepare("
                UPDATE faltas

                SET
                    data_inicio =
                        :data_inicio,

                    data_fim =
                        :data_fim,

                    tipo_falta =
                        :tipo_falta,

                    quantidade_dias =
                        :quantidade_dias,

                    minutos_falta =
                        :minutos_falta,

                    status_id =
                        :status_id,

                    atualizado_por =
                        :atualizado_por

                WHERE id =
                    :falta_id

                  AND empresa_id =
                    :empresa_id
            ");


            $stmtUpdate->execute([
                ':data_inicio' =>
                $data,

                ':data_fim' =>
                $data,

                ':tipo_falta' =>
                $tipoFalta,

                ':quantidade_dias' =>
                $quantidadeDias,

                ':minutos_falta' =>
                $minutosFalta,

                ':status_id' =>
                $statusId,

                ':atualizado_por' =>
                $usuarioId,

                ':falta_id' =>
                $faltaId,

                ':empresa_id' =>
                $empresaId
            ]);


            return;
        }


        // ==================================================
        // CRIAR NOVA FALTA AUTOMÁTICA
        // ==================================================

        $stmtInsert =
            $this->pdo->prepare("
            INSERT INTO faltas
            (
                uuid,
                empresa_id,
                funcionario_id,
                assiduidade_diaria_id,
                data_inicio,
                data_fim,
                tipo_falta,
                origem,
                quantidade_dias,
                minutos_falta,
                remunerada,
                desconta_salario,
                desconta_ferias,
                status_id,
                criado_por,
                atualizado_por
            )
            VALUES
            (
                UUID(),
                :empresa_id,
                :funcionario_id,
                :assiduidade_id,
                :data_inicio,
                :data_fim,
                :tipo_falta,
                'AUTOMATICA',
                :quantidade_dias,
                :minutos_falta,
                0,
                1,
                0,
                :status_id,
                :criado_por,
                :atualizado_por
            )
        ");


        $stmtInsert->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':assiduidade_id' =>
            $assiduidadeId,

            ':data_inicio' =>
            $data,

            ':data_fim' =>
            $data,

            ':tipo_falta' =>
            $tipoFalta,

            ':quantidade_dias' =>
            $quantidadeDias,

            ':minutos_falta' =>
            $minutosFalta,

            ':status_id' =>
            $statusId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);
    }

    // ==================================================
    // SINCRONIZAR HORA EXTRA AUTOMÁTICA
    // ==================================================

    private function sincronizarHoraExtraAutomatica(
        int $empresaId,
        int $funcionarioId,
        int $assiduidadeId,
        string $data,
        int $minutosExtra,
        int $usuarioId
    ): void {

        if ($minutosExtra <= 0) {
            return;
        }

        $statusId =
            $this->statusId(
                'hora_extra',
                [
                    'pendente'
                ]
            );

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM horas_extras

            WHERE empresa_id =
                :empresa_id

              AND funcionario_id =
                :funcionario_id

              AND assiduidade_diaria_id =
                :assiduidade_id

              AND origem =
                'AUTOMATICA'

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':assiduidade_id' =>
            $assiduidadeId
        ]);

        $horaExtraId =
            (int)$stmt->fetchColumn();

        if ($horaExtraId > 0) {
            $stmtUpdate =
                $this->pdo->prepare("
                    UPDATE horas_extras

                    SET
                        data_hora_extra =
                            :data_hora_extra,

                        minutos_apurados =
                            :minutos_apurados,

                        minutos_solicitados =
                            :minutos_solicitados,

                        status_id =
                            :status_id,

                        atualizado_por =
                            :atualizado_por

                    WHERE id =
                        :hora_extra_id
                ");

            $stmtUpdate->execute([
                ':data_hora_extra' =>
                $data,

                ':minutos_apurados' =>
                $minutosExtra,

                ':minutos_solicitados' =>
                $minutosExtra,

                ':status_id' =>
                $statusId,

                ':atualizado_por' =>
                $usuarioId,

                ':hora_extra_id' =>
                $horaExtraId
            ]);

            return;
        }

        $stmtInsert =
            $this->pdo->prepare("
                INSERT INTO horas_extras
                (
                    uuid,
                    empresa_id,
                    funcionario_id,
                    assiduidade_diaria_id,
                    data_hora_extra,
                    minutos_solicitados,
                    minutos_apurados,
                    minutos_aprovados,
                    tipo_hora_extra,
                    origem,
                    percentual_acrescimo,
                    valor_hora,
                    valor_total,
                    status_id,
                    criado_por,
                    atualizado_por
                )
                VALUES
                (
                    UUID(),
                    :empresa_id,
                    :funcionario_id,
                    :assiduidade_id,
                    :data_hora_extra,
                    :minutos_solicitados,
                    :minutos_apurados,
                    0,
                    'DIA_NORMAL',
                    'AUTOMATICA',
                    0,
                    0,
                    0,
                    :status_id,
                    :criado_por,
                    :atualizado_por
                )
            ");

        $stmtInsert->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':assiduidade_id' =>
            $assiduidadeId,

            ':data_hora_extra' =>
            $data,

            ':minutos_solicitados' =>
            $minutosExtra,

            ':minutos_apurados' =>
            $minutosExtra,

            ':status_id' =>
            $statusId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);
    }

    private function validarHorarioFuncionario(
        array $funcionario
    ): void {

        $horarioId =
            (int)(
                $funcionario['horario_trabalho_id']
                ?? 0
            );

        if ($horarioId <= 0) {
            throw new RuntimeException(
                'O funcionário não possui horário de trabalho configurado. '
                    . 'Configure o horário antes de marcar assiduidade.'
            );
        }
    }

    // ==================================================
    // REGISTOS VÁLIDOS DO DIA
    // ==================================================

    private function registosValidosDoDia(
        int $empresaId,
        int $funcionarioId,
        string $data
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                registo.id,
                registo.tipo_registo,
                registo.hora_registo,
                registo.data_hora_registo

            FROM registos_ponto registo

            INNER JOIN status
                ON status.id =
                    registo.status_id

               AND status.tipo =
                    'registo_ponto'

            WHERE registo.empresa_id =
                :empresa_id

              AND registo.funcionario_id =
                :funcionario_id

              AND registo.data_registo =
                :data_registo

              AND LOWER(status.nome) NOT IN
              (
                  'anulado',
                  'anulada'
              )

            ORDER BY
                registo.data_hora_registo ASC,
                registo.id ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data_registo' =>
            $data
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
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
                funcionario.id,
                funcionario.filial_id,
                funcionario.departamento_id,
                funcionario.cargo_id,
                funcionario.horario_trabalho_id,
                funcionario.salario_base,
                funcionario.moeda

            FROM funcionarios funcionario

            WHERE funcionario.id =
                :funcionario_id

              AND funcionario.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':funcionario_id' =>
            $funcionarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        $funcionario =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$funcionario) {
            throw new RuntimeException(
                'Funcionário inválido.'
            );
        }

        return $funcionario;
    }

    private function buscarHorarioFuncionario(
        int $empresaId,
        array $funcionario
    ): array {

        $horarioId =
            (int)(
                $funcionario['horario_trabalho_id']
                ?? 0
            );

        if ($horarioId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("
        SELECT
            id,
            nome,
            hora_entrada,
            hora_saida,
            intervalo_inicio,
            intervalo_fim,
            carga_horaria_diaria,
            carga_horaria_semanal,
            segunda_feira,
            terca_feira,
            quarta_feira,
            quinta_feira,
            sexta_feira,
            sabado,
            domingo,
            permite_horas_extras,
            permite_trabalho_noturno
        FROM horarios_trabalho
        WHERE id = :horario_id
          AND empresa_id = :empresa_id
        LIMIT 1
    ");

        $stmt->execute([
            ':horario_id' =>
            $horarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: [];
    }

    private function verificarDiaLaboral(
        int $empresaId,
        array $funcionario,
        array $horario,
        string $data
    ): array {

        $data =
            $this->validarData(
                $data,
                'data da assiduidade'
            );

        if (empty($horario)) {
            throw new RuntimeException(
                'O funcionário não possui horário de trabalho configurado.'
            );
        }

        $diaNumero =
            (int)(
                new DateTimeImmutable(
                    $data
                )
            )->format('N');

        $colunasDias = [
            1 => 'segunda_feira',
            2 => 'terca_feira',
            3 => 'quarta_feira',
            4 => 'quinta_feira',
            5 => 'sexta_feira',
            6 => 'sabado',
            7 => 'domingo'
        ];

        $nomesDias = [
            1 => 'segunda-feira',
            2 => 'terça-feira',
            3 => 'quarta-feira',
            4 => 'quinta-feira',
            5 => 'sexta-feira',
            6 => 'sábado',
            7 => 'domingo'
        ];

        $colunaDia =
            $colunasDias[$diaNumero];

        $trabalhaPeloHorario =
            !empty($horario[$colunaDia]);

        $filialId =
            !empty($funcionario['filial_id'])
            ? (int)$funcionario['filial_id']
            : null;

        $stmt =
            $this->pdo->prepare("
            SELECT
                calendario.id,
                calendario.titulo,
                calendario.tipo_evento,
                calendario.dia_inteiro,
                calendario.dia_util,
                calendario.permite_trabalho,
                calendario.recorrente_anual,
                calendario.filial_id
            FROM calendario_laboral calendario
            INNER JOIN status estado
                ON estado.id =
                    calendario.status_id
            WHERE calendario.empresa_id =
                    :empresa_id

              AND (
                    calendario.filial_id IS NULL
                    OR calendario.filial_id =
                        :filial_id
              )

              AND calendario.aplica_todos_funcionarios = 1

              AND LOWER(estado.nome) IN (
                    'ativo',
                    'activa',
                    'activo'
              )

              AND (
                    (
                        calendario.recorrente_anual = 0
                        AND :data_normal
                            BETWEEN calendario.data_inicio
                            AND calendario.data_fim
                    )

                    OR

                    (
                        calendario.recorrente_anual = 1
                        AND DATE_FORMAT(
                                :data_recorrente,
                                '%m-%d'
                            )
                            BETWEEN DATE_FORMAT(
                                calendario.data_inicio,
                                '%m-%d'
                            )
                            AND DATE_FORMAT(
                                calendario.data_fim,
                                '%m-%d'
                            )
                    )
              )

            ORDER BY
                CASE
                    WHEN calendario.filial_id IS NOT NULL
                    THEN 0
                    ELSE 1
                END,
                calendario.id DESC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            $filialId,

            ':data_normal' =>
            $data,

            ':data_recorrente' =>
            $data
        ]);

        $eventos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $feriado = false;
        $bloqueadoCalendario = false;
        $autorizadoCalendario = false;
        $motivo = null;

        foreach ($eventos as $evento) {

            $tipo =
                strtoupper(
                    trim(
                        (string)$evento['tipo_evento']
                    )
                );

            if (
                in_array(
                    $tipo,
                    [
                        'FERIADO_NACIONAL',
                        'FERIADO_LOCAL'
                    ],
                    true
                )
            ) {
                $feriado = true;
            }

            /*
         * Uma autorização explícita no calendário
         * permite trabalhar mesmo num fim de semana
         * ou feriado.
         */
            if (
                (int)$evento['permite_trabalho'] === 1
                || $tipo === 'DIA_UTIL_ESPECIAL'
            ) {
                $autorizadoCalendario = true;

                continue;
            }

            /*
         * Apenas eventos de dia inteiro bloqueiam
         * o registo de toda a jornada.
         */
            if (
                (int)$evento['dia_inteiro'] === 1
                && in_array(
                    $tipo,
                    [
                        'FERIADO_NACIONAL',
                        'FERIADO_LOCAL',
                        'DIA_NAO_UTIL',
                        'SUSPENSAO_ATIVIDADE'
                    ],
                    true
                )
            ) {
                $bloqueadoCalendario = true;

                $motivo =
                    trim(
                        (string)(
                            $evento['titulo']
                            ?? ''
                        )
                    );
            }
        }

        $permitido =
            $autorizadoCalendario
            || (
                $trabalhaPeloHorario
                && !$bloqueadoCalendario
            );

        $folga =
            !$trabalhaPeloHorario
            && !$autorizadoCalendario;

        return [
            'permitido' =>
            $permitido,

            'dia_util' =>
            $permitido,

            'feriado' =>
            $feriado,

            'folga' =>
            $folga,

            'trabalho_feriado' =>
            $permitido
                && $feriado,

            'dia_semana' =>
            $diaNumero,

            'nome_dia' =>
            $nomesDias[$diaNumero],

            'motivo' =>
            $motivo
        ];
    }

    private function validarDiaParaRegisto(
        int $empresaId,
        array $funcionario,
        array $horario,
        string $data
    ): array {

        $situacao =
            $this->verificarDiaLaboral(
                $empresaId,
                $funcionario,
                $horario,
                $data
            );

        if (!$situacao['permitido']) {

            if ($situacao['feriado']) {

                $mensagem =
                    'Não é permitido marcar assiduidade nesta data porque é feriado';

                if (!empty($situacao['motivo'])) {
                    $mensagem .=
                        ': '
                        . $situacao['motivo'];
                }

                throw new RuntimeException(
                    $mensagem . '.'
                );
            }

            if ($situacao['folga']) {

                $preposicao =
                    in_array(
                        $situacao['nome_dia'],
                        [
                            'sábado',
                            'domingo'
                        ],
                        true
                    )
                    ? 'no '
                    : 'na ';

                throw new RuntimeException(
                    'Não é permitido marcar assiduidade '
                        . $preposicao
                        . $situacao['nome_dia']
                        . ', pois o funcionário não trabalha neste dia.'
                );
            }

            throw new RuntimeException(
                'Esta data está definida como dia não útil no calendário laboral.'
            );
        }

        return $situacao;
    }

    private function buscarAssiduidadeId(
        int $empresaId,
        int $funcionarioId,
        string $data
    ): int {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM assiduidade_diaria

            WHERE empresa_id =
                :empresa_id

              AND funcionario_id =
                :funcionario_id

              AND data_assiduidade =
                :data_assiduidade

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data_assiduidade' =>
            $data
        ]);

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                'Não foi possível apurar a assiduidade diária.'
            );
        }

        return $id;
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
                funcionario.horario_trabalho_id

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

            ORDER BY
                funcionario.nome_completo ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
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
                nome

            FROM filiais

            WHERE empresa_id =
                :empresa_id

            ORDER BY
                nome ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarStatus(
        string $tipo
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo =
                :tipo

            ORDER BY
                id ASC
        ");

        $stmt->execute([
            ':tipo' =>
            $tipo
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    private function listarTiposRegisto(): array
    {
        return [
            [
                'codigo' => 'ENTRADA',
                'nome' => 'Entrada'
            ],
            [
                'codigo' => 'INICIO_INTERVALO',
                'nome' => 'Início do Intervalo'
            ],
            [
                'codigo' => 'FIM_INTERVALO',
                'nome' => 'Fim do Intervalo'
            ],
            [
                'codigo' => 'SAIDA',
                'nome' => 'Saída'
            ]
        ];
    }

    private function listarOrigensRegisto(): array
    {
        return [
            [
                'codigo' => 'MANUAL',
                'nome' => 'Manual'
            ],
            [
                'codigo' => 'BIOMETRICO',
                'nome' => 'Biométrico'
            ],
            [
                'codigo' => 'CARTAO',
                'nome' => 'Cartão'
            ],
            [
                'codigo' => 'APLICACAO',
                'nome' => 'Aplicação'
            ],
            [
                'codigo' => 'IMPORTACAO',
                'nome' => 'Importação'
            ],
            [
                'codigo' => 'OUTRO',
                'nome' => 'Outro'
            ]
        ];
    }

    private function listarOcorrencias(): array
    {
        return [
            ['codigo' => 'NORMAL', 'nome' => 'Normal'],
            ['codigo' => 'ATRASO', 'nome' => 'Atraso'],
            [
                'codigo' => 'SAIDA_ANTECIPADA',
                'nome' => 'Saída Antecipada'
            ],
            [
                'codigo' => 'FALTA_PARCIAL',
                'nome' => 'Falta Parcial'
            ],
            [
                'codigo' => 'FALTA_TOTAL',
                'nome' => 'Falta Total'
            ],
            ['codigo' => 'FERIAS', 'nome' => 'Férias'],
            ['codigo' => 'LICENCA', 'nome' => 'Licença'],
            ['codigo' => 'FERIADO', 'nome' => 'Feriado'],
            ['codigo' => 'FOLGA', 'nome' => 'Folga'],
            [
                'codigo' => 'INCOMPLETO',
                'nome' => 'Registo Incompleto'
            ]
        ];
    }

    private function listarTiposFalta(): array
    {
        return [
            [
                'codigo' => 'DIA_INTEIRO',
                'nome' => 'Dia Inteiro'
            ],
            [
                'codigo' => 'PARCIAL',
                'nome' => 'Parcial'
            ],
            [
                'codigo' => 'ATRASO',
                'nome' => 'Atraso'
            ],
            [
                'codigo' => 'SAIDA_ANTECIPADA',
                'nome' => 'Saída Antecipada'
            ]
        ];
    }

    private function listarTiposHoraExtra(): array
    {
        return [
            [
                'codigo' => 'DIA_NORMAL',
                'nome' => 'Dia Normal'
            ],
            [
                'codigo' => 'NOCTURNA',
                'nome' => 'Nocturna'
            ],
            [
                'codigo' => 'FERIADO',
                'nome' => 'Feriado'
            ],
            [
                'codigo' => 'FOLGA',
                'nome' => 'Folga'
            ],
            [
                'codigo' => 'FIM_SEMANA',
                'nome' => 'Fim de Semana'
            ]
        ];
    }

    // ==================================================
    // FILTROS
    // ==================================================

    private function normalizarFiltrosComuns(
        array $filtros
    ): array {

        return [
            'id' =>
            $this->inteiroOpcional(
                $filtros['id']
                    ?? null
            ),

            'funcionario_id' =>
            $this->inteiroOpcional(
                $filtros['funcionario_id']
                    ?? null
            ),

            'filial_id' =>
            $this->inteiroOpcional(
                $filtros['filial_id']
                    ?? null
            ),

            'status_id' =>
            $this->inteiroOpcional(
                $filtros['status_id']
                    ?? null
            ),

            'data_inicio' =>
            $this->dataOpcional(
                $filtros['data_inicio']
                    ?? null,
                'data inicial'
            ),

            'data_fim' =>
            $this->dataOpcional(
                $filtros['data_fim']
                    ?? null,
                'data final'
            ),

            'pesquisa' =>
            $this->textoOpcional(
                $filtros['pesquisa']
                    ?? null
            ),

            'tipo_registo' =>
            $filtros['tipo_registo']
                ?? null,

            'origem' =>
            $filtros['origem']
                ?? null,

            'ocorrencia' =>
            $filtros['ocorrencia']
                ?? null
        ];
    }

    private function aplicarFiltrosComuns(
        string &$sql,
        array &$params,
        array $filtros,
        string $alias,
        string $campoData
    ): void {

        if (
            !empty($filtros['id'])
        ) {
            $sql .= "
                AND {$alias}.id =
                    :id
            ";

            $params[':id'] =
                $filtros['id'];
        }

        if (
            !empty($filtros['funcionario_id'])
        ) {
            $sql .= "
                AND {$alias}.funcionario_id =
                    :funcionario_id
            ";

            $params[':funcionario_id'] =
                $filtros['funcionario_id'];
        }

        if (
            !empty($filtros['filial_id'])
            && $alias !== 'falta'
            && $alias !== 'hora_extra'
        ) {
            $sql .= "
                AND {$alias}.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        if (
            !empty($filtros['status_id'])
        ) {
            $sql .= "
                AND {$alias}.status_id =
                    :status_id
            ";

            $params[':status_id'] =
                $filtros['status_id'];
        }

        if (
            !empty($filtros['data_inicio'])
        ) {
            $sql .= "
                AND {$alias}.{$campoData} >=
                    :data_inicio
            ";

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        if (
            !empty($filtros['data_fim'])
        ) {
            $sql .= "
                AND {$alias}.{$campoData} <=
                    :data_fim
            ";

            $params[':data_fim'] =
                $filtros['data_fim'];
        }

        if (
            !empty($filtros['pesquisa'])
        ) {
            $sql .= "
                AND
                (
                    funcionario.nome_completo
                        LIKE :pesquisa

                    OR funcionario.numero_funcionario
                        LIKE :pesquisa
                )
            ";

            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }
    }

    // ==================================================
    // VALIDAÇÃO DA SEQUÊNCIA DO PONTO
    // ==================================================

    private function validarSequenciaPonto(
        int $empresaId,
        int $funcionarioId,
        string $data,
        string $tipoRegisto,
        string $dataHora
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT
                tipo_registo,
                data_hora_registo

            FROM registos_ponto registo

            INNER JOIN status
                ON status.id =
                    registo.status_id

               AND status.tipo =
                    'registo_ponto'

            WHERE registo.empresa_id =
                :empresa_id

              AND registo.funcionario_id =
                :funcionario_id

              AND registo.data_registo =
                :data_registo

              AND LOWER(status.nome) NOT IN
              (
                  'anulado',
                  'anulada'
              )

            ORDER BY
                registo.data_hora_registo DESC,
                registo.id DESC

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data_registo' =>
            $data
        ]);

        $ultimo =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$ultimo) {
            if ($tipoRegisto !== 'ENTRADA') {
                throw new RuntimeException(
                    'O primeiro registo do dia deve ser uma entrada.'
                );
            }

            return;
        }

        if (
            $dataHora
            <= $ultimo['data_hora_registo']
        ) {
            throw new RuntimeException(
                'A hora deve ser posterior ao último registo do funcionário.'
            );
        }

        $permitidos = [
            'ENTRADA' => [
                'INICIO_INTERVALO',
                'SAIDA'
            ],

            'INICIO_INTERVALO' => [
                'FIM_INTERVALO'
            ],

            'FIM_INTERVALO' => [
                'INICIO_INTERVALO',
                'SAIDA'
            ],

            'SAIDA' => []
        ];

        $ultimoTipo =
            $ultimo['tipo_registo'];

        if (
            !in_array(
                $tipoRegisto,
                $permitidos[$ultimoTipo]
                    ?? [],
                true
            )
        ) {
            throw new RuntimeException(
                'A sequência do registo de ponto é inválida.'
            );
        }
    }

    // ==================================================
    // CÁLCULOS DE TEMPO
    // ==================================================

    private function primeiraHoraPorTipo(
        array $registos,
        string $tipo
    ): ?string {

        foreach ($registos as $registo) {
            if (
                $registo['tipo_registo']
                === $tipo
            ) {
                return substr(
                    (string)$registo['hora_registo'],
                    0,
                    8
                );
            }
        }

        return null;
    }

    private function ultimaHoraPorTipo(
        array $registos,
        string $tipo
    ): ?string {

        $hora = null;

        foreach ($registos as $registo) {
            if (
                $registo['tipo_registo']
                === $tipo
            ) {
                $hora =
                    substr(
                        (string)$registo['hora_registo'],
                        0,
                        8
                    );
            }
        }

        return $hora;
    }

    private function diferencaMinutos(
        ?string $inicio,
        ?string $fim
    ): int {

        if (
            $inicio === null
            || $fim === null
        ) {
            return 0;
        }

        $inicioObj =
            DateTimeImmutable::createFromFormat(
                '!H:i:s',
                $inicio
            );

        $fimObj =
            DateTimeImmutable::createFromFormat(
                '!H:i:s',
                $fim
            );

        if (
            $inicioObj === false
            || $fimObj === false
        ) {
            return 0;
        }

        $segundos =
            $fimObj->getTimestamp()
            - $inicioObj->getTimestamp();

        if ($segundos < 0) {
            $segundos += 86400;
        }

        return (int)floor(
            $segundos / 60
        );
    }

    private function calcularMinutosPrevistos(
        array $horario
    ): int {

        if (empty($horario)) {
            return 0;
        }

        $cargaDiaria =
            (float)(
                $horario['carga_horaria_diaria']
                ?? 0
            );

        if ($cargaDiaria > 0) {
            return (int)round(
                $cargaDiaria * 60
            );
        }

        if (
            empty($horario['hora_entrada'])
            || empty($horario['hora_saida'])
        ) {
            return 0;
        }

        $minutos =
            $this->diferencaMinutos(
                $horario['hora_entrada'],
                $horario['hora_saida']
            );

        if (
            !empty($horario['intervalo_inicio'])
            && !empty($horario['intervalo_fim'])
        ) {
            $minutos -=
                $this->diferencaMinutos(
                    $horario['intervalo_inicio'],
                    $horario['intervalo_fim']
                );
        }

        return max(
            0,
            $minutos
        );
    }

    private function calcularAtraso(
        ?string $prevista,
        ?string $real
    ): int {

        if (
            $prevista === null
            || $real === null
            || $real <= $prevista
        ) {
            return 0;
        }

        return $this->diferencaMinutos(
            $prevista,
            $real
        );
    }

    private function calcularSaidaAntecipada(
        ?string $prevista,
        ?string $real
    ): int {

        if (
            $prevista === null
            || $real === null
            || $real >= $prevista
        ) {
            return 0;
        }

        return $this->diferencaMinutos(
            $real,
            $prevista
        );
    }

    private function determinarOcorrencia(
        ?string $entrada,
        ?string $saida,
        int $atraso,
        int $saidaAntecipada,
        bool $faltaDiaInteiro,
        int $minutosFalta
    ): string {

        if ($faltaDiaInteiro) {
            return 'FALTA_TOTAL';
        }

        if (
            $entrada === null
            || $saida === null
        ) {
            return 'INCOMPLETO';
        }

        if ($atraso > 0) {
            return 'ATRASO';
        }

        if ($saidaAntecipada > 0) {
            return 'SAIDA_ANTECIPADA';
        }

        if ($minutosFalta > 0) {
            return 'FALTA_PARCIAL';
        }

        return 'NORMAL';
    }

    private function houveTrabalhoNocturno(
        ?string $entrada,
        ?string $saida
    ): int {

        if (
            $entrada === null
            || $saida === null
        ) {
            return 0;
        }

        return (
            $entrada < '06:00:00'
            || $entrada >= '20:00:00'
            || $saida > '20:00:00'
            || $saida <= '06:00:00'
        )
            ? 1
            : 0;
    }

    private function buscarSalarioVigenteHoraExtra(
        int $empresaId,
        int $funcionarioId,
        string $data
    ): array {

        /*
     * data_hora_extra pode vir como:
     *
     * 2026-08-15
     *
     * ou
     *
     * 2026-08-15 18:00:00
     *
     * Para comparar com data_inicio/data_fim,
     * usamos apenas YYYY-MM-DD.
     */
        $dataReferencia =
            substr(
                trim($data),
                0,
                10
            );


        if ($dataReferencia === '') {

            throw new RuntimeException(
                'Data da hora extra inválida.'
            );
        }


        $stmt =
            $this->pdo->prepare("
            SELECT
                salario.id,
                salario.salario_base,
                salario.horas_mensais,
                salario.dias_mensais,
                salario.moeda,
                salario.data_inicio,
                salario.data_fim

            FROM salarios_funcionarios salario

            INNER JOIN status st
                ON st.id =
                    salario.status_id

               AND st.tipo =
                    'salario_funcionario'

            WHERE salario.empresa_id =
                    :empresa_id

              AND salario.funcionario_id =
                    :funcionario_id

              AND LOWER(st.nome)
                    IN
                    (
                        'activo',
                        'ativo',
                        'activa',
                        'ativa'
                    )

              AND salario.data_inicio
                    <= :data_inicio_referencia

              AND
              (
                  salario.data_fim IS NULL

                  OR salario.data_fim
                        >= :data_fim_referencia
              )

            ORDER BY
                salario.data_inicio DESC,
                salario.id DESC

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId,

            ':data_inicio_referencia' =>
            $dataReferencia,

            ':data_fim_referencia' =>
            $dataReferencia
        ]);


        $salario =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$salario) {

            throw new RuntimeException(
                'O funcionário não possui configuração salarial activa '
                    . 'na data da hora extra.'
            );
        }


        $salarioBase =
            (float)(
                $salario['salario_base']
                ?? 0
            );


        $horasMensais =
            (float)(
                $salario['horas_mensais']
                ?? 0
            );


        if ($salarioBase <= 0) {

            throw new RuntimeException(
                'O salário base do funcionário não está configurado.'
            );
        }


        if ($horasMensais <= 0) {

            throw new RuntimeException(
                'As horas mensais do funcionário não estão configuradas.'
            );
        }


        return $salario;
    }

    private function percentualHoraExtra(
        string $tipoHoraExtra
    ): float {

        $tipo =
            strtoupper(
                trim(
                    $tipoHoraExtra
                )
            );

        return match ($tipo) {

            'DIA_NORMAL' =>
            50.00,

            'NOCTURNA' =>
            50.00,

            'FERIADO',
            'FOLGA',
            'FIM_SEMANA' =>
            100.00,

            default =>
            0.00
        };
    }

    // ==================================================
    // STATUS
    // ==================================================

    private function statusId(
        string $tipo,
        array $nomes
    ): int {

        $nomes =
            array_values(
                array_map(
                    static fn(
                        string $nome
                    ): string =>
                    mb_strtolower(
                        trim($nome),
                        'UTF-8'
                    ),
                    $nomes
                )
            );

        $placeholders = [];

        $params = [
            ':tipo' =>
            $tipo
        ];

        foreach (
            $nomes
            as $indice => $nome
        ) {
            $placeholder =
                ':nome_'
                . $indice;

            $placeholders[] =
                $placeholder;

            $params[$placeholder] =
                $nome;
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                :tipo

              AND LOWER(nome) IN
              (
                  "
            . implode(
                ',',
                $placeholders
            )
            . "
              )

            ORDER BY id

            LIMIT 1
        ");

        $stmt->execute(
            $params
        );

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                'O estado necessário não está configurado para '
                    . $tipo
                    . '.'
            );
        }

        return $id;
    }

    private function validarStatus(
        int $statusId,
        string $tipo
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE id =
                :status_id

              AND tipo =
                :tipo

            LIMIT 1
        ");

        $stmt->execute([
            ':status_id' =>
            $statusId,

            ':tipo' =>
            $tipo
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Estado inválido.'
            );
        }
    }

    // ==================================================
    // VALIDAÇÕES GERAIS
    // ==================================================

    private function validarRegistroEmpresa(
        string $tabela,
        int $registroId,
        int $empresaId,
        string $mensagem
    ): void {

        $tabelasPermitidas = [
            'filiais',
            'funcionarios',
            'horarios_trabalho'
        ];

        if (
            !in_array(
                $tabela,
                $tabelasPermitidas,
                true
            )
        ) {
            throw new RuntimeException(
                'Tabela de validação inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM {$tabela}

            WHERE id =
                :registro_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':registro_id' =>
            $registroId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                $mensagem
            );
        }
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
                'Empresa inválida para a gestão de assiduidade.'
            );
        }
    }

    private function validarId(
        int $id,
        string $entidade
    ): void {

        if ($id <= 0) {
            throw new RuntimeException(
                $entidade
                    . ' inválido.'
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
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($data === '') {
            throw new RuntimeException(
                'Informe a '
                    . $campo
                    . '.'
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
            || trim(
                (string)$valor
            ) === ''
        ) {
            return null;
        }

        return $this->validarData(
            trim(
                (string)$valor
            ),
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
            || $objeto->format(
                'Y-m-d'
            ) !== $data
        ) {
            throw new RuntimeException(
                'A '
                    . $campo
                    . ' é inválida.'
            );
        }

        return $data;
    }

    private function horaObrigatoria(
        mixed $valor,
        string $campo
    ): string {

        $hora =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($hora === '') {
            throw new RuntimeException(
                'Informe a '
                    . $campo
                    . '.'
            );
        }

        if (
            preg_match(
                '/^\d{2}:\d{2}$/',
                $hora
            )
        ) {
            $hora .= ':00';
        }

        $objeto =
            DateTimeImmutable::createFromFormat(
                '!H:i:s',
                $hora
            );

        if (
            $objeto === false
            || $objeto->format(
                'H:i:s'
            ) !== $hora
        ) {
            throw new RuntimeException(
                'A '
                    . $campo
                    . ' é inválida.'
            );
        }

        return $hora;
    }

    private function enumObrigatorio(
        mixed $valor,
        array $permitidos,
        string $campo
    ): string {

        $texto =
            strtoupper(
                trim(
                    (string)(
                        $valor ?? ''
                    )
                )
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
            || trim(
                (string)$valor
            ) === ''
        ) {
            return null;
        }

        return $this->enumObrigatorio(
            $valor,
            $permitidos,
            $campo
        );
    }

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim(
                (string)$valor
            ) === ''
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
                'O '
                    . $campo
                    . ' é inválido.'
            );
        }

        return (int)$inteiro;
    }

    private function decimalNaoNegativo(
        mixed $valor,
        string $campo
    ): string {

        $texto =
            str_replace(
                ',',
                '.',
                trim(
                    (string)(
                        $valor ?? '0'
                    )
                )
            );

        if (
            $texto === ''
            || !is_numeric($texto)
        ) {
            throw new RuntimeException(
                'O '
                    . $campo
                    . ' é inválido.'
            );
        }

        $numero =
            (float)$texto;

        if ($numero < 0) {
            throw new RuntimeException(
                'O '
                    . $campo
                    . ' não pode ser negativo.'
            );
        }

        return number_format(
            $numero,
            2,
            '.',
            ''
        );
    }

    private function decimalOpcional(
        mixed $valor,
        float $minimo,
        float $maximo,
        string $campo
    ): ?string {

        if (
            $valor === null
            || trim(
                (string)$valor
            ) === ''
        ) {
            return null;
        }

        $texto =
            str_replace(
                ',',
                '.',
                trim(
                    (string)$valor
                )
            );

        if (!is_numeric($texto)) {
            throw new RuntimeException(
                'A '
                    . $campo
                    . ' é inválida.'
            );
        }

        $numero =
            (float)$texto;

        if (
            $numero < $minimo
            || $numero > $maximo
        ) {
            throw new RuntimeException(
                'A '
                    . $campo
                    . ' está fora do intervalo permitido.'
            );
        }

        return number_format(
            $numero,
            7,
            '.',
            ''
        );
    }

    private function textoObrigatorio(
        mixed $valor,
        string $campo
    ): string {

        $texto =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($texto === '') {
            throw new RuntimeException(
                'Informe a '
                    . $campo
                    . '.'
            );
        }

        return $texto;
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

    private function buscarStatusId(string $tipo, string $nome): int
    {
        $sql = "
        SELECT id
        FROM status
        WHERE LOWER(tipo) = LOWER(:tipo)
          AND LOWER(nome) = LOWER(:nome)
        LIMIT 1
    ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':tipo' => trim($tipo),
            ':nome' => trim($nome)
        ]);

        $statusId = $stmt->fetchColumn();

        if (!$statusId) {
            throw new RuntimeException(
                "Estado '{$nome}' do tipo '{$tipo}' não encontrado."
            );
        }

        return (int) $statusId;
    }

    private function booleano(
        mixed $valor
    ): int {

        if (is_bool($valor)) {
            return $valor
                ? 1
                : 0;
        }

        return in_array(
            strtolower(
                trim(
                    (string)$valor
                )
            ),
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
