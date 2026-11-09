<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/EscalaoISPC.php';
require_once __DIR__ . '/../controllers/EscalaoISPCController.php';

Auth::requireLogin();

try {
    $pdo = Database::conn();
    $controller = new EscalaoISPCController($pdo);
    $acao = $_REQUEST['acao'] ?? '';

    switch ($acao) {
        case 'listar':
            echo json_encode(['success' => true, 'data' => $controller->listar($_REQUEST)]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'mensagem' => 'Ação inválida.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => $e->getMessage()]);
}
