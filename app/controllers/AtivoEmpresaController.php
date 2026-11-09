<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/AtivoEmpresa.php';

final class AtivoEmpresaController
{
    private AtivoEmpresa $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new AtivoEmpresa(
                $pdo
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        return $this->model
            ->dadosIniciais(
                $this->empresaId()
            );
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $dados = []
    ): array {

        $filtros =
            $this->normalizarFiltros(
                $dados
            );

        return $this->model
            ->listar(
                $this->empresaId(),
                $filtros
            );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $ativoId
    ): array {

        $this->validarId(
            $ativoId
        );

        $ativo =
            $this->model->buscar(
                $this->empresaId(),
                $ativoId
            );

        if ($ativo === null) {

            throw new RuntimeException(
                'Ativo não encontrado.'
            );
        }

        return $ativo;
    }

    // ==================================================
    // PRÓXIMO CÓDIGO
    // ==================================================

    public function proximoCodigo(): array
    {
        return [
            'codigo' =>
            $this->model
                ->proximoCodigo(
                    $this->empresaId()
                )
        ];
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        array $dados
    ): array {

        $ativoId =
            $this->model->criar(
                $this->empresaId(),
                $this->usuarioId(),
                $dados
            );

        return [
            'id' =>
            $ativoId,

            'ativo' =>
            $this->model->buscar(
                $this->empresaId(),
                $ativoId
            )
        ];
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $ativoId,
        array $dados
    ): array {

        $this->validarId(
            $ativoId
        );

        $this->model->editar(
            $this->empresaId(),
            $this->usuarioId(),
            $ativoId,
            $dados
        );

        return [
            'id' =>
            $ativoId,

            'ativo' =>
            $this->model->buscar(
                $this->empresaId(),
                $ativoId
            )
        ];
    }

    // ==================================================
    // LISTAR PARA DEPRECIAÇÃO
    // ==================================================

    public function listarDepreciacoes(
        array $dados
    ): array {

        return $this->model
            ->listarParaDepreciacao(
                $this->empresaId(),
                [
                    'ano' =>
                    (int)(
                        $dados['ano']
                        ?? date('Y')
                    ),

                    'mes' =>
                    (int)(
                        $dados['mes']
                        ?? date('n')
                    ),

                    'tipo' =>
                    $dados['tipo']
                        ?? null,

                    'pesquisa' =>
                    $dados['pesquisa']
                        ?? null
                ]
            );
    }


    // ==================================================
    // PROCESSAR UM ATIVO
    // ==================================================

    public function processarDepreciacao(
        array $dados
    ): array {

        $ativoId =
            (int)(
                $dados['ativo_id']
                ?? $dados['id']
                ?? 0
            );


        if ($ativoId <= 0) {

            throw new RuntimeException(
                'Ativo inválido.'
            );
        }


        return $this->model
            ->processarDepreciacao(
                $this->empresaId(),
                $this->usuarioId(),
                $ativoId,
                (int)(
                    $dados['ano']
                    ?? date('Y')
                ),
                (int)(
                    $dados['mes']
                    ?? date('n')
                )
            );
    }


    // ==================================================
    // PROCESSAR LOTE
    // ==================================================

    public function processarDepreciacoesLote(
        array $dados
    ): array {

        $ativos =
            $dados['ativos']
            ?? [];


        if (is_string($ativos)) {

            $ativos =
                json_decode(
                    $ativos,
                    true
                );
        }


        if (
            !is_array($ativos)
            || empty($ativos)
        ) {

            throw new RuntimeException(
                'Selecione pelo menos um ativo.'
            );
        }


        return $this->model
            ->processarDepreciacoesLote(
                $this->empresaId(),
                $this->usuarioId(),
                $ativos,
                (int)(
                    $dados['ano']
                    ?? date('Y')
                ),
                (int)(
                    $dados['mes']
                    ?? date('n')
                )
            );
    }

    // ==================================================
    // CONFIGURAÇÕES CONTABILÍSTICAS
    // ==================================================

    public function configuracoesContabeis(): array
    {
        return $this->model
            ->listarConfiguracoesContabeis(
                $this->empresaId()
            );
    }


    public function guardarConfiguracaoContabil(
        array $dados
    ): array {

        $id =
            $this->model
            ->guardarConfiguracaoContabil(
                $this->empresaId(),
                $dados
            );


        return [
            'id' =>
            $id
        ];
    }

    public function estornarDepreciacao(
        array $dados
    ): array {

        $id =
            (int)(
                $dados['depreciacao_id']
                ?? 0
            );


        return $this->model
            ->estornarDepreciacao(
                $this->empresaId(),
                $this->usuarioId(),
                $id,
                trim(
                    (string)(
                        $dados['data_estorno']
                        ?? ''
                    )
                ),
                trim(
                    (string)(
                        $dados['motivo']
                        ?? ''
                    )
                )
            );
    }

    // ==================================================
    // ATIVOS PARA MOVIMENTAÇÃO
    // ==================================================

    public function ativosParaMovimentacao(): array
    {
        return $this->model
            ->ativosParaMovimentacao(
                $this->empresaId()
            );
    }


    // ==================================================
    // LISTAR MOVIMENTAÇÕES
    // ==================================================

    public function listarMovimentacoes(
        array $dados = []
    ): array {

        return $this->model
            ->listarMovimentacoes(
                $this->empresaId(),
                [
                    'ativo_id' =>
                    $this->inteiroOpcional(
                        $dados['ativo_id']
                            ?? null
                    ),

                    'tipo_movimentacao' =>
                    $this->textoOpcional(
                        $dados['tipo_movimentacao']
                            ?? null
                    ),

                    'filial_id' =>
                    $this->inteiroOpcional(
                        $dados['filial_id']
                            ?? null
                    ),

                    'departamento_id' =>
                    $this->inteiroOpcional(
                        $dados['departamento_id']
                            ?? null
                    ),

                    'data_inicial' =>
                    $this->textoOpcional(
                        $dados['data_inicial']
                            ?? null
                    ),

                    'data_final' =>
                    $this->textoOpcional(
                        $dados['data_final']
                            ?? null
                    ),

                    'pesquisa' =>
                    $this->textoOpcional(
                        $dados['pesquisa']
                            ?? null
                    )
                ]
            );
    }


    // ==================================================
    // CRIAR MOVIMENTAÇÃO
    // ==================================================

    public function criarMovimentacao(
        array $dados
    ): array {

        $id =
            $this->model
            ->criarMovimentacao(
                $this->empresaId(),
                $this->usuarioId(),
                $dados
            );

        return [
            'id' => $id
        ];
    }

    // ==================================================
    // LISTAR MANUTENÇÕES
    // ==================================================

    public function listarManutencoes(
        array $dados = []
    ): array {

        return $this->model
            ->listarManutencoes(
                $this->empresaId(),
                [
                    'ativo_id' =>
                    $this->inteiroOpcional(
                        $dados['ativo_id']
                            ?? null
                    ),

                    'tipo_manutencao' =>
                    $this->textoOpcional(
                        $dados['tipo_manutencao']
                            ?? null
                    ),

                    'estado' =>
                    $this->textoOpcional(
                        $dados['estado']
                            ?? null
                    ),

                    'data_inicial' =>
                    $this->textoOpcional(
                        $dados['data_inicial']
                            ?? null
                    ),

                    'data_final' =>
                    $this->textoOpcional(
                        $dados['data_final']
                            ?? null
                    ),

                    'pesquisa' =>
                    $this->textoOpcional(
                        $dados['pesquisa']
                            ?? null
                    )
                ]
            );
    }


    // ==================================================
    // BUSCAR MANUTENÇÃO
    // ==================================================

    public function buscarManutencao(
        int $id
    ): ?array {

        if ($id <= 0) {

            throw new RuntimeException(
                'Manutenção inválida.'
            );
        }


        return $this->model
            ->buscarManutencao(
                $this->empresaId(),
                $id
            );
    }


    // ==================================================
    // GUARDAR MANUTENÇÃO
    // ==================================================

    public function guardarManutencao(
        array $dados
    ): array {

        $id =
            $this->model
            ->guardarManutencao(
                $this->empresaId(),
                $this->usuarioId(),
                $dados
            );


        return [
            'id' => $id
        ];
    }

    // ==================================================
    // LISTAR BAIXAS
    // ==================================================

    public function listarBaixas(
        array $dados = []
    ): array {

        return $this->model
            ->listarBaixas(
                $this->empresaId(),
                [
                    'ativo_id' =>
                    $this->inteiroOpcional(
                        $dados['ativo_id']
                            ?? null
                    ),

                    'tipo' =>
                    $this->textoOpcional(
                        $dados['tipo']
                            ?? null
                    ),

                    'data_inicial' =>
                    $this->textoOpcional(
                        $dados['data_inicial']
                            ?? null
                    ),

                    'data_final' =>
                    $this->textoOpcional(
                        $dados['data_final']
                            ?? null
                    ),

                    'pesquisa' =>
                    $this->textoOpcional(
                        $dados['pesquisa']
                            ?? null
                    )
                ]
            );
    }


    // ==================================================
    // BUSCAR BAIXA
    // ==================================================

    public function buscarBaixa(
        int $id
    ): ?array {

        if ($id <= 0) {

            throw new RuntimeException(
                'Baixa inválida.'
            );
        }


        return $this->model
            ->buscarBaixa(
                $this->empresaId(),
                $id
            );
    }


    // ==================================================
    // CRIAR BAIXA
    // ==================================================

    public function criarBaixa(
        array $dados
    ): array {

        return $this->model
            ->criarBaixa(
                $this->empresaId(),
                $this->usuarioId(),
                $dados
            );
    }

    // ==================================================
    // DADOS PARA BAIXA
    // ==================================================

    public function dadosAtivoParaBaixa(
        int $ativoId
    ): array {

        return $this->model
            ->dadosAtivoParaBaixa(
                $this->empresaId(),
                $ativoId
            );
    }

    public function configuracaoBaixas(): ?array
    {
        return $this->model
            ->configuracaoBaixas(
                $this->empresaId()
            );
    }


    public function guardarConfiguracaoBaixas(
        array $dados
    ): void {

        $this->model
            ->guardarConfiguracaoBaixas(
                $this->empresaId(),
                $this->usuarioId(),
                $dados
            );
    }

    // ==================================================
    // NORMALIZAR FILTROS
    // ==================================================

    public function normalizarFiltros(
        array $dados
    ): array {

        return [

            'tipo' =>
            $this->textoOpcional(
                $dados['tipo']
                    ?? null
            ),

            'conta_contabil_id' =>
            $this->inteiroOpcional(
                $dados['conta_contabil_id']
                    ?? null
            ),

            'filial_id' =>
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            ),

            'departamento_id' =>
            $this->inteiroOpcional(
                $dados['departamento_id']
                    ?? null
            ),

            'situacao' =>
            $this->textoOpcional(
                $dados['situacao']
                    ?? null
            ),

            'pesquisa' =>
            $this->textoOpcional(
                $dados['pesquisa']
                    ?? null
            )
        ];
    }

    // ==================================================
    // EMPRESA
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
    // UTILIZADOR
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
    // VALIDAR ID
    // ==================================================

    private function validarId(
        int $id
    ): void {

        if ($id <= 0) {

            throw new RuntimeException(
                'Ativo inválido.'
            );
        }
    }

    // ==================================================
    // HELPERS
    // ==================================================

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim(
                (string)$valor
            ) === ''
        ) {

            return null;
        }

        $valor =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $valor === false
            || $valor <= 0
        ) {

            return null;
        }

        return (int)$valor;
    }

    private function textoOpcional(
        mixed $valor
    ): ?string {

        $texto =
            trim(
                (string)(
                    $valor
                    ?? ''
                )
            );

        return $texto !== ''
            ? $texto
            : null;
    }
}
