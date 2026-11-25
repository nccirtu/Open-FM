<?php

return [

    'child_assets' => 'Sub-assets',
    'overview' => 'Overzicht',
    'sections' => [
        'location' => 'Locatie in het gebouw',
        'lifecycle' => 'Levenscyclus',
    ],

    'tabs' => [
        'general' => 'Algemeen',
        'maintenance' => 'Onderhoud & levenscyclus',
    ],

    'actions' => [
        'new' => 'Nieuw',
        'open' => 'Openen',
        'create_asset_type_heading' => 'Nieuw assettype aanmaken',
        'create_company_heading' => 'Nieuw bedrijf aanmaken',
        'create_inspection_company_heading' => 'Nieuw keuringsbedrijf aanmaken',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Naam',
        'image' => 'Afbeelding',
        'asset_number' => 'Assetnr.',
        'type' => 'Type',
        'manufacturer' => 'Fabrikant',
        'serial_number' => 'Serienummer',
        'serial_number_short' => 'Serienr.',
        'maintenance_partner' => 'Onderhoudspartner',
        'inspection_company' => 'Keuringsbedrijf',
        'last_maintenance' => 'Laatste onderhoud',
        'next_maintenance' => 'Volgend onderhoud',
        'kategorie' => 'Categorie',
        'gruppe' => 'Groep',
        'location' => 'Locatie',
        'parent_asset' => 'Bovenliggende installatie',
        'condition' => 'Conditie',
        'service_status' => 'Bedrijfsstatus',
        'warranty_until' => 'Garantie tot',
        'installed_at' => 'Geïnstalleerd op',
        'decommissioned_at' => 'Buiten gebruik sinds',
        'purchase_price' => 'Aankoopprijs',
        'category' => 'Discipline',
    ],

    'condition' => [
        'good' => 'Goed',
        'fair' => 'Redelijk',
        'poor' => 'Slecht',
    ],

    'service_status' => [
        'in_operation' => 'In bedrijf',
        'out_of_operation' => 'Buiten bedrijf',
        'in_storage' => 'In opslag',
        'disposed' => 'Afgevoerd',
    ],

    'filters' => [
        'all' => 'Alle',
        'include_sub_locations' => 'incl. onderliggende locaties',
        'classification' => 'Classificatie',
        'maintenance_overdue' => 'Achterstallig',
        'due_within' => 'Vervalt binnen :days dagen',
        'maintenance_none' => 'Geen onderhoud gepland',
        'warranty_expired' => 'Verlopen',
        'warranty_expiring' => 'Verloopt binnen 90 dagen',
        'warranty_valid' => 'Geldig',
        'not_specified' => 'Niet opgegeven',
        'installed_from' => 'Geïnstalleerd vanaf',
        'installed_until' => 'Geïnstalleerd tot',
        'hierarchy' => 'Assethiërarchie',
        'top_level_only' => 'Alleen hoofdassets',
        'sub_assets_only' => 'Alleen subassets',
        'decommissioned' => 'Buiten gebruik gesteld',
        'active' => 'In gebruik',
        'bim' => 'BIM-model',
        'bim_linked' => 'Gekoppeld aan BIM-element',
        'bim_not_linked' => 'Zonder BIM-koppeling',
    ],

    'import' => [
        'help' => [
            'asset_number' => 'Alleen voor het bijwerken van bestaande assets (optie „Bestaande assets bijwerken”). Nieuwe assets krijgen automatisch een nummer.',
            'property' => 'Objectnummer.',
            'location' => 'Naam of pad van de locatie in het object, bijv. „Gebouw A › BG › Gang” („/” of „>” mag ook).',
            'parent_asset' => 'Assetnummer of naam van het bovenliggende asset in hetzelfde object.',
        ],
        'options' => [
            'update_existing' => 'Bestaande assets bijwerken via het assetnummer (lege cellen laten waarden ongewijzigd)',
            'create_missing' => 'Onbekende assettypes en bedrijven automatisch aanmaken',
        ],
        'template' => [
            'action' => 'Importsjabloon',
            'heading' => 'Importsjabloon downloaden',
            'description' => 'CSV-bestand met alle standaardkolommen. Kies assettypes om ook hun typespecifieke velden (bijv. vulling van een brandblusser) als kolommen te krijgen. Extra velden worden bij de import automatisch herkend aan de kolomkop.',
            'download' => 'Downloaden',
            'asset_types' => 'Assettypes met extra velden',
        ],
        'completed_title' => 'Assetimport voltooid',
        'completed_body' => '{0} Er is geen asset geïmporteerd.|{1} :count asset is geïmporteerd.|[2,*] :count assets zijn geïmporteerd.',
        'failed_rows' => '{1} :count rij is mislukt.|[2,*] :count rijen zijn mislukt.',
        'errors' => [
            'no_organization' => 'Geen tenant ingesteld voor de import.',
            'property_not_found' => 'Object „:value” niet gevonden.',
            'location_without_property' => 'Locatie „:value” kan zonder object niet worden toegewezen.',
            'location_not_found' => 'Locatie „:value” niet gevonden in het object.',
            'location_ambiguous' => 'Locatie „:value” is niet eenduidig – geef het pad op (bijv. „Gebouw A › BG › Gang”).',
            'asset_type_not_found' => 'Assettype „:value” niet gevonden.',
            'parent_not_found' => 'Bovenliggend asset „:value” niet gevonden.',
            'company_not_found' => 'Bedrijf „:value” niet gevonden.',
            'invalid_number' => '„:value” is geen geldig getal.',
            'invalid_boolean' => '„:value” is geen geldige ja/nee-waarde.',
            'invalid_date' => '„:value” is geen geldige datum.',
            'invalid_option' => '„:value” is geen geldige keuze.',
        ],
    ],

];
