<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'notes',
        'stage',
    ];

    /**
     * get work task for the call
     *
     * @return HasOne<WorkTask>
     */
    public function workTask(): HasOne
    {
        return $this->hasOne(WorkTask::class);
    }
}
