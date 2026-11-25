<?php

namespace App\Filament\Fm\Resources\CompanyResource\RelationManagers;

use App\Models\CompanyContact;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('companies.contacts.models');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')->label(__('companies.contacts.first_name'))->maxLength(255),
            TextInput::make('last_name')->label(__('companies.contacts.last_name'))->required()->maxLength(255),
            TextInput::make('position')->label(__('companies.contacts.position'))->maxLength(255),
            TextInput::make('email')->label(__('companies.contacts.email'))->email()->maxLength(255),
            TextInput::make('phone')->label(__('companies.contacts.phone'))->tel()->maxLength(50),
            TextInput::make('mobile')->label(__('companies.contacts.mobile'))->tel()->maxLength(50),
            Toggle::make('is_primary')->label(__('companies.contacts.is_primary'))->inline(false),
            Textarea::make('notes')->label(__('companies.contacts.notes'))->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('last_name')->label(__('companies.contacts.models'))->state(fn (CompanyContact $record): string => $record->fullName())->weight('medium')->searchable(['first_name', 'last_name']),
                TextColumn::make('position')->label(__('companies.contacts.position'))->placeholder('—'),
                TextColumn::make('email')->label(__('companies.contacts.email'))->placeholder('—')->copyable(),
                TextColumn::make('phone')->label(__('companies.contacts.phone'))->placeholder('—'),
                TextColumn::make('mobile')->label(__('companies.contacts.mobile'))->placeholder('—'),
                IconColumn::make('is_primary')->label(__('companies.contacts.is_primary'))->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ActionGroup::make([EditAction::make(), DeleteAction::make()])]);
    }
}
