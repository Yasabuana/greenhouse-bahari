<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SensorLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'greenhouse_id',
        'node_id',
        'parameter',
        'value',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to a specific greenhouse.
     */
    public function scopeForGreenhouse(Builder $query, string $greenhouseId): Builder
    {
        return $query->where('greenhouse_id', $greenhouseId);
    }

    /**
     * Scope a query to a specific node.
     */
    public function scopeForNode(Builder $query, string $nodeId): Builder
    {
        return $query->where('node_id', $nodeId);
    }

    /**
     * Scope a query to a specific parameter.
     */
    public function scopeForParameter(Builder $query, string $parameter): Builder
    {
        return $query->where('parameter', $parameter);
    }

    /**
     * Scope a query to rows received within the given number of minutes.
     */
    public function scopeRecent(Builder $query, int $minutes): Builder
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
