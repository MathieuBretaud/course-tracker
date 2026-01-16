<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    protected $fillable = ['name', 'category'];

    public function receipts(): BelongsToMany
    {
        return $this->belongsToMany(Receipt::class)
            ->withPivot('price', 'quantity');
    }
}
