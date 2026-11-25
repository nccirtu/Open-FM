<?php

namespace App\Filament\Admin\Pages;

use App\Models\Organization;
use App\Models\Plugin;
use App\Support\Plugins\Installer\InvalidPluginArchive;
use App\Support\Plugins\Installer\PluginInstaller;
use App\Support\Plugins\Marketplace\MarketplaceClient;
use App\Support\Plugins\Marketplace\MarketplaceException;
use App\Support\Plugins\PluginDefinition;
use App\Support\Plugins\PluginRegistry;
use App\Support\Plugins\Plugins;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class ManagePlugins extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'plugins';

    public static function getNavigationGroup(): ?string
    {
        return __('app.admin_groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('plugins.navigation');
    }

    public function getTitle(): string
    {
        return __('plugins.navigation');
    }

    public function getSubheading(): string
    {
        $missing = app(PluginInstaller::class)->missingPlugins();

        return collect([
            __('plugins.admin.subheading'),
            app(MarketplaceClient::class)->configured() ? null : __('plugins.admin.marketplace_missing'),
            $missing === [] ? null : __('plugins.admin.restore_needed', ['plugins' => implode(', ', $missing)]),
        ])->filter()->implode(' ');
    }

    public static function canAccess(): bool
    {
        return Plugins::canManage();
    }

    public function mount(): void
    {
        app(PluginRegistry::class)->sync();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restore')
                ->label(__('plugins.admin.actions.restore'))
                ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                ->color('warning')
                ->visible(fn (): bool => app(PluginInstaller::class)->missingPlugins() !== [])
                ->action(function (): void {
                    app(PluginInstaller::class)->dispatchRestore();
                    $this->flushCachedTableRecords();

                    Notification::make()->info()->title(__('plugins.admin.notifications.restore_queued'))->send();
                }),
            Action::make('upload')
                ->label(__('plugins.admin.actions.upload'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->schema([
                    FileUpload::make('zip')
                        ->label(__('plugins.admin.actions.upload_file'))
                        ->disk('local')
                        ->directory('plugins/uploads')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                        ->required(),
                    FileUpload::make('signature')
                        ->label(__('plugins.admin.actions.upload_signature'))
                        ->helperText(__('plugins.admin.actions.upload_signature_help'))
                        ->disk('local')
                        ->directory('plugins/uploads'),
                    Checkbox::make('trust_unsigned')
                        ->label(__('plugins.admin.actions.upload_trust'))
                        ->visible(fn (): bool => ! config('plugins.require_signature')),
                ])
                ->action(function (array $data): void {
                    $disk = Storage::disk('local');

                    try {
                        [$plugin, $version] = app(PluginInstaller::class)->stageUpload(
                            $disk->path($data['zip']),
                            filled($data['signature'] ?? null) ? $disk->get($data['signature']) : null,
                            (bool) ($data['trust_unsigned'] ?? false),
                        );
                    } catch (InvalidPluginArchive $exception) {
                        Notification::make()->danger()->title(__('plugins.admin.notifications.failed'))->body($exception->getMessage())->send();

                        return;
                    } finally {
                        $disk->delete(array_filter([$data['zip'] ?? null, $data['signature'] ?? null]));
                    }

                    app(PluginInstaller::class)->dispatch($plugin, 'install', $version);
                    $this->queued($plugin->name);
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->rows())
            ->poll(fn (): ?string => $this->rows()->contains(fn (array $row): bool => $row['busy']) ? '3s' : null)
            ->columns([
                TextColumn::make('name')
                    ->label(__('plugins.admin.columns.name'))
                    ->description(fn (array $record): string => $record['description'])
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('category')
                    ->label(__('plugins.admin.columns.category'))
                    ->badge()
                    ->color('gray'),
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
                    ->placeholder('—'),
                TextColumn::make('price')
                    ->label(__('plugins.admin.columns.price'))
                    ->placeholder(__('plugins.admin.price_unknown')),
                TextColumn::make('license')
                    ->label(__('plugins.admin.columns.license'))
                    ->placeholder('—'),
                TextColumn::make('organizations')
                    ->label(__('plugins.admin.columns.organizations'))
                    ->badge()
                    ->color('gray')
                    ->placeholder(fn (array $record): ?string => $record['installed'] ? __('plugins.admin.all_organizations') : null),
            ])
            ->recordActions([
                Action::make('install')
                    ->label(__('plugins.admin.actions.install'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->visible(fn (array $record): bool => ! $record['installed'] && ! $record['busy'] && app(MarketplaceClient::class)->configured())
                    ->requiresConfirmation()
                    ->modalDescription(__('plugins.admin.actions.install_confirm'))
                    ->action(function (array $record): void {
                        try {
                            $plugin = app(PluginInstaller::class)->stageMarketplace($record['slug']);
                        } catch (MarketplaceException $exception) {
                            Notification::make()->danger()->title(__('plugins.admin.notifications.failed'))->body($exception->getMessage())->send();

                            return;
                        }

                        app(PluginInstaller::class)->dispatch($plugin, 'install');
                        $this->queued($record['name']);
                    }),
                Action::make('enable')
                    ->label(__('plugins.admin.actions.enable'))
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('success')
                    ->visible(fn (array $record): bool => $record['state'] === 'disabled')
                    ->action(function (array $record): void {
                        app(PluginRegistry::class)->setEnabled($record['slug'], true);
                        $this->flushCachedTableRecords();

                        Notification::make()->success()->title(__('plugins.admin.notifications.enabled', ['plugin' => $record['name']]))->send();
                    }),
                Action::make('disable')
                    ->label(__('plugins.admin.actions.disable'))
                    ->icon(Heroicon::OutlinedPause)
                    ->color('warning')
                    ->visible(fn (array $record): bool => $record['state'] === 'enabled')
                    ->requiresConfirmation()
                    ->modalDescription(__('plugins.admin.actions.disable_confirm'))
                    ->action(function (array $record): void {
                        app(PluginRegistry::class)->setEnabled($record['slug'], false);
                        $this->flushCachedTableRecords();

                        Notification::make()->success()->title(__('plugins.admin.notifications.disabled', ['plugin' => $record['name']]))->send();
                    }),
                Action::make('error')
                    ->label(__('plugins.admin.actions.show_error'))
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('danger')
                    ->visible(fn (array $record): bool => $record['state'] === 'failed')
                    ->modalHeading(fn (array $record): string => $record['name'])
                    ->modalContent(fn (array $record): HtmlString => new HtmlString('<pre class="whitespace-pre-wrap text-xs">'.e((string) Plugins::record($record['slug'])?->last_error).'</pre>'))
                    ->modalSubmitAction(false),
                ActionGroup::make([
                    Action::make('update')
                        ->label(__('plugins.admin.actions.update'))
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->color('gray')
                        ->visible(fn (array $record): bool => $record['installed'] && ! $record['busy'] && Plugins::record($record['slug']) !== null)
                        ->action(function (array $record): void {
                            app(PluginInstaller::class)->dispatch(Plugins::record($record['slug']), 'update');
                            $this->queued($record['name']);
                        }),
                    Action::make('organizations')
                        ->label(__('plugins.admin.actions.organizations'))
                        ->icon(Heroicon::OutlinedBuildingOffice)
                        ->visible(fn (array $record): bool => $record['installed'] && ! $record['busy'])
                        ->fillForm(fn (array $record): array => ['organizations' => Plugins::record($record['slug'])?->organizations->modelKeys() ?? []])
                        ->schema([
                            Select::make('organizations')
                                ->label(__('plugins.admin.columns.organizations'))
                                ->helperText(__('plugins.admin.actions.organizations_help'))
                                ->multiple()
                                ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all()),
                        ])
                        ->action(function (array $record, array $data): void {
                            app(PluginRegistry::class)->setOrganizations($record['slug'], $data['organizations'] ?? []);
                            $this->flushCachedTableRecords();

                            Notification::make()->success()->title(__('plugins.admin.notifications.organizations_saved', ['plugin' => $record['name']]))->send();
                        }),
                    Action::make('remove')
                        ->label(__('plugins.admin.actions.remove'))
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->visible(fn (array $record): bool => $record['installed'] && ! $record['busy'] && Plugins::record($record['slug']) !== null)
                        ->requiresConfirmation()
                        ->modalDescription(__('plugins.admin.actions.remove_confirm'))
                        ->action(function (array $record): void {
                            app(PluginInstaller::class)->dispatch(Plugins::record($record['slug']), 'remove');
                            $this->queued($record['name']);
                        }),
                ]),
            ])
            ->paginated(false);
    }

    protected function queued(string $name): void
    {
        $this->flushCachedTableRecords();

        Notification::make()->info()->title(__('plugins.admin.notifications.queued', ['plugin' => $name]))->send();
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    protected function rows(): Collection
    {
        $marketplace = app(MarketplaceClient::class)->catalog();

        $catalog = Plugins::catalog()->mapWithKeys(fn (PluginDefinition $definition): array => [
            $definition->slug => $this->row($definition->slug, $definition->name(), $definition->description(), $definition->categoryLabel(), $marketplace),
        ]);

        $thirdParty = Plugins::records()
            ->reject(fn (Plugin $plugin): bool => $catalog->has($plugin->slug))
            ->mapWithKeys(fn (Plugin $plugin): array => [
                $plugin->slug => $this->row($plugin->slug, $plugin->name, $plugin->package, '—', $marketplace),
            ]);

        return $catalog->merge($thirdParty);
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $marketplace
     * @return array<string, mixed>
     */
    protected function row(string $slug, string $name, string $description, string $category, Collection $marketplace): array
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

        $price = $marketplace->get($slug)['price'] ?? null;

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'state' => $state,
            'state_label' => in_array($state, ['installing', 'updating', 'removing', 'failed'], true)
                ? __("plugins.status.{$state}")
                : __("plugins.admin.states.{$state}"),
            'busy' => $status?->isBusy() === true,
            'installed' => $installed,
            'version' => $installed ? $record?->version : null,
            'price' => is_array($price) ? Number::currency((float) ($price['amount'] ?? 0), (string) ($price['currency'] ?? 'EUR'), app()->getLocale()) : null,
            'license' => filled($record?->license_key) ? __('plugins.admin.license_active') : null,
            'organizations' => $record?->organizations->pluck('name')->all() ?? [],
        ];
    }
}
