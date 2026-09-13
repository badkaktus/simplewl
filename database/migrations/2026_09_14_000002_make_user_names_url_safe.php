<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Names with "/" or equal to "." and ".." made public wishlist URLs unreachable.
     */
    public function up(): void
    {
        $users = DB::table('users')
            ->where('name', 'like', '%/%')
            ->orWhereIn('name', User::RESERVED_NAMES)
            ->orderBy('id')
            ->get(['id', 'name']);

        foreach ($users as $user) {
            $name = str_replace('/', '-', $user->name);
            if (in_array($name, User::RESERVED_NAMES, true)) {
                $name = 'user';
            }

            while (DB::table('users')->where('name', $name)->where('id', '!=', $user->id)->exists()) {
                $name .= '-'.$user->id;
            }

            DB::table('users')->where('id', $user->id)->update(['name' => $name]);
        }
    }

    public function down(): void
    {
        //
    }
};
