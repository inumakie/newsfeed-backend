<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('fav_categories')->nullable()->default(json_encode([
                'sports', 'arts and culture', 'music',
                'politics', 'science and technology', 'business and finance',
                'health', 'ecology', 'entertainment', 'travel and tourism'
            ]));
            
            $table->json('fav_sources')->nullable()->default(DB::raw('\'{"newsAPI": true, "nyt": true, "guardian": true}\''));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('fav_sources')->nullable();
            $table->json('fav_authors')->nullable();
            $table->json('fav_categories')->nullable();
        });
    }
};
