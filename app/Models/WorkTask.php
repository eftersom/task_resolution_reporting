<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'resolution_type_id',
        'work_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'work_completed_at' => 'datetime',
        ];
    }

    /**
     * get call for the work task
     *
     * @return BelongsTo<Call>
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * get resolution type for the work task
     *
     * @return BelongsTo<ResolutionType>
     */
    public function resolutionType(): BelongsTo
    {
        return $this->belongsTo(ResolutionType::class);
    }

    /**
     * scope to only include work tasks with a resolution type
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeHasResolutionType(Builder $query): Builder
    {
        return $query->whereNotNull('resolution_type_id');
    }

    /**
     * scope to only include work tasks created between a start and end date
     *
     * @param Builder $query
     * @param string $start
     * @param string $end
     * @return Builder
     */
    public function scopeCreatedBetween(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('work_tasks.created_at', [
            "{$start} 00:00:00",
            "{$end} 23:59:59",
        ]);
    }

    /**
     * scope to only include work tasks with an active call
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithActiveCall(Builder $query): Builder
    {
        $excludedStages = config('reporting.excluded_stages');  

        return $query->whereHas('call', function (Builder $subQuery) use ($excludedStages) {
            $subQuery->whereNotIn('stage', $excludedStages);
        });
    }
}
