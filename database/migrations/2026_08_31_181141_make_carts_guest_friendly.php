<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('session_id')->nullable()->after('user_id');
        });

        DB::statement('ALTER TABLE carts MODIFY user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });

        DB::statement('ALTER TABLE carts MODIFY user_id BIGINT UNSIGNED NOT NULL');
    }
};