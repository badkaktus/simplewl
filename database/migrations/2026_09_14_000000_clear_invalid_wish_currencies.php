<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The currency select used to send "0" when no currency was chosen.
     */
    public function up(): void
    {
        DB::table('wishes')
            ->whereIn('currency', ['0', ''])
            ->update(['currency' => null]);
    }

    public function down(): void
    {
        //
    }
};
