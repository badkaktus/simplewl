<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameDuplicateNames();

        Schema::table('users', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }

    private function renameDuplicateNames(): void
    {
        $duplicateNames = DB::table('users')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            $oldestUserId = DB::table('users')->where('name', $name)->min('id');
            $userIds = DB::table('users')
                ->where('name', $name)
                ->where('id', '!=', $oldestUserId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($userIds as $userId) {
                $newName = $name.'-'.$userId;
                while (DB::table('users')->where('name', $newName)->exists()) {
                    $newName .= '-'.$userId;
                }

                DB::table('users')->where('id', $userId)->update(['name' => $newName]);
            }
        }
    }
};
