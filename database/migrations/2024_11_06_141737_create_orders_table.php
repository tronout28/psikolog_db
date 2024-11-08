<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('buku_id')->nullable();
            $table->unsignedBigInteger('paket_id')->nullable();
            $table->unsignedBigInteger('voucher_id')->nullable();

            $table->string('name');
            $table->string('detailed_address');
            $table->string('phone_number');
            $table->bigInteger('total_price')->nullable();
            $table->enum('status',['paid','unpaid'])->default('unpaid');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('buku_id')->references('id')->on('bukus')->onDelete('cascade');
            $table->foreign('paket_id')->references('id')->on('pakets')->onDelete('cascade');
            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['buku_id']);
            $table->dropForeign(['paket_id']);
            $table->dropForeign(['voucher_id']);
        });
        Schema::dropIfExists('orders');
    }
};