<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('techs', function (Blueprint $table) {
            $table->unsignedInteger('id');    // tech_type_id
            $table->unsignedBigInteger('user_id');
            $table->integer('quantity')->default(0);
            $table->primary(['id', 'user_id']);
            $table->index('user_id');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('techs');
    }
};
