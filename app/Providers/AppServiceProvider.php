<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Zorg dat de mappen bestaan die DOMPDF en PDF-cache nodig hebben.
        // storage/fonts staat in .gitignore dus bestaat niet automatisch op een nieuwe server.
        foreach ([storage_path('fonts'), storage_path('app/pdf-cache')] as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0775, true);
            }
        }

        // Maak het admin-account automatisch aan als ADMIN_EMAIL en ADMIN_PASSWORD
        // in de .env staan en het account nog niet bestaat.
        $this->ensureAdminExists();
    }

    private function ensureAdminExists(): void
    {
        $email    = config('app.admin_email');
        $password = env('ADMIN_PASSWORD');

        if (!$email || !$password) {
            return;
        }

        try {
            if (!User::where('email', $email)->exists()) {
                User::create([
                    'name'     => env('ADMIN_NAME', 'Admin'),
                    'email'    => $email,
                    'password' => Hash::make($password),
                ]);
            }
        } catch (\Throwable) {
            // Database nog niet beschikbaar (bijv. tijdens migraties) — stil negeren.
        }
    }
}
