<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\LoginAction;
use App\Actions\LogoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $user = $action->execute($request, $request->string('email'), $request->string('password'));

        return ApiResponse::data(new UserResource($user));
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request);

        return ApiResponse::message('Logged out.');
    }

    public function user(Request $request): JsonResponse
    {
        return ApiResponse::data(new UserResource($request->user()));
    }
}
