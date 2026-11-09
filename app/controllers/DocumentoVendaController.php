
<?php


require_once __DIR__ . '/../models/DocumentoVenda.php';
require_once __DIR__ . '/../core/Auth.php';

final class DocumentoVendaController
{
    private DocumentoVenda $documento;

    public function __construct(PDO $pdo)
    {
        $this->documento = new DocumentoVenda($pdo);
    }

    public function obterFactura(int $vendaId): array
    {
        $empresaId = Auth::empresaId();

        if ($empresaId === null || $empresaId <= 0) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        return $this->documento->obterFactura(
            $vendaId,
            (int)$empresaId
        );
    }
}
