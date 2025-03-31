<?php

namespace App\Http\Controllers\Api;

use App\Models\Endereco;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;

class EnderecoController extends BaseController
{
    public function index(Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
        
            $enderecos = Endereco::paginate($perPage, ['*'], 'page', $page);
            return $this->sendResponse($enderecos, 'Enderecos recuperados com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }

    public function store(Request $request)
    {
        $rules = [
            'end_tipo_logradouro' => 'required|string|max:50',
            'end_logradouro' => 'required|string|max:200',
            'end_numero' => 'required|integer',
            'end_bairro' => 'required|string|max:100',
            'cid_id' => 'required|integer|exists:cidade,cid_id',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $endereco = Endereco::create($validated);
            return $this->sendResponse($endereco, 'Endereco criado com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar endereco', $e);
        } 
    }

    public function show($id)
    {
        $endereco = Endereco::find($id);

        if (is_null($endereco)) {
            return $this->sendError('Endereco não encontrado.');
        }

        return $this->sendResponse($endereco, 'Endereco recuperado com sucesso.');
    }

    public function update(Request $request, Endereco $endereco)
    {
        $rules = [
            'end_tipo_logradouro' => 'sometimes|string|max:50',
            'end_logradouro' => 'sometimes|string|max:200',
            'end_numero' => 'sometimes|integer',
            'end_bairro' => 'sometimes|string|max:100',
            'cid_id' => 'sometimes|integer|exists:cidade,cid_id',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }
        
        try {
            $endereco->update($validated);  
            return $this->sendResponse($endereco, 'Endereco atualizado com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar endereco', $e);
        } 
    }

    public function destroy(Endereco $endereco)
    {
        $endereco->delete();
        return $this->sendResponse([], 'Endereco excluído com sucesso.');
    }
}