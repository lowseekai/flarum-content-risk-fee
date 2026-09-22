<?php

declare(strict_types=1);

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTableIfNotExists('content_risk_fingerprints', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('user_id');
    $table->string('content_hash', 64);
    $table->string('title_hash', 64);
    $table->unsignedInteger('occurrence_count')->default(1);
    $table->timestamp('last_seen_at');
    $table->timestamps();
    $table->unique(['user_id', 'content_hash', 'title_hash'], 'content_risk_fingerprint_unique');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
