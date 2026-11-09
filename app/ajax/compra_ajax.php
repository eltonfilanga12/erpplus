<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);

/*
 * Impede avisos HTML dentro do JSON.
 */

ini_set(
    'display_errors',
    '0'
);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Compra.php';

require_once __DIR__
    . '/../controllers/CompraController.php';

Auth::iniciarSessao();

/*
|--------------------------------------------------------------------------
| AJAX não deve redirecionar para uma página HTML
|--------------------------------------------------------------------------
*/

if (!Auth::check()) {
    http_response_code(401);

    echo json_encode([
        'success' => false,

        'mensagem' =>
        'Sessão expirada. Faça login novamente.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $pdo = Database::conn();

    $controller =
        new CompraController($pdo);

    $acao = strtolower(
        trim(
            (string)(
                $_REQUEST['acao'] ?? ''
            )
        )
    );

    switch ($acao) {

        // =====================================
        // LISTAR
        // =====================================

        case 'listar':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->listar()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // BUSCAR
        // =====================================

        case 'buscar':

            $id = (int)(
                $_GET['id'] ?? 0
            );

            $compra =
                $controller->buscar($id);

            if (!$compra) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Compra não encontrada.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            echo json_encode([
                'success' => true,

                'data' => [
                    'compra' =>
                    $compra,

                    'itens' =>
                    $controller
                        ->buscarItens($id)
                ]
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // SALVAR
        // =====================================

        case 'salvar':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para guardar.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            $dados = $_POST;

            /*
             * Itens chegam em JSON pelo JavaScript.
             */

            if (
                isset($dados['itens'])
                && is_string(
                    $dados['itens']
                )
            ) {
                $itens = json_decode(
                    $dados['itens'],
                    true
                );

                if (
                    json_last_error()
                    !== JSON_ERROR_NONE
                ) {
                    throw new RuntimeException(
                        'A lista de produtos da compra é inválida.'
                    );
                }

                $dados['itens'] =
                    is_array($itens)
                    ? $itens
                    : [];
            } else {
                $dados['itens'] = [];
            }

            $compraId =
                $controller->salvar(
                    $dados
                );

            $edicao =
                !empty($_POST['id']);

            echo json_encode([
                'success' => true,

                'mensagem' =>
                $edicao
                    ? 'Compra actualizada com sucesso.'
                    : 'Compra criada com sucesso.',

                'data' => [
                    'id' => $compraId
                ]
            ], JSON_UNESCAPED_UNICODE);

            break;

        case 'filiais':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->filiais()
            ], JSON_UNESCAPED_UNICODE);

            break;


        case 'cancelar':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {

                http_response_code(405);

                echo json_encode([
                    'success' => false,
                    'mensagem' =>
                    'Método inválido para cancelar a compra.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }


            $id =
                (int)(
                    $_POST['id']
                    ?? 0
                );


            $motivo =
                trim(
                    (string)(
                        $_POST['motivo']
                        ?? ''
                    )
                );


            $resultado =
                $controller->cancelar(
                    $id,
                    $motivo
                );


            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Compra cancelada e estornada com sucesso.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =====================================
        // ELIMINAR
        // =====================================

        case 'eliminar':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para eliminar.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            $resultado =
                $controller->eliminar(
                    (int)(
                        $_POST['id'] ?? 0
                    )
                );

            echo json_encode([
                'success' =>
                $resultado,

                'mensagem' =>
                $resultado
                    ? 'Compra eliminada com sucesso.'
                    : 'Não foi possível eliminar a compra.'
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // FORNECEDORES
        // =====================================

        case 'fornecedores':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->fornecedores()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // ARMAZÉNS
        // =====================================

        case 'armazens':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->armazens()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // PRODUTOS
        // =====================================

        case 'produtos':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->produtos()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // STATUS
        // =====================================

        case 'status':
        case 'listar_status':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->status()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // AÇÃO INVÁLIDA
        // =====================================

        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,

                'mensagem' =>
                'Acção inválida para compras.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,

        'mensagem' =>
        $e->getMessage(),

        /*
         * Útil durante o desenvolvimento.
         * Remova linha e arquivo em produção.
         */

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}
