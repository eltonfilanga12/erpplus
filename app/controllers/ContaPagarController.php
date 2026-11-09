<?php

declare(strict_types=1);

final class ContaPagarController
{
    // ==================================================
    // LISTAR
    // ==================================================

    public static function listar(): array
    {
        return ContaPagar::listar(
            self::empresaId(),
            self::filialId()
        );
    }

    // ==================================================
    // OBTER
    // ==================================================

    public static function obter(
        int $id
    ): ?array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Conta a pagar inválida.'
            );
        }

        return ContaPagar::buscarPorId(
            $id,
            self::empresaId()
        );
    }

    // ==================================================
    // GUARDAR
    // ==================================================

    public static function guardar(
        array $dados
    ): int|bool {

        $dados['empresa_id'] =
            self::empresaId();

        $dados['usuario_id'] =
            self::usuarioId();

        /*
         * Utilizador associado a uma filial
         * não pode criar a conta noutra filial.
         */

        $filialSessao =
            self::filialId();

        if ($filialSessao !== null) {
            $dados['filial_id'] =
                $filialSessao;
        }

        if (!empty($dados['id'])) {
            return ContaPagar::atualizar(
                $dados
            );
        }

        return ContaPagar::criar(
            $dados
        );
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public static function eliminar(
        int $id
    ): bool {

        if ($id <= 0) {
            throw new RuntimeException(
                'Conta a pagar inválida.'
            );
        }

        return ContaPagar::eliminar(
            $id,
            self::empresaId(),
            self::usuarioId()
        );
    }

    // ==================================================
    // PAGAR
    // ==================================================

    public static function pagar(
        array $dados,
        array $ficheiros = []
    ): bool {

        $dados['empresa_id'] = self::empresaId();
        $dados['usuario_id'] = self::usuarioId();

        /*
     * Tratamento de Upload do Comprovativo
     */
        if (
            isset($ficheiros['comprovativo'])
            && $ficheiros['comprovativo']['error'] === UPLOAD_ERR_OK
        ) {
            $dados['comprovativo'] = self::uploadComprovativo($ficheiros['comprovativo']);
        } else {
            $dados['comprovativo'] = null;
        }

        /*
     * A filial e o centro de custo corretos serão
     * herdados da conta dentro do Model.
     */
        return ContaPagar::registrarPagamento(
            $dados
        );
    }

    /**
     * Auxiliar para upload do comprovativo
     */
    private static function uploadComprovativo(array $file): string
    {
        $extensoesPermitidas = ['pdf', 'png', 'jpg', 'jpeg'];
        $tamanhoMaximo = 5 * 1024 * 1024; // 5MB

        if ($file['size'] > $tamanhoMaximo) {
            throw new RuntimeException('O comprovativo não pode exceder 5MB.');
        }

        $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, $extensoesPermitidas, true)) {
            throw new RuntimeException('Formato de ficheiro inválido. Use PDF, PNG ou JPG.');
        }

        $diretorio = dirname(__DIR__, 2)
            . '/public/uploads/comprovativos/';

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }

        $nomeFicheiro = sprintf(
            'comprovativo_%d_%s.%s',
            time(),
            bin2hex(random_bytes(4)),
            $extensao
        );

        $caminhoDestino = $diretorio . $nomeFicheiro;

        if (!move_uploaded_file($file['tmp_name'], $caminhoDestino)) {
            throw new RuntimeException('Erro ao guardar o ficheiro do comprovativo.');
        }

        return 'public/uploads/comprovativos/' . $nomeFicheiro;
    }

    public static function getHistoricoPagamentos(int $id): array
    {
        if ($id <= 0) {
            throw new RuntimeException('ID de conta a pagar inválido.');
        }

        $empresaId = self::empresaId();

        $conta = ContaPagar::buscarPorId($id, $empresaId);

        if (!$conta) {
            throw new RuntimeException('Conta a pagar não encontrada.');
        }

        $historico = ContaPagar::getHistoricoPagamentosByContaId($id, $empresaId);

        return [
            'conta' => $conta,
            'historico_pagamentos' => $historico
        ];
    }

    // ==================================================
    // EMPRESA
    // ==================================================

    private static function empresaId(): int
    {
        $empresaId =
            Auth::empresaId();

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        return (int)$empresaId;
    }

    // ==================================================
    // UTILIZADOR
    // ==================================================

    private static function usuarioId(): int
    {
        $usuarioId =
            Auth::usuarioId();

        if (
            $usuarioId === null
            || $usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        return (int)$usuarioId;
    }

    // ==================================================
    // FILIAL
    // ==================================================

    private static function filialId(): ?int
    {
        $filialId =
            Auth::filialId();

        return (
            $filialId !== null
            && $filialId > 0
        )
            ? (int)$filialId
            : null;
    }
}
