<?php

namespace App\Http\Controllers\Api;

use App\Models\ServidorEfetivo;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\Lotacao;
use App\Models\Pessoa;
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

    public function porUnidade($unid_id, Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            
            $lotacoes = Lotacao::with(['pessoa.servidorEfetivo', 'unidade', 'pessoa.fotos'])
                ->where('unid_id', $unid_id)
                ->paginate($perPage);

            $servidores = $lotacoes->through(function ($lotacao) {
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
                
            return response()->json([
                'message' => 'Sucesso',
                'data' => $servidores->items(),
                'meta' => [
                    'current_page' => $servidores->currentPage(),
                    'per_page' => $servidores->perPage(),
                    'total' => $servidores->total(),
                    'last_page' => $servidores->lastPage(),
                    'from' => $servidores->firstItem(),
                    'to' => $servidores->lastItem()
                ],
                'links' => [
                    'first' => $servidores->url(1),
                    'last' => $servidores->url($servidores->lastPage()),
                    'prev' => $servidores->previousPageUrl(),
                    'next' => $servidores->nextPageUrl()
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao buscar servidores', $e);
        }
    }

    public function porNome(Request $request)
    {
        $rules = [
            'nome' => 'required|string',
            'per_page' => 'sometimes|integer|min:1|max:100' 
        ];
        
        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $termo = $request->input('nome');
            $perPage = $request->input('per_page', 15); 
        
            $pessoas = Pessoa::with(['servidorEfetivo', 'lotacao.unidade.enderecos.cidade'])
                ->where('pes_nome', 'LIKE', "%{$termo}%")
                ->whereHas('servidorEfetivo')
                ->paginate($perPage);

            if ($pessoas->isEmpty()) {
                return response()->json([
                    'message' => 'Nenhum servidor encontrado com o nome fornecido'
                ], 404);
            }

            $resultados = $pessoas->through(function ($pessoa) {
                $enderecoUnidade = $pessoa->lotacao->unidade->enderecos->first();

                return [
                    'nome' => $pessoa->pes_nome,
                    'unidade' => $pessoa->lotacao->unidade->unid_nome,
                    'endereco_unidade' => $enderecoUnidade ? [
                        'end_tipo_logradouro' => $enderecoUnidade->end_tipo_logradouro,
                        'logradouro' => $enderecoUnidade->end_logradouro,
                        'numero' => $enderecoUnidade->end_numero,
                        'bairro' => $enderecoUnidade->end_bairro,
                        'cidade' => $enderecoUnidade->cidade ? [
                            'nome' => $enderecoUnidade->cidade->cid_nome,
                            'UF' => $enderecoUnidade->cidade->cid_uf
                        ] : null
                    ] : null
                ];
            });
        
            return response()->json([
                'data' => $resultados->items(),
                'meta' => [
                    'current_page' => $resultados->currentPage(),
                    'per_page' => $resultados->perPage(),
                    'total' => $resultados->total(),
                    'last_page' => $resultados->lastPage(),
                    'from' => $resultados->firstItem(),
                    'to' => $resultados->lastItem()
                ],
                'links' => [
                    'first' => $resultados->url(1),
                    'last' => $resultados->url($resultados->lastPage()),
                    'prev' => $resultados->previousPageUrl(),
                    'next' => $resultados->nextPageUrl()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao buscar servidores', $e);
        }
    }
}