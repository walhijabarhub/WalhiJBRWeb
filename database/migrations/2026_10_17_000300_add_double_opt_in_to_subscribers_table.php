<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Double opt-in for the newsletter: a subscriber is only active once confirmed,
     * and every subscriber gets an unsubscribe token.
     */
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('is_active');
        });

        // Existing active subscribers predate double opt-in: keep them subscribed.
        DB::table('subscribers')->where('is_active', true)->update(['confirmed_at' => DB::raw('created_at')]);

        DB::table('subscribers')->whereNull('token')->orderBy('id')->each(function ($row) {
            DB::table('subscribers')->where('id', $row->id)->update(['token' => Str::random(64)]);
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->unique('token');
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn('confirmed_at');
        });
    }
};
