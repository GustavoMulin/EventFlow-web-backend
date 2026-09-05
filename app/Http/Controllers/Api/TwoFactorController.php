<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorController extends Controller
{
    /**
     * Begin enabling two-factor auth: generates the secret + recovery codes (not yet confirmed).
     */
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $enable($request->user());

        return response()->json([
            'svg' => $request->user()->twoFactorQrCodeSvg(),
            'secret_key' => decrypt($request->user()->two_factor_secret),
            'recovery_codes' => $request->user()->recoveryCodes(),
            'confirmed' => false,
        ]);
    }

    /**
     * Confirm two-factor auth with a TOTP code from the authenticator app.
     */
    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $confirm($request->user(), $request->input('code'));

        return response()->json([
            'recovery_codes' => $request->user()->recoveryCodes(),
            'confirmed' => true,
        ]);
    }

    /**
     * Disable two-factor auth for the authenticated user.
     */
    public function disable(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $disable($request->user());

        return response()->json(status: 204);
    }

    /**
     * Return the QR code SVG + otpauth URL for the pending secret.
     */
    public function qrCode(Request $request): JsonResponse
    {
        $this->assertTwoFactorSetupStarted($request);

        return response()->json([
            'svg' => $request->user()->twoFactorQrCodeSvg(),
            'url' => $request->user()->twoFactorQrCodeUrl(),
        ]);
    }

    /**
     * Return the plain-text secret key for manual entry.
     */
    public function secretKey(Request $request): JsonResponse
    {
        $this->assertTwoFactorSetupStarted($request);

        return response()->json([
            'secret_key' => decrypt($request->user()->two_factor_secret),
        ]);
    }

    /**
     * List the current recovery codes.
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $this->assertTwoFactorSetupStarted($request);

        return response()->json(['recovery_codes' => $request->user()->recoveryCodes()]);
    }

    /**
     * Generate a fresh set of recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        $this->assertTwoFactorSetupStarted($request);

        $generate($request->user());

        return response()->json(['recovery_codes' => $request->user()->recoveryCodes()]);
    }

    /**
     * @throws ValidationException
     */
    private function assertTwoFactorSetupStarted(Request $request): void
    {
        if (empty($request->user()->two_factor_secret)) {
            throw ValidationException::withMessages([
                'code' => [__('Two factor authentication has not been enabled.')],
            ]);
        }
    }
}
