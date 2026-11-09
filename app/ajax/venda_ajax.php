<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Venda.php';

require_once __DIR__
    . '/../controllers/VendaController.php';

Auth::iniciarSessao();

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
        new VendaController($pdo);

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

            $venda =
                $controller->buscar($id);

            if (!$venda) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'mensagem' =>
                    'Venda não encontrada.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            echo json_encode([
                'success' => true,
                'data' => [

                    'venda' =>
                    $venda,

                    'itens' =>
                    $controller
                        ->buscarItens($id),

                    'parcelas' =>
                    $controller
                        ->buscarParcelas($id)
                ]
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =====================================
        // PRÓXIMO NÚMERO
        // =====================================

        case 'proximo_numero':
        case 'proximo_numero_factura':

            $tipoDocumento =
                trim(
                    (string)(
                        $_GET['tipo_documento']
                        ?? 'FACTURA'
                    )
                );

            $dataVenda =
                !empty($_GET['data_venda'])
                ? (string)$_GET['data_venda']
                : null;

            $numero =
                $controller->proximoNumero(
                    $tipoDocumento,
                    $dataVenda
                );

            echo json_encode([
                'success' => true,
                'data' => [
                    'numero' => $numero
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

            if (
                isset($dados['itens'])
                && is_string($dados['itens'])
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
                        'A lista de produtos da venda é inválida.'
                    );
                }

                $dados['itens'] =
                    is_array($itens)
                    ? $itens
                    : [];
            } else {
                $dados['itens'] = [];
            }

            $vendaId =
                $controller->salvar(
                    $dados
                );

            $edicao =
                !empty($_POST['id']);

            echo json_encode([
                'success' => true,

                'mensagem' =>
                $edicao
                    ? 'Venda actualizada com sucesso.'
                    : 'Venda criada com sucesso.',

                'data' => [
                    'id' => $vendaId
                ]
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
                'success' => $resultado,

                'mensagem' =>
                $resultado
                    ? 'Venda eliminada com sucesso.'
                    : 'Não foi possível eliminar a venda.'
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =====================================
        // CLIENTES
        // =====================================

        case 'clientes':

            echo json_encode([
                'success' => true,
                'data' =>
                $controller->clientes()
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =====================================
        // ARMAZÉNS
        // =====================================

        case 'armazens':

            echo json_encode([
                'success' => true,
                'data' =>
                $controller->armazens()
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =====================================
        // PRODUTOS
        // =====================================

        case 'produtos':

            echo json_encode([
                'success' => true,
                'data' =>
                $controller->produtos()
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
                $controller->status()
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
                'Acção inválida para vendas.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'mensagem' =>
        $e->getMessage(),
        'linha' =>
        $e->getLine(),
        'arquivo' =>
        $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}
