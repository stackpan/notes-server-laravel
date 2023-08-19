<?php

namespace App\Models;

use App\Casts\Json;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Note extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'title',
        'body',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

//    public function tags(): MorphMany
//    {
//        return $this->morphMany(Tag::class, 'taggable');
//    }
}
