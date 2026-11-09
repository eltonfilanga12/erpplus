<?php

declare(strict_types=1);

final class RelatorioRH
{
    private PDO $db;


    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | ASSIDUIDADE
    |--------------------------------------------------------------------------
    */

    public function assiduidade(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'a.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        /*
        |--------------------------------------------------------------------------
        | Datas
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'a.data_assiduidade >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (!empty($filtros['data_fim'])) {

            $where[] =
                'a.data_assiduidade <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        /*
        |--------------------------------------------------------------------------
        | Funcionário
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['funcionario_id'])) {

            $where[] =
                'a.funcionario_id = :funcionario_id';

            $params[':funcionario_id'] =
                (int)$filtros['funcionario_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | Filial
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['filial_id'])) {

            $where[] = "
                COALESCE(
                    a.filial_id,
                    f.filial_id
                ) = :filial_id
            ";

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | Departamento
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['departamento_id'])) {

            $where[] =
                'f.departamento_id = :departamento_id';

            $params[':departamento_id'] =
                (int)$filtros['departamento_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | Cargo
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['cargo_id'])) {

            $where[] =
                'f.cargo_id = :cargo_id';

            $params[':cargo_id'] =
                (int)$filtros['cargo_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | Ocorrência
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['ocorrencia'])) {

            $where[] =
                'a.ocorrencia = :ocorrencia';

            $params[':ocorrencia'] =
                $filtros['ocorrencia'];
        }


        /*
        |--------------------------------------------------------------------------
        | Pesquisa
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['pesquisa'])) {

            $where[] = "
                (
                    f.numero_funcionario
                        LIKE :pesquisa_numero

                    OR f.nome_completo
                        LIKE :pesquisa_nome
                )
            ";


            $pesquisa =
                '%'
                . $filtros['pesquisa']
                . '%';


            $params[':pesquisa_numero'] =
                $pesquisa;

            $params[':pesquisa_nome'] =
                $pesquisa;
        }


        $sql = "
            SELECT

                a.id,

                a.data_assiduidade,

                a.dia_semana,

                a.hora_prevista_entrada,

                a.hora_prevista_saida,

                a.hora_real_entrada,

                a.hora_real_saida,

                a.minutos_previstos,

                a.minutos_trabalhados,

                a.minutos_atraso,

                a.minutos_saida_antecipada,

                a.minutos_extra,

                a.minutos_falta,

                a.presente,

                a.falta_dia_inteiro,

                a.dia_util,

                a.feriado,

                a.folga,

                a.em_ferias,

                a.em_licenca,

                a.trabalho_nocturno,

                a.trabalho_feriado,

                a.ocorrencia,

                a.observacoes,

                f.id
                    AS funcionario_id,

                f.numero_funcionario,

                f.nome_completo
                    AS funcionario_nome,

                COALESCE(
                    fi.nome,
                    'Sem filial'
                ) AS filial_nome,

                COALESCE(
                    d.nome,
                    'Sem departamento'
                ) AS departamento_nome,

                COALESCE(
                    c.nome,
                    'Sem cargo'
                ) AS cargo_nome,

                COALESCE(
                    st.nome,
                    '-'
                ) AS estado

            FROM assiduidade_diaria a

            INNER JOIN funcionarios f
                ON f.id = a.funcionario_id
               AND f.empresa_id = a.empresa_id

            LEFT JOIN filiais fi
                ON fi.id =
                    COALESCE(
                        a.filial_id,
                        f.filial_id
                    )
               AND fi.empresa_id =
                    a.empresa_id

            LEFT JOIN departamentos d
                ON d.id =
                    f.departamento_id
               AND d.empresa_id =
                    a.empresa_id

            LEFT JOIN cargos c
                ON c.id =
                    f.cargo_id
               AND c.empresa_id =
                    a.empresa_id

            LEFT JOIN status st
                ON st.id =
                    a.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                a.data_assiduidade DESC,
                f.nome_completo ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
        |--------------------------------------------------------------------------
        | Resumo
        |--------------------------------------------------------------------------
        */

        $presencas = 0;
        $faltas = 0;
        $atrasos = 0;
        $saidasAntecipadas = 0;

        $minutosTrabalhados = 0;
        $minutosAtraso = 0;
        $minutosExtra = 0;
        $minutosFalta = 0;


        foreach ($registos as $linha) {

            if ((int)$linha['presente'] === 1) {
                $presencas++;
            }


            if (
                (int)$linha['falta_dia_inteiro'] === 1
            ) {
                $faltas++;
            }


            if (
                (int)$linha['minutos_atraso'] > 0
            ) {
                $atrasos++;
            }


            if (
                (int)$linha['minutos_saida_antecipada'] > 0
            ) {
                $saidasAntecipadas++;
            }


            $minutosTrabalhados +=
                (int)$linha['minutos_trabalhados'];

            $minutosAtraso +=
                (int)$linha['minutos_atraso'];

            $minutosExtra +=
                (int)$linha['minutos_extra'];

            $minutosFalta +=
                (int)$linha['minutos_falta'];
        }


        return [

            'resumo' => [

                'total_registos' =>
                count($registos),

                'presencas' =>
                $presencas,

                'faltas' =>
                $faltas,

                'atrasos' =>
                $atrasos,

                'saidas_antecipadas' =>
                $saidasAntecipadas,

                'minutos_trabalhados' =>
                $minutosTrabalhados,

                'minutos_atraso' =>
                $minutosAtraso,

                'minutos_extra' =>
                $minutosExtra,

                'minutos_falta' =>
                $minutosFalta
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FALTAS
    |--------------------------------------------------------------------------
    */

    public function faltas(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'fa.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'fa.data_fim >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (!empty($filtros['data_fim'])) {

            $where[] =
                'fa.data_inicio <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        $this->aplicarFiltrosFuncionario(
            $where,
            $params,
            $filtros,
            'f'
        );


        if (!empty($filtros['tipo_falta'])) {

            $where[] =
                'fa.tipo_falta = :tipo_falta';

            $params[':tipo_falta'] =
                $filtros['tipo_falta'];
        }


        if (!empty($filtros['origem'])) {

            $where[] =
                'fa.origem = :origem';

            $params[':origem'] =
                $filtros['origem'];
        }


        $sql = "
            SELECT

                fa.id,

                fa.data_inicio,

                fa.data_fim,

                fa.hora_inicio,

                fa.hora_fim,

                fa.tipo_falta,

                fa.origem,

                fa.quantidade_dias,

                fa.minutos_falta,

                fa.motivo,

                fa.justificacao,

                fa.remunerada,

                fa.desconta_salario,

                fa.desconta_ferias,

                fa.observacoes,

                f.id
                    AS funcionario_id,

                f.numero_funcionario,

                f.nome_completo
                    AS funcionario_nome,

                COALESCE(
                    fi.nome,
                    'Sem filial'
                ) AS filial_nome,

                COALESCE(
                    d.nome,
                    'Sem departamento'
                ) AS departamento_nome,

                COALESCE(
                    c.nome,
                    'Sem cargo'
                ) AS cargo_nome,

                COALESCE(
                    st.nome,
                    '-'
                ) AS estado

            FROM faltas fa

            INNER JOIN funcionarios f
                ON f.id =
                    fa.funcionario_id
               AND f.empresa_id =
                    fa.empresa_id

            LEFT JOIN filiais fi
                ON fi.id =
                    f.filial_id
               AND fi.empresa_id =
                    fa.empresa_id

            LEFT JOIN departamentos d
                ON d.id =
                    f.departamento_id
               AND d.empresa_id =
                    fa.empresa_id

            LEFT JOIN cargos c
                ON c.id =
                    f.cargo_id
               AND c.empresa_id =
                    fa.empresa_id

            LEFT JOIN status st
                ON st.id =
                    fa.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                fa.data_inicio DESC,
                f.nome_completo ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $dias = 0.0;
        $minutos = 0;
        $remuneradas = 0;
        $descontamSalario = 0;


        foreach ($registos as $linha) {

            $dias +=
                (float)$linha['quantidade_dias'];

            $minutos +=
                (int)$linha['minutos_falta'];


            if (
                (int)$linha['remunerada'] === 1
            ) {
                $remuneradas++;
            }


            if (
                (int)$linha['desconta_salario'] === 1
            ) {
                $descontamSalario++;
            }
        }


        return [

            'resumo' => [

                'total_faltas' =>
                count($registos),

                'total_dias' =>
                $dias,

                'total_minutos' =>
                $minutos,

                'remuneradas' =>
                $remuneradas,

                'descontam_salario' =>
                $descontamSalario
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | HORAS EXTRAS
    |--------------------------------------------------------------------------
    */

    public function horasExtras(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'he.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'he.data_hora_extra >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (!empty($filtros['data_fim'])) {

            $where[] =
                'he.data_hora_extra <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        $this->aplicarFiltrosFuncionario(
            $where,
            $params,
            $filtros,
            'f'
        );


        if (!empty($filtros['tipo_hora_extra'])) {

            $where[] =
                'he.tipo_hora_extra = :tipo_hora_extra';

            $params[':tipo_hora_extra'] =
                $filtros['tipo_hora_extra'];
        }


        if (!empty($filtros['origem'])) {

            $where[] =
                'he.origem = :origem';

            $params[':origem'] =
                $filtros['origem'];
        }


        $sql = "
            SELECT

                he.id,

                he.data_hora_extra,

                he.hora_inicio,

                he.hora_fim,

                he.minutos_solicitados,

                he.minutos_apurados,

                he.minutos_aprovados,

                he.tipo_hora_extra,

                he.origem,

                he.percentual_acrescimo,

                he.valor_hora,

                he.valor_total,

                he.motivo,

                he.observacoes,

                he.aprovado_em,

                f.id
                    AS funcionario_id,

                f.numero_funcionario,

                f.nome_completo
                    AS funcionario_nome,

                COALESCE(
                    fi.nome,
                    'Sem filial'
                ) AS filial_nome,

                COALESCE(
                    d.nome,
                    'Sem departamento'
                ) AS departamento_nome,

                COALESCE(
                    c.nome,
                    'Sem cargo'
                ) AS cargo_nome,

                COALESCE(
                    st.nome,
                    '-'
                ) AS estado

            FROM horas_extras he

            INNER JOIN funcionarios f
                ON f.id =
                    he.funcionario_id
               AND f.empresa_id =
                    he.empresa_id

            LEFT JOIN filiais fi
                ON fi.id =
                    f.filial_id
               AND fi.empresa_id =
                    he.empresa_id

            LEFT JOIN departamentos d
                ON d.id =
                    f.departamento_id
               AND d.empresa_id =
                    he.empresa_id

            LEFT JOIN cargos c
                ON c.id =
                    f.cargo_id
               AND c.empresa_id =
                    he.empresa_id

            LEFT JOIN status st
                ON st.id =
                    he.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                he.data_hora_extra DESC,
                f.nome_completo ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $solicitados = 0;
        $apurados = 0;
        $aprovados = 0;
        $valorTotal = 0.0;


        foreach ($registos as $linha) {

            $solicitados +=
                (int)$linha['minutos_solicitados'];

            $apurados +=
                (int)$linha['minutos_apurados'];

            $aprovados +=
                (int)$linha['minutos_aprovados'];

            $valorTotal +=
                (float)$linha['valor_total'];
        }


        return [

            'resumo' => [

                'total_registos' =>
                count($registos),

                'minutos_solicitados' =>
                $solicitados,

                'minutos_apurados' =>
                $apurados,

                'minutos_aprovados' =>
                $aprovados,

                'valor_total' =>
                $valorTotal
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FOLHA SALARIAL
    |--------------------------------------------------------------------------
    */

    public function folhaSalarial(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'fs.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (!empty($filtros['periodo_id'])) {

            $where[] =
                'fs.periodo_id = :periodo_id';

            $params[':periodo_id'] =
                (int)$filtros['periodo_id'];
        }


        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'ps.data_fim >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (!empty($filtros['data_fim'])) {

            $where[] =
                'ps.data_inicio <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        if (!empty($filtros['pesquisa'])) {

            $where[] = "
                (
                    fs.codigo
                        LIKE :pesquisa_codigo

                    OR fs.descricao
                        LIKE :pesquisa_descricao

                    OR ps.codigo
                        LIKE :pesquisa_periodo
                )
            ";


            $pesquisa =
                '%'
                . $filtros['pesquisa']
                . '%';


            $params[':pesquisa_codigo'] =
                $pesquisa;

            $params[':pesquisa_descricao'] =
                $pesquisa;

            $params[':pesquisa_periodo'] =
                $pesquisa;
        }


        $sql = "
            SELECT

                fs.id,

                fs.codigo,

                fs.descricao,

                fs.moeda,

                fs.numero_funcionarios,

                fs.total_salario_base,

                fs.total_subsidios,

                fs.total_horas_extras,

                fs.total_outros_proventos,

                fs.total_proventos,

                fs.total_faltas,

                fs.total_atrasos,

                fs.total_inss,

                fs.total_inss_patronal,

                fs.total_irps,

                fs.total_outros_descontos,

                fs.total_descontos,

                fs.total_bruto,

                fs.total_liquido,

                fs.total_pago,

                fs.saldo_pendente,

                fs.versao,

                fs.gerada_em,

                fs.fechada_em,

                ps.id
                    AS periodo_id,

                ps.codigo
                    AS periodo_codigo,

                ps.descricao
                    AS periodo_descricao,

                ps.ano,

                ps.mes,

                ps.data_inicio,

                ps.data_fim,

                COALESCE(
                    st.nome,
                    '-'
                ) AS estado

            FROM folhas_salariais fs

            INNER JOIN periodos_salariais ps
                ON ps.id =
                    fs.periodo_id
               AND ps.empresa_id =
                    fs.empresa_id

            LEFT JOIN status st
                ON st.id =
                    fs.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                ps.ano DESC,
                ps.mes DESC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $funcionarios = 0;
        $salarioBase = 0.0;
        $proventos = 0.0;
        $descontos = 0.0;
        $bruto = 0.0;
        $liquido = 0.0;
        $pago = 0.0;
        $pendente = 0.0;


        foreach ($registos as $linha) {

            $funcionarios +=
                (int)$linha['numero_funcionarios'];

            $salarioBase +=
                (float)$linha['total_salario_base'];

            $proventos +=
                (float)$linha['total_proventos'];

            $descontos +=
                (float)$linha['total_descontos'];

            $bruto +=
                (float)$linha['total_bruto'];

            $liquido +=
                (float)$linha['total_liquido'];

            $pago +=
                (float)$linha['total_pago'];

            $pendente +=
                (float)$linha['saldo_pendente'];
        }


        return [

            'resumo' => [

                'total_folhas' =>
                count($registos),

                'numero_funcionarios' =>
                $funcionarios,

                'total_salario_base' =>
                $salarioBase,

                'total_proventos' =>
                $proventos,

                'total_descontos' =>
                $descontos,

                'total_bruto' =>
                $bruto,

                'total_liquido' =>
                $liquido,

                'total_pago' =>
                $pago,

                'saldo_pendente' =>
                $pendente
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ENCARGOS
    |--------------------------------------------------------------------------
    */

    public function encargos(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'fs.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (!empty($filtros['periodo_id'])) {

            $where[] =
                'fs.periodo_id = :periodo_id';

            $params[':periodo_id'] =
                (int)$filtros['periodo_id'];
        }


        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'ps.data_fim >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (!empty($filtros['data_fim'])) {

            $where[] =
                'ps.data_inicio <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        $sql = "
            SELECT

                fs.id,

                fs.codigo,

                fs.moeda,

                fs.numero_funcionarios,

                fs.total_inss,

                fs.total_inss_patronal,

                fs.total_irps,

                fs.total_outros_descontos,

                (
                    fs.total_inss
                    + fs.total_irps
                ) AS retencoes_trabalhador,

                (
                    fs.total_inss
                    + fs.total_inss_patronal
                    + fs.total_irps
                ) AS encargos_fiscais_sociais,

                ps.id
                    AS periodo_id,

                ps.codigo
                    AS periodo_codigo,

                ps.descricao
                    AS periodo_descricao,

                ps.ano,

                ps.mes,

                ps.data_inicio,

                ps.data_fim,

                COALESCE(
                    st.nome,
                    '-'
                ) AS estado

            FROM folhas_salariais fs

            INNER JOIN periodos_salariais ps
                ON ps.id =
                    fs.periodo_id
               AND ps.empresa_id =
                    fs.empresa_id

            LEFT JOIN status st
                ON st.id =
                    fs.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                ps.ano DESC,
                ps.mes DESC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $inss = 0.0;
        $inssPatronal = 0.0;
        $irps = 0.0;
        $outros = 0.0;


        foreach ($registos as $linha) {

            $inss +=
                (float)$linha['total_inss'];

            $inssPatronal +=
                (float)$linha['total_inss_patronal'];

            $irps +=
                (float)$linha['total_irps'];

            $outros +=
                (float)$linha['total_outros_descontos'];
        }


        return [

            'resumo' => [

                'total_inss_trabalhador' =>
                $inss,

                'total_inss_patronal' =>
                $inssPatronal,

                'total_irps' =>
                $irps,

                'total_outros_descontos' =>
                $outros,

                'retencoes_trabalhador' =>
                $inss + $irps,

                'encargos_fiscais_sociais' =>
                $inss
                    + $inssPatronal
                    + $irps
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FUNCIONÁRIOS
    |--------------------------------------------------------------------------
    */

    public function funcionarios(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'f.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        $this->aplicarFiltrosFuncionario(
            $where,
            $params,
            $filtros,
            'f'
        );


        if (!empty($filtros['sexo'])) {

            $where[] =
                'f.sexo = :sexo';

            $params[':sexo'] =
                $filtros['sexo'];
        }


        if (!empty($filtros['tipo_vinculo'])) {

            $where[] =
                'f.tipo_vinculo = :tipo_vinculo';

            $params[':tipo_vinculo'] =
                $filtros['tipo_vinculo'];
        }


        $sql = "
            SELECT

                f.id,

                f.numero_funcionario,

                f.nome_completo,

                f.sexo,

                f.data_nascimento,

                f.nuit,

                f.numero_inss,

                f.email_profissional,

                f.telefone_principal,

                f.data_admissao,

                f.data_desligamento,

                f.tipo_vinculo,

                f.salario_base
                    AS salario_base_cadastro,

                f.moeda,

                COALESCE(
                    fi.nome,
                    'Sem filial'
                ) AS filial_nome,

                COALESCE(
                    d.nome,
                    'Sem departamento'
                ) AS departamento_nome,

                COALESCE(
                    c.nome,
                    'Sem cargo'
                ) AS cargo_nome,

                COALESCE(
                    st.nome,
                    '-'
                ) AS estado,

                COALESCE(
                    (
                        SELECT sf.salario_base

                        FROM salarios_funcionarios sf

                        WHERE
                            sf.empresa_id =
                                f.empresa_id

                            AND sf.funcionario_id =
                                f.id

                            AND sf.data_inicio <=
                                CURRENT_DATE

                            AND
                            (
                                sf.data_fim IS NULL
                                OR sf.data_fim >=
                                    CURRENT_DATE
                            )

                        ORDER BY
                            sf.data_inicio DESC,
                            sf.id DESC

                        LIMIT 1
                    ),

                    f.salario_base
                ) AS salario_base_atual

            FROM funcionarios f

            LEFT JOIN filiais fi
                ON fi.id =
                    f.filial_id
               AND fi.empresa_id =
                    f.empresa_id

            LEFT JOIN departamentos d
                ON d.id =
                    f.departamento_id
               AND d.empresa_id =
                    f.empresa_id

            LEFT JOIN cargos c
                ON c.id =
                    f.cargo_id
               AND c.empresa_id =
                    f.empresa_id

            LEFT JOIN status st
                ON st.id =
                    f.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                f.nome_completo ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $emQuadro = 0;
        $desligados = 0;
        $masculino = 0;
        $feminino = 0;
        $massaBase = 0.0;


        foreach ($registos as $linha) {

            if (
                empty($linha['data_desligamento'])
            ) {
                $emQuadro++;
            } else {
                $desligados++;
            }


            if (
                $linha['sexo']
                === 'MASCULINO'
            ) {
                $masculino++;
            }


            if (
                $linha['sexo']
                === 'FEMININO'
            ) {
                $feminino++;
            }


            $massaBase +=
                (float)$linha['salario_base_atual'];
        }


        return [

            'resumo' => [

                'total_funcionarios' =>
                count($registos),

                'em_quadro' =>
                $emQuadro,

                'desligados' =>
                $desligados,

                'masculino' =>
                $masculino,

                'feminino' =>
                $feminino,

                'massa_salarial_base' =>
                $massaBase
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | PERFIL DO FUNCIONÁRIO
    |--------------------------------------------------------------------------
    */

    public function perfilFuncionario(
        int $empresaId,
        int $funcionarioId,
        array $filtros = []
    ): array {

        $funcionario =
            $this->buscarFuncionario(
                $empresaId,
                $funcionarioId
            );


        if (!$funcionario) {

            throw new RuntimeException(
                'Funcionário não encontrado.'
            );
        }


        $contrato =
            $this->contratoAtual(
                $empresaId,
                $funcionarioId
            );


        $salario =
            $this->salarioAtual(
                $empresaId,
                $funcionarioId
            );


        /*
        |--------------------------------------------------------------------------
        | Resumo de assiduidade
        |--------------------------------------------------------------------------
        */

        $filtrosPerfil =
            $filtros;


        $filtrosPerfil['funcionario_id'] =
            $funcionarioId;


        $assiduidade =
            $this->assiduidade(
                $empresaId,
                $filtrosPerfil
            );


        $faltas =
            $this->faltas(
                $empresaId,
                $filtrosPerfil
            );


        $horasExtras =
            $this->horasExtras(
                $empresaId,
                $filtrosPerfil
            );


        return [

            'funcionario' =>
            $funcionario,

            'contrato' =>
            $contrato,

            'salario' =>
            $salario,

            'assiduidade' =>
            $assiduidade,

            'faltas' =>
            $faltas,

            'horas_extras' =>
            $horasExtras
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR FILIAIS
    |--------------------------------------------------------------------------
    */

    public function listarFiliais(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
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


    /*
    |--------------------------------------------------------------------------
    | LISTAR DEPARTAMENTOS
    |--------------------------------------------------------------------------
    */

    public function listarDepartamentos(
        int $empresaId,
        ?int $filialId = null
    ): array {

        $sql = "
            SELECT
                id,
                filial_id,
                codigo,
                nome

            FROM departamentos

            WHERE empresa_id =
                :empresa_id
        ";


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            $filialId !== null
            && $filialId > 0
        ) {

            /*
            | Departamentos globais da empresa
            | também permanecem disponíveis.
            */

            $sql .= "
                AND
                (
                    filial_id = :filial_id
                    OR filial_id IS NULL
                )
            ";


            $params[':filial_id'] =
                $filialId;
        }


        $sql .= "
            ORDER BY nome ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR CARGOS
    |--------------------------------------------------------------------------
    */

    public function listarCargos(
        int $empresaId,
        ?int $departamentoId = null
    ): array {

        $sql = "
            SELECT
                id,
                departamento_id,
                codigo,
                nome,
                nivel_hierarquico,
                permite_horas_extras

            FROM cargos

            WHERE empresa_id =
                :empresa_id
        ";


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            $departamentoId !== null
            && $departamentoId > 0
        ) {

            $sql .= "
                AND departamento_id =
                    :departamento_id
            ";


            $params[':departamento_id'] =
                $departamentoId;
        }


        $sql .= "
            ORDER BY
                nivel_hierarquico DESC,
                nome ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR FUNCIONÁRIOS
    |--------------------------------------------------------------------------
    */

    public function listarFuncionariosFiltro(
        int $empresaId,
        array $filtros = []
    ): array {

        $where = [
            'f.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        $this->aplicarFiltrosFuncionario(
            $where,
            $params,
            $filtros,
            'f',
            false
        );


        $sql = "
            SELECT

                f.id,

                f.numero_funcionario,

                f.nome_completo,

                f.filial_id,

                f.departamento_id,

                f.cargo_id

            FROM funcionarios f

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                f.nome_completo ASC
        ";


        $stmt =
            $this->db->prepare($sql);


        $stmt->execute($params);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR PERÍODOS SALARIAIS
    |--------------------------------------------------------------------------
    */

    public function listarPeriodos(
        int $empresaId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT

                    ps.id,

                    ps.codigo,

                    ps.descricao,

                    ps.ano,

                    ps.mes,

                    ps.data_inicio,

                    ps.data_fim,

                    ps.data_prevista_pagamento,

                    ps.dias_uteis,

                    COALESCE(
                        st.nome,
                        '-'
                    ) AS estado

                FROM periodos_salariais ps

                LEFT JOIN status st
                    ON st.id =
                        ps.status_id

                WHERE ps.empresa_id =
                    :empresa_id

                ORDER BY
                    ps.ano DESC,
                    ps.mes DESC
            ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCAR FUNCIONÁRIO
    |--------------------------------------------------------------------------
    */

    private function buscarFuncionario(
        int $empresaId,
        int $funcionarioId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT

                    f.*,

                    COALESCE(
                        fi.nome,
                        'Sem filial'
                    ) AS filial_nome,

                    COALESCE(
                        d.nome,
                        'Sem departamento'
                    ) AS departamento_nome,

                    COALESCE(
                        c.nome,
                        'Sem cargo'
                    ) AS cargo_nome,

                    COALESCE(
                        st.nome,
                        '-'
                    ) AS estado

                FROM funcionarios f

                LEFT JOIN filiais fi
                    ON fi.id =
                        f.filial_id
                   AND fi.empresa_id =
                        f.empresa_id

                LEFT JOIN departamentos d
                    ON d.id =
                        f.departamento_id
                   AND d.empresa_id =
                        f.empresa_id

                LEFT JOIN cargos c
                    ON c.id =
                        f.cargo_id
                   AND c.empresa_id =
                        f.empresa_id

                LEFT JOIN status st
                    ON st.id =
                        f.status_id

                WHERE
                    f.id =
                        :funcionario_id

                    AND f.empresa_id =
                        :empresa_id

                LIMIT 1
            ");


        $stmt->execute([

            ':funcionario_id' =>
            $funcionarioId,

            ':empresa_id' =>
            $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | CONTRATO ATUAL
    |--------------------------------------------------------------------------
    */

    private function contratoAtual(
        int $empresaId,
        int $funcionarioId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT

                    ct.id,

                    ct.numero_contrato,

                    ct.data_assinatura,

                    ct.data_inicio,

                    ct.data_fim,

                    ct.data_proxima_renovacao,

                    ct.salario_base,

                    ct.moeda,

                    ct.periodicidade_salario,

                    ct.horas_semanais,

                    ct.local_trabalho,

                    ct.descricao_funcoes,

                    ct.beneficios,

                    ct.data_suspensao,

                    ct.data_rescisao,

                    COALESCE(
                        fi.nome,
                        'Sem filial'
                    ) AS filial_nome,

                    COALESCE(
                        d.nome,
                        'Sem departamento'
                    ) AS departamento_nome,

                    COALESCE(
                        c.nome,
                        'Sem cargo'
                    ) AS cargo_nome,

                    COALESCE(
                        st.nome,
                        '-'
                    ) AS estado

                FROM contratos_trabalho ct

                LEFT JOIN filiais fi
                    ON fi.id =
                        ct.filial_id
                   AND fi.empresa_id =
                        ct.empresa_id

                LEFT JOIN departamentos d
                    ON d.id =
                        ct.departamento_id
                   AND d.empresa_id =
                        ct.empresa_id

                LEFT JOIN cargos c
                    ON c.id =
                        ct.cargo_id
                   AND c.empresa_id =
                        ct.empresa_id

                LEFT JOIN status st
                    ON st.id =
                        ct.status_id

                WHERE
                    ct.empresa_id =
                        :empresa_id

                    AND ct.funcionario_id =
                        :funcionario_id

                ORDER BY
                    CASE
                        WHEN
                            ct.data_rescisao IS NULL
                            AND
                            (
                                ct.data_fim IS NULL
                                OR ct.data_fim >=
                                    CURRENT_DATE
                            )
                        THEN 0
                        ELSE 1
                    END,

                    ct.data_inicio DESC,
                    ct.id DESC

                LIMIT 1
            ");


        $stmt->execute([

            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | SALÁRIO ATUAL
    |--------------------------------------------------------------------------
    */

    private function salarioAtual(
        int $empresaId,
        int $funcionarioId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT

                    sf.id,

                    sf.moeda,

                    sf.salario_base,

                    sf.periodicidade,

                    sf.horas_mensais,

                    sf.dias_mensais,

                    sf.data_inicio,

                    sf.data_fim,

                    sf.motivo_alteracao,

                    sf.observacoes,

                    COALESCE(
                        st.nome,
                        '-'
                    ) AS estado

                FROM salarios_funcionarios sf

                LEFT JOIN status st
                    ON st.id =
                        sf.status_id

                WHERE
                    sf.empresa_id =
                        :empresa_id

                    AND sf.funcionario_id =
                        :funcionario_id

                    AND sf.data_inicio <=
                        CURRENT_DATE

                    AND
                    (
                        sf.data_fim IS NULL
                        OR sf.data_fim >=
                            CURRENT_DATE
                    )

                ORDER BY
                    sf.data_inicio DESC,
                    sf.id DESC

                LIMIT 1
            ");


        $stmt->execute([

            ':empresa_id' =>
            $empresaId,

            ':funcionario_id' =>
            $funcionarioId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | FILTROS DE FUNCIONÁRIO
    |--------------------------------------------------------------------------
    */

    private function aplicarFiltrosFuncionario(
        array &$where,
        array &$params,
        array $filtros,
        string $alias = 'f',
        bool $aplicarPesquisa = true
    ): void {

        if (!empty($filtros['funcionario_id'])) {

            $where[] =
                "{$alias}.id = :filtro_funcionario_id";

            $params[':filtro_funcionario_id'] =
                (int)$filtros['funcionario_id'];
        }


        if (!empty($filtros['filial_id'])) {

            $where[] =
                "{$alias}.filial_id = :filtro_filial_id";

            $params[':filtro_filial_id'] =
                (int)$filtros['filial_id'];
        }


        if (!empty($filtros['departamento_id'])) {

            $where[] =
                "{$alias}.departamento_id = :filtro_departamento_id";

            $params[':filtro_departamento_id'] =
                (int)$filtros['departamento_id'];
        }


        if (!empty($filtros['cargo_id'])) {

            $where[] =
                "{$alias}.cargo_id = :filtro_cargo_id";

            $params[':filtro_cargo_id'] =
                (int)$filtros['cargo_id'];
        }


        if (
            $aplicarPesquisa
            && !empty($filtros['pesquisa'])
        ) {

            $where[] = "
                (
                    {$alias}.numero_funcionario
                        LIKE :filtro_pesquisa_numero

                    OR {$alias}.nome_completo
                        LIKE :filtro_pesquisa_nome

                    OR {$alias}.nuit
                        LIKE :filtro_pesquisa_nuit
                )
            ";


            $pesquisa =
                '%'
                . $filtros['pesquisa']
                . '%';


            $params[':filtro_pesquisa_numero'] =
                $pesquisa;

            $params[':filtro_pesquisa_nome'] =
                $pesquisa;

            $params[':filtro_pesquisa_nuit'] =
                $pesquisa;
        }
    }
}
