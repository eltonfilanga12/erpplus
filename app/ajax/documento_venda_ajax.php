
<?php


header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Venda.php';
require_once __DIR__ . '/../models/DocumentoVenda.php';
require_once __DIR__ . '/../controllers/DocumentoVendaController.php';

Auth::iniciarSessao();

if (!Auth::check()) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'mensagem' => 'Sessão expirada. Faça login novamente.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);

        echo json_encode([
            'success' => false,
            'mensagem' => 'Método não permitido.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $vendaId = (int)($_GET['id'] ?? 0);

    if ($vendaId <= 0) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'mensagem' => 'Identificador da venda inválido.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $controller = new DocumentoVendaController(
        Database::conn()
    );

    $dados = $controller->obterFactura($vendaId);

    echo json_encode([
        'success' => true,
        'data' => $dados
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'mensagem' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
