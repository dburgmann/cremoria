<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->unsignedInteger('id');           // building_type_id
            $table->unsignedBigInteger('town_id');
            $table->integer('quantity')->default(0);
            $table->primary(['id', 'town_id']);
            $table->index('town_id');

            $table->foreign('town_id')->references('id')->on('towns')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
