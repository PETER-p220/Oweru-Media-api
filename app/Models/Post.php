<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'post_type',
        'title',
        'description',
        'status',
        'moderated_by',
        'moderation_note',
        'ai_generated',
        'metadata',
        'performance_score',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ai_generated' => 'boolean',
        'moderated_by' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class)->orderBy('order');
    }
}

