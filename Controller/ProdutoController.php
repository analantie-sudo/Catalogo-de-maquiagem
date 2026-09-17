<?php

namespace Controller;

use Model\ProdutoModel;
use Exception;
use InvalidArgumentException;

use OpenApi\Attributes as OA;


#[OA\Info(
    version: "1.0.0",
    title: "API Catalogo de Maquiagem",
)]

#[OA\Server(
    url: "http://localhost:3000",
    description: "Servidor de desenvolvimento local da API"
)]

class ProdutoController
{
    private const CAMPOS_OBRIGATORIOS = ["nome", "categoria", "marca", "composicao", "validade", "peso"];

    public function __construct(private ProdutoModel $produtoModel)
    {
    }

    public function ProcessRequest(string $method, ?string $id): void
    {
        header("Content-Type: application/json; charset=UTF-8");

        if ($id === null) {
            match ($method) {
                "GET" => $this->index(),
                "POST" => $this->create(),
                default => $this->methodNotAllowed(["GET", "POST"])
            };

            return;
        }

        match ($method) {
            "GET" => $this->show((int) $id),
            "PATCH" => $this->update((int) $id),
            "DELETE" => $this->delete((int) $id),
            default => $this->methodNotAllowed(["GET", "PATCH", "DELETE"])
        };
    }

    #[OA\Get(
        path: "/produtos",
        summary: "Lista todos os produtos de maquiagem registrados",
        tags: ["Produtos"],
        responses:
        [
            new OA\Response(
                response: 200,
                description: "Requisição concluída com sucesso",
                content: new OA\JsonContent(ref: "#/components/schemas/produtos")
            ),
            new OA\Response(response: 404, description: "Erro ao listar produtos")
        ]
    )]
    public function index(): void
    {
        try {
            $produtos = $this->produtoModel->getAll();
            echo json_encode($produtos);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(["message" => "Erro ao listar produtos: " . $e->getMessage()]);
        }
    }

    #[OA\Post(
        path: "/produtos",
        summary: "Registro de Produto",
        tags: ["Produtos"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/produtoInput")
        ),
        responses:
        [
            new OA\Response(
                response: 201,
                description: "Produto criado com sucesso",
                content: new OA\JsonContent(ref: "#/components/schemas/produtos")
            ),
            new OA\Response(
                response: 422,
                description: "Erro de validação",
                content: new OA\JsonContent(ref: "#/components/schemas/produtos")
            ),
            new OA\Response(
                response: 400,
                description: "Erro ao cadastrar produto",
                content: new OA\JsonContent(ref: "#/components/schemas/produtos")
            ),
            new OA\Response(response: 500, description: "Erro interno do servidor")
        ]
    )]
    public function create(): void
    {
        try {
            $data = $this->readJsonBody();
            $this->validarCamposObrigatorios($data, self::CAMPOS_OBRIGATORIOS);
            $this->validarTiposDoProduto($data);

            $id = $this->produtoModel->create($data);
            $produto = $this->produtoModel->getById($id);

            http_response_code(201);
            echo json_encode($produto);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(["message" => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => "Erro ao cadastrar produto: " . $e->getMessage()]);
        }
    }

    #[OA\Get(
        path: "/produtos/{id}",
        summary: "Busca um produto pelo ID",
        tags: ["Produtos"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Produto encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/produtos")
            ),
            new OA\Response(response: 404, description: "Produto não encontrado")
        ]
    )]
    public function show(int $id): void
    {
        try {
            $produto = $this->produtoModel->getById($id);

            if (!$produto) {
                http_response_code(404);
                echo json_encode(["message" => "Produto não encontrado"]);
                return;
            }

            echo json_encode($produto);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(["message" => "Erro ao buscar produto: " . $e->getMessage()]);
        }
    }

    #[OA\Patch(
        path: "/produtos/{id}",
        summary: "Atualiza um produto existente",
        tags: ["Produtos"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/produtoUpdateInput")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Produto atualizado com sucesso",
                content: new OA\JsonContent(ref: "#/components/schemas/produtos")
            ),
            new OA\Response(response: 404, description: "Produto não encontrado"),
            new OA\Response(response: 422, description: "Erro de validação")
        ]
    )]
    public function update(int $id): void
    {
        try {
            $produtoAtual = $this->produtoModel->getById($id);

            if (!$produtoAtual) {
                http_response_code(404);
                echo json_encode(["message" => "Produto não encontrado"]);
                return;
            }

            $data = $this->readJsonBody();

            $merged = [
                "nome"       => $data['nome']       ?? $produtoAtual['nome'],
                "categoria"  => $data['categoria']  ?? $produtoAtual['categoria'],
                "marca"      => $data['marca']      ?? $produtoAtual['marca'],
                "composicao" => $data['composicao'] ?? $produtoAtual['composicao'],
                "validade"   => $data['validade']   ?? $produtoAtual['validade'],
                "peso"       => $data['peso']       ?? $produtoAtual['peso'],
            ];

            $this->validarTiposDoProduto($merged);

            $this->produtoModel->update(
                $id,
                $merged['nome'],
                $merged['categoria'],
                $merged['marca'],
                $merged['composicao'],
                $merged['validade'],
                (int) $merged['peso']
            );

            $produtoAtualizado = $this->produtoModel->getById($id);
            echo json_encode($produtoAtualizado);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(["message" => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => "Erro ao atualizar produto: " . $e->getMessage()]);
        }
    }

    #[OA\Delete(
        path: "/produtos/{id}",
        summary: "Remove um produto",
        tags: ["Produtos"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Produto removido com sucesso"),
            new OA\Response(response: 404, description: "Produto não encontrado")
        ]
    )]
    public function delete(int $id): void
    {
        try {
            $removido = $this->produtoModel->delete($id);

            if (!$removido) {
                http_response_code(404);
                echo json_encode(["message" => "Produto não encontrado"]);
                return;
            }

            http_response_code(204);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => "Erro ao remover produto: " . $e->getMessage()]);
        }
    }

    private function methodNotAllowed(array $allowed): void
    {
        http_response_code(405);
        header("Allow: " . implode(", ", $allowed));
        echo json_encode(["message" => "Método não permitido"]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents("php://input");

        if ($raw === false || trim($raw) === "") {
            throw new InvalidArgumentException("Corpo da requisição vazio");
        }

        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new InvalidArgumentException("JSON inválido: " . json_last_error_msg());
        }

        return $data;
    }

    private function validarCamposObrigatorios(array $data, array $campos): void
    {
        $faltando = [];

        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $data) || $data[$campo] === null || $data[$campo] === "") {
                $faltando[] = $campo;
            }
        }

        if (!empty($faltando)) {
            throw new InvalidArgumentException(
                "Campos obrigatórios ausentes ou vazios: " . implode(", ", $faltando)
            );
        }
    }

    private function validarTiposDoProduto(array $data): void
    {
        if (isset($data['peso']) && !is_numeric($data['peso'])) {
            throw new InvalidArgumentException("O campo 'peso' deve ser numérico");
        }

        if (isset($data['validade']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['validade'])) {
            throw new InvalidArgumentException("O campo 'validade' deve estar no formato YYYY-MM-DD");
        }

        foreach (['nome', 'categoria', 'marca', 'composicao'] as $campo) {
            if (isset($data[$campo]) && !is_string($data[$campo])) {
                throw new InvalidArgumentException("O campo '{$campo}' deve ser uma string");
            }
        }
    }
}