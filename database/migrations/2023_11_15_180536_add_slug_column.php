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
        Schema::table('wishes', function (Blueprint $table) {
            $table->string('slug', 255)->nullable();
            $table->unique(['wishlist_id', 'slug']);
        });
        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('slug', 255)->nullable();
            $table->unique(['user_id', 'slug']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishes', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
