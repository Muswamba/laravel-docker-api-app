<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\PasswordResetCodeMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
// Removed: use App\Mail\PasswordResetSuccessMail; // Unused
use App\Mail\PasswordResetSuccessfullyMail;
use App\Models\Auth\PasswordResetTokenCode;
use Illuminate\Http\JsonResponse;

class APIAuthResetPasswordController extends Controller
{
    /**
     * Sends a one-time password (OTP) code to the user's email for password reset.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendTokenCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = $request->input('email');
        // Generate a 6-digit code
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        try {
            DB::transaction(function () use ($email, $code, $expiresAt) {
                // Delete any existing codes for this email and create a new one
                PasswordResetTokenCode::where('email', $email)->delete();
                PasswordResetTokenCode::create([
                    'email' => $email,
                    'code' => $code,
                    'expires_at' => $expiresAt,
                ]);
            });

            // Send email
            Mail::to($email)->send(new PasswordResetCodeMail($code));

            return response()->json([
                'message_code' => 'Token code sent successfully',
                'debug_code' => $code // Note: Only for development/debugging. Remove in production.
            ], 200);

        } catch (\Exception $e) {
            // Log the error for better debugging in a real application
            // \Log::error("Password reset token failure for {$email}: " . $e->getMessage());
            return response()->json([
                'message_code' => 'Failed to send token code',
                // 'error' => $e->getMessage() // Consider removing or simplifying error for production
            ], 500);
        }
    }

    /**
     * Validates if the provided email and token code combination is correct and not expired.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateTokenCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code'  => 'required|string|size:6',
        ]);

        $tokenEntry = $this->findValidTokenEntry(
            $request->input('email'),
            $request->input('code')
        );

        if ($tokenEntry instanceof JsonResponse) {
            return $tokenEntry; // Return error response from validation
        }

        return response()->json([
            'message_code' => 'Token code is valid'
        ], 200);
    }

    /**
     * Resets the user's password after validating the token code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code'  => 'required|string|size:6',
            'password' => 'required|string|min:8',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        $tokenEntry = $this->findValidTokenEntry($email, $request->input('code'));

        if ($tokenEntry instanceof JsonResponse) {
            return $tokenEntry; // Return error response from validation
        }

        // Reset password and delete token within a transaction
        try {
            DB::transaction(function () use ($email, $password, $tokenEntry) {
                // Find the user to update the password
                $user = User::where('email', $email)->firstOrFail();
                $user->password = Hash::make($password);
                $user->save();

                // Delete token entry to prevent reuse
                $tokenEntry->delete();

                // Send email success notification
                Mail::to($email)->send(new PasswordResetSuccessfullyMail());
            });

            return response()->json([
                'message_code' => 'Password reset successful'
            ], 200);

        } catch (\Exception $e) {
            // Log the error
            // \Log::error("Password reset failure for {$email}: " . $e->getMessage());
            return response()->json([
                'message_code' => 'Password reset failed',
            ], 500);
        }
    }

    /**
     * Private helper method to find and validate the token entry.
     * Returns the PasswordResetTokenCode model or a JsonResponse error.
     *
     * @param string $email
     * @param string $code
     * @return PasswordResetTokenCode|JsonResponse
     */
    private function findValidTokenEntry(string $email, string $code): PasswordResetTokenCode|JsonResponse
    {
        $tokenEntry = PasswordResetTokenCode::where('email', $email)
            ->where('code', $code)
            ->first();

        if (!$tokenEntry) {
            return response()->json([
                'message_code' => 'Invalid token code'
            ], 400);
        }

        // Accessing expires_at as a Carbon instance is handled by Eloquent automatically
        if ($tokenEntry->expires_at->isPast()) {
            return response()->json([
                'message_code' => 'Token code has expired'
            ], 400);
        }

        return $tokenEntry;
    }
}