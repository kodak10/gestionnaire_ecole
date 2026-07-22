<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogin
{
    public function handle(Login $event)
    {
        activity()
            ->causedBy($event->user)
            ->withProperties([
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent()
            ])
            ->log('Connexion réussie');
    }
}