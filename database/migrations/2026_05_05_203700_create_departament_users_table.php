<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DepartamentUsers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('ID_Users');
            $table->unsignedInteger('ID_Departament');
            $table->timestamps();

            $table->foreign('ID_Users')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ID_Departament')->references('ID_Departament')->on('Departament')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DepartamentUsers');
    }
};
