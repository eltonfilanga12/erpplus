<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/SaldosAbertura.php';

final class SaldosAberturaController
{
    private SaldosAbertura $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new SaldosAbertura(
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

        $filialSessaoId =
            $this->filialId();

        /*
         * Utilizador associado a uma filial
         * não deve escolher outra filial.
         */
        if ($filialSessaoId !== null) {
            $dados['filiais'] = [];
        }

        $dados['filial_sessao_id'] =
            $filialSessaoId;

        return $dados;
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(): array
    {
        return $this->model
            ->listar(
                $this->empresaId()
            );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id
    ): array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Saldo de abertura inválido.'
            );
        }

        $abertura =
            $this->model
            ->buscar(
                $this->empresaId(),
                $id
            );

        $this->validarFilialAbertura(
            $abertura
        );

        return $abertura;
    }

    // ==================================================
    // GUARDAR RASCUNHO
    // ==================================================

    public function guardar(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $filialSessaoId =
            $this->filialId();

        $id =
            $this->inteiroOpcional(
                $dados['id']
                    ?? null
            );

        $exercicioId =
            $this->inteiroObrigatorio(
                $dados['exercicio_id']
                    ?? null,
                'Informe o exercício contabilístico.'
            );

        $planoContasId =
            $this->inteiroObrigatorio(
                $dados['plano_contas_id']
                    ?? null,
                'Informe o plano de contas.'
            );

        $filialId =
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            );

        /*
         * Se o utilizador pertence a uma filial,
         * força essa filial independentemente do
         * valor enviado pelo navegador.
         */
        if ($filialSessaoId !== null) {
            $filialId =
                $filialSessaoId;
        }

        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );

        $descricao =
            trim(
                (string)(
                    $dados['descricao']
                    ?? ''
                )
            );

        if ($descricao === '') {
            $descricao = null;
        }

        $itens =
            $this->normalizarArray(
                $dados['itens']
                    ?? []
            );

        $saldoAberturaId =
            $this->model
            ->guardarRascunho(
                $empresaId,
                $id,
                $exercicioId,
                $planoContasId,
                $filialId,
                $dataAbertura,
                $descricao,
                $itens,
                $this->usuarioId()
            );

        return [
            'id' =>
            $saldoAberturaId,

            'abertura' =>
            $this->model
                ->buscar(
                    $empresaId,
                    $saldoAberturaId
                )
        ];
    }

    // ==================================================
    // ENTRADAS DE STOCK NÃO CONTABILIZADAS
    // ==================================================

    public function entradasStock(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $filialId =
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            );

        $filialSessaoId =
            $this->filialId();

        if ($filialSessaoId !== null) {
            $filialId =
                $filialSessaoId;
        }

        $dataInicial =
            trim(
                (string)(
                    $dados['data_inicial']
                    ?? ''
                )
            );

        $dataFinal =
            trim(
                (string)(
                    $dados['data_final']
                    ?? ''
                )
            );

        if ($dataInicial === '') {
            throw new RuntimeException(
                'Informe a data inicial.'
            );
        }

        if ($dataFinal === '') {
            throw new RuntimeException(
                'Informe a data final.'
            );
        }

        return $this->model
            ->listarEntradasStockNaoContabilizadas(
                $empresaId,
                $dataInicial,
                $dataFinal,
                $filialId
            );
    }

    // ==================================================
    // IMPORTAR UMA ENTRADA DE STOCK
    // ==================================================

    public function importarStock(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $planoContasId =
            $this->inteiroObrigatorio(
                $dados['plano_contas_id']
                    ?? null,
                'Informe o plano de contas.'
            );

        $movimentoId =
            $this->inteiroObrigatorio(
                $dados['movimento_id']
                    ?? null,
                'Informe o movimento de stock.'
            );

        return $this->model
            ->itemEntradaStockAbertura(
                $empresaId,
                $planoContasId,
                $movimentoId
            );
    }

    // ==================================================
    // CONFIRMAR
    // ==================================================

    public function confirmar(
        int $id
    ): array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Saldo de abertura inválido.'
            );
        }

        $abertura =
            $this->model
            ->buscar(
                $this->empresaId(),
                $id
            );

        $this->validarFilialAbertura(
            $abertura
        );

        return $this->model
            ->confirmar(
                $this->empresaId(),
                $id,
                $this->usuarioId()
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

        return (int)$empresaId;
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
    // CAIXAS DISPONÍVEIS PARA ABERTURA
    // ==================================================

    public function caixasDisponiveis(
        array $dados
    ): array {

        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );

        if ($dataAbertura === '') {

            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }


        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;


        /*
     * Utilizador limitado a uma filial
     * só pode consultar a própria filial.
     */
        $filialSessao =
            $this->filialId();


        if ($filialSessao !== null) {

            $filialId =
                $filialSessao;
        }


        return $this->model
            ->listarCaixasDisponiveis(
                $this->empresaId(),
                $dataAbertura,
                $filialId
            );
    }


    // ==================================================
    // BANCOS DISPONÍVEIS PARA ABERTURA
    // ==================================================

    public function bancosDisponiveis(
        array $dados
    ): array {

        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );

        if ($dataAbertura === '') {

            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }


        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;


        $filialSessao =
            $this->filialId();


        if ($filialSessao !== null) {

            $filialId =
                $filialSessao;
        }


        return $this->model
            ->listarBancosDisponiveis(
                $this->empresaId(),
                $dataAbertura,
                $filialId
            );
    }

    // ==================================================
    // UTILIZADOR DA SESSÃO
    // ==================================================

    private function usuarioId(): ?int
    {
        /*
         * Ajusta apenas se no teu Auth
         * o método tiver outro nome.
         */

        if (
            method_exists(
                Auth::class,
                'userId'
            )
        ) {
            $id =
                Auth::userId();

            return (
                $id !== null
                && $id > 0
            )
                ? (int)$id
                : null;
        }

        if (
            method_exists(
                Auth::class,
                'usuarioId'
            )
        ) {
            $id =
                Auth::usuarioId();

            return (
                $id !== null
                && $id > 0
            )
                ? (int)$id
                : null;
        }

        return null;
    }

    // ==================================================
    // IMPORTAR CAIXA
    // ==================================================

    public function importarCaixa(
        array $dados
    ): array {

        $planoContasId =
            $this->inteiroObrigatorio(
                $dados['plano_contas_id']
                    ?? null,
                'Informe o plano de contas.'
            );


        $caixaId =
            $this->inteiroObrigatorio(
                $dados['caixa_id']
                    ?? null,
                'Informe o caixa.'
            );


        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );


        if ($dataAbertura === '') {

            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }


        return $this->model
            ->itemCaixaAbertura(
                $this->empresaId(),
                $planoContasId,
                $caixaId,
                $dataAbertura
            );
    }


    // ==================================================
    // IMPORTAR BANCO
    // ==================================================

    public function importarBanco(
        array $dados
    ): array {

        $planoContasId =
            $this->inteiroObrigatorio(
                $dados['plano_contas_id']
                    ?? null,
                'Informe o plano de contas.'
            );


        $bancoId =
            $this->inteiroObrigatorio(
                $dados['banco_id']
                    ?? null,
                'Informe a conta bancária.'
            );


        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );


        if ($dataAbertura === '') {

            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }


        return $this->model
            ->itemBancoAbertura(
                $this->empresaId(),
                $planoContasId,
                $bancoId,
                $dataAbertura
            );
    }

    // ==================================================
    // CLIENTES PARA ABERTURA
    // ==================================================

    public function clientesDisponiveis(
        array $dados
    ): array {

        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );

        if ($dataAbertura === '') {
            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }

        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;

        $filialSessao =
            $this->filialId();

        if ($filialSessao !== null) {
            $filialId =
                $filialSessao;
        }

        return $this->model
            ->listarClientesDisponiveis(
                $this->empresaId(),
                $dataAbertura,
                $filialId
            );
    }


    // ==================================================
    // FORNECEDORES PARA ABERTURA
    // ==================================================

    public function fornecedoresDisponiveis(
        array $dados
    ): array {

        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );

        if ($dataAbertura === '') {
            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }

        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;

        $filialSessao =
            $this->filialId();

        if ($filialSessao !== null) {
            $filialId =
                $filialSessao;
        }

        return $this->model
            ->listarFornecedoresDisponiveis(
                $this->empresaId(),
                $dataAbertura,
                $filialId
            );
    }

    // ==================================================
    // IMPORTAR CLIENTE
    // ==================================================

    public function importarCliente(
        array $dados
    ): array {

        $planoContasId =
            $this->inteiroObrigatorio(
                $dados['plano_contas_id']
                    ?? null,
                'Informe o plano de contas.'
            );

        $clienteId =
            $this->inteiroObrigatorio(
                $dados['cliente_id']
                    ?? null,
                'Informe o cliente.'
            );

        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );

        if ($dataAbertura === '') {
            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }

        return $this->model
            ->itemClienteAbertura(
                $this->empresaId(),
                $planoContasId,
                $clienteId,
                $dataAbertura
            );
    }


    // ==================================================
    // IMPORTAR FORNECEDOR
    // ==================================================

    public function importarFornecedor(
        array $dados
    ): array {

        $planoContasId =
            $this->inteiroObrigatorio(
                $dados['plano_contas_id']
                    ?? null,
                'Informe o plano de contas.'
            );

        $fornecedorId =
            $this->inteiroObrigatorio(
                $dados['fornecedor_id']
                    ?? null,
                'Informe o fornecedor.'
            );

        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );

        if ($dataAbertura === '') {
            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }

        return $this->model
            ->itemFornecedorAbertura(
                $this->empresaId(),
                $planoContasId,
                $fornecedorId,
                $dataAbertura
            );
    }

    // ==================================================
    // ATIVOS DISPONÍVEIS PARA ABERTURA
    // ==================================================

    public function ativosDisponiveis(
        array $dados
    ): array {

        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );


        if ($dataAbertura === '') {

            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }


        $filialId =
            !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;


        $filialSessao =
            $this->filialId();


        if ($filialSessao !== null) {

            $filialId =
                $filialSessao;
        }


        return $this->model
            ->listarAtivosDisponiveis(
                $this->empresaId(),
                $dataAbertura,
                $filialId
            );
    }


    // ==================================================
    // IMPORTAR ATIVO
    // ==================================================

    public function importarAtivo(
        array $dados
    ): array {

        $planoContasId =
            $this->inteiroObrigatorio(
                $dados['plano_contas_id']
                    ?? null,
                'Informe o plano de contas.'
            );


        $ativoId =
            $this->inteiroObrigatorio(
                $dados['ativo_id']
                    ?? null,
                'Informe o ativo.'
            );


        $dataAbertura =
            trim(
                (string)(
                    $dados['data_abertura']
                    ?? ''
                )
            );


        if ($dataAbertura === '') {

            throw new RuntimeException(
                'Informe a data de abertura.'
            );
        }


        return $this->model
            ->itensAtivoAbertura(
                $this->empresaId(),
                $planoContasId,
                $ativoId,
                $dataAbertura
            );
    }

    // ==================================================
    // VALIDAR FILIAL DA ABERTURA
    // ==================================================

    private function validarFilialAbertura(
        array $abertura
    ): void {

        $filialSessaoId =
            $this->filialId();

        if ($filialSessaoId === null) {
            return;
        }

        $filialAberturaId =
            !empty($abertura['filial_id'])
            ? (int)$abertura['filial_id']
            : null;

        if (
            $filialAberturaId !== null
            && $filialAberturaId
            !== $filialSessaoId
        ) {
            throw new RuntimeException(
                'Não possui permissão para consultar esta abertura.'
            );
        }
    }

    // ==================================================
    // INTEIRO OBRIGATÓRIO
    // ==================================================

    private function inteiroObrigatorio(
        mixed $valor,
        string $mensagem
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
                $mensagem
            );
        }

        return (int)$inteiro;
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

        return (int)$inteiro;
    }

    // ==================================================
    // NORMALIZAR ARRAY
    // ==================================================

    private function normalizarArray(
        mixed $valor
    ): array {

        /*
         * Se vier diretamente de JSON,
         * já será array.
         */
        if (is_array($valor)) {
            return $valor;
        }

        /*
         * Permite também enviar os itens
         * como JSON dentro de FormData.
         */
        if (is_string($valor)) {

            $valor =
                trim(
                    $valor
                );

            if ($valor === '') {
                return [];
            }

            $json =
                json_decode(
                    $valor,
                    true
                );

            if (
                json_last_error()
                !== JSON_ERROR_NONE
                || !is_array($json)
            ) {
                throw new RuntimeException(
                    'Os itens do saldo de abertura são inválidos.'
                );
            }

            return $json;
        }

        return [];
    }
}
