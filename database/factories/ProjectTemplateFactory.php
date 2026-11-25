<?php

namespace Database\Factories;

use App\Models\ProjectTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTemplate>
 */
class ProjectTemplateFactory extends Factory
{
    protected $model = ProjectTemplate::class;

    public function definition(): array
    {
        return [
            'name' => 'Standardprojekt',
            'type' => 'remodel',
            'duration_days' => 90,
            'phases' => [
                ['name' => 'Planung', 'start_offset_days' => 0, 'end_offset_days' => 30],
                ['name' => 'Ausführung', 'start_offset_days' => 31, 'end_offset_days' => 80],
            ],
            'milestones' => [
                ['name' => 'Freigabe Planung', 'phase' => 'Planung', 'due_offset_days' => 30],
                ['name' => 'Abnahme', 'phase' => 'Ausführung', 'due_offset_days' => 85],
            ],
            'tasks' => [
                ['title' => 'Bestandsaufnahme', 'phase' => 'Planung', 'priority' => 'high', 'trade' => 'Planung', 'due_offset_days' => 7, 'checklist' => ['Aufmaß', 'Fotos']],
                ['title' => 'Elektro ausschreiben', 'phase' => 'Planung', 'priority' => 'normal', 'trade' => 'Elektro', 'due_offset_days' => 21],
                ['title' => 'Bodenbelag verlegen', 'phase' => 'Ausführung', 'priority' => 'normal', 'trade' => 'Bodenbelag', 'due_offset_days' => 60],
            ],
            'budget_positions' => [
                ['name' => 'Elektro', 'cost_group' => '440', 'budget' => 40000],
                ['name' => 'Bodenbelag', 'cost_group' => '350', 'budget' => 20000],
            ],
        ];
    }
}
