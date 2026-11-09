<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Departamento.php';

require_once __DIR__
    . '/../controllers/DepartamentoController.php';

Auth::iniciarSessao();

if (!Auth::check()) {

    http_response_code(401);

    echo json_encode([
        'success' => false,

        'mensagem' =>
        'Sessão expirada. Faça login novamente.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $pdo =
        Database::conn();

    $controller =
        new DepartamentoController(
            $pdo
        );

    $acao =
        strtolower(
            trim(
                (string)(
                    $_REQUEST['acao']
                    ?? ''
                )
            )
        );

    switch ($acao) {

        // =====================================
        // DADOS INICIAIS
        // =====================================

        case 'dados_iniciais':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->dadosIniciais()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // LISTAR
        // =====================================

        case 'listar':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->listar()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // BUSCAR
        // =====================================

        case 'buscar':

            $departamentoId =
                (int)(
                    $_GET['id']
                    ?? $_GET['departamento_id']
                    ?? 0
                );

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->buscar(
                        $departamentoId
                    )
            ], JSON_UNESCAPED_UNICODE);

            break;

        case 'validar_importacao':
            validarMetodoPost();
            require_once __DIR__ . '/../services/DepartamentoImportacaoService.php';
            $resposta = $controller->validarImportacaoExcel();
            echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
            break;

        case 'confirmar_importacao':
            validarMetodoPost();
            require_once __DIR__ . '/../services/DepartamentoImportacaoService.php';
            $resposta = $controller->confirmarImportacaoExcel();
            echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
            break;

        case 'baixar_modelo_excel':
            require_once __DIR__ . '/../services/DepartamentoImportacaoService.php';
            $controller->baixarModeloImportacaoExcel();
            break;

        // =====================================
        // CRIAR
        // =====================================

        case 'criar':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $resultado =
                $controller
                ->criar(
                    $dados
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Departamento criado com sucesso.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // EDITAR
        // =====================================

        case 'editar':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $departamentoId =
                (int)(
                    $dados['id']
                    ?? $dados['departamento_id']
                    ?? 0
                );

            $resultado =
                $controller
                ->editar(
                    $departamentoId,
                    $dados
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Departamento atualizado com sucesso.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // ELIMINAR
        // =====================================

        case 'eliminar':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $departamentoId =
                (int)(
                    $dados['id']
                    ?? $dados['departamento_id']
                    ?? 0
                );

            $resultado =
                $controller
                ->eliminar(
                    $departamentoId
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Departamento eliminado com sucesso.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // AÇÃO INVÁLIDA
        // =====================================

        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,

                'mensagem' =>
                'Acção inválida para a gestão de departamentos.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }
} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,

        'mensagem' =>
        $e->getMessage(),

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}


// ==================================================
// VALIDAR POST
// ==================================================

function validarMetodoPost(): void
{
    if (
        ($_SERVER['REQUEST_METHOD'] ?? '')
        !== 'POST'
    ) {
        http_response_code(405);

        throw new RuntimeException(
            'Método inválido para esta operação.'
        );
    }
}


// ==================================================
// OBTER DADOS
// ==================================================

function obterDadosRequisicao(): array
{
    $contentType =
        strtolower(
            trim(
                (string)(
                    $_SERVER['CONTENT_TYPE']
                    ?? ''
                )
            )
        );

    if (
        str_contains(
            $contentType,
            'application/json'
        )
    ) {
        $conteudo =
            file_get_contents(
                'php://input'
            );

        $dados =
            json_decode(
                $conteudo ?: '',
                true
            );

        if (
            json_last_error()
            !== JSON_ERROR_NONE
        ) {
            throw new RuntimeException(
                'Os dados enviados são inválidos.'
            );
        }

        return is_array($dados)
            ? $dados
            : [];
    }

    return $_POST;
}
