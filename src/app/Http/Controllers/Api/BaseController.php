<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    protected function sendResponse($data, $message = '', $code = 200)
    {
        $response = [
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ];

        return response()->json($response, $code);
    }

    protected function sendError($error, $code = 404, $data = [])
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    protected function validateRequest(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = Validator::make(
            $request->all(), 
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            return $this->sendError(
                'Erro de validação',
                422,
                $validator->errors()->toArray()
            );
        }

        return $validator->validated();
    }

    protected function sendSuccess($message = '', $code = 200)
    {
        return $this->sendResponse([], $message, $code);
    }

    protected function sendServerError($error = 'Erro interno do servidor', $exception = null)
    {
        $data = [];
        
        if ($exception !== null && config('app.debug')) {
            $data['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }

        return $this->sendError($error, 500, $data);
    }
}