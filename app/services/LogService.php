<?php

declare(strict_types=1);

final class LogService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registrar(
        int $empresaId,
        ?int $usuarioId,
        string $modulo,
        string $acao,
        ?string $descricao = null,
        ?int $registroId = null,
        ?int $filialId = null
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para o log.'
            );
        }

        $modulo =
            strtoupper(
                trim($modulo)
            );

        $acao =
            strtoupper(
                trim($acao)
            );

        if ($modulo === '') {
            throw new RuntimeException(
                'Módulo não informado para o log.'
            );
        }

        if ($acao === '') {
            throw new RuntimeException(
                'Ação não informada para o log.'
            );
        }

        $ip =
            $this->obterIp();

        $userAgent =
            trim(
                (string)(
                    $_SERVER['HTTP_USER_AGENT']
                    ?? ''
                )
            );

        $metodoHttp =
            strtoupper(
                trim(
                    (string)(
                        $_SERVER['REQUEST_METHOD']
                        ?? ''
                    )
                )
            );

        $rota =
            trim(
                (string)(
                    $_SERVER['REQUEST_URI']
                    ?? ''
                )
            );

        $stmt =
            $this->pdo->prepare("
                INSERT INTO logs
                (
                    empresa_id,
                    filial_id,
                    usuario_id,
                    modulo,
                    acao,
                    descricao,
                    registro_id,
                    ip,
                    user_agent,
                    metodo_http,
                    rota
                )
                VALUES
                (
                    :empresa_id,
                    :filial_id,
                    :usuario_id,
                    :modulo,
                    :acao,
                    :descricao,
                    :registro_id,
                    :ip,
                    :user_agent,
                    :metodo_http,
                    :rota
                )
            ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':filial_id' =>
            $filialId,

            ':usuario_id' =>
            $usuarioId,

            ':modulo' =>
            $modulo,

            ':acao' =>
            $acao,

            ':descricao' =>
            $descricao !== null
                ? trim($descricao)
                : null,

            ':registro_id' =>
            $registroId,

            ':ip' =>
            $ip,

            ':user_agent' =>
            $userAgent !== ''
                ? mb_substr(
                    $userAgent,
                    0,
                    500
                )
                : null,

            ':metodo_http' =>
            $metodoHttp !== ''
                ? mb_substr(
                    $metodoHttp,
                    0,
                    10
                )
                : null,

            ':rota' =>
            $rota !== ''
                ? mb_substr(
                    $rota,
                    0,
                    255
                )
                : null
        ]);
    }

    private function obterIp(): ?string
    {
        $ip =
            trim(
                (string)(
                    $_SERVER['REMOTE_ADDR']
                    ?? ''
                )
            );

        return $ip !== ''
            ? $ip
            : null;
    }
}
