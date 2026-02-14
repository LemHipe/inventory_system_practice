<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Application\Auth\Commands\LoginCommand;
use App\Application\Auth\Commands\RegisterCommand;
use App\Application\Auth\Handlers\LoginHandler;
use App\Application\Auth\Handlers\RegisterHandler;
use App\Domain\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(Request $request): JsonResponse
    {
        // FLOW: request -> validate -> domain/auth service -> create Sanctum token -> return JSON
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $handler = new RegisterHandler($this->authService);
        $result = $handler->handle(new RegisterCommand(
            $validated['name'],
            $validated['email'],
            $validated['password']
        ));

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        $userModel = \App\Models\User::find($result['user']['id']);

        // Sanctum personal access token (PAT). Client should send it as:
        // Authorization: Bearer <token>
        $token = $userModel?->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $result['user'],
            'token' => $token,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        // FLOW: request -> validate -> auth service verifies credentials -> create token -> return JSON
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $handler = new LoginHandler($this->authService);
        $result = $handler->handle(new LoginCommand(
            $validated['email'],
            $validated['password']
        ));

        if (!$result['success']) {
            return response()->json($result, 401);
        }

        $userModel = \App\Models\User::find($result['user']['id']);

        // Create a new token for this login. (Optionally you could revoke old tokens here.)
        $token = $userModel?->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $result['user'],
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        // If the request passes auth:sanctum middleware, $request->user() is the authenticated user.
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Deletes only the current token (the one used for this request).
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
