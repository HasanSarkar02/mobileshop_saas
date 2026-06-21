<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShopOrGlobal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shop_id', 'name', 'logo', 'is_active'])]
class Brand extends Model
{
    use BelongsToShopOrGlobal;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}