<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiTokenResource\Pages\ListApiTokens;
use App\Models\ApiToken;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApiTokenResource extends Resource
{
    protected static ?string $model = ApiToken::class;

    protected static ?string $navigationLabel = 'API-Zugänge';

    protected static ?string $modelLabel = 'API-Token';

    protected static ?string $pluralModelLabel = 'API-Tokens';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.api_tokens.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('abilities')
                    ->label(__('app.api_tokens.abilities'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state),
                TextColumn::make('last_used_at')
                    ->label(__('app.api_tokens.last_used'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder(__('app.api_tokens.never')),
                TextColumn::make('expires_at')
                    ->label(__('app.api_tokens.expires'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder(__('app.api_tokens.unlimited'))
                    ->badge()
                    ->color(fn ($state): string => $state !== null && $state->isPast() ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label(__('app.api_tokens.created'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                DeleteAction::make()
                    ->label(__('app.api_tokens.revoke'))
                    ->modalHeading(__('app.api_tokens.revoke_heading'))
                    ->successNotificationTitle('Token widerrufen'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('app.api_tokens.revoke')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiTokens::route('/'),
        ];
    }
}
