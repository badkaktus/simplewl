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
        Schema::create('wishes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wishlist_id', false, true);
            $table->string('title', 255);
            $table->longText('description')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('image_url', 255)->nullable();
            $table->decimal('amount', 16)->nullable();
            $table->string('currency', 4)->nullable();
            $table->datetimes();

            $table->foreign('wishlist_id')->references('id')->on('wishlists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishes');
    }
};
