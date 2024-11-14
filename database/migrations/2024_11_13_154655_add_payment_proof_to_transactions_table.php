<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('total'); // Sesuaikan dengan kolom terakhir di tabel Anda
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('payment_proof');
        });
    }
};
