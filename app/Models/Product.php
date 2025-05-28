<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     required={"name", "price", "category_id"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="name", type="string", description="Nome do produto", example="Smartphone XYZ"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Descrição detalhada do produto", example="Smartphone com tela OLED e câmera de 108MP"),
 *     @OA\Property(property="price", type="number", format="float", description="Preço do produto", example=2999.90),
 *     @OA\Property(property="category_id", type="integer", description="ID da categoria à qual o produto pertence", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="category", ref="#/components/schemas/Category", readOnly=true, description="Categoria associada (em algumas respostas)")
 * )
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "description",
        "price",
        "category_id",
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

