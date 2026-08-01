<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'bhk_type',
        'carpet_area_min',
        'carpet_area_max',
        'price_from',
        'price_to',
    ];

    protected $casts = [
        'carpet_area_min' => 'float',
        'carpet_area_max' => 'float',
        'price_from'      => 'float',
        'price_to'        => 'float',
    ];

    public const BHK_TYPES = [
        '1BHK', '2BHK', '3BHK', '4BHK', 'Penthouse', 'Commercial', 'Plot', 'Villa',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
