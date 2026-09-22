<?php

declare(strict_types=1);

namespace Lowseekai\ContentRiskFee\Model;

use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskToken extends AbstractModel
{
    protected $table = 'content_risk_tokens';

    protected $casts = [
        'risk_types' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'token_hash',
        'content_hash',
        'title_hash',
        'risk_types',
        'fee',
        'expires_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
