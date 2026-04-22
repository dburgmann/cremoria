<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner')->constrained('users')->cascadeOnDelete();
            $table->integer('start')->nullable();
            $table->integer('start_x')->default(0);
            $table->integer('start_y')->default(0);
            $table->integer('dest_x')->default(0);
            $table->integer('dest_y')->default(0);
            $table->integer('gold')->default(0);
            $table->integer('wood')->default(0);
            $table->integer('stone')->default(0);
            $table->integer('iron')->default(0);
            $table->tinyInteger('action')->default(0);
            $table->integer('duration')->default(0);
            $table->integer('arrival')->default(0);
            $table->index('owner');
            $table->index(['dest_x', 'dest_y']);
            $table->index(['start_x', 'start_y']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
