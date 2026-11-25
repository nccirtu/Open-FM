<?php

namespace App\Flow\Concerns;

use App\Flow\Triggers\RecordCreated;
use App\Flow\Triggers\RecordDeleted;
use App\Flow\Triggers\RecordUpdated;
use Illuminate\Database\Eloquent\Model;
use Packstub\Flow\Facades\Flow;

trait HasWorkflows
{
    public static function bootHasWorkflows(): void
    {
        static::created(function (Model $model): void {
            Flow::dispatch(RecordCreated::class, ['model' => $model]);
        });

        static::updated(function (Model $model): void {
            Flow::dispatch(RecordUpdated::class, [
                'model' => $model,
                'original' => $model->getOriginal(),
                'changes' => $model->getChanges(),
            ]);
        });

        static::deleted(function (Model $model): void {
            Flow::dispatch(RecordDeleted::class, ['model' => $model]);
        });
    }
}
