<?php

namespace App\Models;

use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\SalaryDraw;
use Illuminate\Database\Eloquent\Relations\HasMany;
use \App\Models\Concerns\BelongsToShop;

#[Fillable(['name', 'email', 'password', 'shop_id', 'branch_id', 'user_type', 'phone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'has_system_access' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === UserType::SuperAdmin;
    }

    public function isOwner(): bool
    {
        return $this->user_type === UserType::Owner;
    }

    public function isEmployee(): bool
    {
        return $this->user_type === UserType::Employee;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        if ($this->isSuperAdmin()) {
            return; // Silently discard — never send reset emails to Super Admin
        }

        $this->notify(new \App\Notifications\SetInitialPasswordNotification(
            $token,
            'your account'
        ));
    }

    public function employeeProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function salaryDraws(): HasMany
    {
        return $this->hasMany(SalaryDraw::class);
    }

    public function scopeForShop($query, int $shopId): void
    {
        $query->where('shop_id', $shopId);
    }
}