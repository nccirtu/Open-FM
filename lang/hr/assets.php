<?php

return [

    'child_assets' => 'Podređena sredstva',
    'overview' => 'Pregled',
    'sections' => [
        'location' => 'Lokacija u zgradi',
        'lifecycle' => 'Životni ciklus',
    ],

    'tabs' => [
        'general' => 'Općenito',
        'maintenance' => 'Održavanje i životni ciklus',
    ],

    'actions' => [
        'new' => 'Novo',
        'open' => 'Otvori',
        'create_asset_type_heading' => 'Izradi novi tip sredstva',
        'create_company_heading' => 'Izradi novu tvrtku',
        'create_inspection_company_heading' => 'Izradi novu ispitnu tvrtku',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Naziv',
        'image' => 'Slika',
        'asset_number' => 'Br. sredstva',
        'type' => 'Tip',
        'manufacturer' => 'Proizvođač',
        'serial_number' => 'Serijski broj',
        'serial_number_short' => 'Ser. br.',
        'maintenance_partner' => 'Partner za održavanje',
        'inspection_company' => 'Ispitna tvrtka',
        'last_maintenance' => 'Zadnje održavanje',
        'next_maintenance' => 'Sljedeće održavanje',
        'kategorie' => 'Kategorija',
        'gruppe' => 'Grupa',
        'location' => 'Lokacija',
        'parent_asset' => 'Nadređeno sredstvo',
        'condition' => 'Stanje',
        'service_status' => 'Radni status',
        'warranty_until' => 'Jamstvo do',
        'installed_at' => 'Instalirano',
        'decommissioned_at' => 'Stavljeno izvan pogona',
        'purchase_price' => 'Nabavna cijena',
        'category' => 'Struka',
    ],

    'condition' => [
        'good' => 'Dobro',
        'fair' => 'Srednje',
        'poor' => 'Loše',
    ],

    'service_status' => [
        'in_operation' => 'U radu',
        'out_of_operation' => 'Izvan rada',
        'in_storage' => 'Uskladišteno',
        'disposed' => 'Zbrinuto',
    ],

    'filters' => [
        'all' => 'Sve',
        'include_sub_locations' => 'uklj. podlokacije',
        'classification' => 'Klasifikacija',
        'maintenance_overdue' => 'Zakašnjelo',
        'due_within' => 'Dospijeva za :days dana',
        'maintenance_none' => 'Održavanje nije planirano',
        'warranty_expired' => 'Istekla',
        'warranty_expiring' => 'Istječe u 90 dana',
        'warranty_valid' => 'Važeća',
        'not_specified' => 'Nije navedeno',
        'installed_from' => 'Instalirano od',
        'installed_until' => 'Instalirano do',
        'hierarchy' => 'Hijerarhija imovine',
        'top_level_only' => 'Samo glavna imovina',
        'sub_assets_only' => 'Samo podimovina',
        'decommissioned' => 'Stavljeno izvan pogona',
        'active' => 'U uporabi',
        'bim' => 'BIM model',
        'bim_linked' => 'Povezano s BIM elementom',
        'bim_not_linked' => 'Bez BIM poveznice',
    ],

    'import' => [
        'help' => [
            'asset_number' => 'Koristi se samo za ažuriranje postojeće imovine (opcija „Ažuriraj postojeću imovinu“). Nova imovina dobiva broj automatski.',
            'property' => 'Broj objekta.',
            'location' => 'Naziv ili putanja lokacije u objektu, npr. „Zgrada A › Prizemlje › Hodnik“ (može i „/“ ili „>“).',
            'parent_asset' => 'Broj ili naziv nadređene imovine u istom objektu.',
        ],
        'options' => [
            'update_existing' => 'Ažuriraj postojeću imovinu prema broju (prazne ćelije zadržavaju vrijednosti)',
            'create_missing' => 'Automatski izradi nepoznate vrste imovine i tvrtke',
        ],
        'template' => [
            'action' => 'Predložak za uvoz',
            'heading' => 'Preuzmi predložak za uvoz',
            'description' => 'CSV datoteka sa svim standardnim stupcima. Odaberite vrste imovine kako biste dobili i njihova specifična polja (npr. punjenje vatrogasnog aparata) kao stupce. Dodatna polja prepoznaju se pri uvozu automatski prema zaglavlju stupca.',
            'download' => 'Preuzmi',
            'asset_types' => 'Vrste imovine s dodatnim poljima',
        ],
        'completed_title' => 'Uvoz imovine završen',
        'completed_body' => '{0} Nije uvezena nijedna imovina.|{1} Uvezena je :count imovina.|[2,*] Uvezeno je :count stavki imovine.',
        'failed_rows' => '{1} :count redak nije uspio.|[2,*] :count redaka nije uspjelo.',
        'errors' => [
            'no_organization' => 'Za uvoz nije postavljen klijent.',
            'property_not_found' => 'Objekt „:value“ nije pronađen.',
            'location_without_property' => 'Lokacija „:value“ ne može se dodijeliti bez objekta.',
            'location_not_found' => 'Lokacija „:value“ nije pronađena u objektu.',
            'location_ambiguous' => 'Lokacija „:value“ nije jednoznačna – navedite putanju (npr. „Zgrada A › Prizemlje › Hodnik“).',
            'asset_type_not_found' => 'Vrsta imovine „:value“ nije pronađena.',
            'parent_not_found' => 'Nadređena imovina „:value“ nije pronađena.',
            'company_not_found' => 'Tvrtka „:value“ nije pronađena.',
            'invalid_number' => '„:value“ nije valjan broj.',
            'invalid_boolean' => '„:value“ nije valjana vrijednost da/ne.',
            'invalid_date' => '„:value“ nije valjan datum.',
            'invalid_option' => '„:value“ nije valjan odabir.',
        ],
    ],

];
