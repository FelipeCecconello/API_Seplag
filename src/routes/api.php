<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PessoaEnderecoController;
use App\Http\Controllers\Api\UnidadeEnderecoController;
use App\Http\Controllers\Api\PessoaFotoController;
use App\Http\Controllers\Api\ServidorEfetivoController;
use Illuminate\Support\Facades\Storage;

Route::apiResource('pessoa', \App\Http\Controllers\Api\PessoaController::class);
Route::apiResource('cidade', \App\Http\Controllers\Api\CidadeController::class);
Route::apiResource('endereco', \App\Http\Controllers\Api\EnderecoController::class);
Route::apiResource('unidade', \App\Http\Controllers\Api\UnidadeController::class);
Route::apiResource('lotacao', \App\Http\Controllers\Api\LotacaoController::class);
Route::apiResource('servidor_temporario', \App\Http\Controllers\Api\ServidorTemporarioController::class);
Route::apiResource('servidor_efetivo', \App\Http\Controllers\Api\ServidorEfetivoController::class);

Route::prefix('pessoa/{pessoa}/endereco')->group(function () {
    Route::get('/', [PessoaEnderecoController::class, 'index']);
    Route::post('/', [PessoaEnderecoController::class, 'store']);
    Route::put('/{endereco}', [PessoaEnderecoController::class, 'update']);
    Route::delete('/{endereco}', [PessoaEnderecoController::class, 'destroy']);
});

Route::prefix('unidade/{pessoa}/endereco')->group(function () {
    Route::get('/', [UnidadeEnderecoController::class, 'index']);
    Route::post('/', [UnidadeEnderecoController::class, 'store']);
    Route::put('/{endereco}', [UnidadeEnderecoController::class, 'update']);
    Route::delete('/{endereco}', [UnidadeEnderecoController::class, 'destroy']);
});

Route::post('/pessoa/{pessoa_id}/fotos', [PessoaFotoController::class, 'uploadFoto']);
Route::get('/pessoa/{pessoa_id}/fotos', [PessoaFotoController::class, 'getFotoTemporaria']);
Route::get('/servidor_efetivo/unidade/{unid_id}', [ServidorEfetivoController::class, 'porUnidade']);
