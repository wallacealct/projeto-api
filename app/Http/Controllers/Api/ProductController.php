<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use App\Http\Requests\StoreProductRequest; // Import StoreProductRequest
use App\Http\Requests\UpdateProductRequest; // Import UpdateProductRequest
use Illuminate\Http\Request; // Keep for showByName
use Illuminate\Http\JsonResponse;
use Exception; // Import Exception for basic error handling
use Illuminate\Support\Facades\Log; // Import Log for controller-level logging if needed

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
        // Apply JWT middleware to protect these endpoints
        $this->middleware("auth:api");
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $products = $this->productService->getAllProducts();
            return response()->json(["success" => true, "data" => $products]);
        } catch (Exception $e) {
            Log::error("Error fetching products: " . $e->getMessage());
            return response()->json(["success" => false, "message" => "Erro ao buscar produtos."], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse // Use StoreProductRequest
    {
        $validatedData = $request->validated(); // Get validated data

        try {
            $product = $this->productService->createProduct($validatedData);
            return response()->json(["success" => true, "message" => "Produto criado com sucesso.", "data" => $product], 201);
        } catch (Exception $e) {
            // Service layer already logs details
            return response()->json(["success" => false, "message" => $e->getMessage() ?: "Erro ao criar produto."], 400);
        }
    }

    /**
     * Display the specified resource by ID.
     */
    public function show($id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);
            if (!$product) {
                return response()->json(["success" => false, "message" => "Produto não encontrado."], 404);
            }
            return response()->json(["success" => true, "data" => $product]);
        } catch (Exception $e) {
            Log::error("Error fetching product by ID {$id}: " . $e->getMessage());
            return response()->json(["success" => false, "message" => "Erro ao buscar produto."], 500);
        }
    }

    /**
     * Display the specified resource by Name.
     * This requires a custom route.
     */
    public function showByName(Request $request): JsonResponse
    {
        $name = $request->query("name");
        if (!$name) {
             return response()->json(["success" => false, "message" => "Parâmetro 'name' é obrigatório."], 400);
        }

        try {
            $product = $this->productService->getProductByName($name);
            if (!$product) {
                return response()->json(["success" => false, "message" => "Produto não encontrado."], 404);
            }
            return response()->json(["success" => true, "data" => $product]);
        } catch (Exception $e) {
            Log::error("Error fetching product by name '{$name}': " . $e->getMessage());
            return response()->json(["success" => false, "message" => "Erro ao buscar produto pelo nome."], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, $id): JsonResponse // Use UpdateProductRequest
    {
        $validatedData = $request->validated(); // Get validated data

        // Prevent updating with empty data
        if (empty($validatedData)) {
             return response()->json(["success" => false, "message" => "Nenhum dado fornecido para atualização."], 400);
        }

        try {
            $product = $this->productService->updateProduct($id, $validatedData);
            if (!$product) {
                // Service layer handles logging for not found during update attempt
                return response()->json(["success" => false, "message" => "Produto não encontrado para atualização."], 404);
            }
            return response()->json(["success" => true, "message" => "Produto atualizado com sucesso.", "data" => $product]);
        } catch (Exception $e) {
             // Service layer already logs details
            return response()->json(["success" => false, "message" => $e->getMessage() ?: "Erro ao atualizar produto."], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $deleted = $this->productService->deleteProduct($id);
            if (!$deleted) {
                 // Service layer handles logging for not found during delete attempt
                return response()->json(["success" => false, "message" => "Produto não encontrado ou não pôde ser excluído."], 404);
            }
            return response()->json(["success" => true, "message" => "Produto excluído com sucesso."], 200); // Use 200 OK with message instead of 204
        } catch (Exception $e) {
            Log::error("Error deleting product ID {$id}: " . $e->getMessage());
            return response()->json(["success" => false, "message" => "Erro ao excluir produto."], 500);
        }
    }
}

