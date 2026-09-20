<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao');
            $table->date('data_evento');
            $table->time('hora_evento');
            $table->decimal('preco', 10, 2)->default(0);
            $table->string('endereco')->nullable();
            $table->unsignedInteger('vagas');
            $table->string('banner')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('local_id')->constrained('locais')->restrictOnDelete();
            $table->timestamps();

            $table->index('data_evento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
