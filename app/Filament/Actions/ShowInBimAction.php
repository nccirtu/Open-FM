<?php

namespace App\Filament\Actions;

use App\Filament\Resources\PropertyResource\Pages\BimPage;
use App\Models\BimModel;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View as ViewFactory;

class ShowInBimAction
{
    public static function make(): Action
    {
        return Action::make('showInBim')
            ->label(__('property.bim.show_in_model'))
            ->icon('heroicon-o-cube-transparent')
            ->slideOver()
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('app.close'))
            ->modalHeading(fn (Model $record): string => $record->name ?? __('property.bim.show_in_model'))
            ->visible(fn (Model $record): bool => filled($record->ifc_guid))
            ->modalContent(fn (Model $record): View => ViewFactory::make('filament.bim-drawer', [
                'bimModel' => self::bimModelFor($record),
                'guid' => $record->ifc_guid,
            ]))
            ->extraModalFooterActions(fn (Model $record): array => [
                Action::make('openBimPage')
                    ->label(__('property.bim.open_full'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(BimPage::getUrl(['record' => $record->property_id], panel: 'fm').'?highlight='.$record->ifc_guid),
            ]);
    }

    public static function bimModelFor(Model $record): ?BimModel
    {
        return BimModel::query()
            ->where('property_id', $record->property_id)
            ->latest()
            ->first();
    }
}
