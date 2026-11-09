<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Assiduidade.php';

final class AssiduidadeController
{
    private Assiduidade $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new Assiduidade(
                $pdo
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        array $dados = []
    ): array {

        $dataInicio =
            $this->textoOpcional(
                $dados['data_inicio']
                    ?? null
            );

        $dataFim =
            $this->textoOpcional(
                $dados['data_fim']
                    ?? null
            );

        return $this->model
            ->dadosIniciais(
                $this->empresaId(),
                $dataInicio,
                $dataFim
            );
    }

    // ==================================================
    // ASSIDUIDADE — GUARDAR EM LOTE
    // ==================================================

    public function guardarAssiduidadeLote(
        array $dados
    ): array {

        return $this->model
            ->guardarAssiduidadeLote(
                $this->empresaId(),
                $this->usuarioId(),
                $dados
            );
    }

    public function registarFalta(
        array $dados
    ): array {

        $funcionarioId =
            $this->inteiroObrigatorio(
                $dados['funcionario_id']
                    ?? null,
                'funcionário'
            );

        $data =
            $this->textoObrigatorio(
                $dados['data']
                    ?? null,
                'data da falta'
            );

        $assiduidadeId =
            $this->model
            ->registarFalta(
                $this->empresaId(),
                $this->usuarioId(),
                $funcionarioId,
                $data
            );

        return [
            'assiduidade_id' =>
            $assiduidadeId
        ];
    }
    // ==================================================
    // RESUMO
    // ==================================================

    public function resumo(
        array $dados
    ): array {

        $dataInicio =
            $this->textoObrigatorio(
                $dados['data_inicio']
                    ?? null,
                'data inicial'
            );

        $dataFim =
            $this->textoObrigatorio(
                $dados['data_fim']
                    ?? null,
                'data final'
            );

        return $this->model
            ->resumo(
                $this->empresaId(),
                $dataInicio,
                $dataFim
            );
    }

    // ==================================================
    // REGISTOS DE PONTO — LISTAR
    // ==================================================

    public function listarRegistosPonto(
        array $filtros = []
    ): array {

        return $this->model
            ->listarRegistosPonto(
                $this->empresaId(),
                $this->normalizarFiltros(
                    $filtros
                )
            );
    }

    // ==================================================
    // REGISTOS DE PONTO — BUSCAR
    // ==================================================

    public function buscarRegistoPonto(
        int $registoId
    ): array {

        $this->validarId(
            $registoId,
            'Registo de ponto'
        );

        return $this->model
            ->buscarRegistoPonto(
                $this->empresaId(),
                $registoId
            );
    }

    // ==================================================
    // REGISTOS DE PONTO — CRIAR
    // ==================================================

    public function criarRegistoPonto(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $registoId =
            $this->model
            ->criarRegistoPonto(
                $empresaId,
                $this->usuarioId(),
                $dados
            );

        return [
            'id' =>
            $registoId,

            'registo' =>
            $this->model
                ->buscarRegistoPonto(
                    $empresaId,
                    $registoId
                )
        ];
    }

    // ==================================================
    // REGISTOS DE PONTO — EDITAR
    // ==================================================

    public function editarRegistoPonto(
        int $registoId,
        array $dados
    ): array {

        $this->validarId(
            $registoId,
            'Registo de ponto'
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->editarRegistoPonto(
                $empresaId,
                $this->usuarioId(),
                $registoId,
                $dados
            );

        return [
            'id' =>
            $registoId,

            'registo' =>
            $this->model
                ->buscarRegistoPonto(
                    $empresaId,
                    $registoId
                )
        ];
    }

    // ==================================================
    // REGISTOS DE PONTO — VALIDAR
    // ==================================================

    public function validarRegistoPonto(
        int $registoId
    ): array {

        $this->validarId(
            $registoId,
            'Registo de ponto'
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->validarRegistoPonto(
                $empresaId,
                $this->usuarioId(),
                $registoId
            );

        return [
            'id' =>
            $registoId,

            'registo' =>
            $this->model
                ->buscarRegistoPonto(
                    $empresaId,
                    $registoId
                )
        ];
    }

    public function validarRegistosPontoLote(array $dados): array
    {
        $idsRecebidos = $dados['ids'] ?? [];

        if (!is_array($idsRecebidos)) {
            throw new InvalidArgumentException('A lista de registos é inválida.');
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $idsRecebidos),
            static fn(int $id): bool => $id > 0
        )));

        if (empty($ids)) {
            throw new InvalidArgumentException('Selecione pelo menos um registo.');
        }

        if (count($ids) > 500) {
            throw new InvalidArgumentException('Selecione no máximo 500 registos de cada vez.');
        }

        return $this->model->validarRegistosPontoLote(
            $this->empresaId(),
            $this->usuarioId(),
            $ids
        );
    }

    // ==================================================
    // REGISTOS DE PONTO — ANULAR
    // ==================================================

    public function anularRegistoPonto(
        int $registoId,
        array $dados = []
    ): array {

        $this->validarId(
            $registoId,
            'Registo de ponto'
        );

        $empresaId =
            $this->empresaId();

        $justificacao =
            $this->textoOpcional(
                $dados['justificacao']
                    ?? null
            );

        $this->model
            ->anularRegistoPonto(
                $empresaId,
                $this->usuarioId(),
                $registoId,
                $justificacao
            );

        return [
            'id' =>
            $registoId,

            'registo' =>
            $this->model
                ->buscarRegistoPonto(
                    $empresaId,
                    $registoId
                )
        ];
    }

    // ==================================================
    // ASSIDUIDADE — LISTAR
    // ==================================================

    public function listarAssiduidade(
        array $filtros = []
    ): array {

        return $this->model
            ->listarAssiduidade(
                $this->empresaId(),
                $this->normalizarFiltros(
                    $filtros
                )
            );
    }

    // ==================================================
    // ASSIDUIDADE — APURAR DIA
    // ==================================================

    public function apurarDia(
        array $dados
    ): array {

        $funcionarioId =
            $this->inteiroObrigatorio(
                $dados['funcionario_id']
                    ?? null,
                'funcionário'
            );

        $data =
            $this->textoObrigatorio(
                $dados['data']
                    ?? $dados['data_assiduidade']
                    ?? null,
                'data da assiduidade'
            );

        $empresaId =
            $this->empresaId();

        $assiduidadeId =
            $this->model
            ->apurarDia(
                $empresaId,
                $funcionarioId,
                $data,
                $this->usuarioId()
            );

        return [
            'id' =>
            $assiduidadeId,

            'assiduidade' =>
            $this->buscarAssiduidadeNaLista(
                $empresaId,
                $assiduidadeId
            )
        ];
    }

    // ==================================================
    // ASSIDUIDADE — APROVAR
    // ==================================================

    public function aprovarAssiduidade(
        int $assiduidadeId
    ): array {

        $this->validarId(
            $assiduidadeId,
            'Assiduidade diária'
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->aprovarAssiduidade(
                $empresaId,
                $this->usuarioId(),
                $assiduidadeId
            );

        return [
            'id' =>
            $assiduidadeId,

            'assiduidade' =>
            $this->buscarAssiduidadeNaLista(
                $empresaId,
                $assiduidadeId
            )
        ];
    }

    // ==================================================
    // ASSIDUIDADE — APROVAR EM LOTE
    // ==================================================

    public function aprovarAssiduidadesLote(
        array $dados
    ): array {

        $idsRecebidos =
            $dados['ids']
            ?? [];

        if (!is_array($idsRecebidos)) {

            throw new InvalidArgumentException(
                'A lista de assiduidades é inválida.'
            );
        }

        // ----------------------------------------------
        // NORMALIZAR IDS
        // ----------------------------------------------

        $ids =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $idsRecebidos
                        ),
                        static function (
                            int $id
                        ): bool {

                            return $id > 0;
                        }
                    )
                )
            );

        if (empty($ids)) {

            throw new InvalidArgumentException(
                'Selecione pelo menos uma assiduidade.'
            );
        }

        if (count($ids) > 500) {

            throw new InvalidArgumentException(
                'Selecione no máximo 500 registos de cada vez.'
            );
        }

        return $this->model
            ->aprovarAssiduidadesLote(
                $this->empresaId(),
                $this->usuarioId(),
                $ids
            );
    }

    // ==================================================
    // FALTAS — LISTAR
    // ==================================================

    public function listarFaltas(
        array $filtros = []
    ): array {

        return $this->model
            ->listarFaltas(
                $this->empresaId(),
                $this->normalizarFiltros(
                    $filtros
                )
            );
    }

    // ==================================================
    // FALTAS — JUSTIFICAR
    // ==================================================

    public function justificarFalta(
        int $faltaId,
        array $dados
    ): array {

        $this->validarId(
            $faltaId,
            'Falta'
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->justificarFalta(
                $empresaId,
                $this->usuarioId(),
                $faltaId,
                $dados
            );

        return [
            'id' =>
            $faltaId,

            'falta' =>
            $this->buscarFaltaNaLista(
                $empresaId,
                $faltaId
            )
        ];
    }

    // ==================================================
    // FALTAS — MARCAR INJUSTIFICADA
    // ==================================================

    public function marcarFaltaInjustificada(
        int $faltaId,
        array $dados = []
    ): array {

        $this->validarId(
            $faltaId,
            'Falta'
        );

        $empresaId =
            $this->empresaId();

        $observacoes =
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            );

        $this->model
            ->marcarFaltaInjustificada(
                $empresaId,
                $this->usuarioId(),
                $faltaId,
                $observacoes
            );

        return [
            'id' =>
            $faltaId,

            'falta' =>
            $this->buscarFaltaNaLista(
                $empresaId,
                $faltaId
            )
        ];
    }

    // ==================================================
    // HORAS EXTRAS — LISTAR
    // ==================================================

    public function listarHorasExtras(
        array $filtros = []
    ): array {

        return $this->model
            ->listarHorasExtras(
                $this->empresaId(),
                $this->normalizarFiltros(
                    $filtros
                )
            );
    }

    // ==================================================
    // HORAS EXTRAS — APROVAR
    // ==================================================

    public function aprovarHoraExtra(
        int $horaExtraId,
        array $dados
    ): array {

        $this->validarId(
            $horaExtraId,
            'Hora extra'
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->aprovarHoraExtra(
                $empresaId,
                $this->usuarioId(),
                $horaExtraId,
                $dados
            );

        return [
            'id' =>
            $horaExtraId,

            'hora_extra' =>
            $this->buscarHoraExtraNaLista(
                $empresaId,
                $horaExtraId
            )
        ];
    }

    // ==================================================
    // HORAS EXTRAS — REJEITAR
    // ==================================================

    public function rejeitarHoraExtra(
        int $horaExtraId,
        array $dados = []
    ): array {

        $this->validarId(
            $horaExtraId,
            'Hora extra'
        );

        $empresaId =
            $this->empresaId();

        $observacoes =
            $this->textoOpcional(
                $dados['observacoes']
                    ?? null
            );

        $this->model
            ->rejeitarHoraExtra(
                $empresaId,
                $this->usuarioId(),
                $horaExtraId,
                $observacoes
            );

        return [
            'id' =>
            $horaExtraId,

            'hora_extra' =>
            $this->buscarHoraExtraNaLista(
                $empresaId,
                $horaExtraId
            )
        ];
    }

    // ==================================================
    // NORMALIZAR FILTROS
    // ==================================================

    public function normalizarFiltros(
        array $dados
    ): array {

        return [
            'id' =>
            $this->inteiroOpcional(
                $dados['id']
                    ?? null
            ),

            'funcionario_id' =>
            $this->inteiroOpcional(
                $dados['funcionario_id']
                    ?? null
            ),

            'filial_id' =>
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            ),

            'status_id' =>
            $this->inteiroOpcional(
                $dados['status_id']
                    ?? null
            ),

            'data_inicio' =>
            $this->textoOpcional(
                $dados['data_inicio']
                    ?? null
            ),

            'data_fim' =>
            $this->textoOpcional(
                $dados['data_fim']
                    ?? null
            ),

            'pesquisa' =>
            $this->textoOpcional(
                $dados['pesquisa']
                    ?? null
            ),

            'tipo_registo' =>
            $this->textoOpcional(
                $dados['tipo_registo']
                    ?? null
            ),

            'origem' =>
            $this->textoOpcional(
                $dados['origem']
                    ?? null
            ),

            'ocorrencia' =>
            $this->textoOpcional(
                $dados['ocorrencia']
                    ?? null
            )
        ];
    }

    // ==================================================
    // BUSCAS NAS LISTAS
    // ==================================================

    private function buscarAssiduidadeNaLista(
        int $empresaId,
        int $assiduidadeId
    ): array {

        $registos =
            $this->model
            ->listarAssiduidade(
                $empresaId,
                [
                    'id' =>
                    $assiduidadeId
                ]
            );

        if (empty($registos)) {
            throw new RuntimeException(
                'Assiduidade diária não encontrada.'
            );
        }

        return $registos[0];
    }

    private function buscarFaltaNaLista(
        int $empresaId,
        int $faltaId
    ): array {

        $registos =
            $this->model
            ->listarFaltas(
                $empresaId,
                [
                    'id' =>
                    $faltaId
                ]
            );

        if (empty($registos)) {
            throw new RuntimeException(
                'Falta não encontrada.'
            );
        }

        return $registos[0];
    }

    private function buscarHoraExtraNaLista(
        int $empresaId,
        int $horaExtraId
    ): array {

        $registos =
            $this->model
            ->listarHorasExtras(
                $empresaId,
                [
                    'id' =>
                    $horaExtraId
                ]
            );

        if (empty($registos)) {
            throw new RuntimeException(
                'Hora extra não encontrada.'
            );
        }

        return $registos[0];
    }

    // ==================================================
    // EMPRESA DA SESSÃO
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

    // ==================================================
    // UTILIZADOR DA SESSÃO
    // ==================================================

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

    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarId(
        int $id,
        string $entidade
    ): void {

        if ($id <= 0) {
            throw new RuntimeException(
                $entidade . ' inválido.'
            );
        }
    }

    private function inteiroObrigatorio(
        mixed $valor,
        string $campo
    ): int {

        $inteiro =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $inteiro === false
            || $inteiro <= 0
        ) {
            throw new RuntimeException(
                'Selecione o ' . $campo . '.'
            );
        }

        return (int)$inteiro;
    }

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim((string)$valor) === ''
        ) {
            return null;
        }

        $inteiro =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $inteiro === false
            || $inteiro <= 0
        ) {
            return null;
        }

        return (int)$inteiro;
    }

    private function textoObrigatorio(
        mixed $valor,
        string $campo
    ): string {

        $texto =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        if ($texto === '') {
            throw new RuntimeException(
                'Informe a ' . $campo . '.'
            );
        }

        return $texto;
    }

    private function textoOpcional(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)(
                    $valor ?? ''
                )
            );

        return $texto !== ''
            ? $texto
            : null;
    }
}
