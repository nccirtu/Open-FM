<?php

namespace App\Filament\Waste\Actions;

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMissedReason;
use App\Enums\WastePickupStatus;
use App\Filament\Waste\Pages\ConfirmPickupsPage;
use App\Models\WastePickup;
use App\Services\Waste\PickupService;
use App\Support\Uploads;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

final class PickupActions
{
    /**
     * @return array<int, Action>
     */
    public static function all(): array
    {
        return [self::confirm(), self::missed(), self::plan(), self::cancel()];
    }

    public static function confirm(): Action
    {
        return Action::make('confirmPickup')
            ->label(__('waste.actions.confirm'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (WastePickup $record): bool => ($record->status === WastePickupStatus::Planned || $record->isCompletedByPlan())
                && $record->due_on !== null
                && $record->due_on->lessThanOrEqualTo(CarbonImmutable::today()))
            ->authorize(fn (WastePickup $record): bool => auth()->user()?->can('confirm', $record) ?? false)
            ->modalHeading(fn (WastePickup $record): string => $record->container?->label() ?? __('waste.actions.confirm'))
            ->schema([
                ToggleButtons::make('fill_level_pct')
                    ->label(__('waste.fields.fill_level'))
                    ->options(collect(ConfirmPickupsPage::FILL_LEVELS)->mapWithKeys(fn (int $level): array => [$level => $level.' %'])->all())
                    ->inline(),
                TextInput::make('mass_kg')
                    ->label(__('waste.fields.mass_kg'))
                    ->helperText(__('waste.help.mass_optional'))
                    ->numeric()
                    ->minValue(0)
                    ->suffix('kg'),
                FileUpload::make('receipt')
                    ->label(__('waste.fields.receipt'))
                    ->visibility('private')
                    ->directory('waste-receipts')
                    ->acceptedFileTypes(Uploads::documents()),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(2),
            ])
            ->action(function (WastePickup $record, array $data): void {
                app(PickupService::class)->confirm(
                    $record,
                    WasteCollectionSource::Manual,
                    filled($data['fill_level_pct'] ?? null) ? (int) $data['fill_level_pct'] : null,
                    filled($data['mass_kg'] ?? null) ? (float) $data['mass_kg'] : null,
                    ['receipt' => $data['receipt'] ?? null, 'note' => $data['note'] ?? null],
                );

                Notification::make()->success()->title(trans_choice('waste.notifications.confirmed', 1, ['count' => 1]))->send();
            });
    }

    public static function missed(): Action
    {
        return Action::make('reportMissed')
            ->label(__('waste.actions.report_missed'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (WastePickup $record): bool => ($record->status === WastePickupStatus::Planned || $record->isCompletedByPlan())
                && $record->due_on !== null
                && $record->due_on->lessThanOrEqualTo(CarbonImmutable::today()))
            ->authorize(fn (WastePickup $record): bool => auth()->user()?->can('confirm', $record) ?? false)
            ->modalDescription(__('waste.help.missed'))
            ->schema([
                Select::make('reason')
                    ->label(__('waste.fields.missed_reason'))
                    ->options(WasteMissedReason::class)
                    ->default(WasteMissedReason::DisposerAbsent->value)
                    ->required()
                    ->native(false),
                Textarea::make('note')
                    ->label(__('waste.fields.note'))
                    ->rows(2),
            ])
            ->action(function (WastePickup $record, array $data): void {
                $reason = $data['reason'] instanceof WasteMissedReason ? $data['reason'] : WasteMissedReason::from($data['reason']);
                $extra = app(PickupService::class)->markMissed($record, $reason, $data['note'] ?? null);

                Notification::make()
                    ->warning()
                    ->title(__('waste.notifications.missed'))
                    ->body($extra !== null ? __('waste.notifications.extra_requested') : null)
                    ->send();
            });
    }

    public static function plan(): Action
    {
        return Action::make('planPickup')
            ->label(__('waste.actions.plan_pickup'))
            ->icon('heroicon-o-calendar')
            ->color('info')
            ->visible(fn (WastePickup $record): bool => $record->status === WastePickupStatus::Requested)
            ->authorize(fn (WastePickup $record): bool => auth()->user()?->can('confirm', $record) ?? false)
            ->schema([
                DatePicker::make('due_on')
                    ->label(__('waste.fields.due_on'))
                    ->native(false)
                    ->required(),
            ])
            ->action(function (WastePickup $record, array $data): void {
                app(PickupService::class)->plan($record, CarbonImmutable::parse($data['due_on']));

                Notification::make()->success()->title(__('waste.notifications.planned'))->send();
            });
    }

    public static function cancel(): Action
    {
        return Action::make('cancelPickup')
            ->label(__('waste.actions.cancel_pickup'))
            ->icon('heroicon-o-minus-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (WastePickup $record): bool => in_array($record->status, [WastePickupStatus::Planned, WastePickupStatus::Requested], true))
            ->authorize(fn (WastePickup $record): bool => auth()->user()?->can('confirm', $record) ?? false)
            ->action(function (WastePickup $record): void {
                app(PickupService::class)->cancel($record);

                Notification::make()->success()->title(__('waste.notifications.cancelled'))->send();
            });
    }

    public static function confirmBulk(): BulkAction
    {
        return BulkAction::make('confirmPickups')
            ->label(__('waste.actions.confirm'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('waste.help.confirm_bulk'))
            ->authorize(fn (): bool => auth()->user()?->can('confirm', WastePickup::class) ?? false)
            ->action(function (Collection $records): void {
                $service = app(PickupService::class);
                $today = CarbonImmutable::today();

                $confirmable = $records->filter(fn (WastePickup $pickup): bool => ($pickup->status === WastePickupStatus::Planned || $pickup->isCompletedByPlan())
                    && $pickup->due_on !== null
                    && $pickup->due_on->lessThanOrEqualTo($today));

                $confirmable->each(fn (WastePickup $pickup) => $service->confirm($pickup, WasteCollectionSource::Manual));

                Notification::make()
                    ->success()
                    ->title(trans_choice('waste.notifications.confirmed', $confirmable->count(), ['count' => $confirmable->count()]))
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
