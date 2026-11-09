<?php

declare(strict_types=1);

final class ConfiguracaoContabilisticaRhController
{
    private ConfiguracaoContabilisticaRh $model;

    public function __construct()
    {
        $this->model =
            new ConfiguracaoContabilisticaRh(
                Database::conn()
            );
    }

    public function buscar(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model->buscarPorEmpresa(
                    $this->empresaId()
                )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function contas(
        string $finalidade = ''
    ): array {
        $empresaId = $this->empresaId();

        return [
            'sucesso' => true,
            'dados' => $this->model->listarContas(
                $empresaId,
                $finalidade
            )
        ];
    }

    public function diarios(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listarDiarios(
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

            $configuracaoActual =
                $this->model->buscarPorEmpresa(
                    $empresaId
                );

            $diarioId =
                $this->inteiroObrigatorio(
                    $_POST['diario_contabilistico_id']
                        ?? null,
                    'Selecione o diário contabilístico.'
                );

            if (
                !$this->model->diarioPertenceEmpresa(
                    $diarioId,
                    $empresaId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O diário selecionado não pertence à empresa.'
                ];
            }

            $contaSalariosPagarId =
                $this->inteiroObrigatorio(
                    $_POST['conta_salarios_pagar_id']
                        ?? null,
                    'Selecione a conta de salários a pagar.'
                );

            $contaSalarioBaseId =
                $this->inteiroObrigatorio(
                    $_POST['conta_salario_base_id']
                        ?? null,
                    'Selecione a conta de salário base.'
                );

            $contasOpcionais = [
                'conta_inss_pagar_id',
                'conta_irps_pagar_id',
                'conta_outros_descontos_id',
                'conta_encargos_patronais_id',
                'conta_banco_padrao_id',
                'conta_caixa_padrao_id'
            ];

            $contas = [
                'conta_salario_base_id' =>
                $contaSalarioBaseId,

                'conta_salarios_pagar_id' =>
                $contaSalariosPagarId
            ];

            foreach ($contasOpcionais as $campo) {
                $contas[$campo] =
                    $this->inteiroOpcional(
                        $_POST[$campo]
                            ?? null
                    );
            }

            foreach ($contas as $campo => $contaId) {
                if ($contaId === null) {
                    continue;
                }

                if (
                    !$this->model->contaPertenceEmpresa(
                        $contaId,
                        $empresaId
                    )
                ) {
                    return [
                        'sucesso' => false,
                        'mensagem' =>
                        'Uma das contas selecionadas não pertence à empresa.'
                    ];
                }
            }

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado da configuração.'
                );

            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado selecionado é inválido.'
                ];
            }

            $dados = [
                'diario_contabilistico_id' =>
                $diarioId,

                'conta_salarios_pagar_id' =>
                $contaSalariosPagarId,

                'conta_salario_base_id' =>
                $contaSalarioBaseId,

                'conta_inss_pagar_id' =>
                $contas['conta_inss_pagar_id'],

                'conta_irps_pagar_id' =>
                $contas['conta_irps_pagar_id'],

                'conta_outros_descontos_id' =>
                $contas['conta_outros_descontos_id'],

                'conta_encargos_patronais_id' =>
                $contas['conta_encargos_patronais_id'],

                'conta_banco_padrao_id' =>
                $contas['conta_banco_padrao_id'],

                'conta_caixa_padrao_id' =>
                $contas['conta_caixa_padrao_id'],

                'agrupar_por_filial' =>
                $this->checkbox(
                    $_POST['agrupar_por_filial']
                        ?? null
                ),

                'agrupar_por_centro_custo' =>
                $this->checkbox(
                    $_POST['agrupar_por_centro_custo']
                        ?? null
                ),

                'contabilizar_por_rubrica' =>
                $this->checkbox(
                    $_POST['contabilizar_por_rubrica']
                        ?? null
                ),

                'gerar_lancamento_automaticamente' =>
                $this->checkbox(
                    $_POST['gerar_lancamento_automaticamente']
                        ?? null
                ),

                'status_id' =>
                $statusId
            ];

            if ($configuracaoActual) {
                $id =
                    (int) $configuracaoActual['id'];

                $this->model->atualizar(
                    $id,
                    $empresaId,
                    $usuarioId,
                    $dados
                );

                return [
                    'sucesso' => true,
                    'mensagem' =>
                    'Configuração contabilística atualizada com sucesso.',
                    'dados' => [
                        'id' => $id
                    ]
                ];
            }

            $id =
                $this->model->criar(
                    $empresaId,
                    $usuarioId,
                    $dados
                );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Configuração contabilística criada com sucesso.',
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
                '[ConfiguracaoContabilisticaRhController] %s em %s:%d',
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
                'uq_configuracao_rh_empresa'
            )
            || str_contains(
                $mensagem,
                'uq_config_contabil_rh_empresa'
            )
            || str_contains(
                $mensagem,
                'duplicate entry'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe uma configuração contabilística para esta empresa.'
            ];
        }

        if (
            str_contains(
                $mensagem,
                'foreign key constraint fails'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Uma das contas, o diário ou o estado selecionado é inválido.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível guardar a configuração contabilística.'
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[ConfiguracaoContabilisticaRhController] %s em %s:%d',
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
                : 'Ocorreu um erro interno ao processar a configuração.'
        ];
    }
}
