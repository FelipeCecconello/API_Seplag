<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('foto_pessoa', function (Blueprint $table) {
            $table->id('fp_id');
            $table->foreignId('pes_id')->constrained('pessoa', 'pes_id')->onDelete('cascade');
            $table->dateTime('fp_data')->useCurrent();
            $table->string('fp_bucket', 50);
            $table->string('fp_hash', 64); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('foto_pessoa');
    }
};