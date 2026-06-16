<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->decimal('payment_amount', 10, 2)->nullable()->after('payment_id');
            $table->string('payment_plan', 50)->nullable()->after('payment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['payment_amount', 'payment_plan']);
        });
    }
};
