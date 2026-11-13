<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero_ciclo');
            $table->unsignedSmallInteger('ano');
            $table->unsignedTinyInteger('numero_grupo');
            $table->boolean('abierto')->default(true);
            
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['curso_id', 'numero_ciclo', 'ano', 'numero_grupo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
