<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentProductRepository implements ProductRepositoryInterface
{
    protected $model;

    public function __construct(Product $product)
    {
        $this->model = $product;
    }

    public function getAll()
    {
        return $this->model->with("category")->get(); // Eager load category
    }

    public function findById($id)
    {
        try {
            return $this->model->with("category")->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null; // Or throw a custom exception
        }
    }

    public function findByName($name)
    {
        // Find first product matching the name (case-insensitive)
        return $this->model->with("category")
                           ->whereRaw("LOWER(name) = ?", [strtolower($name)])
                           ->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $product = $this->findById($id);
        if ($product) {
            $product->update($data);
            return $product;
        }
        return null; // Or throw exception
    }

    public function delete($id)
    {
        $product = $this->findById($id);
        if ($product) {
            return $product->delete();
        }
        return false; // Or throw exception
    }
}

