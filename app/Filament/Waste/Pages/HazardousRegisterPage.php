<?php

namespace App\Filament\Waste\Pages;

use App\Filament\Exports\WasteRegisterExporter;
use App\Models\WasteCollection;
use App\Models\WasteTreatment;
use App\Services\Waste\HazardousWasteRegister;
use App\Services\Waste\WasteCollectionRecorder;
use App\Support\Uploads;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HazardousRegisterPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 61;

    protected static ?string $slug = 'register';

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.compliance');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.register');
    }

    public function getTitle(): string
    {
        return __('waste.pages.register');
    }

    public function getSubheading(): ?string
    {
        return __('waste.register.subheading');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', WasteCollection::class) ?? false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        $register = app(HazardousWasteRegister::class);

        return $table
            ->query(fn (): Builder => $register->query()->with(['property', 'fraction', 'company', 'treatment']))
            ->defaultSort('collected_at', 'desc')
            ->emptyStateHeading(__('waste.register.empty'))
            ->emptyStateDescription(__('waste.register.empty_help'))
            ->columns([
                TextColumn::make('collected_at')
                    ->label(__('waste.fields.collected_at'))
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('property.property_number')
                    ->label(__('property.model'))
                    ->searchable(),
                TextColumn::make('fraction.name')
                    ->label(__('waste.models.fraction.singular'))
                    ->description(fn (WasteCollection $record): ?string => $record->fraction?->code)
                    ->badge()
                    ->color(fn (WasteCollection $record): array => Color::hex($record->fraction?->displayColor() ?? '#94a3b8')),
                TextColumn::make('mass_kg')
                    ->label(__('waste.fields.mass_kg'))
                    ->numeric(decimalPlaces: 1, locale: 'de')
                    ->suffix(' kg'),
                TextColumn::make('company.name')
                    ->label(__('waste.fields.company'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('document_number')
                    ->label(__('waste.fields.document_number'))
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('certificate_number')
                    ->label(__('waste.fields.certificate_number'))
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('register_status')
                    ->label(__('waste.fields.register_status'))
                    ->badge()
                    ->state(fn (WasteCollection $record): string => __('waste.register.status.'.$register->status($record)))
                    ->color(fn (WasteCollection $record): string => match ($register->status($record)) {
                        'entered' => 'success',
                        'overdue' => 'danger',
                        default => 'warning',
                    })
                    ->description(fn (WasteCollection $record): ?string => $register->status($record) === 'entered'
                        ? null
                        : ($register->deadline($record) !== null ? __('waste.register.until', ['date' => $register->deadline($record)->format('d.m.Y')]) : null)),
                TextColumn::make('retain_until')
                    ->label(__('waste.fields.retain_until'))
                    ->state(fn (WasteCollection $record): ?string => $register->retainUntil($record)?->format('d.m.Y'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('year')
                    ->label(__('waste.fields.year'))
                    ->options(fn (): array => collect(range(now()->year, now()->year - 5))->mapWithKeys(fn (int $year): array => [$year => (string) $year])->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, string $year): Builder => $query->whereYear('collected_at', (int) $year))),
                Filter::make('missing')
                    ->label(__('waste.register.missing_only'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('document_number')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label(__('waste.actions.export_csv'))
                    ->exporter(WasteRegisterExporter::class),
            ])
            ->recordActions([
                $this->editRegisterAction(),
            ]);
    }

    private function editRegisterAction(): Action
    {
        return Action::make('editRegister')
            ->label(__('waste.actions.edit_register'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->iconButton()
            ->authorize(fn (WasteCollection $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->fillForm(fn (WasteCollection $record): array => [
                'document_number' => $record->document_number,
                'certificate_number' => $record->certificate_number,
                'waste_treatment_id' => $record->waste_treatment_id,
                'receipt' => $record->receipt,
                'note' => $record->note,
            ])
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('document_number')
                        ->label(__('waste.fields.document_number'))
                        ->helperText(__('waste.help.document_number'))
                        ->maxLength(100),
                    TextInput::make('certificate_number')
                        ->label(__('waste.fields.certificate_number'))
                        ->helperText(__('waste.help.certificate_number'))
                        ->maxLength(100),
                ]),
                Select::make('waste_treatment_id')
                    ->label(__('waste.models.treatment.singular'))
                    ->options(fn (): array => WasteTreatment::query()->orderBy('sort')->get()->mapWithKeys(fn (WasteTreatment $treatment): array => [$treatment->getKey() => $treatment->label()])->all())
                    ->searchable(),
                FileUpload::make('receipt')
                    ->label(__('waste.fields.receipt'))
                    ->visibility('private')
                    ->directory('waste-receipts')
                    ->acceptedFileTypes(Uploads::documents()),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(2),
            ])
            ->action(function (WasteCollection $record, array $data): void {
                app(WasteCollectionRecorder::class)->update($record, $record->collected_at, null, null, [
                    'document_number' => $data['document_number'] ?? null,
                    'certificate_number' => $data['certificate_number'] ?? null,
                    'waste_treatment_id' => $data['waste_treatment_id'] ?? null,
                    'receipt' => $data['receipt'] ?? null,
                    'note' => $data['note'] ?? null,
                ]);

                Notification::make()->success()->title(__('waste.notifications.register_saved'))->send();
            });
    }
}
