<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupported($provider);

        if (! $this->isConfigured($provider)) {
            return $this->failure('Цей спосіб входу ще не налаштований.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupported($provider);

        if (! $this->isConfigured($provider)) {
            return $this->failure('Цей спосіб входу ще не налаштований.');
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            Log::warning('Social login callback failed.', [
                'provider' => $provider,
                'exception' => $exception::class,
            ]);

            return $this->failure('Не вдалося увійти. Спробуйте ще раз.');
        }

        $providerUserId = trim((string) $providerUser->getId());
        $email = Str::lower(trim((string) $providerUser->getEmail()));

        if ($providerUserId === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failure('Провайдер не передав підтверджену електронну адресу.');
        }

        $account = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($account) {
            return $this->login($request, $account->user);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user && ! $user->isStudent() && ! $this->canLinkStaffAccount($provider, $user)) {
            return $this->staffFailure();
        }

        $user = DB::transaction(function () use ($user, $provider, $providerUser, $providerUserId, $email) {
            if (! $user) {
                $user = User::create([
                    'name' => trim((string) $providerUser->getName()) ?: Str::before($email, '@'),
                    'email' => $email,
                    'password' => Str::random(64),
                    'role' => 'student',
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            } elseif (! $user->hasVerifiedEmail()) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $existingProvider = $user->socialAccounts()
                ->where('provider', $provider)
                ->first();

            if ($existingProvider && $existingProvider->provider_user_id !== $providerUserId) {
                return null;
            }

            $user->socialAccounts()->firstOrCreate(
                ['provider' => $provider],
                [
                    'provider_user_id' => $providerUserId,
                    'avatar' => $providerUser->getAvatar(),
                ]
            );

            return $user;
        });

        if (! $user) {
            return $this->failure('До цього акаунта вже прив’язаний інший профіль провайдера.');
        }

        return $this->login($request, $user);
    }

    private function login(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();

        $fallback = match (true) {
            $user->isAdmin(), $user->isTeacher() => route('admin.index'),
            $user->isStudent() => route('student.dashboard'),
            default => route('index'),
        };

        return redirect()->intended($fallback);
    }

    private function canLinkStaffAccount(string $provider, User $user): bool
    {
        return $provider === 'google'
            && ($user->isAdmin() || $user->isTeacher());
    }

    private function isConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"))
            && filled(config("services.{$provider}.redirect"));
    }

    private function ensureSupported(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
    }

    private function staffFailure(): RedirectResponse
    {
        return $this->failure('Для входу адміністратора або викладача використайте Google або email і пароль.');
    }

    private function failure(string $message): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->with('social_auth_error', $message);
    }
}
