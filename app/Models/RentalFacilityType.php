<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalFacilityType extends Model
{
    use HasFactory;

    protected $table = 'rental_facility_types';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->icon ? $this->icon . ' ' : '') . $this->name);
    }

    /**
     * Get all facility types
     */
    public static function getAllTypes()
    {
        return static::orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }
}
