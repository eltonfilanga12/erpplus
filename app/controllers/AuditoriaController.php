<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Auditoria.php';


final class AuditoriaController
{
    private PDO $db;

    private Auditoria $model;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new Auditoria(
                $this->db
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Listar
    |--------------------------------------------------------------------------
    */

    public function listar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $filtros = [

                'usuario_id' =>
                $this->inteiroOpcional(
                    $_GET['usuario_id']
                        ?? $_POST['usuario_id']
                        ?? null
                ),


                'operacao' =>
                $this->operacaoOpcional(
                    $_GET['operacao']
                        ?? $_POST['operacao']
                        ?? null
                ),


                'data_inicial' =>
                $this->dataOpcional(
                    $_GET['data_inicial']
                        ?? $_POST['data_inicial']
                        ?? null
                ),


                'data_final' =>
                $this->dataOpcional(
                    $_GET['data_final']
                        ?? $_POST['data_final']
                        ?? null
                )
            ];


            if (
                $filtros['data_inicial']
                !== null
                && $filtros['data_final']
                !== null
                && $filtros['data_inicial']
                > $filtros['data_final']
            ) {
                throw new InvalidArgumentException(
                    'A data inicial não pode ser superior à data final.'
                );
            }


            return [
                'sucesso' => true,

                'dados' =>
                $this->model->listar(
                    $empresaId,
                    $filtros
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
    | Buscar detalhe
    |--------------------------------------------------------------------------
    */

    public function buscar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $id =
                $this->inteiroObrigatorio(
                    $_GET['id']
                        ?? $_POST['id']
                        ?? null,
                    'Auditoria inválida.'
                );


            $auditoria =
                $this->model->buscar(
                    $id,
                    $empresaId
                );


            if (!$auditoria) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Registo de auditoria não encontrado.'
                ];
            }


            /*
             * Transformamos os JSON em arrays
             * antes de enviar ao JavaScript.
             */

            $auditoria['dados_anteriores'] =
                $this->decodificarJson(
                    $auditoria['dados_anteriores'] ?? null
                );


            $auditoria['dados_novos'] =
                $this->decodificarJson(
                    $auditoria['dados_novos'] ?? null
                );


            return [
                'sucesso' => true,
                'dados' => $auditoria
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Dados dos filtros
    |--------------------------------------------------------------------------
    */

    public function filtros(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            return [
                'sucesso' => true,

                'dados' => [

                    'utilizadores' =>
                    $this->model
                        ->listarUtilizadores(
                            $empresaId
                        ),

                    'operacoes' => [
                        'INSERT',
                        'UPDATE',
                        'DELETE'
                    ]
                ]
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Empresa
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
    | Inteiro obrigatório
    |--------------------------------------------------------------------------
    */

    private function inteiroObrigatorio(
        mixed $valor,
        string $mensagem
    ): int {
        $numero =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );


        if (
            $numero === false
            || $numero <= 0
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }


        return (int)$numero;
    }


    /*
    |--------------------------------------------------------------------------
    | Inteiro opcional
    |--------------------------------------------------------------------------
    */

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


        return $this->inteiroObrigatorio(
            $valor,
            'Utilizador inválido.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Operação
    |--------------------------------------------------------------------------
    */

    private function operacaoOpcional(
        mixed $valor
    ): ?string {
        $operacao =
            strtoupper(
                trim(
                    (string)$valor
                )
            );


        if ($operacao === '') {
            return null;
        }


        if (
            !in_array(
                $operacao,
                [
                    'INSERT',
                    'UPDATE',
                    'DELETE'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Operação de auditoria inválida.'
            );
        }


        return $operacao;
    }


    /*
    |--------------------------------------------------------------------------
    | Data
    |--------------------------------------------------------------------------
    */

    private function dataOpcional(
        mixed $valor
    ): ?string {
        $texto =
            trim(
                (string)$valor
            );


        if ($texto === '') {
            return null;
        }


        $data =
            DateTime::createFromFormat(
                'Y-m-d',
                $texto
            );


        if (
            !$data
            || $data->format(
                'Y-m-d'
            ) !== $texto
        ) {
            throw new InvalidArgumentException(
                'Informe uma data válida.'
            );
        }


        return $texto;
    }


    /*
    |--------------------------------------------------------------------------
    | JSON
    |--------------------------------------------------------------------------
    */

    private function decodificarJson(
        mixed $valor
    ): array {
        if (
            $valor === null
            || trim(
                (string)$valor
            ) === ''
        ) {
            return [];
        }


        try {

            $dados =
                json_decode(
                    (string)$valor,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );


            return is_array(
                $dados
            )
                ? $dados
                : [];
        } catch (
            JsonException $e
        ) {

            error_log(
                sprintf(
                    '[AuditoriaController] JSON inválido: %s',
                    $e->getMessage()
                )
            );


            return [];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Erro
    |--------------------------------------------------------------------------
    */

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[AuditoriaController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );


        return [
            'sucesso' => false,

            'mensagem' =>
            $e instanceof
                InvalidArgumentException
                || $e instanceof
                RuntimeException

                ? $e->getMessage()

                : 'Ocorreu um erro interno ao consultar a auditoria.'
        ];
    }
}
