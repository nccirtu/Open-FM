<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RoomUse: string implements HasLabel
{
    case SalesFloor = 'sales_floor';
    case Storage = 'storage';
    case Office = 'office';
    case Technical = 'technical';
    case Sanitary = 'sanitary';
    case StaffRoom = 'staff_room';
    case Entrance = 'entrance';
    case Corridor = 'corridor';
    case Outdoor = 'outdoor';
    case WasteCollection = 'waste_collection';
    case Other = 'other';

    public function getLabel(): string
    {
        return __("property.room_use.{$this->value}");
    }
}
