<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wishes', function (Blueprint $table) {
            $table->string('local_file_name', 255)->nullable()->default(null)->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('wishes', function (Blueprint $table) {
            $table->dropColumn('local_file_name');
        });
    }
};
