<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Tag extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $visible = [
        'body'
    ];

    protected $fillable = [
        'body',
    ];

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
