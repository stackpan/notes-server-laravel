<?php

namespace App\Traits;

trait MustAuthenticated {

    public function authorize(): bool
    {
        return $this->user() !== null;
    }
    
}