<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PessoaEnderecoController;
use App\Http\Controllers\Api\UnidadeEnderecoController;
use App\Http\Controllers\Api\PessoaFotoController;
use App\Http\Controllers\Api\ServidorEfetivoController;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\Api\UserController;

Route::prefix('auth')->group(function () {
    Route::post('login', [ApiAuthController::class, 'login']);
    Route::post('logout', [ApiAuthController::class, 'logout'])->middleware('auth.api');
    Route::post('renew', [ApiAuthController::class, 'renewToken'])->middleware('auth.api');
});

Route::middleware('auth.api')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);

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
    Route::post('/pessoa/{pessoa_id}/fotos/{foto_id}', [PessoaFotoController::class, 'updateFoto']);
    Route::get('/servidor_efetivo/unidade/{unid_id}', [ServidorEfetivoController::class, 'porUnidade']);
    Route::get('/servidor_efetivo/buscar/endereco', [ServidorEfetivoController::class, 'porNome']);
});


