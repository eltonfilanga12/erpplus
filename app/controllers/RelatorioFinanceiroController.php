<?php

declare(strict_types=1);

final class RelatorioFinanceiroController
{
    private RelatorioFinanceiro $model;


    public function __construct(PDO $db)
    {
        $this->model =
            new RelatorioFinanceiro(
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


            $tiposPermitidos = [
                'FLUXO_CAIXA',
                'RECEITAS_DESPESAS',
                'CONTAS_RECEBER',
                'CONTAS_PAGAR',
                'CENTROS_CUSTO',
                'MOVIMENTACAO_BANCARIA'
            ];


            if (
                !in_array(
                    $tipo,
                    $tiposPermitidos,
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'Tipo de relatório financeiro inválido.'
                );
            }


            $filtros =
                $this->prepararFiltros(
                    $dados
                );


            $resultado =
                match ($tipo) {

                    'FLUXO_CAIXA' =>
                    $this->model
                        ->fluxoCaixa(
                            $empresaId,
                            $filtros
                        ),

                    'RECEITAS_DESPESAS' =>
                    $this->model
                        ->receitasDespesas(
                            $empresaId,
                            $filtros
                        ),

                    'CONTAS_RECEBER' =>
                    $this->model
                        ->contasReceber(
                            $empresaId,
                            $filtros
                        ),

                    'CONTAS_PAGAR' =>
                    $this->model
                        ->contasPagar(
                            $empresaId,
                            $filtros
                        ),

                    'CENTROS_CUSTO' =>
                    $this->model
                        ->centrosCusto(
                            $empresaId,
                            $filtros
                        ),

                    'MOVIMENTACAO_BANCARIA' =>
                    $this->model
                        ->movimentacaoBancaria(
                            $empresaId,
                            $filtros
                        ),

                    default =>
                    throw new RuntimeException(
                        'Tipo de relatório financeiro inválido.'
                    )
                };


            return [
                'success' => true,
                'message' =>
                'Relatório financeiro carregado com sucesso.',
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


            $stmt =
                Database::conn()
                ->prepare("
                        SELECT
                            id,
                            nome

                        FROM filiais

                        WHERE empresa_id = :empresa_id

                        ORDER BY nome ASC
                    ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId
            ]);


            return [
                'success' => true,
                'message' =>
                'Filiais carregadas.',
                'data' =>
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
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
    | BANCOS
    |--------------------------------------------------------------------------
    */

    public function bancos(
        int $empresaId
    ): array {
        try {

            $this->validarEmpresa(
                $empresaId
            );


            $stmt =
                Database::conn()
                ->prepare("
                        SELECT
                            id,

                            nome_banco,

                            conta,

                            moeda

                        FROM bancos

                        WHERE empresa_id = :empresa_id

                        ORDER BY
                            nome_banco ASC,
                            conta ASC
                    ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId
            ]);


            return [
                'success' => true,
                'message' =>
                'Contas bancárias carregadas.',
                'data' =>
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
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
    | CENTROS DE CUSTO
    |--------------------------------------------------------------------------
    */

    public function centrosCustoDisponiveis(
        int $empresaId
    ): array {
        try {

            $this->validarEmpresa(
                $empresaId
            );


            $stmt =
                Database::conn()
                ->prepare("
                        SELECT
                            id,
                            codigo,
                            nome,
                            tipo

                        FROM centros_custo

                        WHERE empresa_id = :empresa_id

                        ORDER BY
                            codigo ASC,
                            nome ASC
                    ");


            $stmt->execute([
                ':empresa_id' =>
                $empresaId
            ]);


            return [
                'success' => true,
                'message' =>
                'Centros de custo carregados.',
                'data' =>
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
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
    | FILTROS
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
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            );


        $bancoId =
            $this->inteiroOpcional(
                $dados['banco_id']
                    ?? null
            );


        $centroCustoId =
            $this->inteiroOpcional(
                $dados['centro_custo_id']
                    ?? null
            );


        $estado =
            trim(
                (string)(
                    $dados['estado']
                    ?? ''
                )
            );


        $tipoMovimento =
            strtoupper(
                trim(
                    (string)(
                        $dados['tipo_movimento']
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
        | Tipo de movimento
        |--------------------------------------------------------------------------
        */

        if (
            $tipoMovimento !== ''
            && !in_array(
                $tipoMovimento,
                [
                    'ENTRADA',
                    'SAIDA'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Tipo de movimento inválido.'
            );
        }


        return [

            'data_inicio' =>
            $dataInicio,

            'data_fim' =>
            $dataFim,

            'filial_id' =>
            $filialId,

            'banco_id' =>
            $bancoId,

            'centro_custo_id' =>
            $centroCustoId,

            'estado' =>
            $estado,

            'tipo_movimento' =>
            $tipoMovimento,

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
    | DATA
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


    /*
    |--------------------------------------------------------------------------
    | INTEIRO OPCIONAL
    |--------------------------------------------------------------------------
    */

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }


        if (!is_numeric($valor)) {

            throw new InvalidArgumentException(
                'Identificador inválido.'
            );
        }


        $valor =
            (int)$valor;


        if ($valor <= 0) {

            throw new InvalidArgumentException(
                'Identificador inválido.'
            );
        }


        return $valor;
    }

    /*
|--------------------------------------------------------------------------
| ESTADOS
|--------------------------------------------------------------------------
*/

    public function estados(
        int $empresaId,
        array $dados
    ): array {
        try {

            $this->validarEmpresa(
                $empresaId
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


            $tipoStatus =
                match ($tipo) {

                    'CONTAS_RECEBER' =>
                    'conta_receber',

                    'CONTAS_PAGAR' =>
                    'conta_pagar',

                    default =>
                    null
                };


            if ($tipoStatus === null) {

                return [
                    'success' => true,
                    'message' =>
                    'Nenhum estado aplicável.',
                    'data' => []
                ];
            }


            $stmt =
                Database::conn()
                ->prepare("
                    SELECT
                        id,
                        nome

                    FROM status

                    WHERE LOWER(tipo) =
                        LOWER(:tipo)

                    ORDER BY
                        nome ASC
                ");


            $stmt->execute([
                ':tipo' =>
                $tipoStatus
            ]);


            return [
                'success' => true,
                'message' =>
                'Estados carregados.',
                'data' =>
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
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
}
