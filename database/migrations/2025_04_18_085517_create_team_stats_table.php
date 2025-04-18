<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_stats', function (Blueprint $table) {
            $table->id();
            $table->string('match_no')->nullable();
            $table->unsignedBigInteger('dream_team_id')->nullable()->index();
            $table->unsignedBigInteger('total_dream_players')->nullable();
            $table->decimal('dream_points', 8, 2)->nullable();
            $table->json('players_stats')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_stats');
    }
};
