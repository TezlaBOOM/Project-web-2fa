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
        Schema::create('Programs', function (Blueprint $table) {
            $table->increments('ID_Programs');
            $table->string('Nazwa', 255);
            $table->text('Description')->nullable();
            $table->unsignedInteger('ID_Module');
            $table->timestamps();

            $table->foreign('ID_Module')->references('ID_Module')->on('Module')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Programs');
    }
};
