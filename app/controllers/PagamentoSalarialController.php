<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/IntegracaoFolhaSalarial.php';

class PagamentoSalarialController
{
    private PagamentoSalarial $model;
    private IntegracaoFolhaSalarial $integracaoContabilistica;

    public function __construct()
    {
        $this->model =
            new PagamentoSalarial();

        $this->integracaoContabilistica =
            new IntegracaoFolhaSalarial();
    }

    /**
     * Lista os pagamentos salariais.
     */
    public function listar(): array
    {
        try {
            $empresaId = $this->empresaId();

            $filtros = [
                'periodo_id' =>
                $this->inteiroOpcional(
                    $_GET['periodo_id'] ?? null
                ),

                'funcionario_id' =>
                $this->inteiroOpcional(
                    $_GET['funcionario_id'] ?? null
                ),

                'status_id' =>
                $this->inteiroOpcional(
                    $_GET['status_id'] ?? null
                ),

                'metodo_pagamento' =>
                trim(
                    (string) (
                        $_GET['metodo_pagamento']
                        ?? ''
                    )
                ),

                'pesquisa' =>
                trim(
                    (string) (
                        $_GET['pesquisa']
                        ?? ''
                    )
                )
            ];

            $pagamentos = $this->model->listar(
                $empresaId,
                $filtros
            );

            return [
                'sucesso' => true,
                'dados' => $pagamentos
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /**
     * Busca um pagamento.
     */
    public function buscar(): array
    {
        try {
            $empresaId = $this->empresaId();

            $id = $this->inteiroObrigatorio(
                $_GET['id'] ?? $_POST['id'] ?? null,
                'Pagamento inválido.'
            );

            $pagamento = $this->model->buscar(
                $id,
                $empresaId
            );

            if (!$pagamento) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Pagamento salarial não encontrado.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $pagamento
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /**
     * Cria um pagamento pendente.
     */
    /**
     * Cria um pagamento salarial pendente.
     */
    public function criar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();

            /*
        |--------------------------------------------------------------------------
        | Validar processamento
        |--------------------------------------------------------------------------
        */

            $processamentoId =
                $this->inteiroObrigatorio(
                    $_POST['processamento_id']
                        ?? null,
                    'Selecione o processamento salarial.'
                );

            /*
        |--------------------------------------------------------------------------
        | Validar recibo
        |--------------------------------------------------------------------------
        */

            $reciboId =
                $this->inteiroOpcional(
                    $_POST['recibo_id']
                        ?? null
                );

            if (!$reciboId) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Selecione o recibo salarial.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Validar valores
        |--------------------------------------------------------------------------
        */

            $valorProcessamento =
                $this->decimalObrigatorio(
                    $_POST['valor_processamento']
                        ?? null,
                    'Informe o valor processado.'
                );

            $valorPago =
                $this->decimalObrigatorio(
                    $_POST['valor_pago']
                        ?? null,
                    'Informe o valor a pagar.'
                );

            if ($valorProcessamento <= 0) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O valor processado deve ser maior que zero.'
                ];
            }

            if ($valorPago <= 0) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O valor a pagar deve ser maior que zero.'
                ];
            }

            if ($valorPago > $valorProcessamento) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O valor pago não pode ser superior ao valor processado.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Evitar duplicidade do recibo
        |--------------------------------------------------------------------------
        */

            $pagamentoExistente =
                $this->model->buscarPorRecibo(
                    $reciboId,
                    $empresaId
                );

            if ($pagamentoExistente) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Este recibo já possui um pagamento salarial.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Estado pendente
        |--------------------------------------------------------------------------
        */

            $statusPendenteId =
                $this->model->buscarStatusId(
                    'Pendente'
                );

            if (!$statusPendenteId) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado Pendente não foi configurado para pagamentos salariais.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Forma de pagamento
        |--------------------------------------------------------------------------
        */

            $formaPagamento =
                $this->validarFormaPagamento();

            /*
        |--------------------------------------------------------------------------
        | Validação dos dados bancários
        |--------------------------------------------------------------------------
        */
            $contaContabilId =
                $this->inteiroObrigatorio(
                    $_POST['conta_contabil_id']
                        ?? null,
                    'Selecione a conta de pagamento.'
                );

            if (
                !$this->model->validarContaPagamento(
                    $contaContabilId,
                    $empresaId,
                    $formaPagamento
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A conta contabilística selecionada não é válida para esta forma de pagamento.'
                ];
            }

            $dadosBancarios =
                $this->model
                ->buscarDadosBancariosFuncionarioPorRecibo(
                    $empresaId,
                    $reciboId
                );


            $banco =
                $dadosBancarios['banco_nome']
                ?? null;


            $contaBancaria =
                $dadosBancarios['numero_conta_bancaria']
                ?? null;


            $iban =
                $dadosBancarios['nib']
                ?? null;

            if (
                $formaPagamento
                === 'TRANSFERENCIA_BANCARIA'
            ) {

                if (!$dadosBancarios) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Não foi possível localizar '
                            . 'os dados bancários do funcionário.'
                    ];
                }


                if (!$banco) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O funcionário não possui banco configurado.'
                    ];
                }


                if (!$contaBancaria) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O funcionário não possui '
                            . 'número de conta bancária configurado.'
                    ];
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Gerar número
        |--------------------------------------------------------------------------
        */

            $numero =
                $this->model->gerarNumero(
                    $empresaId
                );

            /*
        |--------------------------------------------------------------------------
        | Dados para gravação
        |--------------------------------------------------------------------------
        */

            $dados = [
                'uuid' =>
                $this->model->gerarUuid(),

                'codigo' =>
                $numero,

                'empresa_id' =>
                $empresaId,

                'processamento_id' =>
                $processamentoId,

                'recibo_id' =>
                $reciboId,

                'numero' =>
                $numero,

                'forma_pagamento' =>
                $formaPagamento,

                'conta_contabil_id' =>
                $contaContabilId,

                'data_pagamento' =>
                $this->dataOpcional(
                    $_POST['data_pagamento']
                        ?? null
                ) ?? date('Y-m-d'),

                'moeda' =>
                strtoupper(
                    trim(
                        (string) (
                            $_POST['moeda']
                            ?? 'MZN'
                        )
                    )
                ),

                'valor_processamento' =>
                $valorProcessamento,

                'valor_pago' =>
                $valorPago,

                'banco' =>
                $banco,

                'conta_bancaria' =>
                $contaBancaria,

                'iban' =>
                $iban,

                'referencia' =>
                $this->textoOpcional(
                    $_POST['referencia']
                        ?? $_POST['referencia_pagamento']
                        ?? null,
                    150
                ),

                'comprovativo' =>
                $this->textoOpcional(
                    $_POST['comprovativo']
                        ?? null,
                    500
                ),

                'observacoes' =>
                $this->textoOpcional(
                    $_POST['observacoes']
                        ?? null,
                    1000
                ),

                'status_id' =>
                $statusPendenteId,

                'registado_por' =>
                $usuarioId
            ];

            /*
        |--------------------------------------------------------------------------
        | Criar
        |--------------------------------------------------------------------------
        */

            $id =
                $this->model->criar(
                    $dados
                );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Pagamento salarial criado com sucesso.',
                'id' => $id
            ];
        } catch (PDOException $e) {
            error_log(
                '[PagamentoSalarialController::criar] '
                    . $e->getMessage()
            );

            $mensagemSql =
                strtolower(
                    $e->getMessage()
                );

            if (
                str_contains(
                    $mensagemSql,
                    'uq_pagamento_empresa_numero'
                )
                || str_contains(
                    $mensagemSql,
                    'uk_pagamento_codigo_empresa'
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Já existe um pagamento com este número ou código.'
                ];
            }

            if (
                str_contains(
                    $mensagemSql,
                    'fk_pagamento_recibo'
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O recibo selecionado não existe ou não pertence ao registo esperado.'
                ];
            }

            if (
                str_contains(
                    $mensagemSql,
                    'fk_pagamento_processamento'
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O processamento salarial selecionado não existe.'
                ];
            }

            if (
                str_contains(
                    $mensagemSql,
                    'fk_pagamento_status'
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado do pagamento não está corretamente configurado.'
                ];
            }

            return [
                'sucesso' => false,
                'mensagem' =>
                'Erro ao guardar o pagamento: '
                    . $e->getMessage()
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function contasPagamento(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $formaPagamento = trim(
                (string) (
                    $_GET['forma_pagamento']
                    ?? $_POST['forma_pagamento']
                    ?? ''
                )
            );

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarContasPagamento(
                        $empresaId,
                        $formaPagamento
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }

    private function validarFormaPagamento(): string
    {
        $forma =
            strtoupper(
                trim(
                    (string) (
                        $_POST['forma_pagamento']
                        ?? $_POST['metodo_pagamento']
                        ?? ''
                    )
                )
            );

        $formasPermitidas = [
            'TRANSFERENCIA_BANCARIA',
            'NUMERARIO',
            'CHEQUE',
            'MPESA',
            'EMOLA',
            'MKESH',
            'CARTEIRA_DIGITAL',
            'OUTRO'
        ];

        if (
            !in_array(
                $forma,
                $formasPermitidas,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Forma de pagamento inválida.'
            );
        }

        return $forma;
    }

    public function processamentosDisponiveis(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $periodoId =
                $this->inteiroOpcional(
                    $_GET['periodo_id']
                        ?? $_POST['periodo_id']
                        ?? null
                );

            return [
                'sucesso' => true,
                'dados' =>
                $this->model
                    ->listarProcessamentosDisponiveis(
                        $empresaId,
                        $periodoId
                    )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /**
     * Atualiza um pagamento pendente.
     */
    public function atualizar(): array
    {
        try {

            $empresaId =
                $this->empresaId();

            $id =
                $this->inteiroObrigatorio(
                    $_POST['id'] ?? null,
                    'Pagamento inválido.'
                );

            $pagamento =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$pagamento) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Pagamento salarial não encontrado.'
                ];
            }

            if (
                mb_strtolower(
                    (string)$pagamento['status_nome']
                ) !== 'pendente'
            ) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Somente pagamentos pendentes podem ser alterados.'
                ];
            }

            $formaPagamento =
                $this->validarFormaPagamento();

            $contaContabilId =
                $this->inteiroObrigatorio(
                    $_POST['conta_contabil_id']
                        ?? null,
                    'Selecione a conta de pagamento.'
                );

            if (
                !$this->model
                    ->validarContaPagamento(
                        $contaContabilId,
                        $empresaId,
                        $formaPagamento
                    )
            ) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A conta selecionada não é compatível com a forma de pagamento.'
                ];
            }

            $valorPago =
                $this->decimalObrigatorio(
                    $_POST['valor_pago']
                        ?? null,
                    'Informe o valor do pagamento.'
                );

            if (
                $valorPago >
                (float)$pagamento['valor_processamento']
            ) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O valor pago não pode ser superior ao valor processado.'
                ];
            }

            $reciboId =
                (int)(
                    $pagamento['recibo_id']
                    ?? 0
                );


            $dadosBancarios =
                $this->model
                ->buscarDadosBancariosFuncionarioPorRecibo(
                    $empresaId,
                    $reciboId
                );


            $banco =
                $dadosBancarios['banco_nome']
                ?? null;


            $contaBancaria =
                $dadosBancarios['numero_conta_bancaria']
                ?? null;


            $iban =
                $dadosBancarios['nib']
                ?? null;

            $dados = [

                'conta_contabil_id' =>
                $contaContabilId,

                'forma_pagamento' =>
                $formaPagamento,

                'data_pagamento' =>
                $this->dataOpcional(
                    $_POST['data_pagamento']
                        ?? null
                ) ?? date('Y-m-d'),

                'moeda' =>
                strtoupper(
                    trim(
                        (string)(
                            $_POST['moeda']
                            ?? 'MZN'
                        )
                    )
                ),

                'valor_processamento' =>
                (float)$pagamento['valor_processamento'],

                'valor_pago' =>
                $valorPago,

                'banco' =>
                $banco,

                'conta_bancaria' =>
                $contaBancaria,

                'iban' =>
                $iban,

                'referencia' =>
                $this->textoOpcional(
                    $_POST['referencia']
                        ?? null,
                    150
                ),

                'comprovativo' =>
                $pagamento['comprovativo']
                    ?? null,

                'observacoes' =>
                $this->textoOpcional(
                    $_POST['observacoes']
                        ?? null,
                    1000
                )
            ];

            $atualizado =
                $this->model->atualizar(
                    $id,
                    $empresaId,
                    $dados
                );

            return [
                'sucesso' => $atualizado,
                'mensagem' =>
                $atualizado
                    ? 'Pagamento salarial atualizado com sucesso.'
                    : 'Não foi possível atualizar o pagamento.'
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function disponiveisLote(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $periodoId =
                $this->inteiroOpcional(
                    $_GET['periodo_id']
                        ?? $_POST['periodo_id']
                        ?? null
                );


            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarDisponiveisLote(
                        $empresaId,
                        $periodoId
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }

    public function criarLote(): array
    {
        $db =
            Database::conn();


        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            /*
        |--------------------------------------------------------------------------
        | Recibos selecionados
        |--------------------------------------------------------------------------
        */

            $recibosJson =
                trim(
                    (string)(
                        $_POST['recibos']
                        ?? ''
                    )
                );


            if ($recibosJson === '') {

                throw new RuntimeException(
                    'Selecione pelo menos um funcionário.'
                );
            }


            $recibos =
                json_decode(
                    $recibosJson,
                    true
                );


            if (
                !is_array($recibos)
                || empty($recibos)
            ) {

                throw new RuntimeException(
                    'A lista de funcionários selecionados é inválida.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Forma de pagamento
        |--------------------------------------------------------------------------
        */

            $formaPagamento =
                $this->validarFormaPagamento();


            /*
        |--------------------------------------------------------------------------
        | Conta da empresa
        |--------------------------------------------------------------------------
        */

            $contaContabilId =
                $this->inteiroObrigatorio(
                    $_POST['conta_contabil_id']
                        ?? null,
                    'Selecione a conta de pagamento.'
                );


            if (
                !$this->model
                    ->validarContaPagamento(
                        $contaContabilId,
                        $empresaId,
                        $formaPagamento
                    )
            ) {

                throw new RuntimeException(
                    'A conta selecionada não é compatível '
                        . 'com a forma de pagamento.'
                );
            }


            /*
        |--------------------------------------------------------------------------
        | Estado Pendente
        |--------------------------------------------------------------------------
        */

            $statusPendenteId =
                $this->model
                ->buscarStatusId(
                    'Pendente'
                );


            if (!$statusPendenteId) {

                throw new RuntimeException(
                    'O estado Pendente não está configurado '
                        . 'para pagamentos salariais.'
                );
            }


            $dataPagamento =
                $this->dataOpcional(
                    $_POST['data_pagamento']
                        ?? null
                )
                ?? date('Y-m-d');


            $observacoes =
                $this->textoOpcional(
                    $_POST['observacoes']
                        ?? null,
                    1000
                );


            /*
        |--------------------------------------------------------------------------
        | Transação
        |--------------------------------------------------------------------------
        */

            if (!$db->inTransaction()) {

                $db->beginTransaction();
            }


            $criados = [];

            $ignorados = [];


            foreach ($recibos as $reciboId) {

                $reciboId =
                    (int)$reciboId;


                if ($reciboId <= 0) {
                    continue;
                }


                /*
             * Consulta novamente no servidor.
             */
                $recibo =
                    $this->model
                    ->buscarReciboDisponivelLote(
                        $empresaId,
                        $reciboId
                    );


                if (!$recibo) {

                    $ignorados[] =
                        $reciboId;

                    continue;
                }


                $valor =
                    round(
                        (float)(
                            $recibo['saldo_pendente']
                            ?? $recibo['salario_liquido']
                            ?? 0
                        ),
                        2
                    );


                if ($valor <= 0) {

                    $ignorados[] =
                        $reciboId;

                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | Validar dados bancários
            |--------------------------------------------------------------------------
            */

                if (
                    $formaPagamento
                    === 'TRANSFERENCIA_BANCARIA'
                ) {

                    if (
                        empty($recibo['banco_nome'])
                    ) {

                        throw new RuntimeException(
                            'Existe funcionário selecionado '
                                . 'sem banco configurado.'
                        );
                    }


                    if (
                        empty($recibo['numero_conta_bancaria'])
                    ) {

                        throw new RuntimeException(
                            'Existe funcionário selecionado '
                                . 'sem número de conta bancária.'
                        );
                    }
                }


                /*
            |--------------------------------------------------------------------------
            | Número do pagamento
            |--------------------------------------------------------------------------
            */

                $numero =
                    $this->model
                    ->gerarNumero(
                        $empresaId
                    );


                /*
            |--------------------------------------------------------------------------
            | Criar pagamento individual
            |--------------------------------------------------------------------------
            */

                $pagamentoId =
                    $this->model
                    ->criar([
                        'uuid' =>
                        $this->model
                            ->gerarUuid(),

                        'codigo' =>
                        $numero,

                        'empresa_id' =>
                        $empresaId,

                        'processamento_id' =>
                        (int)$recibo['processamento_id'],

                        'recibo_id' =>
                        $reciboId,

                        'numero' =>
                        $numero,

                        'forma_pagamento' =>
                        $formaPagamento,

                        'conta_contabil_id' =>
                        $contaContabilId,

                        'data_pagamento' =>
                        $dataPagamento,

                        'moeda' =>
                        strtoupper(
                            (string)(
                                $recibo['moeda']
                                ?? 'MZN'
                            )
                        ),

                        'valor_processamento' =>
                        $valor,

                        'valor_pago' =>
                        $valor,

                        'banco' =>
                        $recibo['banco_nome']
                            ?? null,

                        'conta_bancaria' =>
                        $recibo['numero_conta_bancaria']
                            ?? null,

                        'iban' =>
                        $recibo['nib']
                            ?? null,

                        'referencia' =>
                        null,

                        'comprovativo' =>
                        null,

                        'observacoes' =>
                        $observacoes,

                        'status_id' =>
                        $statusPendenteId,

                        'registado_por' =>
                        $usuarioId
                    ]);


                $criados[] =
                    $pagamentoId;
            }


            if (empty($criados)) {

                throw new RuntimeException(
                    'Nenhum pagamento foi criado.'
                );
            }


            $db->commit();


            return [
                'sucesso' => true,

                'mensagem' =>
                count($criados)
                    . ' pagamento(s) criado(s) com sucesso.',

                'dados' => [
                    'quantidade_criada' =>
                    count($criados),

                    'pagamentos_ids' =>
                    $criados,

                    'ignorados' =>
                    $ignorados
                ]
            ];
        } catch (Throwable $e) {

            if (
                $db instanceof PDO
                && $db->inTransaction()
            ) {

                $db->rollBack();
            }


            return $this->erro(
                $e
            );
        }
    }

    private function confirmarPagamentoInterno(
        int $id,
        int $empresaId,
        int $usuarioId,
        string $dataPagamento,
        ?string $referencia = null
    ): array {

        $pagamento =
            $this->model->buscar(
                $id,
                $empresaId
            );


        if (!$pagamento) {

            throw new RuntimeException(
                'Pagamento salarial não encontrado.'
            );
        }


        if (
            mb_strtolower(
                trim(
                    (string)(
                        $pagamento['status_nome']
                        ?? ''
                    )
                )
            ) !== 'pendente'
        ) {

            throw new RuntimeException(
                'O pagamento '
                    . ($pagamento['numero'] ?? $id)
                    . ' não está pendente.'
            );
        }


        $contaContabilId =
            (int)(
                $pagamento['conta_contabil_id']
                ?? 0
            );


        if ($contaContabilId <= 0) {

            throw new RuntimeException(
                'O pagamento '
                    . ($pagamento['numero'] ?? $id)
                    . ' não possui conta contabilística.'
            );
        }


        $valorPago =
            round(
                (float)(
                    $pagamento['valor_pago']
                    ?? 0
                ),
                2
            );


        if ($valorPago <= 0) {

            throw new RuntimeException(
                'O pagamento possui valor inválido.'
            );
        }


        $statusPagoId =
            $this->model
            ->buscarStatusId(
                'Pago'
            );


        if (!$statusPagoId) {

            throw new RuntimeException(
                'O estado Pago não está configurado.'
            );
        }


        $confirmado =
            $this->model
            ->confirmar(
                $id,
                $empresaId,
                $statusPagoId,
                $usuarioId,
                $dataPagamento,
                $referencia
            );


        if (!$confirmado) {

            throw new RuntimeException(
                'Não foi possível confirmar '
                    . 'o pagamento '
                    . ($pagamento['numero'] ?? $id)
                    . '.'
            );
        }


        /*
     * Contabilização.
     */
        $integracao =
            $this->integracaoContabilistica
            ->contabilizarPagamento(
                $id,
                $empresaId,
                $usuarioId
            );


        $reciboId =
            (int)(
                $pagamento['recibo_id']
                ?? 0
            );


        if ($reciboId <= 0) {

            throw new RuntimeException(
                'O pagamento não possui '
                    . 'recibo associado.'
            );
        }


        if (
            !$this->model
                ->sincronizarRecibo(
                    $reciboId,
                    $empresaId
                )
        ) {

            throw new RuntimeException(
                'Não foi possível sincronizar '
                    . 'o recibo salarial.'
            );
        }


        $processamentoId =
            (int)(
                $pagamento['processamento_id']
                ?? 0
            );


        if ($processamentoId <= 0) {

            throw new RuntimeException(
                'O pagamento não possui '
                    . 'processamento associado.'
            );
        }


        if (
            !$this->model
                ->sincronizarProcessamento(
                    $processamentoId,
                    $empresaId
                )
        ) {

            throw new RuntimeException(
                'Não foi possível sincronizar '
                    . 'o processamento salarial.'
            );
        }


        if (
            !$this->model
                ->sincronizarFolhaPorRecibo(
                    $reciboId,
                    $empresaId
                )
        ) {

            throw new RuntimeException(
                'Não foi possível sincronizar '
                    . 'a folha salarial.'
            );
        }


        return [
            'pagamento_id' =>
            $id,

            'numero' =>
            $pagamento['numero']
                ?? null,

            'valor' =>
            $valorPago,

            'lancamento_contabilistico_id' =>
            $integracao['lancamento_contabilistico_id']
                ?? null
        ];
    }

    public function confirmarLote(): array
    {
        $db =
            Database::conn();


        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            $idsJson =
                trim(
                    (string)(
                        $_POST['pagamentos']
                        ?? ''
                    )
                );


            if ($idsJson === '') {

                throw new RuntimeException(
                    'Selecione pelo menos um pagamento.'
                );
            }


            $ids =
                json_decode(
                    $idsJson,
                    true
                );


            if (
                !is_array($ids)
                || empty($ids)
            ) {

                throw new RuntimeException(
                    'A lista de pagamentos é inválida.'
                );
            }


            $ids =
                array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                'intval',
                                $ids
                            ),
                            fn($id) =>
                            $id > 0
                        )
                    )
                );


            if (empty($ids)) {

                throw new RuntimeException(
                    'Nenhum pagamento válido foi selecionado.'
                );
            }


            $dataPagamento =
                $this->dataOpcional(
                    $_POST['data_pagamento']
                        ?? null
                )
                ?? date('Y-m-d');


            $referencia =
                $this->textoOpcional(
                    $_POST['referencia']
                        ?? null,
                    150
                );


            if (!$db->inTransaction()) {

                $db->beginTransaction();
            }


            $confirmados = [];

            $total =
                0.0;


            foreach ($ids as $id) {

                $resultado =
                    $this->confirmarPagamentoInterno(
                        $id,
                        $empresaId,
                        $usuarioId,
                        $dataPagamento,
                        $referencia
                    );


                $confirmados[] =
                    $resultado;


                $total +=
                    (float)(
                        $resultado['valor']
                        ?? 0
                    );
            }


            $db->commit();


            return [
                'sucesso' => true,

                'mensagem' =>
                count($confirmados)
                    . ' pagamento(s) confirmado(s) '
                    . 'e contabilizado(s) com sucesso.',

                'dados' => [
                    'quantidade' =>
                    count($confirmados),

                    'valor_total' =>
                    round(
                        $total,
                        2
                    ),

                    'pagamentos' =>
                    $confirmados
                ]
            ];
        } catch (Throwable $e) {

            if (
                $db instanceof PDO
                && $db->inTransaction()
            ) {

                $db->rollBack();
            }


            return [
                'sucesso' => false,

                'mensagem' =>
                'Não foi possível confirmar o lote: '
                    . $e->getMessage()
            ];
        }
    }
    /**
     * Confirma o pagamento.
     */
    public function confirmar(): array
    {
        $db = Database::conn();

        try {

            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();

            $id =
                $this->inteiroObrigatorio(
                    $_POST['id']
                        ?? null,
                    'Pagamento inválido.'
                );

            /*
        |--------------------------------------------------------------------------
        | Buscar pagamento
        |--------------------------------------------------------------------------
        */

            $pagamento =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$pagamento) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Pagamento salarial não encontrado.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Apenas pagamentos pendentes
        |--------------------------------------------------------------------------
        */

            if (
                mb_strtolower(
                    trim(
                        (string)$pagamento['status_nome']
                    )
                ) !== 'pendente'
            ) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Somente pagamentos pendentes podem ser confirmados.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Conta contabilística obrigatória
        |--------------------------------------------------------------------------
        */

            $contaContabilId =
                isset(
                    $pagamento['conta_contabil_id']
                )
                ? (int)$pagamento['conta_contabil_id']
                : 0;

            if ($contaContabilId <= 0) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O pagamento não possui uma conta contabilística definida.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Validar valor
        |--------------------------------------------------------------------------
        */

            $valorPago =
                round(
                    (float)$pagamento['valor_pago'],
                    2
                );

            if ($valorPago <= 0) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O pagamento possui um valor inválido.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Estado Pago
        |--------------------------------------------------------------------------
        */

            $statusPagoId =
                $this->model->buscarStatusId(
                    'Pago'
                );

            if (!$statusPagoId) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado Pago de pagamento salarial não foi configurado.'
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

            $dataPagamento =
                $this->dataOpcional(
                    $_POST['data_pagamento']
                        ?? null
                )
                ?? (
                    $pagamento['data_pagamento']
                    ?? date('Y-m-d')
                );

            /*
        |--------------------------------------------------------------------------
        | Referência
        |--------------------------------------------------------------------------
        */

            $referencia =
                $this->textoOpcional(
                    $_POST['referencia_pagamento']
                        ?? $_POST['referencia']
                        ?? $pagamento['referencia']
                        ?? null,
                    150
                );

            /*
        |--------------------------------------------------------------------------
        | Iniciar transação global
        |--------------------------------------------------------------------------
        */

            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            /*
        |--------------------------------------------------------------------------
        | Confirmar pagamento
        |--------------------------------------------------------------------------
        */

            $confirmado =
                $this->model->confirmar(
                    $id,
                    $empresaId,
                    $statusPagoId,
                    $usuarioId,
                    $dataPagamento,
                    $referencia
                );

            if (!$confirmado) {

                throw new RuntimeException(
                    'Não foi possível confirmar o pagamento salarial.'
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Integração contabilística
        |--------------------------------------------------------------------------
        */

            $integracao =
                $this->integracaoContabilistica
                ->contabilizarPagamento(
                    $id,
                    $empresaId,
                    $usuarioId
                );

            /*
|--------------------------------------------------------------------------
| Sincronizar recibo
|--------------------------------------------------------------------------
*/

            $reciboId =
                isset($pagamento['recibo_id'])
                ? (int)$pagamento['recibo_id']
                : 0;

            if ($reciboId <= 0) {
                throw new RuntimeException(
                    'O pagamento não possui recibo salarial associado.'
                );
            }

            $reciboSincronizado =
                $this->model->sincronizarRecibo(
                    $reciboId,
                    $empresaId
                );

            if (!$reciboSincronizado) {
                throw new RuntimeException(
                    'Não foi possível atualizar os valores pagos do recibo salarial.'
                );
            }


            /*
|--------------------------------------------------------------------------
| Sincronizar processamento salarial
|--------------------------------------------------------------------------
*/

            $processamentoId =
                isset($pagamento['processamento_id'])
                ? (int)$pagamento['processamento_id']
                : 0;

            if ($processamentoId <= 0) {
                throw new RuntimeException(
                    'O pagamento não possui processamento salarial associado.'
                );
            }

            $processamentoSincronizado =
                $this->model->sincronizarProcessamento(
                    $processamentoId,
                    $empresaId
                );

            if (!$processamentoSincronizado) {
                throw new RuntimeException(
                    'Não foi possível atualizar o processamento salarial.'
                );
            }


            /*
|--------------------------------------------------------------------------
| Sincronizar folha salarial
|--------------------------------------------------------------------------
*/

            $folhaSincronizada =
                $this->model->sincronizarFolhaPorRecibo(
                    $reciboId,
                    $empresaId
                );

            if (!$folhaSincronizada) {
                throw new RuntimeException(
                    'Não foi possível atualizar os valores pagos da folha salarial.'
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Confirmar tudo
        |--------------------------------------------------------------------------
        */

            if ($db->inTransaction()) {
                $db->commit();
            }

            return [
                'sucesso' => true,

                'mensagem' =>
                'Pagamento salarial confirmado e contabilizado com sucesso.',

                'dados' => [
                    'pagamento_id' =>
                    $id,

                    'lancamento_contabilistico_id' =>
                    $integracao['lancamento_contabilistico_id']
                        ?? null,

                    'numero_lancamento' =>
                    $integracao['numero_lancamento']
                        ?? null,

                    'diario' =>
                    $integracao['diario']
                        ?? null,

                    'valor' =>
                    $integracao['valor']
                        ?? $valorPago
                ]
            ];
        } catch (Throwable $e) {

            if (
                isset($db)
                && $db instanceof PDO
                && $db->inTransaction()
            ) {
                $db->rollBack();
            }

            error_log(
                '[PagamentoSalarialController::confirmar] '
                    . $e->getMessage()
            );

            return [
                'sucesso' => false,
                'mensagem' =>
                'Não foi possível confirmar o pagamento: '
                    . $e->getMessage()
            ];
        }
    }


    public function sincronizarPagamentosConfirmados(): array
    {
        $empresaId =
            $this->empresaId();

        try {
            $pagamentos =
                $this->model->listar(
                    $empresaId,
                    [
                        'status_id' =>
                        $this->model->buscarStatusId(
                            'Pago'
                        )
                    ]
                );

            $total = 0;

            foreach ($pagamentos as $pagamento) {

                $reciboId =
                    (int)(
                        $pagamento['recibo_id']
                        ?? 0
                    );

                $processamentoId =
                    (int)(
                        $pagamento['processamento_id']
                        ?? 0
                    );

                if ($reciboId > 0) {
                    $this->model
                        ->sincronizarRecibo(
                            $reciboId,
                            $empresaId
                        );

                    $this->model
                        ->sincronizarFolhaPorRecibo(
                            $reciboId,
                            $empresaId
                        );
                }

                if ($processamentoId > 0) {
                    $this->model
                        ->sincronizarProcessamento(
                            $processamentoId,
                            $empresaId
                        );
                }

                $total++;
            }

            return [
                'sucesso' => true,
                'mensagem' =>
                'Sincronização concluída com sucesso.',
                'dados' => [
                    'pagamentos_processados' =>
                    $total
                ]
            ];
        } catch (Throwable $e) {
            return [
                'sucesso' => false,
                'mensagem' =>
                $e->getMessage()
            ];
        }
    }
    /**
     * Cancela o pagamento.
     */
    public function cancelar(): array
    {
        try {
            $empresaId = $this->empresaId();
            $usuarioId = $this->usuarioId();

            $id = $this->inteiroObrigatorio(
                $_POST['id'] ?? null,
                'Pagamento inválido.'
            );

            $motivo = trim(
                (string) (
                    $_POST['motivo_cancelamento']
                    ?? ''
                )
            );

            if (mb_strlen($motivo) < 5) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Informe o motivo do cancelamento.'
                ];
            }

            if (mb_strlen($motivo) > 500) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O motivo do cancelamento deve ter no máximo 500 caracteres.'
                ];
            }

            $pagamento = $this->model->buscar(
                $id,
                $empresaId
            );

            if (!$pagamento) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Pagamento salarial não encontrado.'
                ];
            }

            if (
                $pagamento['status_nome']
                === 'Cancelado'
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Este pagamento já está cancelado.'
                ];
            }

            $statusCanceladoId =
                $this->model->buscarStatusId(
                    'Cancelado'
                );

            if (!$statusCanceladoId) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado Cancelado de pagamento salarial não foi configurado.'
                ];
            }

            $cancelado =
                $this->model->cancelar(
                    $id,
                    $empresaId,
                    $statusCanceladoId,
                    $usuarioId,
                    $motivo
                );

            if (!$cancelado) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Não foi possível cancelar o pagamento.'
                ];
            }

            return [
                'sucesso' => true,
                'mensagem' =>
                'Pagamento salarial cancelado com sucesso.'
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /**
     * Lista recibos disponíveis.
     */
    public function recibosDisponiveis(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $periodoId =
                $this->inteiroOpcional(
                    $_GET['periodo_id']
                        ?? $_POST['periodo_id']
                        ?? null
                );

            return [
                'sucesso' => true,
                'dados' =>
                $this->model
                    ->listarRecibosDisponiveis(
                        $empresaId,
                        $periodoId
                    )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }
    /**
     * Lista os estados do módulo.
     */
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

    /**
     * Retorna os totais do módulo.
     */
    public function resumo(): array
    {
        try {
            $empresaId = $this->empresaId();

            $periodoId =
                $this->inteiroOpcional(
                    $_GET['periodo_id']
                        ?? $_POST['periodo_id']
                        ?? null
                );

            $resumo =
                $this->model->resumo(
                    $empresaId
                );

            return [
                'sucesso' => true,
                'dados' => $resumo
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /**
     * Obtém a empresa autenticada.
     */
    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? null;

        if (
            !is_numeric($empresaId)
            || (int) $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }

        return (int) $empresaId;
    }

    /**
     * Obtém o utilizador autenticado.
     */
    private function usuarioId(): ?int
    {
        $usuarioId =
            $_SESSION['usuario_id']
            ?? $_SESSION['usuario']['id']
            ?? null;

        if (
            !is_numeric($usuarioId)
            || (int) $usuarioId <= 0
        ) {
            return null;
        }

        return (int) $usuarioId;
    }

    /**
     * Valida um inteiro obrigatório.
     */
    private function inteiroObrigatorio(
        mixed $valor,
        string $mensagem
    ): int {
        if (
            $valor === null
            || $valor === ''
            || filter_var(
                $valor,
                FILTER_VALIDATE_INT
            ) === false
            || (int) $valor <= 0
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return (int) $valor;
    }

    /**
     * Converte um inteiro opcional.
     */
    private function inteiroOpcional(
        mixed $valor
    ): ?int {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            ) === false
            || (int) $valor <= 0
        ) {
            return null;
        }

        return (int) $valor;
    }

    /**
     * Valida decimal obrigatório.
     */
    private function decimalObrigatorio(
        mixed $valor,
        string $mensagem
    ): float {
        $decimal =
            $this->decimalOpcional($valor);

        if ($decimal === null) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return $decimal;
    }

    /**
     * Converte decimal opcional.
     */
    private function decimalOpcional(
        mixed $valor
    ): ?float {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (is_string($valor)) {
            $valor = trim($valor);

            $valor = str_replace(
                [' ', ','],
                ['', '.'],
                $valor
            );
        }

        if (!is_numeric($valor)) {
            return null;
        }

        return round(
            (float) $valor,
            2
        );
    }

    /**
     * Valida uma data no formato Y-m-d.
     */
    private function dataOpcional(
        mixed $valor
    ): ?string {
        if (
            $valor === null
            || trim((string) $valor) === ''
        ) {
            return null;
        }

        $valor = trim((string) $valor);

        $data = DateTime::createFromFormat(
            'Y-m-d',
            $valor
        );

        if (
            !$data
            || $data->format('Y-m-d')
            !== $valor
        ) {
            throw new InvalidArgumentException(
                'A data informada é inválida.'
            );
        }

        return $valor;
    }

    /**
     * Limpa texto opcional.
     */
    private function textoOpcional(
        mixed $valor,
        int $tamanhoMaximo
    ): ?string {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        if (
            mb_strlen($texto)
            > $tamanhoMaximo
        ) {
            throw new InvalidArgumentException(
                "O campo deve ter no máximo {$tamanhoMaximo} caracteres."
            );
        }

        return $texto;
    }

    /**
     * Valida método de pagamento.
     */
    private function validarMetodoPagamento(
        mixed $metodo
    ): string {
        $metodo = strtoupper(
            trim((string) $metodo)
        );

        $permitidos = [
            'TRANSFERENCIA_BANCARIA',
            'DINHEIRO',
            'CHEQUE',
            'MPESA',
            'EMOLA',
            'MKESH',
            'OUTRO'
        ];

        if (
            !in_array(
                $metodo,
                $permitidos,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Método de pagamento inválido.'
            );
        }

        return $metodo;
    }

    /**
     * Formata erros do Controller.
     */
    private function erro(
        Throwable $e
    ): array {
        error_log(
            '[PagamentoSalarialController] '
                . $e->getMessage()
        );

        if (
            $e instanceof
            InvalidArgumentException
            || $e instanceof
            RuntimeException
        ) {
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Ocorreu um erro ao processar o pagamento salarial.'
        ];
    }
}
