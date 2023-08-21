<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Collaboration extends Pivot
{
    use HasFactory, HasUlids;

    protected $table = 'collaborations';

    public $timestamps = false;
}
