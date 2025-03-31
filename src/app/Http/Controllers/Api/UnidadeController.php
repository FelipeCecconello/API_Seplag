<?php

namespace App\Http\Controllers\Api;

use App\Models\Unidade;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;

class UnidadeController extends BaseController
{
    public function index(Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
        
            $unidades = Unidade::paginate($perPage, ['*'], 'page', $page);
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
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $unidade = Unidade::create($validated);
            return $this->sendResponse($unidade, 'Unidade criada com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar unidade', $e);
        } 
    }

    public function show($id)
    {
        $unidade = Unidade::find($id);

        if (is_null($unidade)) {
            return $this->sendError('Unidade não encontrada.');
        }

        return $this->sendResponse($unidade, 'Unidade recuperada com sucesso.');
    }

    public function update(Request $request, Unidade $unidade)
    {
        $rules = [
            'unid_nome' => 'sometimes|string|max:200',
            'unid_sigla' => 'sometimes|string|max:20',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }
        
        try {
            $unidade->update($validated);  
            return $this->sendResponse($unidade, 'Unidade atualizada com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar unidade', $e);
        } 
    }

    public function destroy(Unidade $unidade)
    {
        $unidade->delete();
        return $this->sendResponse([], 'Unidade excluída com sucesso.');
    }
}