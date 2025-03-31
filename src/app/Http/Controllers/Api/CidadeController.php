<?php

namespace App\Http\Controllers\Api;

use App\Models\Cidade;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;

class CidadeController extends BaseController
{
    public function index(Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
        
            $cidades = Cidade::paginate($perPage, ['*'], 'page', $page);
            return $this->sendResponse($cidades, 'Cidades recuperadas com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }

    public function store(Request $request)
    {
        $rules = [
            'cid_nome' => 'required|string|max:200',
            'cid_uf' => 'required|string|max:2',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $unidade = Cidade::create($validated);
            return $this->sendResponse($unidade, 'Cidade criada com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar cidade', $e);
        } 
    }

    public function show($id)
    {
        $cidade = Cidade::find($id);

        if (is_null($cidade)) {
            return $this->sendError('Cidade não encontrada.');
        }

        return $this->sendResponse($cidade, 'Cidade recuperada com sucesso.');
    }

    public function update(Request $request, Cidade $cidade)
    {
        $rules = [
            'cid_nome' => 'sometimes|string|max:200',
            'cid_uf' => 'sometimes|string|max:2',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }
        
        try {
            $cidade->update($validated);  
            return $this->sendResponse($cidade, 'Cidade atualizada com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar cidade', $e);
        } 
    }

    public function destroy(Cidade $cidade)
    {
        $cidade->delete();
        return $this->sendResponse([], 'Cidade excluída com sucesso.');
    }
}