<?php

namespace App\Services;

use App\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\Log; // Import Log facade
use Exception;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAllProducts()
    {
        Log::info("Fetching all products"); // Add log
        return $this->productRepository->getAll();
    }

    public function getProductById($id)
    {
        Log::info("Fetching product by ID: {$id}"); // Add log
        $product = $this->productRepository->findById($id);
        if (!$product) {
            Log::warning("Product not found with ID: {$id}"); // Add log
            // In a real app, you might throw a custom NotFoundException here
            return null;
        }
        return $product;
    }

    public function getProductByName($name)
    {
        Log::info("Fetching product by name: {$name}"); // Add log
        $product = $this->productRepository->findByName($name);
        if (!$product) {
            Log::warning("Product not found with name: {$name}"); // Add log
             // In a real app, you might throw a custom NotFoundException here
            return null;
        }
        return $product;
    }

    public function createProduct(array $data)
    {
        // Example Business Logic: Check if category exists (assuming CategoryService exists or check via repository)
        // Example Business Logic: Validate price > 0
        if ($data["price"] <= 0) {
            Log::error("Attempted to create product with invalid price", ["data" => $data]); // Add log
            throw new Exception("Product price must be positive.");
        }

        Log::info("Creating new product", ["data" => $data]); // Add log
        try {
            $product = $this->productRepository->create($data);
            Log::info("Product created successfully", ["product_id" => $product->id]); // Add log
            return $product;
        } catch (Exception $e) {
            Log::error("Failed to create product", ["error" => $e->getMessage(), "data" => $data]); // Add log
            throw $e; // Re-throw exception to be handled upstream
        }
    }

    public function updateProduct($id, array $data)
    {
        // Example Business Logic: Check if product exists
        $existingProduct = $this->productRepository->findById($id);
        if (!$existingProduct) {
            Log::warning("Attempted to update non-existent product", ["id" => $id]); // Add log
            return null; // Or throw NotFoundException
        }

        // Example Business Logic: Validate price if provided
        if (isset($data["price"]) && $data["price"] <= 0) {
            Log::error("Attempted to update product with invalid price", ["id" => $id, "data" => $data]); // Add log
            throw new Exception("Product price must be positive.");
        }

        Log::info("Updating product", ["id" => $id, "data" => $data]); // Add log
        try {
            $updatedProduct = $this->productRepository->update($id, $data);
            Log::info("Product updated successfully", ["product_id" => $id]); // Add log
            return $updatedProduct;
        } catch (Exception $e) {
            Log::error("Failed to update product", ["error" => $e->getMessage(), "id" => $id, "data" => $data]); // Add log
            throw $e;
        }
    }

    public function deleteProduct($id)
    {
        Log::info("Attempting to delete product", ["id" => $id]); // Add log
        $deleted = $this->productRepository->delete($id);
        if ($deleted) {
            Log::info("Product deleted successfully", ["id" => $id]); // Add log
        } else {
            Log::warning("Failed to delete product or product not found", ["id" => $id]); // Add log
        }
        return $deleted;
    }
}

