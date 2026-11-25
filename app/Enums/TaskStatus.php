<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasColor, HasLabel
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    public function getLabel(): string
    {
        return __('projects.task_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Todo => 'gray',
            self::InProgress => 'info',
            self::Review => 'warning',
            self::Done => 'success',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Done;
    }
}
