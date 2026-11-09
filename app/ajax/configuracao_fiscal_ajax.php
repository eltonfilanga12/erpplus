<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/ConfiguracaoFiscal.php';
require_once __DIR__ . '/../controllers/ConfiguracaoFiscalController.php';

Auth::requireLogin();

try {
    $pdo = Database::conn();
    $controller = new ConfiguracaoFiscalController($pdo);
    $acao = $_REQUEST['acao'] ?? '';

    switch ($acao) {
        case 'listar':
            echo json_encode(['success' => true, 'data' => $controller->listar($_REQUEST)]);
            break;

        case 'buscar':
            $resultado = $controller->buscar($_REQUEST);
            if (!$resultado) {
                throw new RuntimeException('Registo não encontrado.');
            }
            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        case 'guardar':
            $edicao = !empty($_POST['id']);
            $resultado = $controller->guardar($_POST);
            echo json_encode([
                'success' => $resultado,
                'mensagem' => $resultado ? ($edicao ? 'Configuração atualizada com sucesso.' : 'Configuração criada com sucesso.') : 'Não foi possível guardar.'
            ]);
            break;

        case 'calcular_taxa':
            $taxa = $controller->calcularTaxa($_REQUEST);
            echo json_encode(['success' => true, 'taxa' => $taxa]);
            break;

        case 'listar_status':
            echo json_encode(['success' => true, 'data' => $controller->listarStatus()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'mensagem' => 'Ação inválida.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensagem' => $e->getMessage()]);
}