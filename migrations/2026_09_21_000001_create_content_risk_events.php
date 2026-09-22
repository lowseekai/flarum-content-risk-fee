<?php

declare(strict_types=1);

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTableIfNotExists('content_risk_events', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('user_id');
    $table->unsignedInteger('post_id')->nullable();
    $table->unsignedInteger('discussion_id')->nullable();
    $table->string('content_hash', 64);
    $table->text('risk_types')->nullable();
    $table->text('domains')->nullable();
    $table->unsignedInteger('fee')->default(0);
    $table->unsignedInteger('balance_before')->default(0);
    $table->unsignedInteger('balance_after')->default(0);
    $table->boolean('blocked')->default(false);
    $table->string('action', 40);
    $table->timestamps();
    $table->index(['user_id', 'created_at']);
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('post_id')->references('id')->on('posts')->onDelete('set null');
    $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('set null');
});
