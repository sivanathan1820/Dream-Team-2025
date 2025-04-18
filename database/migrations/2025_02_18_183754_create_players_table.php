<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('team_id');
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->string('position')->nullable();
            $table->string('credit_score')->nullable();
            $table->string('strong')->nullable();
            $table->string('weak')->nullable();
            $table->unsignedTinyInteger('rate')->default(3)->comment('Rating from 1 to 4');
            $table->string('is_captain')->default('no');
            $table->string('is_vice_captain')->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
