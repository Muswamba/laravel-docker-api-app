<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Auth\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use Illuminate\Auth\Events\Registered;
use App\Events\User\UserRegister;
use App\Events\User\UserLastActive;

class AuthApiController extends Controller
{
    /**
     * Register user (API)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'string', 'max:255', 'unique:' . User::class],
            'username' => 'nullable|string|unique:users,username',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        if (empty($validated['username'])) {
            $validated['username'] = $this->generateUsername($validated['name']);
        }

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));
        UserRegister::dispatch($user);
        UserLastActive::dispatch($user);

        return $this->issueTokens($user, 201);
    }

    /**
     * Login user (API)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user = Auth::user();
        UserLastActive::dispatch($user);

        return $this->issueTokens($user);
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $refresh = RefreshToken::where('token', $request->refresh_token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$refresh) {
            return response()->json([
                'message' => 'Invalid refresh token'
            ], 401);
        }

        return $this->issueTokens($refresh->user);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Delete access token
        $user->currentAccessToken()?->delete();

        // Delete refresh tokens
        $user->refreshTokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return new UserResource($request->user());
    }

    // ---------------------------------------
    // Helper: Username generator
    // ---------------------------------------
    private function generateUsername(string $name): string
    {
        $parts = array_values(array_filter(explode(' ', Str::slug($name, ' '))));
        if (empty($parts)) $parts = ['user'];

        $base = strtolower($parts[0] . end($parts));
        $base = Str::limit($base, 12, '');

        if (strlen($base) < 3) {
            $base = strtolower(implode('', array_map(fn($p) => $p[0], $parts)));
        }

        if (empty($base)) $base = 'user';

        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $suffix = (string)$counter;
            $username = Str::limit($base, 20 - strlen($suffix), '') . $suffix;
            $counter++;
            if ($counter > 999) {
                $username = Str::limit($base, 15, '') . Str::random(5);
                break;
            }
        }

        return $username;
    }

    // ---------------------------------------
    // Helper: Issue tokens (access + refresh)
    // ---------------------------------------
    private function issueTokens(User $user, int $status = 200)
    {
        // Delete existing access tokens
        $user->tokens()->delete();

        // Create access token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create refresh token
        $refreshToken = RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'access_token'  => $token,
            'refresh_token' => $refreshToken->token,
            'user'          => new UserResource($user),
            'expires_in'    => 60 * 30, // 30 minutes
        ], $status);
    }
}
