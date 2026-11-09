<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Log.php';

final class LogController
{
    private PDO $db;

    private Log $model;


    public function __construct()
    {
        $this->db =
            Database::conn();

        $this->model =
            new Log(
                $this->db
            );
    }


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

                'acao_log' =>
                $this->textoOpcional(
                    $_GET['acao_log']
                        ?? $_POST['acao_log']
                        ?? null,
                    50
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
                ),

                'pesquisa' =>
                $this->textoOpcional(
                    $_GET['pesquisa']
                        ?? $_POST['pesquisa']
                        ?? null,
                    200
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
                ),

                'debug' => [
                    'empresa_id' => $empresaId,
                    'filtros' => $filtros
                ]
            ];
        } catch (Throwable $e) {
            return $this->erro(
                $e
            );
        }
    }


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

                    'acoes' =>
                    $this->model
                        ->listarAcoes(
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
                'Utilizador inválido.'
            );
        }

        return (int)$numero;
    }


    private function textoOpcional(
        mixed $valor,
        int $limite
    ): ?string {
        $texto =
            trim(
                (string)$valor
            );

        if ($texto === '') {
            return null;
        }

        if (
            mb_strlen(
                $texto
            ) > $limite
        ) {
            throw new InvalidArgumentException(
                'O valor informado excede o limite permitido.'
            );
        }

        return $texto;
    }


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


    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[LogController] %s em %s:%d',
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
                : 'Ocorreu um erro interno ao consultar os logs.'
        ];
    }
}
