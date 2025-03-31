<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

class UserController extends BaseController
{

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $users = User::query()
            ->when($request->has('search'), function ($query) use ($request) {
                $search = $request->input('search');
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request)
    {   
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
    
            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $user
            ], 201);
        }  catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar usuário', $e);
        } 
       
    }

    public function update(Request $request, User $user)
    {   
        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['sometimes', Rules\Password::defaults()],
        ];

        $validated = $this->validateRequest($request, $rules);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated; 
        }

        try {
            $updateData = [
                'name' => $validated['name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
            ];
    
            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
    
            $user->update($updateData);
    
            return response()->json([
                'message' => 'Usuário atualizado com sucesso',
                'user' => $user->fresh()
            ]);
        } catch (\Exception $e) {
            return $this->sendServerError('Erro ao criar usuário', $e);
        } 
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuário removido com sucesso'
        ]);
    }
}