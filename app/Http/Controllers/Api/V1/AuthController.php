<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Регистрация + выдача токена Sanctum.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'plan' => 'free',
            'credits_balance' => (int) config('billing.default_credits', 500),
        ]);

        return response()->json([
            'data' => [
                'user' => UserResource::make($user)->resolve(),
                'token' => $user->createToken($request->validated('device_name') ?? 'api')->plainTextToken,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Вход по email/паролю с выдачей токена.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || !Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        return response()->json([
            'data' => [
                'user' => UserResource::make($user)->resolve(),
                'token' => $user->createToken($request->validated('device_name') ?? 'api')->plainTextToken,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => UserResource::make($request->user())->resolve()]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }
}
