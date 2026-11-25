<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WorkflowResource\Pages\CreateWorkflow;
use App\Filament\Admin\Resources\WorkflowResource\Pages\EditWorkflow;
use App\Filament\Admin\Resources\WorkflowResource\Pages\ListWorkflows;
use App\Models\Organization;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Packstub\Flow\Filament\Resources\WorkflowResource as BaseWorkflowResource;

class WorkflowResource extends BaseWorkflowResource
{
    /**
     * @param  array<int, Component>  $components
     * @return array<int, Component>
     */
    public static function withTenantSelect(array $components): array
    {
        $position = collect($components)->search(fn (Component $component): bool => $component instanceof Section);

        array_splice($components, $position === false ? count($components) : $position, 0, [static::tenantSelect()]);

        return $components;
    }

    public static function tenantSelect(): Select
    {
        return Select::make('tenant_id')
            ->label(__('app.flow.tenant'))
            ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
            ->native(false)
            ->searchable()
            ->nullable()
            ->helperText(__('app.flow.tenant_help'))
            ->columnSpanFull();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withTenantType(array $data): array
    {
        $data['tenant_id'] = filled($data['tenant_id'] ?? null) ? $data['tenant_id'] : null;
        $data['tenant_type'] = $data['tenant_id'] !== null ? (new Organization)->getMorphClass() : null;

        return $data;
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->pushColumns([
                TextColumn::make('tenant.name')
                    ->label(__('app.flow.tenant'))
                    ->placeholder(__('app.flow.global')),
            ])
            ->pushFilters([
                SelectFilter::make('tenant_id')
                    ->label(__('app.flow.tenant'))
                    ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflows::route('/'),
            'create' => CreateWorkflow::route('/create'),
            'edit' => EditWorkflow::route('/{record}/edit'),
        ];
    }
}
