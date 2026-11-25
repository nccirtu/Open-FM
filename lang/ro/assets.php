<?php

return [

    'child_assets' => 'Sub-assets',
    'overview' => 'Prezentare generală',
    'sections' => [
        'location' => 'Amplasare în clădire',
        'lifecycle' => 'Ciclu de viață',
    ],

    'tabs' => [
        'general' => 'General',
        'maintenance' => 'Mentenanță & Ciclu de viață',
    ],

    'actions' => [
        'new' => 'Nou',
        'open' => 'Deschide',
        'create_asset_type_heading' => 'Creează un tip de asset nou',
        'create_company_heading' => 'Creează o firmă nouă',
        'create_inspection_company_heading' => 'Creează o firmă de inspecție nouă',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Nume',
        'image' => 'Imagine',
        'asset_number' => 'Nr. asset',
        'type' => 'Tip',
        'manufacturer' => 'Producător',
        'serial_number' => 'Număr de serie',
        'serial_number_short' => 'Nr. serie',
        'maintenance_partner' => 'Partener de mentenanță',
        'inspection_company' => 'Firmă de inspecție',
        'last_maintenance' => 'Ultima mentenanță',
        'next_maintenance' => 'Următoarea mentenanță',
        'kategorie' => 'Categorie',
        'gruppe' => 'Grup',
        'location' => 'Amplasare',
        'parent_asset' => 'Instalație superioară',
        'condition' => 'Stare',
        'service_status' => 'Stare de funcționare',
        'warranty_until' => 'Garanție până la',
        'installed_at' => 'Instalat la',
        'decommissioned_at' => 'Scos din funcțiune la',
        'purchase_price' => 'Preț de achiziție',
        'category' => 'Specialitate',
    ],

    'condition' => [
        'good' => 'Bună',
        'fair' => 'Medie',
        'poor' => 'Slabă',
    ],

    'service_status' => [
        'in_operation' => 'În funcțiune',
        'out_of_operation' => 'Scos din funcțiune',
        'in_storage' => 'Depozitat',
        'disposed' => 'Casat',
    ],

    'filters' => [
        'all' => 'Toate',
        'include_sub_locations' => 'incl. sublocații',
        'classification' => 'Clasificare',
        'maintenance_overdue' => 'Restant',
        'due_within' => 'Scadent în :days zile',
        'maintenance_none' => 'Nicio mentenanță planificată',
        'warranty_expired' => 'Expirată',
        'warranty_expiring' => 'Expiră în 90 de zile',
        'warranty_valid' => 'Valabilă',
        'not_specified' => 'Nespecificat',
        'installed_from' => 'Instalat de la',
        'installed_until' => 'Instalat până la',
        'hierarchy' => 'Ierarhia activelor',
        'top_level_only' => 'Doar active principale',
        'sub_assets_only' => 'Doar subactive',
        'decommissioned' => 'Scos din funcțiune',
        'active' => 'În uz',
        'bim' => 'Model BIM',
        'bim_linked' => 'Legat de un element BIM',
        'bim_not_linked' => 'Fără legătură BIM',
    ],

    'import' => [
        'help' => [
            'asset_number' => 'Folosit doar pentru actualizarea activelor existente (opțiunea „Actualizează activele existente”). Activele noi sunt numerotate automat.',
            'property' => 'Numărul obiectului.',
            'location' => 'Numele sau calea locației în obiect, de ex. „Clădirea A › Parter › Hol” (merg și „/” sau „>”).',
            'parent_asset' => 'Nr. sau numele activului părinte din același obiect.',
        ],
        'options' => [
            'update_existing' => 'Actualizează activele existente după număr (celulele goale păstrează valorile)',
            'create_missing' => 'Creează automat tipurile de active și firmele necunoscute',
        ],
        'template' => [
            'action' => 'Șablon de import',
            'heading' => 'Descarcă șablonul de import',
            'description' => 'Fișier CSV cu toate coloanele standard. Selectați tipuri de active pentru a primi și câmpurile lor specifice (de ex. încărcătura stingătorului) drept coloane. Câmpurile suplimentare sunt recunoscute automat la import după antetul coloanei.',
            'download' => 'Descarcă',
            'asset_types' => 'Tipuri de active cu câmpuri suplimentare',
        ],
        'completed_title' => 'Importul activelor s-a încheiat',
        'completed_body' => '{0} Niciun activ nu a fost importat.|{1} :count activ a fost importat.|[2,*] :count active au fost importate.',
        'failed_rows' => '{1} :count rând a eșuat.|[2,*] :count rânduri au eșuat.',
        'errors' => [
            'no_organization' => 'Nu este setat niciun client pentru import.',
            'property_not_found' => 'Obiectul „:value” nu a fost găsit.',
            'location_without_property' => 'Locația „:value” nu poate fi atribuită fără obiect.',
            'location_not_found' => 'Locația „:value” nu a fost găsită în obiect.',
            'location_ambiguous' => 'Locația „:value” nu este unică – indicați calea (de ex. „Clădirea A › Parter › Hol”).',
            'asset_type_not_found' => 'Tipul de activ „:value” nu a fost găsit.',
            'parent_not_found' => 'Activul părinte „:value” nu a fost găsit.',
            'company_not_found' => 'Firma „:value” nu a fost găsită.',
            'invalid_number' => '„:value” nu este un număr valid.',
            'invalid_boolean' => '„:value” nu este o valoare da/nu validă.',
            'invalid_date' => '„:value” nu este o dată validă.',
            'invalid_option' => '„:value” nu este o opțiune validă.',
        ],
    ],

];
