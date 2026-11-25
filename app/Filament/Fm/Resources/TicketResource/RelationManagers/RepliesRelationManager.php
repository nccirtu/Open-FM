<?php

namespace App\Filament\Fm\Resources\TicketResource\RelationManagers;

use App\Support\Uploads;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('tickets.replies.title');
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
            Toggle::make('is_internal_note')
                ->label(__('tickets.replies.internal_note'))
                ->helperText(__('tickets.replies.internal_note_help')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author.name')
                    ->label(__('tickets.replies.author'))
                    ->placeholder('—'),
                TextColumn::make('message')
                    ->label(__('tickets.replies.message'))
                    ->wrap()
                    ->limit(160),
                IconColumn::make('is_internal_note')
                    ->label(__('tickets.replies.internal_note'))
                    ->boolean(),
                TextColumn::make('attachments')
                    ->label(__('tickets.replies.attachments'))
                    ->state(fn ($record): ?int => filled($record->attachments) ? count($record->attachments) : null)
                    ->badge()
                    ->placeholder('—'),
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
