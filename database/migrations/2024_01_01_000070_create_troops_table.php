<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('troops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('unit_id');
            $table->integer('quantity')->default(0);
            $table->index('movement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('troops');
    }
};
