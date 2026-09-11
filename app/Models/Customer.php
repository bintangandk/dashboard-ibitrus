<?php

namespace App\Models;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Customer extends Model
{
    use HasFactory;

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
