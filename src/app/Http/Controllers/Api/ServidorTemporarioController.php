<?php

namespace App\Http\Controllers\Api;

use App\Models\ServidorTemporario;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\Cidade;
use App\Models\Endereco;
use App\Models\FotoPessoa;
use App\Models\Pessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ServidorTemporarioController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $servidores = ServidorTemporario::with([
            'pessoa.enderecos.cidade',
            'pessoa.fotos',
            'pessoa.lotacao.unidade'
        ])
        ->paginate($perPage);

        $servidores->getCollection()->transform(function($servidor) {
            if ($servidor->pessoa->fotos) {
                $servidor->pessoa->fotos->each(function($foto) {
                    $foto->url_temporaria = Storage::temporaryUrl(
                        $foto->fp_hash,
                        now()->addMinutes(5)
                    );
                });
            }
            return $servidor;
        });

        return $this->sendResponse($servidores, 'Servidores temporários listados com sucesso.');
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $rules = [
                'pes_nome' => 'required|string|max:200',
                'pes_sexo' => 'required|string|max:9',
                'pes_data_nascimento' => 'required|date',
                'pes_mae' => 'required|string|max:200',
                'pes_pai' => 'required|string|max:200',
                
                'st_data_admissao' => 'required|date',
                'st_data_demissao' => 'nullable|date',

                'end_tipo_logradouro' => 'required|string|max:50',
                'end_logradouro' => 'required|string|max:200',
                'end_numero' => 'required|integer',
                'end_bairro' => 'required|string|max:100',
                'cid_nome' => 'required|string|max:200',
                'cid_uf' => 'required|string|max:2',
                
                'fotos' => 'required|array|max:5',
                'fotos.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',

                'unid_id' => 'required|exists:unidade,unid_id',
                'lot_portaria' => 'required|string',
                'lot_data_lotacao' => 'required|date',
                'lot_data_remocao' => 'nullable|date'
            ];
    
            $validated = $this->validateRequest($request, $rules);

            if ($validated instanceof \Illuminate\Http\JsonResponse) {
                return $validated; 
            }

            $cidade = Cidade::firstOrCreate(
                [
                    'cid_nome' => $validated['cid_nome'],
                    'cid_uf' => $validated['cid_uf']
                ]
            );

            $pessoa = Pessoa::create([
                'pes_nome' => $validated['pes_nome'],
                'pes_sexo' => $validated['pes_sexo'],
                'pes_data_nascimento' => $validated['pes_data_nascimento'],
                'pes_mae' => $validated['pes_mae'],
                'pes_pai' => $validated['pes_pai']
            ]);

            $endereco = Endereco::create([
                'end_tipo_logradouro' => $validated['end_tipo_logradouro'],
                'end_logradouro' => $validated['end_logradouro'],
                'end_numero' => $validated['end_numero'],
                'end_bairro' => $validated['end_bairro'],
                'cid_id' => $cidade->cid_id
            ]);

            $pessoa->enderecos()->attach($endereco->end_id);

            $servidor = ServidorTemporario::create([
                'pes_id' => $pessoa->pes_id,
                'st_data_admissao' => $validated['st_data_admissao'],
                'st_data_demissao' => $validated['st_data_demissao']
            ]);

            $pessoa->lotacao()->create([
                'unid_id' => $validated['unid_id'],
                'lot_portaria' => $validated['lot_portaria'],
                'lot_data_lotacao' => $validated['lot_data_lotacao'],
                'lot_data_remocao' => $validated['lot_data_remocao'],
                'pes_id' => $pessoa->pes_id
            ]);

            foreach ($request->file('fotos') as $foto) {
                $hash = md5(time() . $foto->getClientOriginalName());
                $fileName = "{$pessoa->pes_id}/{$hash}";
                
                Storage::put($fileName, file_get_contents($foto));
                
                FotoPessoa::create([
                    'pes_id' => $pessoa->pes_id,
                    'fp_bucket' => env('AWS_BUCKET', 'pessoa-fotos'),
                    'fp_hash' => $fileName
                ]);
            }

            DB::commit();

            return $this->sendResponse(
                $servidor->load(['pessoa', 'pessoa.enderecos', 'pessoa.lotacao']), 
                'Servidor temporário cadastrado com sucesso.', 
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('Erro ao cadastrar servidor temporário', $e);
        }
    }

    public function show($id)
    {
        try {
            $servidor = ServidorTemporario::with([
                'pessoa.enderecos.cidade', 
                'pessoa.fotos',
                'pessoa.lotacao.unidade'
            ])->findOrFail($id);

            if ($servidor->pessoa && $servidor->pessoa->fotos) {
                $servidor->pessoa->fotos->transform(function ($foto) {
                    $foto->url_temporaria = Storage::temporaryUrl(
                        $foto->fp_hash,
                        now()->addMinutes(5)
                    );
                    return $foto;
                });
            }

            return $this->sendResponse($servidor, 'Servidor encontrado.');

        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao buscar servidor', $e);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $rules = [
                'pes_nome' => 'sometimes|string|max:200',
                'pes_sexo' => 'sometimes|string|max:9',
                'pes_data_nascimento' => 'sometimes|date',
                'pes_mae' => 'sometimes|string|max:200',
                'pes_pai' => 'sometimes|string|max:200',
                
                'st_data_admissao' => 'sometimes|date',
                'st_data_demissao' => 'nullable|date',
                
                'end_tipo_logradouro' => 'sometimes|string|max:50',
                'end_logradouro' => 'sometimes|string|max:200',
                'end_numero' => 'sometimes|integer',
                'end_bairro' => 'sometimes|string|max:100',
                'cid_nome' => 'sometimes|string|max:200',
                'cid_uf' => 'sometimes|string|max:2',
                
                'unid_id' => 'sometimes|exists:unidade,unid_id',
                'lot_portaria' => 'sometimes|string',
                'lot_data_lotacao' => 'sometimes|date',
                'lot_data_remocao' => 'nullable|date'
            ];

            $validated = $this->validateRequest($request, $rules);

            if ($validated instanceof \Illuminate\Http\JsonResponse) {
                return $validated; 
            }

            $servidor = ServidorTemporario::findOrFail($id);
            $pessoa = $servidor->pessoa;
            $endereco = $pessoa->enderecos->first();
            $lotacao = $pessoa->lotacao;
            
            if ($request->hasAny(['pes_nome', 'pes_sexo', 'pes_data_nascimento', 'pes_mae', 'pes_pai'])) {
                $pessoa->update($request->only([
                    'pes_nome', 'pes_sexo', 'pes_data_nascimento', 
                    'pes_mae', 'pes_pai'
                ]));
            }

            if ($request->hasAny(['st_data_admissao', 'st_data_demissao'])) {
                $pessoa->update($request->only([
                    'st_data_admissao', 'st_data_demissao',
                ]));
            }

            if ($request->hasAny(['end_tipo_logradouro', 'end_logradouro', 'end_numero', 'end_bairro', 'cid_nome', 'cid_uf'])) {
                if ($request->has('cid_nome') || $request->has('cid_uf')) {
                    $cidade = Cidade::firstOrCreate(
                        [
                            'cid_nome' => $validated['cid_nome'] ?? $endereco->cidade->cid_nome,
                            'cid_uf' => $validated['cid_uf'] ?? $endereco->cidade->cid_uf
                        ]
                    );
                } else {
                    $cidade = $endereco->cidade;
                }

                $endereco->update([
                    'end_tipo_logradouro' => $validated['end_tipo_logradouro'] ?? $endereco->end_tipo_logradouro,
                    'end_logradouro' => $validated['end_logradouro'] ?? $endereco->end_logradouro,
                    'end_numero' => $validated['end_numero'] ?? $endereco->end_numero,
                    'end_bairro' => $validated['end_bairro'] ?? $endereco->end_bairro,
                    'cid_id' => $cidade->cid_id
                ]);
            }

            if ($lotacao && $request->hasAny(['unid_id', 'lot_portaria', 'lot_data_lotacao', 'lot_data_remocao'])) {
                $lotacao->update([
                    'unid_id' => $validated['unid_id'] ?? $lotacao->unid_id,
                    'lot_portaria' => $validated['lot_portaria'] ?? $lotacao->lot_portaria,
                    'lot_data_lotacao' => $validated['lot_data_lotacao'] ?? $lotacao->lot_data_lotacao,
                    'lot_data_remocao' => $validated['lot_data_remocao'] ?? $lotacao->lot_data_remocao
                ]);
            }

            DB::commit();

            return $this->sendResponse(
                $servidor->fresh(['pessoa', 'pessoa.enderecos.cidade', 'pessoa.lotacao']), 
                'Servidor temporário atualizado com sucesso.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('Erro ao atualizar servidor temporário', $e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $servidor = ServidorTemporario::findOrFail($id);
            $pessoa = $servidor->pessoa;

            foreach ($pessoa->fotos as $foto) {
                Storage::delete($foto->fp_hash);
            }

            $pessoa->lotacao()->delete();
            $servidor->delete();
            $pessoa->enderecos()->detach();
            $pessoa->delete();

            DB::commit();

            return $this->sendResponse(null, 'Servidor removido com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('Erro ao remover servidor', $e);
        }
    }
}