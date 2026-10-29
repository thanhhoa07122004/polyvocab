<?php

namespace App\Providers;

use App\Models\Deck;
use App\Models\Language;
use App\Models\User;
use App\Models\VocabularyEntry;
use App\Policies\DeckPolicy;
use App\Policies\LanguagePolicy;
use App\Policies\VocabularyEntryPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Language::class, LanguagePolicy::class);
        Gate::policy(Deck::class, DeckPolicy::class);
        Gate::policy(VocabularyEntry::class, VocabularyEntryPolicy::class);

        Gate::define('access-admin', function (User $user) {
            return $user->isAdmin();
        });
    }
}
