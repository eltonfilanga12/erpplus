
<?php


require_once __DIR__ . '/Venda.php';

final class DocumentoVenda
{
    private PDO $pdo;
    private Venda $vendaModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->vendaModel = new Venda($pdo);
    }

    /**
     * Prepara os dados de uma Factura.
     * Este método apenas consulta dados.
     */
    public function obterFactura(
        int $vendaId,
        int $empresaId
    ): array {
        if ($vendaId <= 0 || $empresaId <= 0) {
            throw new RuntimeException(
                'Venda ou empresa inválida.'
            );
        }

        $venda = $this->vendaModel->buscar(
            $vendaId,
            $empresaId
        );

        if (!$venda) {
            throw new RuntimeException(
                'Venda não encontrada ou sem acesso.'
            );
        }

        $tipoDocumento = strtoupper(
            trim((string)($venda['tipo_documento'] ?? ''))
        );

        if (!in_array(
            $tipoDocumento,
            ['FACTURA', 'FACTURA_RECIBO'],
            true
        )) {
            throw new RuntimeException(
                'O tipo de documento não é suportado para impressão.'
            );
        }

        if (
            mb_strtolower(
                trim((string)($venda['status_nome'] ?? ''))
            ) === 'cancelada'
        ) {
            throw new RuntimeException(
                'Não é possível imprimir uma Factura '
                    . 'de uma venda cancelada.'
            );
        }

        $itens = $this->vendaModel->buscarItens(
            $vendaId,
            $empresaId
        );

        $stmt = $this->pdo->prepare("
            SELECT *
            FROM empresas
            WHERE id = :empresa_id
            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$empresa) {
            throw new RuntimeException(
                'Os dados da empresa não foram encontrados.'
            );
        }

        $parcelas = $this->vendaModel->buscarParcelas(
            $vendaId,
            $empresaId
        );

        $conta = null;

        if (!empty($venda['conta_receber_id'])) {
            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    numero_documento,
                    data_emissao,
                    data_vencimento,
                    valor,
                    valor_recebido,
                    saldo
                FROM contas_receber
                WHERE id = :conta_id
                  AND empresa_id = :empresa_id
                LIMIT 1
            ");

            $stmt->execute([
                ':conta_id' => (int)$venda['conta_receber_id'],
                ':empresa_id' => $empresaId
            ]);

            $conta = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return [
            'empresa' => $empresa,
            'venda' => $venda,
            'itens' => $itens,
            'parcelas' => $parcelas,
            'conta_receber' => $conta
        ];
    }
}
