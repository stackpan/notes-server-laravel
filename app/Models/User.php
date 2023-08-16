<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable;

class User extends Model implements Authenticatable
{
    use HasFactory;

    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $timestamps = true;
    public $incrementing = true;
    protected $hidden = ['password'];

    protected $fillable = [
        'username',
        'password',
        'email',
        'first_name',
        'last_name',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    public function getAuthIdentifier(): string
    {
        return $this->username;
    }

    public function getAuthPassword(): string
    {
        return $this->password();
    }

    public function getRememberToken(): string
    {
        return $this->token;
    }

    public function setRememberToken($value): void
    {
        $this->token = $value;
    }

    public function getRememberTokenName(): string
    {
        return 'token';
    }
}
