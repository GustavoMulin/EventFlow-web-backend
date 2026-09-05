<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AuthController extends Controller
{
    /**
     * Register a new user and issue an API token.
     */
    public function register(Request $request, CreateNewUser $creator): JsonResponse
    {
        $user = $creator->create($request->all());

        event(new Registered($user));

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => $user,
        ], 201);
    }

    /**
     * Authenticate a user (optionally completing a two-factor challenge) and issue an API token.
     */
    public function login(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $code = $request->string('code')->trim()->value();
            $recoveryCode = $request->string('recovery_code')->trim()->value();

            if ($code === '' && $recoveryCode === '') {
                // Credentials are valid but a second factor is still required.
                return response()->json(['two_factor' => true]);
            }

            $this->assertValidTwoFactorChallenge($user, $code, $recoveryCode, $provider);
        }

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * Revoke the token that was used to authenticate the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }

    /**
     * Return the authenticated user together with its two-factor state.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'email_verified' => $user->hasVerifiedEmail(),
        ]);
    }

    /**
     * Validate the supplied 2FA code or recovery code.
     *
     * @throws ValidationException
     */
    private function assertValidTwoFactorChallenge(User $user, string $code, string $recoveryCode, TwoFactorAuthenticationProvider $provider): void
    {
        if ($recoveryCode !== '') {
            if (! \in_array($recoveryCode, $user->recoveryCodes(), true)) {
                throw ValidationException::withMessages([
                    'recovery_code' => [__('The provided two factor recovery code was invalid.')],
                ]);
            }

            $user->replaceRecoveryCode($recoveryCode);

            return;
        }

        if (! $provider->verify(decrypt($user->two_factor_secret), $code)) {
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ]);
        }
    }
}
