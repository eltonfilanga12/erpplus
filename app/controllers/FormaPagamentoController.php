<?php

declare(strict_types=1);


final class FormaPagamentoController
{


    public static function listar(): array
    {

        return FormaPagamento::listar();
    }
}
