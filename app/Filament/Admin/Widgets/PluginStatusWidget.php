<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\ManagePlugins;
use App\Models\Plugin;
use App\Support\Plugins\Installer\PluginInstaller;
use App\Support\Plugins\PluginDefinition;
use App\Support\Plugins\Plugins;
use Composer\InstalledVersions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class PluginStatusWidget extends TableWidget
{
    public static int $gridW = 4;

    public static int $gridH = 13;

    public static function canView(): bool
    {
        return Plugins::canManage();
    }

    public function getHeading(): ?string
    {
        return __('admin_dashboard.plugins.heading');
    }

    public function table(Table $table): Table
    {
        $missing = app(PluginInstaller::class)->missingPlugins();

        return $table
            ->heading(__('admin_dashboard.plugins.heading'))
            ->description($missing === [] ? null : __('admin_dashboard.plugins.missing', ['plugins' => implode(', ', $missing)]))
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('name')
                    ->label(__('plugins.admin.columns.name'))
                    ->description(fn (array $record): ?string => $record['organizations'] === [] ? null : implode(', ', $record['organizations']))
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('state')
                    ->label(__('plugins.admin.columns.state'))
                    ->badge()
                    ->formatStateUsing(fn (array $record): string => $record['state_label'])
                    ->color(fn (string $state): string => match ($state) {
                        'enabled' => 'success',
                        'disabled' => 'warning',
                        'failed' => 'danger',
                        'installing', 'updating', 'removing' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('version')
                    ->label(__('plugins.admin.columns.version'))
                    ->description(fn (array $record): ?string => $record['version_drift'] ? __('admin_dashboard.plugins.registry_version', ['version' => $record['registry_version']]) : null)
                    ->color(fn (array $record): ?string => $record['version_drift'] ? 'warning' : null)
                    ->placeholder('—'),
            ])
            ->recordUrl(fn (): string => ManagePlugins::getUrl())
            ->paginated(false);
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    protected function rows(): Collection
    {
        $catalog = Plugins::catalog()->mapWithKeys(fn (PluginDefinition $definition): array => [
            $definition->slug => $this->row($definition->slug, $definition->name(), $definition->package),
        ]);

        $thirdParty = Plugins::records()
            ->reject(fn (Plugin $plugin): bool => $catalog->has($plugin->slug))
            ->mapWithKeys(fn (Plugin $plugin): array => [
                $plugin->slug => $this->row($plugin->slug, $plugin->name, $plugin->package),
            ]);

        return $catalog->merge($thirdParty);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(string $slug, string $name, string $package): array
    {
        $record = Plugins::record($slug);
        $installed = Plugins::installed($slug);
        $status = $record?->status;

        $state = match (true) {
            $status?->isBusy() === true => $status->value,
            $status?->value === 'failed' => 'failed',
            ! $installed => 'not_installed',
            ($record?->is_enabled ?? true) => 'enabled',
            default => 'disabled',
        };

        $installedVersion = $installed ? InstalledVersions::getPrettyVersion($package) : null;

        return [
            'slug' => $slug,
            'name' => $name,
            'state' => $state,
            'state_label' => in_array($state, ['installing', 'updating', 'removing', 'failed'], true)
                ? __("plugins.status.{$state}")
                : __("plugins.admin.states.{$state}"),
            'installed' => $installed,
            'version' => $installedVersion,
            'registry_version' => $record?->version,
            'version_drift' => $installedVersion !== null && $record?->version !== null && $record->version !== $installedVersion,
            'organizations' => $record?->organizations->pluck('name')->all() ?? [],
        ];
    }
}
