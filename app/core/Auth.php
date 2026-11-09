<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../services/LogService.php';

final class Auth
{
    public static function iniciarSessao(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function login(array $usuario): void
    {
        self::iniciarSessao();

        session_regenerate_id(true);

        $_SESSION['usuario_id'] =
            (int)$usuario['id'];

        $_SESSION['usuario_nome'] =
            $usuario['nome'] ?? '';

        $_SESSION['usuario_email'] =
            $usuario['email'] ?? '';

        $_SESSION['perfil_id'] =
            (int)$usuario['perfil_id'];

        $_SESSION['empresa_id'] =
            !empty($usuario['empresa_id'])
            ? (int)$usuario['empresa_id']
            : null;

        $_SESSION['filial_id'] =
            !empty($usuario['filial_id'])
            ? (int)$usuario['filial_id']
            : null;


        /*
    |--------------------------------------------------------------------------
    | Log de Login
    |--------------------------------------------------------------------------
    */

        self::registrarLogAutenticacao(
            'LOGIN',
            'Iniciou sessão no sistema.'
        );
    }

    public static function logout(): void
    {
        self::iniciarSessao();


        /*
    |--------------------------------------------------------------------------
    | Log de Logout
    |--------------------------------------------------------------------------
    |
    | Deve ser executado ANTES de destruir a sessão.
    |
    */

        if (
            !empty($_SESSION['usuario_id'])
            && !empty($_SESSION['empresa_id'])
        ) {
            self::registrarLogAutenticacao(
                'LOGOUT',
                'Terminou a sessão no sistema.'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Limpar Sessão
    |--------------------------------------------------------------------------
    */

        $_SESSION = [];


        /*
    |--------------------------------------------------------------------------
    | Eliminar Cookie da Sessão
    |--------------------------------------------------------------------------
    */

        if (
            ini_get(
                'session.use_cookies'
            )
        ) {
            $params =
                session_get_cookie_params();


            setcookie(
                session_name(),
                '',
                [
                    'expires' =>
                    time() - 42000,

                    'path' =>
                    $params['path'],

                    'domain' =>
                    $params['domain'],

                    'secure' =>
                    (bool)$params['secure'],

                    'httponly' =>
                    (bool)$params['httponly'],

                    'samesite' =>
                    $params['samesite']
                        ?? 'Lax'
                ]
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Destruir Sessão
    |--------------------------------------------------------------------------
    */

        session_destroy();

        session_write_close();
    }

    public static function check(): bool
    {
        self::iniciarSessao();

        return !empty($_SESSION['usuario_id']);
    }

    public static function id(): ?int
    {
        self::iniciarSessao();

        return $_SESSION['usuario_id'] ?? null;
    }

    public static function nome(): ?string
    {
        self::iniciarSessao();

        return $_SESSION['usuario_nome'] ?? null;
    }

    public static function email(): ?string
    {
        self::iniciarSessao();

        return $_SESSION['usuario_email'] ?? null;
    }

    public static function perfilId(): ?int
    {
        self::iniciarSessao();

        return $_SESSION['perfil_id'] ?? null;
    }

    public static function filialId(): ?int
    {
        self::iniciarSessao();

        return !empty($_SESSION['filial_id'])
            ? (int)$_SESSION['filial_id']
            : null;
    }

    public static function empresaId(): ?int
    {
        self::iniciarSessao();

        return !empty($_SESSION['empresa_id'])
            ? (int)$_SESSION['empresa_id']
            : null;
    }

    public static function usuarioId(): ?int
    {
        self::iniciarSessao();

        return !empty($_SESSION['usuario_id'])
            ? (int)$_SESSION['usuario_id']
            : null;
    }

    public static function requireLogin(string $redirect = '/erpplus/index.php?page=login'): void
    {
        if (!self::check()) {

            header('Location: ' . $redirect);

            exit;
        }
    }

    public static function requirePerfis(array $perfisPermitidos): void
    {
        self::requireLogin();

        if (!in_array(self::perfilId(), $perfisPermitidos, true)) {

            header('Location: /erpplus/index.php?page=dashboard');

            exit;
        }
    }

    /**
     * Verifica se o utilizador possui acesso ao módulo.
     */
    public static function can(string $slug): bool
    {
        self::requireLogin();

        return Permissao::possuiModulo(
            self::perfilId(),
            $slug
        );
    }

    /**
     * Obriga acesso ao módulo.
     */
    public static function requireModulo(string $slug): void
    {
        self::requireLogin();

        if (!self::can($slug)) {

            http_response_code(403);

            exit('Acesso negado.');
        }
    }

    /**
     * Retorna o menu permitido para o perfil.
     */
    public static function menu(): array
    {
        self::requireLogin();

        return Menu::listarPorPerfil(
            self::perfilId()
        );
    }

    private static function registrarLogAutenticacao(
        string $acao,
        string $descricao
    ): void {
        try {

            $empresaId =
                !empty($_SESSION['empresa_id'])
                ? (int)$_SESSION['empresa_id']
                : 0;


            $usuarioId =
                !empty($_SESSION['usuario_id'])
                ? (int)$_SESSION['usuario_id']
                : null;


            $filialId =
                !empty($_SESSION['filial_id'])
                ? (int)$_SESSION['filial_id']
                : null;


            if (
                $empresaId <= 0
                || $usuarioId === null
            ) {
                return;
            }


            $log =
                new LogService(
                    Database::conn()
                );


            $log->registrar(
                $empresaId,
                $usuarioId,
                'AUTENTICACAO',
                $acao,
                $descricao,
                $usuarioId,
                $filialId
            );
        } catch (Throwable $e) {

            /*
         * Uma falha no Log nunca deve impedir
         * o utilizador de entrar ou sair.
         */

            error_log(
                sprintf(
                    '[Auth][%s] Falha ao registrar log: %s em %s:%d',
                    $acao,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );
        }
    }
}
