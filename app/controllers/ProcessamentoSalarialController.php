<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/ProcessamentoSalarial.php';

class ProcessamentoSalarialController
{
    private ProcessamentoSalarial $model;

    public function __construct(PDO $db)
    {
        $this->model =
            new ProcessamentoSalarial($db);
    }

    /*
    |--------------------------------------------------------------------------
    | Listar
    |--------------------------------------------------------------------------
    */

    public function listar(
        int $empresaId,
        array $filtros = []
    ): array {
        try {
            $this->validarEmpresa($empresaId);

            $dados =
                $this->model->listar(
                    $empresaId,
                    $filtros
                );

            return [
                'success' => true,
                'message' =>
                'Processamentos carregados com sucesso.',
                'data' => $dados
            ];
        } catch (Throwable $e) {
            return $this->respostaErro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar
    |--------------------------------------------------------------------------
    */

    public function buscar(
        int $id,
        int $empresaId
    ): array {
        try {
            $this->validarId($id);
            $this->validarEmpresa($empresaId);

            $processamento =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$processamento) {
                return [
                    'success' => false,
                    'message' =>
                    'Processamento salarial não encontrado.',
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' =>
                'Processamento carregado com sucesso.',
                'data' => $processamento
            ];
        } catch (Throwable $e) {
            return $this->respostaErro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Processar período
    |--------------------------------------------------------------------------
    */

    public function processarPeriodo(
        int $empresaId,
        int $periodoId,
        int $utilizadorId
    ): array {
        try {
            $this->validarEmpresa($empresaId);

            if ($periodoId <= 0) {
                throw new Exception(
                    'Período salarial não informado.'
                );
            }

            $this->validarUtilizador(
                $utilizadorId
            );

            return $this->model
                ->processarPeriodo(
                    $empresaId,
                    $periodoId,
                    $utilizadorId
                );
        } catch (Throwable $e) {
            return $this->respostaErro($e);
        }
    }

    public function recalcularPeriodo(
        int $empresaId,
        int $periodoId,
        int $utilizadorId
    ): array {

        try {

            $this->validarEmpresa(
                $empresaId
            );

            if ($periodoId <= 0) {
                throw new Exception(
                    'Período salarial não informado.'
                );
            }

            $this->validarUtilizador(
                $utilizadorId
            );

            return $this->model
                ->recalcularPeriodo(
                    $empresaId,
                    $periodoId,
                    $utilizadorId
                );
        } catch (Throwable $e) {

            return $this->respostaErro(
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Aprovar
    |--------------------------------------------------------------------------
    */

    public function aprovar(
        int $id,
        int $empresaId,
        int $utilizadorId
    ): array {
        try {
            $this->validarId($id);
            $this->validarEmpresa($empresaId);
            $this->validarUtilizador(
                $utilizadorId
            );

            return $this->model->aprovar(
                $id,
                $empresaId,
                $utilizadorId
            );
        } catch (Throwable $e) {
            return $this->respostaErro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelar
    |--------------------------------------------------------------------------
    */

    public function cancelar(
        int $id,
        int $empresaId,
        int $utilizadorId,
        string $motivo
    ): array {
        try {
            $this->validarId($id);
            $this->validarEmpresa($empresaId);
            $this->validarUtilizador(
                $utilizadorId
            );

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new Exception(
                    'Informe o motivo do cancelamento.'
                );
            }

            return $this->model->cancelar(
                $id,
                $empresaId,
                $utilizadorId,
                $motivo
            );
        } catch (Throwable $e) {
            return $this->respostaErro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

    public function resumo(
        int $empresaId,
        ?int $periodoId = null
    ): array {
        try {
            $this->validarEmpresa($empresaId);

            if (
                $periodoId !== null
                && $periodoId <= 0
            ) {
                $periodoId = null;
            }

            $resumo =
                $this->model->resumo(
                    $empresaId,
                    $periodoId
                );

            return [
                'success' => true,
                'message' =>
                'Resumo carregado com sucesso.',
                'data' => $resumo
            ];
        } catch (Throwable $e) {
            return $this->respostaErro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Listar itens
    |--------------------------------------------------------------------------
    */

    public function listarItens(
        int $processamentoId,
        int $empresaId
    ): array {
        try {
            $this->validarId(
                $processamentoId
            );

            $this->validarEmpresa(
                $empresaId
            );

            /*
             * Primeiro confirma se o processamento
             * pertence à empresa informada.
             */
            $processamento =
                $this->model->buscar(
                    $processamentoId,
                    $empresaId
                );

            if (!$processamento) {
                throw new Exception(
                    'Processamento salarial não encontrado.'
                );
            }

            return [
                'success' => true,
                'message' =>
                'Itens carregados com sucesso.',
                'data' =>
                $processamento['itens']
                    ?? []
            ];
        } catch (Throwable $e) {
            return $this->respostaErro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validações
    |--------------------------------------------------------------------------
    */

    private function validarId(
        int $id
    ): void {
        if ($id <= 0) {
            throw new Exception(
                'Registo inválido.'
            );
        }
    }

    private function validarEmpresa(
        int $empresaId
    ): void {
        if ($empresaId <= 0) {
            throw new Exception(
                'Empresa não informada.'
            );
        }
    }

    private function validarUtilizador(
        int $utilizadorId
    ): void {
        if ($utilizadorId <= 0) {
            throw new Exception(
                'Utilizador não identificado.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Resposta de erro
    |--------------------------------------------------------------------------
    */

    private function respostaErro(
        Throwable $e
    ): array {
        error_log(
            '[ProcessamentoSalarialController] '
                . $e->getMessage()
                . ' em '
                . $e->getFile()
                . ':'
                . $e->getLine()
        );

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => null
        ];
    }
}
