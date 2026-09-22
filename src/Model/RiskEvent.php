<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Model;

use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskEvent extends AbstractModel
{
    protected $table = 'content_risk_events';

    protected $casts = [
        'risk_types' => 'array',
        'domains' => 'array',
        'blocked' => 'boolean',
        'fee' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'post_id' => 'integer',
        'discussion_id' => 'integer',
    ];

    protected $fillable = [
        'user_id',
        'post_id',
        'discussion_id',
        'content_hash',
        'risk_types',
        'domains',
        'fee',
        'balance_before',
        'balance_after',
        'blocked',
        'action',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
