<?php

return [

    'tab' => 'Energia',

    'meter' => [
        'model' => 'Licznik',
        'models' => 'Liczniki',
        'measured_assets' => 'Odbiorniki',
        'supplied_nodes' => 'Zasilane pomieszczenia',
    ],

    'reading' => [
        'model' => 'Odczyt',
        'models' => 'Odczyty',
    ],

    'readings' => [
        'none' => 'Brak odczytów',
    ],

    'goal' => [
        'model' => 'Cel',
        'models' => 'Cele',
        'all_properties' => 'Wszystkie obiekty',
        'asset' => 'Urządzenie',
        'max' => 'maks.',
        'vs_year' => 'wzgl. :year',
        'actual' => 'dotychczas',
        'forecast' => 'Prognoza',
        'target' => 'Cel',
        'empty' => 'Na ten rok nie zdefiniowano jeszcze celów.',
    ],

    'pages' => [
        'record_readings' => 'Odczyt liczników',
    ],

    'sections' => [
        'master' => 'Dane podstawowe',
        'formula' => 'Obliczenie',
    ],

    'fields' => [
        'name' => 'Nazwa',
        'number' => 'Numer licznika',
        'medium' => 'Rodzaj energii',
        'kind' => 'Rodzaj licznika',
        'parent' => 'Licznik nadrzędny',
        'unit' => 'Jednostka liczydła',
        'factor' => 'Współczynnik przeliczeniowy',
        'location' => 'Lokalizacja',
        'device_asset' => 'Urządzenie pomiarowe (asset)',
        'market_location_id' => 'Kod PPE / PPG',
        'calibration_valid_until' => 'Legalizacja ważna do',
        'is_active' => 'Aktywny',
        'notes' => 'Notatki',
        'sign' => 'Znak',
        'share' => 'Udział',
        'last_reading' => 'Ostatni stan',
        'read_at' => 'Data i godzina',
        'value' => 'Stan licznika',
        'photo' => 'Zdjęcie licznika',
        'note' => 'Uwaga',
        'source' => 'Źródło',
        'status' => 'Weryfikacja',
        'created_by' => 'Wprowadzone przez',
        'consumption_year' => 'Zużycie :year',
        'goal_scope' => 'Dotyczy',
        'goal_type' => 'Rodzaj celu',
        'goal_period' => 'Okres',
        'year' => 'Rok',
        'target_value' => 'Wartość docelowa',
        'baseline_year' => 'Rok bazowy',
        'progress' => 'Postęp',
    ],

    'help' => [
        'parent' => 'Licznik nadrzędny w drzewie liczników, np. licznik główny. Puste w przypadku liczników głównych.',
        'factor' => 'Przelicza stan licznika na kWh, np. dla gazu ciepło spalania × współczynnik konwersji (ok. 10,5). Dla liczników kWh: 1.',
        'device_asset' => 'Opcjonalnie: urządzenie jako asset, np. na potrzeby tabliczki znamionowej, etykiety QR i konserwacji.',
        'formula' => 'Licznik obliczeniowy nie ma urządzenia. Jego zużycie wynika z innych liczników, np. licznik główny minus wszystkie podliczniki.',
        'share' => 'Jeśli licznik mierzy kilka urządzeń lub obszarów, jego zużycie jest dzielone według tych udziałów.',
        'goal_scope' => 'Pozostaw puste dla celu obejmującego wszystkie obiekty.',
        'market_location_id' => 'Kod punktu poboru nadany przez operatora sieci (widnieje na każdym rachunku za prąd i gaz, np. PPE/PPG). Tylko dla liczników głównych, np. do dopasowania faktur i danych operatora.',
    ],

    'medium' => [
        'electricity' => 'Energia elektryczna',
        'gas' => 'Gaz',
        'heat' => 'Ciepło',
        'cold' => 'Chłód',
        'water' => 'Woda',
        'oil' => 'Olej opałowy',
        'pv_generation' => 'Produkcja PV',
    ],

    'kind' => [
        'main' => 'Licznik główny',
        'sub' => 'Podlicznik',
        'virtual' => 'Licznik obliczeniowy',
    ],

    'source' => [
        'manual' => 'Telefon / web',
        'import' => 'Import',
        'api' => 'Interfejs',
    ],

    'status' => [
        'plausible' => 'wiarygodny',
        'suspicious' => 'podejrzany',
    ],

    'goal_type' => [
        'max_consumption' => 'Maksymalne zużycie',
        'max_intensity' => 'Wskaźnik na m²',
        'reduction' => 'Oszczędność względem roku bazowego',
    ],

    'goal_period' => [
        'year' => 'Rok',
        'month' => 'miesięcznie',
    ],

    'goal_status' => [
        'good' => 'zgodnie z planem',
        'warn' => 'zagrożony',
        'crit' => 'przekroczony',
        'unknown' => 'brak bazy',
    ],

    'plausibility' => [
        'decreased' => 'Stan jest niższy niż poprzedni. Literówka czy wymiana licznika?',
        'unusually_high' => 'Nietypowo wysoki: :actual :unit dziennie, zwykle ok. :expected :unit. Sprawdź i zrób zdjęcie.',
        'short' => [
            'decreased' => 'niższy niż poprzednio',
            'unusually_high' => 'nietypowo wysoki',
        ],
    ],

    'actions' => [
        'record_reading' => 'Wprowadź odczyt',
        'save_reading' => 'Zapisz stan',
        'add_formula_term' => 'Dodaj licznik',
        'attach_asset' => 'Przypisz urządzenie',
        'attach_node' => 'Przypisz pomieszczenie',
        'edit_share' => 'Zmień udział',
        'import_readings' => 'Importuj odczyty',
        'open_in_energy' => 'Otwórz w obszarze Energia',
        'all_goals' => 'Wszystkie cele',
    ],

    'notifications' => [
        'saved' => 'Stan zapisany',
        'saved_suspicious' => 'Stan zapisany, ale podejrzany',
    ],

    'errors' => [
        'virtual_meter' => 'Dla liczników obliczeniowych nie można wprowadzać stanów.',
    ],

    'filters' => [
        'calibration_due' => 'Wymagana legalizacja (180 dni)',
        'from' => 'Od',
        'until' => 'Do',
        'period' => 'Okres',
        'all_usage_types' => 'Wszystkie rodzaje użytkowania',
        'all_properties' => 'Wszystkie obiekty',
        'periods' => [
            'this_month' => 'Ten miesiąc',
            'last_month' => 'Poprzedni miesiąc',
            'last_30_days' => 'Ostatnie 30 dni',
            'this_quarter' => 'Ten kwartał',
            'this_year' => 'Ten rok',
            'last_year' => 'Poprzedni rok',
            'last_12_months' => 'Ostatnie 12 miesięcy',
            'custom' => 'Własny zakres',
        ],
    ],

    'stats' => [
        'energy_in_period' => 'Energia w okresie',
        'water_in_period' => 'Woda w okresie',
        'goals_on_track' => 'Cele zgodne z planem',
        'suspicious_readings' => 'Podejrzane odczyty',
        'calibration_due' => 'Wymagana legalizacja',
        'next_180_days' => 'w ciągu najbliższych 180 dni',
        'vs_previous_year' => ':change % wzgl. poprzedniego roku',
        'no_comparison' => 'brak wartości z poprzedniego roku',
        'year_to_date' => 'Zużycie :year dotychczas',
        'previous_year_to_date' => 'Ten sam okres :year',
    ],

    'charts' => [
        'consumption_trend' => 'Zużycie miesięczne',
        'consumption_per_day' => 'Zużycie dzienne',
        'meter_consumption' => 'Zużycie',
        'by_asset' => 'Największe odbiorniki',
        'consumption_in' => 'Zużycie w :unit',
        'previous_year' => 'Poprzedni rok',
        'last_30_days' => 'Ostatnie 30 dni',
        'last_12_months' => 'Ostatnie 12 miesięcy',
    ],

    'flow' => [
        'title' => 'Przepływ energii',
        'description' => 'Od liczników głównych przez podliczniki aż do urządzeń. Szerokość = ilość.',
        'empty' => 'Dla tego obiektu i rodzaju energii brak zużycia w tym okresie.',
        'unassigned' => 'nieprzypisane',
    ],

    'consumers' => [
        'title' => 'Odbiorniki i przepływ energii',
        'description' => 'Dokąd trafia zużycie tego licznika: przez podliczniki do przypisanych instalacji. Szerokość = ilość.',
        'empty' => 'Do tego licznika nie przypisano jeszcze odbiorników. Przypisz mierzone instalacje w zakładce „Odbiorniki” lub podłącz pod niego podliczniki.',
        'no_consumption' => 'W wybranym roku brak zużycia dla tego licznika.',
        'list' => 'Największe odbiorniki',
        'hint' => 'Jeśli licznik mierzy kilka instalacji, zużycie dzieli się według zapisanych udziałów. „:unassigned” to reszta, której nie wyjaśnia żadna instalacja ani podlicznik.',
    ],

    'comparison' => [
        'title' => 'Porównanie obiektów',
        'energy' => 'Energia w okresie',
        'intensity' => 'na m²',
        'change' => 'wzgl. poprzedniego roku',
    ],

    'calibration' => [
        'title' => 'Wymagana legalizacja',
        'empty' => 'Brak legalizacji wymaganych w ciągu najbliższych 180 dni',
    ],

    'suspicious' => [
        'title' => 'Podejrzane odczyty',
        'empty' => 'Brak podejrzanych odczytów w okresie',
    ],

    'import' => [
        'help' => [
            'meter' => 'Numer licznika, ewentualnie unikalna nazwa licznika.',
        ],
        'completed_title' => 'Import odczytów zakończony',
        'completed_body' => '{0} Nie zaimportowano żadnego odczytu.|{1} Zaimportowano :count odczyt.|[2,*] Zaimportowano odczyty: :count.',
        'errors' => [
            'meter_not_found' => 'Nie znaleziono licznika „:value”.',
            'meter_ambiguous' => 'Licznik „:value” nie jest jednoznaczny – użyj numeru licznika.',
        ],
    ],

];
