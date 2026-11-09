<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/ObrigacaoFiscal.php';
require_once __DIR__ . '/../controllers/ObrigacaoFiscalController.php';

function responderObrigacao(array $r, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

Auth::iniciarSessao();
if (!Auth::check()) {
    responderObrigacao(['success' => false, 'message' => 'Sessão expirada.', 'data' => null], 401);
}

try {
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    // Unifica parâmetros passados via $_GET e $_POST (multipart/form-data ou x-www-form-urlencoded)
    $dados = array_merge($_GET, $_POST);

    // Tratamento para requisições em formato JSON puro
    if (str_contains(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
        $json = json_decode((string)file_get_contents('php://input'), true);
        if (is_array($json)) {
            $dados = $json;
        }
    }

    // Anexa ficheiros se houver upload
    if (!empty($_FILES)) {
        $dados['FILES'] = $_FILES;
    }

    $empresaId = (int)(Auth::empresaId() ?? 0);
    $usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
    $filialBruta = $_SESSION['filial_id'] ?? $_SESSION['usuario']['filial_id'] ?? null;
    $filialId = $filialBruta === null || $filialBruta === '' ? null : (int)$filialBruta;
    if ($empresaId <= 0 || $usuarioId <= 0) throw new RuntimeException('Sessão inválida.');

    $controller = new ObrigacaoFiscalController(new ObrigacaoFiscal(Database::conn()));
    $acao = strtolower(trim((string)($dados['acao'] ?? '')));
    $resposta = match ($acao) {
        'dados_iniciais' => $controller->dadosIniciais($empresaId, $filialId),
        'listar' => $controller->listar($empresaId, $filialId, $dados),
        'pagamentos' => $controller->listarPagamentos($empresaId, $filialId, $dados),
        'pagar' => $metodo === 'POST'
            ? $controller->pagar($empresaId, $filialId, $usuarioId, $dados)
            : ['success' => false, 'message' => 'Método não permitido.', 'data' => null],
        default => ['success' => false, 'message' => 'Ação inválida.', 'data' => null]
    };
    responderObrigacao($resposta, !empty($resposta['success']) ? 200 : 422);
} catch (Throwable $e) {
    error_log('[obrigacoes_fiscais_ajax] ' . $e->getMessage());
    responderObrigacao(['success' => false, 'message' => $e->getMessage(), 'data' => null], 500);
}
