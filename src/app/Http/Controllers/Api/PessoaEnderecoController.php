<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Pessoa;
use App\Models\Endereco;
use Illuminate\Http\Request;

class PessoaEnderecoController extends BaseController
{
    public function index(Request $request, $pessoaId)
    {   
        try {
            $pessoa = Pessoa::find($pessoaId);
        
            if (!$pessoa) {
                return $this->sendError('Pessoa não encontrada.', 404);
            }
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            $enderecos = $pessoa->enderecos()->paginate($perPage, ['*'], 'page', $page);
            
            return $this->sendResponse($enderecos, 'Endereços da pessoa recuperados com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }

    public function store(Request $request, $pessoaId)
    {   
        $rules = [
            'end_id' => 'required|exists:endereco,end_id',
        ];

        $validated = $this->validateRequest($request, $rules);
        
        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $pessoa = Pessoa::find($pessoaId);
        
            if (!$pessoa) {
                return $this->sendError('Pessoa não encontrada.', 404);
            }
            
            if ($pessoa->enderecos()->where('endereco.end_id', $validated['end_id'])->exists()) {
                return $this->sendError('Este endereço já está vinculado à pessoa.', 409);
            }
    
            $pessoa->enderecos()->attach($validated['end_id']);
    
            return $this->sendResponse(null, 'Endereço vinculado com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao vincular endereco', $e);
        } 
    }

    public function update($pessoaId, $enderecoId)
    {
        try {
            $pessoa = Pessoa::find($pessoaId);
        
            if (!$pessoa) {
                return $this->sendError('Pessoa não encontrada.', 404);
            }

            if (!$pessoa->enderecos()->where('endereco.end_id', $enderecoId)->exists()) {
                return $this->sendError('Este endereço não está vinculado à pessoa.', 404);
            }

            $pessoa->enderecos()->updateExistingPivot($enderecoId);

            return $this->sendResponse(null, 'Vínculo atualizado com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar vínculo', $e);
        }
    }

    public function destroy($pessoaId, $enderecoId)
    {
        $pessoa = Pessoa::find($pessoaId);
        
        if (!$pessoa) {
            return $this->sendError('Pessoa não encontrada.', 404);
        }

        $endereco = Endereco::find($enderecoId);
        
        if (!$endereco) {
            return $this->sendError('Endereco não encontrado.', 404);
        }

        $pessoa->enderecos()->detach($enderecoId);

        return $this->sendResponse(null, 'Endereço desvinculado com sucesso.');
    }
}