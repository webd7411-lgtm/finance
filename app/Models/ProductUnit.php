<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    use HasFactory;

    protected $table = 'product_units';

    protected $fillable = [
        'name',
        'short_code',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get all units as key => label array
     */
    public static function getList(): array
    {
        return static::orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->pluck('name', 'short_code')
            ->toArray();
    }
}
