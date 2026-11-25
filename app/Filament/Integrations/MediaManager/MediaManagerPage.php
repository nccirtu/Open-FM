<?php

namespace App\Filament\Integrations\MediaManager;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Pages\MediaManager;

class MediaManagerPage extends MediaManager
{
    protected static ?string $slug = 'media-manager';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && Gate::forUser($user)->allows('viewAny', File::class);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return parent::shouldRegisterNavigation() && static::canAccess();
    }
}
