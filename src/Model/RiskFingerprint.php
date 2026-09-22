<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Model;

use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFingerprint extends AbstractModel
{
    protected $table = 'content_risk_fingerprints';

    protected $casts = [
        'occurrence_count' => 'integer',
        'created_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'content_hash',
        'title_hash',
        'occurrence_count',
        'last_seen_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
