<?php

declare(strict_types=1);


header(
    'Content-Type: application/json; charset=utf-8'
);



require_once __DIR__ . '/../core/Database.php';

require_once __DIR__ . '/../models/FormaPagamento.php';

require_once __DIR__ . '/../controllers/FormaPagamentoController.php';



$acao = $_GET['acao'] ?? '';



try {


    switch ($acao) {



        case "listar":


            echo json_encode([

                "success" => true,

                "data" =>
                FormaPagamentoController::listar()

            ]);


            break;




        default:


            echo json_encode([

                "success" => false,

                "mensagem" => "Acção inválida"

            ]);
    }
} catch (Throwable $e) {


    http_response_code(500);


    echo json_encode([

        "success" => false,

        "mensagem" => $e->getMessage()

    ]);
}
