<?php

namespace App\Models;

use App\Enums\InterviewSessionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewSession extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'interview_id',
        'messages',
        'summary',
        'topics',
        'status',
        'metadata',
        'input_tokens',
        'output_tokens',
        'cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'messages' => 'array',
        'topics' => 'array',
        'metadata' => 'array',
        'status' => InterviewSessionStatus::class,
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost' => 'decimal:6',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the interview that owns the session.
     */
    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }
}
