<?php

return [

    'model' => 'Obiekt',
    'models' => 'Obiekty',

    'qr' => [
        'print_structure' => 'Etykiety QR struktura',
        'print_assets' => 'Etykiety QR zasoby',
    ],

    'actions' => [
        'create_modal_heading' => 'Utwórz nowy obiekt',
    ],

    'validation' => [
        'unique_number' => 'Obiekt o tym numerze już istnieje.',
    ],

    'sections' => [
        'master' => 'Dane podstawowe',
        'overview' => 'Przegląd',
        'areas' => 'Powierzchnie i dane budynku',
        'location_energy' => 'Lokalizacja i energia',
    ],

    'fields' => [
        'usage_type' => 'Sposób użytkowania',
        'usage_type_help' => 'Określa wskaźniki i obowiązki w obszarze Energia.',
        'construction_year' => 'Rok budowy',
        'construction_year_help' => 'Do ostrzeżeń o substancjach niebezpiecznych przed rozbiórką (np. azbest przed 1993 r.).',
        'manager' => 'Opiekun',
        'open_tickets' => 'Otwarte usterki',
        'property_number' => 'Numer obiektu',
        'street' => 'Ulica i numer domu',
        'zip_code' => 'Kod pocztowy',
        'city' => 'Miasto',
        'state' => 'Województwo',
        'country' => 'Kraj',
        'gross_area' => 'Powierzchnia brutto (m²)',
        'net_area' => 'Powierzchnia netto (m²)',
        'cleanable_area' => 'Powierzchnia do sprzątania (m²)',
        'heated_volume' => 'Kubatura ogrzewana (m³)',
        'latitude' => 'Szerokość geograficzna',
        'longitude' => 'Długość geograficzna',
        'energy_rating' => 'Klasa efektywności energetycznej',
        'energy_cert_valid_until' => 'Świadectwo energetyczne ważne do',
    ],

    'structure' => [
        'model' => 'Element struktury',
        'models' => 'Struktura',
        'location' => 'Lokalizacja',
        'name' => 'Nazwa',
        'type' => 'Typ',
        'parent' => 'Element nadrzędny',
        'level' => 'Kondygnacja',
        'level_help' => '0 = parter, wartości ujemne = kondygnacje podziemne',
        'room_use' => 'Sposób użytkowania',
        'area' => 'Powierzchnia (m²)',
        'height' => 'Wysokość (m)',
        'heated' => 'Ogrzewane',
        'sort' => 'Sortowanie',
        'assets_count' => 'Assety',
        'total_area' => 'Powierzchnia łączna',
        'rooms_count' => 'Pomieszczenia',
        'nodes_count' => 'Elementy struktury',
        'types' => [
            'building' => 'Budynek',
            'floor' => 'Kondygnacja',
            'unit' => 'Jednostka użytkowa',
            'room' => 'Pomieszczenie',
            'zone' => 'Strefa/obszar',
            'outdoor' => 'Teren zewnętrzny',
        ],
    ],

    'bim' => [
        'title' => 'BIM — obiekt :property',
        'upload' => 'Prześlij model IFC',
        'file' => 'Plik IFC',
        'ifc_guid' => 'IFC-GUID',
        'file_help' => 'IFC 2x3 lub IFC4. Duże pliki mogą chwilę potrwać.',
        'file_extension' => 'Dozwolone są tylko pliki IFC (.ifc).',
        'upload_rejected' => 'Przesyłanie zostało odrzucone, ponieważ ścieżka pliku jest nieprawidłowa.',
        'uploaded' => 'Model BIM przesłany',
        'uploaded_at' => 'Przesłano',
        'empty' => 'Dla tego obiektu nie przesłano jeszcze modelu BIM. Użyj „Prześlij model IFC" w prawym górnym rogu.',
        'open_full' => 'Otwórz pełny widok',
        'show_in_model' => 'Pokaż w modelu BIM',
        'inspector' => [
            'title' => 'Element budowlany',
            'linked_asset' => 'Powiązany asset',
            'linked_node' => 'Powiązany element struktury',
            'no_link' => 'Brak powiązanego elementu w CAFM — możesz utworzyć ten element budowlany bezpośrednio jako asset.',
            'open_asset' => 'Otwórz asset',
            'open_structure' => 'Otwórz strukturę',
            'create_asset' => 'Utwórz jako asset',
            'asset_created' => 'Asset utworzony z elementu BIM',
        ],
    ],

    'map' => [
        'heading' => 'Obiekty na mapie',
        'open_tickets' => ':count otwartych usterek',
        'overdue_orders' => ':count zaległych przeglądów/konserwacji',
    ],

    'room_use' => [
        'sales_floor' => 'Sala sprzedaży',
        'storage' => 'Magazyn',
        'office' => 'Biuro',
        'technical' => 'Pomieszczenie techniczne',
        'sanitary' => 'Pomieszczenie sanitarne',
        'staff_room' => 'Pomieszczenie socjalne',
        'entrance' => 'Wejście/wiatrołap',
        'corridor' => 'Korytarz',
        'outdoor' => 'Teren zewnętrzny',
        'waste_collection' => 'Miejsce gromadzenia odpadów',
        'other' => 'Inne',
    ],

    'tabs' => [
        'mine' => 'Moje obiekty',
        'all' => 'Wszystkie obiekty',
    ],

    'usage_type' => [
        'commercial' => 'Komercyjny',
        'residential' => 'Mieszkalny',
        'mixed' => 'Mieszany',
    ],

];
