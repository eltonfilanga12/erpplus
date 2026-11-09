<?php

declare(strict_types=1);

final class RelatorioContabilisticoController
{
    private RelatorioContabilistico $model;


    public function __construct(PDO $db)
    {
        $this->model =
            new RelatorioContabilistico(
                $db
            );
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

            $this->validarEmpresa(
                $empresaId
            );


            /*
            |--------------------------------------------------------------------------
            | Tipo
            |--------------------------------------------------------------------------
            */

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
                    'Tipo de relatório contabilístico não informado.'
                );
            }


            $tiposPermitidos = [

                'DIARIO',

                'RAZAO',

                'BALANCETE',

                'DRE',

                'BALANCO_PATRIMONIAL',

                'IVA'
            ];


            if (
                !in_array(
                    $tipo,
                    $tiposPermitidos,
                    true
                )
            ) {

                throw new InvalidArgumentException(
                    'Tipo de relatório contabilístico inválido.'
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
            | Regras específicas
            |--------------------------------------------------------------------------
            */

            if (
                $tipo === 'RAZAO'
                && empty($filtros['conta_id'])
            ) {

                throw new InvalidArgumentException(
                    'Selecione uma conta contabilística para gerar o Razão.'
                );
            }

            if (
                $tipo === 'BALANCO_PATRIMONIAL'
                && empty($filtros['data_fim'])
            ) {

                throw new InvalidArgumentException(
                    'Informe a data de referência do Balanço Patrimonial.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Executar
            |--------------------------------------------------------------------------
            */

            $resultado =
                match ($tipo) {

                    'DIARIO' =>
                    $this->model
                        ->diario(
                            $empresaId,
                            $filtros
                        ),

                    'RAZAO' =>
                    $this->model
                        ->razao(
                            $empresaId,
                            $filtros
                        ),

                    'BALANCETE' =>
                    $this->model
                        ->balancete(
                            $empresaId,
                            $filtros
                        ),

                    'DRE' =>
                    $this->model
                        ->dre(
                            $empresaId,
                            $filtros
                        ),

                    'BALANCO_PATRIMONIAL' =>
                    $this->model
                        ->balancoPatrimonial(
                            $empresaId,
                            $filtros
                        ),

                    'IVA' =>
                    $this->model
                        ->iva(
                            $empresaId,
                            $filtros
                        ),

                    default =>
                    throw new InvalidArgumentException(
                        'Tipo de relatório contabilístico inválido.'
                    )
                };


            return [

                'success' => true,

                'message' =>
                'Relatório contabilístico carregado com sucesso.',

                'data' =>
                $resultado
            ];
        } catch (Throwable $e) {

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
    | PERÍODOS
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
                'Períodos contabilísticos carregados.',

                'data' =>
                $this->model
                    ->listarPeriodos(
                        $empresaId
                    )
            ];
        } catch (Throwable $e) {

            return [

                'success' => false,

                'message' =>
                $e->getMessage(),

                'data' => []
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DIÁRIOS
    |--------------------------------------------------------------------------
    */

    public function diarios(
        int $empresaId
    ): array {

        try {

            $this->validarEmpresa(
                $empresaId
            );


            return [

                'success' => true,

                'message' =>
                'Diários contabilísticos carregados.',

                'data' =>
                $this->model
                    ->listarDiarios(
                        $empresaId
                    )
            ];
        } catch (Throwable $e) {

            return [

                'success' => false,

                'message' =>
                $e->getMessage(),

                'data' => []
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CONTAS
    |--------------------------------------------------------------------------
    */

    public function contas(
        int $empresaId
    ): array {

        try {

            $this->validarEmpresa(
                $empresaId
            );


            return [

                'success' => true,

                'message' =>
                'Contas contabilísticas carregadas.',

                'data' =>
                $this->model
                    ->listarContas(
                        $empresaId
                    )
            ];
        } catch (Throwable $e) {

            return [

                'success' => false,

                'message' =>
                $e->getMessage(),

                'data' => []
            ];
        }
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
                'Filiais carregadas.',

                'data' =>
                $this->model
                    ->listarFiliais(
                        $empresaId
                    )
            ];
        } catch (Throwable $e) {

            return [

                'success' => false,

                'message' =>
                $e->getMessage(),

                'data' => []
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARAR FILTROS
    |--------------------------------------------------------------------------
    */

    private function prepararFiltros(
        array $dados
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Datas
        |--------------------------------------------------------------------------
        */

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
        | IDs opcionais
        |--------------------------------------------------------------------------
        */

        $periodoId =
            $this->inteiroOpcional(
                $dados['periodo_id']
                    ?? null,
                'Período'
            );


        $diarioId =
            $this->inteiroOpcional(
                $dados['diario_id']
                    ?? null,
                'Diário'
            );


        $contaId =
            $this->inteiroOpcional(
                $dados['conta_id']
                    ?? null,
                'Conta'
            );


        $filialId =
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null,
                'Filial'
            );


        /*
        |--------------------------------------------------------------------------
        | Pesquisa
        |--------------------------------------------------------------------------
        */

        $pesquisa =
            trim(
                (string)(
                    $dados['pesquisa']
                    ?? ''
                )
            );


        return [

            'data_inicio' =>
            $dataInicio,

            'data_fim' =>
            $dataFim,

            'periodo_id' =>
            $periodoId,

            'diario_id' =>
            $diarioId,

            'conta_id' =>
            $contaId,

            'filial_id' =>
            $filialId,

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
        string $nomeCampo
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
                $nomeCampo
                    . ' inválido.'
            );
        }


        $valor =
            (int)$valor;


        if ($valor <= 0) {

            throw new InvalidArgumentException(
                $nomeCampo
                    . ' inválido.'
            );
        }


        return $valor;
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


        return $objeto !== false
            && $objeto->format(
                'Y-m-d'
            ) === $data;
    }
}
