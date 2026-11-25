<?php

namespace App\Filament\Portal\Resources\TicketResource\RelationManagers;

use App\Support\Uploads;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicRepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('tickets.replies.title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->reported_by === auth()->id();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('message')
                ->label(__('tickets.replies.message'))
                ->required()
                ->rows(4),
            FileUpload::make('attachments')
                ->label(__('tickets.replies.attachments'))
                ->multiple()
                ->directory('ticket-attachments')
                ->acceptedFileTypes(Uploads::documents())
                ->downloadable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_internal_note', false))
            ->columns([
                TextColumn::make('author.name')
                    ->label(__('tickets.replies.author'))
                    ->placeholder('—'),
                TextColumn::make('message')
                    ->label(__('tickets.replies.message'))
                    ->wrap()
                    ->limit(160),
                TextColumn::make('created_at')
                    ->label(__('tickets.replies.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at')
            ->headerActions([
                CreateAction::make()
                    ->label(__('tickets.replies.create')),
            ]);
    }
}
