<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_claims', function (Blueprint $table) {
            $table->string('suggested_name')->nullable()->after('message');
            $table->string('suggested_phone', 20)->nullable()->after('suggested_name');
            $table->string('suggested_whatsapp', 20)->nullable()->after('suggested_phone');
            $table->string('suggested_email')->nullable()->after('suggested_whatsapp');
            $table->string('suggested_category', 100)->nullable()->after('suggested_email');
            $table->string('suggested_city', 100)->nullable()->after('suggested_category');
            $table->text('suggested_address')->nullable()->after('suggested_city');
            $table->longText('suggested_description')->nullable()->after('suggested_address');
        });
    }

    public function down(): void
    {
        Schema::table('business_claims', function (Blueprint $table) {
            $table->dropColumn([
                'suggested_name',
                'suggested_phone',
                'suggested_whatsapp',
                'suggested_email',
                'suggested_category',
                'suggested_city',
                'suggested_address',
                'suggested_description',
            ]);
        });
    }
};
