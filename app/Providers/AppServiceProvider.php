<?php

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
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
        // Añadir Reply-To global a todos los correos
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $replyTo = config('mail.reply_to');
            if ($replyTo['address']) {
                $event->message->replyTo($replyTo['address'], $replyTo['name']);
            }
        });
    }
}
