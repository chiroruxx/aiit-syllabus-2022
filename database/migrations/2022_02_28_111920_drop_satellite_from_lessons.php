<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropSatelliteFromLessons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropColumn('satellite');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->unsignedTinyInteger('satellite')->after('content');
        });
    }
}
