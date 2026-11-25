<?php

namespace App\Filament\Contracts\Widgets;

use App\Models\Contract;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringContractsWidget extends TableWidget
{
    public static int $gridW = 6;

    public static int $gridH = 11;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Contract::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.widgets.expiring_contracts'))
            ->modelLabel(__('contracts.model'))
            ->pluralModelLabel(__('contracts.models'))
            ->query(
                Contract::query()
                    ->active()
                    ->whereNotNull('end_date')
                    ->where('end_date', '<=', now()->addDays(180))
                    ->orderBy('end_date')
            )
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('name')
                    ->label(__('contracts.fields.name'))
                    ->limit(35),
                TextColumn::make('partner_name')
                    ->label(__('contracts.fields.partner_name'))
                    ->limit(25),
                TextColumn::make('end_date')
                    ->label(__('contracts.fields.end_date'))
                    ->date('d.m.Y'),
                TextColumn::make('notice_deadline')
                    ->label(__('contracts.fields.notice_deadline'))
                    ->state(fn (Contract $record) => $record->noticeDeadline()?->format('d.m.Y') ?? '—')
                    ->color(fn (Contract $record): string => $record->noticeDeadlineColor()),
            ]);
    }
}
