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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('P_Access');
        Schema::dropIfExists('Operation');
        Schema::dropIfExists('Programs');
        Schema::dropIfExists('Module');
        Schema::enableForeignKeyConstraints();

        Schema::create('P_modul', function (Blueprint $table) {
            $table->id();
            $table->string('nazwa');
            $table->integer('pozycja')->default(0);
            $table->timestamps();
        });

        Schema::create('P_operacje', function (Blueprint $table) {
            $table->id();
            $table->string('nazwa');
            $table->timestamps();
        });

        Schema::create('P_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('p_modul_id')->constrained('P_modul')->onDelete('cascade');
            $table->foreignId('p_operacje_id')->constrained('P_operacje')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('P_access');
        Schema::dropIfExists('P_operacje');
        Schema::dropIfExists('P_modul');
        Schema::enableForeignKeyConstraints();
    }
};
