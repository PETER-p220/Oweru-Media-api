<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'prompt',
        'generated_content',
        'model_used',
        'confidence_score',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'confidence_score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

