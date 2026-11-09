<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Stock.php';


class StockController
{
    private Stock $model;


    public function __construct()
    {
        $this->model =
            new Stock();
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR
    |--------------------------------------------------------------------------
    */

    public function listar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $armazemId =
                $this->inteiroOpcional(
                    $_GET['armazem_id']
                        ?? $_POST['armazem_id']
                        ?? null
                );


            $categoriaId =
                $this->inteiroOpcional(
                    $_GET['categoria_id']
                        ?? $_POST['categoria_id']
                        ?? null
                );


            $situacao =
                strtoupper(
                    trim(
                        (string)(
                            $_GET['situacao']
                            ?? $_POST['situacao']
                            ?? ''
                        )
                    )
                );


            if ($situacao === '') {
                $situacao = null;
            }


            $situacoesPermitidas = [
                'NORMAL',
                'STOCK_BAIXO',
                'SEM_STOCK'
            ];


            if (
                $situacao !== null
                && !in_array(
                    $situacao,
                    $situacoesPermitidas,
                    true
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Situação do stock inválida.'
                ];
            }


            if (
                $armazemId !== null
                && !$this->model
                    ->armazemPertenceEmpresa(
                        $armazemId,
                        $empresaId
                    )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O armazém selecionado é inválido.'
                ];
            }


            if (
                $categoriaId !== null
                && !$this->model
                    ->categoriaPertenceEmpresa(
                        $categoriaId,
                        $empresaId
                    )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A categoria selecionada é inválida.'
                ];
            }


            return [
                'sucesso' => true,

                'dados' =>
                $this->model->listar(
                    $empresaId,
                    $armazemId,
                    $categoriaId,
                    $situacao
                )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | RESUMO
    |--------------------------------------------------------------------------
    */

    public function resumo(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->resumo(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ARMAZÉNS
    |--------------------------------------------------------------------------
    */

    public function armazens(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->armazens(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORIAS
    |--------------------------------------------------------------------------
    */

    public function categorias(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->categorias(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | EMPRESA DA SESSÃO
    |--------------------------------------------------------------------------
    */

    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;


        if (
            $empresaId === null
            || !is_numeric(
                $empresaId
            )
            || (int)$empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }


        return (int)$empresaId;
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


        if (
            !is_numeric(
                $valor
            )
        ) {
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
    | ERRO
    |--------------------------------------------------------------------------
    */

    private function erro(
        Throwable $e
    ): array {

        error_log(
            sprintf(
                '[StockController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );


        return [
            'sucesso' => false,
            'mensagem' =>
            $e->getMessage()
        ];
    }
}
