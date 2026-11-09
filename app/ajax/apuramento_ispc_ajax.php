<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/ApuramentoISPC.php';
require_once __DIR__ . '/../controllers/ApuramentoISPCController.php';

Auth::requireLogin();

try {
    $pdo = Database::conn();
    $controller = new ApuramentoISPCController($pdo);
    $acao = $_REQUEST['acao'] ?? '';

    switch ($acao) {
        case 'listar':
            echo json_encode(['success' => true, 'data' => $controller->listar($_REQUEST)]);
            break;

        case 'buscar':
            $resultado = $controller->buscar($_REQUEST);
            if (!$resultado) {
                throw new RuntimeException('Apuramento não encontrado.');
            }
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        case 'apurar':
            $resultado = $controller->apurar($_POST);
            echo json_encode([
                'success' => $resultado,
                'mensagem' => $resultado ? 'Apuramento de ISPC efetuado com sucesso.' : 'Não foi possível efetuar o apuramento.'
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'mensagem' => 'Ação inválida.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => $e->getMessage()]);
}