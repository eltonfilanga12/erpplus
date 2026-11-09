<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Dashboard.php';

final class DashboardController
{
    private Dashboard $model;


    public function __construct(
        PDO $db
    ) {
        $this->model =
            new Dashboard(
                $db
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CARREGAR DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function carregar(
        array $dados = []
    ): array {

        try {

            $empresaId =
                $this->empresaId();


            $filtros =
                $this->prepararFiltros(
                    $dados
                );


            $resultado =
                $this->model
                ->carregar(
                    $empresaId,
                    $filtros
                );


            return [
                'success' => true,

                'message' =>
                'Dashboard carregado com sucesso.',

                'data' =>
                $resultado
            ];
        } catch (Throwable $e) {

            return $this->respostaErro(
                $e
            );
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

        $periodo =
            strtolower(
                trim(
                    (string)(
                        $dados['periodo']
                        ?? 'mes'
                    )
                )
            );


        if (
            !in_array(
                $periodo,
                [
                    'mes',
                    'trimestre',
                    'ano'
                ],
                true
            )
        ) {

            $periodo =
                'mes';
        }


        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;


        $dataInicio =
            $this->normalizarData(
                $dados['data_inicio']
                    ?? null
            );


        $dataFim =
            $this->normalizarData(
                $dados['data_fim']
                    ?? null
            );


        if (
            $dataInicio !== null
            &&
            $dataFim !== null
            &&
            $dataInicio >
            $dataFim
        ) {

            throw new InvalidArgumentException(
                'A data inicial não pode ser superior à data final.'
            );
        }


        return [

            'periodo' =>
            $periodo,

            'filial_id' =>
            $filialId,

            'data_inicio' =>
            $dataInicio,

            'data_fim' =>
            $dataFim
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR DATA
    |--------------------------------------------------------------------------
    */

    private function normalizarData(
        mixed $valor
    ): ?string {

        if (
            $valor === null
            ||
            trim(
                (string)$valor
            ) === ''
        ) {

            return null;
        }


        $valor =
            trim(
                (string)$valor
            );


        $data =
            DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $valor
            );


        if (
            !$data
            ||
            $data->format(
                'Y-m-d'
            ) !== $valor
        ) {

            throw new InvalidArgumentException(
                'Data inválida.'
            );
        }


        return $valor;
    }


    /*
    |--------------------------------------------------------------------------
    | EMPRESA DA SESSÃO
    |--------------------------------------------------------------------------
    */

    private function empresaId(): int
    {

        $empresaId =
            (int)(
                $_SESSION['empresa_id']
                ?? 0
            );


        if (
            $empresaId <= 0
        ) {

            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }


        return $empresaId;
    }


    /*
    |--------------------------------------------------------------------------
    | RESPOSTA DE ERRO
    |--------------------------------------------------------------------------
    */

    private function respostaErro(
        Throwable $e
    ): array {

        error_log(
            '[DashboardController] '
                . $e->getMessage()
                . ' em '
                . $e->getFile()
                . ':'
                . $e->getLine()
        );


        return [

            'success' =>
            false,

            'message' =>
            $e->getMessage(),

            'data' =>
            null
        ];
    }
}
