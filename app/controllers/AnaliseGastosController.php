<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/AnaliseGastos.php';

final class AnaliseGastosController
{
    private AnaliseGastos $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new AnaliseGastos(
                $pdo
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        $empresaId =
            $this->empresaId();

        $dados =
            $this->model
                ->dadosIniciais(
                    $empresaId
                );

        /*
         * Se o utilizador estiver associado
         * a uma filial, não precisa receber
         * todas as filiais.
         */
        if (
            $this->filialId()
            !== null
        ) {

            $dados['filiais'] =
                [];
        }

        $dados[
            'filial_sessao_id'
        ] =
            $this->filialId();

        return $dados;
    }


    // ==================================================
    // GERAR ANÁLISE
    // ==================================================

    public function gerar(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $filialSessaoId =
            $this->filialId();


        $dataInicial =
            trim(
                (string)(
                    $dados[
                        'data_inicial'
                    ]
                    ?? ''
                )
            );


        $dataFinal =
            trim(
                (string)(
                    $dados[
                        'data_final'
                    ]
                    ?? ''
                )
            );


        $filialId =
            $this->inteiroOpcional(
                $dados[
                    'filial_id'
                ]
                ?? null
            );


        $planoContasId =
            $this->inteiroOpcional(
                $dados[
                    'plano_contas_id'
                ]
                ?? null
            );


        $centroCustoId =
            $this->inteiroOpcional(
                $dados[
                    'centro_custo_id'
                ]
                ?? null
            );


        $compararPeriodoAnterior =
            $this->booleano(
                $dados[
                    'comparar_periodo_anterior'
                ]
                ?? true
            );


        /*
         * Utilizador associado a uma filial:
         * força a própria filial.
         */
        if (
            $filialSessaoId
            !== null
        ) {

            $filialId =
                $filialSessaoId;
        }


        return $this->model
            ->gerar(
                $empresaId,
                $dataInicial,
                $dataFinal,
                $filialId,
                $planoContasId,
                $centroCustoId,
                $compararPeriodoAnterior
            );
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


        return
            (int)$empresaId;
    }


    // ==================================================
    // FILIAL DA SESSÃO
    // ==================================================

    private function filialId(): ?int
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


    // ==================================================
    // INTEIRO OPCIONAL
    // ==================================================

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || $valor === ''
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


        return
            (int)$inteiro;
    }


    // ==================================================
    // BOOLEANO
    // ==================================================

    private function booleano(
        mixed $valor
    ): bool {

        if (
            is_bool(
                $valor
            )
        ) {

            return $valor;
        }


        if (
            is_int(
                $valor
            )
        ) {

            return
                $valor === 1;
        }


        $texto =
            strtolower(
                trim(
                    (string)$valor
                )
            );


        return
            in_array(
                $texto,
                [
                    '1',
                    'true',
                    'sim',
                    'yes',
                    'on'
                ],
                true
            );
    }
}