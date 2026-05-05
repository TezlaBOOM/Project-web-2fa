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
        Schema::create('Module', function (Blueprint $table) {
            $table->increments('ID_Module');
            $table->string('Nazwa', 255);
            $table->text('Description')->nullable();
            $table->integer('Position');
            $table->string('Extension', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Module');
    }
};
