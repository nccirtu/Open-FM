<?php

namespace App\Filament\Energy\Actions;

use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\Energy\MeterReadingRecorder;
use App\Support\Uploads;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class RecordReadingAction
{
    /**
     * @param  (callable(Model): ?Meter)|null  $resolveMeter
     */
    public static function make(string $name = 'recordReading', ?callable $resolveMeter = null): Action
    {
        $meterOf = $resolveMeter ?? fn (?Model $record): ?Meter => $record instanceof Meter ? $record : null;

        return Action::make($name)
            ->label(__('energy.actions.record_reading'))
            ->icon('heroicon-o-pencil-square')
            ->modalHeading(fn (?Model $record): string => __('energy.actions.record_reading').' · '.($meterOf($record)?->name ?? ''))
            ->modalSubmitActionLabel(__('energy.actions.save_reading'))
            ->visible(fn (?Model $record): bool => ($meter = $meterOf($record)) !== null && ! $meter->isVirtual() && (auth()->user()?->can('create', MeterReading::class) ?? false))
            ->schema(fn (?Model $record): array => self::schema($meterOf($record)))
            ->action(function (array $data, ?Model $record) use ($meterOf): void {
                $meter = $meterOf($record);
                $reading = app(MeterReadingRecorder::class)->record(
                    $meter,
                    CarbonImmutable::parse($data['read_at']),
                    (float) $data['value'],
                    MeterReadingSource::Manual,
                    ['photo' => $data['photo'] ?? null, 'note' => $data['note'] ?? null],
                );

                $reading->status === MeterReadingStatus::Suspicious
                    ? Notification::make()->warning()->title(__('energy.notifications.saved_suspicious'))->send()
                    : Notification::make()->success()->title(__('energy.notifications.saved'))->send();
            });
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(?Meter $meter, ?Closure $meterResolver = null): array
    {
        $resolve = $meterResolver ?? fn (): ?Meter => $meter;

        return [
            TextEntry::make('last_reading')
                ->label(__('energy.fields.last_reading'))
                ->state(function () use ($resolve): string {
                    $last = $resolve()?->latestReading;

                    return $last === null
                        ? __('energy.readings.none')
                        : number_format((float) $last->value, 3, ',', '.').' '.$resolve()->unit.' · '.$last->read_at->format('d.m.Y H:i');
                }),
            DateTimePicker::make('read_at')
                ->label(__('energy.fields.read_at'))
                ->default(now()->startOfMinute())
                ->seconds(false)
                ->maxDate(now()->addDay())
                ->native(false)
                ->required()
                ->live(onBlur: true),
            TextInput::make('value')
                ->label(fn (): string => __('energy.fields.value').($resolve()?->unit ? ' ('.$resolve()->unit.')' : ''))
                ->numeric()
                ->minValue(0)
                ->required()
                ->live(onBlur: true),
            TextEntry::make('plausibility')
                ->hiddenLabel()
                ->color('warning')
                ->icon('heroicon-o-exclamation-triangle')
                ->state(fn (Get $get): ?string => self::warning($resolve(), $get('read_at'), $get('value')))
                ->visible(fn (Get $get): bool => self::warning($resolve(), $get('read_at'), $get('value')) !== null),
            FileUpload::make('photo')
                ->label(__('energy.fields.photo'))
                ->image()
                ->acceptedFileTypes(Uploads::images())
                ->visibility('private')
                ->directory('meter-readings')
                ->maxSize(8192),
            Textarea::make('note')
                ->label(__('energy.fields.note'))
                ->rows(2),
        ];
    }

    public static function warning(?Meter $meter, mixed $readAt, mixed $value): ?string
    {
        if ($meter === null || blank($readAt) || ! is_numeric($value)) {
            return null;
        }

        try {
            $check = app(MeterReadingRecorder::class)->check($meter, CarbonImmutable::parse($readAt), (float) $value);
        } catch (Throwable) {
            return null;
        }

        return $check->isSuspicious() ? $check->message($meter->evaluationUnit()) : null;
    }
}
