
<?php


require_once __DIR__
    . '/../models/SalarioFuncionario.php';

final class SalarioFuncionarioController
{
    private SalarioFuncionario $model;


    public function __construct()
    {
        $this->model =
            new SalarioFuncionario();
    }


    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $filtros = []
    ): array {
        $empresaId = (int)(
            $filtros['empresa_id']
            ?? 0
        );

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        $filtrosNormalizados = [
            'empresa_id' => $empresaId,

            'funcionario' => trim(
                (string)(
                    $filtros['funcionario']
                    ?? ''
                )
            ),

            'estado' => trim(
                (string)(
                    $filtros['estado']
                    ?? ''
                )
            )
        ];

        return $this->model
            ->listar(
                $filtrosNormalizados
            );
    }

    // ==================================================
    // CONTRATO ACTIVO DO FUNCIONÁRIO
    // ==================================================

    public function contratoActivo(
        int $empresaId,
        int $funcionarioId
    ): array {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($funcionarioId <= 0) {
            throw new RuntimeException(
                'Seleccione o funcionário.'
            );
        }

        $contrato =
            $this->model
            ->buscarContratoActivoFuncionario(
                $empresaId,
                $funcionarioId
            );

        if (!$contrato) {

            throw new RuntimeException(
                'O funcionário não possui contrato activo.'
            );
        }

        return $contrato;
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id,
        ?int $empresaId = null
    ): array {
        $this->validarId($id);

        $resultado =
            $this->model->buscar($id);

        if (!$resultado) {
            throw new RuntimeException(
                'Configuração salarial não encontrada.'
            );
        }

        if (
            $empresaId !== null
            && $empresaId > 0
            && (int)($resultado['empresa_id'] ?? 0)
            !== $empresaId
        ) {
            throw new RuntimeException(
                'Configuração salarial não encontrada.'
            );
        }

        return $resultado;
    }


    // ==================================================
    // RESUMO
    // ==================================================

    public function resumo(
        int $id
    ): array {
        $this->validarId($id);

        return $this->model
            ->resumo($id);
    }


    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        array $dados
    ): array {
        $this->validarDadosBasicos(
            $dados
        );

        return $this->model
            ->criar($dados);
    }


    // ==================================================
    // ATUALIZAR
    // ==================================================

    public function atualizar(
        int $id,
        array $dados
    ): array {
        $this->validarId($id);

        $this->validarDadosBasicos(
            $dados
        );

        return $this->model
            ->atualizar(
                $id,
                $dados
            );
    }


    // ==================================================
    // ACTIVAR
    // ==================================================

    public function activar(
        int $id,
        int $empresaId,
        int $utilizadorId
    ): array {
        $this->validarOperacaoEstado(
            $id,
            $empresaId,
            $utilizadorId
        );

        return $this->model
            ->alterarEstado(
                $id,
                $empresaId,
                'Activo',
                $utilizadorId
            );
    }


    // ==================================================
    // INACTIVAR
    // ==================================================

    public function inactivar(
        int $id,
        int $empresaId,
        int $utilizadorId
    ): array {
        $this->validarOperacaoEstado(
            $id,
            $empresaId,
            $utilizadorId
        );

        return $this->model
            ->alterarEstado(
                $id,
                $empresaId,
                'Inactivo',
                $utilizadorId
            );
    }


    // ==================================================
    // VALIDAR ID
    // ==================================================

    private function validarId(
        int $id
    ): void {
        if ($id <= 0) {
            throw new RuntimeException(
                'ID da configuração salarial inválido.'
            );
        }
    }


    // ==================================================
    // VALIDAR OPERAÇÃO DE ESTADO
    // ==================================================

    private function validarOperacaoEstado(
        int $id,
        int $empresaId,
        int $utilizadorId
    ): void {
        $this->validarId($id);

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($utilizadorId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido.'
            );
        }
    }


    // ==================================================
    // VALIDAR DADOS BÁSICOS
    // ==================================================

    private function validarDadosBasicos(
        array $dados
    ): void {
        $empresaId = (int)(
            $dados['empresa_id']
            ?? 0
        );

        $funcionarioId = (int)(
            $dados['funcionario_id']
            ?? 0
        );

        $salarioBase = $this->numero(
            $dados['salario_base']
                ?? 0
        );

        $dataInicio = trim(
            (string)(
                $dados['data_inicio']
                ?? ''
            )
        );

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }

        if ($funcionarioId <= 0) {
            throw new RuntimeException(
                'Seleccione o funcionário.'
            );
        }

        if ($salarioBase < 0) {
            throw new RuntimeException(
                'O salário base não pode ser negativo.'
            );
        }

        if ($dataInicio === '') {
            throw new RuntimeException(
                'Informe a data de início.'
            );
        }

        if (!$this->dataValida($dataInicio)) {
            throw new RuntimeException(
                'A data de início é inválida.'
            );
        }

        $dataFim = trim(
            (string)(
                $dados['data_fim']
                ?? ''
            )
        );

        if (
            $dataFim !== ''
            && !$this->dataValida($dataFim)
        ) {
            throw new RuntimeException(
                'A data de fim é inválida.'
            );
        }

        if (
            $dataFim !== ''
            && $dataFim < $dataInicio
        ) {
            throw new RuntimeException(
                'A data de fim não pode ser anterior à data de início.'
            );
        }
    }


    // ==================================================
    // VALIDAR DATA
    // ==================================================

    private function dataValida(
        string $data
    ): bool {
        $objeto =
            DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data
            );

        return $objeto !== false
            && $objeto->format('Y-m-d')
            === $data;
    }


    // ==================================================
    // CONVERTER NÚMERO
    // ==================================================

    private function numero(
        mixed $valor
    ): float {
        if (
            $valor === null
            || $valor === ''
        ) {
            return 0.0;
        }

        $valor = str_replace(
            ',',
            '.',
            trim((string)$valor)
        );

        if (!is_numeric($valor)) {
            throw new RuntimeException(
                'O salário base informado é inválido.'
            );
        }

        return (float)$valor;
    }
}
