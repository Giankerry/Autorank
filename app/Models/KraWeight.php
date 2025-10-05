<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KraWeight extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * This is a security feature to prevent unintended fields from being updated.
     * Only the fields listed here can be filled using methods like `create()` or `update()`.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rank_category',
        'kra1_weight',
        'kra2_weight',
        'kra3_weight',
        'kra4_weight',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * This ensures that when you retrieve these attributes from the model,
     * they are automatically converted to the correct data type.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'kra1_weight' => 'float',
        'kra2_weight' => 'float',
        'kra3_weight' => 'float',
        'kra4_weight' => 'float',
        'is_active' => 'boolean',
    ];
}
