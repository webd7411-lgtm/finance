<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cashier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'code',
        'status',
    ];

    public function shiftClosings()
    {
        return $this->hasMany(ShiftClosing::class, 'cashier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
