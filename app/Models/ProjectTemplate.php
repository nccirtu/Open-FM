<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, array{name: string, start_offset_days?: int|null, end_offset_days?: int|null}>|null $phases
 * @property array<int, array{name: string, phase?: string|null, due_offset_days?: int|null}>|null $milestones
 * @property array<int, array{title: string, phase?: string|null, priority?: string, trade?: string|null, due_offset_days?: int|null, description?: string|null, checklist?: array<int, array{text: string}>}>|null $tasks
 * @property array<int, array{name: string, cost_group?: string|null, cost_center_id?: int|null, budget?: float|null}>|null $budget_positions
 */
class ProjectTemplate extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use HasUuids;

    protected $fillable = ['name', 'description', 'type', 'duration_days', 'phases', 'milestones', 'tasks', 'budget_positions', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'phases' => 'array',
            'milestones' => 'array',
            'tasks' => 'array',
            'budget_positions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (ProjectTemplate $template) => $template->created_by ??= auth()->id());
    }

    public function itemCount(): int
    {
        return count($this->phases ?? []) + count($this->milestones ?? []) + count($this->tasks ?? []) + count($this->budget_positions ?? []);
    }
}
