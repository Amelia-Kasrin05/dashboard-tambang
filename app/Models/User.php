<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Helper methods untuk role (updated system)
    public function isAdminSystem(): bool
    {
        return $this->role === 'admin_system';
    }

    public function isAdminProduksi(): bool
    {
        return $this->role === 'admin_produksi';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    // Legacy support
    public function isSuperAdmin(): bool
    {
        return $this->isAdminSystem();
    }

    // Check if user can upload
    public function canUpload(): bool
    {
        return in_array($this->role, ['admin_system', 'admin_produksi']);
    }

    // Check if user can manage system
    public function canManageSystem(): bool
    {
        return $this->role === 'admin_system';
    }

    // Relasi ke productions_raw
    public function productionsRaw()
    {
        return $this->hasMany(ProductionRaw::class);
    }

    // Relasi ke production_uploads
    public function productionUploads()
    {
        return $this->hasMany(ProductionUpload::class);
    }

    // Helper: Get role label
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin_system' => 'Admin Sistem',
            'admin_produksi' => 'Admin Produksi',
            'viewer' => 'Viewer',
            default => 'Unknown'
        };
    }
}
