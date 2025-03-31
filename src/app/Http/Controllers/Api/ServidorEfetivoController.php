<?php

namespace App\Http\Controllers\Api;

use App\Models\ServidorEfetivo;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\Lotacao;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ServidorEfetivoController extends BaseController
{
    public function index(Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
        
            $servidores = ServidorEfetivo::paginate($perPage, ['*'], 'page', $page);
            return $this->sendResponse($servidores, 'Servidores efetivos recuperados com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }

    public function store(Request $request)
    {
        $rules = [
            'pes_id' => 'required|integer|exists:pessoa,pes_id',
            'se_matricula' => 'required|string|max:20',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $servidor = ServidorEfetivo::create($validated);
            return $this->sendResponse($servidor, 'Servidor efetivo criado com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar servidor efetivo', $e);
        } 
    }

    public function show($id)
    {
        $servidor = ServidorEfetivo::find($id);

        if (is_null($servidor)) {
            return $this->sendError('Servidor efetivo não encontrado.');
        }

        return $this->sendResponse($servidor, 'Servidor efetivo recuperado com sucesso.');
    }

    public function update(Request $request, ServidorEfetivo $servidor)
    {
        $rules = [
            'pes_id' => 'sometimes|integer|exists:pessoa,pes_id',
            'se_matricula' => 'sometimes|string|max:20',
        ];
        
        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }
        
        try {
            $servidor->update($validated);  
            return $this->sendResponse($servidor, 'Servidor efetivo atualizado com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar servidor efetivo', $e);
        } 
    }

    public function destroy(ServidorEfetivo $servidor)
    {
        $servidor->delete();
        return $this->sendResponse([], 'Servidor efetivo excluído com sucesso.');
    }

    public function porUnidade($unid_id)
    {
        try {
            $lotacoes = Lotacao::with(['pessoa.servidorEfetivo'])
                ->where('unid_id', $unid_id)
                ->get();

                $servidores = $lotacoes->map(function ($lotacao) {
                    $pessoa = $lotacao->pessoa;
    
                    return [
                        'nome' => $pessoa->pes_nome,
                        'idade' => Carbon::parse($pessoa->pes_data_nascimento)->age,
                        'unidade' => $lotacao->unidade->unid_nome,
                        'fotografia' => $pessoa->fotos->first() ? 
                            Storage::temporaryUrl(
                                $pessoa->fotos->first()->fp_hash,
                                now()->addMinutes(5)
                            ) : null
                    ];
                });
                
            return response()->json(['message' => 'Teste', 'data' => $servidores], 200);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao buscar servidores', $e);
        }
    }
}