<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymProfile extends Model
{
    protected $fillable = [
        'name',
        'logo_path',
        'phone',
        'email',
        'whatsapp',
        'address',
        'currency',
        'timezone',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
