<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Api\ProductController;
use App\Services\ProductService;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\User; // Needed for authentication
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // Needed for showByName
use Mockery;
use Tymon\JWTAuth\Facades\JWTAuth; // Needed for actingAs
use Illuminate\Foundation\Testing\RefreshDatabase; // Optional: Use if tests need DB interaction

class ProductControllerTest extends TestCase
{
    // use RefreshDatabase; // Uncomment if needed

    protected $productServiceMock;
    protected $productController;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productServiceMock = Mockery::mock(ProductService::class);
        // Use Laravel's service container to resolve the controller with the mock
        $this->app->instance(ProductService::class, $this->productServiceMock);
        $this->productController = $this->app->make(ProductController::class);

        // Create a dummy user for authentication
        // If using RefreshDatabase, use User::factory()->create();
        $this->user = new User(["id" => 1, "name" => "Test User", "email" => "test@example.com"]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function index_returns_all_products()
    {
        $products = Product::factory()->count(2)->make();
        $this->productServiceMock->shouldReceive("getAllProducts")->once()->andReturn($products);

        // Simulate acting as authenticated user
        $response = $this->actingAs($this->user, "api")->getJson("/api/products"); // Assuming route is /api/products

        $response->assertStatus(200)
                 ->assertJson(["success" => true, "data" => $products->toArray()]);
    }

    /** @test */
    public function store_creates_product_successfully()
    {
        $productData = ["name" => "Test", "price" => 10, "category_id" => 1];
        $createdProduct = Product::factory()->make($productData + ["id" => 1]);

        // Mock service method
        $this->productServiceMock->shouldReceive("createProduct")
                                 ->once()
                                 ->with(Mockery::on(function ($arg) use ($productData) {
                                     // Check if the argument contains the expected data
                                     return is_array($arg) && $arg["name"] === $productData["name"];
                                 }))
                                 ->andReturn($createdProduct);

        // Simulate request (validation is handled by FormRequest, assume it passes here)
        $response = $this->actingAs($this->user, "api")->postJson("/api/products", $productData);

        $response->assertStatus(201)
                 ->assertJson(["success" => true, "message" => "Produto criado com sucesso.", "data" => $createdProduct->toArray()]);
    }

    /** @test */
    public function show_returns_product_by_id()
    {
        $product = Product::factory()->make(["id" => 1]);
        $this->productServiceMock->shouldReceive("getProductById")->with(1)->once()->andReturn($product);

        $response = $this->actingAs($this->user, "api")->getJson("/api/products/1");

        $response->assertStatus(200)
                 ->assertJson(["success" => true, "data" => $product->toArray()]);
    }

    /** @test */
    public function show_returns_404_if_product_not_found_by_id()
    {
        $this->productServiceMock->shouldReceive("getProductById")->with(999)->once()->andReturn(null);

        $response = $this->actingAs($this->user, "api")->getJson("/api/products/999");

        $response->assertStatus(404)
                 ->assertJson(["success" => false, "message" => "Produto não encontrado."]);
    }

     /** @test */
    public function show_by_name_returns_product()
    {
        $product = Product::factory()->make(["id" => 1, "name" => "Specific Product"]);
        $this->productServiceMock->shouldReceive("getProductByName")->with("Specific Product")->once()->andReturn($product);

        // Assuming route is /api/products/search?name=Specific%20Product
        $response = $this->actingAs($this->user, "api")->getJson("/api/products/search?name=Specific%20Product");

        $response->assertStatus(200)
                 ->assertJson(["success" => true, "data" => $product->toArray()]);
    }

    /** @test */
    public function show_by_name_returns_404_if_not_found()
    {
        $this->productServiceMock->shouldReceive("getProductByName")->with("Missing Product")->once()->andReturn(null);

        $response = $this->actingAs($this->user, "api")->getJson("/api/products/search?name=Missing%20Product");

        $response->assertStatus(404)
                 ->assertJson(["success" => false, "message" => "Produto não encontrado."]);
    }

     /** @test */
    public function show_by_name_returns_400_if_name_parameter_missing()
    {
        $response = $this->actingAs($this->user, "api")->getJson("/api/products/search"); // No name query param

        $response->assertStatus(400)
                 ->assertJson(["success" => false, "message" => "Parâmetro 'name' é obrigatório."]);
    }

    /** @test */
    public function update_updates_product_successfully()
    {
        $updateData = ["name" => "Updated Product", "price" => 25.00];
        $updatedProduct = Product::factory()->make(["id" => 1] + $updateData);

        $this->productServiceMock->shouldReceive("updateProduct")
                                 ->once()
                                 ->with(1, Mockery::on(function ($arg) use ($updateData) {
                                     return is_array($arg) && $arg["name"] === $updateData["name"];
                                 }))
                                 ->andReturn($updatedProduct);

        $response = $this->actingAs($this->user, "api")->putJson("/api/products/1", $updateData);

        $response->assertStatus(200)
                 ->assertJson(["success" => true, "message" => "Produto atualizado com sucesso.", "data" => $updatedProduct->toArray()]);
    }

    /** @test */
    public function update_returns_404_if_product_not_found()
    {
        $updateData = ["name" => "Updated Product"];
        $this->productServiceMock->shouldReceive("updateProduct")->with(999, $updateData)->once()->andReturn(null);

        $response = $this->actingAs($this->user, "api")->putJson("/api/products/999", $updateData);

        $response->assertStatus(404)
                 ->assertJson(["success" => false, "message" => "Produto não encontrado para atualização."]);
    }

    /** @test */
    public function destroy_deletes_product_successfully()
    {
        $this->productServiceMock->shouldReceive("deleteProduct")->with(1)->once()->andReturn(true);

        $response = $this->actingAs($this->user, "api")->deleteJson("/api/products/1");

        $response->assertStatus(200) // Changed from 204 to 200 as per controller implementation
                 ->assertJson(["success" => true, "message" => "Produto excluído com sucesso."]);
    }

    /** @test */
    public function destroy_returns_404_if_product_not_found()
    {
        $this->productServiceMock->shouldReceive("deleteProduct")->with(999)->once()->andReturn(false);

        $response = $this->actingAs($this->user, "api")->deleteJson("/api/products/999");

        $response->assertStatus(404)
                 ->assertJson(["success" => false, "message" => "Produto não encontrado ou não pôde ser excluído."]);
    }

    // Note: Validation failure tests are typically handled by Feature tests
    // that actually hit the FormRequest validation logic.
    // Unit tests for controllers usually assume validation passes (mocking FormRequest if needed)
    // or focus on the controller's interaction with the service layer.
}

