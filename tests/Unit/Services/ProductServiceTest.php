<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProductService;
use App\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\Category; // Assuming Category model exists
use Illuminate\Foundation\Testing\RefreshDatabase; // Use RefreshDatabase if needed for integration-like unit tests
use Mockery;
use Exception;
use Illuminate\Support\Facades\Log;

class ProductServiceTest extends TestCase
{
    // Use RefreshDatabase if your tests interact with the database directly
    // or if you want a clean slate for each test.
    // use RefreshDatabase;

    protected $productRepositoryMock;
    protected $productService;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the repository interface
        $this->productRepositoryMock = Mockery::mock(ProductRepositoryInterface::class);
        // Instantiate the service with the mock repository
        $this->productService = new ProductService($this->productRepositoryMock);

        // Prevent logs from actually writing during tests (optional)
        Log::shouldReceive("info")->zeroOrMoreTimes();
        Log::shouldReceive("warning")->zeroOrMoreTimes();
        Log::shouldReceive("error")->zeroOrMoreTimes();
        Log::shouldReceive("debug")->zeroOrMoreTimes();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_products()
    {
        $productsCollection = Product::factory()->count(3)->make(); // Use factories or simple collections
        $this->productRepositoryMock
            ->shouldReceive("getAll")
            ->once()
            ->andReturn($productsCollection);

        $result = $this->productService->getAllProducts();

        $this->assertEquals($productsCollection, $result);
    }

    /** @test */
    public function it_can_get_product_by_id()
    {
        $product = Product::factory()->make(["id" => 1]);
        $this->productRepositoryMock
            ->shouldReceive("findById")
            ->with(1)
            ->once()
            ->andReturn($product);

        $result = $this->productService->getProductById(1);

        $this->assertEquals($product, $result);
    }

     /** @test */
    public function it_returns_null_when_product_not_found_by_id()
    {
        $this->productRepositoryMock
            ->shouldReceive("findById")
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->productService->getProductById(999);

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_get_product_by_name()
    {
        $product = Product::factory()->make(["name" => "Test Product"]);
        $this->productRepositoryMock
            ->shouldReceive("findByName")
            ->with("Test Product")
            ->once()
            ->andReturn($product);

        $result = $this->productService->getProductByName("Test Product");

        $this->assertEquals($product, $result);
    }

    /** @test */
    public function it_returns_null_when_product_not_found_by_name()
    {
        $this->productRepositoryMock
            ->shouldReceive("findByName")
            ->with("NonExistent Product")
            ->once()
            ->andReturn(null);

        $result = $this->productService->getProductByName("NonExistent Product");

        $this->assertNull($result);
    }


    /** @test */
    public function it_can_create_a_product()
    {
        $productData = [
            "name" => "New Product",
            "description" => "Description",
            "price" => 10.99,
            "category_id" => 1
        ];
        $createdProduct = Product::factory()->make($productData + ["id" => 5]);

        $this->productRepositoryMock
            ->shouldReceive("create")
            ->with($productData)
            ->once()
            ->andReturn($createdProduct);

        $result = $this->productService->createProduct($productData);

        $this->assertEquals($createdProduct, $result);
    }

    /** @test */
    public function it_throws_exception_when_creating_product_with_zero_price()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Product price must be positive.");

        $productData = [
            "name" => "Free Product",
            "price" => 0,
            "category_id" => 1
        ];

        // Ensure repository create is never called
        $this->productRepositoryMock->shouldNotReceive("create");

        $this->productService->createProduct($productData);
    }

     /** @test */
    public function it_throws_exception_when_creating_product_with_negative_price()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Product price must be positive.");

        $productData = [
            "name" => "Negative Price Product",
            "price" => -5.00,
            "category_id" => 1
        ];

        $this->productRepositoryMock->shouldNotReceive("create");

        $this->productService->createProduct($productData);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $product = Product::factory()->make(["id" => 1]);
        $updateData = ["name" => "Updated Name", "price" => 15.50];
        $updatedProduct = Product::factory()->make(["id" => 1] + $updateData);

        $this->productRepositoryMock
            ->shouldReceive("findById")
            ->with(1)
            ->once()
            ->andReturn($product); // Return the existing product first

        $this->productRepositoryMock
            ->shouldReceive("update")
            ->with(1, $updateData)
            ->once()
            ->andReturn($updatedProduct);

        $result = $this->productService->updateProduct(1, $updateData);

        $this->assertEquals($updatedProduct, $result);
    }

    /** @test */
    public function it_returns_null_when_updating_non_existent_product()
    {
        $updateData = ["name" => "Updated Name"];

        $this->productRepositoryMock
            ->shouldReceive("findById")
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->productRepositoryMock->shouldNotReceive("update");

        $result = $this->productService->updateProduct(999, $updateData);

        $this->assertNull($result);
    }

    /** @test */
    public function it_throws_exception_when_updating_product_with_invalid_price()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Product price must be positive.");

        $product = Product::factory()->make(["id" => 1]);
        $updateData = ["price" => -10.00];

        $this->productRepositoryMock
            ->shouldReceive("findById")
            ->with(1)
            ->once()
            ->andReturn($product);

        $this->productRepositoryMock->shouldNotReceive("update");

        $this->productService->updateProduct(1, $updateData);
    }

    /** @test */
    public function it_can_delete_a_product()
    {
        $this->productRepositoryMock
            ->shouldReceive("delete")
            ->with(1)
            ->once()
            ->andReturn(true);

        $result = $this->productService->deleteProduct(1);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_product()
    {
        $this->productRepositoryMock
            ->shouldReceive("delete")
            ->with(999)
            ->once()
            ->andReturn(false);

        $result = $this->productService->deleteProduct(999);

        $this->assertFalse($result);
    }
}

