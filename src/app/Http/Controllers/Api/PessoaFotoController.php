<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\FotoPessoa;
use App\Models\Pessoa;
use Carbon\Carbon;

class PessoaFotoController extends BaseController
{
    public function uploadFoto(Request $request, $pessoa_id)
    {   
        try {
            $rules = [
                'fotos' => 'required|array|max:5',
                'fotos.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120' 
            ];
            
            $validated = $this->validateRequest($request, $rules);

            if ($validated instanceof \Illuminate\Http\JsonResponse) {
                return $validated; 
            }

            $pessoa = Pessoa::find($pessoa_id);
            if (!$pessoa) {
                return $this->sendError('Pessoa não encontrada', 404);
            }
            
            $uploadedPhotos = [];
            
            foreach ($request->file('fotos') as $foto) {
                $hash = md5(time() . $foto->getClientOriginalName());
                $fileName = "{$pessoa_id}/{$hash}";
                $bucket = env('AWS_BUCKET', 'pessoa-fotos');

                Storage::put($fileName, file_get_contents($foto));

                $fotoPessoa = FotoPessoa::create([
                    'pes_id' => $pessoa->pes_id,
                    'fp_bucket' => $bucket,
                    'fp_hash' => $fileName
                ]);

                $uploadedPhotos[] = $fotoPessoa;
            }

            return response()->json(['message' => 'Fotos enviadas com sucesso', 'fotos' => $uploadedPhotos], 201);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro', $e);
        } 
    }

    public function getFotoTemporaria($pessoa_id, Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15); 
            
            $fotos = FotoPessoa::where('pes_id', $pessoa_id)
                            ->paginate($perPage);
            
            $fotos->getCollection()->transform(function ($foto) {
                $foto->url_temporaria = Storage::temporaryUrl(
                    $foto->fp_hash,
                    now()->addMinutes(5)
                );
                return $foto;
            });
            
            $response = [
                'message' => 'Fotos temporárias da pessoa',
                'data' => $fotos->items(),
                'meta' => [
                    'current_page' => $fotos->currentPage(),
                    'per_page' => $fotos->perPage(),
                    'total' => $fotos->total(),
                    'last_page' => $fotos->lastPage(),
                ],
                'links' => [
                    'first' => $fotos->url(1),
                    'last' => $fotos->url($fotos->lastPage()),
                    'prev' => $fotos->previousPageUrl(),
                    'next' => $fotos->nextPageUrl(),
                ],
            ];
            
            return response()->json($response, 200);
            
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao listar fotos', $e);
        }
    }

    public function updateFoto(Request $request, $pessoa_id, $foto_id)
    {
        try {
            $rules = [
                'fotos' => 'required|array|max:5',
                'fotos.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120' 
            ];
            
            $validated = $this->validateRequest($request, $rules);

            if ($validated instanceof \Illuminate\Http\JsonResponse) {
                return $validated; 
            }

            $pessoa = Pessoa::find($pessoa_id);
            if (!$pessoa) {
                return $this->sendError('Pessoa não encontrada', 404);
            }

            $foto = FotoPessoa::where('pes_id', $pessoa_id)
                            ->where('fp_id', $foto_id)
                            ->first();

            if (!$foto) {
                return $this->sendError('Foto não encontrada para esta pessoa', 404);
            }

            $updateData = [];
            
            if ($request->hasFile('fotos')) {
                Storage::delete($foto->fp_hash);
               
                $hash = md5(time() . $request->file('fotos')[0]->getClientOriginalName());
                $fileName = "{$pessoa_id}/{$hash}";
                Storage::put($fileName, file_get_contents($request->file('fotos')[0]));

                $updateData['fp_hash'] = $fileName;
            }

            if (!empty($updateData)) {
                $foto->update($updateData);
            }

            return response()->json([
                'message' => 'Foto atualizada com sucesso',
                'foto' => $foto->fresh()
            ], 200);

        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao atualizar foto', $e);
        }
    }
}
