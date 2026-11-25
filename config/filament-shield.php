<?php

declare(strict_types=1);
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\PropertyResource;
use App\Filament\Waste\Resources\WastePickups\WastePickupResource;
use App\Models\Organization;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Cafm\FormBuilder\Filament\Pages\FillFormPage;
use Cafm\FormBuilder\Filament\Pages\FormSubmissionsPage;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [

    'shield_resource' => [
        'slug' => 'shield/roles',
        'show_model_path' => false,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => false,
        ],
    ],

    'tenant_model' => Organization::class,

    'auth_provider_model' => 'App\\Models\\User',

    'super_admin' => [
        'enabled' => false,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    'panel_user' => [
        'enabled' => false,
        'name' => 'panel_user',
    ],

    'permissions' => [
        'separator' => ':',
        'case' => 'pascal',
        'generate' => true,
        'format_custom_permission_keys' => true,
    ],

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => true,
        'generate' => true,
        'methods' => [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'forceDeleteAny',
            'restoreAny',
            'reorder',
        ],
    ],

    'localization' => [
        'enabled' => true,
        'key' => 'shield.permissions',
    ],

    'resources' => [
        'subject' => 'model',
        'manage' => [
            RoleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],
            PropertyResource::class => ['viewAll', 'createAll', 'updateAll', 'deleteAll'],
            ProjectResource::class => ['viewAll', 'createAll', 'updateAll', 'deleteAll'],
            WastePickupResource::class => ['confirm'],
        ],
        'exclude' => [
        ],
    ],

    'pages' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            Dashboard::class,
            FillFormPage::class,
            FormSubmissionsPage::class,
        ],
    ],

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    'custom_permissions' => [],

    'discovery' => [
        'discover_all_resources' => true,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],

    'register_role_policy' => true,

];
