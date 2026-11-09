<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/MovimentoStock.php';


class MovimentoStockController
{
    private MovimentoStock $model;


    public function __construct()
    {
        $this->model =
            new MovimentoStock();
    }


    public function listar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $tipo =
                strtoupper(
                    trim(
                        (string)(
                            $_GET['tipo']
                            ?? ''
                        )
                    )
                );


            if ($tipo === '') {
                $tipo = null;
            }


            $permitidos = [
                'ENTRADA',
                'SAIDA',
                'TRANSFERENCIA',
                'AJUSTE'
            ];


            if (
                $tipo !== null
                && !in_array(
                    $tipo,
                    $permitidos,
                    true
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Tipo de movimento inválido.'
                ];
            }


            $armazemId =
                $this->inteiroOpcional(
                    $_GET['armazem_id']
                        ?? null
                );


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
                    'Armazém inválido.'
                ];
            }


            $dataInicio =
                $this->dataOpcional(
                    $_GET['data_inicio']
                        ?? null
                );


            $dataFim =
                $this->dataOpcional(
                    $_GET['data_fim']
                        ?? null
                );


            if (
                $dataInicio !== null
                && $dataFim !== null
                && $dataInicio > $dataFim
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A data inicial não pode ser superior à data final.'
                ];
            }


            return [
                'sucesso' => true,

                'dados' =>
                $this->model->listar(
                    $empresaId,
                    $tipo,
                    $armazemId,
                    $dataInicio,
                    $dataFim
                )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    public function buscar(): array
    {
        try {

            $id =
                (int)(
                    $_GET['id']
                    ?? 0
                );


            if ($id <= 0) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Movimento inválido.'
                ];
            }


            $empresaId =
                $this->empresaId();


            $movimento =
                $this->model->buscar(
                    $id,
                    $empresaId
                );


            if (!$movimento) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Movimento não encontrado.'
                ];
            }


            return [
                'sucesso' => true,

                'dados' => [
                    'movimento' =>
                    $movimento,

                    'itens' =>
                    $this->model->itens(
                        $id,
                        $empresaId
                    )
                ]
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    public function resumo(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model->resumo(
                    $this->empresaId()
                )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    public function armazens(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model->armazens(
                    $this->empresaId()
                )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;


        if (
            $empresaId === null
            || !is_numeric($empresaId)
            || (int)$empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }


        return (int)$empresaId;
    }


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


        $valor = (int)$valor;


        if ($valor <= 0) {

            throw new InvalidArgumentException(
                'Identificador inválido.'
            );
        }


        return $valor;
    }


    private function dataOpcional(
        mixed $valor
    ): ?string {

        $valor =
            trim(
                (string)(
                    $valor
                    ?? ''
                )
            );


        if ($valor === '') {
            return null;
        }


        $data =
            DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $valor
            );


        if (
            !$data
            || $data->format('Y-m-d')
            !== $valor
        ) {
            throw new InvalidArgumentException(
                'Data inválida.'
            );
        }


        return $valor;
    }


    private function erro(
        Throwable $e
    ): array {

        error_log(
            sprintf(
                '[MovimentoStockController] %s em %s:%d',
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
