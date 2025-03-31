<?php

namespace App\Http\Controllers\Api;

use App\Models\ServidorTemporario;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;

class ServidorTemporarioController extends BaseController
{
    public function index(Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
        
            $servidores = ServidorTemporario::paginate($perPage, ['*'], 'page', $page);
            return $this->sendResponse($servidores, 'Servidores temporarios recuperados com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }

    public function store(Request $request)
    {
        $rules = [
            'pes_id' => 'required|integer|exists:pessoa,pes_id',
            'st_data_admissao' => 'required|date',
            'st_data_demissao' => 'nullable|date',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $servidor = ServidorTemporario::create($validated);
            return $this->sendResponse($servidor, 'Servidor temporario criado com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar servidor temporario', $e);
        } 
    }

    public function show($id)
    {
        $servidor = ServidorTemporario::find($id);

        if (is_null($servidor)) {
            return $this->sendError('Servidor temporario não encontrado.');
        }

        return $this->sendResponse($servidor, 'Servidor temporario recuperado com sucesso.');
    }

    public function update(Request $request, ServidorTemporario $servidor)
    {
        $rules = [
            'pes_id' => 'sometimes|integer|exists:pessoa,pes_id',
            'st_data_admissao' => 'sometimes|date',
            'st_data_demissao' => 'sometimes|date',
        ];
        
        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }
        
        try {
            $servidor->update($validated);  
            return $this->sendResponse($servidor, 'Servidor temporario atualizado com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar servidor temporario', $e);
        } 
    }

    public function destroy(ServidorTemporario $servidor)
    {
        $servidor->delete();
        return $this->sendResponse([], 'Servidor temporario excluído com sucesso.');
    }
}