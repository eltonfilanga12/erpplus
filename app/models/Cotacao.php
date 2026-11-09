<?php

declare(strict_types=1);

final class Cotacao
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }


    // =========================================================
    // LISTAR COTAÇÕES
    // =========================================================

    public function listar(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                c.id,
                c.empresa_id,
                c.cliente_id,
                c.numero,
                c.data_emissao,
                c.validade,

                c.cliente_nome,
                c.cliente_nuit,
                c.cliente_telefone,
                c.cliente_endereco,

                c.subtotal,
                c.desconto_percentual,
                c.desconto_valor,
                c.imposto_percentual,
                c.imposto_valor,
                c.total,

                c.observacoes,
                c.status_id,
                c.criado_por,
                c.criado_em,
                c.atualizado_em,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM cotacoes c

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'cotacao'

            WHERE c.empresa_id = :empresa_id

            ORDER BY
                c.data_emissao DESC,
                c.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    // =========================================================
    // BUSCAR COTAÇÃO
    // =========================================================

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $sql = "
            SELECT
                c.*,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM cotacoes c

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'cotacao'

            WHERE c.id = :id
              AND c.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $cotacao = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$cotacao) {
            return null;
        }

        // =====================================================
        // ITENS
        // =====================================================

        $stmtItens = $this->db->prepare("
            SELECT
                id,
                cotacao_id,
                produto_id,
                produto_sku,
                produto_nome,
                produto_descricao,
                unidade,
                quantidade,
                preco_unitario,
                subtotal,
                desconto_percentual,
                desconto_valor,
                base_tributavel,
                imposto_percentual,
                imposto_valor,
                total,
                ordem,
                criado_em,
                atualizado_em

            FROM cotacao_itens

            WHERE cotacao_id = :cotacao_id

            ORDER BY
                ordem ASC,
                id ASC
        ");

        $stmtItens->execute([
            ':cotacao_id' => $id
        ]);

        $cotacao['itens'] = $stmtItens->fetchAll(
            PDO::FETCH_ASSOC
        );

        // =====================================================
        // DADOS BANCÁRIOS DA EMPRESA PARA A COTAÇÃO
        // =====================================================

        $stmtBancos = $this->db->prepare("
    SELECT
        b.id,
        b.nome_banco,
        b.conta,
        b.digito_conta,
        b.iban,
        b.moeda,
        b.titular

    FROM bancos b

    INNER JOIN status s
        ON s.id = b.status_id
       AND s.tipo = 'banco'

    WHERE b.empresa_id = :empresa_id
      AND s.nome = 'Activo'

    ORDER BY
        b.id ASC
");

        $stmtBancos->execute([
            ':empresa_id' => $empresaId
        ]);

        $cotacao['bancos'] = $stmtBancos->fetchAll(
            PDO::FETCH_ASSOC
        );

        return $cotacao;
    }


    // =========================================================
    // DADOS COMPLETOS PARA IMPRESSÃO
    // =========================================================

    public function dadosParaImpressao(
        int $id,
        int $empresaId
    ): ?array {

        /*
         * Buscamos a cotação juntamente com os dados
         * da empresa.
         */

        $sql = "
            SELECT

                c.id,
                c.empresa_id,
                c.cliente_id,
                c.numero,
                c.data_emissao,
                c.validade,

                c.cliente_nome,
                c.cliente_nuit,
                c.cliente_telefone,
                c.cliente_endereco,

                c.subtotal,
                c.desconto_percentual,
                c.desconto_valor,
                c.imposto_percentual,
                c.imposto_valor,
                c.total,

                c.observacoes,
                c.status_id,
                c.criado_por,
                c.criado_em,
                c.atualizado_em,

                s.nome AS status_nome,
                s.descricao AS status_descricao,

                e.id AS empresa_id_dados,
                e.nome AS empresa_nome,
                e.nome_fantasia AS empresa_nome_fantasia,
                e.nuit AS empresa_nuit,
                e.email AS empresa_email,
                e.website AS empresa_website,
                e.telefone AS empresa_telefone,
                e.logradouro AS empresa_logradouro,
                e.provincia AS empresa_provincia,
                e.cidade AS empresa_cidade,
                e.codigo_postal AS empresa_codigo_postal,
                e.logo AS empresa_logo,
                e.pais AS empresa_pais,
                e.moeda AS empresa_moeda,
                e.slogan AS empresa_slogan

            FROM cotacoes c

            INNER JOIN empresas e
                ON e.id = c.empresa_id

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'cotacao'

            WHERE c.id = :id
              AND c.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $cotacao = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$cotacao) {
            return null;
        }


        // =====================================================
        // ITENS DA COTAÇÃO
        // =====================================================

        $stmtItens = $this->db->prepare("
            SELECT
                id,
                cotacao_id,
                produto_id,
                produto_sku,
                produto_nome,
                produto_descricao,
                unidade,
                quantidade,
                preco_unitario,
                subtotal,
                desconto_percentual,
                desconto_valor,
                base_tributavel,
                imposto_percentual,
                imposto_valor,
                total,
                ordem

            FROM cotacao_itens

            WHERE cotacao_id = :cotacao_id

            ORDER BY
                ordem ASC,
                id ASC
        ");

        $stmtItens->execute([
            ':cotacao_id' => $id
        ]);

        // =====================================================
        // ITENS DA COTAÇÃO
        // =====================================================

        $cotacao['itens'] = $stmtItens->fetchAll(
            PDO::FETCH_ASSOC
        );

        // =====================================================
        // DADOS BANCÁRIOS PARA O PDF
        // =====================================================

        $sqlBancos = "
            SELECT
                b.id,
                b.nome_banco,
                b.conta,
                b.digito_conta,
                b.iban,
                b.moeda,
                b.titular

            FROM bancos b

            INNER JOIN status s
                ON s.id = b.status_id

            WHERE b.empresa_id = :empresa_id
              AND s.tipo = 'banco'
              AND s.nome = 'Activo'

            ORDER BY b.id ASC
        ";

        $stmtBancos = $this->db->prepare($sqlBancos);

        $stmtBancos->execute([
            ':empresa_id' => $empresaId
        ]);

        $cotacao['bancos'] = $stmtBancos->fetchAll(
            PDO::FETCH_ASSOC
        );

        return $cotacao;
    }


    // =========================================================
    // GERAR PRÓXIMO NÚMERO
    // =========================================================

    public function gerarProximoNumero(
        int $empresaId
    ): string {

        $ano = date('Y');

        $prefixo =
            'COT-' . $ano . '-';

        $stmt = $this->db->prepare("
            SELECT numero

            FROM cotacoes

            WHERE empresa_id = :empresa_id
              AND numero LIKE :prefixo

            ORDER BY id DESC
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':prefixo' => $prefixo . '%'
        ]);

        $maior = 0;

        while (
            $numero = $stmt->fetchColumn()
        ) {

            $numero = trim(
                (string)$numero
            );

            if (
                preg_match(
                    '/^'
                        . preg_quote(
                            $prefixo,
                            '/'
                        )
                        . '(\d+)$/',
                    $numero,
                    $matches
                )
            ) {

                $sequencia =
                    (int)$matches[1];

                if (
                    $sequencia > $maior
                ) {
                    $maior =
                        $sequencia;
                }
            }
        }

        return sprintf(
            '%s%04d',
            $prefixo,
            $maior + 1
        );
    }


    // =========================================================
    // NÚMERO EXISTE
    // =========================================================

    public function numeroExiste(
        int $empresaId,
        string $numero,
        ?int $ignorarId = null
    ): bool {

        $sql = "
            SELECT COUNT(*)

            FROM cotacoes

            WHERE empresa_id = :empresa_id
              AND UPPER(TRIM(numero))
                  = UPPER(TRIM(:numero))
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':numero' => $numero
        ];

        if ($ignorarId !== null) {

            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute(
            $parametros
        );

        return (int)
        $stmt->fetchColumn() > 0;
    }


    // =========================================================
    // STATUS
    // =========================================================

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                tipo,
                nome,
                descricao

            FROM status

            WHERE tipo = 'cotacao'

            ORDER BY id ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    public function statusExiste(
        int $statusId
    ): bool {

        $stmt =
            $this->db->prepare("
                SELECT COUNT(*)

                FROM status

                WHERE id = :status_id
                  AND tipo = 'cotacao'
            ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int)
        $stmt->fetchColumn() > 0;
    }


    // =========================================================
    // PESQUISAR CLIENTES
    // =========================================================

    public function pesquisarClientes(
        int $empresaId,
        string $termo
    ): array {

        $termo =
            trim($termo);

        if ($termo === '') {
            return [];
        }

        $sql = "
            SELECT
                c.id,
                c.nome,
                c.nome_comercial,
                c.nuit,
                c.telefone,
                c.endereco,
                c.cidade,
                c.provincia

            FROM clientes c

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'cliente'

            WHERE c.empresa_id = :empresa_id

              AND (
                    c.nome LIKE :termo_nome
                    OR c.nome_comercial LIKE :termo_comercial
                    OR c.nuit LIKE :termo_nuit
                    OR c.telefone LIKE :termo_telefone
              )

              AND s.nome IN (
                  'Activo',
                  'Ativo'
              )

            ORDER BY
                c.nome ASC

            LIMIT 20
        ";

        $stmt =
            $this->db->prepare($sql);

        $termoPesquisa =
            '%' . $termo . '%';

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':termo_nome' =>
            $termoPesquisa,

            ':termo_comercial' =>
            $termoPesquisa,

            ':termo_nuit' =>
            $termoPesquisa,

            ':termo_telefone' =>
            $termoPesquisa
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    // =========================================================
    // PESQUISAR PRODUTOS
    // =========================================================

    public function pesquisarProdutos(
        int $empresaId,
        string $termo
    ): array {

        $termo =
            trim($termo);

        if ($termo === '') {
            return [];
        }

        $sql = "
            SELECT
                p.id,
                p.sku,
                p.nome,
                p.descricao,
                p.preco_venda,

                u.id AS unidade_id,
                u.codigo AS unidade_codigo,
                u.nome AS unidade_nome,
                u.casas_decimais

            FROM produtos p

            LEFT JOIN unidades u
                ON u.id = p.unidade_id
               AND u.empresa_id = p.empresa_id

            INNER JOIN status s
                ON s.id = p.status_id
               AND s.tipo = 'produto'

            WHERE p.empresa_id = :empresa_id

              AND (
                    p.nome LIKE :termo_nome
                    OR p.sku LIKE :termo_sku
                    OR p.codigo_barras LIKE :termo_codigo
              )

              AND s.nome IN (
                  'Activo',
                  'Ativo'
              )

            ORDER BY
                p.nome ASC

            LIMIT 30
        ";

        $stmt =
            $this->db->prepare($sql);

        $termoPesquisa =
            '%' . $termo . '%';

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':termo_nome' =>
            $termoPesquisa,

            ':termo_sku' =>
            $termoPesquisa,

            ':termo_codigo' =>
            $termoPesquisa
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    // =========================================================
    // CRIAR
    // =========================================================

    public function criar(
        int $empresaId,
        array $dados,
        array $itens
    ): int {

        $sql = "
            INSERT INTO cotacoes (
                empresa_id,
                cliente_id,
                numero,
                data_emissao,
                validade,

                cliente_nome,
                cliente_nuit,
                cliente_telefone,
                cliente_endereco,

                subtotal,
                desconto_percentual,
                desconto_valor,
                imposto_percentual,
                imposto_valor,
                total,

                observacoes,
                status_id,
                criado_por
            )

            VALUES (
                :empresa_id,
                :cliente_id,
                :numero,
                :data_emissao,
                :validade,

                :cliente_nome,
                :cliente_nuit,
                :cliente_telefone,
                :cliente_endereco,

                :subtotal,
                :desconto_percentual,
                :desconto_valor,
                :imposto_percentual,
                :imposto_valor,
                :total,

                :observacoes,
                :status_id,
                :criado_por
            )
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':cliente_id' =>
            $dados['cliente_id'],

            ':numero' =>
            $dados['numero'],

            ':data_emissao' =>
            $dados['data_emissao'],

            ':validade' =>
            $dados['validade'],

            ':cliente_nome' =>
            $dados['cliente_nome'],

            ':cliente_nuit' =>
            $dados['cliente_nuit'],

            ':cliente_telefone' =>
            $dados['cliente_telefone'],

            ':cliente_endereco' =>
            $dados['cliente_endereco'],

            ':subtotal' =>
            $dados['subtotal'],

            ':desconto_percentual' =>
            $dados['desconto_percentual'],

            ':desconto_valor' =>
            $dados['desconto_valor'],

            ':imposto_percentual' =>
            $dados['imposto_percentual'],

            ':imposto_valor' =>
            $dados['imposto_valor'],

            ':total' =>
            $dados['total'],

            ':observacoes' =>
            $dados['observacoes'],

            ':status_id' =>
            $dados['status_id'],

            ':criado_por' =>
            $dados['criado_por']
        ]);

        $cotacaoId =
            (int)$this->db->lastInsertId();

        if ($cotacaoId <= 0) {

            throw new RuntimeException(
                'Não foi possível criar a cotação.'
            );
        }

        $this->gravarItens(
            $cotacaoId,
            $itens
        );

        return $cotacaoId;
    }


    // =========================================================
    // ATUALIZAR
    // =========================================================

    public function atualizar(
        int $id,
        int $empresaId,
        array $dados,
        array $itens
    ): bool {

        $sql = "
            UPDATE cotacoes

            SET
                cliente_id = :cliente_id,
                numero = :numero,
                data_emissao = :data_emissao,
                validade = :validade,

                cliente_nome = :cliente_nome,
                cliente_nuit = :cliente_nuit,
                cliente_telefone = :cliente_telefone,
                cliente_endereco = :cliente_endereco,

                subtotal = :subtotal,
                desconto_percentual = :desconto_percentual,
                desconto_valor = :desconto_valor,
                imposto_percentual = :imposto_percentual,
                imposto_valor = :imposto_valor,
                total = :total,

                observacoes = :observacoes,
                status_id = :status_id

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $atualizado =
            $stmt->execute([
                ':cliente_id' =>
                $dados['cliente_id'],

                ':numero' =>
                $dados['numero'],

                ':data_emissao' =>
                $dados['data_emissao'],

                ':validade' =>
                $dados['validade'],

                ':cliente_nome' =>
                $dados['cliente_nome'],

                ':cliente_nuit' =>
                $dados['cliente_nuit'],

                ':cliente_telefone' =>
                $dados['cliente_telefone'],

                ':cliente_endereco' =>
                $dados['cliente_endereco'],

                ':subtotal' =>
                $dados['subtotal'],

                ':desconto_percentual' =>
                $dados['desconto_percentual'],

                ':desconto_valor' =>
                $dados['desconto_valor'],

                ':imposto_percentual' =>
                $dados['imposto_percentual'],

                ':imposto_valor' =>
                $dados['imposto_valor'],

                ':total' =>
                $dados['total'],

                ':observacoes' =>
                $dados['observacoes'],

                ':status_id' =>
                $dados['status_id'],

                ':id' =>
                $id,

                ':empresa_id' =>
                $empresaId
            ]);

        if (!$atualizado) {
            return false;
        }

        $this->apagarItens($id);

        $this->gravarItens(
            $id,
            $itens
        );

        return true;
    }


    // =========================================================
    // GRAVAR ITENS
    // =========================================================

    private function gravarItens(
        int $cotacaoId,
        array $itens
    ): void {

        $sql = "
            INSERT INTO cotacao_itens (
                cotacao_id,
                produto_id,
                produto_sku,
                produto_nome,
                produto_descricao,
                unidade,
                quantidade,
                preco_unitario,
                subtotal,
                desconto_percentual,
                desconto_valor,
                base_tributavel,
                imposto_percentual,
                imposto_valor,
                total,
                ordem
            )

            VALUES (
                :cotacao_id,
                :produto_id,
                :produto_sku,
                :produto_nome,
                :produto_descricao,
                :unidade,
                :quantidade,
                :preco_unitario,
                :subtotal,
                :desconto_percentual,
                :desconto_valor,
                :base_tributavel,
                :imposto_percentual,
                :imposto_valor,
                :total,
                :ordem
            )
        ";

        $stmt =
            $this->db->prepare($sql);

        foreach ($itens as $item) {

            $stmt->execute([
                ':cotacao_id' =>
                $cotacaoId,

                ':produto_id' =>
                $item['produto_id'],

                ':produto_sku' =>
                $item['produto_sku'],

                ':produto_nome' =>
                $item['produto_nome'],

                ':produto_descricao' =>
                $item['produto_descricao'],

                ':unidade' =>
                $item['unidade'],

                ':quantidade' =>
                $item['quantidade'],

                ':preco_unitario' =>
                $item['preco_unitario'],

                ':subtotal' =>
                $item['subtotal'],

                ':desconto_percentual' =>
                $item['desconto_percentual'],

                ':desconto_valor' =>
                $item['desconto_valor'],

                ':base_tributavel' =>
                $item['base_tributavel'],

                ':imposto_percentual' =>
                $item['imposto_percentual'],

                ':imposto_valor' =>
                $item['imposto_valor'],

                ':total' =>
                $item['total'],

                ':ordem' =>
                $item['ordem']
            ]);
        }
    }


    // =========================================================
    // APAGAR ITENS
    // =========================================================

    private function apagarItens(
        int $cotacaoId
    ): void {

        $stmt =
            $this->db->prepare("
                DELETE FROM cotacao_itens

                WHERE cotacao_id =
                    :cotacao_id
            ");

        $stmt->execute([
            ':cotacao_id' =>
            $cotacaoId
        ]);
    }


    // =========================================================
    // ELIMINAR
    // =========================================================

    public function eliminar(
        int $id,
        int $empresaId
    ): bool {

        $stmt =
            $this->db->prepare("
                DELETE FROM cotacoes

                WHERE id = :id
                  AND empresa_id = :empresa_id

                LIMIT 1
            ");

        return $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);
    }
}
