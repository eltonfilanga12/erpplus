<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/SaidaStock.php';


final class SaidaStockController
{
    private PDO $db;

    private SaidaStock $model;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new SaidaStock(
                $this->db
            );
    }


    // ==================================================
    // ARMAZÉNS
    // ==================================================

    public function armazens(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarArmazens(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    // ==================================================
    // PRODUTOS
    // ==================================================

    public function produtos(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $armazemId =
                (int)(
                    $_GET['armazem_id']
                    ?? 0
                );


            if ($armazemId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Informe o armazém.'
                ];
            }


            if (
                !$this->model
                    ->armazemPertenceEmpresa(
                        $armazemId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O armazém informado é inválido.'
                ];
            }


            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarProdutosPorArmazem(
                        $empresaId,
                        $armazemId
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    // ==================================================
    // GUARDAR
    // ==================================================

    public function guardar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            // ==========================================
            // ARMAZÉM
            // ==========================================

            $armazemId =
                (int)(
                    $_POST['armazem_id']
                    ?? 0
                );


            if ($armazemId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Selecione o armazém de origem.'
                ];
            }


            if (
                !$this->model
                    ->armazemPertenceEmpresa(
                        $armazemId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O armazém selecionado é inválido.'
                ];
            }


            // ==========================================
            // STATUS
            // ==========================================

            $statusId =
                $this->model
                ->statusConfirmado();


            if ($statusId === null) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O estado Confirmado para movimentos de stock não está configurado.'
                ];
            }


            // ==========================================
            // DATA
            // ==========================================

            $dataMovimento =
                trim(
                    (string)(
                        $_POST['data_movimento']
                        ?? ''
                    )
                );


            if ($dataMovimento === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Informe a data da saída.'
                ];
            }


            $dataMovimento =
                str_replace(
                    'T',
                    ' ',
                    $dataMovimento
                );


            if (
                strlen(
                    $dataMovimento
                ) === 16
            ) {

                $dataMovimento .=
                    ':00';
            }


            // ==========================================
            // OBSERVAÇÕES
            // ==========================================

            $observacoes =
                trim(
                    (string)(
                        $_POST['observacoes']
                        ?? ''
                    )
                );


            if ($observacoes === '') {

                $observacoes =
                    'Saída manual de stock';
            }


            // ==========================================
            // ITENS
            // ==========================================

            $itensJson =
                (string)(
                    $_POST['itens']
                    ?? ''
                );


            if ($itensJson === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Adicione pelo menos um produto.'
                ];
            }


            $itens =
                json_decode(
                    $itensJson,
                    true
                );


            if (
                json_last_error()
                !== JSON_ERROR_NONE
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Os itens da saída possuem um formato inválido.'
                ];
            }


            if (
                !is_array($itens)
                || count($itens) === 0
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Adicione pelo menos um produto.'
                ];
            }


            // ==========================================
            // VALIDAR ITENS
            // ==========================================

            $produtosUtilizados =
                [];


            foreach (
                $itens as $indice => &$item
            ) {

                $produtoId =
                    (int)(
                        $item['produto_id']
                        ?? 0
                    );


                $quantidade =
                    (float)(
                        $item['quantidade']
                        ?? 0
                    );


                if ($produtoId <= 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Selecione o produto na linha '
                            . ($indice + 1)
                            . '.'
                    ];
                }


                $produto =
                    $this->model
                    ->produto(
                        $produtoId,
                        $empresaId
                    );


                if (!$produto) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Produto inválido na linha '
                            . ($indice + 1)
                            . '.'
                    ];
                }


                if ($quantidade <= 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'A quantidade da linha '
                            . ($indice + 1)
                            . ' deve ser superior a zero.'
                    ];
                }


                if (
                    isset(
                        $produtosUtilizados[$produtoId]
                    )
                ) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O produto "'
                            . $produto['nome']
                            . '" foi informado mais de uma vez.'
                    ];
                }


                /*
                 * Esta verificação serve para
                 * mensagem imediata ao utilizador.
                 *
                 * O Model/EstoqueService voltará
                 * a validar dentro da transação.
                 */
                $saldo =
                    $this->model
                    ->saldo(
                        $empresaId,
                        $armazemId,
                        $produtoId
                    );


                if (
                    $quantidade
                    > $saldo + 0.000001
                ) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Stock insuficiente para "'
                            . $produto['nome']
                            . '". Disponível: '
                            . $saldo
                            . '.'
                    ];
                }


                $produtosUtilizados[$produtoId] = true;


                /*
                 * Para a SAÍDA não confiamos
                 * no custo recebido do browser.
                 *
                 * O custo real será substituído
                 * pelo EstoqueService usando CMP.
                 */
                $item = [

                    'produto_id' =>
                    $produtoId,

                    'produto_nome' =>
                    (string)(
                        $produto['nome']
                        ?? ''
                    ),

                    'produto_sku' =>
                    (string)(
                        $produto['sku']
                        ?? ''
                    ),

                    'quantidade' =>
                    $quantidade,

                    /*
                     * Valor provisório.
                     *
                     * EstoqueService atualizará
                     * movimento_estoque_itens com
                     * o CMP verdadeiro.
                     */
                    'custo_unitario' =>
                    0.0
                ];
            }


            unset($item);


            // ==========================================
            // CRIAR SAÍDA
            // ==========================================

            $movimentoId =
                $this->model
                ->criar(
                    $empresaId,
                    $usuarioId,
                    $armazemId,
                    $statusId,
                    $dataMovimento,
                    $observacoes,
                    $itens
                );


            return [
                'sucesso' => true,

                'mensagem' =>
                'Saída de stock registada com sucesso.',

                'movimento_id' =>
                $movimentoId
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    // ==================================================
    // EMPRESA
    // ==================================================

    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;


        if (
            !is_numeric(
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


    // ==================================================
    // UTILIZADOR
    // ==================================================

    private function usuarioId(): int
    {
        $usuarioId =
            $_SESSION['usuario_id']
            ?? $_SESSION['utilizador_id']
            ?? $_SESSION['usuario']['id']
            ?? $_SESSION['user']['id']
            ?? null;


        if (
            !is_numeric(
                $usuarioId
            )
            || (int)$usuarioId <= 0
        ) {

            throw new RuntimeException(
                'Utilizador não identificado na sessão.'
            );
        }


        return (int)$usuarioId;
    }


    // ==================================================
    // ERRO
    // ==================================================

    private function erro(
        Throwable $e
    ): array {

        error_log(
            sprintf(
                '[SaidaStockController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );


        return [
            'sucesso' => false,

            'mensagem' =>
            $e instanceof RuntimeException
                || $e instanceof InvalidArgumentException

                ? $e->getMessage()

                : 'Ocorreu um erro interno ao registar a saída de stock.'
        ];
    }
}
