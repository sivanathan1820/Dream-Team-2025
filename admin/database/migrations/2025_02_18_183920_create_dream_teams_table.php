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
        Schema::create('dream_teams', function (Blueprint $table) {
            $table->id();
            $table->string("match_no")->nullable();
            $table->string("dream_id")->nullable();
            $table->string("players")->nullable();
            $table->string("captain_id")->nullable();
            $table->string("vice_captain_id")->nullable();
            $table->string("total_credit")->nullable();
            $table->string("team_position")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dream_teams');
    }
};
