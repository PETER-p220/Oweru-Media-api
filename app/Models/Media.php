<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'order',
    ];

    protected $appends = ['url'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function getUrlAttribute()
    {
        // Get base URL from config
        $baseUrl = config('app.url', 'http://31.97.176.48:8081');
        
        // Parse the URL to check if port is missing
        $parsedUrl = parse_url($baseUrl);
        
        // If it's localhost or 127.0.0.1 and no port is specified, add :8000
        if (isset($parsedUrl['host']) && 
            in_array($parsedUrl['host'], ['localhost', '127.0.0.1']) && 
            !isset($parsedUrl['port'])) {
            $scheme = $parsedUrl['scheme'] ?? 'http';
            $host = $parsedUrl['host'];
            $path = $parsedUrl['path'] ?? '';
            $baseUrl = "{$scheme}://{$host}:8081{$path}";
        }
        
        // Construct URL manually to ensure correct format
        $filePath = ltrim($this->file_path, '/');
        $url = rtrim($baseUrl, '/') . '/storage/' . $filePath;
        
        return $url;
    }
}

