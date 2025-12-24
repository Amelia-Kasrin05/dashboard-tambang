<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MiningData extends Model
{
    protected $table = 'mining_data';

    protected $fillable = [
        'user_id',
        'upload_id',
        'tanggal',
        'waktu',
        'shift',
        'blok',
        'front',
        'commodity',
        'excavator',
        'dump_truck',
        'dump_loc',
        'rit',
        'tonnase',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu' => 'datetime:H:i',
        'rit' => 'integer',
        'tonnase' => 'decimal:2',
    ];

    /**
     * Relationship: MiningData belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: MiningData belongs to ExcelUpload
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(ExcelUpload::class, 'upload_id');
    }

    /**
     * Scope: Filter by user (USER ISOLATION)
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('tanggal', [$from, $to]);
    }

    /**
     * Scope: Filter by shift
     */
    public function scopeByShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }

    /**
     * Scope: Filter by front
     */
    public function scopeByFront($query, $front)
    {
        return $query->where('front', $front);
    }

    /**
     * Scope: Filter by commodity
     */
    public function scopeByCommodity($query, $commodity)
    {
        return $query->where('commodity', $commodity);
    }

    /**
     * Scope: Filter by excavator
     */
    public function scopeByExcavator($query, $excavator)
    {
        return $query->where('excavator', $excavator);
    }
}
