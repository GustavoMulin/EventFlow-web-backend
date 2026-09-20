<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscricoes', function (Blueprint $table) {
            $table->id();
            $table->uuid('codigo')->unique();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->string('email', 150);
            $table->string('documento', 30)->nullable();
            $table->timestamps();

            $table->unique(['evento_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscricoes');
    }
};
