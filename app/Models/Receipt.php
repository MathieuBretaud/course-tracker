<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Receipt extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'date',
        'store',
        'total',
        'ocr_raw_text',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)
            ->withPivot('price', 'quantity');
    }
}
