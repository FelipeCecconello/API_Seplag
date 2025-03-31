<?php

namespace App\Http\Controllers\Api;

use App\Models\Pessoa;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;

class PessoaController extends BaseController
{
    public function index(Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
        
            $pessoas = Pessoa::paginate($perPage, ['*'], 'page', $page);
            return $this->sendResponse($pessoas, 'Pessoas recuperadas com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }

    public function store(Request $request)
    {
        $rules = [
            'pes_nome' => 'required|string|max:200',
            'pes_sexo' => 'required|string|max:9',
            'pes_data_nascimento' => 'required|date',
            'pes_mae' => 'required|string|max:200',
            'pes_pai' => 'required|string|max:200',
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $pessoa = Pessoa::create($validated);
            return $this->sendResponse($pessoa, 'Pessoa criada com sucesso.', 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar pessoa', $e);
        } 
    }

    public function show($id)
    {
        $pessoa = Pessoa::find($id);

        if (is_null($pessoa)) {
            return $this->sendError('Pessoa não encontrada.');
        }

        return $this->sendResponse($pessoa, 'Pessoa recuperada com sucesso.');
    }

    public function update(Request $request, Pessoa $pessoa)
    {
       $rules = [
            'pes_nome' => 'sometimes|string|max:200',
            'pes_sexo' => 'sometimes|string|unique:pessoa|max:9',
            'pes_data_nascimento' => 'sometimes|date',
            'pes_mae' => 'sometimes|string|unique:pessoa|max:200',
            'pes_pai' => 'sometimes|string|unique:pessoa|max:200',

        ];
        
        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }
        
        try {
            $pessoa->update($validated);  
            return $this->sendResponse($pessoa, 'Pessoa atualizada com sucesso.');
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar pessoa', $e);
        } 
    }

    public function destroy(Pessoa $pessoa)
    {
        $pessoa->delete();
        return $this->sendResponse([], 'Pessoa excluída com sucesso.');
    }
}