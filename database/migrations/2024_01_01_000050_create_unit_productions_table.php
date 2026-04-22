<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('unit_id');
            $table->foreignId('town_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('time')->default(0);
            $table->index('town_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_productions');
    }
};
