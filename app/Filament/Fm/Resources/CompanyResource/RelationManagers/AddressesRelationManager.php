<?php

namespace App\Filament\Fm\Resources\CompanyResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('companies.addresses.models');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label(__('companies.addresses.type'))
                ->options(collect(['billing', 'shipping', 'site', 'other'])->mapWithKeys(fn (string $type): array => [$type => __('companies.addresses.types.'.$type)])->all())
                ->default('billing')
                ->required()
                ->native(false),
            TextInput::make('label')->label(__('companies.addresses.label'))->maxLength(255),
            TextInput::make('street')->label(__('companies.addresses.street'))->maxLength(255)->columnSpanFull(),
            TextInput::make('zip_code')->label(__('companies.addresses.zip_code'))->maxLength(20),
            TextInput::make('city')->label(__('companies.addresses.city'))->maxLength(255),
            TextInput::make('state')->label(__('companies.addresses.state'))->maxLength(255),
            TextInput::make('country')->label(__('companies.addresses.country'))->default('DE')->maxLength(2),
            Toggle::make('is_primary')->label(__('companies.addresses.is_primary'))->inline(false),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label(__('companies.addresses.type'))->formatStateUsing(fn (string $state): string => __('companies.addresses.types.'.$state))->badge(),
                TextColumn::make('label')->label(__('companies.addresses.label'))->placeholder('—'),
                TextColumn::make('street')->label(__('companies.addresses.street'))->placeholder('—'),
                TextColumn::make('zip_code')->label(__('companies.addresses.zip_code')),
                TextColumn::make('city')->label(__('companies.addresses.city')),
                TextColumn::make('country')->label(__('companies.addresses.country')),
                IconColumn::make('is_primary')->label(__('companies.addresses.is_primary'))->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ActionGroup::make([EditAction::make(), DeleteAction::make()])]);
    }
}
