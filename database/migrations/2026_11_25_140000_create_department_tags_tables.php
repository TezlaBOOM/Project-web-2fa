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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('departament_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ID_Departament');
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->foreign('ID_Departament')->references('ID_Departament')->on('Departament')->cascadeOnDelete();
            $table->unique(['ID_Departament', 'tag_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departament_tag');
        Schema::dropIfExists('tags');
    }
};
