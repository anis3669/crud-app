<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
      use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'description',
        'price',
        'quantity',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'quantity' => 'integer',
    ];
}
