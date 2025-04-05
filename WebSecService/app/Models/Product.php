<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model  {

	protected $fillable = [
        'code',
        'name',
        'price',
        'model',
        'description',
        'photo',
        'stock_quantity'
    ];
    
    /**
     * Get the purchases for the product.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
    
    /**
     * Get the customers who purchased this product.
     */
    public function customers(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            Purchase::class,
            'product_id',
            'id',
            'id',
            'user_id'
        );
    }
}