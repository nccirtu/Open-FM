<?php

return [

    'tab' => 'Energy',

    'meter' => [
        'model' => 'Meter',
        'models' => 'Meters',
        'measured_assets' => 'Consumers',
        'supplied_nodes' => 'Supplied rooms',
    ],

    'reading' => [
        'model' => 'Reading',
        'models' => 'Readings',
    ],

    'readings' => [
        'none' => 'No reading yet',
    ],

    'goal' => [
        'model' => 'Goal',
        'models' => 'Goals',
        'all_properties' => 'All properties',
        'asset' => 'Asset',
        'max' => 'max.',
        'vs_year' => 'vs. :year',
        'actual' => 'to date',
        'forecast' => 'Forecast',
        'target' => 'Target',
        'empty' => 'No goals have been set for this year yet.',
    ],

    'pages' => [
        'record_readings' => 'Read meters',
    ],

    'sections' => [
        'master' => 'Master data',
        'formula' => 'Calculation',
    ],

    'fields' => [
        'name' => 'Name',
        'number' => 'Meter number',
        'medium' => 'Energy type',
        'kind' => 'Meter type',
        'parent' => 'Parent meter',
        'unit' => 'Register unit',
        'factor' => 'Conversion factor',
        'location' => 'Location',
        'device_asset' => 'Meter device (asset)',
        'market_location_id' => 'Supply point ID',
        'calibration_valid_until' => 'Calibration valid until',
        'is_active' => 'Active',
        'notes' => 'Notes',
        'sign' => 'Sign',
        'share' => 'Share',
        'last_reading' => 'Last reading',
        'read_at' => 'Read at',
        'value' => 'Meter reading',
        'photo' => 'Photo of the meter',
        'note' => 'Remark',
        'source' => 'Source',
        'status' => 'Check',
        'created_by' => 'Recorded by',
        'consumption_year' => 'Consumption :year',
        'goal_scope' => 'Applies to',
        'goal_type' => 'Goal type',
        'goal_period' => 'Period',
        'year' => 'Year',
        'target_value' => 'Target value',
        'baseline_year' => 'Baseline year',
        'progress' => 'Progress',
    ],

    'help' => [
        'parent' => 'Parent meter in the meter tree, e.g. the main meter. Leave empty for main meters.',
        'factor' => 'Converts the meter reading to kWh, e.g. for gas: calorific value × volume correction factor (around 10.5). For kWh meters: 1.',
        'device_asset' => 'Optional: the device as an asset, e.g. for its nameplate, QR label and maintenance.',
        'formula' => 'A calculated meter has no device. Its consumption is derived from other meters, e.g. main meter minus all sub-meters.',
        'share' => 'If the meter measures several assets or areas, its consumption is split according to these shares.',
        'goal_scope' => 'Leave empty for a goal covering all properties.',
        'market_location_id' => 'ID of the grid connection assigned by the grid operator (printed on every electricity and gas bill; in Germany the 11-digit MaLo-ID). Main meters only, e.g. to match bills and grid-operator data.',
    ],

    'medium' => [
        'electricity' => 'Electricity',
        'gas' => 'Gas',
        'heat' => 'Heat',
        'cold' => 'Cooling',
        'water' => 'Water',
        'oil' => 'Heating oil',
        'pv_generation' => 'PV generation',
    ],

    'kind' => [
        'main' => 'Main meter',
        'sub' => 'Sub-meter',
        'virtual' => 'Calculated meter',
    ],

    'source' => [
        'manual' => 'Mobile / web',
        'import' => 'Import',
        'api' => 'API',
    ],

    'status' => [
        'plausible' => 'plausible',
        'suspicious' => 'suspicious',
    ],

    'goal_type' => [
        'max_consumption' => 'Maximum consumption',
        'max_intensity' => 'Intensity per m²',
        'reduction' => 'Reduction vs. baseline year',
    ],

    'goal_period' => [
        'year' => 'Year',
        'month' => 'per month',
    ],

    'goal_status' => [
        'good' => 'on track',
        'warn' => 'at risk',
        'crit' => 'exceeded',
        'unknown' => 'no baseline',
    ],

    'plausibility' => [
        'decreased' => 'The reading is lower than the previous one. Typo or meter replacement?',
        'unusually_high' => 'Unusually high: :actual :unit per day, typically around :expected :unit. Please check and take a photo.',
        'short' => [
            'decreased' => 'lower than before',
            'unusually_high' => 'unusually high',
        ],
    ],

    'actions' => [
        'record_reading' => 'Record reading',
        'save_reading' => 'Save reading',
        'add_formula_term' => 'Add meter',
        'attach_asset' => 'Assign asset',
        'attach_node' => 'Assign room',
        'edit_share' => 'Change share',
        'import_readings' => 'Import readings',
        'open_in_energy' => 'Open in Energy',
        'all_goals' => 'All goals',
    ],

    'notifications' => [
        'saved' => 'Reading saved',
        'saved_suspicious' => 'Reading saved, but flagged as suspicious',
    ],

    'errors' => [
        'virtual_meter' => 'Readings cannot be recorded for calculated meters.',
    ],

    'filters' => [
        'calibration_due' => 'Calibration due (180 days)',
        'from' => 'From',
        'until' => 'Until',
        'period' => 'Period',
        'all_usage_types' => 'All usage types',
        'all_properties' => 'All properties',
        'periods' => [
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'last_30_days' => 'Last 30 days',
            'this_quarter' => 'This quarter',
            'this_year' => 'This year',
            'last_year' => 'Last year',
            'last_12_months' => 'Last 12 months',
            'custom' => 'Custom range',
        ],
    ],

    'stats' => [
        'energy_in_period' => 'Energy in period',
        'water_in_period' => 'Water in period',
        'goals_on_track' => 'Goals on track',
        'suspicious_readings' => 'Suspicious readings',
        'calibration_due' => 'Calibration due',
        'next_180_days' => 'in the next 180 days',
        'vs_previous_year' => ':change% vs. previous year',
        'no_comparison' => 'no previous-year value',
        'year_to_date' => 'Consumption :year to date',
        'previous_year_to_date' => 'Same period :year',
    ],

    'charts' => [
        'consumption_trend' => 'Consumption per month',
        'consumption_per_day' => 'Consumption per day',
        'meter_consumption' => 'Consumption',
        'by_asset' => 'Largest consumers',
        'consumption_in' => 'Consumption in :unit',
        'previous_year' => 'Previous year',
        'last_30_days' => 'Last 30 days',
        'last_12_months' => 'Last 12 months',
    ],

    'flow' => [
        'title' => 'Energy flow',
        'description' => 'From the main meters via the sub-meters to the assets. Width = quantity.',
        'empty' => 'There is no consumption for this property and energy type in this period.',
        'unassigned' => 'unassigned',
    ],

    'consumers' => [
        'title' => 'Consumers & energy flow',
        'description' => 'Where this meter\'s consumption goes: through sub-meters to the assigned assets. Width = quantity.',
        'empty' => 'No consumers are assigned to this meter yet. Assign the assets it measures in the “Consumers” tab, or add sub-meters below it.',
        'no_consumption' => 'There is no consumption for this meter in the selected year yet.',
        'list' => 'Largest consumers',
        'hint' => 'If a meter measures several assets, its consumption is split by the stored shares. “:unassigned” is the remainder not explained by any asset or sub-meter.',
    ],

    'comparison' => [
        'title' => 'Properties compared',
        'energy' => 'Energy in period',
        'intensity' => 'per m²',
        'change' => 'vs. previous year',
    ],

    'calibration' => [
        'title' => 'Calibration due',
        'empty' => 'No calibration due in the next 180 days',
    ],

    'suspicious' => [
        'title' => 'Suspicious readings',
        'empty' => 'No suspicious readings in this period',
    ],

    'import' => [
        'help' => [
            'meter' => 'Meter number, or alternatively the unique name of the meter.',
        ],
        'completed_title' => 'Reading import completed',
        'completed_body' => '{0} No reading was imported.|{1} :count reading was imported.|[2,*] :count readings were imported.',
        'errors' => [
            'meter_not_found' => 'Meter “:value” not found.',
            'meter_ambiguous' => 'Meter “:value” is ambiguous – please use the meter number.',
        ],
    ],

];
