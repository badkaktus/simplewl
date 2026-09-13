<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROVIDER_COLUMNS = ['google_id', 'telegram_id', 'vk_id', 'fb_id', 'github_id'];

    public function up(): void
    {
        DB::transaction(fn () => $this->mergeDuplicateRows());

        Schema::table('user_attributes', function (Blueprint $table) {
            $table->unique('id');
        });
    }

    public function down(): void
    {
        Schema::table('user_attributes', function (Blueprint $table) {
            $table->dropUnique(['id']);
        });
    }

    /**
     * Every OAuth login used to insert a new row, so one user could have several rows.
     */
    private function mergeDuplicateRows(): void
    {
        $duplicateIds = DB::table('user_attributes')
            ->select('id')
            ->groupBy('id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('id');

        foreach ($duplicateIds as $id) {
            $rows = DB::table('user_attributes')->where('id', $id)->orderBy('created_at')->get();

            $merged = [
                'id' => $id,
                'created_at' => $rows->first()->created_at,
                'updated_at' => $rows->max('updated_at'),
            ];
            foreach (self::PROVIDER_COLUMNS as $column) {
                $merged[$column] = $rows->pluck($column)->filter(fn ($value) => $value !== null)->first();
            }

            // Provider columns are unique, so old rows are deleted before the merged row is inserted
            DB::table('user_attributes')->where('id', $id)->delete();
            DB::table('user_attributes')->insert($merged);
        }
    }
};
