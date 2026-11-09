<?php

declare(strict_types=1);

final class RelatorioOperacionalController
{
    private RelatorioOperacional $model;

    public function __construct(PDO $db)
    {
        $this->model =
            new RelatorioOperacional($db);
    }


    /*
    |--------------------------------------------------------------------------
    | Relatório
    |--------------------------------------------------------------------------
    */

    public function relatorio(
        int $empresaId,
        array $dados
    ): array {
        try {
            if ($empresaId <= 0) {
                throw new Exception(
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
                throw new Exception(
                    'Tipo de relatório não informado.'
                );
            }


            $tiposPermitidos = [
                'VENDAS',
                'COMPRAS',
                'STOCK',
                'CLIENTES',
                'FORNECEDORES',
                'PRODUTOS'
            ];


            if (
                !in_array(
                    $tipo,
                    $tiposPermitidos,
                    true
                )
            ) {
                throw new Exception(
                    'Tipo de relatório inválido.'
                );
            }


            $filtros =
                $this->prepararFiltros(
                    $dados
                );


            $resultado =
                match ($tipo) {

                    'VENDAS' =>
                    $this->model->vendas(
                        $empresaId,
                        $filtros
                    ),

                    'COMPRAS' =>
                    $this->model->compras(
                        $empresaId,
                        $filtros
                    ),

                    'STOCK' =>
                    $this->model->stock(
                        $empresaId,
                        $filtros
                    ),

                    'CLIENTES' =>
                    $this->model->clientes(
                        $empresaId,
                        $filtros
                    ),

                    'FORNECEDORES' =>
                    $this->model->fornecedores(
                        $empresaId,
                        $filtros
                    ),

                    'PRODUTOS' =>
                    $this->model->produtos(
                        $empresaId,
                        $filtros
                    ),

                    default =>
                    throw new Exception(
                        'Tipo de relatório inválido.'
                    )
                };


            return [
                'success' => true,

                'message' =>
                'Relatório carregado com sucesso.',

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
    | Filiais
    |--------------------------------------------------------------------------
    */

    public function filiais(
        int $empresaId
    ): array {
        try {
            if ($empresaId <= 0) {
                throw new Exception(
                    'Empresa não identificada.'
                );
            }


            $filiais =
                $this->model
                ->listarFiliais(
                    $empresaId
                );


            return [
                'success' => true,

                'message' =>
                'Filiais carregadas com sucesso.',

                'data' =>
                $filiais
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
    | Preparar filtros
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


        $filialId =
            (int)(
                $dados['filial_id']
                ?? 0
            );


        $estado =
            trim(
                (string)(
                    $dados['estado']
                    ?? ''
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
        | Validar datas
        |--------------------------------------------------------------------------
        */

        if (
            $dataInicio !== ''
            && !$this->dataValida(
                $dataInicio
            )
        ) {
            throw new Exception(
                'A data inicial é inválida.'
            );
        }


        if (
            $dataFim !== ''
            && !$this->dataValida(
                $dataFim
            )
        ) {
            throw new Exception(
                'A data final é inválida.'
            );
        }


        if (
            $dataInicio !== ''
            && $dataFim !== ''
            && $dataInicio > $dataFim
        ) {
            throw new Exception(
                'A data inicial não pode ser superior à data final.'
            );
        }


        return [
            'data_inicio' =>
            $dataInicio,

            'data_fim' =>
            $dataFim,

            'filial_id' =>
            $filialId > 0
                ? $filialId
                : null,

            'estado' =>
            $estado,

            'pesquisa' =>
            $pesquisa
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Validar data
    |--------------------------------------------------------------------------
    */

    public function estados(
        int $empresaId,
        array $dados
    ): array {
        if ($empresaId <= 0) {
            return [
                'success' => false,
                'message' =>
                'Empresa não identificada.',
                'data' => []
            ];
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

        return [
            'success' => true,
            'message' =>
            'Estados carregados.',
            'data' =>
            $this->model
                ->listarEstados($tipo)
        ];
    }

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
