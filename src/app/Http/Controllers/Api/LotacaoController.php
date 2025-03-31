<?php

namespace App\Http\Controllers\Api;

use App\Models\Lotacao;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;

class LotacaoController extends BaseController
{
    public function index(Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
        
            $lotacoes = Lotacao::paginate($perPage, ['*'], 'page', $page);
            return $this->sendResponse($lotacoes, 'Lotacoes recuperadas com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }

    public function store(Request $request)
    {
        $rules = [
            'pes_id' => 'required|integer|exists:pessoa,pes_id',
            'unid_id' => 'required|integer|exists:unidade,unid_id',
            'lot_data_lotacao' => 'required|date',
            'lot_data_remocao' => 'nullable|date',
            'lot_portaria' => 'required|string|max:100',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $lotacao = Lotacao::create($validated);
            return $this->sendResponse($lotacao, 'Lotacao criada com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar lotacao', $e);
        } 
    }

    public function show($id)
    {
        $lotacao = Lotacao::find($id);

        if (is_null($lotacao)) {
            return $this->sendError('Lotacao não encontrada.');
        }

        return $this->sendResponse($lotacao, 'Lotacao recuperada com sucesso.');
    }

    public function update(Request $request, Lotacao $lotacao)
    {
        $rules = [
            'pes_id' => 'sometimes|integer|exists:pessoa,pes_id',
            'unid_id' => 'sometimes|integer|exists:unidade,unid_id',
            'lot_data_lotacao' => 'sometimes|date',
            'lot_data_remocao' => 'sometimes|date',
            'lot_portaria' => 'sometimes|string|max:100',
        ];
        
        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }
        
        try {
            $lotacao->update($validated);  
            return $this->sendResponse($lotacao, 'Lotacao atualizada com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar lotacao', $e);
        } 
    }

    public function destroy(Lotacao $lotacao)
    {
        $lotacao->delete();
        return $this->sendResponse([], 'Lotacao excluída com sucesso.');
    }
}