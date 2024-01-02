<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('user_attributes', function (Blueprint $table) {
            $table->integer('id')->unsigned();
            $table->decimal('google_id', 30, 0, true)->nullable()->unique()->index();
            $table->decimal('telegram_id', 30, 0, true)->nullable()->unique()->index();
            $table->decimal('vk_id', 30, 0, true)->nullable()->unique()->index();
            $table->decimal('fb_id', 30, 0, true)->nullable()->unique()->index();
            $table->timestamps();

            $table->foreign('id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_attributes');
    }
};
