<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->integer('sender')->default(0); // 0 = system message; no FK intentionally
            $table->foreignId('receiver')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('text');
            $table->boolean('unread')->default(true);
            $table->timestamps();
            $table->index('receiver');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
