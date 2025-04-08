<?php

namespace App\Http\Controllers\Api;

use App\Models\ServidorEfetivo;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\Cidade;
use App\Models\Endereco;
use App\Models\FotoPessoa;
use App\Models\Lotacao;
use App\Models\Pessoa;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ServidorEfetivoController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $servidores = ServidorEfetivo::with([
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

        return $this->sendResponse($servidores, 'Servidores listados com sucesso.');
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
                
                'se_matricula' => 'required|string|max:20|unique:servidor_efetivo,se_matricula',
                
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

            $servidor = ServidorEfetivo::create([
                'pes_id' => $pessoa->pes_id,
                'se_matricula' => $validated['se_matricula']
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
                'Servidor efetivo cadastrado com sucesso.', 
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('Erro ao cadastrar servidor efetivo', $e);
        }
    }

    public function show($id)
    {
        try {
            $servidor = ServidorEfetivo::with([
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
                
                'se_matricula' => 'sometimes|string|max:20|unique:servidor_efetivo,se_matricula,'.$id,
                
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

            $servidor = ServidorEfetivo::findOrFail($id);
            $pessoa = $servidor->pessoa;
            $endereco = $pessoa->enderecos->first();
            $lotacao = $pessoa->lotacao;
            
            if ($request->hasAny(['pes_nome', 'pes_sexo', 'pes_data_nascimento', 'pes_mae', 'pes_pai'])) {
                $pessoa->update($request->only([
                    'pes_nome', 'pes_sexo', 'pes_data_nascimento', 
                    'pes_mae', 'pes_pai'
                ]));
            }

            if ($request->has('se_matricula')) {
                $servidor->update(['se_matricula' => $validated['se_matricula']]);
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
                'Servidor efetivo atualizado com sucesso.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendServerError('Erro ao atualizar servidor efetivo', $e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $servidor = ServidorEfetivo::findOrFail($id);
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

    public function porNome(Request $request)
    {
        $rules = [
            'nome' => 'required|string',
            'per_page' => 'sometimes|integer|min:1|max:100' 
        ];
        
        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $termo = $request->input('nome');
            $perPage = $request->input('per_page', 15); 
        
            $pessoas = Pessoa::with(['servidorEfetivo', 'lotacao.unidade.enderecos.cidade'])
                ->whereHas('servidorEfetivo')
                ->where(function($query) use ($termo) {
                    $query->whereRaw('LOWER(pes_nome) LIKE ?', [strtolower("%{$termo}%")])
                        ->orWhereRaw('UPPER(pes_nome) LIKE ?', [strtoupper("%{$termo}%")]);
                })
                ->paginate($perPage);

            if ($pessoas->isEmpty()) {
                return response()->json([
                    'message' => 'Nenhum servidor encontrado com o nome fornecido'
                ], 404);
            }

            $resultados = $pessoas->through(function ($pessoa) {
                $enderecoUnidade = $pessoa->lotacao->unidade->enderecos->first();

                return [
                    'nome' => $pessoa->pes_nome,
                    'unidade' => $pessoa->lotacao->unidade->unid_nome,
                    'endereco_unidade' => $enderecoUnidade ? [
                        'end_tipo_logradouro' => $enderecoUnidade->end_tipo_logradouro,
                        'logradouro' => $enderecoUnidade->end_logradouro,
                        'numero' => $enderecoUnidade->end_numero,
                        'bairro' => $enderecoUnidade->end_bairro,
                        'cidade' => $enderecoUnidade->cidade ? [
                            'nome' => $enderecoUnidade->cidade->cid_nome,
                            'UF' => $enderecoUnidade->cidade->cid_uf
                        ] : null
                    ] : null
                ];
            });
        
            return response()->json([
                'data' => $resultados->items(),
                'meta' => [
                    'current_page' => $resultados->currentPage(),
                    'per_page' => $resultados->perPage(),
                    'total' => $resultados->total(),
                    'last_page' => $resultados->lastPage(),
                    'from' => $resultados->firstItem(),
                    'to' => $resultados->lastItem()
                ],
                'links' => [
                    'first' => $resultados->url(1),
                    'last' => $resultados->url($resultados->lastPage()),
                    'prev' => $resultados->previousPageUrl(),
                    'next' => $resultados->nextPageUrl()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao buscar servidores', $e);
        }
    }

    public function porUnidade($unid_id, Request $request)
    {   
        try {
            $perPage = $request->input('per_page', 15);
            
            $lotacoes = Lotacao::with([
                    'pessoa.servidorEfetivo', 
                    'unidade', 
                    'pessoa.fotos'
                ])
                ->where('unid_id', $unid_id)
                ->whereHas('pessoa.servidorEfetivo') 
                ->paginate($perPage);

            $servidores = $lotacoes->through(function ($lotacao) {
                $pessoa = $lotacao->pessoa;

                return [
                    'nome' => $pessoa->pes_nome,
                    'idade' => Carbon::parse($pessoa->pes_data_nascimento)->age,
                    'unidade' => $lotacao->unidade->unid_nome,
                    'fotografia' => $pessoa->fotos->first() ? 
                        Storage::temporaryUrl(
                            $pessoa->fotos->first()->fp_hash,
                            now()->addMinutes(5)
                        ) : null
                ];
            });
                
            return response()->json([
                'message' => 'Sucesso',
                'data' => $servidores->items(),
                'meta' => [
                    'current_page' => $lotacoes->currentPage(),
                    'per_page' => $lotacoes->perPage(),
                    'total' => $lotacoes->total(),
                    'last_page' => $lotacoes->lastPage(),
                    'from' => $lotacoes->firstItem(),
                    'to' => $lotacoes->lastItem()
                ],
                'links' => [
                    'first' => $lotacoes->url(1),
                    'last' => $lotacoes->url($lotacoes->lastPage()),
                    'prev' => $lotacoes->previousPageUrl(),
                    'next' => $lotacoes->nextPageUrl()
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao buscar servidores', $e);
        }
    }
}