<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->string('image')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_products', function (Blueprint $table) {
            $table->string('image')->nullable(false)->default('')->change(); // Sesuaikan jika perlu saat rollback
        });
    }
};
