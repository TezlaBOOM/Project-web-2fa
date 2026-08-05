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
        Schema::create('p_access_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('p_modul_id');
            $table->unsignedBigInteger('p_operacje_id');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('login')->nullable();
            $table->text('uwagi')->nullable();
            $table->string('action'); // e.g. 'nadano', 'zaktualizowano', 'odebrano', 'wygasło'
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('p_modul_id')->references('id')->on('P_modul')->onDelete('cascade');
            $table->foreign('p_operacje_id')->references('id')->on('P_operacje')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p_access_history');
    }
};
