<?php

declare(strict_types=1);

final class ApiController
{
    private static function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function status(): void
    {
        $tipo = $_GET['tipo'] ?? '';
        $tipo = trim($tipo);

        if ($tipo === '') {
            self::json(['ok' => false, 'erro' => 'Parâmetro "tipo" é obrigatório.'], 400);
        }

        $lista = Status::listarPorTipo($tipo);
        self::json(['ok' => true, 'data' => $lista]);
    }

    public static function categorias(): void
    {
        $lista = Categoria::listarTodas();
        self::json(['ok' => true, 'data' => $lista]);
    }
}
