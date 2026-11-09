<?php

declare(strict_types=1);

final class RelatorioRHController
{
    private RelatorioRH $model;


    public function __construct(PDO $db)
    {
        $this->model =
            new RelatorioRH($db);
    }


    /*
    |--------------------------------------------------------------------------
    | RELATÓRIO
    |--------------------------------------------------------------------------
    */

    public function relatorio(
        int $empresaId,
        array $dados
    ): array {

        try {

            if ($empresaId <= 0) {

                throw new RuntimeException(
                    'Empresa não identificada.'
                );
            }


            $tipo =
                strtoupper(
                    trim(
                        (string)(
                            $dados['tipo']
                            ?? ''
                        )
                    )
                );


            if ($tipo === '') {

                throw new InvalidArgumentException(
                    'Tipo de relatório não informado.'
                );
            }


            $tiposPermitidos = [

                'ASSIDUIDADE',

                'FALTAS',

                'HORAS_EXTRAS',

                'FOLHA_SALARIAL',

                'ENCARGOS',

                'FUNCIONARIOS',

                'PERFIL_FUNCIONARIO'
            ];


            if (
                !in_array(
                    $tipo,
                    $tiposPermitidos,
                    true
                )
            ) {

                throw new InvalidArgumentException(
                    'Tipo de relatório de RH inválido.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Filtros
            |--------------------------------------------------------------------------
            */

            $filtros =
                $this->prepararFiltros(
                    $dados
                );


            /*
            |--------------------------------------------------------------------------
            | Executar relatório
            |--------------------------------------------------------------------------
            */

            $resultado =
                match ($tipo) {

                    'ASSIDUIDADE' =>
                    $this->model
                        ->assiduidade(
                            $empresaId,
                            $filtros
                        ),

                    'FALTAS' =>
                    $this->model
                        ->faltas(
                            $empresaId,
                            $filtros
                        ),

                    'HORAS_EXTRAS' =>
                    $this->model
                        ->horasExtras(
                            $empresaId,
                            $filtros
                        ),

                    'FOLHA_SALARIAL' =>
                    $this->model
                        ->folhaSalarial(
                            $empresaId,
                            $filtros
                        ),

                    'ENCARGOS' =>
                    $this->model
                        ->encargos(
                            $empresaId,
                            $filtros
                        ),

                    'FUNCIONARIOS' =>
                    $this->model
                        ->funcionarios(
                            $empresaId,
                            $filtros
                        ),

                    'PERFIL_FUNCIONARIO' =>
                    $this->perfilFuncionario(
                        $empresaId,
                        $filtros
                    ),

                    default =>
                    throw new RuntimeException(
                        'Tipo de relatório inválido.'
                    )
                };


            return [

                'success' => true,

                'message' =>
                'Relatório de RH carregado com sucesso.',

                'data' =>
                $resultado
            ];
        } catch (Throwable $e) {

            error_log(
                sprintf(
                    '[RelatorioRHController::relatorio] %s em %s:%d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );


            return [

                'success' => false,

                'message' =>
                $e->getMessage(),

                'data' => null
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PERFIL DO FUNCIONÁRIO
    |--------------------------------------------------------------------------
    */

    private function perfilFuncionario(
        int $empresaId,
        array $filtros
    ): array {

        $funcionarioId =
            (int)(
                $filtros['funcionario_id']
                ?? 0
            );


        if ($funcionarioId <= 0) {

            throw new InvalidArgumentException(
                'Selecione um funcionário para gerar o perfil.'
            );
        }


        return $this->model
            ->perfilFuncionario(
                $empresaId,
                $funcionarioId,
                $filtros
            );
    }


    /*
    |--------------------------------------------------------------------------
    | FILIAIS
    |--------------------------------------------------------------------------
    */

    public function filiais(
        int $empresaId
    ): array {

        try {

            $this->validarEmpresa(
                $empresaId
            );


            return [

                'success' => true,

                'message' =>
                'Filiais carregadas com sucesso.',

                'data' =>
                $this->model
                    ->listarFiliais(
                        $empresaId
                    )
            ];
        } catch (Throwable $e) {

            return $this->respostaErro(
                $e,
                []
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DEPARTAMENTOS
    |--------------------------------------------------------------------------
    */

    public function departamentos(
        int $empresaId,
        array $dados = []
    ): array {

        try {

            $this->validarEmpresa(
                $empresaId
            );


            $filialId =
                $this->inteiroOpcional(
                    $dados['filial_id']
                        ?? null,
                    'Filial'
                );


            /*
            | Segurança adicional:
            | se veio filial, validamos se realmente
            | pertence à empresa.
            */

            if ($filialId !== null) {

                $this->validarIdLista(
                    $filialId,
                    $this->model
                        ->listarFiliais(
                            $empresaId
                        ),
                    'Filial'
                );
            }


            return [

                'success' => true,

                'message' =>
                'Departamentos carregados com sucesso.',

                'data' =>
                $this->model
                    ->listarDepartamentos(
                        $empresaId,
                        $filialId
                    )
            ];
        } catch (Throwable $e) {

            return $this->respostaErro(
                $e,
                []
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CARGOS
    |--------------------------------------------------------------------------
    */

    public function cargos(
        int $empresaId,
        array $dados = []
    ): array {

        try {

            $this->validarEmpresa(
                $empresaId
            );


            $departamentoId =
                $this->inteiroOpcional(
                    $dados['departamento_id']
                        ?? null,
                    'Departamento'
                );


            if ($departamentoId !== null) {

                $this->validarIdLista(
                    $departamentoId,
                    $this->model
                        ->listarDepartamentos(
                            $empresaId
                        ),
                    'Departamento'
                );
            }


            return [

                'success' => true,

                'message' =>
                'Cargos carregados com sucesso.',

                'data' =>
                $this->model
                    ->listarCargos(
                        $empresaId,
                        $departamentoId
                    )
            ];
        } catch (Throwable $e) {

            return $this->respostaErro(
                $e,
                []
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FUNCIONÁRIOS PARA SELECT
    |--------------------------------------------------------------------------
    */

    public function funcionarios(
        int $empresaId,
        array $dados = []
    ): array {

        try {

            $this->validarEmpresa(
                $empresaId
            );


            $filtros =
                $this->prepararFiltros(
                    $dados
                );


            return [

                'success' => true,

                'message' =>
                'Funcionários carregados com sucesso.',

                'data' =>
                $this->model
                    ->listarFuncionariosFiltro(
                        $empresaId,
                        $filtros
                    )
            ];
        } catch (Throwable $e) {

            return $this->respostaErro(
                $e,
                []
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PERÍODOS SALARIAIS
    |--------------------------------------------------------------------------
    */

    public function periodos(
        int $empresaId
    ): array {

        try {

            $this->validarEmpresa(
                $empresaId
            );


            return [

                'success' => true,

                'message' =>
                'Períodos salariais carregados com sucesso.',

                'data' =>
                $this->model
                    ->listarPeriodos(
                        $empresaId
                    )
            ];
        } catch (Throwable $e) {

            return $this->respostaErro(
                $e,
                []
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | OPÇÕES
    |--------------------------------------------------------------------------
    |
    | Usamos isto para preencher selects fixos
    | sem deixar valores espalhados pelo JavaScript.
    |
    */

    public function opcoes(): array
    {
        return [

            'success' => true,

            'message' =>
            'Opções carregadas com sucesso.',

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | Assiduidade
                |--------------------------------------------------------------------------
                */

                'ocorrencias' => [

                    [
                        'valor' =>
                        'NORMAL',

                        'nome' =>
                        'Normal'
                    ],

                    [
                        'valor' =>
                        'ATRASO',

                        'nome' =>
                        'Atraso'
                    ],

                    [
                        'valor' =>
                        'SAIDA_ANTECIPADA',

                        'nome' =>
                        'Saída antecipada'
                    ],

                    [
                        'valor' =>
                        'FALTA_PARCIAL',

                        'nome' =>
                        'Falta parcial'
                    ],

                    [
                        'valor' =>
                        'FALTA_TOTAL',

                        'nome' =>
                        'Falta total'
                    ],

                    [
                        'valor' =>
                        'FERIAS',

                        'nome' =>
                        'Férias'
                    ],

                    [
                        'valor' =>
                        'LICENCA',

                        'nome' =>
                        'Licença'
                    ],

                    [
                        'valor' =>
                        'FERIADO',

                        'nome' =>
                        'Feriado'
                    ],

                    [
                        'valor' =>
                        'FOLGA',

                        'nome' =>
                        'Folga'
                    ],

                    [
                        'valor' =>
                        'INCOMPLETO',

                        'nome' =>
                        'Registo incompleto'
                    ]
                ],


                /*
                |--------------------------------------------------------------------------
                | Faltas
                |--------------------------------------------------------------------------
                */

                'tipos_falta' => [

                    [
                        'valor' =>
                        'DIA_INTEIRO',

                        'nome' =>
                        'Dia inteiro'
                    ],

                    [
                        'valor' =>
                        'PARCIAL',

                        'nome' =>
                        'Parcial'
                    ],

                    [
                        'valor' =>
                        'ATRASO',

                        'nome' =>
                        'Atraso'
                    ],

                    [
                        'valor' =>
                        'SAIDA_ANTECIPADA',

                        'nome' =>
                        'Saída antecipada'
                    ]
                ],


                /*
                |--------------------------------------------------------------------------
                | Horas extras
                |--------------------------------------------------------------------------
                */

                'tipos_hora_extra' => [

                    [
                        'valor' =>
                        'DIA_NORMAL',

                        'nome' =>
                        'Dia normal'
                    ],

                    [
                        'valor' =>
                        'NOCTURNA',

                        'nome' =>
                        'Nocturna'
                    ],

                    [
                        'valor' =>
                        'FERIADO',

                        'nome' =>
                        'Feriado'
                    ],

                    [
                        'valor' =>
                        'FOLGA',

                        'nome' =>
                        'Folga'
                    ],

                    [
                        'valor' =>
                        'FIM_SEMANA',

                        'nome' =>
                        'Fim de semana'
                    ]
                ],


                /*
                |--------------------------------------------------------------------------
                | Origem
                |--------------------------------------------------------------------------
                */

                'origens_falta' => [

                    [
                        'valor' =>
                        'AUTOMATICA',

                        'nome' =>
                        'Automática'
                    ],

                    [
                        'valor' =>
                        'MANUAL',

                        'nome' =>
                        'Manual'
                    ]
                ],


                'origens_hora_extra' => [

                    [
                        'valor' =>
                        'AUTOMATICA',

                        'nome' =>
                        'Automática'
                    ],

                    [
                        'valor' =>
                        'MANUAL',

                        'nome' =>
                        'Manual'
                    ],

                    [
                        'valor' =>
                        'SOLICITACAO',

                        'nome' =>
                        'Solicitação'
                    ]
                ],


                /*
                |--------------------------------------------------------------------------
                | Sexo
                |--------------------------------------------------------------------------
                */

                'sexos' => [

                    [
                        'valor' =>
                        'MASCULINO',

                        'nome' =>
                        'Masculino'
                    ],

                    [
                        'valor' =>
                        'FEMININO',

                        'nome' =>
                        'Feminino'
                    ],

                    [
                        'valor' =>
                        'OUTRO',

                        'nome' =>
                        'Outro'
                    ]
                ],


                /*
                |--------------------------------------------------------------------------
                | Tipo de vínculo
                |--------------------------------------------------------------------------
                */

                'tipos_vinculo' => [

                    [
                        'valor' =>
                        'EFECTIVO',

                        'nome' =>
                        'Efectivo'
                    ],

                    [
                        'valor' =>
                        'CONTRATADO',

                        'nome' =>
                        'Contratado'
                    ],

                    [
                        'valor' =>
                        'ESTAGIARIO',

                        'nome' =>
                        'Estagiário'
                    ],

                    [
                        'valor' =>
                        'TEMPORARIO',

                        'nome' =>
                        'Temporário'
                    ],

                    [
                        'valor' =>
                        'PRESTADOR_SERVICO',

                        'nome' =>
                        'Prestador de serviço'
                    ],

                    [
                        'valor' =>
                        'OUTRO',

                        'nome' =>
                        'Outro'
                    ]
                ]
            ]
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARAR FILTROS
    |--------------------------------------------------------------------------
    */

    private function prepararFiltros(
        array $dados
    ): array {

        $dataInicio =
            trim(
                (string)(
                    $dados['data_inicio']
                    ?? ''
                )
            );


        $dataFim =
            trim(
                (string)(
                    $dados['data_fim']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Datas
        |--------------------------------------------------------------------------
        */

        if (
            $dataInicio !== ''
            && !$this->dataValida(
                $dataInicio
            )
        ) {

            throw new InvalidArgumentException(
                'A data inicial é inválida.'
            );
        }


        if (
            $dataFim !== ''
            && !$this->dataValida(
                $dataFim
            )
        ) {

            throw new InvalidArgumentException(
                'A data final é inválida.'
            );
        }


        if (
            $dataInicio !== ''
            && $dataFim !== ''
            && $dataInicio > $dataFim
        ) {

            throw new InvalidArgumentException(
                'A data inicial não pode ser superior à data final.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | IDs
        |--------------------------------------------------------------------------
        */

        $filialId =
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null,
                'Filial'
            );


        $departamentoId =
            $this->inteiroOpcional(
                $dados['departamento_id']
                    ?? null,
                'Departamento'
            );


        $cargoId =
            $this->inteiroOpcional(
                $dados['cargo_id']
                    ?? null,
                'Cargo'
            );


        $funcionarioId =
            $this->inteiroOpcional(
                $dados['funcionario_id']
                    ?? null,
                'Funcionário'
            );


        $periodoId =
            $this->inteiroOpcional(
                $dados['periodo_id']
                    ?? null,
                'Período salarial'
            );


        /*
        |--------------------------------------------------------------------------
        | Strings
        |--------------------------------------------------------------------------
        */

        $ocorrencia =
            strtoupper(
                trim(
                    (string)(
                        $dados['ocorrencia']
                        ?? ''
                    )
                )
            );


        $tipoFalta =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_falta']
                        ?? ''
                    )
                )
            );


        $tipoHoraExtra =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_hora_extra']
                        ?? ''
                    )
                )
            );


        $origem =
            strtoupper(
                trim(
                    (string)(
                        $dados['origem']
                        ?? ''
                    )
                )
            );


        $sexo =
            strtoupper(
                trim(
                    (string)(
                        $dados['sexo']
                        ?? ''
                    )
                )
            );


        $tipoVinculo =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_vinculo']
                        ?? ''
                    )
                )
            );


        $pesquisa =
            trim(
                (string)(
                    $dados['pesquisa']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Validar ocorrência
        |--------------------------------------------------------------------------
        */

        if (
            $ocorrencia !== ''
            && !in_array(
                $ocorrencia,
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
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Ocorrência de assiduidade inválida.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validar falta
        |--------------------------------------------------------------------------
        */

        if (
            $tipoFalta !== ''
            && !in_array(
                $tipoFalta,
                [
                    'DIA_INTEIRO',
                    'PARCIAL',
                    'ATRASO',
                    'SAIDA_ANTECIPADA'
                ],
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Tipo de falta inválido.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validar hora extra
        |--------------------------------------------------------------------------
        */

        if (
            $tipoHoraExtra !== ''
            && !in_array(
                $tipoHoraExtra,
                [
                    'DIA_NORMAL',
                    'NOCTURNA',
                    'FERIADO',
                    'FOLGA',
                    'FIM_SEMANA'
                ],
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Tipo de hora extra inválido.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Origem
        |--------------------------------------------------------------------------
        */

        if (
            $origem !== ''
            && !in_array(
                $origem,
                [
                    'AUTOMATICA',
                    'MANUAL',
                    'SOLICITACAO'
                ],
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Origem inválida.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Sexo
        |--------------------------------------------------------------------------
        */

        if (
            $sexo !== ''
            && !in_array(
                $sexo,
                [
                    'MASCULINO',
                    'FEMININO',
                    'OUTRO'
                ],
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Sexo inválido.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Tipo vínculo
        |--------------------------------------------------------------------------
        */

        if (
            $tipoVinculo !== ''
            && !in_array(
                $tipoVinculo,
                [
                    'EFECTIVO',
                    'CONTRATADO',
                    'ESTAGIARIO',
                    'TEMPORARIO',
                    'PRESTADOR_SERVICO',
                    'OUTRO'
                ],
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Tipo de vínculo inválido.'
            );
        }


        return [

            'data_inicio' =>
            $dataInicio,

            'data_fim' =>
            $dataFim,

            'filial_id' =>
            $filialId,

            'departamento_id' =>
            $departamentoId,

            'cargo_id' =>
            $cargoId,

            'funcionario_id' =>
            $funcionarioId,

            'periodo_id' =>
            $periodoId,

            'ocorrencia' =>
            $ocorrencia,

            'tipo_falta' =>
            $tipoFalta,

            'tipo_hora_extra' =>
            $tipoHoraExtra,

            'origem' =>
            $origem,

            'sexo' =>
            $sexo,

            'tipo_vinculo' =>
            $tipoVinculo,

            'pesquisa' =>
            $pesquisa
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR EMPRESA
    |--------------------------------------------------------------------------
    */

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {

            throw new RuntimeException(
                'Empresa não identificada.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INTEIRO OPCIONAL
    |--------------------------------------------------------------------------
    */

    private function inteiroOpcional(
        mixed $valor,
        string $campo
    ): ?int {

        if (
            $valor === null
            || $valor === ''
        ) {

            return null;
        }


        if (
            !is_numeric(
                $valor
            )
        ) {

            throw new InvalidArgumentException(
                $campo
                    . ' possui um identificador inválido.'
            );
        }


        $valor =
            (int)$valor;


        if ($valor <= 0) {

            throw new InvalidArgumentException(
                $campo
                    . ' possui um identificador inválido.'
            );
        }


        return $valor;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR ID NUMA LISTA
    |--------------------------------------------------------------------------
    */

    private function validarIdLista(
        int $id,
        array $lista,
        string $campo
    ): void {

        foreach ($lista as $item) {

            if (
                isset($item['id'])
                && (int)$item['id']
                === $id
            ) {

                return;
            }
        }


        throw new InvalidArgumentException(
            $campo
                . ' não pertence à empresa.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR DATA
    |--------------------------------------------------------------------------
    */

    private function dataValida(
        string $data
    ): bool {

        $objeto =
            DateTime::createFromFormat(
                'Y-m-d',
                $data
            );


        return
            $objeto !== false
            && $objeto->format(
                'Y-m-d'
            ) === $data;
    }


    /*
    |--------------------------------------------------------------------------
    | RESPOSTA DE ERRO
    |--------------------------------------------------------------------------
    */

    private function respostaErro(
        Throwable $e,
        mixed $data = null
    ): array {

        error_log(
            sprintf(
                '[RelatorioRHController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );


        return [

            'success' => false,

            'message' =>
            $e->getMessage(),

            'data' =>
            $data
        ];
    }
}
