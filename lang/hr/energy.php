<?php

return [

    'tab' => 'Energija',

    'meter' => [
        'model' => 'Brojilo',
        'models' => 'Brojila',
        'measured_assets' => 'Potrošači',
        'supplied_nodes' => 'Opskrbljene prostorije',
    ],

    'reading' => [
        'model' => 'Očitanje',
        'models' => 'Očitanja',
    ],

    'readings' => [
        'none' => 'Još nema očitanja',
    ],

    'goal' => [
        'model' => 'Cilj',
        'models' => 'Ciljevi',
        'all_properties' => 'Svi objekti',
        'asset' => 'Uređaj',
        'max' => 'maks.',
        'vs_year' => 'u odnosu na :year',
        'actual' => 'dosad',
        'forecast' => 'Projekcija',
        'target' => 'Cilj',
        'empty' => 'Za ovu godinu još nisu postavljeni ciljevi.',
    ],

    'pages' => [
        'record_readings' => 'Očitavanje',
    ],

    'sections' => [
        'master' => 'Matični podaci',
        'formula' => 'Izračun',
    ],

    'fields' => [
        'name' => 'Naziv',
        'number' => 'Broj brojila',
        'medium' => 'Vrsta energije',
        'kind' => 'Vrsta brojila',
        'parent' => 'Nadređeno brojilo',
        'unit' => 'Jedinica brojčanika',
        'factor' => 'Faktor preračunavanja',
        'location' => 'Lokacija',
        'device_asset' => 'Uređaj brojila (sredstvo)',
        'market_location_id' => 'Šifra obračunskog mjernog mjesta',
        'calibration_valid_until' => 'Ovjera vrijedi do',
        'is_active' => 'Aktivno',
        'notes' => 'Bilješke',
        'sign' => 'Predznak',
        'share' => 'Udio',
        'last_reading' => 'Zadnje stanje',
        'read_at' => 'Vrijeme',
        'value' => 'Stanje brojila',
        'photo' => 'Fotografija brojila',
        'note' => 'Napomena',
        'source' => 'Izvor',
        'status' => 'Provjera',
        'created_by' => 'Unio/la',
        'consumption_year' => 'Potrošnja :year',
        'goal_scope' => 'Odnosi se na',
        'goal_type' => 'Vrsta cilja',
        'goal_period' => 'Razdoblje',
        'year' => 'Godina',
        'target_value' => 'Ciljana vrijednost',
        'baseline_year' => 'Bazna godina',
        'progress' => 'Napredak',
    ],

    'help' => [
        'parent' => 'Nadređeno brojilo u stablu brojila, npr. glavno brojilo. Prazno kod glavnih brojila.',
        'factor' => 'Preračunava stanje brojila u kWh, npr. kod plina ogrjevna vrijednost × korekcijski faktor (oko 10,5). Kod kWh brojila 1.',
        'device_asset' => 'Neobavezno: uređaj kao sredstvo, npr. za natpisnu pločicu, QR naljepnicu i održavanje.',
        'formula' => 'Izračunato brojilo nema uređaj. Njegova potrošnja proizlazi iz drugih brojila, npr. glavno brojilo minus sva podbrojila.',
        'share' => 'Ako brojilo mjeri više uređaja ili područja, njegova se potrošnja raspodjeljuje prema tim udjelima.',
        'goal_scope' => 'Ostavite prazno za cilj koji obuhvaća sve objekte.',
        'market_location_id' => 'Oznaka priključka koju dodjeljuje operator mreže (navedena na svakom računu za struju i plin). Samo za glavna brojila, npr. za usklađivanje računa i podataka operatora mreže.',
    ],

    'medium' => [
        'electricity' => 'Električna energija',
        'gas' => 'Plin',
        'heat' => 'Toplina',
        'cold' => 'Hlađenje',
        'water' => 'Voda',
        'oil' => 'Loživo ulje',
        'pv_generation' => 'FN proizvodnja',
    ],

    'kind' => [
        'main' => 'Glavno brojilo',
        'sub' => 'Podbrojilo',
        'virtual' => 'Izračunato brojilo',
    ],

    'source' => [
        'manual' => 'Mobitel / web',
        'import' => 'Uvoz',
        'api' => 'Sučelje',
    ],

    'status' => [
        'plausible' => 'uvjerljivo',
        'suspicious' => 'sumnjivo',
    ],

    'goal_type' => [
        'max_consumption' => 'Najveća količina',
        'max_intensity' => 'Pokazatelj po m²',
        'reduction' => 'Ušteda u odnosu na baznu godinu',
    ],

    'goal_period' => [
        'year' => 'Godina',
        'month' => 'mjesečno',
    ],

    'goal_status' => [
        'good' => 'prema planu',
        'warn' => 'ugroženo',
        'crit' => 'prekoračeno',
        'unknown' => 'nema baze',
    ],

    'plausibility' => [
        'decreased' => 'Stanje je manje od prethodnog. Pogreška pri unosu ili zamjena brojila?',
        'unusually_high' => 'Neuobičajeno visoko: :actual :unit dnevno, uobičajeno je oko :expected :unit. Provjerite i snimite fotografiju.',
        'short' => [
            'decreased' => 'manje nego prije',
            'unusually_high' => 'neuobičajeno visoko',
        ],
    ],

    'actions' => [
        'record_reading' => 'Unesi očitanje',
        'save_reading' => 'Spremi stanje',
        'add_formula_term' => 'Dodaj brojilo',
        'attach_asset' => 'Dodijeli uređaj',
        'attach_node' => 'Dodijeli prostoriju',
        'edit_share' => 'Promijeni udio',
        'import_readings' => 'Uvezi očitanja',
        'open_in_energy' => 'Otvori u području Energija',
        'all_goals' => 'Svi ciljevi',
    ],

    'notifications' => [
        'saved' => 'Stanje spremljeno',
        'saved_suspicious' => 'Stanje spremljeno, ali je sumnjivo',
    ],

    'errors' => [
        'virtual_meter' => 'Za izračunata brojila ne mogu se unositi stanja.',
    ],

    'filters' => [
        'calibration_due' => 'Ovjera dospijeva (180 dana)',
        'from' => 'Od',
        'until' => 'Do',
        'period' => 'Razdoblje',
        'all_usage_types' => 'Sve vrste korištenja',
        'all_properties' => 'Svi objekti',
        'periods' => [
            'this_month' => 'Ovaj mjesec',
            'last_month' => 'Prošli mjesec',
            'last_30_days' => 'Zadnjih 30 dana',
            'this_quarter' => 'Ovo tromjesečje',
            'this_year' => 'Ova godina',
            'last_year' => 'Prošla godina',
            'last_12_months' => 'Zadnjih 12 mjeseci',
            'custom' => 'Vlastito razdoblje',
        ],
    ],

    'stats' => [
        'energy_in_period' => 'Energija u razdoblju',
        'water_in_period' => 'Voda u razdoblju',
        'goals_on_track' => 'Ciljevi prema planu',
        'suspicious_readings' => 'Sumnjiva očitanja',
        'calibration_due' => 'Ovjera dospijeva',
        'next_180_days' => 'u sljedećih 180 dana',
        'vs_previous_year' => ':change % u odnosu na prethodnu godinu',
        'no_comparison' => 'nema vrijednosti za prethodnu godinu',
        'year_to_date' => 'Potrošnja :year dosad',
        'previous_year_to_date' => 'Isto razdoblje :year',
    ],

    'charts' => [
        'consumption_trend' => 'Potrošnja po mjesecima',
        'consumption_per_day' => 'Potrošnja po danu',
        'meter_consumption' => 'Potrošnja',
        'by_asset' => 'Najveći potrošači',
        'consumption_in' => 'Potrošnja u :unit',
        'previous_year' => 'Prethodna godina',
        'last_30_days' => 'Zadnjih 30 dana',
        'last_12_months' => 'Zadnjih 12 mjeseci',
    ],

    'flow' => [
        'title' => 'Tok energije',
        'description' => 'Od glavnih brojila preko podbrojila do uređaja. Širina = količina.',
        'empty' => 'Za ovaj objekt i ovu vrstu energije u tom razdoblju nema potrošnje.',
        'unassigned' => 'nedodijeljeno',
    ],

    'consumers' => [
        'title' => 'Potrošači i tok energije',
        'description' => 'Kamo odlazi potrošnja ovog brojila: preko podbrojila do dodijeljenih postrojenja. Širina = količina.',
        'empty' => 'Ovom brojilu još nisu dodijeljeni potrošači. Dodijelite postrojenja koja mjeri u kartici „Potrošači” ili ispod njega postavite podbrojila.',
        'no_consumption' => 'Za ovo brojilo u odabranoj godini još nema potrošnje.',
        'list' => 'Najveći potrošači',
        'hint' => 'Ako brojilo mjeri više postrojenja, potrošnja se dijeli prema unesenim udjelima. „:unassigned” je ostatak koji ne objašnjava nijedno postrojenje ni podbrojilo.',
    ],

    'comparison' => [
        'title' => 'Usporedba objekata',
        'energy' => 'Energija u razdoblju',
        'intensity' => 'po m²',
        'change' => 'u odnosu na prethodnu godinu',
    ],

    'calibration' => [
        'title' => 'Ovjera dospijeva',
        'empty' => 'Nijedna ovjera ne dospijeva u sljedećih 180 dana',
    ],

    'suspicious' => [
        'title' => 'Sumnjiva očitanja',
        'empty' => 'Nema sumnjivih očitanja u razdoblju',
    ],

    'import' => [
        'help' => [
            'meter' => 'Broj brojila, alternativno jedinstveni naziv brojila.',
        ],
        'completed_title' => 'Uvoz očitanja završen',
        'completed_body' => '{0} Nije uvezeno nijedno očitanje.|{1} Uvezeno je :count očitanje.|[2,*] Broj uvezenih očitanja: :count.',
        'errors' => [
            'meter_not_found' => 'Brojilo „:value“ nije pronađeno.',
            'meter_ambiguous' => 'Brojilo „:value“ nije jednoznačno – upotrijebite broj brojila.',
        ],
    ],

];
