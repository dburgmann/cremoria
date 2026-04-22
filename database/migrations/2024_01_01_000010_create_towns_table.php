<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('towns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('gold')->default(0);
            $table->integer('wood')->default(0);
            $table->integer('stone')->default(0);
            $table->integer('iron')->default(0);
            $table->integer('gold_inc')->default(0);
            $table->integer('wood_inc')->default(0);
            $table->integer('stone_inc')->default(0);
            $table->integer('iron_inc')->default(0);
            $table->integer('iron_inc_max')->default(0);
            $table->integer('building_time')->nullable();
            $table->integer('current_building')->nullable();
            $table->integer('current_bld_pts')->default(0);
            $table->integer('building_pts')->default(0);
            $table->integer('unit_cap_used')->default(0);
            $table->integer('unit_cap')->default(0);
            $table->integer('mov_cap')->default(0);
            $table->integer('mov_cap_used')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('towns');
    }
};
