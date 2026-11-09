<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/AjusteStock.php';


final class AjusteStockController
{
    private PDO $db;

    private AjusteStock $model;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new AjusteStock(
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
                    ->listarProdutos(
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
                    'Selecione o armazém.'
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
                    'Informe a data do ajuste.'
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
            // OBSERVAÇÕES / MOTIVO
            // ==========================================

            $observacoes =
                trim(
                    (string)(
                        $_POST['observacoes']
                        ?? ''
                    )
                );


            if ($observacoes === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Informe o motivo do ajuste.'
                ];
            }


            // ==========================================
            // ITENS
            // ==========================================

            $itens =
                json_decode(
                    $_POST['itens']
                        ?? '',
                    true
                );


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


                $stockFisico =
                    (float)(
                        $item['stock_fisico']
                        ?? -1
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


                if ($stockFisico < 0) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O stock físico da linha '
                            . ($indice + 1)
                            . ' não pode ser negativo.'
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


                $produtosUtilizados[$produtoId] = true;


                /*
                 * O custo é obtido do cadastro
                 * atual do produto.
                 */
                $item = [

                    'produto_id' =>
                    $produtoId,

                    'produto_nome' =>
                    (string)$produto['nome'],

                    'produto_sku' =>
                    (string)(
                        $produto['sku']
                        ?? ''
                    ),

                    'stock_fisico' =>
                    $stockFisico,

                    'custo_unitario' =>
                    (float)$produto['preco_custo']
                ];
            }


            unset($item);


            // ==========================================
            // AJUSTAR
            // ==========================================

            $movimentoId =
                $this->model
                ->ajustar(
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
                'Ajuste de stock registado com sucesso.',

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
                '[AjusteStockController] %s em %s:%d',
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

                : 'Ocorreu um erro interno ao processar o ajuste de stock.'
        ];
    }
}
