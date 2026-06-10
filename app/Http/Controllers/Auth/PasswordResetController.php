<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    private const CODE_TTL_MINUTES = 15;

    public function sendCode(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            Mail::to($request->email)->queue(new ResetCodeMail($code));
        }

        return response()->json(['success' => true]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        if (! $this->codeIsValid($request->email, $request->code)) {
            return response()->json(['message' => 'Código inválido ou expirado.'], 422);
        }

        return response()->json(['success' => true]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! $this->codeIsValid($request->email, $request->code)) {
            return response()->json(['message' => 'Código inválido ou expirado.'], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['success' => true]);
    }

    private function codeIsValid(string $email, string $code): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            return false;
        }

        $createdAt = Carbon::parse($record->created_at);
        $expired = $createdAt->addMinutes(self::CODE_TTL_MINUTES)->isPast();

        if ($expired) {
            return false;
        }

        return Hash::check($code, $record->token);
    }
}
