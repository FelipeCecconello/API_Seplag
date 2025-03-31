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
        Schema::create('endereco', function (Blueprint $table) {
            $table->id('end_id');
            $table->string('end_tipo_logradouro', 50);
            $table->string('end_logradouro', 200);
            $table->integer('end_numero');
            $table->string('end_bairro', 100);
            $table->foreignId('cid_id')->constrained('cidade', 'cid_id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endereco');
    }
};
