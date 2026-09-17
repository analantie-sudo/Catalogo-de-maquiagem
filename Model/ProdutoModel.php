<?php

namespace Model;

use Exception;
use Model\Connection;

use OpenApi\Attributes\Property;
use PDO;
use PDOException;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "produtos",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "nome", type: "string"),
        new OA\Property(property: "categoria", type: "string"),
        new OA\Property(property: "marca", type: "string"),
        new OA\Property(property: "composicao", type: "string"),
        new OA\Property(property: "validade", type: "string", format: "date"),
        new OA\Property(property: "peso", type: "integer")
    ]
)]

#[OA\Schema(
    schema: "produtoInput",
    required: ["nome", "categoria", "marca", "composicao", "validade", "peso"],
    properties: [
        new OA\Property(property: "nome", type: "string", example: "Base Líquida"),
        new OA\Property(property: "categoria", type: "string", example: "Base"),
        new OA\Property(property: "marca", type: "string", example: "Maybelline"),
        new OA\Property(property: "composicao", type: "string", example: "Água, glicerina e pigmentos"),
        new OA\Property(property: "validade", type: "string", format: "date", example: "2027-12-31"),
        new OA\Property(property: "peso", type: "integer", example: 30)
    ]
)]

#[OA\Schema(
    schema: "produtoUpdateInput",
    properties: [
        new OA\Property(property: "nome", type: "string", example: "Base Líquida"),
        new OA\Property(property: "categoria", type: "string", example: "Base"),
        new OA\Property(property: "marca", type: "string", example: "Maybelline"),
        new OA\Property(property: "composicao", type: "string", example: "Água, glicerina e pigmentos"),
        new OA\Property(property: "validade", type: "string", format: "date", example: "2027-12-31"),
        new OA\Property(property: "peso", type: "integer", example: 30)
    ]
)]

class ProdutoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function create(array $data): int
    {
        try {
            $sql = 'INSERT INTO produtos (nome, categoria, marca, composicao, validade, peso)
                    VALUES (:nome, :categoria, :marca, :composicao, :validade, :peso)';

            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(":nome", $data['nome'], PDO::PARAM_STR);
            $stmt->bindValue(":categoria", $data['categoria'], PDO::PARAM_STR);
            $stmt->bindValue(":marca", $data['marca'], PDO::PARAM_STR);
            $stmt->bindValue(":composicao", $data['composicao'], PDO::PARAM_STR);
            $stmt->bindValue(":validade", $data['validade'], PDO::PARAM_STR);
            $stmt->bindValue(":peso", (int) $data['peso'], PDO::PARAM_INT);

            $stmt->execute();

            return (int) $this->db->lastInsertId();
        } catch (PDOException $error) {
            error_log("[ProdutoModel::create] " . $error->getMessage());
            throw new Exception("Erro ao criar produto: " . $error->getMessage());
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT id, nome, categoria, marca, composicao, validade, peso FROM produtos WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $error) {
            error_log("[ProdutoModel::getById] " . $error->getMessage());
            throw new Exception("Erro ao ler informações do produto: " . $error->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $sql = "SELECT id, nome, categoria, marca, composicao, validade, peso FROM produtos ORDER BY id";

            $stmt = $this->db->query($sql);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            error_log("[ProdutoModel::getAll] " . $error->getMessage());
            throw new Exception("Erro ao listar produtos: " . $error->getMessage());
        }
    }

    public function update(int $id, string $nome, string $categoria, string $marca, string $composicao, string $validade, int $peso): bool
    {
        try {
            $sql = "UPDATE produtos
                    SET nome = :nome, categoria = :categoria, marca = :marca,
                        composicao = :composicao, validade = :validade, peso = :peso
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->bindValue(":nome", $nome, PDO::PARAM_STR);
            $stmt->bindValue(":categoria", $categoria, PDO::PARAM_STR);
            $stmt->bindValue(":marca", $marca, PDO::PARAM_STR);
            $stmt->bindValue(":composicao", $composicao, PDO::PARAM_STR);
            $stmt->bindValue(":validade", $validade, PDO::PARAM_STR);
            $stmt->bindValue(":peso", $peso, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $error) {
            error_log("[ProdutoModel::update] " . $error->getMessage());
            throw new Exception("Erro ao atualizar produto: " . $error->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM produtos WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $error) {
            error_log("[ProdutoModel::delete] " . $error->getMessage());
            throw new Exception("Erro ao excluir produto: " . $error->getMessage());
        }
    }
}