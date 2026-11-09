<?php

declare(strict_types=1);

final class Produto
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

    public function listar(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            p.id,
            p.empresa_id,
            p.categoria_id,
            p.marca_id,
            p.unidade_id,
            p.armazem_padrao_id,
            p.status_id,

            p.sku,
            p.nome,
            p.descricao,
            p.imagem_principal,

            p.preco_custo,
            p.preco_venda,
            p.estoque_minimo,
            p.codigo_barras,

            p.criado_em,
            p.atualizado_em,

            c.nome AS categoria_nome,

            m.nome AS marca_nome,

            u.nome AS unidade_nome,
            u.codigo AS unidade_codigo,
            u.casas_decimais AS unidade_casas_decimais,

            a.nome AS armazem_nome,

            s.nome AS status_nome,
            s.descricao AS status_descricao

        FROM produtos p

        INNER JOIN categorias c
            ON c.id = p.categoria_id
           AND c.empresa_id = p.empresa_id

        LEFT JOIN marcas m
            ON m.id = p.marca_id
           AND m.empresa_id = p.empresa_id

        LEFT JOIN unidades u
            ON u.id = p.unidade_id
           AND u.empresa_id = p.empresa_id

        LEFT JOIN armazens a
            ON a.id = p.armazem_padrao_id
           AND a.empresa_id = p.empresa_id

        INNER JOIN status s
            ON s.id = p.status_id
           AND s.tipo = 'produto'

        WHERE p.empresa_id = :empresa_id

        ORDER BY
            p.nome ASC,
            p.id DESC
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function gerarProximoSku(
        int $empresaId,
        string $prefixo,
        ?int $ignorarId = null
    ): string {

        $prefixo =
            strtoupper(
                trim($prefixo)
            );


        if ($prefixo === '') {
            $prefixo = 'PROD';
        }


        $sql = "
        SELECT sku

        FROM produtos

        WHERE empresa_id =
            :empresa_id

          AND UPPER(sku)
              LIKE :prefixo
    ";


        $parametros = [
            ':empresa_id' =>
            $empresaId,

            ':prefixo' =>
            $prefixo . '-%'
        ];


        if ($ignorarId !== null) {

            $sql .= "
            AND id <> :ignorar_id
        ";

            $parametros[':ignorar_id'] = $ignorarId;
        }


        $sql .= "
        ORDER BY id DESC
    ";


        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $parametros
        );


        $maiorSequencia = 0;


        while (
            $sku =
            $stmt->fetchColumn()
        ) {

            $sku =
                strtoupper(
                    trim(
                        (string)$sku
                    )
                );


            if (
                preg_match(
                    '/^'
                        . preg_quote(
                            $prefixo,
                            '/'
                        )
                        . '-(\d+)$/',
                    $sku,
                    $matches
                )
            ) {

                $sequencia =
                    (int)$matches[1];


                if (
                    $sequencia
                    > $maiorSequencia
                ) {

                    $maiorSequencia =
                        $sequencia;
                }
            }
        }


        $proximaSequencia =
            $maiorSequencia + 1;


        /*
     * Começa com 3 dígitos:
     * 001, 002 ... 999.
     *
     * Ao chegar a 1000 o PHP naturalmente
     * produzirá 1000, sem cortar números.
     */
        return sprintf(
            '%s-%03d',
            $prefixo,
            $proximaSequencia
        );
    }
    
    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $sql = "
        SELECT
            p.*,

            c.nome AS categoria_nome,

            m.nome AS marca_nome,

            u.nome AS unidade_nome,
            u.codigo AS unidade_codigo,
            u.casas_decimais AS unidade_casas_decimais,

            a.nome AS armazem_nome,

            s.nome AS status_nome

        FROM produtos p

        INNER JOIN categorias c
            ON c.id = p.categoria_id
           AND c.empresa_id = p.empresa_id

        LEFT JOIN marcas m
            ON m.id = p.marca_id
           AND m.empresa_id = p.empresa_id

        LEFT JOIN unidades u
            ON u.id = p.unidade_id
           AND u.empresa_id = p.empresa_id

        LEFT JOIN armazens a
            ON a.id = p.armazem_padrao_id
           AND a.empresa_id = p.empresa_id

        INNER JOIN status s
            ON s.id = p.status_id
           AND s.tipo = 'produto'

        WHERE p.id = :id
          AND p.empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);

        $produto =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $produto ?: null;
    }

    public function listarMarcas(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            m.id,
            m.nome

        FROM marcas m

        INNER JOIN status s
            ON s.id = m.status_id
           AND s.tipo = 'marca'

        WHERE m.empresa_id = :empresa_id

          AND s.nome IN (
              'Activo',
              'Ativo',
              'Activa',
              'Ativa'
          )

        ORDER BY m.nome ASC
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    public function marcaPertenceEmpresa(
        int $marcaId,
        int $empresaId
    ): bool {

        $stmt =
            $this->db->prepare("
            SELECT COUNT(*)

            FROM marcas

            WHERE id = :marca_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':marca_id' =>
            $marcaId,

            ':empresa_id' =>
            $empresaId
        ]);

        return (int)
        $stmt->fetchColumn() > 0;
    }

    public function listarUnidades(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            u.id,
            u.codigo,
            u.nome,
            u.casas_decimais

        FROM unidades u

        INNER JOIN status s
            ON s.id = u.status_id
           AND s.tipo = 'unidade'

        WHERE u.empresa_id = :empresa_id

          AND s.nome IN (
              'Activo',
              'Ativo',
              'Activa',
              'Ativa'
          )

        ORDER BY
            u.nome ASC
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    public function unidadePertenceEmpresa(
        int $unidadeId,
        int $empresaId
    ): bool {

        $stmt =
            $this->db->prepare("
            SELECT COUNT(*)

            FROM unidades

            WHERE id = :unidade_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':unidade_id' =>
            $unidadeId,

            ':empresa_id' =>
            $empresaId
        ]);

        return (int)
        $stmt->fetchColumn() > 0;
    }

    public function listarCategorias(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                c.id,
                c.nome,
                c.categoria_pai_id,
                cp.nome AS categoria_pai_nome

            FROM categorias c

            LEFT JOIN categorias cp
                ON cp.id = c.categoria_pai_id
               AND cp.empresa_id = c.empresa_id

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'categoria'

            WHERE c.empresa_id = :empresa_id
              AND s.nome IN (
                  'Activa',
                  'Ativa'
              )

            ORDER BY
                c.categoria_pai_id IS NOT NULL,
                COALESCE(cp.nome, c.nome),
                c.nome
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarArmazens(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                a.id,
                a.nome,
                a.codigo,
                f.nome AS filial_nome

            FROM armazens a

            LEFT JOIN filiais f
                ON f.id = a.filial_id
               AND f.empresa_id = a.empresa_id

            INNER JOIN status s
                ON s.id = a.status_id
               AND s.tipo = 'armazem'

            WHERE a.empresa_id = :empresa_id
              AND s.nome IN (
                  'Activo',
                  'Ativo'
              )

            ORDER BY
                a.nome ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                tipo,
                nome,
                descricao

            FROM status

            WHERE tipo = 'produto'

            ORDER BY
                CASE
                    WHEN nome IN (
                        'Activo',
                        'Ativo'
                    ) THEN 1

                    WHEN nome IN (
                        'Inactivo',
                        'Inativo'
                    ) THEN 2

                    WHEN nome = 'Esgotado'
                    THEN 3

                    WHEN nome = 'Descontinuado'
                    THEN 4

                    ELSE 5
                END,
                nome ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function statusExiste(
        int $statusId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :status_id
              AND tipo = 'produto'
        ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function categoriaPertenceEmpresa(
        int $categoriaId,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM categorias

            WHERE id = :categoria_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':categoria_id' => $categoriaId,
            ':empresa_id' => $empresaId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function armazemPertenceEmpresa(
        int $armazemId,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM armazens

            WHERE id = :armazem_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':armazem_id' => $armazemId,
            ':empresa_id' => $empresaId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function skuExiste(
        int $empresaId,
        string $sku,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM produtos

            WHERE empresa_id = :empresa_id
              AND UPPER(TRIM(sku)) =
                  UPPER(TRIM(:sku))
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':sku' => $sku
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function codigoBarrasExiste(
        int $empresaId,
        string $codigoBarras,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM produtos

            WHERE empresa_id = :empresa_id
              AND codigo_barras = :codigo_barras
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':codigo_barras' => $codigoBarras
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(
        int $empresaId,
        array $dados
    ): int {

        $sql = "
        INSERT INTO produtos (
            empresa_id,
            categoria_id,
            marca_id,
            unidade_id,
            armazem_padrao_id,
            status_id,
            sku,
            nome,
            descricao,
            imagem_principal,
            preco_custo,
            preco_venda,
            estoque_minimo,
            codigo_barras
        )
        VALUES (
            :empresa_id,
            :categoria_id,
            :marca_id,
            :unidade_id,
            :armazem_padrao_id,
            :status_id,
            :sku,
            :nome,
            :descricao,
            :imagem_principal,
            :preco_custo,
            :preco_venda,
            :estoque_minimo,
            :codigo_barras
        )
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':categoria_id' =>
            $dados['categoria_id'],

            ':marca_id' =>
            $dados['marca_id'],

            ':unidade_id' =>
            $dados['unidade_id'],

            ':armazem_padrao_id' =>
            $dados['armazem_padrao_id'],

            ':status_id' =>
            $dados['status_id'],

            ':sku' =>
            $dados['sku'],

            ':nome' =>
            $dados['nome'],

            ':descricao' =>
            $dados['descricao'],

            ':imagem_principal' =>
            $dados['imagem_principal'],

            ':preco_custo' =>
            $dados['preco_custo'],

            ':preco_venda' =>
            $dados['preco_venda'],

            ':estoque_minimo' =>
            $dados['estoque_minimo'],

            ':codigo_barras' =>
            $dados['codigo_barras']
        ]);

        return (int)
        $this->db->lastInsertId();
    }

    public function atualizar(
        int $id,
        int $empresaId,
        array $dados
    ): bool {

        $sql = "
        UPDATE produtos

        SET
            categoria_id = :categoria_id,
            marca_id = :marca_id,
            unidade_id = :unidade_id,
            armazem_padrao_id = :armazem_padrao_id,
            status_id = :status_id,

            sku = :sku,
            nome = :nome,
            descricao = :descricao,
            imagem_principal = :imagem_principal,

            preco_custo = :preco_custo,
            preco_venda = :preco_venda,
            estoque_minimo = :estoque_minimo,
            codigo_barras = :codigo_barras

        WHERE id = :id
          AND empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare($sql);

        return $stmt->execute([
            ':categoria_id' =>
            $dados['categoria_id'],

            ':marca_id' =>
            $dados['marca_id'],

            ':unidade_id' =>
            $dados['unidade_id'],

            ':armazem_padrao_id' =>
            $dados['armazem_padrao_id'],

            ':status_id' =>
            $dados['status_id'],

            ':sku' =>
            $dados['sku'],

            ':nome' =>
            $dados['nome'],

            ':descricao' =>
            $dados['descricao'],

            ':imagem_principal' =>
            $dados['imagem_principal'],

            ':preco_custo' =>
            $dados['preco_custo'],

            ':preco_venda' =>
            $dados['preco_venda'],

            ':estoque_minimo' =>
            $dados['estoque_minimo'],

            ':codigo_barras' =>
            $dados['codigo_barras'],

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    public function eliminar(
        int $id,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            DELETE FROM produtos

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        return $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);
    }
}
