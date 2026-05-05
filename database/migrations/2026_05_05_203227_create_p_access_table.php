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
        Schema::create('P_Access', function (Blueprint $table) {
            $table->increments('ID_Access');
            $table->unsignedBigInteger('ID_Users');
            $table->unsignedInteger('ID_Module');
            $table->unsignedInteger('ID_Operation');
            $table->unsignedInteger('ID_Rule');
            $table->timestamps();

            $table->foreign('ID_Users')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ID_Module')->references('ID_Module')->on('Module')->onDelete('cascade');
            $table->foreign('ID_Operation')->references('ID_Operation')->on('Operation')->onDelete('cascade');
            $table->foreign('ID_Rule')->references('ID_Rule')->on('Rule')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('P_Access');
    }
};
