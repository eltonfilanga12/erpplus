<?php

declare(strict_types=1);


final class FormaPagamento
{


    public static function listar(): array
    {

        $pdo = Database::conn();


        $sql = "

        SELECT

            fp.id,

            fp.nome,

            fp.descricao,

            fp.status_id,

            s.nome AS status


        FROM formas_pagamento fp


        LEFT JOIN status s

            ON s.id = fp.status_id


        WHERE fp.status_id = 1


        ORDER BY fp.nome ASC


        ";



        return $pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
