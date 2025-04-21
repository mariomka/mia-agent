<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interview extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'agent_name',
        'language',
        'target_name',
        'target_description',
        'topics',
        'interview_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'topics' => 'array',
    ];

    /**
     * Get the sessions for the interview.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(InterviewSession::class);
    }
}
