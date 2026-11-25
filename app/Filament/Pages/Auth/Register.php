<?php

namespace App\Filament\Pages\Auth;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Register extends \Filament\Auth\Pages\Register
{
    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        if (Role::query()->where('name', 'User')->exists()) {
            $user->assignRole('User');
        }

        return $user;
    }
}
