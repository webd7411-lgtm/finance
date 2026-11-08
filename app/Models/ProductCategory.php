<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'product_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get all categories as key => label array
     */
    public static function getList(): array
    {
        return static::orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->pluck('name', 'slug')
            ->toArray();
    }
}
