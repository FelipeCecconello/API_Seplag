<?php

namespace App\Http\Controllers\Api;

use App\Models\Unidade;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\Cidade;
use App\Models\Endereco;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnidadeController extends BaseController
{
    public function index(Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
        
            $unidades = Unidade::with(['enderecos.cidade'])->paginate($perPage, ['*'], 'page', $page);
            return $this->sendResponse($unidades, 'Unidades recuperadas com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }
    
    public function store(Request $request)
    {
        $rules = [
            'unid_nome' => 'required|string|max:200',
            'unid_sigla' => 'required|string|max:20',

            'end_tipo_logradouro' => 'required|string|max:50',
            'end_logradouro' => 'required|string|max:200',
            'end_numero' => 'required|integer',
            'end_bairro' => 'required|string|max:100',
            'cid_nome' => 'required|string|max:200',
            'cid_uf' => 'required|string|max:2',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
             $cidade = Cidade::firstOrCreate(
                [
                    'cid_nome' => $validated['cid_nome'],
                    'cid_uf' => $validated['cid_uf']
                ]
            );

            $endereco = Endereco::create([
                'end_tipo_logradouro' => $validated['end_tipo_logradouro'],
                'end_logradouro' => $validated['end_logradouro'],
                'end_numero' => $validated['end_numero'],
                'end_bairro' => $validated['end_bairro'],
                'cid_id' => $cidade->cid_id
            ]);

            $unidade = Unidade::create($validated);

            $unidade->enderecos()->attach($endereco->end_id);

            DB::commit();
            return $this->sendResponse($unidade, 'Unidade criada com sucesso.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('Erro ao criar unidade', $e);
        } 
    }

    public function show($id)
    {
        $unidade = Unidade::with([
            'enderecos.cidade', 
        ])->findOrFail($id);

        return $this->sendResponse($unidade, 'Unidade recuperada com sucesso.');
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $rules = [
                'unid_nome' => 'sometimes|string|max:200',
                'unid_sigla' => 'sometimes|string|max:20',

                'end_tipo_logradouro' => 'sometimes|string|max:50',
                'end_logradouro' => 'sometimes|string|max:200',
                'end_numero' => 'sometimes|integer',
                'end_bairro' => 'sometimes|string|max:100',
                'cid_nome' => 'sometimes|string|max:200',
                'cid_uf' => 'sometimes|string|max:2',
            ];

            $validated = $this->validateRequest($request, $rules);

            if ($validated instanceof \Illuminate\Http\JsonResponse) {
                return $validated; 
            }

            $unidade = Unidade::findOrFail($id);
            $endereco = $unidade->enderecos->first();

            $unidade->update($request->only(['unid_nome', 'unid_sigla']));

            if ($request->hasAny(['end_tipo_logradouro', 'end_logradouro', 'end_numero', 'end_bairro', 'cid_nome', 'cid_uf'])) {
                $cidade = Cidade::firstOrCreate(
                    [
                        'cid_nome' => $validated['cid_nome'] ?? $endereco->cidade->cid_nome,
                        'cid_uf' => $validated['cid_uf'] ?? $endereco->cidade->cid_uf
                    ]
                );

                $endereco->update([
                    'end_tipo_logradouro' => $validated['end_tipo_logradouro'] ?? $endereco->end_tipo_logradouro,
                    'end_logradouro' => $validated['end_logradouro'] ?? $endereco->end_logradouro,
                    'end_numero' => $validated['end_numero'] ?? $endereco->end_numero,
                    'end_bairro' => $validated['end_bairro'] ?? $endereco->end_bairro,
                    'cid_id' => $cidade->cid_id
                ]);
            }

            DB::commit();
            return $this->sendResponse($unidade->fresh(['enderecos.cidade']), 'Unidade atualizada com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('Erro ao atualizar unidade', $e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $unidade = Unidade::with('enderecos')->findOrFail($id);
            
            if ($unidade->lotacoes()->exists()) {
                return $this->sendError('Não é possível excluir a unidade pois existem servidores lotados nela.', 422);
            }

            $unidade->enderecos()->detach();
            
            $unidade->delete();

            DB::commit();
            return $this->sendResponse(null, 'Unidade removida com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('Erro ao remover unidade', $e);
        }
    }
}