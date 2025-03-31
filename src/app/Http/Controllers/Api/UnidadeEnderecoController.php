<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Unidade;
use App\Models\Endereco;
use Illuminate\Http\Request;

class UnidadeEnderecoController extends BaseController
{
    public function index(Request $request, $unidadeId)
    {   
        try {
            $unidade = Unidade::find($unidadeId);
        
            if (!$unidade) {
                return $this->sendError('Unidade não encontrada.', 404);
            }

            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            $enderecos = $unidade->enderecos()->paginate($perPage, ['*'], 'page', $page);
            
            return $this->sendResponse($enderecos, 'Endereços da unidade recuperados com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        }
    }

    public function store(Request $request, $unidadeId)
    {   
        $rules = [
            'end_id' => 'required|exists:endereco,end_id',
        ];

        $validated = $this->validateRequest($request, $rules);
        
        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $unidade = Unidade::find($unidadeId);
        
            if (!$unidade) {
                return $this->sendError('Unidade não encontrada.', 404);
            }
            
            if ($unidade->enderecos()->where('endereco.end_id', $validated['end_id'])->exists()) {
                return $this->sendError('Este endereço já está vinculado à unidade.', 409);
            }
    
            $unidade->enderecos()->attach($validated['end_id']);
    
            return $this->sendResponse(null, 'Endereço vinculado com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao vincular endereco', $e);
        } 
    }

    public function update($unidadeId, $enderecoId)
    {
        try {
            $unidade = Unidade::find($unidadeId);
            
            if (!$unidade) {
                return $this->sendError('Unidade não encontrada.', 404);
            }

            if (!$unidade->enderecos()->where('endereco.end_id', $enderecoId)->exists()) {
                return $this->sendError('Este endereço não está vinculado à unidade.', 404);
            }

            $unidade->enderecos()->updateExistingPivot($enderecoId);

            return $this->sendResponse(null, 'Vínculo atualizado com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar vínculo', $e);
        }
    }

    public function destroy($unidadeId, $enderecoId)
    {
        $unidade = Unidade::find($unidadeId);
        
        if (!$unidade) {
            return $this->sendError('Unidade não encontrada.', 404);
        }

        $endereco = Endereco::find($enderecoId);
        
        if (!$endereco) {
            return $this->sendError('Endereco não encontrado.', 404);
        }

        $unidade->enderecos()->detach($enderecoId);

        return $this->sendResponse(null, 'Endereço desvinculado com sucesso.');
    }
}