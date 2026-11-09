<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/IntegracaoFolhaSalarial.php';

class FolhaSalarialController
{
    private FolhaSalarial $model;

    private IntegracaoFolhaSalarial
        $integracaoContabilistica;

    public function __construct(PDO $db)
    {
        $this->model =
            new FolhaSalarial($db);

        $this->integracaoContabilistica =
            new IntegracaoFolhaSalarial();
    }

    public function listar(
        int $empresaId,
        array $dados
    ): array {
        $filtros = [
            'periodo_id' =>
            $dados['periodo_id'] ?? null,

            'estado' =>
            $dados['estado'] ?? null,

            'ano' =>
            $dados['ano'] ?? null,

            'pesquisa' =>
            $dados['pesquisa'] ?? null
        ];

        return [
            'success' => true,
            'message' =>
            'Folhas salariais carregadas.',
            'data' =>
            $this->model->listar(
                $empresaId,
                $filtros
            )
        ];
    }

    public function periodos(
    int $empresaId
): array {
    try {
        $periodos =
            $this->model
                ->listarPeriodosDisponiveis(
                    $empresaId
                );

        return [
            'success' => true,
            'message' =>
                'Períodos carregados com sucesso.',
            'data' => $periodos
        ];
    } catch (Throwable $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => []
        ];
    }
}

    public function buscar(
        int $empresaId,
        array $dados
    ): array {
        $id =
            (int)($dados['id'] ?? 0);

        $folha =
            $this->model->buscar(
                $id,
                $empresaId
            );

        if (!$folha) {
            throw new Exception(
                'Folha salarial não encontrada.'
            );
        }

        return [
            'success' => true,
            'message' =>
            'Folha salarial carregada.',
            'data' =>
            $folha
        ];
    }

    public function gerar(
        int $empresaId,
        int $utilizadorId,
        array $dados
    ): array {
        return $this->model->gerar(
            $empresaId,
            (int)($dados['periodo_id'] ?? 0),
            $utilizadorId,
            isset($dados['observacoes'])
                ? (string)$dados['observacoes']
                : null
        );
    }

    public function recalcular(
        int $empresaId,
        int $utilizadorId,
        array $dados
    ): array {
        return $this->model->recalcular(
            (int)($dados['id'] ?? 0),
            $empresaId,
            $utilizadorId
        );
    }

    public function fechar(
        int $empresaId,
        int $utilizadorId,
        array $dados
    ): array {
        return $this->model->fechar(
            (int)($dados['id'] ?? 0),
            $empresaId,
            $utilizadorId
        );
    }

    public function cancelar(
        int $empresaId,
        int $utilizadorId,
        array $dados
    ): array {
        return $this->model->cancelar(
            (int)($dados['id'] ?? 0),
            $empresaId,
            $utilizadorId,
            (string)($dados['motivo'] ?? '')
        );
    }

    public function funcionarios(
        int $empresaId,
        array $dados
    ): array {
        return [
            'success' => true,
            'message' =>
            'Funcionários carregados.',
            'data' =>
            $this->model
                ->listarFuncionarios(
                    (int)(
                        $dados['periodo_id']
                        ?? 0
                    ),
                    $empresaId
                )
        ];
    }

    public function resumo(
        int $empresaId
    ): array {
        return [
            'success' => true,
            'message' =>
            'Resumo carregado.',
            'data' =>
            $this->model->resumo(
                $empresaId
            )
        ];
    }

    public function contabilizar(
    int $empresaId,
    int $utilizadorId,
    array $dados
): array {
    try {
        $folhaId =
            (int)(
                $dados['id']
                ?? 0
            );

        if ($folhaId <= 0) {
            throw new Exception(
                'Folha salarial inválida.'
            );
        }

        $folha =
            $this->model->buscar(
                $folhaId,
                $empresaId
            );

        if (!$folha) {
            throw new Exception(
                'Folha salarial não encontrada.'
            );
        }

        if (
            strtoupper(
                trim(
                    (string)(
                        $folha['estado']
                        ?? ''
                    )
                )
            ) !== 'FECHADA'
        ) {
            throw new Exception(
                'Apenas folhas fechadas podem ser contabilizadas.'
            );
        }

        $resultado =
            $this
                ->integracaoContabilistica
                ->contabilizarFolha(
                    $folhaId,
                    $empresaId,
                    $utilizadorId > 0
                        ? $utilizadorId
                        : null
                );

        return [
            'success' => true,

            'message' =>
                $resultado['mensagem']
                ?? 'Folha salarial contabilizada com sucesso.',

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
}
