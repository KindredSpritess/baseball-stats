<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ball_in_plays', function (Blueprint $table) {
            $table->id();
            // Player, Play, Position
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('play_id')->constrained()->cascadeOnDelete();
            $table->json('position')->nullable();
            $table->string('type')->nullable();
            $table->integer('distance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ball_in_plays');
    }
};
