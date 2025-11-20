<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        // Лист підтвердження e-mail
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Вітаємо у школі іноземних мов «Корпорація мов»')
                ->greeting('Вітаємо вас у школі іноземних мов «Корпорація мов»!')
                ->line('Щоб продовжити реєстрацію та користуватися особистим кабінетом, підтвердіть, будь ласка, вашу електронну адресу.')
                ->action('Підтвердити e-mail', $url)
                ->line('Якщо ви не реєструвалися на нашому сайті, просто проігноруйте цей лист.');
        });

        // Лист для "Забув пароль"
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false));
            return (new MailMessage)
                ->subject('Скидання пароля — «Корпорація мов»')
                ->greeting('Скидання пароля')
                ->line('Ми отримали запит на скидання вашого пароля.')
                ->action('Створити новий пароль', $url)
                ->line('Це посилання дійсне обмежений час. Якщо ви не надсилали запит — просто проігноруйте цей лист.');
        });
    }
}
