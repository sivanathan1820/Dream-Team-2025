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
        Schema::create('grounds', function (Blueprint $table) {
            $table->id();
            $table->string("ground_id")->nullable();
            $table->string("ground_name")->nullable();
            $table->unsignedSmallInteger("avg_score")->nullable();
            $table->unsignedSmallInteger("avg_wickets")->nullable();
            $table->unsignedSmallInteger("avg_first_ing_score")->nullable();
            $table->unsignedSmallInteger("avg_first_ing_wickets")->nullable();
            $table->unsignedSmallInteger("avg_second_ing_score")->nullable();
            $table->unsignedSmallInteger("avg_second_ing_wickets")->nullable();
            $table->enum("ground_support_for", ["bat", "ball", "both"])->default("both");
            $table->enum("bowling_support_for", ["NULL", "spin", "fast", "both"])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grounds'); // Drops the grounds table if it exists
    }
};
