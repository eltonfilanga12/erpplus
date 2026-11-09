<?php

declare(strict_types=1);

final class RubricaSalarialContaController
{
    private RubricaSalarialConta $model;

    public function __construct()
    {
        $this->model =
            new RubricaSalarialConta(
                Database::conn()
            );
    }

    public function listar(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listar(
                    $this->empresaId()
                )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function contas(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listarContas(
                    $this->empresaId()
                )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function status(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listarStatus()
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function guardar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();

            $dados =
                $this->validarMapeamento(
                    $_POST,
                    $empresaId
                );

            $id =
                $this->model->guardar(
                    $empresaId,
                    $dados['rubrica_salarial_id'],
                    $usuarioId,
                    $dados
                );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Mapeamento contabilístico guardado com sucesso.',
                'dados' => [
                    'id' => $id
                ]
            ];
        } catch (PDOException $e) {
            return $this->erroBanco($e);
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function guardarTodos(
        array $payload
    ): array {
        try {
            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();

            $itens =
                $payload['itens']
                ?? null;

            if (
                !is_array($itens)
                || $itens === []
            ) {
                throw new InvalidArgumentException(
                    'Nenhum mapeamento foi enviado.'
                );
            }

            $this->model
                ->iniciarTransacao();

            $quantidade = 0;

            foreach ($itens as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $dados =
                    $this->validarMapeamento(
                        $item,
                        $empresaId
                    );

                $this->model->guardar(
                    $empresaId,
                    $dados['rubrica_salarial_id'],
                    $usuarioId,
                    $dados
                );

                $quantidade++;
            }

            $this->model
                ->confirmarTransacao();

            return [
                'sucesso' => true,
                'mensagem' =>
                $quantidade
                    . ' mapeamento(s) guardado(s) com sucesso.',
                'dados' => [
                    'quantidade' => $quantidade
                ]
            ];
        } catch (Throwable $e) {
            $this->model
                ->cancelarTransacao();

            return $this->erro($e);
        }
    }

    public function eliminar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $rubricaId =
                $this->inteiroObrigatorio(
                    $_POST['rubrica_salarial_id']
                        ?? null,
                    'Rubrica inválida.'
                );

            if (
                !$this->model
                    ->rubricaPertenceEmpresa(
                        $rubricaId,
                        $empresaId
                    )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A rubrica informada não pertence à empresa.'
                ];
            }

            $this->model->eliminar(
                $empresaId,
                $rubricaId
            );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Mapeamento contabilístico removido com sucesso.'
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    private function validarMapeamento(
        array $dados,
        int $empresaId
    ): array {
        $rubricaId =
            $this->inteiroObrigatorio(
                $dados['rubrica_salarial_id']
                    ?? null,
                'Rubrica inválida.'
            );

        if (
            !$this->model
                ->rubricaPertenceEmpresa(
                    $rubricaId,
                    $empresaId
                )
        ) {
            throw new InvalidArgumentException(
                'A rubrica informada não pertence à empresa.'
            );
        }

        $rubrica =
            $this->model->obterRubrica(
                $rubricaId,
                $empresaId
            );

        if (!$rubrica) {
            throw new InvalidArgumentException(
                'Rubrica salarial não encontrada.'
            );
        }

        $tipoRubrica =
            strtoupper(
                trim(
                    (string)$rubrica['tipo']
                )
            );

        $contaDebitoId =
            $this->inteiroOpcional(
                $dados['conta_debito_id']
                    ?? null
            );

        $contaCreditoId =
            $this->inteiroOpcional(
                $dados['conta_credito_id']
                    ?? null
            );

        $usarSalariosPagar =
            $this->checkbox(
                $dados['usar_conta_salarios_pagar']
                    ?? null
            );

        $centroCustoObrigatorio =
            $this->checkbox(
                $dados['centro_custo_obrigatorio']
                    ?? null
            );

        $statusId =
            $this->inteiroObrigatorio(
                $dados['status_id']
                    ?? null,
                'Selecione o estado do mapeamento.'
            );

        if (
            !$this->model
                ->statusExiste($statusId)
        ) {
            throw new InvalidArgumentException(
                'O estado selecionado é inválido.'
            );
        }

        foreach (
            [$contaDebitoId, $contaCreditoId]
            as $contaId
        ) {
            if ($contaId === null) {
                continue;
            }

            if (
                !$this->model
                    ->contaPertenceEmpresa(
                        $contaId,
                        $empresaId
                    )
            ) {
                throw new InvalidArgumentException(
                    'Uma das contas selecionadas não pertence à empresa.'
                );
            }
        }

        if (
            $contaDebitoId === null
            && $contaCreditoId === null
            && $usarSalariosPagar === 0
        ) {
            throw new InvalidArgumentException(
                'Configure pelo menos uma conta ou marque a utilização da conta de salários a pagar.'
            );
        }

        /*
|--------------------------------------------------------------------------
| Regras contabilísticas por tipo
|--------------------------------------------------------------------------
*/

        if ($tipoRubrica === 'PROVENTO') {

            if ($contaDebitoId === null) {
                throw new InvalidArgumentException(
                    'Para uma rubrica de provento, selecione a conta de débito.'
                );
            }

            if (
                $usarSalariosPagar === 0
                && $contaCreditoId === null
            ) {
                throw new InvalidArgumentException(
                    'Selecione a conta de crédito ou marque Salários a pagar.'
                );
            }
        }


        if ($tipoRubrica === 'DESCONTO') {

            if ($contaCreditoId === null) {
                throw new InvalidArgumentException(
                    'Para uma rubrica de desconto, selecione a conta de crédito.'
                );
            }

            if (
                $usarSalariosPagar === 0
                && $contaDebitoId === null
            ) {
                throw new InvalidArgumentException(
                    'Selecione a conta de débito ou marque Salários a pagar.'
                );
            }
        }

        return [
            'rubrica_salarial_id' =>
            $rubricaId,

            'conta_debito_id' =>
            $contaDebitoId,

            'conta_credito_id' =>
            $contaCreditoId,

            'usar_conta_salarios_pagar' =>
            $usarSalariosPagar,

            'centro_custo_obrigatorio' =>
            $centroCustoObrigatorio,

            'status_id' =>
            $statusId
        ];
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
            || (int) $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }

        return (int) $empresaId;
    }

    private function usuarioId(): int
    {
        $usuarioId =
            $_SESSION['usuario_id']
            ?? $_SESSION['usuario']['id']
            ?? $_SESSION['user']['id']
            ?? null;

        if (
            $usuarioId === null
            || !is_numeric($usuarioId)
            || (int) $usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        return (int) $usuarioId;
    }

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

        return (int) $numero;
    }

    private function inteiroOpcional(
        mixed $valor
    ): ?int {
        if (
            $valor === null
            || trim((string) $valor) === ''
        ) {
            return null;
        }

        return $this->inteiroObrigatorio(
            $valor,
            'Conta contabilística inválida.'
        );
    }

    private function checkbox(
        mixed $valor
    ): int {
        return in_array(
            (string) $valor,
            ['1', 'true', 'on', 'yes'],
            true
        ) ? 1 : 0;
    }

    private function erroBanco(
        PDOException $e
    ): array {
        error_log(
            sprintf(
                '[RubricaSalarialContaController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        $mensagem =
            mb_strtolower(
                $e->getMessage()
            );

        if (
            str_contains(
                $mensagem,
                'foreign key constraint fails'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'A rubrica, conta ou estado selecionado é inválido.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível guardar o mapeamento contabilístico.'
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[RubricaSalarialContaController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        return [
            'sucesso' => false,
            'mensagem' =>
            $e instanceof InvalidArgumentException
                || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Ocorreu um erro interno ao processar o mapeamento.'
        ];
    }
}
