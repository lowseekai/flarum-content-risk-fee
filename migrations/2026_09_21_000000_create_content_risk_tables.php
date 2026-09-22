<?php

declare(strict_types=1);

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTableIfNotExists('content_risk_tokens', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('user_id');
    $table->string('token_hash', 64)->unique();
    $table->string('content_hash', 64);
    $table->string('title_hash', 64);
    $table->text('risk_types')->nullable();
    $table->unsignedInteger('fee')->default(0);
    $table->timestamp('expires_at');
    $table->timestamp('used_at')->nullable();
    $table->timestamps();
    $table->index(['user_id', 'content_hash', 'title_hash']);
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
