<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name');
            $table->string('country', 3);
            $table->unsignedTinyInteger('power_rating');
            $table->unsignedTinyInteger('home_advantage')->default(5);
            $table->unsignedTinyInteger('supporter_strength')->default(5);
            $table->unsignedTinyInteger('goalkeeper_factor')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
