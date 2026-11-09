<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Departamento.php';

final class DepartamentoController
{
    private Departamento $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new Departamento(
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
        int $departamentoId
    ): array {

        $this->validarDepartamentoId(
            $departamentoId
        );

        return $this->model
            ->buscar(
                $this->empresaId(),
                $departamentoId
            );
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        array $dados
    ): array {

        $departamentoId =
            $this->model
            ->criar(
                $this->empresaId(),
                $this->usuarioId(),
                $dados
            );

        return [
            'id' =>
            $departamentoId,

            'departamento' =>
            $this->model
                ->buscar(
                    $this->empresaId(),
                    $departamentoId
                )
        ];
    }

    // Adicionar dentro da classe DepartamentoController em DepartamentoController.php

    public function validarImportacaoExcel(): array
    {
        try {
            $empresaId = $this->empresaId();
            $usuarioId = $this->usuarioId();

            if (!isset($_FILES['ficheiro']) || !is_array($_FILES['ficheiro'])) {
                return [
                    'success' => false,
                    'mensagem' => 'Selecione um ficheiro Excel.'
                ];
            }

            $servico = new DepartamentoImportacaoService($this->model->getPdo() ?? Database::conn());
            $resultado = $servico->validarArquivo($_FILES['ficheiro'], $empresaId, $usuarioId);

            return [
                'success' => true,
                'mensagem' => 'Ficheiro validado com sucesso.',
                'data' => $resultado
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
            $token = trim((string)($_POST['token'] ?? ''));

            if ($token === '') {
                return [
                    'success' => false,
                    'mensagem' => 'Token de importação inválido.'
                ];
            }

            $servico = new DepartamentoImportacaoService($this->model->getPdo() ?? Database::conn());
            $resultado = $servico->confirmar($token, $empresaId, $usuarioId);

            return [
                'success' => true,
                'mensagem' => 'Importação concluída com sucesso.',
                'data' => $resultado
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'mensagem' => 'Não foi possível concluir a importação: ' . $e->getMessage()
            ];
        }
    }

    public function baixarModeloImportacaoExcel(): void
    {
        try {
            $empresaId = $this->empresaId();
            if ($empresaId <= 0) {
                throw new RuntimeException('Empresa inválida.');
            }

            $servico = new DepartamentoImportacaoService($this->model->getPdo() ?? Database::conn());
            $spreadsheet = $servico->gerarModeloExcel($empresaId);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $nomeArquivo = 'modelo_importacao_departamentos_' . date('Ymd_His') . '.xlsx';

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
    // EDITAR
    // ==================================================

    public function editar(
        int $departamentoId,
        array $dados
    ): array {

        $this->validarDepartamentoId(
            $departamentoId
        );

        $this->model
            ->editar(
                $this->empresaId(),
                $this->usuarioId(),
                $departamentoId,
                $dados
            );

        return [
            'id' =>
            $departamentoId,

            'departamento' =>
            $this->model
                ->buscar(
                    $this->empresaId(),
                    $departamentoId
                )
        ];
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $departamentoId
    ): array {

        $this->validarDepartamentoId(
            $departamentoId
        );

        $this->model
            ->eliminar(
                $this->empresaId(),
                $this->usuarioId(),
                $departamentoId
            );

        return [
            'id' =>
            $departamentoId
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

    // ==================================================
    // VALIDAR ID
    // ==================================================

    private function validarDepartamentoId(
        int $departamentoId
    ): void {

        if ($departamentoId <= 0) {
            throw new RuntimeException(
                'Departamento inválido.'
            );
        }
    }
}
