
<?php

require_once __DIR__
    . '/../models/PeriodoSalarial.php';

final class PeriodoSalarialController
{
    private PeriodoSalarial $periodoSalarial;


    public function __construct(PDO $pdo)
    {
        $this->periodoSalarial =
            new PeriodoSalarial($pdo);
    }


    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $filtros = []
    ): array {
        $filtrosNormalizados =
            $this->normalizarFiltros($filtros);

        return $this->periodoSalarial
            ->listar(
                $this->empresaId(),
                $filtrosNormalizados
            );
    }


    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id
    ): ?array {
        $this->validarId(
            $id,
            'Período salarial inválido.'
        );

        return $this->periodoSalarial
            ->buscarPorId(
                $id,
                $this->empresaId()
            );
    }


    // ==================================================
    // BUSCAR POR UUID
    // ==================================================

    public function buscarPorUuid(
        string $uuid
    ): ?array {
        $uuid = trim($uuid);

        if ($uuid === '') {
            throw new RuntimeException(
                'UUID do período salarial inválido.'
            );
        }

        return $this->periodoSalarial
            ->buscarPorUuid(
                $uuid,
                $this->empresaId()
            );
    }


    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        array $dados
    ): array {
        return $this->periodoSalarial
            ->criar(
                $dados,
                $this->empresaId(),
                $this->usuarioId()
            );
    }


    // ==================================================
    // ATUALIZAR
    // ==================================================

    public function atualizar(
        int $id,
        array $dados
    ): array {
        $this->validarId(
            $id,
            'Período salarial inválido.'
        );

        return $this->periodoSalarial
            ->atualizar(
                $id,
                $dados,
                $this->empresaId(),
                $this->usuarioId()
            );
    }


    // ==================================================
    // INICIAR PROCESSAMENTO
    // ==================================================

    public function iniciarProcessamento(
        int $id
    ): array {
        $this->validarId(
            $id,
            'Período salarial inválido.'
        );

        $alterado =
            $this->periodoSalarial
            ->iniciarProcessamento(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );
        if (!$alterado) {
            return $this->respostaErro(
                'Não foi possível iniciar o processamento. '
                    . 'Verifique se o período está no estado Aberto.'
            );
        }

        return $this->respostaSucesso(
            'Processamento salarial iniciado com sucesso.',
            $this->periodoSalarial->buscarPorId(
                $id,
                $this->empresaId()
            )
        );
    }


    // ==================================================
    // CONCLUIR PROCESSAMENTO
    // ==================================================

    public function concluirProcessamento(
        int $id
    ): array {
        $this->validarId(
            $id,
            'Período salarial inválido.'
        );

        $alterado =
            $this->periodoSalarial
            ->concluirProcessamento(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );

        if (!$alterado) {
            return $this->respostaErro(
                'Não foi possível concluir o processamento. '
                    . 'Verifique se o período está no estado Em Processamento.'
            );
        }

        return $this->respostaSucesso(
            'Processamento salarial concluído com sucesso.',
            $this->periodoSalarial->buscarPorId(
                $id,
                $this->empresaId()
            )
        );
    }


    // ==================================================
    // FECHAR
    // ==================================================

    public function fechar(
        int $id
    ): array {
        $this->validarId(
            $id,
            'Período salarial inválido.'
        );

        $alterado =
            $this->periodoSalarial
            ->fechar(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );

        if (!$alterado) {
            return $this->respostaErro(
                'Não foi possível fechar o período salarial. '
                    . 'Verifique se o período está no estado Processado.'
            );
        }

        return $this->respostaSucesso(
            'Período salarial fechado com sucesso.',
            $this->periodoSalarial->buscarPorId(
                $id,
                $this->empresaId()
            )
        );
    }


    // ==================================================
    // REABRIR
    // ==================================================

    public function reabrir(
        int $id
    ): array {
        $this->validarId(
            $id,
            'Período salarial inválido.'
        );

        $alterado =
            $this->periodoSalarial
            ->reabrir(
                $id,
                $this->empresaId(),
                $this->usuarioId()
            );

        if (!$alterado) {
            return $this->respostaErro(
                'Não foi possível reabrir o período salarial. '
                    . 'Verifique se o período está no estado Fechado.'
            );
        }

        return $this->respostaSucesso(
            'Período salarial reaberto com sucesso.',
            $this->periodoSalarial->buscarPorId(
                $id,
                $this->empresaId()
            )
        );
    }


    // ==================================================
    // CANCELAR
    // ==================================================

    public function cancelar(
        int $id,
        string $motivo
    ): array {
        $this->validarId(
            $id,
            'Período salarial inválido.'
        );

        $motivo = trim($motivo);

        if ($motivo === '') {
            return $this->respostaErro(
                'Informe o motivo do cancelamento.',
                [
                    'motivo_cancelamento' =>
                    'Informe o motivo do cancelamento.'
                ]
            );
        }

        if (mb_strlen($motivo, 'UTF-8') < 5) {
            return $this->respostaErro(
                'O motivo do cancelamento deve ter pelo menos 5 caracteres.',
                [
                    'motivo_cancelamento' =>
                    'Introduza pelo menos 5 caracteres.'
                ]
            );
        }

        if (mb_strlen($motivo, 'UTF-8') > 1000) {
            return $this->respostaErro(
                'O motivo do cancelamento não pode exceder 1000 caracteres.',
                [
                    'motivo_cancelamento' =>
                    'O limite máximo é de 1000 caracteres.'
                ]
            );
        }

        $alterado =
            $this->periodoSalarial
            ->cancelar(
                $id,
                $this->empresaId(),
                $motivo,
                $this->usuarioId()
            );

        if (!$alterado) {
            return $this->respostaErro(
                'Não foi possível cancelar o período salarial. '
                    . 'O período pode já estar cancelado.'
            );
        }

        return $this->respostaSucesso(
            'Período salarial cancelado com sucesso.',
            $this->periodoSalarial->buscarPorId(
                $id,
                $this->empresaId()
            )
        );
    }


    // ==================================================
    // LISTAR ESTADOS
    // ==================================================

    public function listarEstados(): array
    {
        return $this->periodoSalarial
            ->listarEstados();
    }


    // ==================================================
    // LISTAR ANOS
    // ==================================================

    public function listarAnos(): array
    {
        return $this->periodoSalarial
            ->listarAnos(
                $this->empresaId()
            );
    }


    // ==================================================
    // RESUMO
    // ==================================================

    public function resumo(): array
    {
        return $this->periodoSalarial
            ->resumo(
                $this->empresaId()
            );
    }


    // ==================================================
    // NORMALIZAR FILTROS
    // ==================================================

    private function normalizarFiltros(
        array $filtros
    ): array {
        $normalizados = [];

        if (
            isset($filtros['ano'])
            && $filtros['ano'] !== ''
        ) {
            $ano = (int)$filtros['ano'];

            if ($ano < 2000 || $ano > 2100) {
                throw new RuntimeException(
                    'O ano informado é inválido.'
                );
            }

            $normalizados['ano'] = $ano;
        }

        if (
            isset($filtros['mes'])
            && $filtros['mes'] !== ''
        ) {
            $mes = (int)$filtros['mes'];

            if ($mes < 1 || $mes > 12) {
                throw new RuntimeException(
                    'O mês informado é inválido.'
                );
            }

            $normalizados['mes'] = $mes;
        }

        if (
            isset($filtros['status_id'])
            && $filtros['status_id'] !== ''
        ) {
            $statusId =
                (int)$filtros['status_id'];

            if ($statusId <= 0) {
                throw new RuntimeException(
                    'O estado informado é inválido.'
                );
            }

            $normalizados['status_id'] =
                $statusId;
        }

        return $normalizados;
    }


    // ==================================================
    // VALIDAR ID
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
    // EMPRESA DA SESSÃO
    // ==================================================

    private function empresaId(): int
    {
        $empresaId = Auth::empresaId();

        if (
            $empresaId === null
            || (int)$empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        return (int)$empresaId;
    }


    // ==================================================
    // UTILIZADOR DA SESSÃO
    // ==================================================

    private function usuarioId(): int
    {
        $usuarioId = Auth::usuarioId();

        if (
            $usuarioId === null
            || (int)$usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        return (int)$usuarioId;
    }


    // ==================================================
    // RESPOSTA DE SUCESSO
    // ==================================================

    private function respostaSucesso(
        string $mensagem,
        mixed $dados = null
    ): array {
        $resposta = [
            'sucesso' => true,
            'mensagem' => $mensagem
        ];

        if ($dados !== null) {
            $resposta['dados'] = $dados;
        }

        return $resposta;
    }


    // ==================================================
    // RESPOSTA DE ERRO
    // ==================================================

    private function respostaErro(
        string $mensagem,
        array $erros = []
    ): array {
        $resposta = [
            'sucesso' => false,
            'mensagem' => $mensagem
        ];

        if ($erros !== []) {
            $resposta['erros'] = $erros;
        }

        return $resposta;
    }
}
