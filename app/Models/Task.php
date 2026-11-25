<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Flow\Concerns\HasWorkflows;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, array{text: string, done: bool}>|null $checklist
 */
class Task extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use HasUuids;
    use HasWorkflows;

    protected $fillable = [
        'project_id', 'phase_id', 'milestone_id', 'assignee_id', 'blocked_by_task_id', 'title', 'description',
        'status', 'priority', 'trade', 'start_date', 'due_date', 'completed_at', 'position', 'checklist', 'created_by',
    ];

    protected $attributes = [
        'status' => 'todo',
        'priority' => 'normal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'checklist' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Task $task): void {
            $task->created_by ??= auth()->id();
            $task->organization_id ??= $task->project?->organization_id;
        });

        static::saving(function (Task $task): void {
            if ($task->status === TaskStatus::Done && $task->completed_at === null) {
                $task->completed_at = now();
            } elseif ($task->status !== TaskStatus::Done) {
                $task->completed_at = null;
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'blocked_by_task_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', TaskStatus::Done->value);
    }

    public function scopeAssignedTo(Builder $query, int|string $userId): Builder
    {
        return $query->where('assignee_id', $userId);
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen() && $this->due_date !== null && $this->due_date->lt(now()->startOfDay());
    }

    public function isBlocked(): bool
    {
        return $this->blockedBy !== null && $this->blockedBy->status !== TaskStatus::Done;
    }

    public function checklistProgress(): ?string
    {
        $items = $this->checklist ?? [];

        if ($items === []) {
            return null;
        }

        return count(array_filter($items, fn (array $item): bool => (bool) ($item['done'] ?? false))).'/'.count($items);
    }
}
