<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/CargoImportacaoService.php';
require_once __DIR__
    . '/../models/Cargo.php';

final class CargoController
{
    private Cargo $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new Cargo(
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
        int $cargoId
    ): array {

        $this->validarCargoId(
            $cargoId
        );

        return $this->model
            ->buscar(
                $this->empresaId(),
                $cargoId
            );
    }

    // ==================================================
    // CRIAR
    // ==================================================

    public function criar(
        array $dados
    ): array {

        $empresaId =
            $this->empresaId();

        $cargoId =
            $this->model
            ->criar(
                $empresaId,
                $this->usuarioId(),
                $dados
            );

        return [
            'id' =>
            $cargoId,

            'cargo' =>
            $this->model
                ->buscar(
                    $empresaId,
                    $cargoId
                )
        ];
    }


// Adicionar dentro da classe CargoController:

public function getPdo(): PDO
{
    return $this->model->getPdo() ?? Database::conn();
}

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

        $servico = new CargoImportacaoService($this->getPdo());
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

        $servico = new CargoImportacaoService($this->getPdo());
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

        $servico = new CargoImportacaoService($this->getPdo());
        $spreadsheet = $servico->gerarModeloExcel($empresaId);

        if (ob_get_length()) {
            ob_end_clean();
        }

        $nomeArquivo = 'modelo_importacao_cargos_' . date('Ymd_His') . '.xlsx';

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
        int $cargoId,
        array $dados
    ): array {

        $this->validarCargoId(
            $cargoId
        );

        $empresaId =
            $this->empresaId();

        $this->model
            ->editar(
                $empresaId,
                $this->usuarioId(),
                $cargoId,
                $dados
            );

        return [
            'id' =>
            $cargoId,

            'cargo' =>
            $this->model
                ->buscar(
                    $empresaId,
                    $cargoId
                )
        ];
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $cargoId
    ): array {

        $this->validarCargoId(
            $cargoId
        );

        $this->model
            ->eliminar(
                $this->empresaId(),
                $this->usuarioId(),
                $cargoId
            );

        return [
            'id' =>
            $cargoId
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

    private function validarCargoId(
        int $cargoId
    ): void {

        if ($cargoId <= 0) {
            throw new RuntimeException(
                'Cargo inválido.'
            );
        }
    }
}
