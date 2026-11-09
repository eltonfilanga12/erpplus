<?php

declare(strict_types=1);

final class AuditoriaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registrar(
        int $empresaId,
        ?int $usuarioId,
        string $tabela,
        int $registroId,
        string $operacao,
        ?array $dadosAnteriores = null,
        ?array $dadosNovos = null
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para auditoria.'
            );
        }

        if ($registroId <= 0) {
            throw new RuntimeException(
                'Registro inválido para auditoria.'
            );
        }

        $tabela =
            trim($tabela);

        if ($tabela === '') {
            throw new RuntimeException(
                'Tabela não informada para auditoria.'
            );
        }

        $operacao =
            strtoupper(
                trim($operacao)
            );

        if (
            !in_array(
                $operacao,
                [
                    'INSERT',
                    'UPDATE',
                    'DELETE'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Operação de auditoria inválida.'
            );
        }

        $stmt =
            $this->pdo->prepare("
                INSERT INTO auditoria
                (
                    empresa_id,
                    usuario_id,
                    tabela,
                    registro_id,
                    operacao,
                    dados_anteriores,
                    dados_novos
                )
                VALUES
                (
                    :empresa_id,
                    :usuario_id,
                    :tabela,
                    :registro_id,
                    :operacao,
                    :dados_anteriores,
                    :dados_novos
                )
            ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':usuario_id' =>
            $usuarioId,

            ':tabela' =>
            $tabela,

            ':registro_id' =>
            $registroId,

            ':operacao' =>
            $operacao,

            ':dados_anteriores' =>
            $dadosAnteriores !== null
                ? json_encode(
                    $dadosAnteriores,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                )
                : null,

            ':dados_novos' =>
            $dadosNovos !== null
                ? json_encode(
                    $dadosNovos,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                )
                : null
        ]);
    }

    public function insercao(
        int $empresaId,
        ?int $usuarioId,
        string $tabela,
        int $registroId,
        array $dadosNovos
    ): void {

        $this->registrar(
            $empresaId,
            $usuarioId,
            $tabela,
            $registroId,
            'INSERT',
            null,
            $dadosNovos
        );
    }

    public function atualizacao(
        int $empresaId,
        ?int $usuarioId,
        string $tabela,
        int $registroId,
        array $dadosAnteriores,
        array $dadosNovos
    ): void {

        $this->registrar(
            $empresaId,
            $usuarioId,
            $tabela,
            $registroId,
            'UPDATE',
            $dadosAnteriores,
            $dadosNovos
        );
    }

    public function exclusao(
        int $empresaId,
        ?int $usuarioId,
        string $tabela,
        int $registroId,
        array $dadosAnteriores
    ): void {

        $this->registrar(
            $empresaId,
            $usuarioId,
            $tabela,
            $registroId,
            'DELETE',
            $dadosAnteriores,
            null
        );
    }
}
