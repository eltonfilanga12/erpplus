<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/ContabilidadeService.php';
require_once __DIR__ . '/../models/PagamentoIva.php';
require_once __DIR__ . '/../controllers/PagamentoIvaController.php';

function responderPagamentoIva(array $resposta, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($metodo, ['GET', 'POST'], true)) {
        responderPagamentoIva(['success' => false, 'message' => 'Método não permitido.', 'data' => null], 405);
    }

    $payload = array_merge($_GET, $_POST);
    if ($metodo === 'POST' && empty($_POST) && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $json = json_decode((string)file_get_contents('php://input'), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $payload = $json;
        }
    }
    // Anexar ficheiros enviados ao payload
    if (!empty($_FILES)) {
        $payload['FILES'] = $_FILES;
    }

    $empresaId = (int)($_SESSION['empresa_id'] ?? $_SESSION['usuario']['empresa_id'] ?? 0);
    $usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
    $filialBruta = $_SESSION['filial_id'] ?? $_SESSION['usuario']['filial_id'] ?? null;
    $filialId = !empty($filialBruta) ? (int)$filialBruta : null;

    if ($empresaId <= 0 || $usuarioId <= 0) {
        responderPagamentoIva(['success' => false, 'message' => 'Sessão inválida.', 'data' => null], 401);
    }

    $acao = strtolower(trim((string)($payload['acao'] ?? '')));
    $controller = new PagamentoIvaController(Database::conn());

    $resposta = match ($acao) {
        'dados_iniciais' => $controller->dadosIniciais($empresaId, $filialId),
        'listar' => $controller->listar($empresaId, $filialId),
        'calcular' => $controller->calcular($empresaId, $filialId, $payload),
        'pagamentos' => $controller->pagamentos($empresaId, $payload),
        'anular' => $metodo === 'POST'
            ? $controller->anular(
                $empresaId,
                $filialId,
                $payload
            )
            : [
                'success' => false,
                'message' => 'Método não permitido.',
                'data' => null
            ],
        'apurar' => $metodo === 'POST'
            ? $controller->apurar($empresaId, $filialId, $usuarioId, $payload)
            : ['success' => false, 'message' => 'Método não permitido.', 'data' => null],
        'pagar' => $metodo === 'POST'
            ? $controller->pagar($empresaId, $filialId, $usuarioId, $payload)
            : ['success' => false, 'message' => 'Método não permitido.', 'data' => null],
        default => ['success' => false, 'message' => 'Ação inválida.', 'data' => null]
    };

    responderPagamentoIva($resposta, !empty($resposta['success']) ? 200 : 422);
} catch (Throwable $e) {
    error_log('[pagamento_iva_ajax] ' . $e->getMessage());
    responderPagamentoIva(['success' => false, 'message' => $e->getMessage(), 'data' => null], 500);
}
