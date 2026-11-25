<?php

return [

    'child_assets' => 'Assety podrzędne',
    'overview' => 'Przegląd',
    'sections' => [
        'location' => 'Lokalizacja w budynku',
        'lifecycle' => 'Cykl życia',
    ],

    'tabs' => [
        'general' => 'Ogólne',
        'maintenance' => 'Konserwacja i cykl życia',
    ],

    'actions' => [
        'new' => 'Nowy',
        'open' => 'Otwórz',
        'create_asset_type_heading' => 'Utwórz nowy typ assetu',
        'create_company_heading' => 'Utwórz nową firmę',
        'create_inspection_company_heading' => 'Utwórz nową firmę kontrolną',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Nazwa',
        'image' => 'Zdjęcie',
        'asset_number' => 'Nr assetu',
        'type' => 'Typ',
        'manufacturer' => 'Producent',
        'serial_number' => 'Numer seryjny',
        'serial_number_short' => 'Nr ser.',
        'maintenance_partner' => 'Partner serwisowy',
        'inspection_company' => 'Firma kontrolna',
        'last_maintenance' => 'Ostatnia konserwacja',
        'next_maintenance' => 'Następna konserwacja',
        'kategorie' => 'Kategoria',
        'gruppe' => 'Grupa',
        'location' => 'Lokalizacja',
        'parent_asset' => 'Instalacja nadrzędna',
        'condition' => 'Stan',
        'service_status' => 'Status eksploatacji',
        'warranty_until' => 'Gwarancja do',
        'installed_at' => 'Zainstalowano',
        'decommissioned_at' => 'Wycofano z eksploatacji',
        'purchase_price' => 'Cena zakupu',
        'category' => 'Branża',
    ],

    'condition' => [
        'good' => 'Dobry',
        'fair' => 'Średni',
        'poor' => 'Zły',
    ],

    'service_status' => [
        'in_operation' => 'W eksploatacji',
        'out_of_operation' => 'Wyłączony z eksploatacji',
        'in_storage' => 'W magazynie',
        'disposed' => 'Zutylizowany',
    ],

    'filters' => [
        'all' => 'Wszystkie',
        'include_sub_locations' => 'wraz z lokalizacjami podrzędnymi',
        'classification' => 'Klasyfikacja',
        'maintenance_overdue' => 'Zaległe',
        'due_within' => 'Termin w ciągu :days dni',
        'maintenance_none' => 'Brak zaplanowanej konserwacji',
        'warranty_expired' => 'Wygasła',
        'warranty_expiring' => 'Wygasa w ciągu 90 dni',
        'warranty_valid' => 'Ważna',
        'not_specified' => 'Nie podano',
        'installed_from' => 'Zainstalowano od',
        'installed_until' => 'Zainstalowano do',
        'hierarchy' => 'Hierarchia zasobów',
        'top_level_only' => 'Tylko zasoby główne',
        'sub_assets_only' => 'Tylko zasoby podrzędne',
        'decommissioned' => 'Wycofane z eksploatacji',
        'active' => 'W użyciu',
        'bim' => 'Model BIM',
        'bim_linked' => 'Powiązane z elementem BIM',
        'bim_not_linked' => 'Bez powiązania BIM',
    ],

    'import' => [
        'help' => [
            'asset_number' => 'Służy tylko do aktualizacji istniejących zasobów (opcja „Aktualizuj istniejące zasoby”). Nowe zasoby otrzymują numer automatycznie.',
            'property' => 'Numer obiektu.',
            'location' => 'Nazwa lub ścieżka lokalizacji w obiekcie, np. „Budynek A › Parter › Korytarz” (działa też „/” lub „>”).',
            'parent_asset' => 'Nr lub nazwa zasobu nadrzędnego w tym samym obiekcie.',
        ],
        'options' => [
            'update_existing' => 'Aktualizuj istniejące zasoby według numeru (puste komórki zachowują wartości)',
            'create_missing' => 'Automatycznie twórz nieznane typy zasobów i firmy',
        ],
        'template' => [
            'action' => 'Szablon importu',
            'heading' => 'Pobierz szablon importu',
            'description' => 'Plik CSV ze wszystkimi standardowymi kolumnami. Wybierz typy zasobów, aby otrzymać również ich pola specyficzne (np. ilość środka w gaśnicy) jako kolumny. Pola dodatkowe są rozpoznawane podczas importu automatycznie po nagłówku kolumny.',
            'download' => 'Pobierz',
            'asset_types' => 'Typy zasobów z polami dodatkowymi',
        ],
        'completed_title' => 'Import zasobów zakończony',
        'completed_body' => '{0} Nie zaimportowano żadnego zasobu.|{1} Zaimportowano :count zasób.|[2,*] Zaimportowano zasoby: :count.',
        'failed_rows' => '{1} :count wiersz zakończył się błędem.|[2,*] Wiersze z błędem: :count.',
        'errors' => [
            'no_organization' => 'Nie ustawiono klienta dla importu.',
            'property_not_found' => 'Nie znaleziono obiektu „:value”.',
            'location_without_property' => 'Lokalizacji „:value” nie można przypisać bez obiektu.',
            'location_not_found' => 'Nie znaleziono lokalizacji „:value” w obiekcie.',
            'location_ambiguous' => 'Lokalizacja „:value” jest niejednoznaczna – podaj ścieżkę (np. „Budynek A › Parter › Korytarz”).',
            'asset_type_not_found' => 'Nie znaleziono typu zasobu „:value”.',
            'parent_not_found' => 'Nie znaleziono zasobu nadrzędnego „:value”.',
            'company_not_found' => 'Nie znaleziono firmy „:value”.',
            'invalid_number' => '„:value” nie jest prawidłową liczbą.',
            'invalid_boolean' => '„:value” nie jest prawidłową wartością tak/nie.',
            'invalid_date' => '„:value” nie jest prawidłową datą.',
            'invalid_option' => '„:value” nie jest prawidłowym wyborem.',
        ],
    ],

];
