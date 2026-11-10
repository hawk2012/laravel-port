<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan', 20)->default('free')->after('email');
            $table->unsignedInteger('credits_balance')->default(500)->after('plan');
            $table->timestamp('last_generation_at')->nullable()->after('credits_balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan', 'credits_balance', 'last_generation_at']);
        });
    }
};
