<?php

namespace App\Providers;

use App\Listeners\PublishTemporarySite;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, PublishTemporarySite::class);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Confirm your 321Sites email address')
                ->greeting('Welcome to 321Sites')
                ->line('Confirm your email address to publish your site and start receiving customer enquiries.')
                ->action('Confirm email address', $url)
                ->line('If you did not create an account, you can safely ignore this email.');
        });
    }
}
