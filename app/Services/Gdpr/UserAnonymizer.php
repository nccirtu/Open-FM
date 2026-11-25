<?php

namespace App\Services\Gdpr;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserAnonymizer
{
    public function anonymize(User $user): User
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'name' => 'Gelöschter Nutzer #'.$user->getKey(),
                'email' => 'geloescht-'.$user->getKey().'-'.Str::random(8).'@invalid.example',
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => null,
                'remember_token' => null,
            ])->save();

            $user->organizations()->detach();
            $user->properties()->detach();
            $user->tokens()->delete();

            DB::table(config('permission.table_names.model_has_roles'))
                ->where('model_type', $user->getMorphClass())
                ->where('model_id', $user->getKey())
                ->delete();
        });

        return $user->fresh();
    }
}
