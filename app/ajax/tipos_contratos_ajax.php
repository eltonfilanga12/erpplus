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
    . '/../models/TipoContrato.php';

require_once __DIR__
    . '/../controllers/TipoContratoController.php';

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
        new TipoContratoController(
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


        // Adicionar no switch($acao):
        case 'validar_importacao':
            validarMetodoPost();
            $resposta = $controller->validarImportacaoExcel();
            echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
            break;

        case 'confirmar_importacao':
            validarMetodoPost();
            $resposta = $controller->confirmarImportacaoExcel();
            echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
            break;

        case 'baixar_modelo_excel':
            $controller->baixarModeloImportacaoExcel();
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

            $tipoContratoId =
                (int)(
                    $_GET['id']
                    ?? $_GET['tipo_contrato_id']
                    ?? 0
                );

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->buscar(
                        $tipoContratoId
                    )
            ], JSON_UNESCAPED_UNICODE);

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
                'Tipo de contrato criado com sucesso.',

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

            $tipoContratoId =
                (int)(
                    $dados['id']
                    ?? $dados['tipo_contrato_id']
                    ?? 0
                );

            $resultado =
                $controller
                ->editar(
                    $tipoContratoId,
                    $dados
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Tipo de contrato atualizado com sucesso.',

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

            $tipoContratoId =
                (int)(
                    $dados['id']
                    ?? $dados['tipo_contrato_id']
                    ?? 0
                );

            $resultado =
                $controller
                ->eliminar(
                    $tipoContratoId
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Tipo de contrato eliminado com sucesso.',

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
                'Acção inválida para a gestão dos tipos de contrato.'
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
// VALIDAR MÉTODO POST
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
// OBTER DADOS DA REQUISIÇÃO
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
