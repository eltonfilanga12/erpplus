<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/ReciboSalario.php';

final class ReciboSalarioController
{
    private ReciboSalario $model;

    public function __construct()
    {
        $this->model =
            new ReciboSalario();
    }

    /*
    |--------------------------------------------------------------------------
    | Listar recibos
    |--------------------------------------------------------------------------
    */
    public function listar(
        int $empresaId,
        array $dados = []
    ): array {
        try {
            $this->validarEmpresa($empresaId);

            $filtros = [
                'periodo_id' =>
                    $this->inteiroOpcional(
                        $dados['periodo_id'] ?? null
                    ),

                'folha_salarial_id' =>
                    $this->inteiroOpcional(
                        $dados[
                            'folha_salarial_id'
                        ] ?? null
                    ),

                'funcionario_id' =>
                    $this->inteiroOpcional(
                        $dados[
                            'funcionario_id'
                        ] ?? null
                    ),

                'estado' =>
                    trim(
                        (string) (
                            $dados['estado'] ?? ''
                        )
                    ),

                'pesquisa' =>
                    trim(
                        (string) (
                            $dados['pesquisa'] ?? ''
                        )
                    )
            ];

            $recibos =
                $this->model->listar(
                    $empresaId,
                    $filtros
                );

            return $this->sucesso(
                'Recibos carregados com sucesso.',
                $recibos
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar recibo
    |--------------------------------------------------------------------------
    */
    public function buscar(
        int $empresaId,
        array $dados
    ): array {
        try {
            $this->validarEmpresa($empresaId);

            $id =
                $this->inteiroObrigatorio(
                    $dados['id'] ?? null,
                    'Informe o recibo salarial.'
                );

            $recibo =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if ($recibo === null) {
                throw new RuntimeException(
                    'Recibo salarial não encontrado.'
                );
            }

            return $this->sucesso(
                'Recibo carregado com sucesso.',
                $recibo
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Listar períodos
    |--------------------------------------------------------------------------
    */
    public function periodos(
        int $empresaId
    ): array {
        try {
            $this->validarEmpresa($empresaId);

            $periodos =
                $this->model->listarPeriodos(
                    $empresaId
                );

            return $this->sucesso(
                'Períodos carregados com sucesso.',
                $periodos
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Listar folhas
    |--------------------------------------------------------------------------
    */
    public function folhas(
        int $empresaId,
        array $dados = []
    ): array {
        try {
            $this->validarEmpresa($empresaId);

            $periodoId =
                $this->inteiroOpcional(
                    $dados['periodo_id'] ?? null
                );

            $folhas =
                $this->model->listarFolhas(
                    $empresaId,
                    $periodoId
                );

            return $this->sucesso(
                'Folhas salariais carregadas.',
                $folhas
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Funcionários de uma folha
    |--------------------------------------------------------------------------
    */
    public function funcionarios(
    int $empresaId,
    array $dados
): array {
    try {
        $this->validarEmpresa(
            $empresaId
        );

        $folhaSalarialId =
            $this->inteiroObrigatorio(
                $dados[
                    'folha_salarial_id'
                ] ?? null,
                'Selecione a folha salarial.'
            );

        $funcionarios =
            $this->model
                ->listarFuncionariosFolha(
                    $folhaSalarialId,
                    $empresaId
                );

        return $this->sucesso(
            'Funcionários carregados.',
            $funcionarios
        );
    } catch (Throwable $e) {
        return $this->erro($e);
    }
}

    /*
    |--------------------------------------------------------------------------
    | Gerar recibo individual
    |--------------------------------------------------------------------------
    */
    public function gerar(
    int $empresaId,
    int $usuarioId,
    array $dados
): array {
    try {
        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUsuario(
            $usuarioId
        );

        $folhaSalarialId =
            $this->inteiroObrigatorio(
                $dados[
                    'folha_salarial_id'
                ] ?? null,
                'Selecione a folha salarial.'
            );

        $processamentoId =
            $this->inteiroObrigatorio(
                $dados[
                    'processamento_id'
                ] ?? null,
                'Selecione o funcionário.'
            );

        $observacoes =
            $this->textoOpcional(
                $dados['observacoes']
                ?? null
            );

        $recibo =
            $this->model
                ->gerarIndividual(
                    $processamentoId,
                    $folhaSalarialId,
                    $empresaId,
                    $usuarioId,
                    $observacoes
                );

        return $this->sucesso(
            'Recibo salarial gerado com sucesso.',
            $recibo
        );
    } catch (Throwable $e) {
        return $this->erro($e);
    }
}

    /*
    |--------------------------------------------------------------------------
    | Gerar recibos de toda a folha
    |--------------------------------------------------------------------------
    */
    public function gerarTodos(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): array {
        try {
            $this->validarEmpresa($empresaId);
            $this->validarUsuario($usuarioId);

            $folhaSalarialId =
                $this->inteiroObrigatorio(
                    $dados[
                        'folha_salarial_id'
                    ] ?? null,
                    'Selecione a folha salarial.'
                );

            $observacoes =
                $this->textoOpcional(
                    $dados['observacoes'] ?? null
                );

            $resultado =
                $this->model->gerarTodos(
                    $folhaSalarialId,
                    $empresaId,
                    $usuarioId,
                    $observacoes
                );

            $gerados =
                (int) (
                    $resultado['gerados'] ?? 0
                );

            $ignorados =
                (int) (
                    $resultado['ignorados'] ?? 0
                );

            $mensagem =
                $gerados === 1
                    ? '1 recibo salarial foi gerado.'
                    : $gerados
                        . ' recibos salariais foram gerados.';

            if ($ignorados > 0) {
                $mensagem .=
                    ' '
                    . $ignorados
                    . ' processamento(s) não foram gerados.';
            }

            if (
                $gerados === 0
                && isset($resultado['message'])
            ) {
                $mensagem =
                    (string) $resultado['message'];
            }

            return $this->sucesso(
                $mensagem,
                $resultado
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Processar geração pelo tipo escolhido
    |--------------------------------------------------------------------------
    */
    public function processarGeracao(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): array {
        try {
            $tipoGeracao =
                trim(
                    (string) (
                        $dados[
                            'tipo_geracao'
                        ] ?? ''
                    )
                );

            if ($tipoGeracao === 'todos') {
                return $this->gerarTodos(
                    $empresaId,
                    $usuarioId,
                    $dados
                );
            }

            if ($tipoGeracao === 'individual') {
                return $this->gerar(
                    $empresaId,
                    $usuarioId,
                    $dados
                );
            }

            throw new InvalidArgumentException(
                'Selecione um tipo de geração válido.'
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Marcar como enviado
    |--------------------------------------------------------------------------
    */
    public function marcarEnviado(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): array {
        try {
            $this->validarEmpresa($empresaId);
            $this->validarUsuario($usuarioId);

            $id =
                $this->inteiroObrigatorio(
                    $dados['id'] ?? null,
                    'Informe o recibo salarial.'
                );

            $recibo =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if ($recibo === null) {
                throw new RuntimeException(
                    'Recibo salarial não encontrado.'
                );
            }

            if ($recibo['estado'] === 'Cancelado') {
                throw new RuntimeException(
                    'Não é possível enviar um recibo cancelado.'
                );
            }

            if ($recibo['estado'] === 'Entregue') {
                throw new RuntimeException(
                    'Este recibo já foi entregue.'
                );
            }

            if ($recibo['estado'] === 'Enviado') {
                throw new RuntimeException(
                    'Este recibo já está marcado como enviado.'
                );
            }

            $atualizado =
                $this->model->marcarEnviado(
                    $id,
                    $empresaId,
                    $usuarioId
                );

            if (!$atualizado) {
                throw new RuntimeException(
                    'Não foi possível atualizar o recibo.'
                );
            }

            return $this->sucesso(
                'Recibo marcado como enviado.',
                [
                    'id' => $id
                ]
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Marcar como entregue
    |--------------------------------------------------------------------------
    */
    public function marcarEntregue(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): array {
        try {
            $this->validarEmpresa($empresaId);
            $this->validarUsuario($usuarioId);

            $id =
                $this->inteiroObrigatorio(
                    $dados['id'] ?? null,
                    'Informe o recibo salarial.'
                );

            $recibo =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if ($recibo === null) {
                throw new RuntimeException(
                    'Recibo salarial não encontrado.'
                );
            }

            if ($recibo['estado'] === 'Cancelado') {
                throw new RuntimeException(
                    'Não é possível entregar um recibo cancelado.'
                );
            }

            if ($recibo['estado'] === 'Entregue') {
                throw new RuntimeException(
                    'Este recibo já está marcado como entregue.'
                );
            }

            $atualizado =
                $this->model->marcarEntregue(
                    $id,
                    $empresaId,
                    $usuarioId
                );

            if (!$atualizado) {
                throw new RuntimeException(
                    'Não foi possível atualizar o recibo.'
                );
            }

            return $this->sucesso(
                'Recibo marcado como entregue.',
                [
                    'id' => $id
                ]
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelar recibo
    |--------------------------------------------------------------------------
    */
    public function cancelar(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): array {
        try {
            $this->validarEmpresa($empresaId);
            $this->validarUsuario($usuarioId);

            $id =
                $this->inteiroObrigatorio(
                    $dados['id'] ?? null,
                    'Informe o recibo salarial.'
                );

            $motivo =
                trim(
                    (string) (
                        $dados['motivo'] ?? ''
                    )
                );

            if (mb_strlen($motivo) < 5) {
                throw new InvalidArgumentException(
                    'Informe um motivo com pelo menos 5 caracteres.'
                );
            }

            $cancelado =
                $this->model->cancelar(
                    $id,
                    $empresaId,
                    $usuarioId,
                    $motivo
                );

            if (!$cancelado) {
                throw new RuntimeException(
                    'Não foi possível cancelar o recibo.'
                );
            }

            return $this->sucesso(
                'Recibo salarial cancelado com sucesso.',
                [
                    'id' => $id
                ]
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */
    public function resumo(
        int $empresaId
    ): array {
        try {
            $this->validarEmpresa($empresaId);

            $resumo =
                $this->model->resumo(
                    $empresaId
                );

            return $this->sucesso(
                'Resumo carregado com sucesso.',
                $resumo
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Métodos auxiliares
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

    private function validarUsuario(
        int $usuarioId
    ): void {
        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não identificado.'
            );
        }
    }

    private function inteiroObrigatorio(
        mixed $valor,
        string $mensagem
    ): int {
        if (
            $valor === null
            || $valor === ''
            || !is_numeric($valor)
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        $inteiro = (int) $valor;

        if ($inteiro <= 0) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return $inteiro;
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
            return null;
        }

        $inteiro = (int) $valor;

        return $inteiro > 0
            ? $inteiro
            : null;
    }

    private function textoOpcional(
        mixed $valor
    ): ?string {
        if ($valor === null) {
            return null;
        }

        $texto =
            trim((string) $valor);

        return $texto !== ''
            ? $texto
            : null;
    }

    private function sucesso(
        string $mensagem,
        mixed $dados = null
    ): array {
        return [
            'success' => true,
            'message' => $mensagem,
            'data' => $dados
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[ReciboSalarioController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data' => null
        ];
    }
}