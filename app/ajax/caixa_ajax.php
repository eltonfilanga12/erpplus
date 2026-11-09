<?php

declare(strict_types=1);


header('Content-Type: application/json; charset=utf-8');


session_start();



require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Caixa.php';
require_once __DIR__ . '/../controllers/CaixaController.php';




try {


    $pdo = Database::conn();


    $controller = new CaixaController($pdo);



    $acao = $_REQUEST['acao'] ?? '';



    $empresa_id = $_SESSION['empresa_id'] ?? null;

    $filial_id = $_SESSION['filial_id'] ?? null;

    $usuario_id = $_SESSION['usuario_id'] ?? null;




    if (!$empresa_id) {
        throw new RuntimeException(
            'Empresa do utilizador não encontrada.'
        );
    }

    if (!$usuario_id) {
        throw new RuntimeException(
            'Utilizador não autenticado.'
        );
    }





    switch ($acao) {



        // =====================================
        // LISTAR
        // =====================================

        case "listar":

            echo json_encode([

                "success" => true,

                "perfil_id" => $_SESSION['perfil_id'],

                "data" => $controller->listar(
                    (int)$empresa_id,
                    $filial_id ? (int)$filial_id : null
                )

            ]);

            break;

        case "movimento":

            $_POST['empresa_id'] = $empresa_id;
            $_POST['filial_id']  = $filial_id;
            $_POST['usuario_id'] = $usuario_id;

            $resultado = $controller->movimento($_POST);

            echo json_encode([
                "success" => $resultado,
                "mensagem" => $resultado
                    ? "Movimento registado com sucesso."
                    : "Não foi possível registar o movimento."
            ]);

            break;

        case "fechar":

            $_POST['empresa_id'] = $empresa_id;
            $_POST['filial_id'] = $filial_id;
            $_POST['usuario_id'] = $usuario_id;

            $fechamento = $controller->fechar($_POST);

            echo json_encode([
                "success" => true,
                "mensagem" => "Caixa fechado com sucesso.",
                "data" => $fechamento
            ]);

            break;

        case "filiais":

            echo json_encode([
                "success" => true,

                "data" =>
                $controller->filiais(
                    (int)$empresa_id
                )

            ], JSON_UNESCAPED_UNICODE);

            break;

        case "status":

            echo json_encode([
                "success" => true,
                "data" => $controller->status()
            ]);

            break;

        case "transferir":

            $_POST['empresa_id'] = $empresa_id;
            $_POST['filial_id']  = $filial_id;
            $_POST['usuario_id'] = $usuario_id;

            $resultado = $controller->transferir($_POST);

            echo json_encode([
                "success" => $resultado,
                "mensagem" => $resultado
                    ? "Transferência realizada com sucesso."
                    : "Não foi possível realizar a transferência."
            ]);

            break;

        case "extrato":

            echo json_encode([
                "success" => true,

                "data" => $controller->extrato(
                    (int)($_GET['caixa_id'] ?? 0),
                    (int)$empresa_id,
                    $_GET
                )
            ]);

            break;







        // =====================================
        // OBTER
        // =====================================


        case "obter":


            echo json_encode([

                "success" => true,

                "data" => $controller->obter(

                    (int)$_GET['id'],

                    (int)$empresa_id

                )

            ]);


            break;










        // =====================================
        // GUARDAR
        // =====================================

        case "guardar":

            $_POST['empresa_id'] =
                (int)$empresa_id;

            $_POST['usuario_id'] =
                (int)$usuario_id;

            /*
                * Mantém a filial escolhida
                * no formulário.
                */
            $_POST['filial_id'] =
                !empty($_POST['filial_id'])
                ? (int)$_POST['filial_id']
                : null;

            $edicao =
                !empty($_POST['id']);

            $resultado =
                $controller->guardar(
                    $_POST
                );

            echo json_encode([
                "success" =>
                $resultado,

                "mensagem" =>
                $resultado
                    ? (
                        $edicao
                        ? "Caixa atualizado com sucesso."
                        : "Caixa cadastrado com sucesso."
                    )
                    : "Não foi possível guardar o caixa."
            ]);

            break;


        // =====================================
        // ELIMINAR
        // =====================================

        case "eliminar":

            $resultado = $controller->eliminar(
                (int)($_POST['id'] ?? 0),
                (int)$empresa_id
            );

            echo json_encode([
                "success" => $resultado,
                "mensagem" => $resultado
                    ? "Caixa eliminado com sucesso."
                    : "Não foi possível eliminar o caixa."
            ]);

            break;
        // =====================================
        // ABRIR CAIXA
        // =====================================


        case "abrir":

            $_POST['empresa_id'] = $empresa_id;
            $_POST['filial_id'] = $filial_id;
            $_POST['usuario_id'] = $usuario_id;

            $resultado = $controller->abrir($_POST);

            echo json_encode([
                "success" => $resultado,
                "mensagem" => $resultado
                    ? "Caixa aberto com sucesso."
                    : "Não foi possível abrir o caixa."
            ]);

            break;
        // =====================================
        // SALDO ACTUAL
        // =====================================


        case "saldo":

            $caixaId = (int)($_GET['caixa_id'] ?? 0);

            if ($caixaId <= 0) {
                throw new RuntimeException(
                    'Caixa inválido.'
                );
            }

            echo json_encode([
                "success" => true,

                "saldo" => $controller->saldo(
                    $caixaId,
                    (int)$empresa_id
                )
            ]);

            break;


        default:


            http_response_code(400);


            echo json_encode([


                "success" => false,


                "mensagem" => "Acção inválida"


            ]);
    }
} catch (Throwable $e) {



    http_response_code(500);



    echo json_encode([


        "success" => false,


        "mensagem" => $e->getMessage(),


        "linha" => $e->getLine(),


        "arquivo" => $e->getFile()


    ]);
}
