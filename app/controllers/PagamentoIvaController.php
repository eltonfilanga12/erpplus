<?php

declare(strict_types=1);

final class PagamentoIvaController
{
    private PagamentoIva $model;

    public function __construct(PDO $db)
    {
        $this->model = new PagamentoIva($db);
    }

    public function dadosIniciais(int $empresaId, ?int $filialId): array
    {
        return $this->executar(fn() => $this->model->dadosIniciais($empresaId, $filialId));
    }

    public function listar(int $empresaId, ?int $filialId): array
    {
        return $this->executar(fn() => $this->model->listar($empresaId, $filialId));
    }


    public function calcular(int $empresaId, ?int $filialId, array $dados): array
    {
        return $this->executar(function () use ($empresaId, $filialId, $dados) {
            $dataInicio = trim((string)($dados['data_inicio'] ?? ''));
            $dataFim = trim((string)($dados['data_fim'] ?? ''));

            if ($dataInicio === '' || $dataFim === '') {
                throw new InvalidArgumentException(
                    'Informe a data inicial e a data final do período.'
                );
            }

            if ($dataInicio > $dataFim) {
                throw new InvalidArgumentException(
                    'A data inicial não pode ser posterior à data final.'
                );
            }


            $filialEfetiva = $filialId;

            if ($filialEfetiva === null && isset($dados['filial_id'])) {
                $valorFilial = $dados['filial_id'];

                if ($valorFilial !== '' && $valorFilial !== null) {
                    $filialEfetiva = filter_var(
                        $valorFilial,
                        FILTER_VALIDATE_INT
                    );

                    if ($filialEfetiva === false || $filialEfetiva <= 0) {
                        throw new InvalidArgumentException(
                            'A filial selecionada é inválida.'
                        );
                    }
                }
            }

            return $this->model->calcular(
                $empresaId,
                $dataInicio,
                $dataFim,
                $filialId
            );
        });
    }


    public function apurar(
        int $empresaId,
        ?int $filialId,
        int $usuarioId,
        array $dados
    ): array {
        return $this->executar(function () use (
            $empresaId,
            $filialId,
            $usuarioId,
            $dados
        ) {
            $dataInicio = trim((string)($dados['data_inicio'] ?? ''));
            $dataFim = trim((string)($dados['data_fim'] ?? ''));

            if ($dataInicio === '' || $dataFim === '') {
                throw new InvalidArgumentException(
                    'Informe o período para apurar o IVA.'
                );
            }

            if ($dataInicio > $dataFim) {
                throw new InvalidArgumentException(
                    'A data inicial não pode ser posterior à data final.'
                );
            }
            

            // Preserva os dados recebidos, incluindo filial_id, se existir.
            $id = $this->model->apurar(
                $empresaId,
                $filialId,
                $usuarioId,
                $dados
            );

            return ['id' => $id];
        }, 'IVA apurado com sucesso.');
    }

    public function pagar(int $empresaId, ?int $filialId, int $usuarioId, array $dados): array
    {
        return $this->executar(function () use ($empresaId, $filialId, $usuarioId, $dados) {
            $id = $this->model->pagar($empresaId, $filialId, $usuarioId, $dados);
            return ['id' => $id];
        }, 'Pagamento do IVA registado com sucesso.');
    }

    public function anular(
        int $empresaId,
        ?int $filialId,
        array $dados
    ): array {
        return $this->executar(
            function () use (
                $empresaId,
                $filialId,
                $dados
            ) {
                $apuramentoId =
                    (int)(
                        $dados['apuramento_id']
                        ?? 0
                    );

                $this->model->anular(
                    $empresaId,
                    $filialId,
                    $apuramentoId
                );

                return [
                    'id' => $apuramentoId
                ];
            },
            'Apuramento do IVA anulado com sucesso.'
        );
    }

    public function pagamentos(int $empresaId, array $dados): array
    {
        return $this->executar(fn() => $this->model->pagamentos(
            $empresaId,
            (int)($dados['apuramento_id'] ?? 0)
        ));
    }

    private function executar(callable $acao, string $mensagem = 'Dados carregados com sucesso.'): array
    {
        try {
            return ['success' => true, 'message' => $mensagem, 'data' => $acao()];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }
}
