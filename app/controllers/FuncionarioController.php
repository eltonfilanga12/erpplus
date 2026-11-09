<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Funcionario.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

final class FuncionarioController
{
    private PDO $pdo;

    private Funcionario $model;

    private AuditoriaService $auditoria;

    private LogService $log;

    public function __construct(PDO $pdo)
    {
        $this->pdo =
            $pdo;

        $this->model =
            new Funcionario(
                $pdo
            );

        $this->auditoria =
            new AuditoriaService(
                $pdo
            );

        $this->log =
            new LogService(
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

    public function validarImportacaoExcel(): array
    {
        try {
            $empresaId = $this->empresaId();
            $usuarioId = $this->usuarioId();

            if (!isset($_FILES['ficheiro']) || !is_array($_FILES['ficheiro'])) {
                return ['success' => false, 'mensagem' => 'Seleccione um ficheiro Excel.'];
            }

            $servico = new FuncionarioImportacaoService($this->pdo);
            $resultado = $servico->validarArquivo($_FILES['ficheiro'], $empresaId, $usuarioId);

            return [
                'success' => true,
                'mensagem' => 'Ficheiro validado com sucesso.',
                'data' => $resultado // Usar 'data' para alinhar com o helper requisicaoFuncionarios do JS
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }

    public function confirmarImportacaoExcel(): array
    {
        try {
            $empresaId = $this->empresaId();
            $usuarioId = $this->usuarioId();

            // Tenta obter o token de $_POST ou lê o input se for FormData/JSON
            $token = trim((string)($_POST['token'] ?? $_REQUEST['token'] ?? ''));

            if ($token === '') {
                // Tenta ler caso venha via corpo bruto (FormData / raw input)
                $input = json_decode(file_get_contents('php://input'), true);
                $token = trim((string)($input['token'] ?? ''));
            }

            if ($token === '') {
                // Tenta ler diretamente de $_POST caso o PHP tenha populado
                $token = trim((string)($_POST['token'] ?? ''));
            }

            if ($token === '') {
                return ['sucesso' => false, 'mensagem' => 'Token de importação inválido.'];
            }

            $servico = new FuncionarioImportacaoService($this->pdo);
            $resultado = $servico->confirmar($token, $empresaId, $usuarioId);

            foreach ($resultado['produtos'] ?? [] as $func) {
                $funcId = (int)$func['id'];
                $this->auditoria->insercao($empresaId, $usuarioId, 'funcionarios', $funcId, [
                    'id' => $funcId,
                    'numero_funcionario' => $func['sku'],
                    'nome' => $func['nome'],
                    'linha_excel' => $func['linha']
                ]);
                $this->log->registrar($empresaId, $usuarioId, 'FUNCIONARIOS', 'CRIAR', 'Funcionário importado via Excel: ' . $func['nome'], $funcId, $this->filialId());
            }

            return [
                'sucesso' => true,
                'mensagem' => $resultado['mensagem'],
                'dados' => $resultado
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function baixarModeloImportacaoExcel(): void
    {
        try {
            $empresaId = $this->empresaId();
            if ($empresaId <= 0) {
                throw new RuntimeException('Empresa inválida.');
            }

            $servico = new FuncionarioImportacaoService($this->pdo); // Corrigido de $this->db para $this->pdo
            $spreadsheet = $servico->gerarModeloExcel($empresaId);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $nomeArquivo = 'modelo_importacao_funcionarios_' . date('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'mensagem' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $filtros = []
    ): array {

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
        int $funcionarioId
    ): array {

        $this->validarFuncionarioId(
            $funcionarioId
        );


        $empresaId =
            $this->empresaId();


        $funcionario =
            $this->model
            ->buscar(
                $empresaId,
                $funcionarioId
            );


        $funcionario['documentos'] =
            $this->model
            ->listarDocumentos(
                $empresaId,
                $funcionarioId
            );


        return $funcionario;
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $usuarioId =
            $this->usuarioId();

        $arquivos =
            is_array(
                $dados['_files']
                    ?? null
            )
            ? $dados['_files']
            : [];

        unset(
            $dados['_files']
        );


        $this->pdo
            ->beginTransaction();

        try {

            $funcionarioId =
                $this->model
                ->criar(
                    $empresaId,
                    $usuarioId,
                    $dados
                );


            $this->model
                ->processarArquivosFuncionario(
                    $empresaId,
                    $usuarioId,
                    $funcionarioId,
                    $arquivos
                );


            $funcionario =
                $this->model->buscar(
                    $empresaId,
                    $funcionarioId
                );


            $this->auditoria
                ->insercao(
                    $empresaId,
                    $usuarioId,
                    'funcionarios',
                    $funcionarioId,
                    $this->dadosAuditoriaFuncionario(
                        $funcionario
                    )
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'FUNCIONARIOS',
                    'CRIAR',
                    'Criou o funcionário '
                        . (
                            $funcionario['nome_completo']
                            ?? $funcionario['numero_funcionario']
                        )
                        . '.',
                    $funcionarioId,
                    isset($funcionario['filial_id'])
                        ? (int)$funcionario['filial_id']
                        : null
                );


            $this->pdo
                ->commit();


            return [
                'id' =>
                $funcionarioId,

                'funcionario' =>
                $funcionario
            ];
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {
                $this->pdo
                    ->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // EDITAR
    // ==================================================

    public function editar(
        int $funcionarioId,
        array $dados
    ): array {

        $this->validarFuncionarioId(
            $funcionarioId
        );

        $empresaId =
            $this->empresaId();

        $usuarioId =
            $this->usuarioId();


        $antes =
            $this->model->buscar(
                $empresaId,
                $funcionarioId
            );


        $arquivos =
            is_array(
                $dados['_files']
                    ?? null
            )
            ? $dados['_files']
            : [];

        unset(
            $dados['_files']
        );


        $this->pdo
            ->beginTransaction();

        try {

            $this->model
                ->editar(
                    $empresaId,
                    $usuarioId,
                    $funcionarioId,
                    $dados
                );


            $this->model
                ->processarArquivosFuncionario(
                    $empresaId,
                    $usuarioId,
                    $funcionarioId,
                    $arquivos
                );


            $depois =
                $this->model->buscar(
                    $empresaId,
                    $funcionarioId
                );


            $this->auditoria
                ->atualizacao(
                    $empresaId,
                    $usuarioId,
                    'funcionarios',
                    $funcionarioId,
                    $this->dadosAuditoriaFuncionario(
                        $antes
                    ),
                    $this->dadosAuditoriaFuncionario(
                        $depois
                    )
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'FUNCIONARIOS',
                    'EDITAR',
                    'Atualizou o funcionário '
                        . (
                            $depois['nome_completo']
                            ?? $depois['numero_funcionario']
                        )
                        . '.',
                    $funcionarioId,
                    isset($depois['filial_id'])
                        && (int)$depois['filial_id'] > 0
                        ? (int)$depois['filial_id']
                        : null
                );


            $this->pdo
                ->commit();


            return [
                'id' =>
                $funcionarioId,

                'funcionario' =>
                $depois
            ];
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {
                $this->pdo
                    ->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $funcionarioId
    ): array {

        $this->validarFuncionarioId(
            $funcionarioId
        );


        $empresaId =
            $this->empresaId();

        $usuarioId =
            $this->usuarioId();


        $funcionario =
            $this->model->buscar(
                $empresaId,
                $funcionarioId
            );


        $antes =
            $this->dadosAuditoriaFuncionario(
                $funcionario
            );


        $nome =
            (string)(
                $funcionario['nome_completo']
                ?? $funcionario['numero_funcionario']
                ?? 'Funcionário'
            );


        $filialId =
            isset($funcionario['filial_id'])
            && (int)$funcionario['filial_id'] > 0
            ? (int)$funcionario['filial_id']
            : null;


        $this->pdo
            ->beginTransaction();

        try {

            $this->model
                ->eliminar(
                    $empresaId,
                    $funcionarioId
                );


            $this->auditoria
                ->exclusao(
                    $empresaId,
                    $usuarioId,
                    'funcionarios',
                    $funcionarioId,
                    $antes
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'FUNCIONARIOS',
                    'ELIMINAR',
                    'Eliminou o funcionário '
                        . $nome
                        . '.',
                    $funcionarioId,
                    $filialId
                );


            $this->pdo
                ->commit();


            return [
                'id' =>
                $funcionarioId
            ];
        } catch (Throwable $e) {

            if (
                $this->pdo
                ->inTransaction()
            ) {
                $this->pdo
                    ->rollBack();
            }

            throw $e;
        }
    }

    private function dadosAuditoriaFuncionario(
        array $funcionario
    ): array {

        return [
            'id' =>
            (int)($funcionario['id'] ?? 0),

            'empresa_id' =>
            (int)($funcionario['empresa_id'] ?? 0),

            'filial_id' =>
            $funcionario['filial_id'] ?? null,

            'departamento_id' =>
            $funcionario['departamento_id'] ?? null,

            'cargo_id' =>
            $funcionario['cargo_id'] ?? null,

            'horario_trabalho_id' =>
            $funcionario['horario_trabalho_id'] ?? null,

            'numero_funcionario' =>
            $funcionario['numero_funcionario'] ?? null,

            'primeiro_nome' =>
            $funcionario['primeiro_nome'] ?? null,

            'apelido' =>
            $funcionario['apelido'] ?? null,

            'nome_completo' =>
            $funcionario['nome_completo'] ?? null,

            'sexo' =>
            $funcionario['sexo'] ?? null,

            'nuit' =>
            $funcionario['nuit'] ?? null,

            'numero_inss' =>
            $funcionario['numero_inss'] ?? null,

            'email_profissional' =>
            $funcionario['email_profissional'] ?? null,

            'telefone_principal' =>
            $funcionario['telefone_principal'] ?? null,

            'data_admissao' =>
            $funcionario['data_admissao'] ?? null,

            'data_desligamento' =>
            $funcionario['data_desligamento'] ?? null,

            'tipo_vinculo' =>
            $funcionario['tipo_vinculo'] ?? null,

            'salario_base' =>
            $funcionario['salario_base'] ?? null,

            'moeda' =>
            $funcionario['moeda'] ?? null,

            'status_id' =>
            $funcionario['status_id'] ?? null
        ];
    }

    // ==================================================
    // NORMALIZAR FILTROS
    // ==================================================

    public function normalizarFiltros(
        array $dados
    ): array {

        return [
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

            'cargo_id' =>
            $this->inteiroOpcional(
                $dados['cargo_id']
                    ?? null
            ),

            'status_id' =>
            $this->inteiroOpcional(
                $dados['status_id']
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

    private function erro(Throwable $e): array
    {
        http_response_code(422);
        return [
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ];
    }

    private function filialId(): ?int
    {
        // Retorna o ID da filial se estiver disponível na sessão ou request, caso contrário null
        return isset($_SESSION['filial_id']) ? (int)$_SESSION['filial_id'] : null;
    }

    // ==================================================
    // VALIDAR ID
    // ==================================================

    private function validarFuncionarioId(
        int $funcionarioId
    ): void {

        if ($funcionarioId <= 0) {
            throw new RuntimeException(
                'Funcionário inválido.'
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
