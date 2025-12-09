<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AITrainingData extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'category',
        'title',
        'description',
        'metadata',
        'performance_score',
        'engagement_count',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}

