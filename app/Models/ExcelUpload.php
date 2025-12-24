<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExcelUpload extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_filename',
        'row_count',
        'status',
        'error_message',
    ];

    protected $casts = [
        'row_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Upload belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Upload has many MiningData
     */
    public function miningData(): HasMany
    {
        return $this->hasMany(MiningData::class, 'upload_id');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
