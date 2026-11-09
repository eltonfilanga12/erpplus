<?php

declare(strict_types=1);

final class ObrigacaoFiscalController
{
    public function __construct(private ObrigacaoFiscal $model) {}

    public function dadosIniciais(int $empresaId, ?int $filialId): array
    {
        return $this->responder(fn() => $this->model->dadosIniciais($empresaId, $filialId));
    }

    public function listar(int $empresaId, ?int $filialId, array $dados): array
    {
        $filialSelecionada = $filialId;
        if ($filialSelecionada === null && !empty($dados['filial_id'])) {
            $filialSelecionada = (int)$dados['filial_id'];
        }

        return $this->responder(fn() => $this->model->listar(
            $empresaId,
            $filialSelecionada,
            strtoupper(trim((string)($dados['tipo'] ?? 'INSS'))),
            (int)($dados['ano'] ?? date('Y'))
        ));
    }

    public function listarPagamentos(int $empresaId, ?int $filialId, array $dados): array
    {
        $folhaId = (int)($dados['folha_id'] ?? 0);
        $tipo = strtoupper(trim((string)($dados['tipo'] ?? 'INSS')));

        return $this->responder(
            fn() => $this->model->listarPagamentos($empresaId, $filialId, $folhaId, $tipo)
        );
    }

    public function pagar(int $empresaId, ?int $filialId, int $usuarioId, array $dados): array
    {
        $filialSelecionada = $filialId;
        if ($filialSelecionada === null && !empty($dados['filial_id'])) {
            $filialSelecionada = (int)$dados['filial_id'];
        }

        return $this->responder(
            fn() => ['id' => $this->model->pagar($empresaId, $filialSelecionada, $usuarioId, $dados)],
            'Pagamento registado com sucesso.'
        );
    }

    private function responder(callable $acao, string $mensagem = 'Dados carregados com sucesso.'): array
    {
        try {
            return ['success' => true, 'message' => $mensagem, 'data' => $acao()];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }
}
