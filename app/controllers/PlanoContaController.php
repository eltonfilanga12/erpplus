<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/PlanoConta.php';

require_once __DIR__
    . '/../scripts/importar_pgc_nirf.php';

final class PlanoContaController
{
    private PlanoConta $planoConta;

    public function __construct(PDO $pdo)
    {
        $this->planoConta =
            new PlanoConta($pdo);
    }

    // ==================================================
    // PLANOS DE CONTAS
    // ==================================================

    public function listarPlanos(): array
    {
        return $this->planoConta
            ->listarPlanos(
                $this->empresaId()
            );
    }

    public function buscarPlano(
        int $id
    ): ?array {

        $this->validarId(
            $id,
            'Plano de contas inválido.'
        );

        return $this->planoConta
            ->buscarPlano(
                $id,
                $this->empresaId()
            );
    }

    public function guardarPlano(
        array $dados
    ): int {

        $dados['empresa_id'] =
            $this->empresaId();

        $dados['usuario_id'] =
            $this->usuarioId();

        unset(
            $dados['filial_id'],
            $dados['ativo']
        );

        return $this->planoConta
            ->guardarPlano(
                $dados
            );
    }

    public function alterarStatusPlano(
        int $id,
        int $statusId
    ): bool {

        $this->validarId(
            $id,
            'Plano de contas inválido.'
        );

        $this->validarId(
            $statusId,
            'Status do plano inválido.'
        );

        return $this->planoConta
            ->alterarStatusPlano(
                $id,
                $this->empresaId(),
                $statusId,
                $this->usuarioId()
            );
    }

    public function eliminarPlano(
        int $id
    ): bool {

        $this->validarId(
            $id,
            'Plano de contas inválido.'
        );

        return $this->planoConta
            ->eliminarPlano(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );
    }

    // ==================================================
    // IMPORTAR PGC-NIRF
    // ==================================================

    public function importarPgcNirf(
        int $planoId
    ): array {

        $this->validarId(
            $planoId,
            'Plano de contas inválido.'
        );


        /*
     * Garante que o plano pertence
     * à empresa atual.
     */

        $plano =
            $this->planoConta
            ->buscarPlano(
                $planoId,
                $this->empresaId()
            );


        if (!$plano) {
            throw new RuntimeException(
                'Plano de contas não encontrado para esta empresa.'
            );
        }


        /*
     * O importador espera um status
     * da conta contabilística.
     */

        $statusConta =
            $this->statusContaAtiva();


        $contas =
            require __DIR__
            . '/../scripts/dados_pgc_nirf.php';


        if (
            !is_array($contas)
            || empty($contas)
        ) {
            throw new RuntimeException(
                'Os dados do PGC-NIRF não foram encontrados.'
            );
        }


        $importador =
            new ImportadorPgcNirf(
                $this->empresaId(),
                $planoId,
                $statusConta,
                $this->usuarioId()
            );


        return $importador
            ->importar(
                $contas
            );
    }

    private function statusContaAtiva(): int
    {
        $status =
            $this->planoConta
            ->listarStatus(
                'conta_contabilistica'
            );


        foreach ($status as $item) {

            $nome =
                mb_strtolower(
                    trim(
                        (string)(
                            $item['nome']
                            ?? ''
                        )
                    )
                );


            if (
                in_array(
                    $nome,
                    [
                        'ativo',
                        'activo',
                        'ativa',
                        'activa'
                    ],
                    true
                )
            ) {
                return (int)$item['id'];
            }
        }


        throw new RuntimeException(
            'Não existe um status ativo para contas contabilísticas.'
        );
    }

    // ==================================================
    // CONTAS CONTABILÍSTICAS
    // ==================================================

    public function listarContas(
        int $planoId
    ): array {

        $this->validarId(
            $planoId,
            'Plano de contas inválido.'
        );

        return $this->planoConta
            ->listarContas(
                $planoId,
                $this->empresaId()
            );
    }

    public function listarArvore(
        int $planoId
    ): array {

        $this->validarId(
            $planoId,
            'Plano de contas inválido.'
        );

        return $this->planoConta
            ->listarArvore(
                $planoId,
                $this->empresaId()
            );
    }

    public function buscarConta(
        int $id
    ): ?array {

        $this->validarId(
            $id,
            'Conta contabilística inválida.'
        );

        return $this->planoConta
            ->buscarConta(
                $id,
                $this->empresaId()
            );
    }

    public function guardarConta(
        array $dados
    ): int {

        $dados['empresa_id'] =
            $this->empresaId();

        $dados['usuario_id'] =
            $this->usuarioId();

        unset(
            $dados['filial_id'],
            $dados['ativo']
        );

        return $this->planoConta
            ->guardarConta(
                $dados
            );
    }

    public function alterarStatusConta(
        int $id,
        int $statusId
    ): bool {

        $this->validarId(
            $id,
            'Conta contabilística inválida.'
        );

        $this->validarId(
            $statusId,
            'Status da conta inválido.'
        );

        return $this->planoConta
            ->alterarStatusConta(
                $id,
                $this->empresaId(),
                $statusId,
                $this->usuarioId()
            );
    }

    public function eliminarConta(
        int $id
    ): bool {

        $this->validarId(
            $id,
            'Conta contabilística inválida.'
        );

        return $this->planoConta
            ->eliminarConta(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );
    }

    public function contasParaSelect(
        int $planoId,
        bool $somenteAnaliticas = false
    ): array {

        $this->validarId(
            $planoId,
            'Plano de contas inválido.'
        );

        return $this->planoConta
            ->contasParaSelect(
                $planoId,
                $this->empresaId(),
                $somenteAnaliticas
            );
    }

    // ==================================================
    // STATUS
    // ==================================================

    public function statusPlanos(): array
    {
        return $this->planoConta
            ->listarStatus(
                'plano_contas'
            );
    }

    public function statusContas(): array
    {
        return $this->planoConta
            ->listarStatus(
                'conta_contabilistica'
            );
    }

    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarId(
        int $id,
        string $mensagem
    ): void {

        if ($id <= 0) {
            throw new RuntimeException(
                $mensagem
            );
        }
    }

    // ==================================================
    // DADOS DA SESSÃO
    // ==================================================

    private function empresaId(): int
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

    private function usuarioId(): int
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
}
