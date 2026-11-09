<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Assiduidade.php';

require_once __DIR__
    . '/../controllers/AssiduidadeController.php';

Auth::iniciarSessao();

if (!Auth::check()) {

    http_response_code(401);

    responderJson([
        'success' => false,
        'mensagem' =>
        'Sessão expirada. Faça login novamente.'
    ]);
}

try {

    $pdo =
        Database::conn();

    $controller =
        new AssiduidadeController(
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

        // =================================================
        // DADOS INICIAIS
        // =================================================

        case 'dados_iniciais':

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->dadosIniciais(
                        $_GET
                    )
            ]);

            break;

        // =================================================
        // ASSIDUIDADE — GUARDAR EM LOTE
        // =================================================

        case 'guardar_assiduidade_lote':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $resultado =
                $controller
                ->guardarAssiduidadeLote(
                    $dados
                );

            responderJson([
                'success' =>
                true,

                'mensagem' =>
                'Assiduidade registada com sucesso.',

                'data' =>
                $resultado
            ]);

            break;

        case 'registar_falta':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $resultado =
                $controller
                ->registarFalta(
                    $dados
                );

            responderJson([
                'success' =>
                true,

                'mensagem' =>
                'Falta registada com sucesso.',

                'data' =>
                $resultado
            ]);

            break;

        // =================================================
        // RESUMO
        // =================================================

        case 'resumo':

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->resumo(
                        $_GET
                    )
            ]);

            break;

        case 'validar_registos_ponto_lote':

            validarMetodoPost();

            $resultado = $controller->validarRegistosPontoLote(
                obterDadosRequisicao()
            );

            responderJson([
                'success' => true,
                'mensagem' => $resultado['total_validados']
                    . ' registo(s) validado(s) com sucesso.',
                'data' => $resultado
            ]);

            break;


        // =================================================
        // REGISTOS DE PONTO — LISTAR
        // =================================================

        case 'listar_registos_ponto':

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->listarRegistosPonto(
                        $_GET
                    )
            ]);

            break;


        // =================================================
        // REGISTOS DE PONTO — BUSCAR
        // =================================================

        case 'buscar_registo_ponto':

            $registoId =
                obterIdRequisicao(
                    [
                        'id',
                        'registo_id',
                        'registo_ponto_id'
                    ],
                    $_GET
                );

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->buscarRegistoPonto(
                        $registoId
                    )
            ]);

            break;


        // =================================================
        // REGISTOS DE PONTO — CRIAR
        // =================================================

        case 'criar_registo_ponto':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $resultado =
                $controller
                ->criarRegistoPonto(
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Registo de ponto criado e assiduidade apurada com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // REGISTOS DE PONTO — EDITAR
        // =================================================

        case 'editar_registo_ponto':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $registoId =
                obterIdRequisicao(
                    [
                        'id',
                        'registo_id',
                        'registo_ponto_id'
                    ],
                    $dados
                );

            $resultado =
                $controller
                ->editarRegistoPonto(
                    $registoId,
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Registo de ponto atualizado e assiduidade recalculada.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // REGISTOS DE PONTO — VALIDAR
        // =================================================

        case 'validar_registo_ponto':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $registoId =
                obterIdRequisicao(
                    [
                        'id',
                        'registo_id',
                        'registo_ponto_id'
                    ],
                    $dados
                );

            $resultado =
                $controller
                ->validarRegistoPonto(
                    $registoId
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Registo de ponto validado com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // REGISTOS DE PONTO — ANULAR
        // =================================================

        case 'anular_registo_ponto':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $registoId =
                obterIdRequisicao(
                    [
                        'id',
                        'registo_id',
                        'registo_ponto_id'
                    ],
                    $dados
                );

            $resultado =
                $controller
                ->anularRegistoPonto(
                    $registoId,
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Registo de ponto anulado e assiduidade recalculada.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // ASSIDUIDADE DIÁRIA — LISTAR
        // =================================================

        case 'listar_assiduidade':

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->listarAssiduidade(
                        $_GET
                    )
            ]);

            break;


        // =================================================
        // ASSIDUIDADE DIÁRIA — APURAR
        // =================================================

        case 'apurar_dia':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $resultado =
                $controller
                ->apurarDia(
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Assiduidade diária apurada com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // ASSIDUIDADE DIÁRIA — APROVAR
        // =================================================

        case 'aprovar_assiduidade':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $assiduidadeId =
                obterIdRequisicao(
                    [
                        'id',
                        'assiduidade_id',
                        'assiduidade_diaria_id'
                    ],
                    $dados
                );

            $resultado =
                $controller
                ->aprovarAssiduidade(
                    $assiduidadeId
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Assiduidade diária aprovada com sucesso.',

                'data' =>
                $resultado
            ]);

            break;

        // =================================================
        // ASSIDUIDADE DIÁRIA — APROVAR EM LOTE
        // =================================================

        case 'aprovar_assiduidades_lote':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $resultado =
                $controller
                ->aprovarAssiduidadesLote(
                    $dados
                );

            responderJson([
                'success' =>
                true,

                'mensagem' =>
                $resultado['total_aprovados']
                    . ' registo(s) de assiduidade aprovado(s) com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // FALTAS — LISTAR
        // =================================================

        case 'listar_faltas':

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->listarFaltas(
                        $_GET
                    )
            ]);

            break;


        // =================================================
        // FALTAS — JUSTIFICAR
        // =================================================

        case 'justificar_falta':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $faltaId =
                obterIdRequisicao(
                    [
                        'id',
                        'falta_id'
                    ],
                    $dados
                );

            $resultado =
                $controller
                ->justificarFalta(
                    $faltaId,
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Falta justificada com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // FALTAS — MARCAR COMO INJUSTIFICADA
        // =================================================

        case 'marcar_falta_injustificada':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $faltaId =
                obterIdRequisicao(
                    [
                        'id',
                        'falta_id'
                    ],
                    $dados
                );

            $resultado =
                $controller
                ->marcarFaltaInjustificada(
                    $faltaId,
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Falta marcada como injustificada.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // HORAS EXTRAS — LISTAR
        // =================================================

        case 'listar_horas_extras':

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->listarHorasExtras(
                        $_GET
                    )
            ]);

            break;


        // =================================================
        // HORAS EXTRAS — APROVAR
        // =================================================

        case 'aprovar_hora_extra':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $horaExtraId =
                obterIdRequisicao(
                    [
                        'id',
                        'hora_extra_id'
                    ],
                    $dados
                );

            $resultado =
                $controller
                ->aprovarHoraExtra(
                    $horaExtraId,
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Horas extras aprovadas com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // HORAS EXTRAS — REJEITAR
        // =================================================

        case 'rejeitar_hora_extra':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $horaExtraId =
                obterIdRequisicao(
                    [
                        'id',
                        'hora_extra_id'
                    ],
                    $dados
                );

            $resultado =
                $controller
                ->rejeitarHoraExtra(
                    $horaExtraId,
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Horas extras rejeitadas.',

                'data' =>
                $resultado
            ]);

            break;


        // =================================================
        // AÇÃO INVÁLIDA
        // =================================================

        default:

            http_response_code(400);

            responderJson([
                'success' => false,

                'mensagem' =>
                'Acção inválida para a gestão de assiduidade.'
            ]);

            break;
    }
} catch (RuntimeException $e) {

    http_response_code(422);

    responderJson([
        'success' => false,

        'mensagem' =>
        $e->getMessage()
    ]);
} catch (PDOException $e) {

    http_response_code(500);

    responderJson([
        'success' => false,

        'mensagem' =>
        'Ocorreu um erro ao aceder à base de dados.',

        'detalhe' =>
        $e->getMessage(),

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ]);
} catch (Throwable $e) {

    http_response_code(500);

    responderJson([
        'success' => false,

        'mensagem' =>
        $e->getMessage(),

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ]);
}


// ==================================================
// RESPONDER JSON
// ==================================================

function responderJson(
    array $dados
): never {

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}


// ==================================================
// VALIDAR MÉTODO POST
// ==================================================

function validarMetodoPost(): void
{
    $metodo =
        strtoupper(
            trim(
                (string)(
                    $_SERVER['REQUEST_METHOD']
                    ?? ''
                )
            )
        );

    if ($metodo !== 'POST') {

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

        if (
            $conteudo === false
            || trim($conteudo) === ''
        ) {
            return [];
        }

        $dados =
            json_decode(
                $conteudo,
                true
            );

        if (
            json_last_error()
            !== JSON_ERROR_NONE
        ) {
            throw new RuntimeException(
                'Os dados JSON enviados são inválidos.'
            );
        }

        if (!is_array($dados)) {
            throw new RuntimeException(
                'O conteúdo enviado deve ser um objeto JSON válido.'
            );
        }

        return $dados;
    }

    return is_array($_POST)
        ? $_POST
        : [];
}


// ==================================================
// OBTER ID DA REQUISIÇÃO
// ==================================================

function obterIdRequisicao(
    array $campos,
    array $dados
): int {

    foreach ($campos as $campo) {

        if (
            !array_key_exists(
                $campo,
                $dados
            )
        ) {
            continue;
        }

        $valor =
            filter_var(
                $dados[$campo],
                FILTER_VALIDATE_INT
            );

        if (
            $valor !== false
            && $valor > 0
        ) {
            return (int)$valor;
        }
    }

    throw new RuntimeException(
        'O identificador do registo é inválido.'
    );
}
