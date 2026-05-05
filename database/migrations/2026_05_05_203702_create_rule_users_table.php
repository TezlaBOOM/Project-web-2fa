<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RuleUsers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('ID_Users');
            $table->unsignedInteger('ID_Rule');
            $table->timestamps();

            $table->foreign('ID_Users')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ID_Rule')->references('ID_Rule')->on('Rule')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RuleUsers');
    }
};
