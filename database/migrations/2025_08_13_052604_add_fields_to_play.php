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
        Schema::table('plays', function (Blueprint $table) {
            $table->integer('inning')->nullable()->after('play');
            $table->integer('inning_half')->nullable()->after('inning');
            $table->boolean('run_scoring')->default(false)->after('inning_half');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropColumn(['inning', 'inning_half', 'run_scoring']);
        });
    }
};
