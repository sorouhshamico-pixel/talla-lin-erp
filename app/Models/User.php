<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'current_branch_id',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function currentBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
            ->withPivot([
                'company_id',
                'role',
                'is_primary',
                'can_access',
            ])
            ->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function canAccessBranch(int $branchId): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->branches()
            ->where('branches.id', $branchId)
            ->wherePivot('can_access', true)
            ->exists();
    }
}
